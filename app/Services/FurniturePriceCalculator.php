<?php

namespace App\Services;

class FurniturePriceCalculator
{
    private array $materials = [];
    private int $markup = 0;

    public function setMaterial(string $name, float $price): static
    {
        $this->materials[$name] = $price;
        return $this;
    }

    public function setMarkup(float $markup): static
    {
        $this->markup = $markup;
        return $this;
    }

    public function calculatePrice(array $formula): float|int
    {
        $totalPrice = 0;

        foreach ($formula as $item => $quantity) {
            if (isset($this->materials[$item])) {
                $totalPrice += $this->materials[$item] * $quantity;
            }
        }

        return $totalPrice * (1 + $this->markup);
    }
}
