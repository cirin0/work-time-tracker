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
        $user = auth('api')->user();
        $isAdmin = $user && $user->isAdmin();
        $isOffice = $user && $user->work_mode === WorkMode::OFFICE;

        $requireGpsAndQr = $isOffice && !$isAdmin;
        $gpsRule = $requireGpsAndQr ? 'required|numeric|between:-90,90' : 'nullable|numeric|between:-90,90';
        $lonRule = $requireGpsAndQr ? 'required|numeric|between:-180,180' : 'nullable|numeric|between:-180,180';
        $qrRule = $requireGpsAndQr ? 'required|string' : 'nullable|string';

        return [
            'start_comment' => 'nullable|string|max:255',
            'latitude' => $gpsRule,
            'longitude' => $lonRule,
            'qr_code' => $qrRule,
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
