<?php

namespace App\Console\Commands\Import;

use App\Models\Client;
use App\Services\Import\ClientImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Однопроходная дедупликация клиентов, созданных багованным импортом
 * из clients_data-*.csv: email в наших записях остался NULL, хотя в CSV он был,
 * поэтому вместо обновления существующих клиентов мы создали пустые дубликаты.
 *
 * Команда по паре (last_name, first_name) сопоставляет импортированных
 * клиентов с email из CSV и, если в БД есть ровно один «старый» клиент с тем же email
 * (и не входит в пачку импорта) — soft-delete-ит дубликат.
 */
class DedupeImportedClients extends Command
{
    protected $signature = 'clients:dedupe-import
        {csv : Путь к исходному clients_data-*.csv}
        {--imported-from= : Импорт начался не ранее этой метки (default: автоопределение пачки)}
        {--imported-to= : …и не позднее (default: автоопределение)}
        {--apply : Выполнить удаление (по умолчанию dry-run)}';

    protected $description = 'Слить пустые дубликаты клиентов, созданные багованным импортом из CSV';

    public function handle(ClientImportService $service): int
    {
        $csvPath = (string) $this->argument('csv');
        if (!is_file($csvPath) || !is_readable($csvPath)) {
            $this->error("CSV не найден или недоступен: {$csvPath}");
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        $from = $this->option('imported-from');
        $to = $this->option('imported-to');

        // Если не задано — ищем самый «массовый» момент создания клиентов без email/заказов.
        if (!$from || !$to) {
            $bucket = DB::table('clients')
                ->whereNull('deleted_at')
                ->whereNull('email')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->whereColumn('orders.client_id', 'clients.id'))
                ->select(DB::raw('created_at AS t'), DB::raw('COUNT(*) AS cnt'))
                ->groupBy('t')
                ->orderByDesc('cnt')
                ->first();
            if (!$bucket || $bucket->cnt < 5) {
                $this->error('Не удалось автоматически найти пачку импорта (нет массовой group by created_at). Передайте --imported-from=... --imported-to=...');
                return self::FAILURE;
            }
            $from = $bucket->t;
            $to = $bucket->t;
        }

        $this->info("Пачка импорта: $from .. $to");

        // Загружаем CSV: ключ last_name|first_name (lowercase) -> список {email, phone_digits}.
        $this->info('Парсим CSV…');
        $csvByName = [];
        $rowsParsed = 0;
        foreach ($service->parseRows($csvPath) as $row) {
            $rowsParsed++;
            $ln = mb_strtolower(trim((string) ($row['last_name'] ?? '')));
            $fn = mb_strtolower(trim((string) ($row['first_name'] ?? '')));
            if ($ln === '' && $fn === '') continue;
            $email = trim((string) ($row['email'] ?? ''));
            $phoneDigits = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));
            $csvByName["$ln|$fn"][] = ['email' => $email, 'phone_digits' => $phoneDigits];
        }
        $this->info("Строк в CSV прочитано: $rowsParsed; уникальных ФИО: " . count($csvByName));

        $imported = Client::with('profile')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->get();

        $this->info("Импортированных клиентов в пачке: " . $imported->count());

        $toDelete = [];        // массив [new_id, old_id, email]
        $conflicts = [];       // ручной разбор
        $skipped = [];

        foreach ($imported as $c) {
            $ln = mb_strtolower(trim($c->profile?->last_name ?? ''));
            $fn = mb_strtolower(trim($c->profile?->first_name ?? ''));
            $key = "$ln|$fn";
            $csvRows = $csvByName[$key] ?? [];

            $dupDigits = preg_replace('/\D+/', '', (string) ($c->profile?->phone ?? ''));
            $dupTail = $dupDigits !== '' ? substr($dupDigits, -10) : null;

            // Шаг 1: сужаем по phone_tail (последние 10 цифр телефона).
            // Это особенно важно для омонимов ФИО.
            $csvEmailsFromPhone = [];
            if ($dupTail) {
                foreach ($csvRows as $r) {
                    if ($r['phone_digits'] !== '' && substr($r['phone_digits'], -10) === $dupTail && $r['email']) {
                        $csvEmailsFromPhone[$r['email']] = true;
                    }
                }
            }
            $emails = array_keys($csvEmailsFromPhone);

            // Шаг 2: если по телефону ничего — берём все email из CSV под этим ФИО.
            if (empty($emails)) {
                foreach ($csvRows as $r) if ($r['email']) $emails[] = $r['email'];
                $emails = array_values(array_unique($emails));
            }

            if (empty($emails)) {
                $skipped[] = ['id' => $c->id, 'reason' => 'no csv email', 'name' => "$ln $fn"];
                continue;
            }

            // Для каждого email находим старых клиентов в БД и собираем уникальные old_id.
            $oldIds = [];
            $emailByOld = [];
            foreach ($emails as $em) {
                $ids = DB::table('clients')
                    ->whereNull('deleted_at')
                    ->where('id', '!=', $c->id)
                    ->where('created_at', '<', $from)
                    ->where('email', $em)
                    ->pluck('id')->all();
                foreach ($ids as $oid) {
                    $oldIds[$oid] = true;
                    $emailByOld[$oid] = $em;
                }
            }
            $oldIds = array_keys($oldIds);

            if (count($oldIds) === 0) {
                $skipped[] = ['id' => $c->id, 'reason' => 'emails have no old client', 'name' => "$ln $fn", 'emails' => $emails];
                continue;
            }
            if (count($oldIds) > 1) {
                $conflicts[] = ['id' => $c->id, 'name' => "$ln $fn", 'emails' => $emails, 'multi_old' => $oldIds];
                continue;
            }

            $toDelete[] = ['new_id' => $c->id, 'old_id' => $oldIds[0], 'email' => $emailByOld[$oldIds[0]]];
        }

        $this->info('');
        $this->info("К удалению: " . count($toDelete));
        $this->info("Конфликтов: " . count($conflicts));
        $this->info("Пропущено (нет email в CSV / не нашли старого): " . count($skipped));

        if (!$apply) {
            $this->warn('DRY RUN — ничего не меняем. Запустите с --apply для выполнения.');
            $this->line('Примеры к удалению:');
            foreach (array_slice($toDelete, 0, 5) as $r) {
                $this->line("  dup id={$r['new_id']} → old id={$r['old_id']} ({$r['email']})");
            }
            return self::SUCCESS;
        }

        if (empty($toDelete)) {
            $this->warn('Нечего удалять.');
            return self::SUCCESS;
        }

        $ids = array_column($toDelete, 'new_id');
        DB::transaction(function () use ($ids) {
            Client::whereIn('id', $ids)->delete(); // soft-delete
        });
        $this->info('Soft-deleted клиентов: ' . count($ids));

        // Сохраняем отчёт
        $reportPath = storage_path('logs/clients_dedupe_' . now()->format('Ymd_His') . '.json');
        file_put_contents($reportPath, json_encode([
            'imported_from' => $from,
            'imported_to' => $to,
            'to_delete' => $toDelete,
            'conflicts' => $conflicts,
            'skipped' => $skipped,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $this->info("Отчёт: $reportPath");

        return self::SUCCESS;
    }
}
