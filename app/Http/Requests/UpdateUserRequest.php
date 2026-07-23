<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role'     => ['required', 'in:admin,editor,viewer'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => '名前は必須です。',
            'email.required'     => 'メールアドレスは必須です。',
            'email.unique'       => 'このメールアドレスは既に使用されています。',
            'password.confirmed' => 'パスワードが一致しません。',
            'role.required'      => 'ロールは必須です。',
        ];
    }
}
