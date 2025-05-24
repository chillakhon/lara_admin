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
    }

 
}
