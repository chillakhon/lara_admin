<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    // Массивы реальных категорий по разным направлениям
    private $mainCategories = [
        'Электроника' => [
            'Смартфоны и планшеты',
            'Ноутбуки и компьютеры',
            'Аудиотехника',
            'Фототехника',
            'ТВ и видео',
        ],
        'Одежда' => [
            'Верхняя одежда',
            'Джинсы и брюки',
            'Платья и юбки',
            'Футболки и топы',
            'Спортивная одежда',
        ],
        'Дом и сад' => [
            'Мебель',
            'Освещение',
            'Текстиль',
            'Посуда',
            'Садовый инвентарь',
        ],
        'Красота и здоровье' => [
            'Уход за лицом',
            'Уход за волосами',
            'Макияж',
            'Парфюмерия',
            'Витамины и БАДы',
        ],
    ];

    public function definition(): array
    {
        // Получаем случайную основную категорию и её подкатегории
        $mainCategory = fake()->randomElement(array_keys($this->mainCategories));
        $subcategories = $this->mainCategories[$mainCategory];

        return [
            'name' => fake()->randomElement([$mainCategory, ...$subcategories]),
            'description' => fake()->paragraph(),
        ];
    }

    // Состояние для создания только главных категорий
    public function mainCategory(): self
    {
        return $this->state(function () {
            return [
                'name' => fake()->randomElement(array_keys($this->mainCategories)),
                'parent_id' => null,
            ];
        });
    }

    // Состояние для создания подкатегорий
    public function subcategory(Category $parentCategory): self
    {
        return $this->state(function () use ($parentCategory) {
            $subcategories = $this->mainCategories[$parentCategory->name] ?? [];
            return [
                'name' => fake()->randomElement($subcategories),
                'parent_id' => $parentCategory->id,
            ];
        });
    }
}
