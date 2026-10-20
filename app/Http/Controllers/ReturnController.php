<?php
namespace App\Http\Controllers;
use App\Models\{BorrowRecord, DepositTransaction, Payment};
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index()
    {
        $records = BorrowRecord::with(['user','bookCopy.book'])->whereIn('status',['borrowing','overdue'])->latest()->paginate(25);
        return view('returns.index', compact('records'));
    }

    public function preview(Request $request,BorrowRecord $record,PricingService $pricing){
        try {
        if ($record->status === 'returned') return redirect()->route('returns.index')->with('success','Phiếu #'.$record->id.' đã được quyết toán trước đó. Không ghi nhận thu hoặc hoàn cọc thêm.');
        $data=$request->validate(['condition'=>'required|in:good,damaged,lost','copy_code'=>'required_unless:condition,lost|nullable|string|max:40','damage_note'=>'nullable|string|max:255','damage_amount'=>'nullable|integer|min:0']);
        $record->load(['user','bookCopy.book','rentalPolicy']);
        if(!in_array($record->status,['borrowing','overdue'],true))throw \Illuminate\Validation\ValidationException::withMessages(['settlement'=>'Phiếu đã kết thúc']);
        if($data['condition']!=='lost' && mb_strtoupper(trim($data['copy_code']??''))!==mb_strtoupper(trim($record->bookCopy->copy_code)))throw \Illuminate\Validation\ValidationException::withMessages(['copy_code'=>'Mã bản sao không đúng']);
        if(isset($data['damage_amount']) && ($data['condition']!=='damaged'||empty($data['damage_note'])))throw \Illuminate\Validation\ValidationException::withMessages(['settlement'=>'Cần ghi lý do điều chỉnh phí hư hỏng']);
        $quote=$pricing->settle($record,$data['condition'],today(),$data['damage_amount']??null);
        return view('returns.preview',compact('record','data','quote'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) throw $e;
            return redirect()->route('returns.index')->withErrors($e->errors())->withInput();
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('returns.index')->withErrors(['settlement'=>$e->getMessage()])->withInput();
        }
    }
    public function store(Request $request, BorrowRecord $record, PricingService $pricing)
    {
        try {
        $data = $request->validate([
            'condition' => 'required|in:good,damaged,lost',
            'copy_code' => 'required_unless:condition,lost|nullable|string|max:40',
            'damage_note' => 'nullable|string|max:255',
            'damage_amount' => 'nullable|integer|min:0',
            'method' => 'required|in:cash,transfer',
            'refund_method' => 'nullable|in:cash,transfer',
            'expected_late'=>'required|integer|min:0',
            'expected_damage'=>'required|integer|min:0',
            'expected_deduct'=>'required|integer|min:0',
            'expected_refund'=>'required|integer|min:0',
        ]);
        $processed = DB::transaction(function () use ($record, $data, $pricing) {
            $record = BorrowRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            // Serialize retries; an already settled record must never create a second refund.
            if ($record->status === 'returned') return false;
            if (!in_array($record->status, ['borrowing','overdue'], true)) throw \Illuminate\Validation\ValidationException::withMessages(['settlement'=>'Phiếu đã kết thúc']);
            if ($data['condition'] !== 'lost' && mb_strtoupper(trim($data['copy_code'] ?? '')) !== mb_strtoupper(trim($record->bookCopy->copy_code))) throw \Illuminate\Validation\ValidationException::withMessages(['copy_code'=>'Mã bản sao không trùng phiếu']);
            if (isset($data['damage_amount']) && ($data['condition'] !== 'damaged' || empty($data['damage_note']))) throw \Illuminate\Validation\ValidationException::withMessages(['settlement'=>'Điều chỉnh phí hư hỏng cần ghi rõ lý do']);
            $record->load(['rentalPolicy','bookCopy']);
            $result = $pricing->settle($record, $data['condition'], today(), $data['damage_amount'] ?? null);
            foreach(['late_fee'=>'expected_late','damage_fee'=>'expected_damage','deduct'=>'expected_deduct','refund'=>'expected_refund'] as $field=>$expected){
                if($result[$field] !== (int)$data[$expected])throw \Illuminate\Validation\ValidationException::withMessages(['settlement'=>'Số tiền đã thay đổi, vui lòng xem lại bảng xác nhận']);
            }
            $record->update([
                'status'=>'returned','return_date'=>today(),'return_condition'=>$data['condition'],
                'fine_amount'=>$result['late_fee'],'damage_fee'=>$result['damage_fee'],
                'damage_note'=>$data['damage_note'] ?? null,
            ]);
            $record->bookCopy()->update(['status'=>match($data['condition']) {'good'=>'available','damaged'=>'damaged','lost'=>'lost'}]);
            if ($result['deduct'] > 0) DepositTransaction::create([
                'borrow_record_id'=>$record->id,'type'=>'deduct','amount'=>$result['deduct'],
                'reason'=>'Khấu trừ phạt/bồi thường','processed_by'=>auth()->id(),'processed_at'=>now(),
            ]);
            if ($result['refund'] > 0) DepositTransaction::create([
                'borrow_record_id'=>$record->id,'type'=>'refund','amount'=>$result['refund'],
                'reason'=>'Hoàn lại tiền cọc bằng '.(($data['refund_method'] ?? $data['method']) === 'cash' ? 'tiền mặt' : 'chuyển khoản'),'processed_by'=>auth()->id(),'processed_at'=>now(),
            ]);
            // BR-18: ghi nhận ĐỦ số tiền phạt/bồi thường một lần, bao gồm phần trừ từ cọc.
            $deductLeft = $result['deduct'];
            foreach (['late_fine'=>$result['late_fee'], 'damage_fee'=>$result['damage_fee']] as $type=>$amount) {
                if (!$amount) continue;
                $fromDeposit = min($deductLeft, $amount);
                $deductLeft -= $fromDeposit;
                foreach (['deposit'=>$fromDeposit, $data['method']=>$amount-$fromDeposit] as $method=>$part) {
                    if ($part <= 0) continue;
                    Payment::create([
                        'user_id'=>$record->user_id,'borrow_record_id'=>$record->id,'type'=>$type,
                        'amount'=>$part,'method'=>$method,'status'=>'paid','paid_at'=>now(),
                        'received_by'=>auth()->id(),
                    ]);
                }
            }
            return true;
        });
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) throw $e;
            return redirect()->route('returns.index')->withErrors($e->errors())->withInput();
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('returns.index')->withErrors(['settlement'=>$e->getMessage()])->withInput();
        }
        return redirect()->route('returns.index')->with('success', $processed
            ? 'Đã quyết toán phiếu #'.$record->id.'. Đã ghi nhận trả sách và các khoản thu, khấu trừ, hoàn cọc.'
            : 'Phiếu #'.$record->id.' đã được quyết toán trước đó. Không ghi nhận thu hoặc hoàn cọc thêm.');
    }
}
