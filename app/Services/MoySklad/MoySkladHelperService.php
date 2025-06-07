<?php

namespace App\Services\MoySklad;

use App\Models\DeliveryServiceSetting;
use App\Traits\ProductsTrait;
use Evgeek\Moysklad\Api\Record\Objects\UnknownObject;
use Evgeek\Moysklad\MoySklad;
use Exception;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestSize\Unknown;

class MoySkladHelperService
{
    use ProductsTrait;

    private MoySklad $moySklad;
    private $token;
    private $baseURL = "https://api.moysklad.ru/api/remap/1.2";

    public function __construct()
    {

        $moyskadSettings = DeliveryServiceSetting
            ::where('service_name', 'moysklad')
            ->first();

        if (!$moyskadSettings) {
            throw new Exception("Настройки для МойСклад не найдены. Пожалуйста, настройте сервис в админке.");
        }

        $this->token = $moyskadSettings->token;
        $this->moySklad = new MoySklad(["{$moyskadSettings->token}"]);
    }


    public function get_currencies()
    {
        return $this->moySklad->query()->entity()->currency()->get()->rows[0];
    }

    public function get_price_types()
    {
        return $this->moySklad->query()->context()->companysettings()->pricetype()->get();
    }

    public function get_units($search = null)
    {
        $search = "штук";
        $units = $this->moySklad->query()->entity()->uom()->get();
        $rows = collect($units->rows ?? []);

        if (!$search) {
            return $rows;
        }

        $search = mb_strtolower(trim($search));

        return $rows->first(function ($unit) use ($search) {
            return str_contains(mb_strtolower($unit->description ?? ''), $search)
                || str_contains(mb_strtolower($unit->name ?? ''), $search);
        });
    }

    public function check_products()
    {
        return $this->moySklad->query()->entity()->product()->get();
    }

    public function check_stock()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept-Encoding' => 'gzip',
            'Content-Type' => 'application/json',
        ])->get("{$this->baseURL}/report/stock/all/current");

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => $response->body(),
            ], $response->getStatusCode());
        }

        return $response->json();
    }


    public function create_characteristics()
    {
        $objects = [
            ["name" => "Размер", "type" => "string"],
            ["name" => "Цвет", "type" => "string"],
            ["name" => "Test", "type" => "string"]
        ];

        $all_characteristics = $this->get_characteristics();

        foreach ($objects as $key => $value) {
            if (isset($all_characteristics[$value['name']])) {
                continue;
            }
            // url name after every "/" -> https://api.moysklad.ru/api/remap/1.2/entity/variant/metadata/characteristics
            $msCharacteristic = UnknownObject::make($this->moySklad, [
                'entity',
                'variant',
                'metadata',
                'characteristics'
            ], 'characteristic');

            $msCharacteristic->name = $value['name'];
            $msCharacteristic->type = $value['type'];

            $msCharacteristic->create();
        }

        return response()->json([
            'success' => true,
            'message' => 'Характеристики успешно созданы',
        ]);
    }

    public function get_characteristics()
    {
        $characteristics = $this->moySklad->query()
            ->entity()
            ->variant()
            ->metadata()
            ->characteristics()
            ->get();

        $characteristics = $characteristics->characteristics ?? [];

        $result = [];

        foreach ($characteristics as $characteristic) {
            $result[$characteristic->name] = [
                'id' => $characteristic->id,
                'name' => $characteristic->name,
                'type' => $characteristic->type,
                'meta' => $characteristic->meta,
            ];
        }
        
        return $result;
    }
}
