<?php

namespace App\Contracts;

interface MessageSourceAdapter
{
    public function sendMessage(string $externalId, string $content, array $attachments = []): bool;
    public function markAsRead(string $externalId): bool;
    public function getSourceName(): string;
} 