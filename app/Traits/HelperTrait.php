<?php
namespace App\Traits;

use App\Models\Material;
use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait HelperTrait
{

    protected array $modelMap = [
        'ProductVariant' => ProductVariant::class,
        'Product' => Product::class,
        'Material' => Material::class,
    ];


    public function get_model_by_type($type)
    {
        $modelClass = match ($type) {
            'ProductVariant' => ProductVariant::class, // this should come here for now
            'Product' => Product::class,
            'Material' => Material::class,
            'material' => Material::class,
            "product" => Product::class,
            "variant" => ProductVariant::class,
        };

        return $modelClass;
    }

    public function get_type_by_model($model_type)
    {
        $modelClass = match ($model_type) {
            ProductVariant::class => 'ProductVariant',
            Product::class => 'Product',
            Material::class => 'Material',
            'material' => "Material",
            "product" => 'Product',
            "variant" => 'ProductVariant',
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
                    $item['norm_qty'] = $item['quantity'] ? $item['quantity'] / ($recipe['planned_quantity'] ?? 1) : null;
                    $item[$material_type_name] = $this->get_type_by_model($item[$material_type_name]);
                }
            }

            if (isset($recipe['output_products'])) {
                foreach ($recipe['output_products'] as &$item) {
                    $item['norm_qty'] = $item['qty'] ? $item['qty'] / ($recipe['planned_quantity'] ?? 1) : null;
                    $item[$output_type_name] = $this->get_type_by_model($item[$output_type_name]);
                }
            }
        }
    }

    public function get_true_model_by_type($component_type)
    {
        return match ($component_type) {
            'Product' => Product::query(),
            'ProductVariant' => ProductVariant::query(),
            'Material' => Material::query(),
            default => throw new Exception("Unknown item type: {$component_type}"),
        };
    }
}
