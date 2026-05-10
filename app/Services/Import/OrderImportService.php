<?php

namespace App\Services\Import;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\DeliveryMethod;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderHistory;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Импорт заказов из выгрузки InSales (orders-DD.MM.YYYY.csv).
 *
 * Файл — UTF-16LE/UTF-8, tab-разделённый. Один заказ занимает несколько строк
 * (по строке на каждую позицию + строка "Доставка" + опционально "Скидка");
 * заказы разделены пустой строкой. Парсинг идёт через генератор parseOrders(),
 * чтобы можно было обрабатывать большие выгрузки без загрузки в память.
 */
class OrderImportService
{
    /** @var array<string,int> normalized name => id */
    protected array $deliveryMethodsByName = [];

    /** @var array<string,int> sku => product_variant_id */
    protected array $variantsBySku = [];

    /** @var array<string,int> sku => product_id (для товаров без вариантов) */
    protected array $productsBySku = [];

    /** @var array<string,int> normalized name => user_id */
    protected array $usersByName = [];

    protected bool $referencesLoaded = false;

    /**
     * Сопоставление "название колонки" => "ключ".
     * В шапке CSV есть дубликат "Имя" — мы его теряем (берём первое вхождение).
     */
    protected array $columnAliases = [
        '№' => 'order_number',
        'Дата создания' => 'created_at',
        'Статус заказа' => 'status_text',
        'Статус оплаты' => 'payment_status_text',
        'ФИО заказчика' => 'recipient_full_name',
        'Телефон' => 'phone',
        'Имя' => 'first_name',
        'E-mail' => 'email',
        'Почта' => 'email_alt',
        'Согласие на обработку персональных данных' => 'personal_data_consent',
        'Получать уведомления о заказе' => 'messenger_subscription',
        'Адрес доставки (город, улица, дом, офис (квартира))' => 'delivery_full',
        'Страна' => 'country',
        'Населенный пункт' => 'city',
        'Почтовый индекс' => 'postal_code',
        'Адрес' => 'address_line',
        'Наименование товара (услуги)' => 'product_name',
        'Количество' => 'quantity',
        'Артикул' => 'sku',
        'Коды маркировки' => 'marking_codes',
        'Способ доставки' => 'delivery_method',
        'Сумма для получения' => 'amount',
        'Цена закупки' => 'purchase_price',
        'Комментарий покупателя' => 'buyer_comment',
        'Комментарии продавца' => 'seller_comment',
        'Реферер при последнем заходе' => 'referrer_last',
        'Реферер при первом заходе' => 'referrer_first',
        'Источник при последнем заходе' => 'utm_source',
        'Источник при первом заходе' => 'utm_source_first',
        'Посадочная страница при последнем заходе' => 'landing_last',
        'Посадочная страница при первом заходе' => 'landing_first',
        'История изменений' => 'history_text',
        'Имя ответственного' => 'assigned_user_name',
        'Желаемая дата доставки' => 'delivery_date',
        'Номер для отслеживания заказа СДЭК' => 'cdek_track_number',
        'Идентификатор отгрузки АКС' => 'axs_shipment_id',
        'Трек-номер' => 'tracking_number',
        'Id транзакции' => 'transaction_id',
        'Статусы DMS для CP' => 'cp_dms_status',
        'Трек-номер Почта России' => 'russian_post_track',
        'Второй чек прихода CloudKassir' => 'cloudkassir_second_receipt',
        'Чек не пробивать' => 'no_receipt',
        'Страна экспорта' => 'export_country',
        'Зачислено баллов' => 'bonuses_credited',
        'Списано баллов' => 'bonuses_used',
    ];

    /**
     * Поточный парсер: собирает строки одного заказа (до пустого разделителя)
     * и отдаёт массив с шапкой + позициями.
     *
     * @return \Generator<int, array{header: array<string,?string>, items: array<int,array<string,?string>>, raw_rows: array<int,array<string,?string>>}>
     */
    public function parseOrders(string $filePath): \Generator
    {
        $handle = @fopen($filePath, 'rb');
        if (!$handle) {
            throw new \RuntimeException("Не удалось открыть файл: {$filePath}");
        }

        try {
            // Авто-детект BOM/кодировки
            $bom = fread($handle, 4);
            rewind($handle);

            if (strncmp($bom, "\xFF\xFE", 2) === 0) {
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

            $mapped = [];
            $seen = [];
            foreach ($header as $idx => $raw) {
                $title = trim((string) $raw);
                $key = $this->columnAliases[$title] ?? $title;
                if (isset($seen[$key])) {
                    $key .= '__dup_' . $idx;
                }
                $seen[$key] = true;
                $mapped[$idx] = $key;
            }

            $current = [];
            while (!feof($handle)) {
                $row = fgetcsv($handle, 0, "\t");
                if ($row === false) {
                    break;
                }

                $assoc = [];
                foreach ($mapped as $idx => $key) {
                    $assoc[$key] = $row[$idx] ?? null;
                }

                $orderNumber = trim((string) ($assoc['order_number'] ?? ''));
                $product = trim((string) ($assoc['product_name'] ?? ''));

                // Разделитель заказов — полностью пустая строка.
                if ($orderNumber === '' && $product === '') {
                    if ($current) {
                        yield $this->groupOrder($current);
                        $current = [];
                    }
                    continue;
                }

                $current[] = $assoc;
            }

            if ($current) {
                yield $this->groupOrder($current);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int,array<string,?string>>  $rows
     * @return array{header: array<string,?string>, items: array<int,array<string,?string>>, raw_rows: array<int,array<string,?string>>}
     */
    protected function groupOrder(array $rows): array
    {
        // В качестве шапки используем первую строку с заполненным order_number.
        $header = $rows[0] ?? [];
        foreach ($rows as $r) {
            if (trim((string) ($r['order_number'] ?? '')) !== '') {
                $header = $r;
                break;
            }
        }

        $items = [];
        foreach ($rows as $r) {
            $product = trim((string) ($r['product_name'] ?? ''));
            if ($product === '' || $product === 'Доставка' || $product === 'Скидка') {
                continue;
            }
            $items[] = $r;
        }

        return [
            'header' => $header,
            'items' => $items,
            'raw_rows' => $rows,
        ];
    }

    /**
     * Полный импорт.
     *
     * @param  array{
     *     limit?: int,
     *     dry_run?: bool,
     *     overwrite?: bool,
     *     import_history?: bool,
     * }  $options
     * @return array<string, mixed>
     */
    public function import(string $filePath, array $options = []): array
    {
        $limit = (int) ($options['limit'] ?? 0);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $overwrite = (bool) ($options['overwrite'] ?? true);
        $importHistory = (bool) ($options['import_history'] ?? true);

        $this->loadReferences();

        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'items_total' => 0,
            'history_total' => 0,
            'errors_list' => [],
        ];

        foreach ($this->parseOrders($filePath) as $idx => $bundle) {
            $stats['total']++;

            if ($limit > 0 && $stats['total'] > $limit) {
                $stats['total']--;
                break;
            }

            $orderNumber = trim((string) ($bundle['header']['order_number'] ?? ''));
            if ($orderNumber === '') {
                $stats['skipped']++;
                continue;
            }

            try {
                if ($dryRun) {
                    $exists = Order::where('order_number', $orderNumber)->exists();
                    $exists ? $stats['updated']++ : $stats['created']++;
                    $stats['items_total'] += count($bundle['items']);
                    continue;
                }

                $isNew = false;
                $itemsCount = 0;
                $historyCount = 0;
                DB::transaction(function () use (
                    $bundle,
                    $orderNumber,
                    $overwrite,
                    $importHistory,
                    &$isNew,
                    &$itemsCount,
                    &$historyCount,
                ) {
                    [$orderData, $addressData, $itemsData, $historyEntries, $totals] =
                        $this->mapBundle($bundle);

                    /** @var Order|null $order */
                    $order = Order::where('order_number', $orderNumber)->first();

                    $payload = array_merge(
                        ['order_number' => $orderNumber],
                        $orderData,
                        $totals,
                    );

                    if (!$order) {
                        $isNew = true;
                        $order = new Order();
                        // forceFill, чтобы сохранить created_at/updated_at из CSV
                        // (они не в $fillable, и Eloquent::create их отбросил бы).
                        $order->forceFill($payload);
                        $order->save();
                    } elseif ($overwrite) {
                        $order->forceFill($payload);
                        $order->save();
                    }

                    if ($overwrite || $isNew) {
                        OrderAddress::updateOrCreate(
                            ['order_id' => $order->id],
                            $addressData,
                        );

                        // Позиции пересоздаём целиком — это безопаснее, чем
                        // пытаться смерджить по индексу/legacy_sku.
                        $order->items()->delete();
                        foreach ($itemsData as $item) {
                            $order->items()->create($item);
                            $itemsCount++;
                        }

                        if ($importHistory && !empty($historyEntries)) {
                            $order->history()->delete();
                            // Через DB::insert, чтобы сохранить исторический created_at —
                            // Eloquent::create() перезатёр бы его на now().
                            $rows = [];
                            foreach ($historyEntries as $entry) {
                                $rows[] = array_merge(
                                    $entry,
                                    ['order_id' => $order->id],
                                );
                            }
                            if ($rows) {
                                DB::table('order_histories')->insert($rows);
                                $historyCount += count($rows);
                            }
                        }
                    }
                });

                $isNew ? $stats['created']++ : $stats['updated']++;
                $stats['items_total'] += $itemsCount;
                $stats['history_total'] += $historyCount;
            } catch (\Throwable $e) {
                $stats['errors']++;
                if (count($stats['errors_list']) < 50) {
                    $stats['errors_list'][] = [
                        'order_number' => $orderNumber,
                        'error' => $e->getMessage(),
                    ];
                }
                Log::warning('CSV order import failed', [
                    'order_number' => $orderNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Преобразует одну группу строк CSV (заказ) в данные для записи в БД.
     *
     * @param  array{header: array<string,?string>, items: array<int,array<string,?string>>, raw_rows: array<int,array<string,?string>>}  $bundle
     * @return array{0: array<string,mixed>, 1: array<string,mixed>, 2: array<int,array<string,mixed>>, 3: array<int,array<string,mixed>>, 4: array<string,mixed>}
     */
    protected function mapBundle(array $bundle): array
    {
        $header = $bundle['header'];
        $rawRows = $bundle['raw_rows'];

        // Связка с существующим клиентом по email/phone.
        $email = $this->nullableString($header['email'] ?? null) ?? $this->nullableString($header['email_alt'] ?? null);
        $phone = $this->normalizePhone((string) ($header['phone'] ?? ''));
        $client = $this->findClient($email, $phone);

        // Получатель: ФИО приходит одной строкой "Фамилия Имя Отчество".
        $recipient = $this->splitFullName((string) ($header['recipient_full_name'] ?? ''));
        if (!$recipient['first_name']) {
            $recipient['first_name'] = $this->nullableString($header['first_name'] ?? null) ?? '';
        }

        $createdAt = $this->parseDate((string) ($header['created_at'] ?? ''));
        $deliveryDate = $this->parseDate((string) ($header['delivery_date'] ?? ''));

        $status = $this->resolveStatus((string) ($header['status_text'] ?? ''));
        $paymentStatus = $this->resolvePaymentStatus((string) ($header['payment_status_text'] ?? ''));

        // Суммы: ходим по всем строкам и считаем item_subtotal/delivery/discount.
        $itemsSubtotal = 0.0;
        $deliveryCost = 0.0;
        $itemsDiscount = 0.0;
        foreach ($rawRows as $r) {
            $product = trim((string) ($r['product_name'] ?? ''));
            $amount = $this->parseDecimal($r['amount'] ?? null);
            if ($product === 'Доставка') {
                $deliveryCost += $amount;
            } elseif ($product === 'Скидка') {
                $itemsDiscount += abs($amount);
            } elseif ($product !== '') {
                $itemsSubtotal += $amount;
            }
        }

        $deliveryMethodText = $this->nullableString($header['delivery_method'] ?? null);
        $deliveryMethodId = $deliveryMethodText
            ? $this->resolveDeliveryMethodId($deliveryMethodText)
            : null;

        $assignedUserId = $this->resolveUserId(
            $this->nullableString($header['assigned_user_name'] ?? null)
        );

        $legacyMeta = array_filter([
            'cdek_track_number' => $this->nullableString($header['cdek_track_number'] ?? null),
            'axs_shipment_id' => $this->nullableString($header['axs_shipment_id'] ?? null),
            'cp_dms_status' => $this->nullableString($header['cp_dms_status'] ?? null),
            'russian_post_track' => $this->nullableString($header['russian_post_track'] ?? null),
            'cloudkassir_second_receipt' => $this->nullableString($header['cloudkassir_second_receipt'] ?? null),
            'transaction_id' => $this->nullableString($header['transaction_id'] ?? null),
            'imported_at' => now()->toIso8601String(),
        ], fn($v) => $v !== null && $v !== '');

        $orderData = [
            'client_id' => $client?->id,
            'assigned_user_id' => $assignedUserId,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'utm_source' => $this->nullableString($header['utm_source'] ?? null),
            'utm_source_first' => $this->nullableString($header['utm_source_first'] ?? null),
            'referrer_first' => $this->nullableString($header['referrer_first'] ?? null, 1024),
            'referrer_last' => $this->nullableString($header['referrer_last'] ?? null, 1024),
            'landing_first' => $this->nullableString($header['landing_first'] ?? null, 1024),
            'landing_last' => $this->nullableString($header['landing_last'] ?? null, 1024),
            'notes' => $this->nullableString($header['seller_comment'] ?? null, 65000),
            'tracking_number' => $this->nullableString($header['tracking_number'] ?? null),
            'no_receipt' => $this->parseBool($header['no_receipt'] ?? null),
            'export_country' => $this->nullableString($header['export_country'] ?? null, 64),
            'bonuses_credited' => $this->parseDecimal($header['bonuses_credited'] ?? null),
            'bonuses_used' => $this->parseDecimal($header['bonuses_used'] ?? null),
            'legacy_delivery_method' => $deliveryMethodText
                ? mb_substr($deliveryMethodText, 0, 512)
                : null,
            'delivery_method_id' => $deliveryMethodId,
            'delivery_cost' => $deliveryCost,
            'delivery_date' => $deliveryDate,
            'payment_id' => $this->nullableString($header['transaction_id'] ?? null),
            'legacy_meta' => $legacyMeta ?: null,
        ];

        if ($createdAt) {
            $orderData['created_at'] = $createdAt;
            $orderData['updated_at'] = $createdAt;
        }

        // Платёжная дата: если статус "Оплачен" и есть transaction_id — считаем оплаченным.
        if ($paymentStatus === PaymentStatus::PAID->value && $createdAt) {
            $orderData['paid_at'] = $createdAt;
        }

        $totals = [
            'total_amount' => round($itemsSubtotal - $itemsDiscount + $deliveryCost, 2),
            'total_amount_original' => round($itemsSubtotal + $deliveryCost, 2),
            'discount_amount' => round($itemsDiscount, 2),
            'total_items_discount' => round($itemsDiscount, 2),
            'total_promo_discount' => 0,
        ];

        $addressData = [
            'recipient_first_name' => $recipient['first_name'] ?: null,
            'recipient_last_name' => $recipient['last_name'] ?: null,
            'recipient_middle_name' => $recipient['middle_name'] ?: null,
            'recipient_phone' => $phone,
            'country' => $this->nullableString($header['country'] ?? null),
            'city' => $this->nullableString($header['city'] ?? null),
            'postal_code' => $this->nullableString($header['postal_code'] ?? null, 20),
            'address' => $this->nullableString($header['address_line'] ?? null, 65000)
                ?? $this->nullableString($header['delivery_full'] ?? null, 65000),
            'buyer_comment' => $this->nullableString($header['buyer_comment'] ?? null, 65000),
            'delivery_date' => $deliveryDate,
        ];

        // Позиции
        $itemsData = [];
        foreach ($bundle['items'] as $itemRow) {
            $sku = $this->nullableString($itemRow['sku'] ?? null, 255);
            $quantity = (int) $this->parseDecimal($itemRow['quantity'] ?? null);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $amount = $this->parseDecimal($itemRow['amount'] ?? null);
            $price = $quantity > 0 ? round($amount / $quantity, 2) : $amount;

            [$productId, $variantId] = $sku
                ? $this->resolveProductBySku($sku)
                : [null, null];

            $itemsData[] = [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'legacy_name' => $this->nullableString($itemRow['product_name'] ?? null, 255),
                'legacy_sku' => $sku,
                'quantity' => $quantity,
                'price' => $price,
                'purchase_price' => $this->nullableDecimal($itemRow['purchase_price'] ?? null),
                'marking_codes' => $this->nullableString($itemRow['marking_codes'] ?? null, 65000),
                'discount' => 0,
                'is_gift' => false,
            ];
        }

        // История изменений: одна большая текстовая колонка вида
        // "DD.MM.YYYY HH:MM <event> [пользователем NAME]\n…"
        $historyEntries = $this->parseHistory((string) ($header['history_text'] ?? ''));

        return [$orderData, $addressData, $itemsData, $historyEntries, $totals];
    }

    /**
     * Парсит "История изменений" InSales.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseHistory(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // События начинаются с даты "DD.MM.YYYY HH:MM"; разделим по этой границе.
        $pattern = '/(?<=^|\n)(\d{2}\.\d{2}\.\d{4} \d{2}:\d{2})\s+/u';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts) || count($parts) < 2) {
            return [];
        }

        $entries = [];
        for ($i = 0; $i + 1 < count($parts); $i += 2) {
            $when = $parts[$i];
            $body = trim($parts[$i + 1]);
            $createdAt = $this->parseDate($when);
            if (!$createdAt) {
                continue;
            }

            $entries[] = [
                'action' => 'imported',
                'description' => mb_substr($body, 0, 65000),
                'comment' => null,
                'status' => null,
                'payment_status' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        return $entries;
    }

    protected function findClient(?string $email, ?string $phone): ?Client
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

    protected function resolveDeliveryMethodId(string $name): ?int
    {
        $key = $this->normalizeName($name);
        if (isset($this->deliveryMethodsByName[$key])) {
            return $this->deliveryMethodsByName[$key];
        }
        // InSales часто пишет "Method (Description)" — попробуем по первой части.
        if (preg_match('/^([^()]+)/u', $name, $m)) {
            $short = $this->normalizeName(trim($m[1]));
            if (isset($this->deliveryMethodsByName[$short])) {
                return $this->deliveryMethodsByName[$short];
            }
        }

        return null;
    }

    /**
     * @return array{0: ?int, 1: ?int}  product_id, product_variant_id
     */
    protected function resolveProductBySku(string $sku): array
    {
        if (isset($this->variantsBySku[$sku])) {
            $variantId = $this->variantsBySku[$sku];
            $productId = ProductVariant::where('id', $variantId)->value('product_id');
            return [$productId, $variantId];
        }
        if (isset($this->productsBySku[$sku])) {
            return [$this->productsBySku[$sku], null];
        }

        return [null, null];
    }

    protected function resolveUserId(?string $name): ?int
    {
        if (!$name) {
            return null;
        }
        $key = $this->normalizeName($name);
        return $this->usersByName[$key] ?? null;
    }

    protected function loadReferences(): void
    {
        if ($this->referencesLoaded) {
            return;
        }

        DeliveryMethod::query()
            ->select(['id', 'name'])
            ->get()
            ->each(function (DeliveryMethod $m) {
                $name = $this->normalizeName((string) $m->name);
                if ($name !== '' && !isset($this->deliveryMethodsByName[$name])) {
                    $this->deliveryMethodsByName[$name] = $m->id;
                }
            });

        ProductVariant::query()
            ->whereNotNull('sku')
            ->select(['id', 'product_id', 'sku'])
            ->chunk(2000, function ($chunk) {
                foreach ($chunk as $v) {
                    $sku = trim((string) $v->sku);
                    if ($sku !== '' && !isset($this->variantsBySku[$sku])) {
                        $this->variantsBySku[$sku] = $v->id;
                    }
                }
            });

        Product::query()
            ->whereNotNull('code')
            ->select(['id', 'code'])
            ->chunk(2000, function ($chunk) {
                foreach ($chunk as $p) {
                    $sku = trim((string) $p->code);
                    if ($sku !== '' && !isset($this->productsBySku[$sku])) {
                        $this->productsBySku[$sku] = $p->id;
                    }
                }
            });

        // Менеджеры: сопоставляем по ФИО и email.
        User::query()
            ->with('profile')
            ->select(['id', 'email'])
            ->chunk(500, function ($chunk) {
                foreach ($chunk as $user) {
                    $name = trim($user->profile?->full_name ?? '');
                    if ($name !== '') {
                        $key = $this->normalizeName($name);
                        if (!isset($this->usersByName[$key])) {
                            $this->usersByName[$key] = $user->id;
                        }
                    }
                    if ($user->email) {
                        $emailKey = mb_strtolower(trim($user->email));
                        if (!isset($this->usersByName[$emailKey])) {
                            $this->usersByName[$emailKey] = $user->id;
                        }
                    }
                }
            });

        $this->referencesLoaded = true;
    }

    protected function resolveStatus(string $value): string
    {
        $v = mb_strtolower(trim($value));
        return match ($v) {
            'доставлен' => OrderStatus::DELIVERED->value,
            'отменен', 'отменён' => OrderStatus::CANCELLED->value,
            'отгружен' => OrderStatus::SHIPPED->value,
            'возврат', 'в процессе возврата' => OrderStatus::PRODUCT_RETURN->value,
            'новый' => OrderStatus::NEW->value,
            'в обработке', 'согласован' => OrderStatus::PROCESSING->value,
            default => OrderStatus::NEW->value,
        };
    }

    protected function resolvePaymentStatus(string $value): string
    {
        $v = mb_strtolower(trim($value));
        return match ($v) {
            'оплачен' => PaymentStatus::PAID->value,
            'не оплачен' => PaymentStatus::PENDING->value,
            'возврат оплаты' => PaymentStatus::REFUNDED->value,
            'ошибка оплаты' => PaymentStatus::FAILED->value,
            default => PaymentStatus::PENDING->value,
        };
    }

    /**
     * @return array{first_name: string, last_name: string, middle_name: string}
     */
    protected function splitFullName(string $value): array
    {
        $parts = preg_split('/\s+/u', trim($value)) ?: [];
        $parts = array_values(array_filter($parts, fn($p) => $p !== ''));

        return [
            'last_name' => $parts[0] ?? '',
            'first_name' => $parts[1] ?? '',
            'middle_name' => $parts[2] ?? '',
        ];
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
        $s = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], (string) $value);
        return (float) $s;
    }

    protected function nullableDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $this->parseDecimal($value);
    }

    protected function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        foreach (['d.m.Y H:i', 'd.m.Y H:i:s', 'd.m.Y', 'Y-m-d H:i:s', 'Y-m-d'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $value);
                if ($d !== false) {
                    return $d;
                }
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
}
