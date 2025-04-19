<?php

namespace Database\Seeders;

use App\Models\ReviewAttribute;
use Database\Factories\ReviewAttributeFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
