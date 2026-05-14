<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Field extends Model
{
    protected $fillable = [
        'name',
        'key',
        'type',
        'settings',
        'required',
        'order',
        'parent_id'
    ];

    protected $casts = [
        'settings' => 'array',
        'required' => 'boolean'
    ];

    /**
     * Дочерние поля (для repeater)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Field::class, 'parent_id')->orderBy('order');
    }

    /**
     * Родительское поле
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'parent_id');
    }

    /**
     * Значения поля
     */
    public function values(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }
}
