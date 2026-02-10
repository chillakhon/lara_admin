<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NodeTrait;


class Category extends Model
{
    use HasFactory, NodeTrait;

    protected $guarded = false;

    public $timestamps = false;

    protected $appends = ['depth'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'show_in_catalog_menu',
        'show_as_home_banner',
        'menu_order',
        'banner_image',

        'banner_image_desktop',
        'banner_image_mobile',

        'is_new_product'
    ];

    protected $casts = [
        'show_in_catalog_menu' => 'boolean',
        'show_as_home_banner' => 'boolean',
        'is_new_product' => 'boolean',
        'menu_order' => 'integer',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = $category->generateUniqueSlug($category->name);
        });

        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });
    }

    public function getDepthAttribute()
    {
        return $this->ancestors->count();
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->with('children')
            ->defaultOrder();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    public function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $count = static::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function options()
    {
        return $this->hasMany(Option::class);
    }

    public function colorOptions(): HasMany
    {
        return $this->hasMany(ColorOption::class);
    }

    public function scopeDefaultOrder($query)
    {
        return $query->orderBy('_lft');
    }
}
