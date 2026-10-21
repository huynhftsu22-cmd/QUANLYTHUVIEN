<?php
namespace App\Http\Controllers;
use App\Models\{Payment,DepositTransaction,BorrowRecord};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments=Payment::with(['user','receiver'])->latest()->paginate(30);
        $deposits=DepositTransaction::with(['borrowRecord.user','borrowRecord.bookCopy.book'])->latest()->limit(30)->get();
        $depositRecords = BorrowRecord::with(['user','bookCopy.book','depositTransactions'])->whereHas('depositTransactions')->latest()->limit(30)->get();
        return view('payments.index',compact('payments','deposits','depositRecords'));
    }
    // BR-23: không xóa khoản thu, đánh dấu cancelled và ghi rõ lý do.
    public function cancel(Request $request, Payment $payment)
    {
        $data=$request->validate(['reason'=>'required|string|min:5|max:70']);
        DB::transaction(function()use($payment,$data){
            $payment=Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if($payment->status!=='paid')abort(422,'Khoản thu đã bị hủy');
            $payment->update(['status'=>'cancelled','reference'=>'Hủy bởi NV '.auth()->user()->user_code.': '.$data['reason']]);
        });
        return back()->with('success','Đã đánh dấu hủy khoản thu; hãy ghi nhận khoản đúng theo quy trình nghiệp vụ');
    }
    // BR-23: giao dịch cọc không sửa/xóa; chỉ thêm giao dịch điều chỉnh.
    public function adjust(Request $request, DepositTransaction $transaction)
    {
        $data=$request->validate(['type'=>'required|in:adjust_up,adjust_down','amount'=>'required|integer|min:1','reason'=>'required|string|min:5|max:255']);
        DB::transaction(function()use($transaction,$data){
            $record=BorrowRecord::whereKey($transaction->borrow_record_id)->lockForUpdate()->firstOrFail();
            $original=DepositTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if(in_array($original->type,['adjust_up','adjust_down'],true))abort(422,'Không thể điều chỉnh lại dòng điều chỉnh');
            if(DepositTransaction::where('adjusts_id',$original->id)->exists())abort(422,'Dòng này đã được điều chỉnh');
            if($data['type']==='adjust_down' && $original->type!=='hold')abort(422,'Giảm cọc chỉ áp dụng cho dòng giữ cọc');
            if($data['type']==='adjust_up' && !in_array($original->type,['refund','deduct'],true))abort(422,'Tăng cọc chỉ áp dụng cho dòng hoàn hoặc khấu trừ');
            if($data['amount']>$original->amount)abort(422,'Số điều chỉnh vượt giao dịch gốc');
            $entries=DepositTransaction::where('borrow_record_id',$record->id)->lockForUpdate()->get();
            $balance=0;
            foreach($entries as $entry){
                $balance+=in_array($entry->type,['hold','adjust_up'],true)?$entry->amount:-$entry->amount;
            }
            if($data['type']==='adjust_down' && $balance<$data['amount'])abort(422,'Số dư cọc không đủ');
            DepositTransaction::create(['borrow_record_id'=>$record->id,'type'=>$data['type'],
                'adjusts_id'=>$original->id,'amount'=>$data['amount'],'reason'=>$data['reason'],
                'processed_by'=>auth()->id(),'processed_at'=>now()]);
        });
        return back()->with('success','Đã thêm dòng điều chỉnh tiền cọc');
    }
}
