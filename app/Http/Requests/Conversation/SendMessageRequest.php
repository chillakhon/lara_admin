<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Публичный endpoint, авторизация не требуется
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => 'nullable|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp3,wav,ogg,m4a|max:10240', // 10MB
        ];
    }

    /**
     * Дополнительная валидация: должен быть текст ИЛИ файлы
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $content = $this->input('content');
            $hasFiles = $this->hasFile('attachments');

            // Если нет текста И нет файлов - ошибка
            if (empty($content) && !$hasFiles) {
                $validator->errors()->add(
                    'content',
                    'Необходимо указать текст сообщения или прикрепить файл'
                );
            }
        });
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'content.string' => 'Текст сообщения должен быть строкой',
            'content.max' => 'Текст сообщения не должен превышать 5000 символов',

            'attachments.array' => 'Файлы должны быть массивом',
            'attachments.max' => 'Максимум 5 файлов за раз',

            'attachments.*.file' => 'Один из элементов не является файлом',
            'attachments.*.mimes' => 'Разрешены только файлы: JPG, PNG, MP3, WAV, OGG, M4A',
            'attachments.*.max' => 'Максимальный размер файла: 10 МБ',
        ];
    }

    /**
     * Кастомные названия атрибутов для ошибок
     */
    public function attributes(): array
    {
        return [
            'content' => 'текст сообщения',
            'attachments' => 'файлы',
            'attachments.*' => 'файл',
        ];
    }

    /**
     * Обработка неудачной валидации (для API)
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
