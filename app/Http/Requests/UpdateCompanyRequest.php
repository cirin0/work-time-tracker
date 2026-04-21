<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = Company::first()?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($companyId)],
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'description' => 'sometimes|nullable|string|max:1000',
            'address' => 'sometimes|nullable|string|max:500',
            'manager_id' => 'sometimes|nullable|integer|exists:users,id',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'radius_meters' => 'sometimes|nullable|integer|min:1',
            'lateness_grace_minutes' => 'sometimes|nullable|integer|min:0|max:60',
            'overtime_threshold_hours' => 'sometimes|nullable|numeric|min:0|max:24',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A company with this name already exists.',
            'email.email' => 'Please provide a valid email address.',
            'manager_id.exists' => 'The specified manager does not exist.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'radius_meters.min' => 'Radius must be at least 1 meter.',
        ];
    }
}
