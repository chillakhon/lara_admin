<?php

namespace App\Http\Requests\Segment;

use Illuminate\Foundation\Http\FormRequest;

class ExportSegmentRequest extends FormRequest
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
            'columns' => 'nullable|array',
            'columns.*' => 'string|in:id,full_name,phone,email,birthday,address,average_check,total_amount,orders_count',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'columns.array' => 'Колонки должны быть переданы в виде массива',
            'columns.*.in' => 'Недопустимая колонка для экспорта',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Если колонки не указаны, устанавливаем значения по умолчанию
        if (!$this->has('columns')) {
            $this->merge([
                'columns' => [
                    'id',
                    'full_name',
                    'phone',
                    'email',
                    'birthday',
                    'address',
                    'average_check',
                    'total_amount',
                    'orders_count'
                ]
            ]);
        }
    }
}
