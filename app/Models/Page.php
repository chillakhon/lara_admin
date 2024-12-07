<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function pageContents(): HasMany
    {
        return $this->hasMany(PageContent::class);
    }

    public function getContentByLanguage(string $language = 'ru'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->pageContents()
            ->where('language', $language)
            ->orderBy('sort_order')
            ->get();
    }

    public function getContentBlockByKey(string $key, string $language = 'ru'): ?PageContent
    {
        return $this->pageContents()
            ->whereHas('contentBlock', function ($query) use ($key) {
                $query->where('key', $key);
            })
            ->where('language', $language)
            ->first();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = \Str::slug($page->title);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = \Str::slug($page->title);
            }
        });
    }
}
