<?php

namespace App\DTOs\OtoBanner;

use App\Enums\Oto\OtoBannerDeviceType;
use App\Enums\Oto\OtoBannerInputFieldType;
use App\Enums\Oto\OtoBannerStatus;

use Illuminate\Http\Request;

class CreateOtoBannerDTO
{
    public function __construct(
        public readonly string $name,
        public readonly OtoBannerStatus $status,
        public readonly OtoBannerDeviceType $deviceType,
        public readonly ?string $title,
        public readonly ?string $subtitle,
        public readonly bool $buttonEnabled,
        public readonly ?string $buttonText,
        public readonly bool $inputFieldEnabled,
        public readonly OtoBannerInputFieldType $inputFieldType,
        public readonly ?string $inputFieldLabel,
        public readonly ?string $inputFieldPlaceholder,
        public readonly bool $inputFieldRequired,
        public readonly int $displayDelaySeconds,
        public readonly ?string $privacyText,
        public readonly ?array $segmentIds,
        public readonly mixed $image = null, // Uploaded file
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            status: OtoBannerStatus::from($request->input('status', 'inactive')),
            deviceType: OtoBannerDeviceType::from($request->input('device_type')),
            title: $request->input('title'),
            subtitle: $request->input('subtitle'),
            buttonEnabled: $request->boolean('button_enabled', true),
            buttonText: $request->input('button_text', 'Отправить'),
            inputFieldEnabled: $request->boolean('input_field_enabled', true),
            inputFieldType: OtoBannerInputFieldType::from($request->input('input_field_type', 'email')),
            inputFieldLabel: $request->input('input_field_label'),
            inputFieldPlaceholder: $request->input('input_field_placeholder'),
            inputFieldRequired: $request->boolean('input_field_required', true),
            displayDelaySeconds: $request->integer('display_delay_seconds', 0),
            privacyText: $request->input('privacy_text'),
            segmentIds: $request->input('segment_ids'),
            image: $request->file('image'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status->value,
            'device_type' => $this->deviceType->value,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'button_enabled' => $this->buttonEnabled,
            'button_text' => $this->buttonText,
            'input_field_enabled' => $this->inputFieldEnabled,
            'input_field_type' => $this->inputFieldType->value,
            'input_field_label' => $this->inputFieldLabel,
            'input_field_placeholder' => $this->inputFieldPlaceholder,
            'input_field_required' => $this->inputFieldRequired,
            'display_delay_seconds' => $this->displayDelaySeconds,
            'privacy_text' => $this->privacyText,
            'segment_ids' => $this->segmentIds,
        ];
    }
}
