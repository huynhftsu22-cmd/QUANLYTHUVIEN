<?php
namespace App\Http\Controllers;
use App\Models\{BorrowRecord,BookCopy,RentalPolicy,StudentVerification,DepositTransaction,Payment,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PricingService;
class BorrowController extends Controller {
 public function index(){ $records=BorrowRecord::with(['user','bookCopy.book'])->latest()->paginate(25);return view('borrow.index',compact('records')); }
 public function reserve(Request $r){$v=$r->validate(['book_id'=>'required|exists:books,id','rental_days'=>'required|integer|between:1,30']);try { DB::transaction(function()use($v){$policy=RentalPolicy::active();if($v['rental_days']>$policy->max_days)throw \Illuminate\Validation\ValidationException::withMessages(['reservation' => 'Vượt số ngày cho phép']);$user=User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();if (BorrowRecord::where('user_id',$user->id)->whereHas('bookCopy', fn($q) => $q->where('book_id',$v['book_id']))->whereIn('status',['pending','borrowing','overdue'])->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['reservation' => 'Bạn đang có phiếu chưa trả cho đầu sách này']);$book=\App\Models\Book::whereKey($v['book_id'])->where('is_active',true)->firstOrFail();$count=BorrowRecord::where('user_id',$user->id)->whereIn('status',['pending','borrowing','overdue'])->count();if($count >= $policy->max_books)throw \Illuminate\Validation\ValidationException::withMessages(['reservation' => 'Đã đạt giới hạn số sách']);$copy=BookCopy::where('book_id',$v['book_id'])->where('status','available')->orderBy('copy_code')->lockForUpdate()->first();if(!$copy)throw \Illuminate\Validation\ValidationException::withMessages(['reservation' => 'Hiện không còn bản sao có thể đặt']);$copy->update(['status'=>'reserved']);BorrowRecord::create(['user_id'=>$user->id,'book_copy_id'=>$copy->id,'rental_policy_id'=>null,'rental_days'=>$v['rental_days'],'requested_at'=>now(),'status'=>'pending']);}); } catch (\Illuminate\Validation\ValidationException $e) { if ($r->expectsJson()) throw $e; return redirect()->route('books.show', ['book'=>$v['book_id'], 'days'=>$v['rental_days']])->withErrors($e->errors())->withInput(); } return redirect()->route('history.index')->with('success','Đã giữ sách, vui lòng đến quầy trong 24 giờ'); }
 public function cancel(\Illuminate\Http\Request $request, BorrowRecord $record){if($record->user_id!==auth()->id()&&auth()->user()->role!=='admin')abort(403);$admin=auth()->user()->role==='admin';
  $reason=$admin ? $request->validate(['cancel_reason'=>'required|string|min:5|max:255'])['cancel_reason'] : 'Độc giả hủy yêu cầu';
  app(\App\Services\BorrowService::class)->cancelPending($record, $reason);return back()->with('success','Đã hủy đặt sách'); }
 public function preview(BorrowRecord $record){
  $record=BorrowRecord::with(['user','bookCopy.book'])->findOrFail($record->id);
  if($record->status!=='pending') return redirect()->route('borrow.index')->withErrors(['handover'=>'Phiếu này không còn chờ bàn giao. Vui lòng kiểm tra trạng thái trong danh sách.']);
  $policy=RentalPolicy::active();
  $quote=app(PricingService::class)->quote($record->user,(int)$record->bookCopy->book->cover_price,(int)$record->rental_days,$policy);
  $warning=$record->user->membershipCards()->where('status','active')->whereDate('expires_at','>=',today())->whereDate('expires_at','<',$quote['due_date'])->exists();
  return view('borrow.preview',compact('record','quote','warning'));
 }
 public function handover(Request $request, BorrowRecord $record)
 {
  $data = $request->validate([
   'copy_code'=>'required|string|max:40', 'method'=>'required|in:cash,transfer',
   'expected_fee'=>'required|integer|min:0', 'expected_deposit'=>'required|integer|min:0',
  ]);
  try {
   DB::transaction(function () use ($record, $data) {
    $record = BorrowRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
    $record->load(['bookCopy.book','user']);
    if ($record->status !== 'pending') throw \Illuminate\Validation\ValidationException::withMessages(['handover'=>'Phiếu này đã được xử lý, không thể bàn giao lần nữa.']);
    $expectedCode = $record->bookCopy->copy_code;
    if (mb_strtoupper(trim($expectedCode)) !== mb_strtoupper(trim($data['copy_code']))) {
     throw \Illuminate\Validation\ValidationException::withMessages(['copy_code'=>'Mã bản sao không đúng. Phiếu này yêu cầu '.$expectedCode.'.']);
    }
    $policy = RentalPolicy::active();
    if ($record->requested_at && now()->gt(\Carbon\Carbon::parse($record->requested_at)->addHours($policy->hold_hours))) {
     throw \Illuminate\Validation\ValidationException::withMessages(['handover'=>'Phiếu giữ chỗ đã quá hạn. Vui lòng hủy phiếu hết hạn trước khi đặt lại.']);
    }
    $user = $record->user;
    if (!$user->isActive()) throw \Illuminate\Validation\ValidationException::withMessages(['handover'=>'Độc giả đã bị khóa hoặc vô hiệu hóa.']);
    if ($record->bookCopy->status !== 'reserved') throw \Illuminate\Validation\ValidationException::withMessages(['handover'=>'Bản sao không còn ở trạng thái giữ chỗ. Vui lòng kiểm tra lại phiếu.']);
    $pricing = app(PricingService::class)->rental($user, (int)$record->bookCopy->book->cover_price, (int)$record->rental_days, $policy);
    if ($pricing['rental_fee'] !== (int)$data['expected_fee'] || $pricing['deposit_amount'] !== (int)$data['expected_deposit']) {
     throw \Illuminate\Validation\ValidationException::withMessages(['handover'=>'Chi phí đã thay đổi. Bảng bên dưới đã được cập nhật; vui lòng đối chiếu lại trước khi xác nhận.']);
    }
    $record->update(array_merge($pricing, ['status'=>'borrowing','borrow_date'=>today(),'rental_policy_id'=>$policy->id,'lent_by'=>auth()->id()]));
    $record->bookCopy()->update(['status'=>'borrowed']);
    if ($pricing['rental_fee'] > 0) Payment::create(['user_id'=>$user->id,'borrow_record_id'=>$record->id,'type'=>'rental_fee','amount'=>$pricing['rental_fee'],'method'=>$data['method'],'status'=>'paid','paid_at'=>now(),'received_by'=>auth()->id()]);
    if ($pricing['deposit_amount'] > 0) DepositTransaction::create(['borrow_record_id'=>$record->id,'type'=>'hold','amount'=>$pricing['deposit_amount'],'processed_by'=>auth()->id(),'processed_at'=>now()]);
   });
  } catch (\Illuminate\Validation\ValidationException $e) {
   if ($request->expectsJson()) throw $e;
   $destination = $record->fresh()->status === 'pending' ? route('borrow.preview',$record) : route('borrow.index');
   return redirect($destination)->withErrors($e->errors())->withInput($request->only(['copy_code','method']));
  }
  return redirect()->route('borrow.index')->with('success','Đã bàn giao và ghi nhận thu tại quầy.');
 }
}
