@extends('layouts.app')
@section('title','Xác nhận quyết toán trả sách')
@section('content')
<h2>Xác nhận tiền trả sách</h2><div class="card p-4">
<p>Phiếu #{{ $record->id }} – {{ $record->user?->name }} – {{ $record->bookCopy?->book?->title }}</p>
<p>Tình trạng: {{ config('saysach_labels.condition.'.$data['condition'], $data['condition']) }} | Trễ {{ $quote['late_days'] }} ngày</p>
<table class="table"><tr><th>Tiền cọc đang giữ trước quyết toán</th><td>{{ number_format($quote['deposit_held'], 0, ',', '.') }} đ</td></tr><tr><th>Phạt trễ</th><td>{{ number_format($quote['late_fee']) }} đ</td></tr><tr><th>Phí hư hỏng/mất</th><td>{{ number_format($quote['damage_fee']) }} đ</td></tr><tr><th>Khấu trừ cọc</th><td>{{ number_format($quote['deduct']) }} đ</td></tr><tr><th>Tiền cọc hoàn lại cho độc giả</th><td>{{ number_format($quote['refund']) }} đ</td></tr><tr><th>Cần thu thêm</th><td><strong>{{ number_format($quote['extra_payment']) }} đ</strong></td></tr></table>
<form method="POST" id="settlement-form" action="{{ route('returns.store',$record) }}">@csrf
@foreach(['condition','copy_code','damage_note','damage_amount'] as $key)<input type="hidden" name="{{ $key }}" value="{{ $data[$key]??'' }}">@endforeach
<input type="hidden" name="expected_late" value="{{ $quote['late_fee'] }}"><input type="hidden" name="expected_damage" value="{{ $quote['damage_fee'] }}"><input type="hidden" name="expected_deduct" value="{{ $quote['deduct'] }}"><input type="hidden" name="expected_refund" value="{{ $quote['refund'] }}">
@if($quote['refund'] > 0)
<label class="form-label mt-3" for="refund-method">Phương thức hoàn lại {{ number_format($quote['refund'], 0, ',', '.') }} đ tiền cọc</label>
<select class="form-select mb-3" id="refund-method" name="refund_method" required><option value="cash">Hoàn bằng tiền mặt</option><option value="transfer">Hoàn bằng chuyển khoản</option></select>
<p class="deposit-explanation">Chỉ xác nhận sau khi đã hoàn tiền cọc cho độc giả theo phương thức đã chọn. Tiền thuê đã thu không thuộc khoản hoàn này.</p>
@else
<p class="deposit-explanation">Phiếu này không có tiền cọc để hoàn sau khấu trừ. Nếu cần thu thêm, nhận đủ số tiền bên trên trước khi xác nhận.</p>
@endif
@if($quote['extra_payment'] > 0)
<label>Phương thức thu thêm</label><select class="form-select" name="method"><option value="cash">Tiền mặt</option><option value="transfer">Chuyển khoản</option></select>
@else
<input type="hidden" name="method" value="cash">
@endif
<button class="btn btn-primary mt-3">Xác nhận quyết toán</button>
<a class="btn btn-outline-secondary mt-3" href="{{ route('returns.index') }}">Quay lại</a></form></div>
<script>
(() => {
 const form = document.getElementById('settlement-form');
 let submitting = false;
 form.addEventListener('submit', event => {
  if (submitting) { event.preventDefault(); return; }
  if (!window.confirm('Đã đối chiếu tiền, nhận sách và hoàn cọc/thu thêm đúng số tiền hiển thị?')) { event.preventDefault(); return; }
  submitting = true;
  const button = form.querySelector('button[type="submit"], button');
  button.disabled = true;
  button.textContent = 'Đang xử lý…';
 });
 window.addEventListener('pageshow', () => {
  submitting = false;
  const button = form.querySelector('button[type="submit"], button');
  button.disabled = false;
  button.textContent = 'Xác nhận quyết toán';
 });
})();
</script>
@endsection
