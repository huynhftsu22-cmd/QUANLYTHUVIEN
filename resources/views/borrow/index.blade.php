@extends('layouts.app')
@section('title', 'Đặt mượn và bàn giao')
@section('content')
<h2 class="mb-4">Đặt mượn và bàn giao</h2>
<div class="card p-3 table-responsive"><table class="table"><tr><th>Mã phiếu</th><th>Độc giả</th><th>Sách</th><th>Ngày đặt</th><th>Trạng thái</th><th></th></tr>@foreach($records as $r)<tr><td>#{{ $r->id }}</td><td>{{ $r->user?->name }}</td><td>{{ $r->bookCopy?->book?->title }}</td><td>{{ $r->requested_at ? \Carbon\Carbon::parse($r->requested_at)->format('d/m/Y H:i') : '—' }}</td><td>{{ config('saysach_labels.borrow_status.'.$r->status, 'Chưa xác định') }}</td><td>@if($r->status==='pending')<a class="btn btn-sm btn-primary" href="{{ route('borrow.preview',$r) }}">Xem tiền & xác nhận giao</a><form method="POST" action="{{ route('borrow.cancel',$r) }}" class="mt-1">@csrf<input name="cancel_reason" required minlength="5" maxlength="255" class="form-control form-control-sm" placeholder="Lý do quản trị hủy (bắt buộc)"><button class="btn btn-sm btn-outline-danger">Hủy giữ chỗ</button></form>@endif</td></tr>@endforeach</table>{{ $records->links() }}</div>
@endsection
