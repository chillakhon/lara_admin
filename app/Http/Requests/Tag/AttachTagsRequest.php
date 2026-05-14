<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class AttachTagsRequest extends FormRequest
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
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'required|integer|exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'tag_ids.required' => 'Необходимо указать хотя бы один тег',
            'tag_ids.array' => 'Теги должны быть массивом',
            'tag_ids.min' => 'Необходимо указать хотя бы один тег',
            'tag_ids.*.exists' => 'Один или несколько тегов не существуют',
        ];
    }

}
