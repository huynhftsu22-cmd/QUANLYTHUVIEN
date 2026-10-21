@extends('layouts.app')
@section('title', 'Xác minh học sinh/sinh viên')
@section('content')
<h1 class="mb-3">Xác minh học sinh/sinh viên</h1>
<p class="text-muted">Học sinh và sinh viên đều có thể đăng ký thẻ thành viên. Xác minh tại đây để được áp dụng ưu đãi tiền thuê khi chưa có thẻ thành viên phù hợp; ưu đãi không cộng thêm vào phiếu đã được miễn tiền thuê bằng thẻ.</p>
<div class="card p-4 mb-4">
@if($pending)
    <h2 class="h4">Yêu cầu đang chờ duyệt</h2>
    <p class="mb-0">Bạn đã gửi xác minh {{ $pending->learner_type_label }} tại {{ $pending->school_name }}. Thư viện sẽ kiểm tra minh chứng trước khi áp dụng ưu đãi.</p>
@elseif($active)
    <h2 class="h4">Xác minh còn hiệu lực</h2>
    <p class="mb-0">Đối tượng: <strong>{{ $active->learner_type_label }}</strong> · Có hiệu lực đến {{ \Carbon\Carbon::parse($active->expires_at)->format('d/m/Y') }}.</p>
@else
    <h2 class="h4 mb-3">Gửi yêu cầu xác minh</h2>
    <form method="POST" action="{{ route('verifications.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            <div class="col-md-4"><label for="learner-type" class="form-label">Đối tượng <span aria-hidden="true">*</span></label><select id="learner-type" name="learner_type" class="form-select @error('learner_type') is-invalid @enderror" required><option value="">Chọn đối tượng</option><option value="school" @selected(old('learner_type')==='school')>Học sinh</option><option value="university" @selected(old('learner_type')==='university')>Sinh viên</option></select>@error('learner_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-8"><label for="verification-school" class="form-label">Tên trường <span aria-hidden="true">*</span></label><input id="verification-school" name="school_name" class="form-control @error('school_name') is-invalid @enderror" value="{{ old('school_name') }}" maxlength="255" required>@error('school_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="verification-code" class="form-label">Mã học sinh/sinh viên <span aria-hidden="true">*</span></label><input id="verification-code" name="student_code" class="form-control @error('student_code') is-invalid @enderror" value="{{ old('student_code') }}" maxlength="30" required>@error('student_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label for="verification-evidence" class="form-label">Minh chứng bắt buộc <span aria-hidden="true">*</span></label><input id="verification-evidence" name="evidence" type="file" class="form-control @error('evidence') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" aria-describedby="verification-evidence-help" required>@error('evidence')<div class="invalid-feedback">{{ $message }}</div>@enderror<div id="verification-evidence-help" class="form-text">Đính kèm ảnh thẻ học sinh/sinh viên hoặc giấy xác nhận đang theo học, nhìn rõ họ tên, trường và mã học sinh/sinh viên. Chấp nhận JPG, JPEG, PNG hoặc PDF, tối đa 5 MB. Nếu cần gửi nhiều mặt/trang, gộp trong một PDF. Khi biểu mẫu báo lỗi, vui lòng chọn lại tệp.</div></div>
        </div>
        <p class="text-muted mt-3">Chỉ bạn và quản trị viên được tải minh chứng. Ưu đãi chỉ áp dụng sau khi yêu cầu được duyệt và còn hiệu lực.</p>
        <button class="btn btn-primary" type="submit">Gửi xác minh</button>
    </form>
@endif
</div>
<h2 class="h4 mb-3">Lịch sử xác minh</h2>
<div class="card table-responsive"><table class="table"><thead><tr><th>Đối tượng</th><th>Trường / Mã</th><th>Trạng thái</th><th>Hạn xác minh</th><th>Minh chứng</th></tr></thead><tbody>
@forelse($items as $item)
<tr><td>{{ $item->learner_type_label }}</td><td>{{ $item->school_name }}<div class="small text-muted">{{ $item->student_code }}</div></td><td>{{ $item->status_label }}@if($item->status==='rejected')<div class="small">Lý do: {{ $item->reject_reason }}</div>@endif</td><td>{{ $item->expires_at ? \Carbon\Carbon::parse($item->expires_at)->format('d/m/Y') : '—' }}</td><td>@if($item->evidence_path)<a href="{{ route('verifications.evidence',$item) }}">Tải minh chứng</a>@else<span class="text-muted">Chưa có minh chứng</span>@endif</td></tr>
@empty<tr><td colspan="5" class="text-center py-4">Bạn chưa gửi yêu cầu xác minh.</td></tr>@endforelse
</tbody></table></div>
@endsection
