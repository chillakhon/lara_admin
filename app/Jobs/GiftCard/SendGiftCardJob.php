<?php

namespace App\Jobs\GiftCard;

use App\Models\GiftCard\GiftCard;
use App\Services\GiftCard\GiftCardDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendGiftCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Попыток отправки
    public $backoff = [60, 300, 900]; // Задержки между попытками (1 мин, 5 мин, 15 мин)

    public function __construct(
        protected int $giftCardId
    )
    {
    }

    public function handle(GiftCardDeliveryService $deliveryService): void
    {
        try {
            $giftCard = GiftCard::find($this->giftCardId);

            if (!$giftCard) {
                Log::warning('SendGiftCardJob: Gift card not found', [
                    'gift_card_id' => $this->giftCardId,
                ]);
                return;
            }

            // Проверяем, не отправлена ли уже
            if ($giftCard->sent_at) {
                Log::info('SendGiftCardJob: Gift card already sent', [
                    'gift_card_id' => $giftCard->id,
                ]);
                return;
            }


            // 3. Проверка типа карты (только электронные)
            if ($giftCard->type !== GiftCard::TYPE_ELECTRONIC) {
                Log::info('Gift card is not electronic, skipping', [
                    'id' => $giftCard->id,
                    'type' => $giftCard->type
                ]);
                return;
            }


            // 4. Проверка готовности к отправке
            if (!$this->isReadyToSend($giftCard)) {
                Log::info('Gift card is not ready to send', [
                    'id' => $giftCard->id,
                    'status' => $giftCard->status
                ]);
                return;
            }


            $success = $deliveryService->send($giftCard);

            if ($success) {
                // Отправляем подтверждение покупателю
                $deliveryService->sendDeliveryConfirmation($giftCard);

                Log::info('Gift card sent successfully', ['id' => $giftCard->id]);
            } else {
                throw new \RuntimeException('Failed to send gift card');
            }

        } catch (\Exception $e) {
            Log::error('SendGiftCardJob: Failed to send gift card', [
                'gift_card_id' => $this->giftCardId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Повторяем попытку
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            }
        }
    }


    /**
     * Проверка готовности карты к отправке
     */
    private function isReadyToSend(GiftCard $giftCard): bool
    {


        // Проверяем наличие получателя
        if (empty($giftCard->recipient_email) && empty($giftCard->recipient_phone)) {
            Log::error('No recipient contact info', ['id' => $giftCard->id]);
            return false;
        }

        // Проверяем канал доставки
        if (!in_array($giftCard->delivery_channel, ['email', 'telegram', 'sms'])) {
            Log::error('Invalid delivery channel', [
                'id' => $giftCard->id,
                'channel' => $giftCard->delivery_channel
            ]);
            return false;
        }

        return true;
    }

    public function failed(\Exception $exception): void
    {
        Log::error('SendGiftCardJob: All attempts failed', [
            'gift_card_id' => $this->giftCardId,
            'error' => $exception->getMessage(),
        ]);

        // TODO: Уведомить администратора о неудачной отправке
    }
}
