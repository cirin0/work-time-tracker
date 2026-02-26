<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignManagerToCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manager_id' => 'required|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'manager_id.required' => 'Manager ID is required.',
            'manager_id.integer' => 'Manager ID must be an integer.',
            'manager_id.exists' => 'The specified manager does not exist.',
        ];
    }
}
