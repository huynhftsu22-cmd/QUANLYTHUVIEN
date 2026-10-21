<?php
namespace App\Http\Controllers;
use App\Models\{User,MembershipCard,RentalPolicy,MembershipRequest};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class MembershipCardController extends Controller {
 public function mine(Request $request) {
  $cards = $request->user()->membershipCards()->orderByDesc('expires_at')->paginate(10);
  $policy = RentalPolicy::active();
  $requests = MembershipRequest::where('user_id',$request->user()->id)->with('card')->latest()->paginate(10, ['*'], 'requests_page');
  $pending = MembershipRequest::where('user_id',$request->user()->id)->where('status','pending')->first();
  $currentCard = $request->user()->membershipCards()->whereIn('status',['active','suspended'])->whereDate('expires_at','>=',today())->orderByDesc('expires_at')->first();
  return view('cards.mine', compact('cards','policy','requests','pending','currentCard'));
 }
 public function index(Request $r){
  $cards=MembershipCard::with('user')->when($r->q,fn($q)=>$q->whereHas('user',fn($u)=>$u->where('name','like','%'.$r->q.'%')->orWhere('user_code','like','%'.$r->q.'%')->orWhere('email','like','%'.$r->q.'%')->orWhere('phone','like','%'.$r->q.'%')))->latest()->paginate(20);
  $users=User::where('role','user')->where('status','active')->orderBy('name')->get();$pendingRequests=MembershipRequest::with('user')->where('status','pending')->oldest()->paginate(10, ['*'], 'requests_page');
  return view('cards.index',compact('cards','users','pendingRequests'));
 }
 public function store(Request $r){
  $v=$r->validate(['user_id'=>'required|exists:users,id','months'=>'required|integer|between:1,12','method'=>'required|in:cash,transfer']);
  DB::transaction(function()use($v){
   $user=User::whereKey($v['user_id'])->lockForUpdate()->firstOrFail();
   if(MembershipRequest::where('user_id',$user->id)->where('status','pending')->exists()) {
    throw \Illuminate\Validation\ValidationException::withMessages(['membership'=>'Độc giả có đăng ký đang chờ. Vui lòng xác nhận ở danh sách đăng ký trực tuyến bên dưới để tránh cấp trùng.']);
   }
   app(\App\Services\MembershipService::class)->issue($user, (int)$v['months'], (int)RentalPolicy::active()->card_fee_monthly*(int)$v['months'], $v['method'], (int)auth()->id());
  });return back()->with('success','Đã cấp/gia hạn thẻ');
 }
 public function status(Request $r,MembershipCard $card){
  $v=$r->validate(['status'=>'required|in:active,suspended']);
  DB::transaction(function()use($card,$v){
   User::whereKey($card->user_id)->lockForUpdate()->firstOrFail();
   $card=MembershipCard::whereKey($card->id)->lockForUpdate()->firstOrFail();
   if($card->status==='cancelled'||Carbon::parse($card->expires_at)->lt(today()))abort(422,'Thẻ đã hủy/hết hạn');
   $card->update(['status'=>$v['status']]);
  });return back()->with('success','Đã cập nhật trạng thái thẻ');
 }
}
