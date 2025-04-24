<?php
namespace App\Traits;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductVariant;

trait HelperTrait
{
    public function get_model_by_type($type)
    {
        $modelClass = match ($type) {
            'ProductVariant' => ProductVariant::class, // this should come here for now
            'Product' => Product::class,
            'Material' => Material::class,
        };

        return $modelClass;
    }

    public function get_type_by_model($model_type)
    {
        $modelClass = match ($model_type) {
            ProductVariant::class => 'ProductVariant',
            Product::class => 'Product',
            Material::class => 'Material',
        };

        return $modelClass;
    }
}
