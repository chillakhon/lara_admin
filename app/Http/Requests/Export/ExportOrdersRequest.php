<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

class ExportOrdersRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'ids' => 'nullable|array|max:1000',
            'ids.*' => 'integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ids.array' => 'Поле ids должно быть массивом',
            'ids.max' => 'Максимальное количество ID для экспорта: 1000',
            'ids.*.integer' => 'Каждый ID должен быть целым числом',
            'ids.*.min' => 'ID должен быть больше 0',
        ];
    }
}
