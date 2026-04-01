<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manager_comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'manager_comment.string' => 'Manager comments must be a text string.',
            'manager_comment.max' => 'Manager comments must not exceed 1000 characters.',
        ];
    }
}
