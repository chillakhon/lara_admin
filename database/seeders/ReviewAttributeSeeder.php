<?php

namespace Database\Seeders;

use App\Models\Review\ReviewAttribute;
use Illuminate\Database\Seeder;

class ReviewAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReviewAttribute::factory()->count(100)->create();
    }
}
