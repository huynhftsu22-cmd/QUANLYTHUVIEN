<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * UC02 – Đăng ký tài khoản.
     * user_code, role, status do hệ thống gán (BR-11); người dùng không được nhập.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            return User::create([
                'user_code' => User::nextCode('user'),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'password' => $data['password'], // cast 'hashed' của User tự băm bcrypt
                'role' => 'user',
                'status' => 'active',
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('books.index')->with('success', 'Đăng ký tài khoản thành công.');
    }

    /**
     * UC03 – Đăng nhập. Chặn tài khoản locked / deleted (BR-09, BR-10).
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.'])
                ->onlyInput('email');
        }

        if (! $user->isActive()) {
            return back()
                ->withErrors(['email' => 'Tài khoản đã bị khóa hoặc vô hiệu hóa.'])
                ->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(
            $user->isAdmin() ? route('statistics.index') : route('books.index')
        );
    }

    /**
     * UC03 – Đăng xuất.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('books.index')->with('success', 'Đã đăng xuất.');
    }
}
