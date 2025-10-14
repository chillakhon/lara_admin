<?php

namespace App\Services\ContactRequest;

use App\Models\ContactRequest;
use App\Models\MailSetting;
use App\Notifications\NewContactRequestNotification;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ContactRequestService
{
    public function handleNewContactRequest(ContactRequest $contactRequest): void
    {
        $this->sendEmailNotification($contactRequest);
//        $this->sendTelegramNotification($contactRequest);
    }

    private function sendEmailNotification(ContactRequest $contactRequest): void
    {
        $emailData = [
            'id' => $contactRequest->id,
            'name' => $contactRequest->name,
            'email' => $contactRequest->email,
            'phone' => $contactRequest->phone,
            'message' => $contactRequest->message,
            'created_at' => $contactRequest->created_at->format('d.m.Y H:i'),
        ];


        $settings = MailSetting::first();

        if (!$settings || empty($settings->notification_email)) {
            Log::warning('Notification email not configured in mail_settings table.');
            return;
        }

        Notification::route('mail', $settings->notification_email)
            ->notify(new NewContactRequestNotification($emailData));
    }

    private function sendTelegramNotification(ContactRequest $contactRequest): void
    {
        if (!empty($contactRequest->client_id)) {
            $telegramService = new TelegramNotificationService();
            $telegramService->sendContactRequestNotificationToClient($contactRequest);
        }
    }
}
