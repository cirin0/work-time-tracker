<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => $this->role ?? 'employee',
        ]);
    }
}
