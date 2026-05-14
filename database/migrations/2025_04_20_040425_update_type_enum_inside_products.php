<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('simple', 'manufactured', 'composite', 'material') NOT NULL DEFAULT 'simple'");

        DB::statement("ALTER TABLE product_variants MODIFY COLUMN type ENUM('simple', 'manufactured', 'composite', 'material') NOT NULL DEFAULT 'simple'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN type ENUM('simple', 'manufactured', 'composite') NOT NULL DEFAULT 'simple'");

        DB::statement("ALTER TABLE product_variants MODIFY COLUMN type ENUM('simple', 'manufactured', 'composite') NOT NULL DEFAULT 'simple'");
    }
};
