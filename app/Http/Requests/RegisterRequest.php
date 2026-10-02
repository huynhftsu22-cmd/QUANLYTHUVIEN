<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Phone is optional in the project scaffold. When provided, keep a
            // Vietnamese mobile-number format instead of accepting arbitrary text.
            'phone' => ['nullable', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Họ và tên không được để trống.',
            'name.max' => 'Họ và tên không được dài quá 255 ký tự.',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email phải là địa chỉ email hợp lệ.',
            'email.max' => 'Email không được dài quá 255 ký tự.',
            'email.unique' => 'Email đã tồn tại trong hệ thống.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'address.max' => 'Địa chỉ không được dài quá 255 ký tự.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Họ và tên',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'address' => 'Địa chỉ',
            'password' => 'Mật khẩu',
        ];
    }
}
