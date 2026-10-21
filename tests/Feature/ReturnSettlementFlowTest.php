<?php
namespace Tests\Feature;
use App\Models\{User,Book,BookCopy,BorrowRecord,DepositTransaction,Payment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnSettlementFlowTest extends TestCase
{
 use RefreshDatabase;
 private function borrowing(): BorrowRecord {
  $this->seed();
        \App\Models\RentalPolicy::active()->update(['deposit_rate'=>50]);
  $reader=User::where('role','user')->firstOrFail();$admin=User::where('role','admin')->firstOrFail();
  $book=Book::where('book_code','S0006')->firstOrFail();$book->update(['cover_price'=>55000]);
  BookCopy::create(['book_id'=>$book->id,'copy_code'=>'S0006-01','status'=>'available']);
  $this->actingAs($reader)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>5]);
  $record=BorrowRecord::firstOrFail();
  $this->actingAs($admin)->post(route('borrow.handover',$record),['copy_code'=>'S0006-01','method'=>'cash','expected_fee'=>15000,'expected_deposit'=>28000])->assertSessionHasNoErrors();
  return $record->fresh();
 }
 private function data(): array {return ['condition'=>'good','copy_code'=>'S0006-01','method'=>'cash','refund_method'=>'cash','expected_late'=>0,'expected_damage'=>0,'expected_deduct'=>0,'expected_refund'=>28000];}
 public function test_settlement_redirects_to_list_and_repeated_submission_is_harmless(): void {
  $r=$this->borrowing();$data=$this->data();
  $this->post(route('returns.preview',$r),$data)->assertOk()->assertSee('28.000')->assertSee('settlement-form');
  $this->post(route('returns.store',$r),$data)->assertRedirect(route('returns.index'))->assertSessionHasNoErrors();
  $this->post(route('returns.store',$r),$data)->assertRedirect(route('returns.index'))->assertSessionHasNoErrors()->assertSessionHas('success',fn($s)=>str_contains($s,'đã được quyết toán trước đó'));
  $this->assertSame(1,DepositTransaction::where('type','refund')->count());
  $this->assertSame(28000,(int)DepositTransaction::where('type','refund')->sum('amount'));
  $this->assertSame(1,Payment::count());$this->assertSame('returned',$r->fresh()->status);
  $this->post(route('returns.preview',$r),$data)->assertRedirect(route('returns.index'));
 }
 public function test_wrong_return_copy_and_stale_amount_keep_the_record_and_deposit(): void {
  $r=$this->borrowing();
  $data=$this->data();$data['copy_code']='S0014-01';
  $this->post(route('returns.preview',$r),$data)->assertRedirect(route('returns.index'))->assertSessionHasErrors('copy_code');
  $this->post(route('returns.store',$r),$data)->assertRedirect(route('returns.index'))->assertSessionHasErrors('copy_code');
  $data=$this->data();$data['expected_refund']=1;
  $this->post(route('returns.store',$r),$data)->assertRedirect(route('returns.index'))->assertSessionHasErrors('settlement');
  $this->assertSame('borrowing',$r->fresh()->status);$this->assertSame(0,DepositTransaction::where('type','refund')->count());
  $this->assertSame(28000,$r->fresh()->deposit_summary['balance']);
 }
 public function test_missing_input_and_invalid_damage_return_a_form_error(): void {
  $r=$this->borrowing();$data=$this->data();unset($data['copy_code']);
  $this->post(route('returns.store',$r),$data)->assertRedirect(route('returns.index'))->assertSessionHasErrors('copy_code');
  $data=$this->data();$data['condition']='damaged';$data['damage_amount']=999999;$data['damage_note']='Nhập phí vượt giá bìa';
  $this->post(route('returns.preview',$r),$data)->assertRedirect(route('returns.index'))->assertSessionHasErrors('settlement');
  $this->assertSame('borrowing',$r->fresh()->status);
 }
}
