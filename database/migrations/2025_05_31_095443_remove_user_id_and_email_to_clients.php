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
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign('clients_user_id_foreign');
            $table->dropColumn('user_id');

            $table->string('email')->unique()->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');

            $table->unsignedBigInteger('user_id')->nullable()->after('id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null'); // Use 'cascade' or other as originally defined
        });
    }
};
