<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MoySklad\MoySkladHelperService;
use App\Services\MoySklad\ProductsService;
use App\Services\MoySklad\ProductVariantService;
use App\Services\MoySklad\ReportService;
use Exception;
use Http;
use Illuminate\Http\Request;

class MoySkladController extends Controller
{
    public function update_moy_sklad_settings(Request $request)
    {
        try {

            $validated = $request->validate([
                'email' => 'required|string',
                'password' => 'required|string',
            ]);

            $username = $validated['email'];
            $password = $validated['password'];

            $response = Http::withHeaders([
                'Accept-Encoding' => 'gzip',
            ])->withBasicAuth($username, $password)
                ->post('https://api.moysklad.ru/api/remap/1.2/security/token');

            if ($response->successful()) {

                $data = $response->json();

                $token = $data['access_token'];

                DeliveryServiceSetting::updateOrCreate([
                    'service_name' => 'moysklad'
                ], [
                    'token' => $token,
                ]);

                $moySkladService = new MoySkladHelperService();

                $moySkladService->create_characteristics();

                $moySkladService->sync_products_with_moysklad();

                return response()->json([
                    'success' => true,
                    'message' => 'Настройки МойСклад успешно обновлены'
                ]);

            }
            return response()->json([
                'success' => false,
                'message' => $response->body(),
            ], $response->getStatusCode());
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getTrace(),
            ]);
        }
    }

    public function sync_products()
    {
        $moySkladService = new MoySkladHelperService();

        $moySkladService->sync_products_with_moysklad();

        return response()->json([
            'success' => true,
            'message' => "Продукты синхронизированы!"
        ]);
    }

    public function get_currencies()
    {
        $moySkladService = new MoySkladHelperService();

        return $moySkladService->get_currencies();
    }

    public function get_price_types()
    {
        $moySkladService = new MoySkladHelperService();

        return $moySkladService->get_price_types();
    }

    public function get_units()
    {
        $moySkladService = new MoySkladHelperService();

        return $moySkladService->get_units();
    }

    public function get_characteristics()
    {
        $moySkladService = new MoySkladHelperService();

        return $moySkladService->get_characteristics();
    }

    public function get_products()
    {
        $moySkladService = new MoySkladHelperService();

        return $moySkladService->get_products();
    }

    public function get_product_variants()
    {
        $moySkladService = new MoySkladHelperService();

        return $moySkladService->get_product_variants();
    }

    public function get_products_stock()
    {
        $moySkladService = new MoySkladHelperService();

        return $moySkladService->check_stock();
    }

    public function create_product(Product $product)
    {
        $moySkladService = new ProductsService();

        return $moySkladService->create_product($product);
    }

    public function create_modification(
        ProductVariant $productVariant,
        \Evgeek\Moysklad\Api\Record\Objects\Entities\Product $product // not project's model, it's from MoySklad package
    ) {
        $moySkladService = new ProductVariantService();

        return $moySkladService->create_modification($productVariant, $product);
    }

    public function update_product(Product $product)
    {
        $moySkladService = new ProductsService();

        return $moySkladService->update_product($product);
    }

    public function update_modification(ProductVariant $productVariant)
    {
        $moySkladService = new ProductVariantService();

        return $moySkladService->update_modification($productVariant);
    }

    public function delete_product($id)
    {
        $moySkladService = new ProductsService();

        return $moySkladService->delete_product($id);
    }

    public function delete_variant($id)
    {
        $moySkladService = new ProductVariantService();

        return $moySkladService->delete_variant($id);
    }

    public function mass_variant_creation_and_update(
        $productVariants,
        \Evgeek\Moysklad\Api\Record\Objects\Entities\Product $product
    ) {
        $modifications = [];
        $moySkladHelperService = new MoySkladHelperService();
        $codeAndIds = [];

        try {
            // Get required MoySklad data
            $currency = $moySkladHelperService->get_currencies();
            $priceTypes = $moySkladHelperService->get_price_types();

            if (empty($priceTypes)) {
                throw new Exception('Не удалось получить типы цен из МойСклад.');
            }

            $priceType = $priceTypes[0];
            $sizeId = $moySkladHelperService->ensureCharacteristic('Размер', 'string');
            $colorId = $moySkladHelperService->ensureCharacteristic('Цвет', 'string');

            foreach ($productVariants as $variant) {
                $existingVariant = ProductVariant::find($variant->id);

                if (!$existingVariant) {
                    Log::warning("Variant not found", ['variant_id' => $variant->id]);
                    continue;
                }

                // Validate required fields
                if (empty($variant->name)) {
                    Log::warning("Variant missing name", ['variant_id' => $variant->id]);
                    continue;
                }

                // Ensure variant has a code
                if (!$existingVariant->code) {
                    $existingVariant->code = (string) rand(1000000000, 9999999999);
                    $existingVariant->save();
                }

                $data = [
                    'name' => $variant->name,
                    'description' => $variant->description ?? '',
                    'salePrices' => [
                        [
                            'value' => max(0, ($variant->price ?? 0) * 100), // Ensure non-negative
                            'currency' => $currency,
                            'priceType' => $priceType,
                        ]
                    ],
                    'buyPrice' => [
                        'value' => max(0, ($variant->cost_price ?? 0) * 100),
                        'currency' => $currency,
                    ],
                    'characteristics' => [
                        [
                            'id' => (string) $sizeId,
                            'value' => $variant->name,
                        ],
                        [
                            'id' => (string) $colorId,
                            'value' => $existingVariant->table_color?->name ?? 'Без цвета',
                        ],
                    ],
                ];

                $codeAndIds[$existingVariant->code] = $existingVariant->uuid;

                if ($existingVariant->uuid) {
                    // Update existing variant
                    $data['meta'] = [
                        'href' => "{$this->baseURL}/entity/variant/{$existingVariant->uuid}",
                        "metadataHref" => "{$this->baseURL}/entity/variant/metadata",
                        'type' => 'variant',
                        'mediaType' => 'application/json',
                    ];
                } else {
                    // Create new variant
                    $data['code'] = $existingVariant->code;
                    $data['product'] = ['meta' => $product->meta];
                }

                $modifications[] = $data;
            }

            if (empty($modifications)) {
                Log::warning("No valid modifications to process");
                return [];
            }

            Log::info("Sending modifications to MoySklad", [
                'count' => count($modifications),
                'sample' => array_slice($modifications, 0, 2) // Log first 2 for debugging
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept-Encoding' => 'gzip',
                'Content-Type' => 'application/json',
            ])->post('https://api.moysklad.ru/api/remap/1.2/entity/variant', $modifications);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error("MoySklad variant creation/update failed", [
                    'status' => $response->status(),
                    'response' => $errorBody,
                    'modifications_sent' => $modifications
                ]);

                // Parse MoySklad error for better debugging
                $decodedError = json_decode($errorBody, true);
                if (isset($decodedError['errors']) && is_array($decodedError['errors'])) {
                    $errorMessages = array_map(function($error) {
                        return $error['error'] ?? 'Unknown error';
                    }, $decodedError['errors']);

                    throw new Exception('MoySklad API errors: ' . implode(', ', $errorMessages));
                }

                throw new Exception("HTTP {$response->status()}: {$errorBody}");
            }

            $responseJson = $response->json();

            if (!$responseJson) {
                throw new Exception('Invalid JSON response from MoySklad');
            }

            // Process response and map codes to IDs
            foreach ($responseJson as $jsonData) {
                if (!isset($jsonData['code']) || !isset($jsonData['id'])) {
                    Log::warning("Invalid response item", ['item' => $jsonData]);
                    continue;
                }

                $code = (string) $jsonData['code'];
                if (array_key_exists($code, $codeAndIds)) {
                    $codeAndIds[$code] = $jsonData['id'];
                }
            }

            Log::info("Successfully processed variants", [
                'processed' => count($responseJson),
                'mapped_codes' => array_keys($codeAndIds)
            ]);

            return $codeAndIds;

        } catch (Exception $e) {
            Log::error("Exception in mass_variant_creation_and_update", [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'modifications_count' => count($modifications ?? [])
            ]);
            throw $e;
        }
    }

    public function mass_variant_deletion(array $ids)
    {
        if (empty($ids)) {
            Log::info("No variants to delete");
            return true;
        }

        $objects = [];
        $validIds = [];

        foreach ($ids as $id) {
            if (!$id || !is_string($id)) {
                Log::warning("Invalid variant ID for deletion", ['id' => $id]);
                continue;
            }

            try {
                // Optional: validate variant exists
                $this->moySklad->query()->entity()->variant()->byId($id)->get();
                $validIds[] = $id;

                $objects[] = [
                    "meta" => [
                        "href" => "{$this->baseURL}/entity/variant/{$id}",
                        "metadataHref" => "{$this->baseURL}/entity/variant/metadata",
                        "type" => "variant",
                        "mediaType" => "application/json"
                    ]
                ];

            } catch (\Exception $e) {
                Log::warning("Variant not found for deletion", ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        if (empty($objects)) {
            Log::warning("No valid variants found for deletion");
            return true;
        }

        Log::info("Deleting variants from MoySklad", [
            'count' => count($objects),
            'ids' => $validIds
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->post("{$this->baseURL}/entity/variant/delete", $objects);

        if (!$response->successful()) {
            $errorBody = $response->body();
            Log::error("Failed to mass delete variants", [
                'status' => $response->status(),
                'response' => $errorBody,
                'payload' => $objects
            ]);

            $decodedBody = json_decode($errorBody, true);
            $message = $decodedBody['errors'][0]['error'] ??
                "Ошибка удаления: невозможно удалить, так как продукт используется в других модулях.";

            throw new \Exception("Ошибка при удалении вариантов товара: " . $message);
        }

        Log::info("Successfully deleted variants from MoySklad", ['count' => count($validIds)]);
        return true;
    }

    public function check_product_for_existence($uuid)
    {
        $productsService = new ProductsService();

        return $productsService->check_product_for_existence($uuid);
    }


    public function report_dashboard(Request $request)
    {
        $reportService = new ReportService();

        return $reportService->report_dashboard($request);
    }
}
