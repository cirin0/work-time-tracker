<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePinCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_pin_code' => 'required|string|size:4',
            'new_pin_code' => 'required|string|size:4|regex:/^[0-9]+$/|different:current_pin_code',
        ];
    }

    public function messages(): array
    {
        return [
            'current_pin_code.required' => 'Current pin code is required.',
            'new_pin_code.required' => 'New pin code is required.',
            'new_pin_code.size' => 'New pin code must be exactly 4 digits.',
            'new_pin_code.regex' => 'New pin code must contain only digits.',
            'new_pin_code.different' => 'New pin code must be different from the current one.',
        ];
    }
}
