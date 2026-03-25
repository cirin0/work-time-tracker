<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckAppUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version_code' => ['nullable', 'integer', 'min:1'],
            'platform' => ['nullable', 'string', 'max:20'],
            'channel' => ['nullable', 'string', 'max:30'],
        ];
    }
}

