<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:companies,name',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'manager_id' => 'nullable|integer|exists:users,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:1',
            'lateness_grace_minutes' => 'nullable|integer|min:0|max:60',
            'overtime_threshold_hours' => 'nullable|numeric|min:0|max:24',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Company name is required.',
            'name.unique' => 'A company with this name already exists.',
            'email.email' => 'Please provide a valid email address.',
            'manager_id.exists' => 'The specified manager does not exist.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'radius_meters.min' => 'Radius must be at least 1 meter.',
        ];
    }
}
