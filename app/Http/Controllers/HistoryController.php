<?php
namespace App\Http\Controllers;
use App\Models\BorrowRecord;
use Illuminate\Http\Request;
class HistoryController extends Controller {
 /** UC05: lịch sử mượn của chính độc giả đang đăng nhập, lọc theo trạng thái. */
 public function index(Request $request){
  $status=$request->query('status');
  if($status && !in_array($status,['pending','borrowing','overdue','returned','cancelled'],true))$status=null;
  $records=BorrowRecord::with(['bookCopy.book','depositTransactions'])->where('user_id',auth()->id())
   ->when($status,fn($q)=>$q->where('status',$status))->latest()->paginate(20)->withQueryString();
  return view('history.index',compact('records','status'));
 }
}
