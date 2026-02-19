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
            'stop_comment' => 'nullable|string|max:255',
            'pin_code' => 'required|string|size:4',
        ];
    }

    public function messages(): array
    {
        return [
            'stop_comment.string' => 'The comment must be a text string.',
            'stop_comment.max' => 'The comment must not exceed 255 characters.',
            'pin_code.required' => 'Pin code is required to stop the time entry.',
            'pin_code.size' => 'Pin code must be exactly 4 digits.',
        ];
    }
}
