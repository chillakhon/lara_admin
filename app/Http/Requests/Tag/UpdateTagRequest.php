<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('tags', 'name')->ignore($this->route('tag'))
            ],
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'Название тега обязательно',
            'name.unique' => 'Тег с таким названием уже существует',
            'name.max' => 'Название не должно превышать 50 символов',
            'color.regex' => 'Цвет должен быть в формате HEX (например: #FF5733)',
        ];
    }
}
