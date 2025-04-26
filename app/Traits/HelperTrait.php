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


    public function change_items_model_type(
        &$recipes,
        $material_type_name = 'component_type',
        $output_type_name = 'component_type'
    ) {
        foreach ($recipes as $key => &$recipe) {
            if (isset($recipe['material_items'])) {
                foreach ($recipe['material_items'] as &$item) {
                    $item[$material_type_name] = $this->get_type_by_model($item[$material_type_name]);
                }
            }

            if (isset($recipe['output_products'])) {
                foreach ($recipe['output_products'] as &$item) {
                    $item[$output_type_name] = $this->get_type_by_model($item[$output_type_name]);
                }
            }
        }
    }
}
