<?php

namespace Database\Seeders;

use App\Models\ReviewResponse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
