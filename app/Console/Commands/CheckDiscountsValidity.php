<?php

namespace App\Console\Commands;

use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDiscountsValidity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discounts:check-validity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivates expired discounts and activates currently valid ones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $expired = Discount::where('is_active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->update(['is_active' => false]);

        $activated = Discount::where('is_active', false)
            ->where(function ($query) use ($now) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $now)
            ->update(['is_active' => true]);
    }
}
