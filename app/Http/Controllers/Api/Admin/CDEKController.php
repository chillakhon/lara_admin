<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryServiceSetting;
use App\Services\Delivery\CdekDeliveryService;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;

class CDEKController extends Controller
{
    use HelperTrait;


    public function get_cdek_locations(Request $request)
    {
        $cdek_service = new CdekDeliveryService();

        $locations = $cdek_service->get_offices(
            $request->get('country_code', 'ru'),
            $request->get('city_code'),
            $request->get('region_code'),
            $request->get('city_name'),
            true,
            $request->boolean('get_locations_only', false)
        );

        if ($request->get('per_page')) {
            $paginated = $this->paginate_collection($locations, $request);
            return response()->json([
                'cdek_offices' => $paginated->items(),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage(),
                ],
            ]);
        } else {
            return response()->json([
                'cdek_offices' => $locations
            ]);
        }
    }

    public function get_cdek_cities(Request $request)
    {

        $request->validate([
            'city' => 'nullable|string',
            'country_code' => 'nullable|string',
            'region_code' => 'nullable|string',
            'code' => 'nullable|string',
        ]);

        $cdek_service = new CdekDeliveryService();

        $cities = $cdek_service->location_cities($request);

        $paginated = $this->paginate_collection($cities, $request);

        return response()->json([
            'cities' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }


    public function get_cdek_regions(Request $request)
    {
        $cdek_service = new CdekDeliveryService();
        return $cdek_service->location_regions($request);
    }

    public function get_tariffs()
    {
        // return 
    }

    public function check_address()
    {
    }

    public function update_cdek_settings(Request $request)
    {
        $request->validate([
            'acount' => 'required|string',
            'secure_password' => 'required|string',
        ]);

        $cdek_settings = DeliveryServiceSetting::where('service_name', 'cdek')->first();

        if (!$cdek_settings) {
            $cdek_settings = DeliveryServiceSetting::create([
                'service_name' => 'cdek',
            ]);
        }


        $cdek_settings->token = $request->get('acount');
        $cdek_settings->secret = $request->get('secure_password');
        $cdek_settings->call_courier_to_the_office = $request->boolean('call_courier_to_the_office', false);
        $cdek_settings->save();

        return response()->json([
            'message' => 'Настройки CDEK успешно обновлены',
            'settings' => $cdek_settings
        ]);
    }
}
