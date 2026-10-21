<?php
namespace Tests\Feature;
use App\Models\{Book, BookCopy, BorrowRecord, MembershipCard, Payment, RentalPolicy, StudentVerification, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class VerificationDeletionTest extends TestCase
{
    use RefreshDatabase;
    private function library(): array
    {
        $this->seed(); Storage::fake('local');
        $reader=User::where('role','user')->firstOrFail();
        $admin=User::where('role','admin')->firstOrFail();
        $item=StudentVerification::create(['user_id'=>$reader->id,'learner_type'=>'school','school_name'=>'Trường thử','student_code'=>'HS001','status'=>'approved','verified_by'=>$admin->id,'expires_at'=>today()->addMonth()->toDateString()]);
        return [$reader,$admin,$item];
    }
    public function test_delete_removes_only_selected_verification_and_preserves_account_card_and_financial_history(): void
    {
        [$reader,$admin,$item]=$this->library();
        Storage::disk('local')->put('student-evidence/old.pdf','proof');
        $item->update(['evidence_path'=>'student-evidence/old.pdf']);
        $other=StudentVerification::create(['user_id'=>$reader->id,'school_name'=>'Trường khác','student_code'=>'SV002','status'=>'rejected','reject_reason'=>'Đã hết hạn']);
        $card=MembershipCard::create(['user_id'=>$reader->id,'issued_by'=>$admin->id,'card_code'=>'TV-KEEP','months'=>1,'fee_amount'=>80000,'issued_at'=>today()->toDateString(),'expires_at'=>today()->addMonth()->toDateString(),'status'=>'active']);
        $book=Book::firstOrFail();$copy=BookCopy::create(['book_id'=>$book->id,'copy_code'=>'KEEP-001','status'=>'borrowed']);
        $record=BorrowRecord::create(['user_id'=>$reader->id,'book_copy_id'=>$copy->id,'membership_card_id'=>$card->id,'rental_policy_id'=>RentalPolicy::active()->id,'rental_days'=>10,'requested_at'=>now(),'borrow_date'=>today(),'due_date'=>today()->addDays(10),'status'=>'borrowing','cover_price'=>150000,'rental_fee'=>24000,'discount_amount'=>6000,'deposit_amount'=>75000,'lent_by'=>$admin->id]);
        Payment::create(['user_id'=>$reader->id,'membership_card_id'=>$card->id,'type'=>'card_fee','amount'=>80000,'method'=>'cash','status'=>'paid','received_by'=>$admin->id,'paid_at'=>now()]);
        $this->actingAs($admin)->get(route('verifications.confirm-delete',$item))->assertOk()->assertSee('Hồ sơ #'.$item->id)->assertSee($reader->user_code);
        $this->from(route('verifications.confirm-delete',$item))->delete(route('verifications.destroy',$item))->assertSessionHasErrors('confirm_delete');
        $this->assertDatabaseHas('student_verifications',['id'=>$item->id]);
        $this->delete(route('verifications.destroy',$item),['confirm_delete'=>1])->assertRedirect(route('verifications.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('student_verifications',['id'=>$item->id]);
        $this->assertDatabaseHas('student_verifications',['id'=>$other->id]);
        Storage::disk('local')->assertMissing('student-evidence/old.pdf');
        $this->assertDatabaseHas('users',['id'=>$reader->id,'status'=>'active']);
        $this->assertDatabaseHas('membership_cards',['id'=>$card->id,'status'=>'active']);
        $this->assertDatabaseHas('borrow_records',['id'=>$record->id,'rental_fee'=>24000,'discount_amount'=>6000,'deposit_amount'=>75000]);
        $this->assertDatabaseCount('payments',1);
        $this->assertNull(StudentVerification::activeFor($reader));
        $this->actingAs($reader)->get(route('verifications.mine'))->assertOk()->assertSee('Gửi yêu cầu xác minh');
        $this->post(route('verifications.store'),['learner_type'=>'school','school_name'=>'Trường mới','student_code'=>'HS001'])->assertSessionHasErrors('evidence');
    }
    public function test_reader_and_guest_cannot_delete_and_missing_legacy_file_is_supported(): void
    {
        [$reader,$admin,$item]=$this->library();
        $this->delete(route('verifications.destroy',$item),['confirm_delete'=>1])->assertRedirect(route('login'));
        $this->actingAs($reader)->get(route('verifications.confirm-delete',$item))->assertForbidden();
        $this->delete(route('verifications.destroy',$item),['confirm_delete'=>1])->assertForbidden();
        $this->assertDatabaseHas('student_verifications',['id'=>$item->id]);
        $this->actingAs($admin)->get(route('verifications.index'))->assertOk()->assertSee(route('verifications.confirm-delete',$item));
        $this->delete(route('verifications.destroy',$item),['confirm_delete'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users',['id'=>$reader->id]);
        $this->delete(route('verifications.destroy',$item),['confirm_delete'=>1])->assertNotFound();
    }
}
