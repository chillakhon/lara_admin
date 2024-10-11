<?php

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Notifiable;
    use HasUuids;
    protected $guarded = false;
    protected $table = 'clients';
    public $timestamps = false;
    protected $fillable = [
        'system_id',
        'telegraph_chat_id',
        'tg_id',
        'username',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'telegraph_chat_id');
    }
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function getDisplayName(): string
    {
        if ($this->username) {
            return '@' . $this->username;
        } elseif ($this->first_name || $this->last_name) {
            return trim($this->first_name . ' ' . $this->last_name);
        } else {
            return 'Client #' . $this->tg_id;
        }
    }

    public function routeNotificationForTelegraph()
    {
        return $this->telegraph_chat_id;
    }
}
