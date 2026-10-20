@extends('layouts.app')

@section('title', 'Đăng nhập')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card p-4">
            <h1 class="h4 mb-4">Đăng nhập</h1>

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

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
                        autofocus
                    >
                    @error('email')
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
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
            </form>

            <p class="mt-3 mb-0 text-center">
                Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký</a>
            </p>
        </div>
    </div>
</div>
@endsection
