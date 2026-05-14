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
            $table->unsignedBigInteger('review_response_id')->nullable()->after('client_id');

            $table->foreign('review_response_id', 'fk_review_response_id')
                ->on('review_responses')
                ->references('id')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_responses', function (Blueprint $table) {
            $table->dropForeign('fk_review_response_id');

            $table->dropColumn('review_response_id');
        });
    }
};
