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
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID is required.',
            'user_id.integer' => 'User ID must be an integer.',
            'user_id.exists' => 'User not found.',
            'code.required' => 'Verification code is required.',
            'code.string' => 'Verification code must be a string.',
            'code.size' => 'Verification code must be 6 digits.',
        ];
    }
}
