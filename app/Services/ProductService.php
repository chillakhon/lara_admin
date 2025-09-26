<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function setActive(array $ids, bool $status): void
    {
        DB::transaction(function () use ($ids, $status) {
            Product::whereIn('id', $ids)
                ->update(['is_active' => $status, 'updated_at' => now()]);

            ProductVariant::whereIn('product_id', $ids)
                ->update(['is_active' => $status, 'updated_at' => now()]);
        });
    }
}

