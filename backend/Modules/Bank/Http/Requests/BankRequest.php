<?php

namespace Modules\Bank\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BankRequest extends FormRequest
{
    // Check if the user is authorized to make this request
    public function authorize(): bool
    {
        return true;
    }

    // Define the validation rules
    public function rules(): array
    {
        // Default rules array
        $rules = [];

        // Store validation (for store method - POST request)
        if ($this->isMethod('post')) {
            if ($this->filled('bank_id')) {
                // Validation for editing a bank
                $rules = [
                    'bank_id' => 'required|exists:banks,id',
                    'bank_name' => 'nullable|string|max:255',
                    'branch_name' => 'nullable|string|max:255',
                    'account_no' => 'nullable|string|max:255',
                    'ifsc_no' => 'nullable|string|max:255',
                    'mobile_no' => 'nullable|string|max:15',
                    'aadhar_no' => 'nullable|string|max:12',
                    'pan_no' => 'nullable|string|max:10',
                    'bank_attachment' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
                    'status' => 'nullable|boolean',
                    'is_default' => 'nullable|boolean',
                ];
            }else{
                $rules = [
                    'bank_name' => 'nullable|string|max:255',
                    'branch_name' => 'nullable|string|max:255',
                    'account_no' => 'nullable|string|max:255',
                    'ifsc_no' => 'nullable|string|max:255',
                    'mobile_no' => 'nullable|string|max:15',
                    'aadhar_no' => 'nullable|string|max:12',
                    'pan_no' => 'nullable|string|max:10',
                    'bank_attachment' => 'nullable|file|mimes:jpeg,png,pdf|max:2048',
                    'status' => 'nullable|boolean',
                    'is_default' => 'nullable|boolean',
                ];
            }
        }

        // Show bank validation (for showBank method - GET request)
        if ($this->isMethod('get') && $this->route()->getName() === 'user-bank-detail') {
            $rules = [
                'user_id' => 'required|integer|exists:users,id',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ];
        }

        // Set default bank validation (for setDefault method - GET request)
        if ($this->isMethod('get') && $this->route()->getName() === 'default-bank') {
            $rules = [
                'id' => 'required|integer|exists:banks,id',
            ];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        if (request()->wantsJson() || request()->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
