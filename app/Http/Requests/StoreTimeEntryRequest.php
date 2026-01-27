<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_comment' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'start_comment.string' => 'The comment must be a text string.',
            'start_comment.max' => 'The comment must not exceed 255 characters.',
        ];
    }
}
