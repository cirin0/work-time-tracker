<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StopTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.string' => 'The comment must be a text string.',
            'comment.max' => 'The comment must not exceed 255 characters.',
        ];
    }
}
