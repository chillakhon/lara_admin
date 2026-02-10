<?php

namespace App\DTOs\OtoBanner;

use App\Enums\Oto\OtoBannerDeviceType;
use App\Enums\Oto\OtoBannerInputFieldType;
use App\Enums\Oto\OtoBannerStatus;
use Illuminate\Http\Request;

class UpdateOtoBannerDTO
{
    public function __construct(
        public readonly bool                     $titleProvided = false,
        public readonly ?string                  $title = null,

        public readonly bool                     $subtitleProvided = false,
        public readonly ?string                  $subtitle = null,

        public readonly bool                     $buttonTextProvided = false,
        public readonly ?string                  $buttonText = null,

        public readonly bool                     $privacyTextProvided = false,
        public readonly ?string                  $privacyText = null,

        public readonly bool $inputFieldPlaceholderProvided = false,
        public readonly ?string $inputFieldPlaceholder = null,

        // остальное как было...
        public readonly ?string                  $name = null,
        public readonly ?OtoBannerStatus         $status = null,
        public readonly ?OtoBannerDeviceType     $deviceType = null,
        public readonly ?bool                    $buttonEnabled = null,
        public readonly ?bool                    $inputFieldEnabled = null,
        public readonly ?OtoBannerInputFieldType $inputFieldType = null,
        public readonly ?string                  $inputFieldLabel = null,
        public readonly ?bool                    $inputFieldRequired = null,
        public readonly ?int                     $displayDelaySeconds = null,
        public readonly ?array                   $segmentIds = null,
        public readonly mixed                    $image = null,
        public readonly ?int                     $promoCodeId = null,
    )
    {
    }

    public static function fromRequest(Request $request): self
    {
        $titleProvided = $request->exists('title');
        $subtitleProvided = $request->exists('subtitle');
        $buttonTextProvided = $request->exists('button_text');
        $privacyTextProvided = $request->exists('privacy_text');
        $inputFieldPlaceholderProvided = $request->exists('input_field_placeholder');

        return new self(
            titleProvided: $titleProvided,
            title: $titleProvided ? $request->input('title') : null,

            subtitleProvided: $subtitleProvided,
            subtitle: $subtitleProvided ? $request->input('subtitle') : null,

            buttonTextProvided: $buttonTextProvided,
            buttonText: $buttonTextProvided ? $request->input('button_text') : null,

            privacyTextProvided: $privacyTextProvided,
            privacyText: $privacyTextProvided ? $request->input('privacy_text') : null,

            inputFieldPlaceholderProvided: $inputFieldPlaceholderProvided,
            inputFieldPlaceholder: $inputFieldPlaceholderProvided
                ? $request->input('input_field_placeholder')
                : null,


            name: $request->input('name'),
            status: $request->has('status') ? OtoBannerStatus::from($request->input('status')) : null,
            deviceType: $request->has('device_type') ? OtoBannerDeviceType::from($request->input('device_type')) : null,
            buttonEnabled: $request->has('button_enabled') ? $request->boolean('button_enabled') : null,
            inputFieldEnabled: $request->has('input_field_enabled') ? $request->boolean('input_field_enabled') : null,
            inputFieldType: $request->has('input_field_type') ? OtoBannerInputFieldType::from($request->input('input_field_type')) : null,
            inputFieldLabel: $request->input('input_field_label'),
            inputFieldRequired: $request->has('input_field_required') ? $request->boolean('input_field_required') : null,
            displayDelaySeconds: $request->input('display_delay_seconds'),
            segmentIds: $request->input('segment_ids'),
            image: $request->file('image'),
            promoCodeId: $request->integer('promo_code_id'),
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->status !== null) $data['status'] = $this->status->value;
        if ($this->deviceType !== null) $data['device_type'] = $this->deviceType->value;

        // ВАЖНО: обновляем даже null, если поле было в запросе
        if ($this->titleProvided) $data['title'] = $this->title;
        if ($this->subtitleProvided) $data['subtitle'] = $this->subtitle;
        if ($this->buttonTextProvided) $data['button_text'] = $this->buttonText;
        if ($this->privacyTextProvided) $data['privacy_text'] = $this->privacyText;
        if ($this->inputFieldPlaceholderProvided) $data['input_field_placeholder'] = $this->inputFieldPlaceholder;

        if ($this->buttonEnabled !== null) $data['button_enabled'] = $this->buttonEnabled;
        if ($this->inputFieldEnabled !== null) $data['input_field_enabled'] = $this->inputFieldEnabled;
        if ($this->inputFieldType !== null) $data['input_field_type'] = $this->inputFieldType->value;
        if ($this->inputFieldLabel !== null) $data['input_field_label'] = $this->inputFieldLabel;
        if ($this->inputFieldRequired !== null) $data['input_field_required'] = $this->inputFieldRequired;
        if ($this->displayDelaySeconds !== null) $data['display_delay_seconds'] = $this->displayDelaySeconds;
        if ($this->segmentIds !== null) $data['segment_ids'] = $this->segmentIds;
        if ($this->promoCodeId !== null) $data['promo_code_id'] = $this->promoCodeId;
        return $data;
    }
}

