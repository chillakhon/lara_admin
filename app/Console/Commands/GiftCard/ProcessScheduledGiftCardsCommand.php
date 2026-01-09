<?php

namespace App\Console\Commands\GiftCard;

use App\Jobs\GiftCard\SendGiftCardJob;
use App\Models\GiftCard\GiftCard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledGiftCardsCommand extends Command
{
    protected $signature = 'giftcards:send-scheduled';
    protected $description = 'Отправить подарочные карты по расписанию';

    public function handle(): int
    {
        $this->info('Проверка отложенных подарочных карт...');

        // Получаем карты, которые пора отправить
        $giftCards = GiftCard::where('status', GiftCard::STATUS_ACTIVE)
            ->whereNotNull('scheduled_at')
            ->whereNull('sent_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($giftCards->isEmpty()) {
            $this->info('Нет карт для отправки.');
            return Command::SUCCESS;
        }

        $this->info("Найдено карт для отправки: {$giftCards->count()}");

        foreach ($giftCards as $giftCard) {
            try {
                // Диспатчим джобу на отправку
                SendGiftCardJob::dispatch($giftCard->id);

                $this->info("Карта #{$giftCard->id} добавлена в очередь на отправку");

                Log::info('Scheduled gift card dispatched', [
                    'gift_card_id' => $giftCard->id,
                    'scheduled_at' => $giftCard->scheduled_at,
                ]);

            } catch (\Exception $e) {
                $this->error("Ошибка при обработке карты #{$giftCard->id}: {$e->getMessage()}");

                Log::error('Failed to dispatch scheduled gift card', [
                    'gift_card_id' => $giftCard->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Обработка завершена.');
        return Command::SUCCESS;
    }
}
