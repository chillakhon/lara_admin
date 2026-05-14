<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mail_settings', function (Blueprint $table) {
            $table->string('imap_host')->nullable()->after('encryption');
            $table->integer('imap_port')->nullable()->after('imap_host');
        });
    }

    public function down(): void
    {
        Schema::table('mail_settings', function (Blueprint $table) {
            $table->dropColumn(['imap_host', 'imap_port']);
        });
    }
};
