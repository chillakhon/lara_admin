<?php

namespace App\Http\Requests\Segment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSegmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизацию проверяем в middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:segments,name'
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'recalculate_frequency' => [
                'required',
                Rule::in(['on_view', 'manual'])
            ],
            'conditions' => 'nullable|array',
            'conditions.period' => [
                'nullable',
                Rule::in(['all_time', 'last_month', 'last_6_months', 'last_year'])
            ],
            'conditions.min_orders_count' => 'nullable|integer|min:0',
            'conditions.max_orders_count' => 'nullable|integer|min:0|gte:conditions.min_orders_count',
            'conditions.min_total_amount' => 'nullable|numeric|min:0',
            'conditions.min_average_check' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название сегмента обязательно для заполнения',
            'name.unique' => 'Сегмент с таким названием уже существует',
            'name.max' => 'Название сегмента не должно превышать 255 символов',
            'description.max' => 'Описание не должно превышать 1000 символов',
            'recalculate_frequency.required' => 'Необходимо указать частоту пересчёта',
            'recalculate_frequency.in' => 'Некорректная частота пересчёта',
            'conditions.period.in' => 'Некорректный период для условий',
            'conditions.min_orders_count.integer' => 'Минимальное количество заказов должно быть целым числом',
            'conditions.min_orders_count.min' => 'Минимальное количество заказов не может быть отрицательным',
            'conditions.max_orders_count.gte' => 'Максимальное количество заказов должно быть больше или равно минимальному',
            'conditions.min_total_amount.numeric' => 'Минимальная сумма должна быть числом',
            'conditions.min_total_amount.min' => 'Минимальная сумма не может быть отрицательной',
            'conditions.min_average_check.numeric' => 'Минимальный средний чек должен быть числом',
            'conditions.min_average_check.min' => 'Минимальный средний чек не может быть отрицательным',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'название',
            'description' => 'описание',
            'is_active' => 'активность',
            'recalculate_frequency' => 'частота пересчёта',
            'conditions.period' => 'период',
            'conditions.min_orders_count' => 'минимальное количество заказов',
            'conditions.max_orders_count' => 'максимальное количество заказов',
            'conditions.min_total_amount' => 'минимальная сумма покупок',
            'conditions.min_average_check' => 'минимальный средний чек',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Проверяем, что если указаны условия, то хотя бы одно заполнено
            $conditions = $this->input('conditions', []);

            if (!empty($conditions)) {
                $hasCondition = !empty($conditions['period'])
                    || !empty($conditions['min_orders_count'])
                    || !empty($conditions['max_orders_count'])
                    || !empty($conditions['min_total_amount'])
                    || !empty($conditions['min_average_check']);

                if (!$hasCondition) {
                    $validator->errors()->add(
                        'conditions',
                        'Необходимо указать хотя бы одно условие для сегмента'
                    );
                }
            }
        });
    }
}
