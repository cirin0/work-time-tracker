<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manager_comment' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'manager_comment.required' => 'Manager comments are required when rejecting a leave request.',
            'manager_comment.string' => 'Manager comments must be a text string.',
            'manager_comment.max' => 'Manager comments must not exceed 1000 characters.',
        ];
    }
}
