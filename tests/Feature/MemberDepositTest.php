<?php
namespace Tests\Feature;
use App\Models\{Book, BookCopy, BorrowRecord, DepositTransaction, MembershipCard, Payment, RentalPolicy, User};
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MemberDepositTest extends TestCase
{
    use RefreshDatabase;
    private function library(): array
    {
        $this->seed();
        $reader=User::where('role','user')->firstOrFail();
        $admin=User::where('role','admin')->firstOrFail();
        $book=Book::where('book_code','S0002')->firstOrFail();
        $book->update(['cover_price'=>150000]);
        $copy=BookCopy::create(['book_id'=>$book->id,'copy_code'=>'MEMBER-01','status'=>'available']);
        $card=MembershipCard::create(['user_id'=>$reader->id,'issued_by'=>$admin->id,'card_code'=>'TV-DEP','months'=>1,'fee_amount'=>80000,'issued_at'=>today()->toDateString(),'expires_at'=>today()->addMonth()->toDateString(),'status'=>'active']);
        return [$reader,$admin,$book,$copy,$card];
    }
    public function test_member_pays_standard_deposit_even_with_old_zero_member_rate(): void
    {
        [$reader,,$book]=$this->library();
        $policy=RentalPolicy::active();
        $policy->update(['member_deposit_rate'=>0]);
        $this->actingAs($reader)->getJson(route('books.quote',['book'=>$book,'days'=>10]))->assertOk()
            ->assertJsonPath('rental_fee',0)->assertJsonPath('discount_amount',0)
            ->assertJsonPath('deposit_amount',150000)->assertJsonPath('total_amount',150000);
        $this->get(route('cards.mine'))->assertOk()->assertSee('vẫn đặt cọc 100%');
        $policy->update(['deposit_rate'=>60]);
        $this->assertSame(90000,app(PricingService::class)->rental($reader,150000,10)['deposit_amount']);
        $policy->update(['deposit_rate'=>50]);
        $this->assertSame(76000,app(PricingService::class)->rental($reader,151000,10)['deposit_amount']);
    }
    public function test_member_handover_requires_deposit_and_return_refunds_it_once(): void
    {
        [$reader,$admin,$book,$copy]=$this->library();
        $this->actingAs($reader)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>10])->assertSessionHasNoErrors();
        $record=BorrowRecord::firstOrFail();
        $this->actingAs($admin)->post(route('borrow.handover',$record),['copy_code'=>$copy->copy_code,'method'=>'cash','expected_fee'=>0,'expected_deposit'=>0])->assertSessionHasErrors('handover');
        $this->assertSame('pending',$record->fresh()->status);
        $this->assertDatabaseCount('deposit_transactions',0);
        $this->post(route('borrow.handover',$record),['copy_code'=>$copy->copy_code,'method'=>'cash','expected_fee'=>0,'expected_deposit'=>150000])->assertSessionHasNoErrors();
        $this->assertSame(150000,(int)$record->fresh()->deposit_amount);
        $this->assertSame(150000,$record->fresh()->deposit_summary['balance']);
        $this->assertSame(0,Payment::where('type','rental_fee')->count());
        $data=['copy_code'=>$copy->copy_code,'condition'=>'good','method'=>'cash','refund_method'=>'cash','expected_late'=>0,'expected_damage'=>0,'expected_deduct'=>0,'expected_refund'=>150000];
        for($i=0;$i<2;$i++) $this->post(route('returns.store',$record),$data)->assertSessionHasNoErrors();
        $this->assertSame(0,$record->fresh()->deposit_summary['balance']);
        $this->assertSame(150000,$record->fresh()->deposit_summary['refunded']);
        $this->assertSame(1,DepositTransaction::where('type','hold')->count());
        $this->assertSame(1,DepositTransaction::where('type','refund')->count());
    }
    public function test_existing_loan_with_no_deposit_is_not_retroactively_charged(): void
    {
        [$reader,$admin,$book,$copy,$card]=$this->library();
        $copy->update(['status'=>'borrowed']);
        $record=BorrowRecord::create(['user_id'=>$reader->id,'book_copy_id'=>$copy->id,'membership_card_id'=>$card->id,'rental_policy_id'=>RentalPolicy::active()->id,'rental_days'=>10,'requested_at'=>now(),'borrow_date'=>today(),'due_date'=>today()->addDays(10),'status'=>'borrowing','cover_price'=>150000,'rental_fee'=>0,'discount_amount'=>0,'deposit_amount'=>0,'lent_by'=>$admin->id]);
        $this->actingAs($admin)->post(route('returns.store',$record),['copy_code'=>$copy->copy_code,'condition'=>'good','method'=>'cash','expected_late'=>0,'expected_damage'=>0,'expected_deduct'=>0,'expected_refund'=>0])->assertSessionHasNoErrors();
        $this->assertSame('returned',$record->fresh()->status);
        $this->assertSame(0,(int)$record->fresh()->deposit_amount);
        $this->assertDatabaseCount('deposit_transactions',0);
        $this->assertDatabaseCount('payments',0);
    }
    public function test_new_policy_preserves_old_loans_and_is_repeatable(): void
    {
        [$reader,$admin,$book,$copy,$card]=$this->library();
        $old=RentalPolicy::active();
        $old->update(['deposit_rate'=>50,'member_deposit_rate'=>0]);
        $record=BorrowRecord::create(['user_id'=>$reader->id,'book_copy_id'=>$copy->id,'membership_card_id'=>$card->id,'rental_policy_id'=>$old->id,'rental_days'=>10,'requested_at'=>now(),'borrow_date'=>today(),'due_date'=>today()->addDays(10),'status'=>'borrowing','cover_price'=>150000,'rental_fee'=>0,'discount_amount'=>0,'deposit_amount'=>75000,'lent_by'=>$admin->id]);
        DepositTransaction::create(['borrow_record_id'=>$record->id,'type'=>'hold','amount'=>75000,'processed_by'=>$admin->id,'processed_at'=>now()]);
        $migration=require database_path('migrations/2026_10_21_000003_set_standard_deposit_to_full_cover_price.php');
        $migration->up();
        $new=RentalPolicy::active();
        $this->assertNotSame($old->id,$new->id);
        $this->assertSame(100,(int)$new->deposit_rate);
        $this->assertSame(100,(int)$new->member_deposit_rate);
        $this->assertSame(50,(int)$old->fresh()->deposit_rate);
        $this->assertSame($old->id,$record->fresh()->rental_policy_id);
        $this->assertSame(75000,(int)$record->fresh()->deposit_amount);
        $this->assertSame(75000,app(PricingService::class)->returns($record->fresh(),'good')['refund']);
        $count=RentalPolicy::count();
        $migration->up();
        $this->assertSame($count,RentalPolicy::count());
        $quote=app(PricingService::class)->rental($reader,150001,10);
        $this->assertSame(150001,$quote['deposit_amount']);
        $card->update(['status'=>'suspended']);
        $quote=app(PricingService::class)->rental($reader,150001,10);
        $this->assertSame(150001,$quote['deposit_amount']);
        $this->assertSame(30000,$quote['rental_fee']);
    }

}
