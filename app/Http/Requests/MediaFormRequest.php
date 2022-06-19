<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaFormRequest extends FormRequest
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
            "media" => "required|mimes:png,jpg,jpeg,svg|max:10240",
            "media_category" => "required|in:tweet_image"
        ];
    }
}
