<?php

namespace App\Services;

use App\Models\RawMaterial;

class MaterialPriceCalculator
{
    private $unitConverter;
    private $formulaParser;

    public function __construct(UnitConverter $unitConverter, FormulaParser $formulaParser)
    {
        $this->unitConverter = $unitConverter;
        $this->formulaParser = $formulaParser;
    }

    public function calculate(string $formula, string $fromUnit, string $toUnit)
    {
        $rawMaterials = RawMaterial::all()->keyBy('name');

        // Replace raw material names with their prices
        foreach ($rawMaterials as $name => $rawMaterial) {
            $formula = str_replace($name, $rawMaterial->price, $formula);
        }

        // Parse and calculate the formula
        $result = $this->formulaParser->parse($formula);

        // Convert the result to the desired unit
        return $this->unitConverter->convert($result, $fromUnit, $toUnit);
    }
}
