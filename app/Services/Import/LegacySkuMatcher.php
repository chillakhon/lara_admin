<?php

namespace App\Services\Import;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Сопоставление "наследственных" SKU из InSales-выгрузки с
 * текущим каталогом (products/product_variants).
 *
 * Формат legacy SKU:
 *   {prefix}-{цвет}-{размер}    — самый частый, e.g. `again-love-черный-m`
 *   {prefix}-{размер}            — для сетов/типов без цвета, e.g. `again-loveset-m`, `again-gel-100`
 *   {prefix}                     — без вариаций, e.g. `again-loveset`
 *
 * Особенности каталога:
 *   - `products.code` иногда хранится в обратном порядке: `body-again` против CSV `again-body-...`
 *   - `slug` тоже может содержать тот же ключ
 *   - У большинства `product_variants` поле `sku` пустое — поэтому вариант ищем по
 *     `name` (как правило что-то вроде "Чёрный M" или "M / Чёрный").
 */
class LegacySkuMatcher
{
    /** Известные «технические» суффиксы размеров. */
    protected const SIZE_TOKENS = [
        'xxs', 'xs', 's', 'm', 'l', 'xl', 'xxl', 'xxxl', 'xxxxl', 'xxxxxl',
    ];

    /** Известные «технические» суффиксы цветов (нормализованы). */
    protected const COLOR_TOKENS = [
        'черный' => 'чёрный',
        'чёрный' => 'чёрный',
        'белый' => 'белый',
        'бежевый' => 'бежевый',
        'красный' => 'красный',
        'фиолет' => 'фиолетовый',
        'фиолетовый' => 'фиолетовый',
        'фуксия' => 'фуксия',
        'графитовый' => 'графитовый',
        'розоваяпудра' => 'розовая пудра',
        'розовая пудра' => 'розовая пудра',
    ];

    /** @var array<string,int> code/slug => product_id */
    protected array $productByCode = [];

    /** @var array<int, array<int, array{id:int,name:string,sku:?string}>> product_id => variants */
    protected array $variantsByProduct = [];

    protected bool $loaded = false;

    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        Product::query()
            ->select(['id', 'code', 'slug', 'name'])
            ->chunk(500, function ($chunk) {
                foreach ($chunk as $p) {
                    foreach ([(string) $p->code, (string) $p->slug] as $key) {
                        $key = mb_strtolower(trim($key));
                        if ($key !== '' && !isset($this->productByCode[$key])) {
                            $this->productByCode[$key] = $p->id;
                        }
                    }
                }
            });

        ProductVariant::query()
            ->select(['id', 'product_id', 'sku', 'name'])
            ->chunk(2000, function ($chunk) {
                foreach ($chunk as $v) {
                    $this->variantsByProduct[$v->product_id][] = [
                        'id' => $v->id,
                        'name' => (string) $v->name,
                        'sku' => $v->sku,
                    ];
                }
            });

        $this->loaded = true;
    }

    /**
     * Подбирает (product_id, variant_id) по legacy SKU.
     *
     * @return array{product_id:?int, variant_id:?int, matched_by:?string}
     */
    public function match(string $legacySku): array
    {
        $this->load();

        $sku = mb_strtolower(trim($legacySku));
        if ($sku === '') {
            return ['product_id' => null, 'variant_id' => null, 'matched_by' => null];
        }

        // 1) Точное совпадение с code/slug продукта.
        if (isset($this->productByCode[$sku])) {
            $productId = $this->productByCode[$sku];
            return [
                'product_id' => $productId,
                'variant_id' => null,
                'matched_by' => 'exact_code',
            ];
        }

        // 2) Снимаем «технические» хвосты: размер и/или цвет.
        $segments = explode('-', $sku);
        [$prefix, $size, $color] = $this->splitTail($segments);

        if ($prefix === '') {
            return ['product_id' => null, 'variant_id' => null, 'matched_by' => null];
        }

        $productId = $this->findProductByPrefix($prefix);
        if ($productId === null) {
            return ['product_id' => null, 'variant_id' => null, 'matched_by' => null];
        }

        $variantId = $this->findVariant($productId, $size, $color);

        return [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'matched_by' => $variantId ? 'prefix+variant' : 'prefix',
        ];
    }

    /**
     * Отделяет от хвоста размер и/или цвет.
     *
     * @param  array<int,string>  $segments
     * @return array{0:string, 1:?string, 2:?string}  prefix, size, color
     */
    protected function splitTail(array $segments): array
    {
        $size = null;
        $color = null;

        // Размер всегда последний, если он совпадает с известным токеном.
        $last = end($segments);
        if ($last !== false && $this->isSizeToken((string) $last)) {
            $size = mb_strtoupper((string) $last);
            array_pop($segments);
        } elseif ($last !== false && preg_match('/^\d{2,4}$/', (string) $last)) {
            // числовой суффикс (e.g. `again-gel-100`) считаем размером/объёмом
            $size = (string) $last;
            array_pop($segments);
        }

        // Цвет идёт перед размером и тоже может быть в виде «двух слов через пробел»,
        // которые в SKU превратились в единый сегмент или сегмент с пробелом.
        if ($segments) {
            $candidate = mb_strtolower((string) end($segments));
            if (isset(self::COLOR_TOKENS[$candidate])) {
                $color = self::COLOR_TOKENS[$candidate];
                array_pop($segments);
            }
        }

        $prefix = implode('-', $segments);

        return [$prefix, $size, $color];
    }

    protected function isSizeToken(string $value): bool
    {
        return in_array(mb_strtolower($value), self::SIZE_TOKENS, true);
    }

    protected function findProductByPrefix(string $prefix): ?int
    {
        $candidates = [$prefix];

        // Перестановка двух частей для случаев `again-body` ↔ `body-again`.
        $parts = explode('-', $prefix);
        if (count($parts) === 2) {
            $candidates[] = $parts[1] . '-' . $parts[0];
        }

        // Если префикс включает «teens» — иногда product code хранится без дефиса,
        // например, для `again-loveteens` (как одно слово) или `body-againteens`.
        // Если в нашем префиксе есть `teens`, попробуем оба варианта.
        foreach ($candidates as $c) {
            if (isset($this->productByCode[$c])) {
                return $this->productByCode[$c];
            }
        }

        return null;
    }

    /**
     * Подбирает variant у product по размеру и/или цвету (по name).
     */
    protected function findVariant(int $productId, ?string $size, ?string $color): ?int
    {
        if (empty($this->variantsByProduct[$productId])) {
            return null;
        }
        if ($size === null && $color === null) {
            return null;
        }

        $size = $size !== null ? mb_strtolower($size) : null;
        $color = $color !== null ? mb_strtolower($color) : null;

        // Сначала пробуем найти точное совпадение по size + color (в любом порядке).
        $bestId = null;
        $bestScore = 0;

        foreach ($this->variantsByProduct[$productId] as $v) {
            $name = mb_strtolower($v['name']);
            $score = 0;

            if ($size !== null && $this->variantNameMatchesSize($name, $size)) {
                $score += 2;
            }
            if ($color !== null && str_contains($name, $color)) {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $v['id'];
            }
        }

        // Минимум: должен заматчиться хотя бы один из критериев.
        return $bestScore > 0 ? $bestId : null;
    }

    /**
     * Размер в имени варианта может быть как самостоятельное слово
     * («M», «M / Чёрный», «Чёрный M», «размер M»), а не как substring
     * («XSM» содержит «M», но это не размер).
     */
    protected function variantNameMatchesSize(string $name, string $size): bool
    {
        // Границы слова: пробел, скобка, слэш, начало/конец строки.
        $pattern = '/(^|[^a-zа-я0-9])' . preg_quote($size, '/') . '($|[^a-zа-я0-9])/u';
        return (bool) preg_match($pattern, $name);
    }
}
