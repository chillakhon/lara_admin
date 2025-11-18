<?php

namespace App\Services\Messaging\Adapters;

use App\Models\MailSetting;
use App\Services\Messaging\AbstractMessageAdapter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailAdapter extends AbstractMessageAdapter
{
    protected MailSetting $settings;

    public function __construct()
    {
        $this->settings = MailSetting::first();

        if (!$this->settings) {
            Log::error("EmailAdapter: MailSettings not found in database");
            throw new Exception("Email settings не найдены в БД");
        }

    }

    /**
     * Отправить сообщение по email с Reply-To
     * @param string $externalId - email адрес получателя
     * @param string $content - текст сообщения
     * @param array $attachments - вложения
     * @return bool
     */
    public function sendMessage(string $externalId, string $content, array $attachments = []): bool
    {
        try {
            $to = $externalId;
            $htmlContent = nl2br(e($content));
            $unsubscribeUrl = url('/api/public/unsubscribe');

            $html = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto;'>
                    {$htmlContent}
                    <hr style='margin-top: 30px; border: none; border-top: 1px solid #ddd;'>
                    <p style='font-size: 12px; color: #999; margin-top: 20px;  text-align: center;'>
                        <a href='{$unsubscribeUrl}' style='color: #0066cc; text-decoration: none;'>
                            Отписаться от рассылки
                        </a>
                    </p>
                </div>
            </body>
            </html>
        ";

            Mail::html($html, function ($message) use ($to) {
                $message->to($to)
                    ->from($this->settings->from_address)
                    ->subject('Re: Ответ от поддержки');
            });

            return true;

        } catch (Exception $e) {
            Log::error("EmailAdapter: Exception while sending message", [
                'to' => $externalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Отметить сообщение как прочитанное
     */
    public function markAsRead(string $externalId): bool
    {
        // Email не имеет встроенного API для отметки как прочитано
        return true;
    }

    public function getSourceName(): string
    {
        return 'email';
    }


}
