<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindAccountFormRequest extends FormRequest
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
            "user_identifier" => "required",
            "task_id" => "required|in:". implode(",", config('task-ids.tasks'))
        ];
    }
}
