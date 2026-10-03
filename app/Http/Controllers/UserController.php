<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->when($request->filled('q'), function ($q) use ($request) {
            $term = '%'.$request->q.'%';
            $q->where(fn ($s) => $s->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('user_code', 'like', $term));
        })->when(in_array($request->status, ['active', 'locked', 'deleted'], true), fn ($q) => $q->where('status', $request->status));

        return view('users.index', ['users' => $query->latest()->paginate(15)->withQueryString()]);
    }

    public function show(User $user): View
    {
        return view('users.show', ['user' => $user, 'records' => $user->borrowRecords()->with('book')->latest()->limit(10)->get()]);
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // Chuẩn hóa +84... -> 0... trước khi kiểm tra để không lách được ràng buộc trùng SĐT.
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);
        }

        $user->update($request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'phone' => ['nullable', 'string', 'regex:/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/', Rule::unique('users', 'phone')->ignore($user)],
            'address' => ['nullable', 'string', Rule::in(config('hcm_wards'))],
        ], [
            'phone.regex' => 'Số điện thoại không hợp lệ (ví dụ: 0901234567 hoặc +84901234567).',
            'phone.unique' => 'Số điện thoại đã được sử dụng bởi tài khoản khác.',
            'address.in' => 'Vui lòng chọn phường trong danh sách của Thành phố Hồ Chí Minh.',
        ], [
            'name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Số điện thoại', 'address' => 'Địa chỉ',
        ]));

        return redirect()->route('users.show', $user)->with('success', 'Đã cập nhật người dùng.');
    }

    public function lock(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Không thể tự khóa tài khoản đang đăng nhập.');
        }
        $user->update(['status' => 'locked']);

        return back()->with('success', 'Đã khóa tài khoản.');
    }

    public function unlock(User $user): RedirectResponse
    {
        if ($user->status === 'deleted') {
            return back()->with('error', 'Tài khoản đã xóa không thể mở khóa.');
        }
        $user->update(['status' => 'active']);

        return back()->with('success', 'Đã mở khóa tài khoản.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Không thể tự vô hiệu hóa tài khoản đang đăng nhập.');
        }
        $user->update(['status' => 'deleted']);

        return redirect()->route('users.index')->with('success', 'Đã vô hiệu hóa tài khoản, lịch sử vẫn được bảo toàn.');
    }
}
