<?php

namespace App\Console\Commands\Import;

use App\Models\Client;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportClientsFromCsv extends Command
{
    protected $signature = 'import:clients {--limit=0 : Максимум клиентов для импорта (0 = без лимита)}';
    protected $description = 'Импортировать клиентов из CSV файла';

    // Путь к файлу
//    protected $csvPath = '/Users/chilla/Desktop/clients_data-utf8.csv';
    protected $csvPath = '/var/www/html/laravel/storage/imports/clients_data-utf8.csv';
    public function handle()
    {
        $this->info('🚀 Начинаем импорт клиентов...');

        // Проверяем файл
        if (!file_exists($this->csvPath)) {
            $this->error("❌ Файл не найден: {$this->csvPath}");
            return 1;
        }

        $this->info("✅ Файл найден");

        // Открываем CSV
        $file = fopen($this->csvPath, 'r');

        // Читаем заголовок
        $header = fgetcsv($file, 0, "\t");
        $this->info("✅ Заголовок прочитан");

        // Создаем прогресс-бар
        $totalLines = $this->countLines($this->csvPath) - 1; // -1 за заголовок
        $progressBar = $this->output->createProgressBar($totalLines);
        $progressBar->start();

        $imported = 0;
        $errors = 0;
        $skipped = 0;
        $limit = (int) $this->option('limit');
        $count = 0;

        // Читаем каждую строку
        while (($row = fgetcsv($file, 0, "\t")) !== false) {
            // Проверяем лимит
            if ($limit > 0 && $count >= $limit) {
                break;
            }

            try {
                // Пропускаем если количество колонок не совпадает
                if (count($header) !== count($row)) {
                    Log::warning('Неверное количество колонок в строке', [
                        'expected' => count($header),
                        'got' => count($row),
                    ]);
                    $skipped++;
                    $progressBar->advance();
                    $count++;
                    continue;
                }

                $data = array_combine($header, $row);

                // Импортируем клиента
                if ($this->importClient($data)) {
                    $imported++;
                } else {
                    $errors++;
                }
            } catch (\Exception $e) {
                Log::error('Ошибка импорта строки', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errors++;
            }

            $progressBar->advance();
            $count++;
        }

        $progressBar->finish();
        fclose($file);

        $this->newLine(2);
        $this->info("✅ Импорт завершен!");
        $this->line("Успешно импортировано: <fg=green>{$imported}</>");
        $this->line("Ошибок: <fg=red>{$errors}</>");
        $this->line("Пропущено: <fg=yellow>{$skipped}</>");

        return 0;
    }

    protected function importClient($data)
    {
        // Получаем и очищаем email
        $email = trim($data['E-mail'] ?? '');

        if (empty($email)) {
            return false;
        }

        // Проверяем на дубликат
        if (Client::where('email', $email)->exists()) {
            Log::info('Клиент с таким email уже существует', ['email' => $email]);
            return false;
        }

        try {
            // 1. Создаем Client
            $client = Client::create([
                'email' => $email,
                'password' => null,
                'verification_code' => null,
                'verification_sent' => null,
                'verified_at' => null,
                'client_level_id' => null,
                'bonus_balance' => 0,
            ]);

            // 2. Собираем полный адрес
            $address = $this->buildAddress(
                $data['улица'] ?? '',
                $data['дом'] ?? '',
                $data['квартира'] ?? '',
                $data['адрес'] ?? ''
            );

            // 3. Нормализуем телефон
            $phone = $this->normalizePhone($data['Телефон'] ?? '');

            // 4. Получаем first_name и last_name
            $firstName = trim($data['Имя'] ?? '');
            $lastName = trim($data['Фамилия'] ?? '');

            // 5. Создаем UserProfile
            UserProfile::create([
                'client_id' => $client->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'address' => $address,
                'delivery_address' => $address,
                'delivery_country_id' => null,
                'delivery_city_id' => null,
                'delivery_postal_code' => trim($data['почтовый индекс'] ?? ''),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Ошибка при создании клиента', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function normalizePhone($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // Удаляем все кроме цифр и +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Если начинается с 8 и всего 11 цифр, заменяем на 7
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '8') {
            $phone = '7' . substr($phone, 1);
        }

        // Если нет +, добавляем
        if (strpos($phone, '+') === false && !empty($phone)) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    protected function buildAddress($street, $house, $apartment, $address)
    {
        $parts = array_filter([
            trim($street),
            trim($house),
            trim($apartment)
        ]);

        if (!empty($parts)) {
            return implode(', ', $parts);
        }

        return trim($address);
    }

    protected function countLines($filePath)
    {
        $count = 0;
        $file = fopen($filePath, 'r');
        while (!feof($file)) {
            $count += substr_count(fread($file, 8192), "\n");
        }
        fclose($file);
        return $count;
    }
}
