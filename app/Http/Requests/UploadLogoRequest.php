<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'logo.image' => 'The logo must be an image file.',
            'logo.mimes' => 'The logo must be a file of type: jpeg, png, jpg, svg.',
            'logo.max' => 'The logo size must not exceed 2MB.',
        ];
    }
}
