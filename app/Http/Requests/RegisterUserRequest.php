<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Periksa identitas penyewa, keunikan email, dan kata sandi sebelum akun dapat didaftarkan.
     */
    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'regex:/\A[0-9]{16}\z/'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/\A[^\x00]{1,72}\z/'],
        ];
    }
}
