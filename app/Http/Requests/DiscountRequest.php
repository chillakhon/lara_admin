<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscountRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed,special_price',
            'value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'priority' => 'nullable|integer|min:0',
            'conditions' => 'nullable|array',
            'discount_type' => 'required|in:specific,category,all',
            'categories' => 'nullable|array|required_if:discount_type,category',
            'categories.*' => 'exists:categories,id',
            'products' => 'nullable|array|required_if:discount_type,specific',
            'products.*' => 'exists:products,id',
            'product_variants' => 'nullable|array',
            'product_variants.*' => 'exists:product_variants,id'
        ];
    }
} 