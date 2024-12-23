<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_method_id')->nullable()
                ->constrained()
                ->onDelete('restrict');
            $table->json('delivery_address')->nullable();
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->json('delivery_data')->nullable();
            $table->string('delivery_comment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_method_id']);
            $table->dropColumn([
                'delivery_method_id',
                'delivery_address',
                'delivery_cost',
                'delivery_data',
                'delivery_comment'
            ]);
        });
    }
}; 