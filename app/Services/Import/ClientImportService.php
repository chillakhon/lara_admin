<?php

namespace App\Services\Import;

use App\Models\City;
use App\Models\Client;
use App\Models\Country;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Импорт клиентов из CSV (поддерживает форматы UTF-8 и UTF-16LE с BOM).
 *
 * Файл читается поточно через генератор parseRows(), что позволяет
 * обрабатывать большие выгрузки без загрузки в память целиком.
 */
class ClientImportService
{
    /** @var array<string,int> code => id */
    protected array $countriesByCode = [];
    /** @var array<string,int> normalized name => id */
    protected array $countriesByName = [];
    /** @var array<string,int> normalized name => id */
    protected array $citiesByName = [];

    protected bool $referencesLoaded = false;

    /**
     * Карта "название колонки в CSV" => "ключ для дальнейшей логики".
     * Дубль "Имя" обрабатывается отдельно — берём первое вхождение.
     */
    protected array $columnAliases = [
        'Подписка на новости' => 'subscribed_to_newsletter',
        'Имя' => 'first_name',
        'Телефон' => 'phone',
        'Фамилия' => 'last_name',
        'Отчество' => 'middle_name',
        'E-mail' => 'email',
        'Согласие на обработку персональных данных' => 'personal_data_consent',
        'Получать уведомления о заказе' => 'messenger_subscription',
        'кол-во заказов' => 'orders_count',
        'оборот' => 'turnover',
        'подписан на рассылку' => 'subscribed_to_newsletter',
        'зарегистрирован' => 'is_registered',
        'группа' => 'group_name',
        'RFM' => 'rfm_segment',
        'кол-во бонусов' => 'bonus_balance',
        'дата создания' => 'created_at',
        'страна' => 'country_text',
        'регион' => 'delivery_region',
        'город' => 'city_text',
        'улица' => 'delivery_street',
        'дом' => 'delivery_house',
        'квартира' => 'delivery_apartment',
        'адрес' => 'delivery_address',
        'почтовый индекс' => 'delivery_postal_code',
    ];

    /**
     * Поточно отдаёт ассоциативные массивы строк CSV.
     * Ключи — нормализованные (см. columnAliases), либо исходные заголовки,
     * если для них нет алиаса.
     *
     * @return \Generator<int, array<string, string|null>>
     */
    public function parseRows(string $filePath): \Generator
    {
        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            throw new \RuntimeException("Не удалось открыть файл: {$filePath}");
        }

        try {
            // Авто-детект BOM/кодировки по первым байтам.
            $bom = fread($handle, 4);
            rewind($handle);

            if (strncmp($bom, "\xFF\xFE", 2) === 0) {
                // UTF-16LE: пропускаем BOM и навешиваем iconv-фильтр.
                fread($handle, 2);
                stream_filter_append($handle, 'convert.iconv.UTF-16LE/UTF-8');
            } elseif (strncmp($bom, "\xFE\xFF", 2) === 0) {
                fread($handle, 2);
                stream_filter_append($handle, 'convert.iconv.UTF-16BE/UTF-8');
            } elseif (strncmp($bom, "\xEF\xBB\xBF", 3) === 0) {
                fread($handle, 3);
            }

            $header = fgetcsv($handle, 0, "\t");
            if ($header === false || $header === null) {
                return;
            }

            // Маппим заголовки. Дубликаты заменяем на первое вхождение.
            $mapped = [];
            $seen = [];
            foreach ($header as $idx => $raw) {
                $title = trim((string) $raw);
                $key = $this->columnAliases[$title] ?? $title;
                if (isset($seen[$key])) {
                    // Для дубля колонки используем суффикс, чтобы потом игнорировать.
                    $key .= '__dup_' . $idx;
                }
                $seen[$key] = true;
                $mapped[$idx] = $key;
            }

            while (!feof($handle)) {
                $row = fgetcsv($handle, 0, "\t");
                if ($row === false) {
                    break;
                }
                // Пустые строки CSV игнорируем.
                if (count($row) === 1 && ($row[0] === null || trim((string) $row[0]) === '')) {
                    continue;
                }

                $assoc = [];
                foreach ($mapped as $idx => $key) {
                    $assoc[$key] = $row[$idx] ?? null;
                }
                yield $assoc;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Полный импорт файла. Возвращает агрегированную статистику.
     *
     * @param  array{
     *     limit?: int,
     *     dry_run?: bool,
     *     overwrite?: bool,
     * }  $options
     * @return array<string, mixed>
     */
    public function import(string $filePath, array $options = []): array
    {
        $limit = (int) ($options['limit'] ?? 0);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $overwrite = (bool) ($options['overwrite'] ?? true);

        $this->loadReferences();

        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'errors_list' => [],
        ];

        foreach ($this->parseRows($filePath) as $i => $row) {
            $stats['total']++;

            if ($limit > 0 && $stats['total'] > $limit) {
                $stats['total']--; // не считаем последнюю
                break;
            }

            $email = trim((string) ($row['email'] ?? ''));
            $phone = $this->normalizePhone((string) ($row['phone'] ?? ''));

            // Без email и без phone строку идентифицировать нечем — пропускаем.
            if ($email === '' && $phone === null) {
                $stats['skipped']++;
                continue;
            }

            try {
                [$clientData, $profileData] = $this->mapRow($row);

                if ($dryRun) {
                    // В dry-run отчитываемся только о действии, ничего не пишем.
                    $exists = $this->findExistingClient($email !== '' ? $email : null, $phone) !== null;
                    $exists ? $stats['updated']++ : $stats['created']++;
                    continue;
                }

                $isNew = false;
                DB::transaction(function () use ($email, $phone, $clientData, $profileData, $overwrite, &$isNew) {
                    /** @var Client|null $client */
                    $client = $this->findExistingClient($email !== '' ? $email : null, $phone);

                    if (!$client) {
                        $isNew = true;
                        $client = Client::create(array_merge([
                            'email' => $email !== '' ? $email : null,
                            'bonus_balance' => 0,
                        ], $clientData));
                    } elseif ($overwrite) {
                        // Не перетираем непустой email пустым.
                        if ($email !== '' && $email !== $client->email) {
                            $clientData['email'] = $email;
                        }
                        $client->fill($clientData);
                        $client->save();
                    }

                    $profile = UserProfile::where('client_id', $client->id)->first();
                    if (!$profile) {
                        UserProfile::create(array_merge(['client_id' => $client->id], $profileData));
                    } elseif ($overwrite) {
                        $profile->fill($profileData);
                        $profile->save();
                    }
                });

                $isNew ? $stats['created']++ : $stats['updated']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                if (count($stats['errors_list']) < 50) {
                    $stats['errors_list'][] = [
                        'row' => $i + 2, // +1 за заголовок, +1 за 1-based
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ];
                }
                Log::warning('CSV client import row failed', [
                    'row' => $i,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Преобразует одну CSV-строку в данные для Client + UserProfile.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function mapRow(array $row): array
    {
        $countryText = trim((string) ($row['country_text'] ?? ''));
        $cityText = trim((string) ($row['city_text'] ?? ''));

        $clientData = [
            'subscribed_to_newsletter' => $this->parseBool($row['subscribed_to_newsletter'] ?? null),
            'personal_data_consent' => $this->parseBool($row['personal_data_consent'] ?? null),
            'messenger_subscription' => $this->parseBool($row['messenger_subscription'] ?? null),
            'group_name' => $this->nullableString($row['group_name'] ?? null, 255),
            'rfm_segment' => $this->nullableString($row['rfm_segment'] ?? null, 32),
            'bonus_balance' => $this->parseDecimal($row['bonus_balance'] ?? null),
        ];

        if (!empty($row['created_at'])) {
            $created = $this->parseDate((string) $row['created_at']);
            if ($created) {
                $clientData['created_at'] = $created;
            }
        }

        $profileData = [
            'first_name' => $this->nullableString($row['first_name'] ?? null, 255),
            'last_name' => $this->nullableString($row['last_name'] ?? null, 255),
            'middle_name' => $this->nullableString($row['middle_name'] ?? null, 255),
            'phone' => $this->normalizePhone((string) ($row['phone'] ?? '')),
            'address' => $this->buildSingleLineAddress($row),
            'delivery_address' => $this->nullableString($row['delivery_address'] ?? null, 500),
            'delivery_country_id' => $this->resolveCountryId($countryText),
            'delivery_city_id' => $this->resolveCityId($cityText),
            'delivery_region' => $this->nullableString($row['delivery_region'] ?? null, 255),
            'delivery_street' => $this->nullableString($row['delivery_street'] ?? null, 255),
            'delivery_house' => $this->nullableString($row['delivery_house'] ?? null, 50),
            'delivery_apartment' => $this->nullableString($row['delivery_apartment'] ?? null, 50),
            'delivery_postal_code' => $this->nullableString($row['delivery_postal_code'] ?? null, 20),
        ];

        return [array_filter($clientData, fn($v) => $v !== null), $profileData];
    }

    /**
     * Ищем клиента по email (приоритетно) или нормализованному phone.
     * Возвращает первого подходящего клиента или null.
     */
    protected function findExistingClient(?string $email, ?string $phone): ?Client
    {
        if ($email !== null && $email !== '') {
            $client = Client::withTrashed()->where('email', $email)->first();
            if ($client) {
                return $client;
            }
        }

        if ($phone !== null && $phone !== '') {
            $clientId = UserProfile::where('phone', $phone)->value('client_id');
            if ($clientId) {
                return Client::withTrashed()->find($clientId);
            }
        }

        return null;
    }

    protected function loadReferences(): void
    {
        if ($this->referencesLoaded) {
            return;
        }

        Country::query()
            ->select(['id', 'name', 'code'])
            ->get()
            ->each(function (Country $c) {
                $code = strtoupper(trim((string) $c->code));
                if ($code !== '') {
                    $this->countriesByCode[$code] = $c->id;
                }
                $name = $this->normalizeName((string) $c->name);
                if ($name !== '') {
                    $this->countriesByName[$name] = $c->id;
                }
            });

        City::query()
            ->select(['id', 'name'])
            ->get()
            ->each(function (City $c) {
                $name = $this->normalizeName((string) $c->name);
                if ($name === '') {
                    return;
                }
                // Если несколько городов с одним названием — оставляем первый.
                if (!isset($this->citiesByName[$name])) {
                    $this->citiesByName[$name] = $c->id;
                }
            });

        $this->referencesLoaded = true;
    }

    protected function resolveCountryId(string $value): ?int
    {
        if ($value === '') {
            return null;
        }
        $code = strtoupper($value);
        if (isset($this->countriesByCode[$code])) {
            return $this->countriesByCode[$code];
        }
        $name = $this->normalizeName($value);
        return $this->countriesByName[$name] ?? null;
    }

    protected function resolveCityId(string $value): ?int
    {
        if ($value === '') {
            return null;
        }
        $name = $this->normalizeName($value);
        return $this->citiesByName[$name] ?? null;
    }

    protected function normalizeName(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    protected function parseBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $v = mb_strtolower(trim((string) $value));
        return in_array($v, ['да', 'yes', '1', 'true'], true);
    }

    protected function nullableString($value, int $max = 255): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '' || $s === '""') {
            return null;
        }
        return mb_substr($s, 0, $max);
    }

    protected function parseDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $s = str_replace([' ', ','], ['', '.'], (string) $value);
        return (float) $s;
    }

    protected function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        foreach (['d.m.Y H:i', 'd.m.Y H:i:s', 'd.m.Y', 'Y-m-d H:i:s', 'Y-m-d'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value);
            } catch (\Throwable $e) {
                // try next
            }
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizePhone(string $phone): ?string
    {
        if ($phone === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }
        return '+' . $digits;
    }

    protected function buildSingleLineAddress(array $row): ?string
    {
        $parts = array_filter([
            $this->nullableString($row['delivery_region'] ?? null),
            $this->nullableString($row['city_text'] ?? null),
            $this->nullableString($row['delivery_street'] ?? null),
            $this->nullableString($row['delivery_house'] ?? null),
            $this->nullableString($row['delivery_apartment'] ?? null),
        ]);

        if (!empty($parts)) {
            return mb_substr(implode(', ', $parts), 0, 255);
        }

        return $this->nullableString($row['delivery_address'] ?? null, 255);
    }
}
