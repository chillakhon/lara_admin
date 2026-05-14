<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Client;
use Illuminate\Console\Command;

class CheckRelations extends Command
{
    protected $signature = 'check:relations';
    protected $description = 'Проверить связи между моделями';

    public function handle()
    {
        $this->info('🔍 Проверка связей...');
        $this->newLine();

        // Проверка Order -> Client
        $this->info('1. Проверка Order -> Client');
        try {
            $order = Order::first();
            if ($order) {
                $client = $order->client;
                if ($client) {
                    $this->line("   ✅ Order->client работает (client_id: {$client->id})");
                } else {
                    $this->error("   ❌ Order->client вернул null");
                }
            } else {
                $this->warn("   ⚠️  Нет заказов в БД");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Ошибка: " . $e->getMessage());
        }
        $this->newLine();

        // Проверка Client -> Profile
        $this->info('2. Проверка Client -> Profile');
        try {
            $client = Client::first();
            if ($client) {
                $profile = $client->profile;
                if ($profile) {
                    $this->line("   ✅ Client->profile работает");
                    $this->line("      - first_name: {$profile->first_name}");
                    $this->line("      - last_name: {$profile->last_name}");
                    $this->line("      - phone: {$profile->phone}");
                } else {
                    $this->error("   ❌ Client->profile вернул null");
                    $this->warn("      Убедитесь, что таблица client_profiles существует и связь настроена");
                }
            } else {
                $this->warn("   ⚠️  Нет клиентов в БД");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Ошибка: " . $e->getMessage());
        }
        $this->newLine();

        // Проверка Order -> Client -> Profile
        $this->info('3. Проверка Order -> Client -> Profile');
        try {
            $order = Order::with('client.profile')->first();
            if ($order && $order->client && $order->client->profile) {
                $profile = $order->client->profile;
                $this->line("   ✅ Order->client->profile работает");
                $this->line("      - Имя: {$profile->first_name} {$profile->last_name}");
            } else {
                $this->error("   ❌ Цепочка связей не работает");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Ошибка: " . $e->getMessage());
        }
        $this->newLine();

        // Проверка таблиц
        $this->info('4. Проверка таблиц в БД');
        $tables = ['orders', 'clients', 'client_profiles', 'order_items', 'promo_codes'];
        foreach ($tables as $table) {
            if (\Schema::hasTable($table)) {
                $this->line("   ✅ Таблица '{$table}' существует");
            } else {
                $this->error("   ❌ Таблица '{$table}' не найдена!");
            }
        }
        $this->newLine();

        // Проверка колонок в client_profiles
        $this->info('5. Проверка колонок в client_profiles');
        if (\Schema::hasTable('client_profiles')) {
            $columns = ['client_id', 'first_name', 'last_name', 'phone', 'delivery_address'];
            foreach ($columns as $column) {
                if (\Schema::hasColumn('client_profiles', $column)) {
                    $this->line("   ✅ Колонка '{$column}' существует");
                } else {
                    $this->error("   ❌ Колонка '{$column}' не найдена!");
                }
            }
        }
        $this->newLine();

        $this->info('✨ Проверка завершена!');
    }
}
