<?php

namespace Modules\Category\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        switch (strtolower($this->getMethod())) {
            case 'post':
                return [
                    'slug' => 'string',
                    'name' => 'required|string',
                    'price_from' => 'required|numeric|min:0',
                    'durMin' => 'required|integer|min:0',
                    'durMax' => 'required|integer|min:0',
                    'feature_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                ];
                break;
            case 'put':
            case 'patch':
                return [
                    'slug' => 'string',
                    'name' => 'required|string',
                    'durMin' => 'required|integer|min:0',
                    'durMax' => 'required|integer|min:0',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                ];
                break;
        }

        return [];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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

        if (request()->wantsJson() || request()->is('api/*')) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
