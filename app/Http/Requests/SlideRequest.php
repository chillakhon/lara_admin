<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlideRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:10240', // Desktop изображение: до 10MB
            'image_mobile' => 'nullable|image|max:10240', // Mobile изображение: до 10MB
            'subtitle' => 'nullable|string|max:255',
            'text' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'Файл изображения (desktop) не должен превышать 10 мегабайт.',
            'image.image' => 'Загруженный файл (desktop) должен быть изображением.',
            'image_mobile.max' => 'Файл изображения (mobile) не должен превышать 10 мегабайт.',
            'image_mobile.image' => 'Загруженный файл (mobile) должен быть изображением.',
        ];
    }
}
