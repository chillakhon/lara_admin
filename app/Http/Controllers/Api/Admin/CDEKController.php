<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Delivery\CdekDeliveryService;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;

class CDEKController extends Controller
{
    use HelperTrait;

    private CdekDeliveryService $cdek_service;

    public function __construct()
    {
        $this->cdek_service = new CdekDeliveryService();
    }

    public function get_cdek_locations(Request $request)
    {
        $locations = $this->cdek_service->get_offices($request);

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

        $cities = $this->cdek_service->location_cities($request);

        return $cities;

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
        return $this->cdek_service->location_regions($request);
    }

    public function get_tariffs()
    {
        // return 
    }

    public function check_address()
    {
    }
}
