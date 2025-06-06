<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Models\ProductVariant;
use App\Traits\ProductsTrait;
use Evgeek\Moysklad\Api\Record\Objects\UnknownObject;
use Evgeek\Moysklad\MoySklad;
use Exception;

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
        $code = rand(1000000000, 9999999999);

        $msModification = UnknownObject::make($this->moySklad, ['entity', 'variant'], 'variant');

        $msModification->name = $productVariant->name;
        $msModification->code = "{$code}";
        $msModification->description = $productVariant->description ?? '';
        // weight and volume is not necessary for product variants (modifications in MoySklad)

        $msModification->salePrices = [
            [
                'value' => ($productVariant->price ?? 0) * 100, // копейки
                'currency' => $this->get_currencies(),
                'priceType' => $this->get_price_types()[0],
            ],
        ];

        $msModification->product = [
            'meta' => $produt->meta,
        ];

        $msModification->create();

        return $msModification;
    }


    public function update_modification()
    {
    }
}
