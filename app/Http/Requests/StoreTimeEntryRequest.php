<?php

namespace App\Http\Requests;

use App\Enums\WorkMode;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $isOffice = $user && $user->work_mode === WorkMode::office;

        return [
            'start_comment' => 'nullable|string|max:255',
            'latitude' => [
                $isOffice ? 'required' : 'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                $isOffice ? 'required' : 'nullable',
                'numeric',
                'between:-180,180',
            ],
            'qr_code' => [
                $isOffice ? 'required' : 'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start_comment.string' => 'The comment must be a text string.',
            'start_comment.max' => 'The comment must not exceed 255 characters.',
            'latitude.required' => 'Latitude is required for office work mode.',
            'longitude.required' => 'Longitude is required for office work mode.',
            'qr_code.required' => 'QR code scanning is required for office work mode.',
        ];
    }
}
