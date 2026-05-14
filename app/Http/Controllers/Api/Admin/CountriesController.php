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

    public function searchRegions(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'country_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $regions = Region::query()
            ->select(['id', 'name', 'country_id']);

        if (!empty($validated['query'])) {
            $regions->where('name', 'like', '%' . $validated['query'] . '%');
        }

        if (!empty($validated['country_id'])) {
            $regions->where('country_id', $validated['country_id']);
        }

        $regions = $regions
            ->orderBy('name', 'asc')
            ->limit($validated['limit'] ?? 20)
            ->get();

        return response()->json([
            'success' => true,
            'regions' => $regions,
        ]);
    }

    public function searchCities(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'country_id' => 'nullable|integer',
            'region_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $cities = City::query()
            ->leftJoin('region', 'city.region_id', '=', 'region.id')
            ->select([
                'city.id',
                'city.name',
                'city.region_id',
                'region.country_id',
                'region.name as region_name',
            ]);

        if (!empty($validated['query'])) {
            $cities->where('city.name', 'like', '%' . $validated['query'] . '%');
        }

        if (!empty($validated['country_id'])) {
            $cities->where('region.country_id', $validated['country_id']);
        }

        if (!empty($validated['region_id'])) {
            $cities->where('city.region_id', $validated['region_id']);
        }

        $cities = $cities
            ->orderBy('city.name', 'asc')
            ->limit($validated['limit'] ?? 20)
            ->get();

        return response()->json([
            'success' => true,
            'cities' => $cities,
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


        $cities->orderBy('city.name', 'asc');

        $cities = $cities->get();

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }
}
