<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\FieldGroup;
use App\Models\FieldType;
use App\Models\Field;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentBlockSeeder extends Seeder
{
    public function run()
    {
        // Очищаем таблицы в правильном порядке (из-за внешних ключей)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ContentBlock::truncate();
        Field::truncate();
        FieldGroup::truncate();
        FieldType::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Создаем типы полей если их нет
        $textType = FieldType::create([
            'name' => 'Текстовое поле',
            'type' => 'text'
        ]);

        $textareaType = FieldType::create([
            'name' => 'Многострочное поле',
            'type' => 'textarea'
        ]);

        $imageType = FieldType::create([
            'name' => 'Изображение',
            'type' => 'image'
        ]);

        $wysiwygType = FieldType::create([
            'name' => 'Визуальный редактор',
            'type' => 'wysiwyg'
        ]);

        // Создаем группу полей для текстового блока
        $textBlockGroup = FieldGroup::create([
            'name' => 'Текстовый блок'
        ]);

        // Добавляем поля в группу
        $textBlockGroup->fields()->createMany([
            [
                'name' => 'Заголовок',
                'key' => 'text_block_title',
                'field_type_id' => $textType->id,
                'required' => true
            ],
            [
                'name' => 'Текст',
                'key' => 'text_block_content',
                'field_type_id' => $wysiwygType->id,
                'required' => true
            ]
        ]);

        // Создаем текстовый блок
        ContentBlock::create([
            'name' => 'Текстовый блок',
            'key' => 'text_block',
            'field_group_id' => $textBlockGroup->id,
            'description' => 'Блок с заголовком и текстом'
        ]);

        // Создаем группу полей для блока с изображением
        $imageBlockGroup = FieldGroup::create([
            'name' => 'Блок с изображением'
        ]);

        // Добавляем поля в группу
        $imageBlockGroup->fields()->createMany([
            [
                'name' => 'Заголовок',
                'key' => 'image_block_title',
                'field_type_id' => $textType->id,
                'required' => true
            ],
            [
                'name' => 'Описание',
                'key' => 'image_block_description',
                'field_type_id' => $textareaType->id,
                'required' => false
            ],
            [
                'name' => 'Изображение',
                'key' => 'image_block_image',
                'field_type_id' => $imageType->id,
                'required' => true
            ]
        ]);

        // Создаем блок с изображением
        ContentBlock::create([
            'name' => 'Блок с изображением',
            'key' => 'image_block',
            'field_group_id' => $imageBlockGroup->id,
            'description' => 'Блок с заголовком, описанием и изображением'
        ]);
    }
} 