<?php
namespace Tests\Feature;

use App\Models\{User,Author,Category,Book,BookCopy,BorrowRecord,RentalPolicy};
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryFlowTest extends TestCase
{
    use RefreshDatabase;

    private function fixtures(): array
    {
        $admin=User::create(['user_code'=>'NV0001','name'=>'Admin','email'=>'admin@test.local','phone'=>'0901111111','password'=>'Test@12345','role'=>'admin','status'=>'active']);
        $reader=User::create(['user_code'=>'DG0001','name'=>'Reader','email'=>'reader@test.local','phone'=>'0902222222','password'=>'Test@12345','role'=>'user','status'=>'active']);
        $author=Author::create(['name'=>'Tác giả']);
        $category=Category::create(['name'=>'Văn học']);
        $book=Book::create(['book_code'=>'S0001','title'=>'Sách thử','author_id'=>$author->id,'category_id'=>$category->id,'cover_price'=>100000,'is_active'=>true]);
        $copy=BookCopy::create(['book_id'=>$book->id,'copy_code'=>'S0001-01','status'=>'available']);
        $policy=RentalPolicy::create(['effective_at'=>today(),'daily_rate'=>3000,'student_discount'=>20,'deposit_rate'=>50,'member_deposit_rate'=>0,'late_fee'=>5000,'damage_rate'=>30,'lost_rate'=>100,'lost_admin_fee'=>20000,'card_fee_monthly'=>80000,'max_days'=>30,'max_books'=>3,'hold_hours'=>24,'is_active'=>true]);
        return compact('admin','reader','book','copy','policy');
    }

    public function test_guest_can_browse_but_cannot_reserve(): void
    {
        $f=$this->fixtures();
        $this->get(route('books.index'))->assertOk();
        $this->post(route('borrow.reserve'),['book_id'=>$f['book']->id,'rental_days'=>10])->assertRedirect(route('login'));
    }

    public function test_reader_cannot_access_admin(): void
    {
        $f=$this->fixtures();
        $this->actingAs($f['reader'])->get(route('books.admin.index'))->assertForbidden();
    }

    public function test_reservation_handover_and_return(): void
    {
        $f=$this->fixtures();
        $this->actingAs($f['reader'])->post(route('borrow.reserve'),['book_id'=>$f['book']->id,'rental_days'=>10])->assertRedirect(route('history.index'));
        $record=BorrowRecord::firstOrFail();
        $this->assertSame('pending',$record->status);
        $this->assertSame('reserved',$f['copy']->fresh()->status);
        $this->assertNull($record->cover_price);
        $this->actingAs($f['admin'])->post(route('borrow.handover',$record),['copy_code'=>'S0001-01','method'=>'cash','expected_fee'=>30000,'expected_deposit'=>50000])->assertSessionHasNoErrors();
        $this->assertSame('borrowing',$record->fresh()->status);
        $this->assertSame(30000,(int)$record->fresh()->rental_fee);
        $this->assertSame(50000,(int)$record->fresh()->deposit_amount);
        $this->post(route('returns.store',$record),['copy_code'=>'S0001-01','condition'=>'good','method'=>'cash','expected_late'=>0,'expected_damage'=>0,'expected_deduct'=>0,'expected_refund'=>50000])->assertSessionHasNoErrors();
        $this->assertSame('returned',$record->fresh()->status);
        $this->assertSame('available',$f['copy']->fresh()->status);
    }

    public function test_duplicate_reservation_same_book_is_rejected(): void
    {
        $f=$this->fixtures();
        $this->actingAs($f['reader'])->post(route('borrow.reserve'),['book_id'=>$f['book']->id,'rental_days'=>10]);
        $this->post(route('borrow.reserve'),['book_id'=>$f['book']->id,'rental_days'=>10])->assertRedirect(route('books.show', ['book'=>$f['book']->id, 'days'=>10]))->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('borrow_records',1);
    }

    public function test_pricing_examples(): void
    {
        $f=$this->fixtures();
        $pricing=app(PricingService::class);
        $quote=$pricing->rental($f['reader'],100000,10,$f['policy']);
        $this->assertSame(30000,$quote['rental_fee']);
        $this->assertSame(50000,$quote['deposit_amount']);
        $record=new BorrowRecord(['rental_days'=>10,'deposit_amount'=>50000,'cover_price'=>100000,'due_date'=>today()->subDays(3)->toDateString()]);
        $record->setRelation('rentalPolicy',$f['policy']);
        $result=$pricing->returns($record,'good');
        $this->assertSame(15000,$result['late_fee']);
        $this->assertSame(35000,$result['refund']);
        $lost=$pricing->returns($record,'lost');
        $this->assertSame(120000,$lost['damage_fee']);
        $this->assertSame(85000,$lost['extra_payment']);
    }
}
