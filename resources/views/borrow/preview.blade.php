@extends('layouts.app')
@section('title','Xác nhận bàn giao và thu tiền')
@section('content')
<h2>Xác nhận bàn giao và thu tiền</h2>
<div class="card p-4">
<p>Phiếu #{{ $record->id }} – {{ $record->user?->name }} – {{ $record->bookCopy?->book?->title }}</p>
<p>Mã bản sao: <strong>{{ $record->bookCopy?->copy_code }}</strong> | Số ngày: {{ $record->rental_days }} | Hạn trả dự kiến: {{ $quote['due_date'] }}</p>
@if($warning)<div class="alert alert-warning">Thẻ thành viên không phủ hết thời gian mượn. Phí thuê đã được tính theo mức không có thẻ.</div>@endif
<table class="table"><tr><th>Phí thuê sau ưu đãi</th><td>{{ number_format($quote['rental_fee']) }} đ</td></tr><tr><th>Đã giảm trong phí thuê</th><td>{{ number_format($quote['discount_amount']) }} đ</td></tr><tr><th>Tiền cọc</th><td>{{ number_format($quote['deposit_amount']) }} đ</td></tr><tr><th>Tổng cần thu tại quầy</th><td><strong>{{ number_format($quote['rental_fee']+$quote['deposit_amount']) }} đ</strong></td></tr></table>
<p class="deposit-explanation">Tiền cọc được giữ riêng và hoàn lại khi trả sách sau khi khấu trừ phạt/bồi thường nếu có. Nếu trả đúng hạn và nguyên vẹn, độc giả được hoàn đủ {{ number_format($quote['deposit_amount'], 0, ',', '.') }} đ tiền cọc. Phí thuê không được tính vào tiền hoàn cọc.</p>
<form method="POST" id="handover-form" data-copy-code="{{ $record->bookCopy->copy_code }}" action="{{ route('borrow.handover',$record) }}">@csrf
<input type="hidden" name="expected_fee" value="{{ $quote['rental_fee'] }}"><input type="hidden" name="expected_deposit" value="{{ $quote['deposit_amount'] }}">
<label for="handover-copy-code" class="form-label">Nhập mã bản sao đang bàn giao</label>
<input id="handover-copy-code" class="form-control @error('copy_code') is-invalid @enderror" name="copy_code" value="{{ old('copy_code') }}" required maxlength="40" autocomplete="off" spellcheck="false" aria-describedby="copy-code-help copy-code-error">
<div class="form-text" id="copy-code-help">Phiếu này chỉ bàn giao bản sao <strong>{{ $record->bookCopy->copy_code }}</strong>. Nhập mã trên đúng cuốn sách, không dùng mã của đầu sách khác.</div>
<p id="copy-code-error" class="text-danger mt-2" role="alert" @if(!$errors->has('copy_code')) hidden @endif>{{ $errors->first('copy_code') }}</p>
<label>Phương thức thu tại quầy</label><select class="form-select" name="method"><option value="cash" @selected(old('method','cash')==='cash')>Tiền mặt</option><option value="transfer" @selected(old('method')==='transfer')>Chuyển khoản</option></select>
<button class="btn btn-primary mt-3">Xác nhận đã thu và giao sách</button>
<a class="btn btn-outline-secondary mt-3" href="{{ route('borrow.index') }}">Quay lại</a></form></div>
<script>
(() => {
 const form = document.getElementById('handover-form');
 const input = document.getElementById('handover-copy-code');
 const error = document.getElementById('copy-code-error');
 input.addEventListener('input', () => { error.hidden = true; input.classList.remove('is-invalid'); });
 form.addEventListener('submit', event => {
  const expected = form.dataset.copyCode.trim().toLocaleUpperCase('vi');
  if (input.value.trim().toLocaleUpperCase('vi') !== expected) {
   event.preventDefault();
   error.textContent = 'Mã bản sao không đúng. Phiếu này yêu cầu ' + form.dataset.copyCode + '.';
   error.hidden = false;
   input.classList.add('is-invalid');
   input.focus();
   return;
  }
  if (!window.confirm('Đã nhận đủ tiền và xác nhận bàn giao đúng bản sao ' + form.dataset.copyCode + '?')) event.preventDefault();
 });
})();
</script>
@endsection
