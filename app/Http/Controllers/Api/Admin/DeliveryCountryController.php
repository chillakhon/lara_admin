<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DeliveryMethod;
use App\Models\DeliveryMethodByCountry;
use Illuminate\Http\Request;

class DeliveryCountryController extends Controller
{
    public function assignCountries(Request $request, $delivery_method_id)
    {
        $validated = $request->validate([
            'country_ids' => 'required|array',
            'country_ids.*' => 'integer|exists:country,id',
        ]);

        $deliveryMethod = DeliveryMethod::findOrFail($delivery_method_id);

        DeliveryMethodByCountry
            ::where('delivery_method_id', $deliveryMethod->id)
            ->delete();

        $now = now();
        $insertData = collect($validated['country_ids'])->map(function ($country_id) use ($delivery_method_id, $now) {
            return [
                'country_id' => $country_id,
                'delivery_method_id' => $delivery_method_id,
            ];
        })->toArray();

        DeliveryMethodByCountry::insert($insertData);

        return response()->json([
            'success' => true,
            'message' => 'Список стран для метода доставки успешно добавлены!',
        ]);
    }

    public function getCountries($delivery_method_id)
    {
        $deliveryMethod = DeliveryMethod::findOrFail($delivery_method_id);

        $countryIds = DeliveryMethodByCountry
            ::where('delivery_method_id', $delivery_method_id)
            ->pluck('country_id');

        $countries = Country::whereIn('id', $countryIds)->get(['id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }
}
