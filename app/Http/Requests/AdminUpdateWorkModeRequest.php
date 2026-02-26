<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateWorkModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_mode' => 'required|in:remote,office,hybrid',
        ];
    }

    public function messages(): array
    {
        return [
            'work_mode.required' => 'The work mode field is required.',
            'work_mode.in' => 'The work mode must be one of: remote, office, hybrid.',
        ];
    }
}
