<?php

namespace App\Http\Requests;

use App\Rules\Phone;
use App\Rules\PhoneMustBeUnique;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SignUpFormRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "email" => "email|nullable",
            "phone" => [
                "nullable",
                Rule::prohibitedIf(request()->filled('email')),
                new Phone
            ],
            "description" => "required|in:signup_with_email,signup_with_phone",
            "password" => ["required", Password::min(8)->mixedCase()->numbers()]
        ];
    }
}
