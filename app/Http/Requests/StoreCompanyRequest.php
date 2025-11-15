<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
//    public function rules(): array
//    {
//        $nameRules = ['string', 'max:255', Rule::unique('companies', 'name')->ignore($this->company)];
//        if ($this->isMethod('post')) {
//            array_unshift($nameRules, 'required');
//        } else {
//            array_unshift($nameRules, 'sometimes');
//        }
//
//        return [
//            'name' => $nameRules,
//            'email' => 'nullable|email|max:255',
//            'phone' => 'nullable|string|max:20',
//            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
//            'description' => 'nullable|string|max:1000',
//            'address' => 'nullable|string|max:500',
//            'manager_id' => 'sometimes|exists:users,id',
//        ];
//    }

    public function rules(): array
    {
        return [
            'name' => 'required', 'string', 'max:255', 'unique:companies,name',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'manager_id' => 'sometimes|exists:users,id',
            // update in future
//            'manager_id' => 'required|exists:users,id',
        ];
    }
}
