<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    public function fieldValues(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    /**
     * Получить значение поля по ключу
     */
    public function getFieldValue(string $key, $default = null)
    {
        $value = $this->fieldValues()
            ->whereHas('field', function ($query) use ($key) {
                $query->where('key', $key);
            })
            ->first();

        return $value ? $value->value : $default;
    }

    /**
     * Получить все значения для repeater поля
     */
    public function getRepeaterValues(string $key): array
    {
        $repeater = $this->fieldValues()
            ->whereHas('field', function ($query) use ($key) {
                $query->where('key', $key)
                    ->where('type', 'repeater');
            })
            ->first();

        if (!$repeater) {
            return [];
        }

        return $repeater->children()
            ->with(['field', 'children.field'])
            ->get()
            ->groupBy('order')
            ->map(function ($group) {
                return $group->mapWithKeys(function ($value) {
                    return [$value->field->key => $value->value];
                });
            })
            ->values()
            ->toArray();
    }
}
