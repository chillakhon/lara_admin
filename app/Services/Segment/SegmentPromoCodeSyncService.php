<?php

namespace App\Services\Segment;

use App\Models\Client;
use App\Models\Segments\Segment;
use Illuminate\Support\Facades\DB;

class SegmentPromoCodeSyncService
{
    /**
     * Синхронизировать промокоды сегмента с клиентами
     */
    public function syncPromoCodeesToClients(Segment $segment, array $clientIds): void
    {
        $promoCodeIds = $segment->promoCodes()->pluck('promo_codes.id')->toArray();

        if (empty($promoCodeIds)) {
            return;
        }

        $this->syncSpecificPromoCodeesToClients($promoCodeIds, $clientIds);
    }

    /**
     * Синхронизировать конкретные промокоды с клиентами
     */
    public function syncSpecificPromoCodeesToClients(array $promoCodeIds, array $clientIds): void
    {
        $now = now();
        $insertData = [];

        foreach ($clientIds as $clientId) {
            foreach ($promoCodeIds as $promoCodeId) {
                $insertData[] = [
                    'client_id' => $clientId,
                    'promo_code_id' => $promoCodeId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($insertData)) {
            // Используем insertOrIgnore для избежания дубликатов
            DB::table('promo_code_client')->insertOrIgnore($insertData);
        }
    }

    /**
     * Удалить промокоды сегмента у клиентов
     */
    public function removePromoCodesFromClients(Segment $segment, array $clientIds): void
    {
        $promoCodeIds = $segment->promoCodes()->pluck('promo_codes.id')->toArray();

        if (empty($promoCodeIds)) {
            return;
        }

        $this->removeSpecificPromoCodesFromClients($promoCodeIds, $clientIds);
    }

    /**
     * Удалить конкретные промокоды у клиентов
     */
    public function removeSpecificPromoCodesFromClients(array $promoCodeIds, array $clientIds): void
    {
        DB::table('promo_code_client')
            ->whereIn('client_id', $clientIds)
            ->whereIn('promo_code_id', $promoCodeIds)
            ->delete();
    }

    /**
     * Открепить все промокоды сегмента от всех клиентов
     */
    public function detachAllPromoCodesFromSegment(Segment $segment): void
    {
        $clientIds = $segment->clients()->pluck('clients.id')->toArray();
        $promoCodeIds = $segment->promoCodes()->pluck('promo_codes.id')->toArray();

        if (!empty($clientIds) && !empty($promoCodeIds)) {
            $this->removeSpecificPromoCodesFromClients($promoCodeIds, $clientIds);
        }
    }

    /**
     * Синхронизировать промокоды при изменении списка промокодов сегмента
     */
    public function syncSegmentPromoCodes(Segment $segment): void
    {
        $clientIds = $segment->clients()->pluck('clients.id')->toArray();

        if (empty($clientIds)) {
            return;
        }

        $this->syncPromoCodeesToClients($segment, $clientIds);
    }
}
