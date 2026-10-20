<?php
namespace App\Http\Controllers;
use App\Models\{StudentVerification, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
class StudentVerificationController extends Controller
{
    public function index()
    {
        $items = StudentVerification::with('user')->latest()->paginate(20);
        return view('verifications.index',compact('items'));
    }
    public function mine()
    {
        $items = StudentVerification::where('user_id',auth()->id())->latest()->get();
        $pending = $items->firstWhere('status','pending');
        $active = StudentVerification::activeFor(auth()->user());
        return view('verifications.mine',compact('items','pending','active'));
    }
    public function store(Request $request)
    {
        $values = $request->validate([
            'learner_type'=>'required|in:school,university',
            'school_name'=>'required|string|max:255',
            'student_code'=>'required|string|max:30',
            'evidence'=>'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'learner_type.required'=>'Vui lòng chọn học sinh hoặc sinh viên.',
            'learner_type.in'=>'Đối tượng xác minh không hợp lệ.',
            'evidence.required'=>'Vui lòng đính kèm ảnh hoặc PDF minh chứng trước khi gửi xác minh.',
            'evidence.file'=>'Tệp minh chứng không hợp lệ.',
            'evidence.uploaded'=>'Không tải được minh chứng. Vui lòng chọn lại tệp tối đa 5 MB.',
            'evidence.mimes'=>'Minh chứng phải là ảnh JPG, JPEG, PNG hoặc tệp PDF.',
            'evidence.max'=>'Minh chứng không được vượt quá 5 MB.',
        ], ['school_name'=>'Tên trường','student_code'=>'Mã học sinh/sinh viên']);
        $path = null;
        try {
            DB::transaction(function () use ($request,$values,&$path) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                if (StudentVerification::where('user_id',$user->id)->where('status','pending')->exists()) {
                    throw ValidationException::withMessages(['verification'=>'Bạn đã có yêu cầu xác minh chờ duyệt.']);
                }
                if (StudentVerification::activeFor($user)) {
                    throw ValidationException::withMessages(['verification'=>'Xác minh của bạn vẫn còn hiệu lực.']);
                }
                $path = $request->file('evidence')->store('student-evidence','local');
                if (!$path) throw ValidationException::withMessages(['evidence'=>'Không lưu được minh chứng. Vui lòng gửi lại.']);
                StudentVerification::create([
                    'user_id'=>$user->id,'learner_type'=>$values['learner_type'],'school_name'=>$values['school_name'],
                    'student_code'=>$values['student_code'],'evidence_path'=>$path,'status'=>'pending',
                ]);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        return redirect()->route('verifications.mine')->with('success','Đã gửi xác minh học sinh/sinh viên. Ưu đãi chỉ áp dụng sau khi được duyệt và còn hiệu lực.');
    }
    public function evidence(StudentVerification $verification)
    {
        if (auth()->user()->role !== 'admin' && $verification->user_id !== auth()->id()) abort(403);
        if (!$verification->evidence_path || !Storage::disk('local')->exists($verification->evidence_path)) abort(404);
        return Storage::disk('local')->download($verification->evidence_path);
    }
    public function review(Request $request, StudentVerification $verification)
    {
        $values = $request->validate([
            'status'=>'required|in:approved,rejected',
            'reject_reason'=>'required_if:status,rejected|nullable|string|min:5|max:255',
            'expires_at'=>'required_if:status,approved|nullable|date|after_or_equal:today',
        ], [
            'reject_reason.required_if'=>'Vui lòng ghi lý do từ chối.',
            'expires_at.required_if'=>'Vui lòng chọn ngày hết hạn xác minh.',
            'expires_at.date'=>'Ngày hết hạn xác minh không hợp lệ.',
            'expires_at.after_or_equal'=>'Ngày hết hạn phải từ hôm nay trở đi.',
        ], ['reject_reason'=>'Lý do từ chối','expires_at'=>'Ngày hết hạn','status'=>'Trạng thái']);
        $changed = DB::transaction(function () use ($verification,$values) {
            $user = User::whereKey($verification->user_id)->lockForUpdate()->firstOrFail();
            $item = StudentVerification::whereKey($verification->id)->lockForUpdate()->firstOrFail();
            if ($item->status !== 'pending') return false;
            if ($values['status'] === 'approved') {
                if (!$item->evidence_path || !Storage::disk('local')->exists($item->evidence_path)) {
                    throw ValidationException::withMessages(['evidence'=>'Yêu cầu thiếu minh chứng hoặc tệp không còn tồn tại. Vui lòng từ chối và đề nghị độc giả gửi lại đầy đủ.']);
                }
                if (StudentVerification::where('user_id',$user->id)->where('status','approved')->whereDate('expires_at','>=',today())->whereKeyNot($item->id)->exists()) {
                    throw ValidationException::withMessages(['verification'=>'Độc giả đã có xác minh còn hiệu lực.']);
                }
            }
            $item->update(['status'=>$values['status'],'reject_reason'=>$values['status']==='rejected'?$values['reject_reason']:null,
                'verified_by'=>auth()->id(),'verified_at'=>today()->toDateString(),'expires_at'=>$values['status']==='approved'?$values['expires_at']:null]);
            return true;
        });
        return redirect()->route('verifications.index')->with('success',$changed?'Đã xử lý yêu cầu xác minh.':'Yêu cầu này đã được xử lý trước đó.');
    }
    public function confirmDelete(StudentVerification $verification)
    {
        $verification->load('user');
        return view('verifications.delete',compact('verification'));
    }
    public function destroy(Request $request, StudentVerification $verification)
    {
        $request->validate(['confirm_delete'=>'accepted'],[
            'confirm_delete.accepted'=>'Vui lòng xác nhận xóa hồ sơ xác minh này.',
        ]);
        $path = DB::transaction(function () use ($verification) {
            User::whereKey($verification->user_id)->lockForUpdate()->firstOrFail();
            $item = StudentVerification::whereKey($verification->id)->lockForUpdate()->firstOrFail();
            $path = $item->evidence_path;
            $item->delete();
            return $path;
        });
        // Clean up only after the record is committed; never delete another record's attachment.
        if ($path && !StudentVerification::where('evidence_path',$path)->exists()) {
            try {
                if (!Storage::disk('local')->delete($path)) {
                    \Illuminate\Support\Facades\Log::warning('Verification evidence cleanup failed after deletion.');
                }
            } catch (\Throwable $error) { report($error); }
        }
        return redirect()->route('verifications.index')->with('success','Đã xóa hồ sơ xác minh #'.$verification->id.'. Tài khoản, thẻ thành viên và lịch sử mượn được giữ nguyên. Độc giả có thể gửi xác minh mới.');
    }

}
