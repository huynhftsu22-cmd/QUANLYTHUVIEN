@extends('layouts.app')
@section('title', 'Sửa người dùng')
@section('content')
<div class="card p-4">
    <h1 class="h3">Sửa {{ $user->user_code }}</h1>
    <p class="text-muted">Mã người dùng và vai trò không được chỉnh thủ công.</p>
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Mã người dùng</label>
                <input class="form-control" value="{{ $user->user_code }}" disabled>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Vai trò</label>
                <input class="form-control" value="{{ $user->role === 'admin' ? 'Admin' : 'Độc giả' }}" disabled>
            </div>
            @foreach(['name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Số điện thoại', 'address' => 'Địa chỉ'] as $field => $label)
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ $label }} @if(in_array($field, ['name', 'email']))<span class="text-danger">*</span>@endif</label>
                    <input class="form-control @error($field) is-invalid @enderror" name="{{ $field }}" value="{{ old($field, $user->$field) }}" {{ in_array($field, ['name', 'email']) ? 'required' : '' }}>
                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
        <button class="btn btn-primary">Lưu</button>
        <a class="btn btn-outline-secondary" href="{{ route('users.show', $user) }}">Hủy</a>
    </form>
</div>
@endsection
