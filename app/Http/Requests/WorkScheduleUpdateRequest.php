<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkScheduleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'break_duration' => 'sometimes|integer|min:0',
            'monday' => 'sometimes|boolean',
            'tuesday' => 'sometimes|boolean',
            'wednesday' => 'sometimes|boolean',
            'thursday' => 'sometimes|boolean',
            'friday' => 'sometimes|boolean',
            'saturday' => 'sometimes|boolean',
            'sunday' => 'sometimes|boolean',
            'company_id' => 'sometimes|exists:companies,id',
            'is_default' => 'sometimes|boolean',
        ];
    }
}
