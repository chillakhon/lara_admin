<?php

namespace Database\Seeders;

use App\Models\Review\ReviewResponse;
use Illuminate\Database\Seeder;

class ReviewResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReviewResponse::factory()->count(50)->create();
    }
}
