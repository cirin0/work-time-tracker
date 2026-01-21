<?php

namespace App\Http\Requests;

use App\Enums\LeaveRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(LeaveRequestType::class)],
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'The leave type is required.',
            'type.enum' => 'The leave type must be one of: sick, vacation, personal or unpaid.',
            'start_date.required' => 'The start date is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.after_or_equal' => 'The start date must be today or a future date.',
            'end_date.required' => 'The end date is required.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'reason.required' => 'The reason is required.',
            'reason.string' => 'The reason must be a text string.',
            'reason.max' => 'The reason must not exceed 1000 characters.',
        ];
    }
}
