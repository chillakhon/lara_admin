<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Поля под легаси-импорт заказов из InSales (orders-DD.MM.YYYY.csv).
 *
 * Часто используемые поля выносим в отдельные колонки, чтобы по ним можно было
 * фильтровать/индексировать. Редкие нишевые поля (DMS-статус, второй чек ККТ,
 * служебные Почты России и т.п.) складываем в `legacy_meta` JSON, чтобы не
 * раздувать схему и не множить миграции.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'assigned_user_id')) {
                $table->foreignId('assigned_user_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable();
            }
            if (!Schema::hasColumn('orders', 'bonuses_credited')) {
                $table->decimal('bonuses_credited', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'bonuses_used')) {
                $table->decimal('bonuses_used', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'no_receipt')) {
                $table->boolean('no_receipt')->default(false);
            }
            if (!Schema::hasColumn('orders', 'export_country')) {
                $table->string('export_country', 64)->nullable();
            }
            if (!Schema::hasColumn('orders', 'utm_source_first')) {
                $table->string('utm_source_first')->nullable();
            }
            if (!Schema::hasColumn('orders', 'referrer_first')) {
                $table->string('referrer_first', 1024)->nullable();
            }
            if (!Schema::hasColumn('orders', 'referrer_last')) {
                $table->string('referrer_last', 1024)->nullable();
            }
            if (!Schema::hasColumn('orders', 'landing_first')) {
                $table->string('landing_first', 1024)->nullable();
            }
            if (!Schema::hasColumn('orders', 'landing_last')) {
                $table->string('landing_last', 1024)->nullable();
            }
            if (!Schema::hasColumn('orders', 'legacy_meta')) {
                $table->json('legacy_meta')->nullable();
            }
            if (!Schema::hasColumn('orders', 'legacy_delivery_method')) {
                $table->string('legacy_delivery_method', 512)->nullable();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'purchase_price')) {
                $table->decimal('purchase_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('order_items', 'marking_codes')) {
                $table->text('marking_codes')->nullable();
            }
        });

        // У легаси-позиций может не быть привязки к актуальному product_id —
        // имя/артикул сохраняем в legacy_name/legacy_sku, а product_id оставляем NULL.
        \DB::statement('ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NULL');

        // Импортированные исторические события не несут структурированного статуса —
        // оставляем колонку nullable, чтобы можно было хранить только description.
        \DB::statement('ALTER TABLE order_histories MODIFY status VARCHAR(255) NULL');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'assigned_user_id')) {
                $table->dropConstrainedForeignId('assigned_user_id');
            }
            foreach ([
                'tracking_number',
                'bonuses_credited',
                'bonuses_used',
                'no_receipt',
                'export_country',
                'utm_source_first',
                'referrer_first',
                'referrer_last',
                'landing_first',
                'landing_last',
                'legacy_meta',
                'legacy_delivery_method',
            ] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['purchase_price', 'marking_codes'] as $col) {
                if (Schema::hasColumn('order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
