<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Models\ProductVariant;
use App\Traits\ProductsTrait;
use Evgeek\Moysklad\Api\Record\Objects\UnknownObject;
use Evgeek\Moysklad\MoySklad;
use Exception;
use Http;
use Log;

class ProductVariantService
{
    use ProductsTrait;

    private MoySklad $moySklad;
    private $token;
    private $baseURL = "https://api.moysklad.ru/api/remap/1.2";

    public function __construct()
    {

        $moyskadSettings = DeliveryServiceSetting
            ::where('service_name', 'moysklad')
            ->first();

        if (!$moyskadSettings) {
            throw new Exception("Настройки для МойСклад не найдены. Пожалуйста, настройте сервис в админке.");
        }

        $this->token = $moyskadSettings->token;
        $this->moySklad = new MoySklad(["{$moyskadSettings->token}"]);
    }


    public function create_modification(
        ProductVariant $productVariant,
        \Evgeek\Moysklad\Api\Record\Objects\Entities\Product $produt
    ) {
        $moySkladHelperService = new MoySkladHelperService();
        $code = rand(1000000000, 9999999999);

        $msModification = UnknownObject::make($this->moySklad, ['entity', 'variant'], 'variant');

        $msModification->name = $productVariant->name;
        $msModification->code = "{$code}";
        $msModification->description = $productVariant->description ?? '';
        // weight and volume is not necessary for product variants (modifications in MoySklad)

        $msModification->salePrices = [
            [
                'value' => ($productVariant->price ?? 0) * 100, // копейки
                'currency' => $moySkladHelperService->get_currencies(),
                'priceType' => $moySkladHelperService->get_price_types()[0],
            ],
        ];

        $msModification->product = [
            'meta' => $produt->meta,
        ];

        $sizeId = $moySkladHelperService->ensureCharacteristic('Размер', 'string');

        $msModification->characteristics = [
            [
                "id" => "{$sizeId}",
                "value" => $productVariant->name,
            ],
        ];

        $msModification->create();

        Log::info("Modification created in MoySklad", [
            $msModification
        ]);

        return $msModification;
    }


    public function update_modification(ProductVariant $productVariant)
    {
        $moySkladHelperService = new MoySkladHelperService();
        // $code = rand(1000000000, 9999999999);

        $msModification = UnknownObject::make($this->moySklad, ['entity', 'variant'], 'variant');
        $msModification->id = $productVariant->uuid;

        $msModification->name = $productVariant->name;
        // $msModification->code = "{$code}";
        $msModification->description = $productVariant->description ?? '';
        // weight and volume is not necessary for product variants (modifications in MoySklad)

        $msModification->salePrices = [
            [
                'value' => ($productVariant->price ?? 0) * 100, // копейки
                'currency' => $moySkladHelperService->get_currencies(),
                'priceType' => $moySkladHelperService->get_price_types()[0],
            ],
        ];

        // setting product for updating modification is not necessary
        // $msModification->product = [
        //     'meta' => $produt->meta,
        // ];

        $sizeId = $moySkladHelperService->ensureCharacteristic('Размер', 'string');

        $msModification->characteristics = [
            [
                "id" => "{$sizeId}",
                "value" => $productVariant->name,
            ],
        ];

        $msModification->update();

        // Log::info("Modification created in MoySklad", [
        //     $msModification
        // ]);

        return $msModification;
    }

    public function delete_variant($id)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->delete("{$this->baseURL}/entity/variant/{$id}");

        if ($response->successful()) {
            return true;
        }

        return false;
    }


    public function mass_variant_deletion(array $ids)
    {
        $objects = [];

        foreach ($ids as $id) {
            try {
                // Optional: validate variant exists
                $variant = $this->moySklad->query()->entity()->variant()->byId($id)->get();

                $objects[] = UnknownObject::make($this->moySklad, ['id' => $id], 'variant');
                
            } catch (\Exception $e) {
                Log::warning("Skipping unknown variant ID: $id");
            }
        }

        if (!empty($objects)) {
            $this->moySklad->query()->entity()->variant()->massDelete($objects);
        }

        return true;
    }
}
