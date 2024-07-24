<?php

namespace App\Services;

class UnitConverter
{
    private array $conversionRates = [
        'length' => [
            'm' => 1,
            'cm' => 0.01,
            'mm' => 0.001,
            'km' => 1000,
            'inch' => 0.0254,
            'ft' => 0.3048,
        ],
        'area' => [
            'm2' => 1,
            'cm2' => 0.0001,
            'km2' => 1000000,
            'sqft' => 0.092903,
        ],
        'volume' => [
            'm3' => 1,
            'l' => 0.001,
            'ml' => 0.000001,
            'gal' => 0.00378541,
        ],
        'mass' => [
            'kg' => 1,
            'g' => 0.001,
            't' => 1000,
            'lb' => 0.453592,
        ],
    ];

    /**
     * @throws \Exception
     */
    public function convert($value, $fromUnit, $toUnit): float|int
    {
        $category = $this->getCategory($fromUnit);
        if ($category !== $this->getCategory($toUnit)) {
            throw new \Exception("Cannot convert between different unit categories");
        }

        $baseValue = $value * $this->conversionRates[$category][$fromUnit];
        return $baseValue / $this->conversionRates[$category][$toUnit];
    }

    /**
     * @throws \Exception
     */
    private function getCategory($unit): string
    {
        foreach ($this->conversionRates as $category => $units) {
            if (array_key_exists($unit, $units)) {
                return $category;
            }
        }
        throw new \Exception("Unknown unit: $unit");
    }
}
