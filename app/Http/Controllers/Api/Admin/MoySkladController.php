<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MoySklad\MoySkladHelperService;
use App\Services\MoySklad\ProductsService;
use App\Services\MoySklad\ProductVariantService;
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
        array $productVariants,
        \Evgeek\Moysklad\Api\Record\Objects\Entities\Product $product
    ) {
        $moySkladService = new ProductVariantService();

        return $moySkladService->mass_variant_creation_and_update($productVariants, $product);
    }

    public function mass_variant_deletion(array $ids)
    {
        $moySkladService = new ProductVariantService();

        return $moySkladService->mass_variant_deletion($ids);
    }
}
