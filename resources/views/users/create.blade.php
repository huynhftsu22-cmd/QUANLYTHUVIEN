@extends('layouts.app')
@section('title', 'Thêm tài khoản')
@section('content')
<div class="card p-4">
    <h1 class="h3">Thêm tài khoản</h1>
    <p class="text-muted">Mã người dùng được hệ thống tự sinh theo vai trò: DG cho độc giả, NV cho admin.</p>
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        <div class="row">
            @foreach(['name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Số điện thoại', 'address' => 'Địa chỉ'] as $field => $label)
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ $label }} @if(in_array($field, ['name', 'email']))<span class="text-danger">*</span>@endif</label>
                    <input class="form-control @error($field) is-invalid @enderror" type="{{ $field === 'email' ? 'email' : 'text' }}" name="{{ $field }}" value="{{ old($field) }}" {{ in_array($field, ['name', 'email']) ? 'required' : '' }}>
                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            @endforeach
            <div class="col-md-6 mb-3">
                <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                <select class="form-select @error('role') is-invalid @enderror" name="role">
                    <option value="user" @selected(old('role', 'user') === 'user')>Độc giả (DG)</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin (NV)</option>
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6"></div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                <input class="form-control @error('password') is-invalid @enderror" type="password" name="password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Nhập lại mật khẩu <span class="text-danger">*</span></label>
                <input class="form-control" type="password" name="password_confirmation" required>
            </div>
        </div>
        <button class="btn btn-primary">Tạo tài khoản</button>
        <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Hủy</a>
    </form>
</div>
@endsection
