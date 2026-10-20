@extends('layouts.app')
@section('title','Đối soát thanh toán và tiền cọc')
@section('content')
<h2>Đối soát thanh toán và tiền cọc</h2><p class="text-muted">Dữ liệu thu tại quầy. Không xóa giao dịch đã ghi.</p>
<div class="card p-3 table-responsive mb-3"><h5>Khoản thu</h5><table class="table table-sm"><thead><tr><th>Mã giao dịch</th><th>Độc giả</th><th>Loại</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th><th>Ghi chú</th><th>Thao tác</th></tr></thead><tbody>
@foreach($payments as $p)<tr><td>#{{ $p->id }}</td><td>{{ $p->user?->name }}</td><td>{{ config('saysach_labels.payment_type.'.$p->type, $p->type) }}</td><td>{{ number_format($p->amount) }}đ</td><td>{{ config('saysach_labels.payment_method.'.$p->method, $p->method) }}</td><td>{{ config('saysach_labels.payment_status.'.$p->status, $p->status) }}</td><td>{{ $p->reference }}</td><td>@if($p->status==='paid')<form method="POST" action="{{ route('payments.cancel',$p) }}">@csrf @method('PATCH')<input class="form-control form-control-sm" name="reason" placeholder="Lý do hủy" required minlength="5"><button class="btn btn-sm btn-outline-danger mt-1">Hủy ghi nhận sai</button></form>@endif</td></tr>@endforeach
</tbody></table>{{ $payments->links() }}</div>
<div class="card p-3 table-responsive mb-3"><h5>Tiền cọc theo phiếu mượn gần đây</h5>
<p class="small text-muted">Tiền cọc giữ riêng, hoàn lại khi trả sách sau khấu trừ phạt/bồi thường. Khoản hoàn cọc không phải doanh thu và không nằm trong bảng khoản thu.</p>
<table class="table table-sm"><thead><tr><th>Phiếu / Độc giả</th><th>Sách</th><th>Cọc đã thu</th><th>Đã khấu trừ</th><th>Đã hoàn lại</th><th>Còn đang giữ</th></tr></thead><tbody>
@forelse($depositRecords as $record)
    @php($summary = $record->deposit_summary)
    <tr><td>#{{ $record->id }} · {{ $record->user?->name }}</td><td>{{ $record->bookCopy?->book?->title }}</td>
    @foreach(['held','deducted','refunded','balance'] as $key)<td>{{ number_format($summary[$key], 0, ',', '.') }} đ</td>@endforeach</tr>
@empty<tr><td colspan="6" class="text-center">Chưa có giao dịch tiền cọc.</td></tr>@endforelse
</tbody></table></div>
<div class="card p-3 table-responsive"><h5>Chi tiết thu, khấu trừ và hoàn lại tiền cọc</h5><table class="table table-sm"><thead><tr><th>Mã giao dịch</th><th>Phiếu</th><th>Loại</th><th>Số tiền</th><th>Lý do</th><th>Điều chỉnh</th></tr></thead><tbody>
@foreach($deposits as $d)<tr><td>#{{ $d->id }}</td><td>#{{ $d->borrow_record_id }}<br>{{ $d->borrowRecord?->user?->name }}</td><td>{{ config('saysach_labels.deposit_type.'.$d->type, $d->type) }}</td><td>{{ number_format($d->amount) }}đ</td><td>{{ $d->reason }}</td><td>@if(in_array($d->type, ['hold','refund','deduct'], true) && !\App\Models\DepositTransaction::where('adjusts_id', $d->id)->exists())<form method="POST" action="{{ route('deposits.adjust',$d) }}" class="d-flex flex-wrap gap-1">@csrf<select name="type" class="form-select form-select-sm" style="width:190px">@if($d->type === 'hold')<option value="adjust_down">Giảm cọc đã thu</option>@else<option value="adjust_up">Khôi phục số dư cọc</option>@endif</select><input type="number" name="amount" min="1" class="form-control form-control-sm" style="width:110px" placeholder="Số tiền" required><input name="reason" class="form-control form-control-sm" style="width:150px" placeholder="Lý do" required minlength="5"><button class="btn btn-sm btn-outline-primary">Ghi điều chỉnh</button></form>@else<span class="text-muted">Đã điều chỉnh</span>@endif</td></tr>@endforeach
</tbody></table></div>
@endsection
