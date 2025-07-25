<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Models\ProductVariant;
use App\Traits\ProductsTrait;
use Evgeek\Moysklad\Api\Record\Objects\Entities\Product;
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

        $msModification->buyPrice = [
            'value' => ($productVariant->cost_price ?? 0) * 100, // копейки
        ];

        $msModification->product = [
            'meta' => $produt->meta,
        ];

        $sizeId = $moySkladHelperService->ensureCharacteristic('Размер', 'string');
        $colorId = $moySkladHelperService->ensureCharacteristic('Цвет', 'string');

        $msModification->characteristics = [
            [
                "id" => "{$sizeId}",
                "value" => $productVariant->name,
            ],
            [
                "id" => "{$colorId}",
                "value" => $productVariant->table_color?->name ?? '',
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

        $msModification->buyPrice = [
            'value' => ($productVariant->cost_price ?? 0) * 100, // копейки
        ];

        // setting product for updating modification is not necessary
        // $msModification->product = [
        //     'meta' => $produt->meta,
        // ];

        $sizeId = $moySkladHelperService->ensureCharacteristic('Размер', 'string');
        $colorId = $moySkladHelperService->ensureCharacteristic('Цвет', 'string');

        $msModification->characteristics = [
            [
                "id" => "{$sizeId}",
                "value" => $productVariant->name,
            ],
            [
                "id" => "{$colorId}",
                "value" => $productVariant->table_color?->name ?? '',
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

    public function mass_variant_creation_and_update(
        $productVariants,
        \Evgeek\Moysklad\Api\Record\Objects\Entities\Product $product
    ) {
        $modifications = [];
        $moySkladHelperService = new MoySkladHelperService();

        $codeAndIds = [];

        $currency = $moySkladHelperService->get_currencies();
        $priceType = $moySkladHelperService->get_price_types()[0];
        $sizeId = $moySkladHelperService->ensureCharacteristic('Размер', 'string');
        $colorId = $moySkladHelperService->ensureCharacteristic('Цвет', 'string');

        foreach ($productVariants as $key => $variant) {
            $existingVariant = ProductVariant::find($variant->id);

            $data = [
                'name' => $variant->name,
                'description' => $variant->description ?? '',
                'salePrices' => [
                    [
                        'value' => ($variant->price ?? 0) * 100,
                        'currency' => $currency,
                        'priceType' => $priceType,
                    ]
                ],
                'buyPrice' => [
                    'value' => ($variant->cost_price ?? 0) * 100, // копейки
                ],
                'characteristics' => [
                    [
                        'id' => (string) $sizeId,
                        'value' => $variant->name,
                    ],
                    [
                        'id' => (string) $colorId,
                        'value' => $existingVariant->table_color?->name ?? '',
                    ],
                ],
            ];

            $codeAndIds[$existingVariant->code] = $existingVariant?->uuid;

            if ($existingVariant?->uuid) {
                $data['meta'] = [
                    'href' => "{$this->baseURL}/entity/variant/{$existingVariant->uuid}",
                    "metadataHref" => "{$this->baseURL}/entity/variant/metadata",
                    'type' => 'variant',
                    'mediaType' => 'application/json',
                ];
            } else {
                if (!$existingVariant->code) {
                    $existingVariant->code = (string) rand(1000000000, 9999999999);
                    $existingVariant->save();
                }
                $data['code'] = $existingVariant->code;
                $data['product'] = ['meta' => $product->meta];
            }

            $modifications[] = $data;
        }
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->post('https://api.moysklad.ru/api/remap/1.2/entity/variant', $modifications);

        if (!$response->successful()) {
            Log::info("error from creation of variants", [$modifications]);
            throw new Exception($response->body());
        }

        $coming_json = $response->json();

        if ($coming_json) {
            foreach ($coming_json as $key => $jsonData) {
                $code = (string) $jsonData['code'];
                if (array_key_exists($code, $codeAndIds)) {
                    $codeAndIds[$code] = $jsonData['id'];
                }
            }
        }

        Log::info("data", [$codeAndIds]);

        return $codeAndIds;
    }

    public function mass_variant_deletion(array $ids)
    {
        $objects = [];

        foreach ($ids as $id) {
            try {
                // Optional: validate variant exists
                $variant = $this->moySklad->query()->entity()->variant()->byId($id)->get();

                $objects[] = [
                    "meta" => [
                        "href" => "{$this->baseURL}/entity/variant/{$id}",
                        "metadataHref" => "{$this->baseURL}/entity/variant/metadata",
                        "type" => "variant",
                        "mediaType" => "application/json"
                    ]
                ];

            } catch (\Exception $e) {
                Log::warning("Skipping unknown variant ID: $id", [$e]);
            }
        }

        Log::info("deleting only:", $objects);

        if (!empty($objects)) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept-Encoding' => 'gzip',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseURL}/entity/variant/delete", $objects);

            if (!$response->successful()) {
                Log::error("Failed to mass delete variants", [
                    'response' => $response->body(),
                    'payload' => $objects
                ]);
                $decodedBody = json_decode($response->body());
                $message = $decodedBody?->errors[0]->error ?? "Ошибка удаления: невозможно удалить, так как продукт используется в других модулях.";
                throw new \Exception("Ошибка при удалении вариантов товара: " . $message);
            }
        }

        return true;
    }
}
