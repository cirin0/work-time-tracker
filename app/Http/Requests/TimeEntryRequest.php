<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TimeEntryRequest',
    description: 'Request for creating or updating a time entry',
    properties: [
        new OA\Property(property: 'comment', type: 'string', maxLength: 255, example: 'Worked on project X', nullable: true),
    ],
    type: 'object',
)]
class TimeEntryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => 'nullable|string|max:255',
        ];
    }
}
