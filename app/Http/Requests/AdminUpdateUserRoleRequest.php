<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'required|in:employee,manager,admin',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'The role field is required.',
            'role.in' => 'The role must be one of: employee, manager, admin.',
        ];
    }
}
