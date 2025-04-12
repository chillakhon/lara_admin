<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToReviewsTable extends Migration
{
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('status')->default('new')->after('rating');
        });

        \DB::table('reviews')
            ->where('is_published', true)
            ->update(['status' => 'published']);

        \DB::table('reviews')
            ->where('is_published', false)
            ->update(['status' => 'new']);
    }

    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}

