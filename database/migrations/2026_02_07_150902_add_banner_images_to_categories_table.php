<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('banner_image_desktop')->nullable()->after('banner_image');
            $table->string('banner_image_mobile')->nullable()->after('banner_image_desktop');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['banner_image_desktop', 'banner_image_mobile']);
        });
    }
};
