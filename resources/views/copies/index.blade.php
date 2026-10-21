@extends('layouts.app')
@section('title', 'Quản lý bản sao')
@section('content')
<h1 class="mb-4">Quản lý bản sao</h1>
<p>Đầu sách: <strong>{{ $book->title }}</strong></p>
<div class="card p-3 mb-3"><h5>Thêm bản sao (1–50 bản, mã tự sinh)</h5><form method="POST" action="{{ route('copies.store',$book) }}">@csrf<div class="row g-2"><div class="col"><input name="quantity" type="number" min="1" max="50" value="{{ old('quantity',1) }}" class="form-control" aria-label="Số lượng bản sao" required></div><div class="col"><input name="shelf_location" class="form-control" placeholder="Vị trí kệ" aria-label="Vị trí kệ" value="{{ old('shelf_location') }}" maxlength="50"></div><div class="col-auto"><button class="btn btn-primary">Thêm bản sao</button></div></div></form></div>
<button type="button" class="btn btn-outline-primary mb-3" onclick="window.print()">In mã vạch</button>
<div class="card p-3 table-responsive"><table class="table copy-table"><thead><tr><th>Mã bản sao / Mã vạch</th><th>Trạng thái</th><th>Vị trí</th><th>Ghi chú</th><th>Thao tác</th></tr></thead><tbody>
@forelse($copies as $copy)
    @php($locked = in_array($copy->status,['borrowed','reserved']))
    <tr>
        <td><strong>{{ $copy->copy_code }}</strong><div class="copy-barcode mt-1">{!! (new \Picqer\Barcode\BarcodeGeneratorSVG())->getBarcode($copy->copy_code, \Picqer\Barcode\BarcodeGeneratorSVG::TYPE_CODE_128, 1.4, 34) !!}</div></td>
        <td><select name="status" class="form-select" form="copy-form-{{ $copy->id }}" aria-label="Trạng thái {{ $copy->copy_code }}" @disabled($locked)>
            @if($locked)<option value="{{ $copy->status }}" selected>{{ config('saysach_labels.copy_status.'.$copy->status,'Chưa xác định') }}</option>
            @else @foreach(['available','damaged','maintenance','lost','retired'] as $status)<option value="{{ $status }}" @selected($copy->status===$status)>{{ config('saysach_labels.copy_status.'.$status,'Chưa xác định') }}</option>@endforeach @endif
        </select></td>
        <td><input name="shelf_location" class="form-control" value="{{ $copy->shelf_location }}" maxlength="50" aria-label="Vị trí kệ {{ $copy->copy_code }}" form="copy-form-{{ $copy->id }}" @disabled($locked)></td>
        <td><input name="condition_note" class="form-control" value="{{ $copy->condition_note }}" maxlength="255" aria-label="Ghi chú {{ $copy->copy_code }}" form="copy-form-{{ $copy->id }}" @disabled($locked)></td>
        <td><form id="copy-form-{{ $copy->id }}" method="POST" action="{{ route('copies.update',$copy) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary" @disabled($locked)>Lưu</button></form></td>
    </tr>
@empty<tr><td colspan="5" class="text-center py-4">Chưa có bản sao.</td></tr>@endforelse
</tbody></table></div>
@endsection
