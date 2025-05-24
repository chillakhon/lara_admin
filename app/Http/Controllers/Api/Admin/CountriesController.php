<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
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
    }

    public function cities(Request $request)
    {
    }
}
