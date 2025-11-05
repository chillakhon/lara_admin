<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'direction',
        'content',
        'content_type',
        'status',
        'source_data'
    ];

    protected $casts = [
        'source_data' => 'array'
    ];

    // Добавим константы для enum значений
    const DIRECTION_INCOMING = 'incoming';
    const DIRECTION_OUTGOING = 'outgoing';

    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';

    const CONTENT_TYPE_TEXT = 'text';
    const CONTENT_TYPE_IMAGE = 'image';
    const CONTENT_TYPE_FILE = 'file';
    const CONTENT_TYPE_VOICE = 'voice';
    const CONTENT_TYPE_VIDEO = 'video';

    // Валидация значений enum
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($message) {
            $validDirections = ['incoming', 'outgoing'];
            $validStatuses = ['sending', 'sent', 'delivered', 'read', 'failed'];
            $validContentTypes = ['text', 'image', 'file', 'voice', 'video'];

            if (!in_array($message->direction, $validDirections)) {
                throw new \InvalidArgumentException("Invalid direction value: {$message->direction}");
            }

            if (!in_array($message->status, $validStatuses)) {
                throw new \InvalidArgumentException("Invalid status value: {$message->status}");
            }

            if (!in_array($message->content_type, $validContentTypes)) {
                throw new \InvalidArgumentException("Invalid content_type value: {$message->content_type}");
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }
}
