<?php
namespace Tests\Feature;
use App\Models\{Book,BookCopy,BorrowRecord,Payment,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
class VietnameseStatisticsTest extends TestCase
{
    use RefreshDatabase;
    private function library(): array
    {
        $this->seed();
        return [User::where('role','user')->firstOrFail(),User::where('role','admin')->firstOrFail(),Book::orderBy('book_code')->firstOrFail()];
    }
    private function history(User $reader,Book $book,string $status,?string $borrowDate,string $requested='2026-10-02'): BorrowRecord
    {
        $copy=BookCopy::create(['book_id'=>$book->id,'copy_code'=>'ST-'.(BookCopy::count()+1),'status'=>'available','shelf_location'=>'A1']);
        return BorrowRecord::create(['user_id'=>$reader->id,'book_copy_id'=>$copy->id,'status'=>$status,'rental_days'=>3,'requested_at'=>$requested.' 10:00:00','borrow_date'=>$borrowDate]);
    }
    public function test_statistics_include_zero_books_and_count_only_handover_dates_in_period(): void
    {
        [$reader,$admin,$book]=$this->library();
        foreach(['borrowing','overdue','returned'] as $status) $this->history($reader,$book,$status,'2026-10-04');
        $this->history($reader,$book,'cancelled','2026-10-04');
        $this->history($reader,$book,'pending',null);
        $this->history($reader,$book,'returned','2026-09-30');
        $this->history($reader,$book,'returned','2026-10-01','2026-09-30');
        $response=$this->actingAs($admin)->get(route('statistics.index',['from'=>'2026-10-01','to'=>'2026-10-31']));
        $response->assertOk()->assertSee('Thống kê lượt mượn sách')->assertDontSee('Top 10')->assertSee('Đã trả')->assertSee('Có sẵn');
        $counts=$response->viewData('topBooks');
        $this->assertSame(Book::count(),$counts->total());
        $this->assertSame($book->id,$counts->first()->id);
        $this->assertSame(4,(int)$counts->first()->borrows_count);
        $this->assertSame(0,(int)$counts->getCollection()->last()->borrows_count);
        $this->get(route('statistics.index',['from'=>'2026-10-01','to'=>'2026-10-31','books_page'=>2]))->assertOk()->assertSee('Thống kê lượt mượn sách')->assertSee('Hiển thị')->assertDontSee('Showing');
        $this->get(route('borrow.index'))->assertOk()->assertSee('Đang mượn')->assertSee('Đã hủy')->assertDontSee('<td>returned</td>',false);
    }
    public function test_export_uses_same_full_book_statistics_and_vietnamese_statuses(): void
    {
        [$reader,$admin,$book]=$this->library();
        $this->history($reader,$book,'returned','2026-10-04');
        $this->history($reader,$book,'cancelled',null);
        Excel::fake();
        $this->actingAs($admin)->get(route('statistics.export',['from'=>'2026-10-01','to'=>'2026-10-31']))->assertOk();
        Excel::assertDownloaded('thong_ke_2026-10-01_2026-10-31.xlsx',function($export)use($book){
            $sheets=$export->sheets();
            $this->assertSame('Thống kê lượt mượn sách',$sheets[4]->title());
            $rows=$sheets[4]->array();
            $this->assertCount(Book::count()+1,$rows);
            $this->assertSame([$book->book_code,$book->title,1],$rows[1]);
            $this->assertSame(0,$rows[count($rows)-1][2]);
            $this->assertSame('Đã trả',$sheets[2]->array()[1][4]);
            $this->assertSame('Có sẵn',$sheets[3]->array()[1][0]);
            return true;
        });
    }
    public function test_daily_revenue_matches_summary_and_formats_vietnamese(): void
    {
        [$reader,$admin]=$this->library();
        foreach(['rental_fee'=>10000,'card_fee'=>80000,'late_fine'=>5000,'damage_fee'=>100000] as $type=>$amount) Payment::create(['user_id'=>$reader->id,'type'=>$type,'amount'=>$amount,'method'=>'cash','status'=>'paid','paid_at'=>'2026-10-04 10:00:00','received_by'=>$admin->id]);
        Payment::create(['user_id'=>$reader->id,'type'=>'rental_fee','amount'=>9999,'method'=>'cash','status'=>'cancelled','paid_at'=>'2026-10-04 10:00:00','received_by'=>$admin->id]);
        $response=$this->actingAs($admin)->get(route('statistics.index',['from'=>'2026-10-01','to'=>'2026-10-31']));
        $response->assertOk()->assertSee('04/10/2026')->assertSee('195.000 đ');
        $this->assertSame(195000,$response->viewData('stats')['Doanh thu trong kỳ (đ)']);
        $this->assertSame(195000,(int)$response->viewData('dailyRevenue')->sum('total'));
    }
    public function test_copy_labels_and_actual_shelf_locations_are_preserved(): void
    {
        [$reader,$admin,$book]=$this->library();
        $copy=BookCopy::create(['book_id'=>$book->id,'copy_code'=>'SHELF-01','status'=>'borrowed','shelf_location'=>'A1']);
        $response=$this->actingAs($admin)->get(route('copies.index',$book));
        $response->assertOk()->assertSee('Đang được mượn')->assertSee('Mã bản sao / Mã vạch')->assertSee('A1')->assertDontSee('>borrowed<',false);
        $copy->update(['status'=>'available']);
        $this->patch(route('copies.update',$copy),['status'=>'available','shelf_location'=>'B2','condition_note'=>''])->assertSessionHasNoErrors();
        $this->assertSame('B2',$copy->fresh()->shelf_location);
        $this->post(route('copies.store',$book),['quantity'=>1])->assertSessionHasNoErrors();
        $this->assertNull(BookCopy::latest('id')->firstOrFail()->shelf_location);
    }
    public function test_last_available_copy_can_be_reserved_and_next_reader_sees_error(): void
    {
        [$reader,,$book]=$this->library();
        $other=User::create(['user_code'=>'DG9999','name'=>'Other','email'=>'other@test.local','phone'=>'0903333333','password'=>'Test@12345','role'=>'user','status'=>'active']);
        $copy=BookCopy::create(['book_id'=>$book->id,'copy_code'=>'LAST-01','status'=>'available']);
        $this->actingAs($reader)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>3])->assertSessionHasNoErrors();
        $this->assertSame('reserved',$copy->fresh()->status);
        $this->actingAs($other)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>3])->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('borrow_records',1);
        $record=BorrowRecord::firstOrFail();
        $this->actingAs($reader)->post(route('borrow.cancel',$record))->assertSessionHasNoErrors();
        $this->assertSame('available',$copy->fresh()->status);
        $this->actingAs($other)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>3])->assertSessionHasNoErrors();
    }
}
