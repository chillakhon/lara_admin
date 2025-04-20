<?php
namespace App\Traits;

use App\Models\Recipe;

trait RecipeTrait
{
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
}
