<?php

namespace Modules\Wallet\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [];

        // Store validation (POST method)
        if ($this->isMethod('post')) {
            $rules = [
                'amount' => 'required|numeric|min:1',
                'status' => 'required|boolean', 
            ];
        }

        if ($this->isMethod('post') && $this->route()->getName() === 'wallet-top-up') {
            $rules = [
                'amount' => 'required|numeric|min:1',
                'transaction_type' => 'required',
                'transaction_id' => 'required|string',
            ];
        }

        if ($this->isMethod('get') && $this->route()->getName() === 'wallet-history') {
            $rules = [
                'per_page' => 'nullable|integer|min:1|max:100', 
                'page' => 'nullable|integer|min:1', 
                'orderby' => 'nullable|in:asc,desc', 
            ];
        }
        return $rules;
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'all_message' => $validator->errors(),
        ];

        // If the request expects JSON or is an API request
        if (request()->wantsJson() || request()->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        // For web requests, redirect back with errors and old input
        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
