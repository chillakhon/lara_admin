<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Публичный токен для просмотра заказа без авторизации:
 * /orders/{view_token} на витрине. Длина 32 hex-символа,
 * чтобы ссылку нельзя было угадать.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'view_token')) {
                $table->string('view_token', 64)->nullable()->unique()->after('order_number');
            }
        });

        // Бэкфилл существующих заказов уникальными токенами.
        Order::query()
            ->whereNull('view_token')
            ->orderBy('id')
            ->chunkById(500, function ($orders) {
                foreach ($orders as $order) {
                    do {
                        $token = bin2hex(random_bytes(16));
                    } while (Order::where('view_token', $token)->exists());

                    $order->forceFill(['view_token' => $token])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'view_token')) {
                $table->dropUnique(['view_token']);
                $table->dropColumn('view_token');
            }
        });
    }
};
