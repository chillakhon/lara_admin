<?php

namespace App\Console\Commands;

use App\Models\DeliveryServiceSetting;
use App\Models\Unit;
use DB;
use Evgeek\Moysklad\MoySklad;
use Exception;
use Illuminate\Console\Command;
use Log;

class FixUnitsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-units-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for fixing units: Getting units which are necessary';

    /**
     * Execute the console command.
     */

    private MoySklad $moySklad;

    public function handle()
    {

        $this->authorizied();

        // I DO NOT KNOW WHY, BUT TRANSACTIONS ARE NOT WORKING!!!!!
        try {
            $msUnits = $this->moySklad->query()->entity()->uom()->get();

            Unit::where('abbreviation', 'м³')->update(['abbreviation' => 'м3']);
            Unit::where('abbreviation', 'см³')->update(['abbreviation' => 'см3']);
            Unit::where('abbreviation', 'м²')->update(['abbreviation' => 'м2']);
            Unit::where('abbreviation', 'см²')->update(['abbreviation' => 'см2']);
            Unit::where('abbreviation', 'мм²')->update(['abbreviation' => 'мм2']);

            $localUnits = Unit::whereIn('abbreviation', [
                'шт',
                'г',
                'кг',
                'см',
                'м',
                'см2',
                'м2',
                'л',
                'мг',
                'мл',
                'мин'
            ])->get()->keyBy(fn($unit) => mb_strtolower($unit->name));

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Unit::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            foreach ($msUnits->rows as $key => $value) {
                $msNames = array_map('trim', explode(';', mb_strtolower($value->description)));

                foreach ($msNames as $abbr) {
                    if (isset($localUnits[$abbr])) {
                        $unit = $localUnits[$abbr];
                        Unit::create([
                            'name' => $unit->name,
                            "abbreviation" => $unit->abbreviation,
                            "meta_data" => json_encode($value->meta),
                            "description" => $value->description,
                        ]);
                        break;
                    }
                }
            }


            $this->info("Successfully changed!");

        } catch (Exception $e) {
            Log::error("Error:", [$e]);
        }
    }


    private function authorizied()
    {
        $moyskadSettings = DeliveryServiceSetting
            ::where('service_name', 'moysklad')
            ->first();

        if (!$moyskadSettings) {
            throw new Exception("Настройки для МойСклад не найдены. Пожалуйста, настройте сервис в админке.");
        }

        $this->moySklad = new MoySklad(["{$moyskadSettings->token}"]);
    }
}
