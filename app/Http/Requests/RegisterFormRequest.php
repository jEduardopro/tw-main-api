<?php

namespace App\Http\Requests;

use App\Rules\Phone;
use App\Rules\PhoneMustBeUnique;
use Illuminate\Foundation\Http\FormRequest;

class RegisterFormRequest extends FormRequest
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
            "name" => "required|string|max:255",
            "email" => "email|unique:users,email|nullable",
            "phone" => ["nullable", new Phone, new PhoneMustBeUnique],
            "date_birth" => "required|date_format:Y-m-d|before_or_equal:". now()->subYears(13)
        ];
    }
}
