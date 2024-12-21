<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'default_unit_id',
        'is_active',
        'has_variants'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'has_variants' => 'boolean',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->slug = $product->generateUniqueSlug($product->name);
        });

        static::updating(function ($product) {
            if ($product->isDirty('name')) {
                $product->slug = $product->generateUniqueSlug($product->name);
            }
        });
    }

    public function recipeItems(): MorphMany
    {
        return $this->morphMany(RecipeItem::class, 'component');
    }

    public function inventoryBalance(): MorphOne
    {
        return $this->morphOne(InventoryBalance::class, 'item');
    }

    public function getCurrentStock(): float
    {
        return $this->inventoryBalance?->total_quantity ?? 0;
    }

    public function getAverageCost(): float
    {
        return $this->inventoryBalance?->average_price ?? 0;
    }

    public function inventoryBatches()
    {
        return $this->morphMany(InventoryBatch::class, 'item');
    }

    public function checkAvailability(float $quantity): bool
    {
        return $this->getCurrentStock() >= $quantity;
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'item');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultRecipe()
    {
        return $this->recipes()
            ->wherePivot('is_default', true)
            ->wherePivot('product_variant_id', null)
            ->first();
    }

    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'product_recipes')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function defaultUnit()
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }


    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'product_options')
            ->using(ProductOption::class)
            ->withPivot(['is_required', 'order'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function images()
    {
        return $this->morphToMany(Image::class, 'imageable')
            ->withPivot('product_variant_id')
            ->withTimestamps();
    }

    public function getImagesForVariant($variantId)
    {
        return $this->images()
            ->wherePivot('product_variant_id', $variantId)
            ->get();
    }

    public function generateUniqueSlug($name)
    {
        $slug = $this->slugify($this->transliterate($name));
        $count = static::whereRaw("slug RLIKE '^{$slug}(-[0-9]+)?$'")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    protected function slugify($text)
    {
        // Remove unwanted characters
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // Remove remaining unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim
        $text = trim($text, '-');

        // Remove duplicate separators
        $text = preg_replace('~-+~', '-', $text);

        // Lowercase
        $text = strtolower($text);

        return $text ?: 'n-a';
    }

    protected function transliterate($text)
    {
        $transliterationTable = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
        ];

        return strtr($text, $transliterationTable);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function getAverageRatingAttribute(): float
    {
        return $this->reviews()
            ->published()
            ->verified()
            ->avg('rating') ?? 0;
    }

}
