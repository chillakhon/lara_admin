<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        $categories = ['Гостиная', 'Спальня', 'Кухня', 'Офис', 'Детская', 'Ванная'];
        $subcategories = ['Диваны', 'Кровати', 'Столы', 'Стулья', 'Шкафы', 'Комоды', 'Полки'];

        return [
            'name' => $this->faker->unique()->randomElement($categories),
            'slug' => function (array $attributes) {
                return Str::slug($attributes['name']);
            },
            'description' => $this->faker->sentence,
        ];
    }

    public function subcategory()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->unique()->randomElement(['Диваны', 'Кровати', 'Столы', 'Стулья', 'Шкафы', 'Комоды', 'Полки']),
            ];
        });
    }

}
