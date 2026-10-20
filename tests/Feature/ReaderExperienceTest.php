<?php
namespace Tests\Feature;

use App\Models\{Book, BookCopy, BorrowRecord, DepositTransaction, MembershipCard, RentalPolicy, StudentVerification, User};
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function setupLibrary(): array
    {
        $this->seed();
        \App\Models\RentalPolicy::active()->update(['deposit_rate'=>50]);
        $reader = User::where('role', 'user')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();
        $book = Book::where('book_code', 'S0002')->firstOrFail();
        $book->update(['cover_price' => 150000]);
        $copy = BookCopy::create(['book_id'=>$book->id,'copy_code'=>'REAL-001','status'=>'available']);
        return [$reader, $admin, $book, $copy];
    }

    public function test_quotes_follow_selected_days_and_reject_invalid_values(): void
    {
        [$reader, , $book] = $this->setupLibrary();
        $this->actingAs($reader);
        foreach ([1,10,30] as $days) {
            $this->getJson(route('books.quote', ['book'=>$book,'days'=>$days]))->assertOk()
                ->assertJsonPath('days', $days)->assertJsonPath('rental_fee', $days*3000)
                ->assertJsonPath('deposit_amount',75000)->assertJsonPath('total_amount', $days*3000+75000);
        }
        foreach ([0,31,'1.5','abc'] as $days) $this->getJson(route('books.quote',['book'=>$book,'days'=>$days]))->assertUnprocessable()->assertJsonValidationErrors('days');
        $this->get(route('books.show',$book))->assertOk()->assertSee('rental-quote.js')->assertSee('Tiền cần cọc')->assertDontSee('Tạm tính cho');
    }

    public function test_quote_applies_card_only_when_it_covers_the_full_rental(): void
    {
        [$reader,$admin,$book] = $this->setupLibrary();
        MembershipCard::create(['user_id'=>$reader->id,'issued_by'=>$admin->id,'card_code'=>'TV-OWN','months'=>1,'fee_amount'=>80000,'issued_at'=>today(),'expires_at'=>today()->addDays(12),'status'=>'active']);
        $this->actingAs($reader)->getJson(route('books.quote',['book'=>$book,'days'=>10]))->assertOk()->assertJsonPath('rental_fee',0)->assertJsonPath('deposit_amount',75000)->assertJsonPath('total_amount',75000);
        $this->getJson(route('books.quote',['book'=>$book,'days'=>14]))->assertOk()->assertJsonPath('rental_fee',42000)->assertJsonPath('total_amount',117000);
    }

    public function test_quote_includes_student_discount_without_a_card(): void
    {
        [$reader,$admin,$book] = $this->setupLibrary();
        StudentVerification::create(['user_id'=>$reader->id,'school_name'=>'Trường đại học','student_code'=>'SV-001','status'=>'approved','verified_by'=>$admin->id,'verified_at'=>today(),'expires_at'=>today()->addYear()]);
        $this->actingAs($reader)->getJson(route('books.quote',['book'=>$book,'days'=>10]))->assertOk()
            ->assertJsonPath('discount_amount',6000)->assertJsonPath('rental_fee',24000)->assertJsonPath('total_amount',99000);
    }

    public function test_repeated_reservations_show_errors_and_leave_inventory_unchanged(): void
    {
        [$reader, , $book, $copy] = $this->setupLibrary();
        $this->actingAs($reader)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>10])->assertRedirect(route('history.index'));
        $record = BorrowRecord::firstOrFail();
        foreach (['pending','borrowing','overdue'] as $status) {
            $record->update(['status'=>$status]);
            $this->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>10])
                ->assertRedirect(route('books.show',['book'=>$book->id,'days'=>10]))->assertSessionHasErrors('reservation');
        }
        $this->assertDatabaseCount('borrow_records',1);
        $this->assertSame('reserved',$copy->fresh()->status);
        $record->update(['status'=>'returned']);
        $copy->refresh()->update(['status'=>'available']);
        $this->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>10])->assertRedirect(route('history.index'));
        $this->assertDatabaseCount('borrow_records',2);
    }

    public function test_reader_membership_page_is_private(): void
    {
        [$reader,$admin] = $this->setupLibrary();
        $other = User::create(['user_code'=>'DG9999','name'=>'Other','email'=>'other@test.local','phone'=>'0903333333','password'=>'Test@12345','role'=>'user','status'=>'active']);
        foreach ([[$reader,'TV-MY'],[$other,'TV-OTHER']] as [$owner,$code]) MembershipCard::create(['user_id'=>$owner->id,'issued_by'=>$admin->id,'card_code'=>$code,'months'=>1,'fee_amount'=>80000,'issued_at'=>today(),'expires_at'=>today()->addMonth(),'status'=>'active']);
        $this->get(route('cards.mine'))->assertRedirect(route('login'));
        $this->actingAs($reader)->get(route('cards.mine'))->assertOk()->assertSee('TV-MY')->assertDontSee('TV-OTHER');
        $this->get(route('books.index'))->assertSee('Thẻ thành viên');
        $this->get(route('cards.index'))->assertForbidden();
    }

    public function test_return_refunds_the_remaining_ledger_balance_once_and_displays_it(): void
    {
        [$reader,$admin,$book,$copy] = $this->setupLibrary();
        $this->actingAs($reader)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>10]);
        $record = BorrowRecord::firstOrFail();
        $this->actingAs($admin)->post(route('borrow.handover',$record),['copy_code'=>$copy->copy_code,'method'=>'cash','expected_fee'=>30000,'expected_deposit'=>75000])->assertSessionHasNoErrors();
        $hold = DepositTransaction::where('type','hold')->firstOrFail();
        $this->post(route('deposits.adjust',$hold),['type'=>'adjust_down','amount'=>5000,'reason'=>'Điều chỉnh số tiền thực nhận'])->assertSessionHasNoErrors();
        $this->assertSame(70000,$record->fresh()->deposit_summary['balance']);
        $quote = app(PricingService::class)->returns($record->fresh(),'good');
        $this->assertSame(70000,$quote['refund']);
        $data=['condition'=>'good','copy_code'=>$copy->copy_code,'method'=>'cash','refund_method'=>'transfer','expected_late'=>0,'expected_damage'=>0,'expected_deduct'=>0,'expected_refund'=>70000];
        $this->post(route('returns.store',$record),$data)->assertSessionHasNoErrors();
        $summary = $record->fresh()->deposit_summary;
        $this->assertSame(70000,$summary['refunded']);
        $this->assertSame(0,$summary['balance']);
        $this->assertDatabaseHas('deposit_transactions',['borrow_record_id'=>$record->id,'type'=>'refund','amount'=>70000,'reason'=>'Hoàn lại tiền cọc bằng chuyển khoản']);
        $this->get(route('payments.index'))->assertOk()->assertSee('Hoàn lại tiền cọc')->assertSee('70.000');
        $this->actingAs($reader)->get(route('history.index'))->assertOk()->assertSee('Cọc đã hoàn lại')->assertSee('70.000');
        $this->actingAs($admin)->post(route('returns.store',$record),$data)->assertRedirect(route('returns.index'))->assertSessionHasNoErrors();
        $this->assertSame(1,DepositTransaction::where('type','refund')->count());
    }
}
