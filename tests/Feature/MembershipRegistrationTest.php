<?php
namespace Tests\Feature;
use App\Models\{MembershipCard, MembershipRequest, RentalPolicy, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MembershipRegistrationTest extends TestCase
{
    use RefreshDatabase;
    private function library(): array
    {
        $this->seed();
        return [User::where('role','user')->firstOrFail(),User::where('role','admin')->firstOrFail()];
    }
    public function test_request_uses_server_price_has_no_benefits_and_rejects_duplicates(): void
    {
        [$reader,$admin]=$this->library();
        $this->actingAs($reader)->get(route('cards.mine'))->assertOk()->assertSee('Đăng ký thẻ thành viên')->assertSee('membership-registration.js')->assertSee('saysach-brand-icon');
        $this->post(route('cards.requests.store'),['months'=>3,'user_id'=>$admin->id,'fee_amount'=>1])->assertRedirect(route('cards.mine'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membership_requests',['user_id'=>$reader->id,'months'=>3,'fee_amount'=>240000,'status'=>'pending']);
        $this->assertDatabaseCount('membership_cards',0);
        $this->assertDatabaseCount('payments',0);
        $this->assertNull($reader->validCardFor(today()));
        $this->from(route('cards.mine'))->post(route('cards.requests.store'),['months'=>1])->assertRedirect(route('cards.mine'))->assertSessionHasErrors('membership');
        $this->assertDatabaseCount('membership_requests',1);
        $this->get(route('cards.mine'))->assertSee('Chờ thanh toán tại quầy')->assertDontSee('id="membership-registration"',false);
    }
    public function test_confirmation_requires_receipt_and_issues_once_at_agreed_price(): void
    {
        [$reader,$admin]=$this->library();
        $this->actingAs($reader)->post(route('cards.requests.store'),['months'=>2]);
        $registration=MembershipRequest::firstOrFail();
        RentalPolicy::active()->update(['card_fee_monthly'=>90000]);
        $data=['decision'=>'approve','method'=>'transfer','payment_received'=>1];
        $this->actingAs($admin)->get(route('cards.index'))->assertOk()->assertSee('Đăng ký trực tuyến chờ thanh toán')->assertSee('160.000');
        $this->from(route('cards.index'))->post(route('cards.requests.review',$registration),['decision'=>'approve','method'=>'cash'])->assertSessionHasErrors('payment_received');
        $this->assertDatabaseCount('payments',0);
        for($i=0;$i<2;$i++) $this->post(route('cards.requests.review',$registration),$data)->assertRedirect(route('cards.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('membership_cards',1);
        $this->assertDatabaseCount('payments',1);
        $card=MembershipCard::firstOrFail();
        $this->assertSame(today()->addMonthsNoOverflow(2)->toDateString(),$card->expires_at);
        $this->assertDatabaseHas('payments',['membership_card_id'=>$card->id,'amount'=>160000,'method'=>'transfer','status'=>'paid','received_by'=>$admin->id]);
        $this->assertSame('approved',$registration->fresh()->status);
        $this->assertNotNull($reader->validCardFor(today()->addDays(10)));
        $this->actingAs($reader)->get(route('cards.mine'))->assertSee($card->card_code)->assertSee('Đã cấp/gia hạn');
    }
    public function test_renewal_extends_existing_expiry_and_counter_cannot_bypass_pending_request(): void
    {
        [$reader,$admin]=$this->library();
        $card=MembershipCard::create(['user_id'=>$reader->id,'issued_by'=>$admin->id,'card_code'=>'TV-TEST','months'=>1,'fee_amount'=>80000,'issued_at'=>today(),'expires_at'=>today()->addDays(10),'status'=>'active']);
        $this->actingAs($reader)->post(route('cards.requests.store'),['months'=>1]);
        $registration=MembershipRequest::firstOrFail();
        $this->actingAs($admin)->from(route('cards.index'))->post(route('cards.store'),['user_id'=>$reader->id,'months'=>1,'method'=>'cash'])->assertSessionHasErrors('membership');
        $this->assertDatabaseCount('payments',0);
        $this->post(route('cards.requests.review',$registration),['decision'=>'approve','method'=>'cash','payment_received'=>1])->assertSessionHasNoErrors();
        $this->assertSame(today()->addDays(10)->addMonthsNoOverflow()->toDateString(),$card->fresh()->expires_at);
        $this->assertDatabaseCount('membership_cards',1);
        $this->assertDatabaseCount('payments',1);
    }
    public function test_cancellation_rejection_and_owner_permissions(): void
    {
        [$reader,$admin]=$this->library();
        $other=User::create(['user_code'=>'DG9999','name'=>'Other Reader','email'=>'other@test.local','phone'=>'0903333333','password'=>'Test@12345','role'=>'user','status'=>'active']);
        $this->post(route('cards.requests.store'),['months'=>1])->assertRedirect(route('login'));
        $this->actingAs($reader)->post(route('cards.requests.store'),['months'=>1]);
        $registration=MembershipRequest::firstOrFail();
        $this->post(route('cards.requests.review',$registration),['decision'=>'approve','method'=>'cash','payment_received'=>1])->assertForbidden();
        $this->actingAs($other)->post(route('cards.requests.cancel',$registration))->assertNotFound();
        $this->get(route('cards.mine'))->assertDontSee('Chờ thanh toán tại quầy</span>',false);
        $this->actingAs($reader)->post(route('cards.requests.cancel',$registration))->assertRedirect(route('cards.mine'));
        $this->assertSame('cancelled',$registration->fresh()->status);
        $this->actingAs($admin)->post(route('cards.requests.review',$registration),['decision'=>'approve','method'=>'cash','payment_received'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments',0);
        $this->actingAs($reader)->post(route('cards.requests.store'),['months'=>1]);
        $next=MembershipRequest::latest('id')->firstOrFail();
        $this->actingAs($admin)->post(route('cards.requests.review',$next),['decision'=>'reject'])->assertSessionHasNoErrors();
        $this->assertSame('rejected',$next->fresh()->status);
        $this->assertDatabaseCount('payments',0);
        $this->assertDatabaseCount('membership_cards',0);
        $this->post(route('cards.requests.store'),['months'=>1])->assertForbidden();
    }
    public function test_invalid_months_and_suspended_or_locked_accounts(): void
    {
        [$reader,$admin]=$this->library();
        $this->actingAs($reader);
        foreach([0,13,'1.5','x'] as $months) $this->post(route('cards.requests.store'),['months'=>$months])->assertSessionHasErrors('months');
        $this->assertDatabaseCount('membership_requests',0);
        $card=MembershipCard::create(['user_id'=>$reader->id,'issued_by'=>$admin->id,'card_code'=>'TV-SUSPEND','months'=>1,'fee_amount'=>80000,'issued_at'=>today(),'expires_at'=>today()->addMonth(),'status'=>'suspended']);
        $this->post(route('cards.requests.store'),['months'=>1])->assertSessionHasErrors('membership');
        $card->update(['status'=>'active']);
        $this->post(route('cards.requests.store'),['months'=>1])->assertSessionHasNoErrors();
        $registration=MembershipRequest::firstOrFail();
        $reader->update(['status'=>'locked']);
        $this->actingAs($admin)->from(route('cards.index'))->post(route('cards.requests.review',$registration),['decision'=>'approve','method'=>'cash','payment_received'=>1])->assertSessionHasErrors('membership');
        $this->assertSame('pending',$registration->fresh()->status);
        $this->assertDatabaseCount('payments',0);
    }
    public function test_expired_card_is_replaced_from_confirmation_date_and_processed_request_cannot_be_cancelled(): void
    {
        [$reader,$admin]=$this->library();
        $old=MembershipCard::create(['user_id'=>$reader->id,'issued_by'=>$admin->id,'card_code'=>'TV-EXPIRED','months'=>1,'fee_amount'=>80000,'issued_at'=>today()->subMonths(2)->toDateString(),'expires_at'=>today()->subMonth()->toDateString(),'status'=>'active']);
        $this->actingAs($reader)->post(route('cards.requests.store'),['months'=>1]);
        $registration=MembershipRequest::firstOrFail();
        $this->actingAs($admin)->post(route('cards.requests.review',$registration),['decision'=>'approve','method'=>'cash','payment_received'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('membership_cards',2);
        $card=MembershipCard::latest('id')->firstOrFail();
        $this->assertSame(today()->toDateString(),$card->issued_at);
        $this->assertSame(today()->addMonthsNoOverflow()->toDateString(),$card->expires_at);
        $this->actingAs($reader)->post(route('cards.requests.cancel',$registration))->assertSessionHasNoErrors();
        $this->assertSame('approved',$registration->fresh()->status);
        $this->assertDatabaseCount('payments',1);
    }

}
