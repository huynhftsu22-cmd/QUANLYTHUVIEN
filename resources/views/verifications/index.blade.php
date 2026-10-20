@extends('layouts.app')
@section('title', 'Xác minh học sinh/sinh viên')
@section('content')
<style>
.verification-page .verification-intro{max-width:780px;margin-bottom:24px;color:#65716b}
.verification-page .verification-table{min-width:880px;margin-bottom:0;table-layout:fixed}
.verification-page .verification-table th{padding:15px 20px;font-size:12px;font-weight:700;white-space:nowrap}
.verification-page .verification-table td{padding:22px 20px;vertical-align:top;overflow-wrap:anywhere;line-height:1.6}
.verification-page .verification-reader{font-weight:600;color:#254633}
.verification-page .verification-detail{margin-top:5px;color:#6b796f;font-size:12px}
.verification-page .verification-school{margin-top:7px}
.verification-page .verification-pill{display:inline-flex;align-items:center;padding:5px 10px;border-radius:7px;font-size:12px;font-weight:600;line-height:1.5}
.verification-page .verification-pill.approved{background:#eaf2e8;color:#315d3c}
.verification-page .verification-pill.pending{background:#f6efdF;color:#7b5b20}
.verification-page .verification-pill.rejected,.verification-page .verification-pill.expired{background:#f0efeb;color:#62675d}
.verification-page .verification-evidence-missing{display:inline-block;color:#89682c;background:#faf3e4;border:1px solid #eddfbf;border-radius:7px;padding:5px 9px;font-size:12px;white-space:nowrap}
.verification-page .verification-document{display:inline-block;color:#315d3c;font-size:13px;text-underline-offset:3px}
.verification-page .verification-actions{display:flex;flex-direction:column;align-items:flex-start;gap:14px}
.verification-page .verification-delete{display:inline-flex;align-items:center;justify-content:center;border:1px solid #e6d4cf;border-radius:8px;background:#fffefa;color:#9a4f40;padding:7px 12px;font-size:12px;font-weight:500;line-height:1.5;text-decoration:none;white-space:nowrap;min-height:34px}
.verification-page .verification-delete:hover{background:#faf0ed;color:#833f33;border-color:#ceaaa0}
.verification-page .verification-delete:focus-visible{outline:3px solid #bd9270;outline-offset:2px}
.verification-page .verification-review{width:100%;min-width:0}
.verification-page .verification-review .form-label{line-height:1.5}
.verification-page .verification-pagination:empty{display:none}
.verification-page .verification-pagination{padding:16px 20px 0}
@media(pointer:coarse){.verification-page .verification-delete{min-height:44px}}
</style>
<section class="verification-page">
    <h1 class="mb-2">Xác minh học sinh/sinh viên</h1>
    <p class="verification-intro">Kiểm tra thông tin và minh chứng trước khi duyệt ưu đãi cho độc giả.</p>
    <div class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table verification-table">
                <colgroup><col style="width:16%"><col style="width:30%"><col style="width:17%"><col style="width:17%"><col style="width:20%"></colgroup>
                <thead><tr><th scope="col">Độc giả</th><th scope="col">Thông tin xác minh</th><th scope="col">Minh chứng</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    @php($state = $item->status === 'approved' && $item->expires_at && \Carbon\Carbon::parse($item->expires_at)->lt(today()) ? 'expired' : $item->status)
                    <tr>
                        <td><div class="verification-reader">{{ $item->user?->name }}</div><div class="verification-detail">{{ $item->user?->user_code }}</div></td>
                        <td><strong>{{ $item->learner_type_label }}</strong><div class="verification-school">{{ $item->school_name }}</div><div class="verification-detail">Mã: {{ $item->student_code }}</div></td>
                        <td>@if($item->evidence_path)<a class="verification-document" href="{{ route('verifications.evidence',$item) }}">Tải minh chứng</a>@else<span class="verification-evidence-missing">Thiếu minh chứng</span>@endif</td>
                        <td><span class="verification-pill {{ $state }}">{{ $item->status_label }}</span>@if($item->expires_at)<div class="verification-detail">Đến {{ \Carbon\Carbon::parse($item->expires_at)->format('d/m/Y') }}</div>@endif @if($item->reject_reason)<div class="verification-detail">{{ $item->reject_reason }}</div>@endif</td>
                        <td><div class="verification-actions">
                            @if($item->status==='pending')
<form method="POST" action="{{ route('verifications.review',$item) }}" class="verification-review">
    @csrf @method('PATCH')
    <label class="form-label small" for="verification-status-{{ $item->id }}">Quyết định</label><select id="verification-status-{{ $item->id }}" name="status" class="form-select form-select-sm mb-2" required><option value="approved" @disabled(!$item->evidence_path)>Duyệt</option><option value="rejected" @selected(!$item->evidence_path)>Từ chối</option></select>
    <label class="form-label small" for="verification-expiry-{{ $item->id }}">Hạn xác minh (bắt buộc khi duyệt)</label><input id="verification-expiry-{{ $item->id }}" type="date" name="expires_at" class="form-control form-control-sm mb-2" min="{{ today()->toDateString() }}" value="{{ now()->addYear()->toDateString() }}">
    <label class="form-label small" for="verification-reason-{{ $item->id }}">Lý do (bắt buộc khi từ chối)</label><input id="verification-reason-{{ $item->id }}" name="reject_reason" class="form-control form-control-sm" maxlength="255">
    <button class="btn btn-sm btn-primary mt-2" type="submit">Xác nhận</button>
</form>
                            @endif
                            <a class="verification-delete" href="{{ route('verifications.confirm-delete',$item) }}" aria-label="Xóa hồ sơ xác minh #{{ $item->id }} của {{ $item->user?->name }}">Xóa hồ sơ</a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5 text-muted">Chưa có yêu cầu xác minh.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="verification-pagination">{{ $items->links() }}</div>@endif
    </div>
</section>
@endsection
