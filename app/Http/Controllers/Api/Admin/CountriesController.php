<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;

class CountriesController extends Controller
{
    public function countries(Request $request)
    {
        $countries = Country::query();

        if ($request->get('name')) {
            $countries->where("name", "like", "%{$request->get('name')}%");
        }

        $countries = $countries
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'countries' => $countries,
        ]);
    }

    public function regions(Request $request)
    {
        $regions = Region::query();

        if ($request->get('name')) {
            $regions->where("name", "like", "%{$request->get('name')}%");
        }

        if ($request->filled('country_id')) {
            $regions->where('country_id', (int) $request->input('country_id'));
        }

        $regions = $regions
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'regions' => $regions,
        ]);
    }

    public function cities(Request $request)
    {
        $cities = City
            ::leftJoin('region', 'city.region_id', 'region.id')
            ->leftJoin('country', 'region.country_id', 'country.id')
            ->select('city.*');

        $cities->where("country.id", $request->get('country_id', 0));

        if ($request->get('name')) {
            $cities->where("city.name", "like", "%{$request->get('name')}%");
        }

        if ($request->filled('region_id')) {
            $cities->where("region.id", $request->get('region_id'));
        }

        $cities = $cities->get();

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }
}
