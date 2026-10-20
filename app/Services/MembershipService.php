<?php
namespace App\Services;
use App\Models\{MembershipCard, Payment, User};
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
class MembershipService
{
    // Caller holds the user's row lock inside its transaction.
    public function issue(User $user, int $months, int $fee, string $method, int $adminId): MembershipCard
    {
        if ($user->role !== 'user' || $user->status !== 'active') {
            throw ValidationException::withMessages(['membership'=>'Tài khoản độc giả không hoạt động.']);
        }
        $existing = MembershipCard::where('user_id',$user->id)
            ->whereIn('status',['active','suspended'])->whereDate('expires_at','>=',today())
            ->lockForUpdate()->orderByDesc('expires_at')->first();
        if ($existing && $existing->status === 'suspended') {
            throw ValidationException::withMessages(['membership'=>'Thẻ đang tạm khóa. Vui lòng mở khóa trước khi gia hạn.']);
        }
        if ($existing) {
            $existing->update(['expires_at'=>Carbon::parse($existing->expires_at)->addMonthsNoOverflow($months)->toDateString(), 'months'=>$months, 'fee_amount'=>$fee]);
            $card = $existing;
        } else {
            $card = MembershipCard::create(['user_id'=>$user->id,'card_code'=>'TMP'.strtoupper(bin2hex(random_bytes(6))),
                'months'=>$months,'fee_amount'=>$fee,'issued_at'=>today()->toDateString(),'expires_at'=>today()->addMonthsNoOverflow($months)->toDateString(),
                'status'=>'active','issued_by'=>$adminId]);
            $card->update(['card_code'=>'TV'.str_pad((string)$card->id,4,'0',STR_PAD_LEFT)]);
        }
        Payment::create(['user_id'=>$user->id,'membership_card_id'=>$card->id,'type'=>'card_fee','amount'=>$fee,
            'method'=>$method,'status'=>'paid','paid_at'=>now(),'received_by'=>$adminId]);
        return $card;
    }
}
