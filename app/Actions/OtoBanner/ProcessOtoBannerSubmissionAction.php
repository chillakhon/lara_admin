<?php

namespace App\Actions\OtoBanner;

use App\DTOs\OtoBanner\OtoBannerSubmissionDTO;
use App\Models\Client;
use App\Models\ContactRequest;
use App\Models\Oto\OtoBanner;
use App\Models\Tag\Tag;
use App\Repositories\OtoBanner\OtoBannerSubmissionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessOtoBannerSubmissionAction
{
    public function __construct(
        private readonly OtoBannerSubmissionRepository $repository,
        private readonly AttachClientToSegmentsAction  $attachToSegmentsAction,
    )
    {
    }

    public function execute(OtoBanner $banner, OtoBannerSubmissionDTO $dto): ContactRequest
    {
        return DB::transaction(function () use ($banner, $dto) {

            $promoStatus = null;
            $promoId = null;
            $alreadyHasPromo = null;

            $banner->load('promoCode');

            // Если клиент не авторизован, пытаемся найти или создать
            $clientId = $dto->clientId ?? $this->findOrCreateClient($dto);

            // --- 1) Считаем статус промо ДО создания заявки ---
            if ($banner->promo_code_id && $banner->input_field_type?->value === 'email') {
                $promo = $banner->promoCode;

                if ($promo && $clientId) {
                    $client = Client::find($clientId);

                    if ($client && $client->email) {
                        $alreadyHasPromo = $client->promoCodes()
                            ->where('promo_code_id', $promo->id)
                            ->exists();

                        $promoId = $promo->id;
                        $promoStatus = $alreadyHasPromo ? 'already_issued' : 'issued';
                    }
                }
            }

            // --- 2) Создаём заявку ---
            $submissionData = $dto->toContactRequestArray($banner->name);
            $submissionData['client_id'] = $clientId;

            // Добавляем promo-мету (не затирая существующую meta)
            if (!empty($promoStatus) && !empty($promoId)) {
                $submissionData['meta'] = $submissionData['meta'] ?? [];
                $submissionData['meta']['promo'] = [
                    'status' => $promoStatus,
                    'promo_code_id' => $promoId,
                ];
            }

            $submission = $this->repository->create($submissionData);

            // Тегируем заявку названием баннера
            $this->tagSubmission($submission, $banner);

            // Добавляем клиента в сегменты если указаны
            if ($banner->segment_ids && !empty($banner->segment_ids)) {
                $client = Client::find($clientId);
                if ($client) {
                    $this->attachToSegmentsAction->execute($client, $banner->segment_ids);
                }
            }

            // --- 3) Если промо новое — привязываем и отправляем письмо ---
            if ($promoStatus === 'issued' && $promoId && $clientId) {
                $client = Client::find($clientId);
                $promo = $banner->promoCode;

                if ($client && $promo && $client->email) {
                    // На всякий случай (чтобы не упасть на unique)
                    if (!$client->promoCodes()->where('promo_code_id', $promo->id)->exists()) {
                        $client->promoCodes()->attach($promo->id);
                    }

                    $html = view('emails.oto-promo-code', compact('client', 'promo', 'banner'))->render();

                    \App\Services\Notifications\Jobs\SendNotificationJob::dispatch(
                        channel: 'email',
                        recipientId: $client->email,
                        message: $html,
                        data: [
                            'subject' => 'Ваш эксклюзивный промокод от OTO-предложения!',
                        ]
                    );
                }
            }

            return $submission->load(['client.profile', 'otoBanner']);
        });
    }


    private function findOrCreateClient(OtoBannerSubmissionDTO $dto): ?int
    {
        // Ищем клиента по email или phone
        $client = null;

        if ($dto->email) {
            $client = Client::where('email', $dto->email)->first();
        }

        if (!$client && $dto->phone) {
            $client = Client::whereHas('profile', function ($query) use ($dto) {
                $query->where('phone', $dto->phone);
            })->first();
        }

        // Если не нашли - создаём нового
        if (!$client && $dto->email) {
            $client = Client::create([
                'email' => $dto->email,
                'password' => Hash::make(Str::random(16)),
            ]);

            // Создаём профиль
            $client->profile()->create([
                'first_name' => $dto->name ?? 'Гость',
                'phone' => $dto->phone,
            ]);
        }

        return $client?->id;
    }

    private function tagSubmission(ContactRequest $submission, OtoBanner $banner): void
    {
        // Находим или создаём тег с названием баннера
        $tag = Tag::firstOrCreate(
            ['name' => $banner->name],
            ['color' => '#' . substr(md5($banner->name), 0, 6)]
        );

        // Привязываем тег к клиенту если есть
        if ($submission->client_id) {
            $client = Client::find($submission->client_id);
            if ($client && !$client->tags()->where('tag_id', $tag->id)->exists()) {
                $client->tags()->attach($tag->id);
            }
        }
    }
}
