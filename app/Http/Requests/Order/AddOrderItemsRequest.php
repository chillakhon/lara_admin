<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.color_id' => 'nullable|integer|exists:colors,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'required|numeric|min:0|max:9999999',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Необходимо добавить хотя бы один товар',
            'items.*.product_id.required' => 'Не указан ID товара',
            'items.*.product_id.exists' => 'Товар не найден',
            'items.*.quantity.min' => 'Количество должно быть не менее 1',
            'items.*.quantity.max' => 'Количество не может превышать 999',
            'items.*.price.min' => 'Цена не может быть отрицательной',
        ];
    }
}
