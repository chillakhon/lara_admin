<?php

namespace App\Http\Requests\Segment;

use Illuminate\Foundation\Http\FormRequest;

class AttachClientsRequest extends FormRequest
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
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_ids.required' => 'Необходимо указать ID клиентов',
            'client_ids.array' => 'ID клиентов должны быть переданы в виде массива',
            'client_ids.min' => 'Необходимо указать хотя бы одного клиента',
            'client_ids.*.required' => 'ID клиента обязателен',
            'client_ids.*.integer' => 'ID клиента должен быть целым числом',
            'client_ids.*.exists' => 'Клиент с указанным ID не существует',
        ];
    }
}
