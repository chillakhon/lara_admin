<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoCodeProduct extends Model
{
    use HasFactory;

    protected $table = 'promo_code_product';

    protected $fillable = [
        'promo_code_id',
        'product_id',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }


}
