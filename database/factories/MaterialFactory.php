<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition()
    {
        return [
            'title' => $this->faker->word,
            'unit_id' =>  function () {
                $unitIds = Unit::pluck('id')->toArray();

                if (empty($unitIds)) {
                    return Unit::factory()->create()->id;
                }

                return $this->faker->randomElement($unitIds);
            }
        ];
    }
}
