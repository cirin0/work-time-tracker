<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.exists' => 'User not found.',
            'code.required' => 'Verification code is required.',
            'code.string' => 'Verification code must be a string.',
            'code.size' => 'Verification code must be 6 digits.',
        ];
    }
}
