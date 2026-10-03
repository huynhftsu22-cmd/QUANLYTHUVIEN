@extends('layouts.app')

@section('title', 'Đăng ký')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card p-4">
            <h1 class="h4 mb-4">Đăng ký độc giả</h1>

            <form method="POST" action="{{ route('register.store') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="name">Họ và tên</label>
                    <input
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        required
                        autofocus
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input
                        id="email"
                        class="form-control @error('email') is-invalid @enderror"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input
                        id="phone"
                        class="form-control @error('phone') is-invalid @enderror"
                        type="tel"
                        name="phone"
                        value="{{ old('phone') }}"
                        autocomplete="tel"
                    >
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input
                        id="password"
                        class="form-control @error('password') is-invalid @enderror"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Nhập lại mật khẩu</label>
                    <input
                        id="password_confirmation"
                        class="form-control"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button class="btn btn-primary" type="submit">Tạo tài khoản</button>
                <a class="btn btn-link" href="{{ route('login') }}">Đã có tài khoản</a>
            </form>
        </div>
    </div>
</div>
@endsection
