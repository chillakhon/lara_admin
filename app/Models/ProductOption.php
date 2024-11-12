<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductOption extends Pivot
{
    protected $table = 'product_options';

    protected $fillable = [
        'product_id',
        'option_id',
        'is_required',
        'order'
    ];

    protected $casts = [
        'is_required' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
