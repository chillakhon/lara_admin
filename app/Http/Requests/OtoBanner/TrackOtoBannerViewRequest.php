<?php

namespace App\Http\Requests\OtoBanner;

use Illuminate\Foundation\Http\FormRequest;

class TrackOtoBannerViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'Session ID обязателен',
        ];
    }
}
