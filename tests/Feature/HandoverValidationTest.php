<?php
namespace Tests\Feature;

use App\Models\{User,Book,BookCopy,BorrowRecord,Payment,DepositTransaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandoverValidationTest extends TestCase
{
    use RefreshDatabase;

    private function pending(): array
    {
        $this->seed();
        \App\Models\RentalPolicy::active()->update(['deposit_rate'=>50]);
        $reader=User::where('role','user')->firstOrFail();
        $admin=User::where('role','admin')->firstOrFail();
        $book=Book::where('book_code','S0006')->firstOrFail();
        $book->update(['cover_price'=>55000]);
        $copy=BookCopy::create(['book_id'=>$book->id,'copy_code'=>'S0006-01','status'=>'available']);
        $other=Book::where('book_code','S0014')->firstOrFail();
        BookCopy::create(['book_id'=>$other->id,'copy_code'=>'S0014-01','status'=>'available']);
        $this->actingAs($reader)->post(route('borrow.reserve'),['book_id'=>$book->id,'rental_days'=>5]);
        $this->actingAs($admin);
        return [BorrowRecord::firstOrFail(), $copy];
    }

    public function test_wrong_code_returns_to_form_with_error_and_no_financial_changes(): void
    {
        [$record,$copy]=$this->pending();
        $url=route('borrow.preview',$record);
        $this->from($url)->post(route('borrow.handover',$record),['copy_code'=>'S0014-01','method'=>'cash','expected_fee'=>15000,'expected_deposit'=>28000])
            ->assertRedirect($url)->assertSessionHasErrors('copy_code')->assertSessionHasInput('copy_code','S0014-01');
        $this->get($url)->assertOk()->assertSee('Phiếu này yêu cầu S0006-01.')->assertDontSee('HttpException');
        $this->assertSame('pending',$record->fresh()->status);
        $this->assertSame('reserved',$copy->fresh()->status);
        $this->assertDatabaseCount('payments',0);
        $this->assertDatabaseCount('deposit_transactions',0);
    }

    public function test_correct_code_succeeds_and_double_submit_does_not_charge_again(): void
    {
        [$record,$copy]=$this->pending();
        $data=['copy_code'=>' s0006-01 ','method'=>'cash','expected_fee'=>15000,'expected_deposit'=>28000];
        $this->post(route('borrow.handover',$record),$data)->assertRedirect(route('borrow.index'))->assertSessionHasNoErrors();
        $this->assertSame('borrowing',$record->fresh()->status);
        $this->assertSame('borrowed',$copy->fresh()->status);
        $this->post(route('borrow.handover',$record),$data)->assertRedirect(route('borrow.index'))->assertSessionHasErrors('handover');
        $this->assertDatabaseCount('payments',1);
        $this->assertDatabaseCount('deposit_transactions',1);
        $this->get(route('borrow.preview',$record))->assertRedirect(route('borrow.index'));
    }

    public function test_stale_quote_is_refused_without_charging(): void
    {
        [$record]=$this->pending();
        $this->post(route('borrow.handover',$record),['copy_code'=>'S0006-01','method'=>'cash','expected_fee'=>1,'expected_deposit'=>1])
            ->assertRedirect(route('borrow.preview',$record))->assertSessionHasErrors('handover');
        $this->assertDatabaseCount('payments',0);
        $this->assertSame('pending',$record->fresh()->status);
    }

    public function test_missing_copy_code_has_vietnamese_return_message(): void
    {
        $this->pending();
        $validator=validator(['condition'=>'good'], ['copy_code'=>'required_unless:condition,lost']);
        $this->assertSame('Vui lòng nhập mã bản sao khi sách không bị mất.', $validator->errors()->first('copy_code'));
    }
}
