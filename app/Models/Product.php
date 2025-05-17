<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;  // Добавляем интерфейс HasMedia
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"simple", "manufactured", "composite"}
 *     ),
 *     @OA\Property(property="default_unit_id", type="integer", nullable=true),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="has_variants", type="boolean"),
 *     @OA\Property(property="allow_preorder", type="boolean"),
 *     @OA\Property(property="after_purchase_processing_time", type="integer"),
 *     @OA\Property(property="price", type="number", format="float", nullable=true),
 *     @OA\Property(property="cost_price", type="number", format="float", nullable=true),
 *     @OA\Property(property="stock_quantity", type="integer", example=0),
 *     @OA\Property(property="min_order_quantity", type="integer", example=1),
 *     @OA\Property(property="max_order_quantity", type="integer", nullable=true),
 *     @OA\Property(property="is_featured", type="boolean", default=false),
 *     @OA\Property(property="is_new", type="boolean", default=false),
 *     @OA\Property(property="discount_price", type="number", format="float", nullable=true),
 *     @OA\Property(property="sku", type="string", nullable=true),
 *     @OA\Property(property="currency", type="string", nullable=true),
 *     @OA\Property(property="barcode", type="string", nullable=true),
 *     @OA\Property(property="weight", type="number", format="float", nullable=true),
 *     @OA\Property(property="length", type="number", format="float", nullable=true),
 *     @OA\Property(property="width", type="number", format="float", nullable=true),
 *     @OA\Property(property="height", type="number", format="float", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="categories",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Category")
 *     )
 * )
 */

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    // protected $fillable = [
    //     'name',
    //     'slug',
    //     'description',
    //     'type',
    //     'default_unit_id',
    //     'is_active',
    //     'has_variants',
    //     'price',
    //     'cost_price',
    //     'stock_quantity',
    //     'min_order_quantity',
    //     'max_order_quantity',
    //     'is_featured',
    //     'is_new',
    //     'discount_price',
    //     'sku',
    //     'barcode',
    //     'weight',
    //     'length',
    //     'width',
    //     'height',
    //     'currency',
    // ];

    protected $guarded = ["id"];

    protected $casts = [
        'is_active' => 'boolean',
        'has_variants' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
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
        return $this->morphOne(InventoryBalance::class, 'item', 'item_type', 'item_id');
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
        return $this->morphMany(Image::class, 'item');
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
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'yo',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'kh',
            'ц' => 'ts',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'shch',
            'ъ' => '',
            'ы' => 'y',
            'ь' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'Yo',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'Kh',
            'Ц' => 'Ts',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Shch',
            'Ъ' => '',
            'Ы' => 'Y',
            'Ь' => '',
            'Э' => 'E',
            'Ю' => 'Yu',
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
            ->where('is_published', true)
            ->avg('rating') ?? 0;
    }

    public function getReviewsCountAttribute()
    {
        return $this->reviews()
            ->where('is_published', true)
            ->count();
    }


    public function colors(): MorphToMany
    {
        return $this->morphToMany(Color::class, 'colorable');
    }
}
