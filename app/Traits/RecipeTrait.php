<?php
namespace App\Traits;

use App\Models\Recipe;

trait RecipeTrait
{

    use HelperTrait;
    public function solve_category_cost(Recipe $recipe): Recipe
    {
        $cost_rates = $recipe->costRates;

        if (count($cost_rates) >= 1) {
            $modifies_cost_rates = [];

            $output_qty = $recipe->output_quantity ?? 0;

            foreach ($cost_rates as $key => $cost_rate) {
                $rate_per_unit_total = $output_qty * ($cost_rate->rate_per_unit ?? 0);
                $fixed_rate_total = $output_qty * ($cost_rate->fixed_rate ?? 0);
                $modifies_cost_rates[] = [
                    'category_name' => $cost_rate?->category?->name ?? '',
                    'rate_per_unit_total' => $rate_per_unit_total,
                    'fixed_rate_total' => $fixed_rate_total,
                    'total' => $rate_per_unit_total + $fixed_rate_total,
                ];
            }

            $recipe->unsetRelation('costRates');
            $recipe->cost_rates = $modifies_cost_rates;
        }

        return $recipe;
    }


    public function get_parent_product_of_component(Recipe $recipe): Recipe
    {
        foreach ($recipe->material_items as $materialItem) {
            $get_component_type = $this->get_type_by_model($materialItem->component_type);

            if ($get_component_type == PRODUCT_VARIANT) {
                $product = \App\Models\Product::find($materialItem->component->product_id);
                if ($product) {
                    $materialItem->parent_product_id = $product->id;
                    $materialItem->parent_product_name = $product->name;
                }
            }
        }

        foreach ($recipe->output_products as $outputProduct) {
            $get_component_type = $this->get_type_by_model($outputProduct->component_type);

            if ($get_component_type == PRODUCT_VARIANT) {
                $product = \App\Models\Product::find($outputProduct->component->product_id);
                if ($product) {
                    $outputProduct->parent_product_id = $product->id;
                    $outputProduct->parent_product_name = $product->name;
                }
            }
        }

        return $recipe;
    }
}
