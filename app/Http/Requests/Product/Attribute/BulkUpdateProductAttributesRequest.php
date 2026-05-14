<?php

namespace App\Http\Requests\Product\Attribute;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateProductAttributesRequest extends FormRequest
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
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|exists:products,id',
            'attributes' => 'required|array|min:1',
            'attributes.absorbency_level' => 'nullable|integer|min:0|max:6',
            'attributes.fit_type' => 'nullable|in:low,tall',
            'attributes.weight' => 'nullable|numeric|min:0',
            'attributes.length' => 'nullable|numeric|min:0',
            'attributes.width' => 'nullable|numeric|min:0',
            'attributes.height' => 'nullable|numeric|min:0',
            'attributes.is_new' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_ids.required' => 'Необходимо выбрать хотя бы один товар.',
            'product_ids.array' => 'Неверный формат списка товаров.',
            'product_ids.min' => 'Необходимо выбрать хотя бы один товар.',
            'product_ids.*.exists' => 'Один или несколько товаров не найдены.',

            'attributes.required' => 'Необходимо указать хотя бы один атрибут для обновления.',
            'attributes.array' => 'Неверный формат атрибутов.',
            'attributes.min' => 'Необходимо указать хотя бы один атрибут для обновления.',

            'attributes.absorbency_level.integer' => 'Уровень впитываемости должен быть числом.',
            'attributes.absorbency_level.min' => 'Уровень впитываемости не может быть меньше 0.',
            'attributes.absorbency_level.max' => 'Уровень впитываемости не может быть больше 6.',

            'attributes.fit_type.in' => 'Тип посадки должен быть "low" (низкая) или "tall" (высокая).',

            'attributes.weight.numeric' => 'Вес должен быть числом.',
            'attributes.weight.min' => 'Вес не может быть отрицательным.',

            'attributes.length.numeric' => 'Длина должна быть числом.',
            'attributes.length.min' => 'Длина не может быть отрицательной.',

            'attributes.width.numeric' => 'Ширина должна быть числом.',
            'attributes.width.min' => 'Ширина не может быть отрицательной.',

            'attributes.height.numeric' => 'Высота должна быть числом.',
            'attributes.height.min' => 'Высота не может быть отрицательной.',

            'attributes.is_new.boolean' => 'Новинка должна быть true или false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_ids' => 'список товаров',
            'attributes' => 'атрибуты',
            'attributes.absorbency_level' => 'уровень впитываемости',
            'attributes.fit_type' => 'тип посадки',
            'attributes.weight' => 'вес',
            'attributes.length' => 'длина',
            'attributes.width' => 'ширина',
            'attributes.height' => 'высота',
            'attributes.is_new' => 'новинка',
        ];
    }
}
