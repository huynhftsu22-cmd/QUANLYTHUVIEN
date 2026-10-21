@extends('layouts.app')
@section('title', 'Xóa hồ sơ xác minh')
@section('content')
<h1 class="mb-3">Xóa hồ sơ xác minh</h1>
<div class="card p-4" style="max-width:760px">
    <h2 class="h4">Hồ sơ #{{ $verification->id }}</h2>
    <dl class="row mb-3">
        <dt class="col-sm-4">Độc giả</dt><dd class="col-sm-8">{{ $verification->user?->name }} · {{ $verification->user?->user_code }}</dd>
        <dt class="col-sm-4">Đối tượng</dt><dd class="col-sm-8">{{ $verification->learner_type_label }}</dd>
        <dt class="col-sm-4">Trường / Mã</dt><dd class="col-sm-8">{{ $verification->school_name }} / {{ $verification->student_code }}</dd>
        <dt class="col-sm-4">Trạng thái</dt><dd class="col-sm-8">{{ $verification->status_label }}</dd>
    </dl>
    <p>Hồ sơ xác minh này và tệp minh chứng đi kèm sẽ bị xóa. Tài khoản độc giả, thẻ thành viên, phiếu mượn và giao dịch tiền vẫn được giữ nguyên.</p>
    <p>Ưu đãi học sinh/sinh viên cho lượt mượn mới chỉ áp dụng nếu độc giả còn xác minh khác được duyệt và còn hạn. Phiếu đã bàn giao giữ nguyên số tiền đã chốt. Độc giả có thể gửi xác minh mới với minh chứng đầy đủ.</p>
    <form method="POST" action="{{ route('verifications.destroy',$verification) }}">
        @csrf @method('DELETE')
        <div class="form-check mb-3"><input id="confirm-delete-verification" name="confirm_delete" type="checkbox" value="1" class="form-check-input" required><label for="confirm-delete-verification" class="form-check-label">Tôi xác nhận xóa hồ sơ xác minh #{{ $verification->id }}.</label></div>
        <div class="d-flex flex-wrap gap-2"><button class="btn btn-danger" type="submit">Xóa hồ sơ xác minh</button><a class="btn btn-outline-secondary" href="{{ route('verifications.index') }}">Hủy, giữ hồ sơ</a></div>
    </form>
</div>
@endsection
