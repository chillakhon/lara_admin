<?php

namespace Database\Seeders;

use App\Models\Field;
use Illuminate\Database\Seeder;

class FieldsSeeder extends Seeder
{
    public function run()
    {
        // Создаем repeater для блока с контентом
        $contentBlock = Field::create([
            'name' => 'Блоки контента',
            'key' => 'content_blocks',
            'type' => 'repeater',
            'settings' => [
                'min' => 0,
                'max' => null,
                'layout' => 'block' // block или row
            ]
        ]);

        // Создаем поля внутри repeater
        $contentBlock->children()->createMany([
            [
                'name' => 'Заголовок',
                'key' => 'title',
                'type' => 'text',
                'required' => true,
                'order' => 0
            ],
            [
                'name' => 'Подзаголовок',
                'key' => 'subtitle',
                'type' => 'text',
                'required' => false,
                'order' => 1
            ],
            [
                'name' => 'Контент',
                'key' => 'content',
                'type' => 'wysiwyg',
                'settings' => [
                    'height' => 300,
                    'toolbar' => 'full'
                ],
                'required' => true,
                'order' => 2
            ],
            [
                'name' => 'Изображения',
                'key' => 'images',
                'type' => 'gallery',
                'settings' => [
                    'min' => 0,
                    'max' => 10
                ],
                'order' => 3
            ],
            [
                'name' => 'Тип блока',
                'key' => 'block_type',
                'type' => 'select',
                'settings' => [
                    'options' => [
                        ['value' => 'text', 'label' => 'Текстовый блок'],
                        ['value' => 'image', 'label' => 'Блок с изображением'],
                        ['value' => 'gallery', 'label' => 'Галерея'],
                        ['value' => 'video', 'label' => 'Видео']
                    ]
                ],
                'required' => true,
                'order' => 4
            ]
        ]);
    }
} 