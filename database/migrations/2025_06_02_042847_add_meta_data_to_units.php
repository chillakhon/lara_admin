<?php

use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->longText('meta_data')->nullable()->after('abbreviation');
        });

        $data_for_creation = [
            ["name" => "Минута", "abbreviation" => "мин"],
            ["name" => "Сантиметр", "abbreviation" => "см"]
        ];

        foreach ($data_for_creation as $key => $value) {
            $check = Unit::where('abbreviation', $value['abbreviation'])->first();
            if (!$check) {
                Unit::create($value);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['meta_data']);
        });
    }
};
