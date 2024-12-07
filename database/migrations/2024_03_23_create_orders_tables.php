<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PromoCode;
return new class extends Migration
{
    public function up()
    {
        // Заказы
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('client_id')->nullable()->constrained();
            $table->foreignId('lead_id')->nullable()->constrained();
            $table->string('status'); // new, processing, completed, cancelled
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->foreignIdFor(PromoCode::class)->nullable()->constrained();
            $table->string('payment_method')->nullable(); // cash, card, online
            $table->string('payment_provider')->nullable(); // stripe, paypal, etc
            $table->string('payment_id')->nullable(); // ID транзакции в платежной системе
            $table->timestamp('paid_at')->nullable();
            $table->string('source')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // История обработки заказов
        Schema::create('order_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('status');
            $table->string('payment_status')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // Позиции заказа
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_variant_id')->nullable()->constrained();
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->timestamps();
        });

        // Платежи
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('payment_method');
            $table->string('payment_provider');
            $table->string('payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status'); // pending, completed, failed, refunded
            $table->json('payment_data')->nullable(); // Дополнительные данные от платежной системы
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order_histories');
        Schema::dropIfExists('orders');
    }
}; 