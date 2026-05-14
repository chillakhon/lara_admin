<?php

namespace Database\Seeders;

use App\Models\ShipmentStatus;
use Illuminate\Database\Seeder;

class ShipmentStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'code' => ShipmentStatus::NEW,
                'name' => 'Новый',
                'description' => 'Отправление создано'
            ],
            [
                'code' => ShipmentStatus::PROCESSING,
                'name' => 'В обработке',
                'description' => 'Отправление обрабатывается'
            ],
            [
                'code' => ShipmentStatus::READY_FOR_PICKUP,
                'name' => 'Готов к отправке',
                'description' => 'Отправление готово к передаче курьеру'
            ],
            [
                'code' => ShipmentStatus::IN_TRANSIT,
                'name' => 'В пути',
                'description' => 'Отправление в процессе доставки'
            ],
            [
                'code' => ShipmentStatus::DELIVERED,
                'name' => 'Доставлено',
                'description' => 'Отправление успешно доставлено'
            ],
            [
                'code' => ShipmentStatus::CANCELLED,
                'name' => 'Отменено',
                'description' => 'Отправление отменено'
            ],
            [
                'code' => ShipmentStatus::RETURNED,
                'name' => 'Возврат',
                'description' => 'Отправление возвращено отправителю'
            ],
        ];

        foreach ($statuses as $status) {
            ShipmentStatus::create($status);
        }
    }
} 