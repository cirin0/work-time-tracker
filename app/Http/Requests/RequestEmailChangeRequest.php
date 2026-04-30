<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class RequestEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currentEmail = $this->user()?->email;

        return [
            'new_email' => [
                'required',
                'email',
                'unique:users,email',
                function (string $attribute, mixed $value, Closure $fail) use ($currentEmail): void {
                    if (is_string($currentEmail) && $value === $currentEmail) {
                        $fail('New email must be different from your current email.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_email.required' => 'New email is required.',
            'new_email.email' => 'New email must be a valid email address.',
            'new_email.unique' => 'This email is already in use.',
        ];
    }
}
