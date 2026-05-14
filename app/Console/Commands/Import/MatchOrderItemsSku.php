<?php

namespace App\Console\Commands\Import;

use App\Services\Import\LegacySkuMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MatchOrderItemsSku extends Command
{
    protected $signature = 'import:match-skus
        {--apply : Применить изменения (по умолчанию — только показать покрытие)}
        {--show-unmatched=20 : Сколько unmatched SKU вывести}';

    protected $description = 'Сопоставить legacy_sku в order_items с текущим каталогом (products/product_variants).';

    public function handle(LegacySkuMatcher $matcher): int
    {
        $apply = (bool) $this->option('apply');
        $showUnmatched = (int) $this->option('show-unmatched');

        $rows = DB::table('order_items')
            ->whereNotNull('legacy_sku')
            ->selectRaw('legacy_sku, COUNT(*) AS cnt, MIN(legacy_name) AS legacy_name')
            ->groupBy('legacy_sku')
            ->orderByDesc('cnt')
            ->get();

        $summary = [
            'unique_skus' => 0,
            'total_items' => 0,
            'matched_unique' => 0,
            'matched_with_variant_unique' => 0,
            'matched_items' => 0,
            'matched_with_variant_items' => 0,
            'unmatched_unique' => 0,
            'unmatched_items' => 0,
        ];

        $updates = [];
        $unmatched = [];

        foreach ($rows as $r) {
            $summary['unique_skus']++;
            $summary['total_items'] += $r->cnt;

            $m = $matcher->match($r->legacy_sku);
            if ($m['product_id']) {
                $summary['matched_unique']++;
                $summary['matched_items'] += $r->cnt;
                if ($m['variant_id']) {
                    $summary['matched_with_variant_unique']++;
                    $summary['matched_with_variant_items'] += $r->cnt;
                }
                $updates[$r->legacy_sku] = [$m['product_id'], $m['variant_id']];
            } else {
                $summary['unmatched_unique']++;
                $summary['unmatched_items'] += $r->cnt;
                $unmatched[] = ['sku' => $r->legacy_sku, 'name' => $r->legacy_name, 'cnt' => $r->cnt];
            }
        }

        $this->info($apply ? 'Применяю обновления…' : 'Dry-run, изменений не делаю.');
        $updatedRows = 0;
        if ($apply) {
            foreach ($updates as $sku => [$pid, $vid]) {
                $payload = ['product_id' => $pid];
                if ($vid !== null) {
                    $payload['product_variant_id'] = $vid;
                }
                $updatedRows += DB::table('order_items')
                    ->where('legacy_sku', $sku)
                    ->update($payload);
            }
        }

        $this->table(
            ['Метрика', 'Значение'],
            collect($summary)->map(fn ($v, $k) => [$k, $v])->values()->all(),
        );

        if ($apply) {
            $this->info("Обновлено строк order_items: {$updatedRows}");
        }

        if ($showUnmatched > 0 && $unmatched) {
            $this->newLine();
            $this->info("Top {$showUnmatched} unmatched SKU (нет в каталоге):");
            $top = array_slice($unmatched, 0, $showUnmatched);
            $this->table(['cnt', 'legacy_sku', 'legacy_name'], array_map(
                fn ($u) => [$u['cnt'], $u['sku'], $u['name']],
                $top,
            ));
        }

        return self::SUCCESS;
    }
}
