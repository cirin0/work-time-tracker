<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetupPinCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin_code' => 'required|string|size:4|regex:/^[0-9]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'pin_code.required' => 'Pin code is required.',
            'pin_code.size' => 'Pin code must be exactly 4 digits.',
            'pin_code.regex' => 'Pin code must contain only digits.',
        ];
    }
}
