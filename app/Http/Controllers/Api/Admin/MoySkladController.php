<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryServiceSetting;
use App\Models\Product;
use App\Services\MoySklad\ProductsService;
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

    public function get_currencies()
    {
        $moySkladService = new ProductsService();

        return $moySkladService->get_currencies();
    }

    public function get_price_types()
    {
        $moySkladService = new ProductsService();

        return $moySkladService->get_price_types();
    }

    public function get_units()
    {
        $moySkladService = new ProductsService();

        return $moySkladService->get_units();
    }

    public function get_products()
    {
        $moySkladService = new ProductsService();

        return $moySkladService->check_products();
    }

    public function get_products_stock()
    {
        $moySkladService = new ProductsService();

        return $moySkladService->check_stock();
    }

    public function sync_products_with_moysklad(Request $request)
    {
    }

    public function create_product(Product $product)
    {
        $moySkladService = new ProductsService();

        return $moySkladService->create_product($product);
    }

    public function update_product(Product $product)
    {
    }
}
