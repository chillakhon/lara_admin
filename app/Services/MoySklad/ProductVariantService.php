<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Models\ProductVariant;
use App\Traits\ProductsTrait;
use Evgeek\Moysklad\Api\Record\Objects\UnknownObject;
use Evgeek\Moysklad\MoySklad;
use Exception;
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

        $caracteristics = $moySkladHelperService->get_characteristics();
        $msModification->characteristics = [
            [
                "id" => $caracteristics["Размер"]['id'] ?? null,
                "value" => $productVariant->name,
            ],
            // [
            //     "id" => $caracteristics["Цвет"]['id'] ?? null,
            //     "value" => $productVariant->color,
            // ],
        ];

        $msModification->create();

        Log::info("Modification created in MoySklad", [
            $msModification
        ]);

        return $msModification;
    }


    public function update_modification()
    {
    }
}
