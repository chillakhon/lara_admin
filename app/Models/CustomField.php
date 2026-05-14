<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $fillable = ['name', 'type', 'settings'];
    
    protected $casts = [
        'settings' => 'json'
    ];

    const TYPES = [
        'text' => 'Текстовое поле',
        'textarea' => 'Текстовая область',
        'wysiwyg' => 'Визуальный редактор',
        'image' => 'Изображение',
        'gallery' => 'Галерея',
        'select' => 'Выпадающий список',
        'checkbox' => 'Флажок',
        'repeater' => 'Повторитель'
    ];
}