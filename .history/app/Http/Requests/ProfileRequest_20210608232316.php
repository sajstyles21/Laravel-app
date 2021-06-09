<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Auth;
use Illuminate\Validation\Rule;

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
        $user = Auth::user();
        return [
            'name' => ['required'],
            'user_name' => ['required','min:4','max:20'],
            'avatar' => ['required','dimensions:max_width=256,max_height=256'],
            'email' => ['required','unique:users,email,'.$user->id],
            'user_role' => ['required', Rule::in(User::$roleTypes)],
        ];
    }

    /**
     * Get the error messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'avatar.dimensions'=>'Image dimensions should be 256 X 256'
            'avatar.dimensions'=>'Image dimensions should be 256 X 256'
        ];
    }
}
