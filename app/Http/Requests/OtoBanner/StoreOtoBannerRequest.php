<?php

namespace App\Http\Requests\OtoBanner;


use App\Enums\Oto\OtoBannerDeviceType;
use App\Enums\Oto\OtoBannerInputFieldType;
use App\Enums\Oto\OtoBannerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOtoBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(OtoBannerStatus::class)],
            'device_type' => ['required', Rule::enum(OtoBannerDeviceType::class)],

            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:1000'],

            'button_enabled' => ['boolean'],
            'button_text' => ['nullable', 'string', 'max:100'],

            'input_field_enabled' => ['boolean'],
            'input_field_type' => ['required', Rule::enum(OtoBannerInputFieldType::class)],
            'input_field_label' => ['nullable', 'string', 'max:255'],
            'input_field_placeholder' => ['nullable', 'string', 'max:255'],
            'input_field_required' => ['boolean'],

            'display_delay_seconds' => ['integer', 'min:0', 'max:3600'],

            'privacy_text' => ['nullable', 'string', 'max:2000'],

            'segment_ids' => ['nullable', 'array'],
            'segment_ids.*' => ['integer', 'exists:segments,id'],
            'promo_code_id' => 'nullable|integer|exists:promo_codes,id',
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название баннера обязательно',
            'device_type.required' => 'Тип устройства обязателен',
            'image.required' => 'Изображение баннера обязательно',
            'image.image' => 'Файл должен быть изображением',
            'image.max' => 'Максимальный размер изображения 5MB',
            'segment_ids.*.exists' => 'Один или несколько сегментов не существуют',
        ];
    }
}
