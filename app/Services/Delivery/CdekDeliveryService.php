<?php

namespace App\Services\Delivery;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShipmentStatus;
// use CdekSDK\CdekClient;
use Carbon\Carbon;
use CdekSDK2\Actions\LocationCities;
use CdekSDK2\Actions\LocationRegions;
use CdekSDK2\Actions\Offices;
use CdekSDK2\Constraints\Currencies;
use CdekSDK2\Dto\City;
use CdekSDK2\Dto\CityList;
use CdekSDK2\Dto\PickupPointList;
use CdekSDK2\Dto\RegionList;
use CdekSDK2\Dto\TariffList;
use CdekSDK2\Dto\TariffListItem;
use CdekSDK2\Exceptions\AuthException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use CdekSDK2\Client as SdekClient;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Cache;

class CdekDeliveryService extends DeliveryService
{
    // private HttpClient $client;

    private SdekClient $cdek;

    public function __construct()
    {
        $client = new HttpClient();

        $this->cdek = new SdekClient($client);
        $this->cdek->setAccount('wqGwiQx0gg8mLtiEKsUinjVSICCjtTEP'); // put real account (using for tests right now)
        $this->cdek->setSecure('RmAmgvSgSl1yirlz9QupbzOJVqhCxcP5');//  put real secure (using for tests right now)
        $this->cdek->setTest(true);


        // from docs: https://github.com/cdek-it/sdk2.0/wiki#%D0%B0%D0%B2%D1%82%D0%BE%D1%80%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F
        $this->init_cdek_token();
    }

    public function calculateRate(Order $order): Collection
    {
        // Реализация расчета стоимости через API СДЭК
        return collect([
            'price' => 0,
            'estimated_days' => 0
        ]);
    }

    public function createShipment(Order $order): Shipment
    {
        // Создание отправления в СДЭК
        // return Shipment::create([
        //     'order_id' => $order->id,
        //     'delivery_method_id' => $order->delivery_method_id,
        //     'status_id' => ShipmentStatus::where('code', ShipmentStatus::NEW)->first()->id,
        //     // ... остальные поля
        // ]);
        try {



        } catch (\CdekSDK2\Exceptions\RequestException $exception) {
            $exception->getMessage();
        }
    }

    public function getTrackingInfo(string $trackingNumber): array
    {
        // Получение информации о статусе доставки
        return [];
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        // Отмена отправления
        return true;
    }

    public function printLabel(Shipment $shipment): string
    {
        // Получение PDF с накладной
        return '';
    }

    public function get_offices(Request $request)
    {
        $filter = [
            'country_code' => $request->get('country_code', 'ru'),
            'city_code' => $request->get('city_code'),
            'region_code' => $request->get('region_code'),
        ];
        // city_name should not be empty but city_code should be empty
        // and region code should be empty
        if ($request->get('city_name') && !$request->get('city_code') && !$request->get('region_code')) {
            $city = $this->searhc_for_city_code($request->get('city_name'));
            if ($city) {
                $filter['city_code'] = $city->code;
            } else {
                return [];
            }
        }

        $result = $this->cdek->offices()->getFiltered($filter);

        if (!$result->isOk()) {
            return [];
        }

        $pick_up_point_list = $this->cdek->formatResponseList($result, PickupPointList::class);

        $pick_up_point_offices = $pick_up_point_list->items;

        $offices = [];

        foreach ($pick_up_point_offices as $point) {
            $offices[] = [
                'code' => $point->code,
                'name' => $point->name,
                'type' => $point->type,
                'owner_code' => $point->owner_code,
                'address' => $point->location->address,
                'full_address' => $point->location->address_full,
                'city' => $point->location->city,
                'postal_code' => $point->location->postal_code,
                'region' => $point->location->region,
                'longitude' => $point->location->longitude,
                'latitude' => $point->location->latitude,
                'work_time' => $point->work_time,
                'address_comment' => $point->address_comment,
                'note' => $point->note,
                'is_dressing_room' => $point->is_dressing_room,
                'have_cash' => $point->have_cash,
                'have_cashless' => $point->have_cashless,
                'allowed_cod' => $point->allowed_cod,
                'nearest_station' => $point->nearest_station,
                'nearest_metro_station' => $point->nearest_metro_station,
                'email' => $point->email,
                'phone' => $point->phones[0]->number ?? null,
                'images' => array_map(fn($img) => $img->url, $point->office_image_list ?? []),
                'work_time_list' => array_map(fn($time) => [
                    'day' => $time->day,
                    'time' => $time->time,
                ], $point->work_time_list ?? []),
            ];
        }

        return $offices;
    }

    public function searhc_for_city_code($city_name): City|null
    {
        $city_result = $this->cdek->cities()->getFiltered([
            'city' => $city_name,
        ]);

        if (!$city_result->isOk()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при поиске города',
            ], 500);
        }

        $cities = $this->cdek->formatResponseList($city_result, CityList::class);
        return $cities->items[0] ?? null;
    }

    public function location_cities(Request $request)
    {
        $result = $this->cdek->cities()->getFiltered([
            'country_codes' => $request->get('country_code', 'ru'),
            'city' => $request->get('city'),
            'code' => $request->get('code'), // city code
            'region_code' => $request->get('region_code'),
        ]);

        LocationCities::FILTER;

        if (!$result->isOk()) {
            return [];
        }

        $cities = $this->cdek->formatResponseList($result, CityList::class);

        return $cities->items;
    }

    public function location_regions(Request $request)
    {
        $result = $this->cdek->regions()->getFiltered([
            'country_codes' => $request->get('country_code', 'ru'),
        ]);

        // LocationRegions::FILTER;

        if (!$result->isOk()) {
            return [];
        }

        //Запрос успешно выполнился
        $regions = $this->cdek->formatResponseList($result, RegionList::class);

        return $regions->items;
    }

    public function cdek_tariffs()
    {
        $tariffs = \CdekSDK2\BaseTypes\Tarifflist::TYPE_DELIVERY;
    }


    private function init_cdek_token()
    {
        $cdek_token = 'cdek_token';
        $cached = Cache::get($cdek_token);

        if ($cached && Carbon::parse($cached['expire'])->isFuture()) {
            $this->cdek->setToken($cached['token']);
        } else {
            try {
                $this->cdek->authorize();
                Cache::put($cdek_token, [
                    'token' => $this->cdek->getToken(),
                    'expire' => Carbon::createFromTimestamp($this->cdek->getExpire()),
                ], $this->cdek->getExpire() - time());
            } catch (AuthException $e) {
                throw new \Exception("Ошибка авторизации в СДЭК: " . $e->getMessage());
            }
        }
    }
}