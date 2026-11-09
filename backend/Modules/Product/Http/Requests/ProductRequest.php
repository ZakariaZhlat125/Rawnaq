<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name_ar' => ['required', 'string', 'max:190'],
            'name_en' => ['required', 'string', 'max:190'],
            'category_ids' => ['required'],
            'brand_id' => ['required'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            // 'stock' => ['numeric', 'min:1'],
            // 'price' => ['numeric', 'min:1']
        ];
    }

    public function messages()
    {
        return [
            'stock.min' => 'Need to add 1 stock minimun.',
            'price.min' => 'Need to add 1 price minimun.',
        ];
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
}
