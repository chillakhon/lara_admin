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

        $regions->where('country_id', (int) $request->get('country_id', 0));

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
            ->select('city.*', 'region.name as region_name');

        $cities->where("country.id", $request->get('country_id', 0));

        if ($request->get('name')) {
            $name = $request->get('name');
            $cities->where(function ($query) use ($name) {
                $query->where('city.name', 'like', "%{$name}%")
                    ->orWhere('region.name', 'like', "%{$name}%");
            });
        }

        if ($request->filled('region_id')) {
            $cities->where("region.id", $request->input('region_id'));
        }

        $cities = $cities->get();

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }
}
