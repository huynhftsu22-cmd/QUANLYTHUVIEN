<?php
namespace Tests\Feature;
use App\Models\{MembershipCard, StudentVerification, User};
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class LearnerVerificationTest extends TestCase
{
    use RefreshDatabase;
    private function library(): array
    {
        Storage::fake('local');
        $this->seed();
        return [User::where('role','user')->firstOrFail(),User::where('role','admin')->firstOrFail()];
    }
    private function data(string $type='school'): array
    {
        return ['learner_type'=>$type,'school_name'=>'Trường Hòa Bình','student_code'=>'HS001',
            'evidence'=>UploadedFile::fake()->create('xac-nhan.pdf',100,'application/pdf')];
    }
    public function test_evidence_is_required_and_wrong_mime_or_oversized_files_are_rejected(): void
    {
        [$reader]=$this->library();
        $this->actingAs($reader)->get(route('verifications.mine'))->assertOk()->assertSee('Xác minh học sinh/sinh viên')->assertSee('Minh chứng bắt buộc');
        $data=$this->data();unset($data['evidence']);
        $this->from(route('verifications.mine'))->post(route('verifications.store'),$data)->assertRedirect(route('verifications.mine'))->assertSessionHasErrors('evidence');
        foreach([UploadedFile::fake()->create('proof.txt',100,'text/plain'),UploadedFile::fake()->create('large.pdf',5121,'application/pdf'),UploadedFile::fake()->create('fake.jpg',100,'text/plain')] as $file) {
            $this->post(route('verifications.store'),array_replace($this->data(),['evidence'=>$file]))->assertSessionHasErrors('evidence');
        }
        $this->post(route('verifications.store'),array_replace($this->data(),['learner_type'=>'other']))->assertSessionHasErrors('learner_type');
        $this->assertDatabaseCount('student_verifications',0);
        $this->assertSame([],Storage::disk('local')->allFiles());
    }
    public function test_both_learner_types_receive_discount_only_after_approval_and_not_on_top_of_membership(): void
    {
        [$reader,$admin]=$this->library();
        foreach(['school','university'] as $type) {
            $this->actingAs($reader)->post(route('verifications.store'),$this->data($type))->assertRedirect(route('verifications.mine'))->assertSessionHasNoErrors();
            $item=StudentVerification::latest('id')->firstOrFail();
            $this->assertSame($type,$item->learner_type);
            Storage::disk('local')->assertExists($item->evidence_path);
            $quote=app(PricingService::class)->rental($reader,150000,10);
            $this->assertSame(0,$quote['discount_amount']);
            $this->actingAs($admin)->get(route('verifications.index'))->assertOk()->assertSee($item->learner_type_label);
            $review=['status'=>'approved','expires_at'=>today()->addYear()->toDateString()];
            $this->patch(route('verifications.review',$item),$review)->assertRedirect(route('verifications.index'))->assertSessionHasNoErrors();
            $this->patch(route('verifications.review',$item),$review)->assertSessionHasNoErrors();
            $quote=app(PricingService::class)->rental($reader,150000,10);
            $this->assertSame(6000,$quote['discount_amount']);
            $this->assertSame(24000,$quote['rental_fee']);
            $this->actingAs($reader)->get(route('verifications.mine'))->assertSee('Xác minh còn hiệu lực');
            $item->refresh()->update(['expires_at'=>today()->subDay()->toDateString()]);
            $this->assertSame(0,app(PricingService::class)->rental($reader,150000,10)['discount_amount']);
        }
        $item->update(['expires_at'=>today()->addYear()->toDateString()]);
        MembershipCard::create(['user_id'=>$reader->id,'issued_by'=>$admin->id,'card_code'=>'TV-HSSV','months'=>1,'fee_amount'=>80000,'issued_at'=>today()->toDateString(),'expires_at'=>today()->addMonth()->toDateString(),'status'=>'active']);
        $quote=app(PricingService::class)->rental($reader,150000,10);
        $this->assertSame(0,$quote['rental_fee']);
        $this->assertSame(0,$quote['discount_amount']);
    }
    public function test_repeat_submission_does_not_store_extra_files_and_active_verification_blocks_new_requests(): void
    {
        [$reader,$admin]=$this->library();
        $this->actingAs($reader)->post(route('verifications.store'),$this->data())->assertSessionHasNoErrors();
        $this->from(route('verifications.mine'))->post(route('verifications.store'),$this->data())->assertRedirect(route('verifications.mine'))->assertSessionHasErrors('verification');
        $this->assertDatabaseCount('student_verifications',1);
        $this->assertCount(1,Storage::disk('local')->allFiles());
        $item=StudentVerification::firstOrFail();
        $this->actingAs($admin)->patch(route('verifications.review',$item),['status'=>'approved','expires_at'=>today()->addYear()->toDateString()]);
        $this->actingAs($reader)->post(route('verifications.store'),$this->data())->assertSessionHasErrors('verification');
        $this->assertCount(1,Storage::disk('local')->allFiles());
    }
    public function test_evidence_is_private_and_missing_legacy_evidence_cannot_be_approved(): void
    {
        [$reader,$admin]=$this->library();
        $this->actingAs($reader)->post(route('verifications.store'),$this->data());
        $item=StudentVerification::firstOrFail();
        $this->get(route('verifications.evidence',$item))->assertOk();
        $other=User::create(['user_code'=>'DG9999','name'=>'Other','email'=>'other@test.local','phone'=>'0903333333','password'=>'Test@12345','role'=>'user','status'=>'active']);
        $this->actingAs($other)->get(route('verifications.evidence',$item))->assertForbidden();
        $this->actingAs($reader)->patch(route('verifications.review',$item),['status'=>'approved','expires_at'=>today()->addYear()->toDateString()])->assertForbidden();
        $this->actingAs($admin)->get(route('verifications.evidence',$item))->assertOk();
        Storage::disk('local')->delete($item->evidence_path);
        $this->from(route('verifications.index'))->patch(route('verifications.review',$item),['status'=>'approved','expires_at'=>today()->addYear()->toDateString()])->assertSessionHasErrors('evidence');
        $this->assertSame('pending',$item->fresh()->status);
        $this->patch(route('verifications.review',$item),['status'=>'rejected','reject_reason'=>'Thiếu minh chứng.'])->assertSessionHasNoErrors();
        $this->assertSame('rejected',$item->fresh()->status);
        $this->actingAs($reader)->get(route('verifications.mine'))->assertSee('Thiếu minh chứng.')->assertSee('Gửi yêu cầu xác minh');
        $this->post(route('verifications.store'),$this->data())->assertSessionHasNoErrors();
    }
    public function test_real_png_upload_and_old_university_records_keep_their_identity(): void
    {
        [$reader,$admin]=$this->library();
        $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=');
        $this->actingAs($reader)->post(route('verifications.store'),array_replace($this->data(),['evidence'=>UploadedFile::fake()->createWithContent('the-hoc-sinh.png',$png)]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_verifications',['learner_type'=>'school','user_id'=>$reader->id]);
        $legacy=StudentVerification::create(['user_id'=>$reader->id,'school_name'=>'Trường cũ','student_code'=>'SV-CU','status'=>'approved','verified_by'=>$admin->id,'expires_at'=>today()->addMonth()->toDateString()]);
        $this->assertSame('university',$legacy->fresh()->learner_type);
        $this->assertSame('Sinh viên',$legacy->fresh()->learner_type_label);
        $this->assertNotNull(StudentVerification::activeFor($reader));
        $this->actingAs($admin)->get(route('verifications.mine'))->assertForbidden();
        $this->post(route('verifications.store'),$this->data())->assertForbidden();
    }
}
