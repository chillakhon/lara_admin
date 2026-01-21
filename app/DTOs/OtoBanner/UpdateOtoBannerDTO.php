<?php

namespace App\DTOs\OtoBanner;

use App\Enums\Oto\OtoBannerDeviceType;
use App\Enums\Oto\OtoBannerInputFieldType;
use App\Enums\Oto\OtoBannerStatus;
use Illuminate\Http\Request;

class UpdateOtoBannerDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?OtoBannerStatus $status = null,
        public readonly ?OtoBannerDeviceType $deviceType = null,
        public readonly ?string $title = null,
        public readonly ?string $subtitle = null,
        public readonly ?bool $buttonEnabled = null,
        public readonly ?string $buttonText = null,
        public readonly ?bool $inputFieldEnabled = null,
        public readonly ?OtoBannerInputFieldType $inputFieldType = null,
        public readonly ?string $inputFieldLabel = null,
        public readonly ?string $inputFieldPlaceholder = null,
        public readonly ?bool $inputFieldRequired = null,
        public readonly ?int $displayDelaySeconds = null,
        public readonly ?string $privacyText = null,
        public readonly ?array $segmentIds = null,
        public readonly mixed $image = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            status: $request->has('status') ? OtoBannerStatus::from($request->input('status')) : null,
            deviceType: $request->has('device_type') ? OtoBannerDeviceType::from($request->input('device_type')) : null,
            title: $request->input('title'),
            subtitle: $request->input('subtitle'),
            buttonEnabled: $request->has('button_enabled') ? $request->boolean('button_enabled') : null,
            buttonText: $request->input('button_text'),
            inputFieldEnabled: $request->has('input_field_enabled') ? $request->boolean('input_field_enabled') : null,
            inputFieldType: $request->has('input_field_type') ? OtoBannerInputFieldType::from($request->input('input_field_type')) : null,
            inputFieldLabel: $request->input('input_field_label'),
            inputFieldPlaceholder: $request->input('input_field_placeholder'),
            inputFieldRequired: $request->has('input_field_required') ? $request->boolean('input_field_required') : null,
            displayDelaySeconds: $request->input('display_delay_seconds'),
            privacyText: $request->input('privacy_text'),
            segmentIds: $request->input('segment_ids'),
            image: $request->file('image'),
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->status !== null) $data['status'] = $this->status->value;
        if ($this->deviceType !== null) $data['device_type'] = $this->deviceType->value;
        if ($this->title !== null) $data['title'] = $this->title;
        if ($this->subtitle !== null) $data['subtitle'] = $this->subtitle;
        if ($this->buttonEnabled !== null) $data['button_enabled'] = $this->buttonEnabled;
        if ($this->buttonText !== null) $data['button_text'] = $this->buttonText;
        if ($this->inputFieldEnabled !== null) $data['input_field_enabled'] = $this->inputFieldEnabled;
        if ($this->inputFieldType !== null) $data['input_field_type'] = $this->inputFieldType->value;
        if ($this->inputFieldLabel !== null) $data['input_field_label'] = $this->inputFieldLabel;
        if ($this->inputFieldPlaceholder !== null) $data['input_field_placeholder'] = $this->inputFieldPlaceholder;
        if ($this->inputFieldRequired !== null) $data['input_field_required'] = $this->inputFieldRequired;
        if ($this->displayDelaySeconds !== null) $data['display_delay_seconds'] = $this->displayDelaySeconds;
        if ($this->privacyText !== null) $data['privacy_text'] = $this->privacyText;
        if ($this->segmentIds !== null) $data['segment_ids'] = $this->segmentIds;

        return $data;
    }
}
