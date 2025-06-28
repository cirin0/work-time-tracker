<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserRequest',
    description: 'User registration request',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
        new OA\Property(property: 'role', type: 'string', enum: ['user', 'admin', 'manager'], example: 'user')
    ],
    type: 'object'
)]
class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|',
            'role' => 'sometimes|in:employee,admin,manager',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => $this->role ?? 'employee', // Default role if not provided
        ]);
    }
}
