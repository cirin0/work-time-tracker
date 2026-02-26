<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => 'The avatar must be an image file.',
            'avatar.mimes' => 'The avatar must be a file of type: jpeg, png, jpg, svg.',
            'avatar.max' => 'The avatar size must not exceed 2MB.',
        ];
    }
}
