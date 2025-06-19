<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('review_responses', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->after('user_id');

            $table->foreign('client_id', 'review_responses_client_id_foreign')
                ->on('clients')
                ->references('id')
                ->onDelete('set null')
                ->onUpdate('cascade');

           $table->unsignedBigInteger('user_id')->nullable()->change(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_responses', function (Blueprint $table) {
            $table->dropForeign('review_responses_client_id_foreign');

            $table->dropColumn('client_id');
        });
    }
};
