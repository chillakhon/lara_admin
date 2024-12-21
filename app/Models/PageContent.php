<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageContent extends Model
{
    protected $fillable = [
        'page_id',
        'content_block_id',
        'field_values',
        'order'
    ];

    protected $casts = [
        'field_values' => 'array'
    ];

    /**
     * Get the page that owns the content.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the content block that owns the content.
     */
    public function contentBlock(): BelongsTo
    {
        return $this->belongsTo(ContentBlock::class);
    }
} 