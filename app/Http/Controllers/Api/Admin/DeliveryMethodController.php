<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMethod;
use App\Models\Product;
use App\Services\Delivery\CdekDeliveryService;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryMethodController extends Controller
{

    use HelperTrait;

    public function index(Request $request)
    {

        $request->validate([
            'city_name' => 'required|string',
            'items' => 'required|array',
        ]);

        $cdek_pickup = "cdek_pickup";
        $cdek_courier = "cdek_courier";
        $main_delivery_methods_code = [$cdek_pickup, $cdek_courier];

        $delivery_methods = DeliveryMethod
            ::whereIn('code', $main_delivery_methods_code)
            ->orderBy('id', 'asc')
            ->select(['id', 'name', 'code', 'description', 'provider_class'])
            ->get();


        $cdek = new CdekDeliveryService();

        $cdek_locations = $cdek->get_offices(
            $request->get('country_code'),
            null,
            null,
            $request->get('city_name'),
            false,
            $request->boolean('get_locations_only', false),
        );

        $solved_methods = collect();

        foreach ($delivery_methods as $key => &$method) {
            if ($method->code === $cdek_pickup && count($cdek_locations) >= 1) {
                $method['tariff'] = null;
                $method['locations'] = $cdek_locations;
                $solved_methods[] = $method;
            }

            if ($method->code == $cdek_courier && count($cdek_locations) >= 1) {
                $location = $cdek_locations[0];
                return $this->create_packages($request->get('items'));
                $tariff = $cdek->calculate_with_specific_tariff(
                    $this->get_address_from_location($location, $request->get('country_code')),
                    $this->create_packages($request->get('items'))
                );
                if ($tariff) {
                    $method['tariff'] = $tariff;
                    $solved_methods[] = $method;
                }
            }
        }

        return response()->json([
            'data' => $solved_methods,
            'meta' => [
                'total_methods' => $solved_methods->count(),
            ]
        ]);
    }

    public function create_packages($items = []): array
    {
        $packages = [];
        foreach ($items as $key => $item) {
            $id = null;
            $product_type = null;

            if (!is_null($item['product_variant_id'])) {
                $product_type = $this->get_true_model_by_type("ProductVariant");
                $id = $item['product_variant_id'];
            } else if (!is_null($item['product_id'])) {
                $product_type = $this->get_true_model_by_type("Product");
                $id = $item['product_id'];
            }

            $product = $product_type->where('id', $id)->first();

            if (!$product) {
                continue;
            }

            $packages[] = [
                'weight' => $product->weight,
                'length' => $product->length,
                'width' => $product->width,
                'height' => $product->height,
            ];
        }
        return $packages;
    }

    public function get_address_from_location($location, $country_code = 'RU')
    {
        return [
            'address' => $location['address'],
            'address_full' => $location['full_address'],
            'postal_code' => $location['postal_code'],
            'city' => $location['city'],
            'region' => $location['region'],
            'code' => $location['city_code'],
            'region_code' => $location['region_code'],
            'country_code' => $country_code,
            'longitude' => $location['longitude'],
            'latitude' => $location['latitude'],
        ];
    }


    public function show(DeliveryMethod $method)
    {
        // Загружаем зоны и ставки для метода доставки
        $methodData = $method->load(['zones.rates']);

        // Возвращаем данные в формате JSON
        return response()->json($methodData);
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:delivery_methods',
            'description' => 'nullable|string',
            'provider_class' => 'required|string',
            'settings' => 'required|array',
            'is_active' => 'boolean'
        ]);

        // Создаем новый метод доставки
        $deliveryMethod = DeliveryMethod::create($validated);

        // Возвращаем успешный JSON-ответ
        return response()->json([
            'message' => 'Метод доставки создан',
            'data' => $deliveryMethod
        ], 201); // Статус 201 для успешного создания ресурса
    }


    public function update(Request $request, DeliveryMethod $method)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_class' => 'required|string',
            'settings' => 'required|array',
            'is_active' => 'boolean'
        ]);

        $method->update($validated);

        return response()->json([
            'message' => 'Метод доставки обновлен',
            'data' => $method
        ]);
    }

    public function destroy(DeliveryMethod $method)
    {
        $method->delete();

        return response()->json([
            'message' => 'Метод доставки удален'
        ]);
    }

}
