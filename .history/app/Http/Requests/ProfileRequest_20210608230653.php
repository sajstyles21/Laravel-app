<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required','email','exists:users,email'],
            'user_name' => ['required','min:4','max:20'],
            'avatar' => ['required','dimensions:max_width=256,max_height=256'],
            'email' => ['required','unique:users'],
            'user_role' => ['required', Rule::in(User::$roleTypes)],
            
        ];
    }
}
