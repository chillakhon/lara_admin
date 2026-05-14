<?php

namespace App\Http\Requests\OtoBanner;

use Illuminate\Foundation\Http\FormRequest;

class AttachToSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'segment_ids' => ['required', 'array', 'min:1'],
            'segment_ids.*' => ['integer', 'exists:segments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'segment_ids.required' => 'Выберите хотя бы один сегмент',
            'segment_ids.*.exists' => 'Один или несколько сегментов не существуют',
        ];
    }
}
