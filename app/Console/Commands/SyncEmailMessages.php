<?php

namespace App\Console\Commands;

use App\Services\Email\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

            $client = Client::account('default');
            $client->connect();

            $inbox = $client->getFolder('INBOX');

            $emails = $inbox->search()
                ->unseen()
                ->since(now()->subDays(30))
                ->get();

            if ($emails->isEmpty()) {
                $this->info('✅ Новых писем не найдено');
                return Command::SUCCESS;
            }

            $this->info("📧 Найдено писем: " . $emails->count());

            $processedCount = $this->getProcessedEmailCount();
            $isFirstRun = $processedCount === 0;

            if ($isFirstRun) {
                $emails = $emails->slice(-20);
                $this->info("📧 Первый запуск: обработаю последние 20 писем");
            }

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

    protected function processEmail($email)
    {
        $fromEmail = $email->getFrom()[0]->mail;
        $subject = $email->getSubject();
        $body = $email->getHTMLBody() ?? $email->getTextBody();
        $messageId = $email->getMessageId();

        // ← РАСКОММЕНТИРОВАЛИ!
        $attachments = $this->getEmailAttachments($email);

        $data = [
            'from' => $fromEmail,
            'subject' => $subject,
            'text' => $body,
            'message_id' => $messageId,
            'attachments' => $attachments // ← Передаем вложения
        ];

        $result = $this->emailService->handleIncomingEmail($data);

        if (!($result['ok'] ?? false)) {
            throw new Exception($result['error'] ?? 'Unknown error');
        }

        $email->setFlag(['Seen']);
    }

    /**
     * Получить вложения из письма и сохранить в правильную структуру
     */
    protected function getEmailAttachments($email)
    {
        $attachments = [];

        try {
            foreach ($email->getAttachments() as $attachment) {
                $originalFileName = $attachment->getName();
                $mimeType = $attachment->getMimeType();

                // Определяем расширение
                $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
                if (!$extension) {
                    $extension = $this->guessExtensionFromMime($mimeType);
                }

                // Генерируем уникальное имя файла
                $hash = md5(time() . uniqid());
                $fileName = $hash . '.' . $extension;

                // Используем ту же структуру что и в FileStorageService
                $directory = 'chat-attachments/' . now()->format('Y/m');
                $filePath = $directory . '/' . $fileName;

                // Получаем содержимое файла
                $content = $attachment->getContent();

                if ($content) {
                    // Сохраняем в storage/app/public/
                    Storage::disk('public')->put($filePath, $content);

                    $attachments[] = [
                        'type' => $this->getAttachmentType($mimeType),
                        'url' => url('storage/' . $filePath),
                        'file_path' => $filePath,
                        'file_name' => $originalFileName,
                        'file_size' => strlen($content),
                        'mime_type' => $mimeType,
                    ];

                    Log::info("Email attachment saved", [
                        'original_name' => $originalFileName,
                        'saved_as' => $fileName,
                        'size' => strlen($content)
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error("Error getting email attachments", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $attachments;
    }

    /**
     * Определить тип вложения по MIME
     */
    protected function getAttachmentType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        return 'file';
    }

    /**
     * Угадать расширение по MIME типу
     */
    protected function guessExtensionFromMime(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/wav' => 'wav',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];

        return $mimeMap[$mimeType] ?? 'bin';
    }

    protected function getProcessedEmailCount()
    {
        return \App\Models\Conversation::where('source', 'email')->count();
    }
}
