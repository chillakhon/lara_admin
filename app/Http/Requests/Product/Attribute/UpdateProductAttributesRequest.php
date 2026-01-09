<?php

namespace App\Http\Requests\Product\Attribute;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductAttributesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // или добавь логику проверки прав
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'absorbency_level' => 'nullable|integer|min:0|max:6',
            'fit_type' => 'nullable|in:low,tall',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'absorbency_level.integer' => 'Уровень впитываемости должен быть числом.',
            'absorbency_level.min' => 'Уровень впитываемости не может быть меньше 0.',
            'absorbency_level.max' => 'Уровень впитываемости не может быть больше 6.',

            'fit_type.in' => 'Тип посадки должен быть "low" (низкая) или "tall" (высокая).',

            'weight.numeric' => 'Вес должен быть числом.',
            'weight.min' => 'Вес не может быть отрицательным.',

            'length.numeric' => 'Длина должна быть числом.',
            'length.min' => 'Длина не может быть отрицательной.',

            'width.numeric' => 'Ширина должна быть числом.',
            'width.min' => 'Ширина не может быть отрицательной.',

            'height.numeric' => 'Высота должна быть числом.',
            'height.min' => 'Высота не может быть отрицательной.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'absorbency_level' => 'уровень впитываемости',
            'fit_type' => 'тип посадки',
            'weight' => 'вес',
            'length' => 'длина',
            'width' => 'ширина',
            'height' => 'высота',
        ];
    }
}
