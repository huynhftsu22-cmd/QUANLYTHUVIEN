@extends('layouts.app')
@section('title', 'Lịch sử mượn sách')
@section('content')
<h1 class="mb-3">Lịch sử mượn sách</h1>
<form method="GET" class="row g-2 mb-3"><div class="col-auto"><select name="status" class="form-select" aria-label="Lọc trạng thái" onchange="this.form.submit()"><option value="">Tất cả trạng thái</option>@foreach(config('saysach_labels.borrow_status') as $key=>$label)<option value="{{ $key }}" @selected($status===$key)>{{ $label }}</option>@endforeach</select></div></form>
<p class="small text-muted">Tiền cọc được hoàn lại sau khi trả sách và khấu trừ tiền phạt/bồi thường nếu có. Phiếu chờ nhận chưa phát sinh thu tiền. Tiền thuê được tính riêng với tiền cọc.</p>
<div class="card table-responsive"><table class="table"><thead><tr><th>Phiếu / Sách</th><th>Ngày đặt</th><th>Ngày mượn / Hạn trả</th><th>Ngày trả</th><th>Tiền thuê đã chốt</th><th>Cọc đã thu</th><th>Khấu trừ cọc</th><th>Cọc đã hoàn lại</th><th>Cọc còn đang giữ</th><th>Phạt / Bồi thường</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
@forelse($records as $r)
    @php($deposit = $r->deposit_summary)
    <tr>
        <td>#{{ $r->id }} · {{ $r->bookCopy?->book?->title }}<br><small>{{ $r->bookCopy?->copy_code }}</small></td>
        <td>{{ $r->requested_at }}</td><td>{{ $r->borrow_date ?: 'Chưa bàn giao' }}<br>{{ $r->due_date }}</td><td>{{ $r->return_date ?: '—' }}</td>
        <td>{{ $r->borrow_date ? number_format((int)$r->rental_fee, 0, ',', '.').' đ' : 'Chưa thu' }}</td>
        <td>{{ number_format($deposit['held'], 0, ',', '.') }} đ</td><td>{{ number_format($deposit['deducted'], 0, ',', '.') }} đ</td>
        <td>{{ number_format($deposit['refunded'], 0, ',', '.') }} đ</td><td>{{ number_format($deposit['balance'], 0, ',', '.') }} đ</td>
        <td>{{ number_format((int)$r->fine_amount + (int)$r->damage_fee, 0, ',', '.') }} đ</td>
        <td>{{ config('saysach_labels.borrow_status.'.$r->status, $r->status) }}</td>
        <td>@if($r->status==='pending')<form method="POST" action="{{ route('borrow.cancel',$r) }}">@csrf<button class="btn btn-sm btn-outline-danger">Hủy yêu cầu</button></form>@endif</td>
    </tr>
@empty<tr><td colspan="12" class="text-center py-4">Chưa có lịch sử mượn.</td></tr>@endforelse
</tbody></table></div><div class="mt-3">{{ $records->links() }}</div>
@endsection
