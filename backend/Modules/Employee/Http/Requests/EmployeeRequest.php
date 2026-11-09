<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
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
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'required|string|unique:users,email',
                    'mobile' => 'required|string',
                    'status' => ['sometimes', 'boolean'],
                    'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('status', 1)->whereNull('deleted_at')],
                    'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')->where('status', 1)],
                    'password' => 'required|min:8',
                    'confirm_password' => 'required|same:password',
                ];
                break;
            case 'put':
            case 'patch':
                return [
                    'first_name' => 'required|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => ['required', 'string', Rule::unique('users', 'email')->ignore($this->id)->whereNull('deleted_at')],
                    'mobile' => 'required|string',
                    'status' => ['sometimes', 'boolean'],
                    'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('status', 1)->whereNull('deleted_at')],
                    'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')->where('status', 1)],
                ];
                break;
            default:
                return [];
                break;
        }
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
