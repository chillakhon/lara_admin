<?php

namespace App\Console\Commands\Import;

use App\Models\Client;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewResponse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ImportReviewsFromCsv extends Command
{
    protected $signature = 'import:reviews';
    protected $description = 'Импортировать отзывы из CSV файла';

    // Путь к файлу
//    protected $csvPath = '/Users/chilla/Desktop/reviews-data-utf8.csv';
    protected $csvPath = '/var/www/html/laravel/storage/imports/reviews-data-utf8.csv';
    public function handle()
    {
        $this->info('🚀 Начинаем импорт отзывов...');

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
        $totalLines = $this->countLines($this->csvPath) - 1;
        $progressBar = $this->output->createProgressBar($totalLines);
        $progressBar->start();

        $imported = 0;
        $errors = 0;
        $skipped = 0;

        // Читаем каждую строку
        while (($row = fgetcsv($file, 0, "\t")) !== false) {
            try {
                // Пропускаем если количество колонок не совпадает
                if (count($header) !== count($row)) {
                    Log::warning('Неверное количество колонок в строке', [
                        'expected' => count($header),
                        'got' => count($row),
                    ]);
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $data = array_combine($header, $row);

                // Импортируем отзыв
                if ($this->importReview($data)) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                Log::error('Ошибка импорта строки', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        fclose($file);

        $this->newLine(2);
        $this->info("✅ Импорт завершен!");
        $this->line("Успешно импортировано: <fg=green>{$imported}</>");
        $this->line("Пропущено: <fg=yellow>{$skipped}</>");
        $this->line("Ошибок: <fg=red>{$errors}</>");

        return 0;
    }

    protected function importReview($data)
    {
        // 1. Проверяем рейтинг
        $rating = trim($data['Рейтинг'] ?? '');
        if (empty($rating) || $rating === 'nan' || !is_numeric($rating)) {
            return false;
        }
        $rating = (int) $rating;

        // 2. Ищем клиента по email
        $email = trim($data['E-mail автора'] ?? '');
        if (empty($email)) {
            Log::warning('Пропущен отзыв: нет email автора');
            return false;
        }

        $client = Client::where('email', $email)->first();
        if (!$client) {
            Log::info('Клиент не найден', ['email' => $email]);
            return false;
        }

        // 3. Ищем товар по первому слову названия
        $productName = trim($data['Название Товара'] ?? '');
        if (empty($productName)) {
            Log::warning('Пропущен отзыв: нет названия товара');
            return false;
        }

        // Берем первое слово из названия
        $firstWord = explode(' ', $productName)[0];

        $product = Product::where('name', 'like', '%' . $firstWord . '%')->first();
        if (!$product) {
            Log::info('Товар не найден', ['product_name' => $productName, 'search_word' => $firstWord]);
            return false;
        }

        try {
            // 4. Парсим дату публикации
            $publishedAt = $this->parseDate($data['Дата и время публикации'] ?? '');
            if (!$publishedAt) {
                $publishedAt = now(); // Если дата не распарсилась, берем текущее время
            }
            // 5. Определяем опубликован ли отзыв
            $isPublished = trim($data['Опубликованность'] ?? '') === 'Да';

            // 6. Создаем Review
            $review = Review::create([
                'client_id' => $client->id,
                'reviewable_type' => Product::class,
                'reviewable_id' => $product->id,
                'content' => trim($data['Комментарий'] ?? ''),
                'rating' => $rating,
                'is_published' => $isPublished,
                'is_verified' => $isPublished,
                'status' => $isPublished ? Review::STATUS_PUBLISHED : Review::STATUS_NEW,
                'published_at' => $isPublished ? $publishedAt : null,
                'created_at' => $publishedAt,
            ]);

            // 7. Если есть ответ менеджера, создаём ReviewResponse
            $managerResponse = trim($data['Ответ менеджера'] ?? '');
            if (!empty($managerResponse)) {
                $responseDate = $this->parseDate($data['Дата и время ответа'] ?? '');

                ReviewResponse::create([
                    'review_id' => $review->id,
                    'user_id' => 1, // Admin user
                    'content' => $managerResponse,
                    'is_published' => true,
                    'created_at' => $responseDate ?? now(),
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Ошибка при создании отзыва', [
                'email' => $email,
                'product_name' => $productName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            // Формат: "19.05.2024 18:36" -> Carbon
            return Carbon::createFromFormat('d.m.Y H:i', trim($dateString));
        } catch (\Exception $e) {
            Log::warning('Не удалось распарсить дату', [
                'date' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
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
