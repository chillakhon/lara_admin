<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductVariantOptionValue extends Pivot
{
    protected $table = 'product_variant_option_values';

    protected $fillable = [
        'product_variant_id',
        'option_value_id'
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class);
    }
}
