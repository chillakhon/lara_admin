<?php

namespace App\DTOs\OtoBanner;

use Illuminate\Http\Request;

class OtoBannerSubmissionDTO
{
    public function __construct(
        public readonly int $otoBannerId,
        public readonly ?int $clientId,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $message,
        public readonly string $inputFieldValue,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly ?string $sessionId = null,
    ) {}

    public static function fromRequest(Request $request, int $bannerId): self
    {
        return new self(
            otoBannerId: $bannerId,
            clientId: auth('sanctum')->id(),
            name: $request->input('name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            message: $request->input('message'),
            inputFieldValue: $request->input('input_field_value'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            sessionId: $request->input('session_id') ?? uniqid('session_', true),
        );
    }

    public function toContactRequestArray(string $bannerName): array
    {
        return [
            'oto_banner_id' => $this->otoBannerId,
            'client_id' => $this->clientId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            'source' => 'oto_banner_' . $this->otoBannerId,
            'status' => 'new',
            'ip' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'meta' => [
                'banner_id' => $this->otoBannerId,
                'banner_name' => $bannerName,
                'form_type' => 'oto',
                'input_fields' => [
                    'value' => $this->inputFieldValue,
                ],
                'session_id' => $this->sessionId,
            ],
        ];
    }
}
