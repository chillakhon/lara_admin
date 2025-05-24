<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
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

        if (!is_null($request->integer('country_id'))) {
            $regions->where("country_id", $request->integer('country_id'));
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
    }
}
