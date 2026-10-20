<?php
namespace App\Http\Controllers;
use App\Models\{MembershipCard, MembershipRequest, RentalPolicy, User};
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class MembershipRequestController extends Controller
{
    public function store(Request $request)
    {
        $values = $request->validate(['months'=>'required|integer|between:1,12'], [
            'months.required'=>'Vui lòng chọn thời hạn đăng ký.',
            'months.integer'=>'Thời hạn đăng ký phải là số tháng nguyên.',
            'months.between'=>'Thời hạn đăng ký phải từ 1 đến 12 tháng.',
        ]);
        DB::transaction(function () use ($request,$values) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($user->status !== 'active' || $user->role !== 'user') abort(403);
            if (MembershipRequest::where('user_id',$user->id)->where('status','pending')->exists()) {
                throw ValidationException::withMessages(['membership'=>'Bạn đã có một đăng ký đang chờ xác nhận.']);
            }
            if (MembershipCard::where('user_id',$user->id)->where('status','suspended')->whereDate('expires_at','>=',today())->exists()) {
                throw ValidationException::withMessages(['membership'=>'Thẻ đang tạm khóa. Vui lòng liên hệ thư viện để mở khóa trước khi gia hạn.']);
            }
            $fee = (int) RentalPolicy::active()->card_fee_monthly * (int)$values['months'];
            MembershipRequest::create(['user_id'=>$user->id,'months'=>(int)$values['months'],'fee_amount'=>$fee,'status'=>'pending']);
        });
        return redirect()->route('cards.mine')->with('success','Đã gửi đăng ký thẻ. Vui lòng đến quầy thanh toán để thư viện kích hoạt hoặc gia hạn thẻ.');
    }
    public function cancel(Request $request, MembershipRequest $registration)
    {
        abort_unless($registration->user_id === $request->user()->id,404);
        $changed = DB::transaction(function () use ($registration,$request) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $registration = MembershipRequest::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            if ($registration->status !== 'pending') return false;
            $registration->update(['status'=>'cancelled']);
            return true;
        });
        return redirect()->route('cards.mine')->with('success',$changed ? 'Đã hủy đăng ký thẻ.' : 'Đăng ký này đã được xử lý trước đó.');
    }
    public function review(Request $request, MembershipRequest $registration, MembershipService $service)
    {
        $values = $request->validate(['decision'=>'required|in:approve,reject',
            'method'=>'required_if:decision,approve|nullable|in:cash,transfer',
            'payment_received'=>'accepted_if:decision,approve'],
            [
                'payment_received.accepted_if'=>'Vui lòng xác nhận đã nhận đủ phí thẻ trước khi kích hoạt.',
                'method.required_if'=>'Vui lòng chọn phương thức đã nhận tiền.',
                'method.in'=>'Phương thức đã nhận tiền không hợp lệ.',
                'decision.required'=>'Vui lòng chọn cách xử lý đăng ký.',
                'decision.in'=>'Cách xử lý đăng ký không hợp lệ.',
            ]);
        $changed = DB::transaction(function () use ($registration,$request,$service,$values) {
            $user = User::whereKey($registration->user_id)->lockForUpdate()->firstOrFail();
            $registration = MembershipRequest::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            if ($registration->status !== 'pending') return false;
            $card = $values['decision'] === 'approve'
                ? $service->issue($user,$registration->months,$registration->fee_amount,$values['method'],(int)$request->user()->id)
                : null;
            $registration->update(['status'=>$card ? 'approved' : 'rejected','membership_card_id'=>$card?->id,
                'reviewed_by'=>$request->user()->id,'reviewed_at'=>now()]);
            return true;
        });
        return redirect()->route('cards.index')->with('success',!$changed ? 'Đăng ký đã được xử lý trước đó. Không thu phí thêm.' :
            ($values['decision']==='approve' ? 'Đã xác nhận thanh toán và cấp/gia hạn thẻ.' : 'Đã từ chối đăng ký thẻ.'));
    }
}
