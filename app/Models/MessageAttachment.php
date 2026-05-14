<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'type',
        'url',
        'file_path',
        'file_name',
        'file_size',
        'mime_type'
    ];


    protected static function boot()
    {
        parent::boot();

        // При удалении attachment - удаляем файл с диска
        static::deleting(function ($attachment) {
            if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
