<?php

namespace App\Http\Requests\Segment;

use Illuminate\Foundation\Http\FormRequest;

class AttachPromoCodesRequest extends FormRequest
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
            'promo_code_ids' => 'required|array|min:1',
            'promo_code_ids.*' => 'required|integer|exists:promo_codes,id',
            'auto_apply' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'promo_code_ids.required' => 'Необходимо указать ID промокодов',
            'promo_code_ids.array' => 'ID промокодов должны быть переданы в виде массива',
            'promo_code_ids.min' => 'Необходимо указать хотя бы один промокод',
            'promo_code_ids.*.required' => 'ID промокода обязателен',
            'promo_code_ids.*.integer' => 'ID промокода должен быть целым числом',
            'promo_code_ids.*.exists' => 'Промокод с указанным ID не существует',
            'auto_apply.boolean' => 'Параметр auto_apply должен быть булевым значением',
        ];
    }
}
