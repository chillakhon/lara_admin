<?php

namespace App\Actions\OtoBanner;

use App\Models\Client;
use Carbon\Carbon;

class AttachClientToSegmentsAction
{
    public function execute(Client $client, array $segmentIds): void
    {
        foreach ($segmentIds as $segmentId) {
            // Проверяем, не находится ли уже клиент в сегменте
            if (!$client->segments()->where('segment_id', $segmentId)->exists()) {
                $client->segments()->attach($segmentId, [
                    'added_at' => Carbon::now(),
                ]);
            }
        }
    }
}
