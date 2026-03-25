<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['nullable', 'string', 'max:20'],
            'channel' => ['nullable', 'string', 'max:30'],
            'version_code' => ['required', 'integer', 'min:1'],
            'version_name' => ['required', 'string', 'max:50'],
            'apk' => [
                'required',
                'file',
                'mimes:apk,zip',
                'mimetypes:application/vnd.android.package-archive,application/octet-stream,application/zip,application/x-zip-compressed',
                'max:262144',
            ],
            'changelog' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
