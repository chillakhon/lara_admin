<?php

namespace App\Console\Commands\Import;

use App\Services\Import\ClientImportService;
use Illuminate\Console\Command;

class ImportClientsFromCsv extends Command
{
    protected $signature = 'import:clients
        {file : Путь к CSV (UTF-8/UTF-16LE с BOM, tab-разделитель, формат InSales)}
        {--limit=0 : Максимум клиентов (0 = без лимита)}
        {--dry-run : Только показать статистику, без записи в БД}
        {--no-overwrite : Не переписывать существующих клиентов}
        {--memory=1024M : memory_limit на время выполнения}';

    protected $description = 'Импортировать клиентов из CSV-выгрузки InSales';

    public function handle(ClientImportService $service): int
    {
        $file = (string) $this->argument('file');
        if (!is_file($file) || !is_readable($file)) {
            $this->error("Файл не найден или недоступен: {$file}");
            return self::FAILURE;
        }

        @set_time_limit(0);
        @ini_set('memory_limit', (string) $this->option('memory'));

        $this->info(sprintf(
            'Импорт клиентов: %s (%s МБ)%s',
            $file,
            number_format(filesize($file) / 1024 / 1024, 1),
            $this->option('dry-run') ? ' — DRY RUN' : '',
        ));

        $start = microtime(true);
        $stats = $service->import($file, [
            'limit' => (int) $this->option('limit'),
            'dry_run' => (bool) $this->option('dry-run'),
            'overwrite' => !$this->option('no-overwrite'),
        ]);
        $stats['duration_sec'] = round(microtime(true) - $start, 2);
        $stats['peak_memory_mb'] = round(memory_get_peak_usage(true) / 1024 / 1024, 1);

        $this->newLine();
        $this->table(
            ['Метрика', 'Значение'],
            collect($stats)
                ->reject(fn ($v, $k) => $k === 'errors_list')
                ->map(fn ($v, $k) => [$k, is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)])
                ->values()
                ->all(),
        );

        if (!empty($stats['errors_list'])) {
            $this->warn('Ошибки (первые ' . count($stats['errors_list']) . '):');
            foreach ($stats['errors_list'] as $e) {
                $this->line(' - ' . json_encode($e, JSON_UNESCAPED_UNICODE));
            }
        }

        return ($stats['errors'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }
}
