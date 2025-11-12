<?php

namespace App\Console\Commands;

use App\Services\Email\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;
use Exception;

class SyncEmailMessages extends Command
{
    protected $signature = 'email:sync';
    protected $description = 'Синхронизировать входящие письма из IMAP (Yandex 360 / Mail.ru)';

    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    public function handle()
    {
        try {
            $this->info('🔄 Синхронизация писем началась...');

            // Подключаемся через Webklex IMAP Client
            $client = Client::account('default');
            $client->connect();

            // Получаем папку INBOX
            $inbox = $client->getFolder('INBOX');

            // Получаем непрочитанные письма за последние 30 дней
            $emails = $inbox->search()
                ->unseen()
                ->since(now()->subDays(30))
                ->get();

            if ($emails->isEmpty()) {
                $this->info('✅ Новых писем не найдено');
                return Command::SUCCESS;
            }

            $this->info("📧 Найдено писем: " . $emails->count());

            // Проверяем первый запуск
            $processedCount = $this->getProcessedEmailCount();
            $isFirstRun = $processedCount === 0;

            // Если первый запуск — берём только последние 20
            if ($isFirstRun) {
                $emails = $emails->slice(-20);
                $this->info("📧 Первый запуск: обработаю последние 20 писем");
            }

            // Обрабатываем каждое письмо
            foreach ($emails as $email) {
                try {
                    $this->processEmail($email);
                    $this->info("✓ Письмо от {$email->getFrom()[0]->mail} обработано");
                } catch (Exception $e) {
                    $this->error("✗ Ошибка при обработке письма: " . $e->getMessage());
                    Log::error("SyncEmailMessages: Error processing email", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $client->disconnect();
            $this->info('✅ Синхронизация завершена успешно');
            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error('❌ Критическая ошибка: ' . $e->getMessage());
            Log::error("SyncEmailMessages: Critical error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Обработать одно письмо
     */
    protected function processEmail($email)
    {
        // Извлекаем данные письма
        $fromEmail = $email->getFrom()[0]->mail;
        $subject = $email->getSubject();
        $body = $email->getHTMLBody() ?? $email->getTextBody();
        $messageId = $email->getMessageId();

        // Получаем вложения
//        $attachments = $this->getEmailAttachments($email);
        $attachments = [];

        // Отправляем в EmailService
        $data = [
            'from' => $fromEmail,
            'subject' => $subject,
            'text' => $body,
            'message_id' => $messageId,
            'attachments' => $attachments
        ];

        $result = $this->emailService->handleIncomingEmail($data);

        if (!($result['ok'] ?? false)) {
            throw new Exception($result['error'] ?? 'Unknown error');
        }

        // Помечаем письмо как прочитанное
        $email->setFlag(['Seen']);
    }

    /**
     * Получить вложения из письма
     */
    protected function getEmailAttachments($email)
    {
        $attachments = [];

        try {
            foreach ($email->getAttachments() as $attachment) {

                // Генерируем уникальное имя файла
                $fileName = uniqid() . '_' . $attachment->getName();
                $filePath = 'public/attachments/emails/' . $fileName;

                // Создаём папку если её нет
                if (!file_exists('storage/app/public/attachments/emails/')) {
                    mkdir('storage/app/public/attachments/emails/', 0755, true);
                }

                // Получаем содержимое файла и сохраняем
                $content = $attachment->getAttributes()['content'] ?? null;

                if ($content) {
                    file_put_contents(storage_path('app/' . $filePath), $content);

                    $attachments[] = [
                        'filename' => $attachment->getName(),
                        'url' => '/storage/attachments/emails/' . $fileName,
                        'id' => $attachment->getId(),
                        'mime_type' => $attachment->getMimeType()
                    ];
                }
            }
        } catch (Exception $e) {
            Log::warning("Error getting attachments", ['error' => $e->getMessage()]);
        }

        return $attachments;
    }

    /**
     * Получить количество уже обработанных писем
     */
    protected function getProcessedEmailCount()
    {
        return \App\Models\Conversation::where('source', 'email')->count();
    }
}
