<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Nhận các trường theo bảng users: name, email, phone, password (+ xác nhận).
     * user_code / role / status không nằm trong rules nên không thể bị gửi lên từ form.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // SĐT di động Việt Nam: 0 hoặc +84, đầu số 3/5/7/8/9, tổng 10 số.
            'phone' => ['nullable', 'string', 'regex:/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/'],
            // Mật khẩu: tối thiểu 8 ký tự, có chữ hoa, chữ thường và chữ số.
            'password' => [
                'required', 'string', 'confirmed', 'min:8',
                'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/',
            ],
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
            'phone.regex' => 'Số điện thoại không hợp lệ (ví dụ: 0901234567 hoặc +84901234567).',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.regex' => 'Mật khẩu phải có đủ chữ hoa, chữ thường và chữ số.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Họ và tên',
            'email' => 'Email',
            'phone' => 'Số điện thoại',
            'password' => 'Mật khẩu',
        ];
    }
}
