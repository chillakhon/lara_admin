<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Создаем основные категории
        Category::factory()
            ->mainCategory()
            ->count(4)
            ->create()
            ->each(function ($category) {
                // Для каждой основной категории создаем 3-5 подкатегорий
                Category::factory()
                    ->subcategory($category)
                    ->count(fake()->numberBetween(3, 5))
                    ->create();
            });
    }
}
