<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldValue extends Model
{
    protected $fillable = [
        'page_id',
        'field_id',
        'value',
        'order',
        'parent_id'
    ];

    protected $casts = [
        'value' => 'array'
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FieldValue::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FieldValue::class, 'parent_id')->orderBy('order');
    }
} 