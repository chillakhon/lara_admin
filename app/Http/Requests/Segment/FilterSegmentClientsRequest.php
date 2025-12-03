<?php

namespace App\Http\Requests\Segment;

use Illuminate\Foundation\Http\FormRequest;

class FilterSegmentClientsRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'period_from' => 'nullable|date',
            'period_to' => 'nullable|date|after_or_equal:period_from',
            'min_total_amount' => 'nullable|numeric|min:0',
            'max_total_amount' => 'nullable|numeric|min:0|gte:min_total_amount',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|string|in:created_at,total_amount,average_check,orders_count',
            'sort_direction' => 'nullable|string|in:asc,desc',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Поисковый запрос не должен превышать 255 символов',
            'period_from.date' => 'Дата начала периода должна быть корректной датой',
            'period_to.date' => 'Дата окончания периода должна быть корректной датой',
            'period_to.after_or_equal' => 'Дата окончания должна быть больше или равна дате начала',
            'min_total_amount.numeric' => 'Минимальная сумма должна быть числом',
            'min_total_amount.min' => 'Минимальная сумма не может быть отрицательной',
            'max_total_amount.numeric' => 'Максимальная сумма должна быть числом',
            'max_total_amount.gte' => 'Максимальная сумма должна быть больше или равна минимальной',
            'per_page.integer' => 'Количество элементов на странице должно быть целым числом',
            'per_page.min' => 'Минимальное количество элементов на странице: 1',
            'per_page.max' => 'Максимальное количество элементов на странице: 100',
            'sort_by.in' => 'Недопустимое поле для сортировки',
            'sort_direction.in' => 'Направление сортировки должно быть asc или desc',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'search' => 'поиск',
            'period_from' => 'начало периода',
            'period_to' => 'окончание периода',
            'min_total_amount' => 'минимальная сумма',
            'max_total_amount' => 'максимальная сумма',
            'per_page' => 'элементов на странице',
            'sort_by' => 'поле сортировки',
            'sort_direction' => 'направление сортировки',
        ];
    }
}
