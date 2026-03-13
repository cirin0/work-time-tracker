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
        $isAdmin = $user && $user->isAdmin();
        $isOffice = $user && $user->work_mode === WorkMode::OFFICE;

        $requireGpsAndQr = $isOffice && !$isAdmin;

        return [
            'start_comment' => 'nullable|string|max:255',
            'latitude' => [
                $requireGpsAndQr ? 'required' : 'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                $requireGpsAndQr ? 'required' : 'nullable',
                'numeric',
                'between:-180,180',
            ],
            'qr_code' => [
                $requireGpsAndQr ? 'required' : 'nullable',
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
