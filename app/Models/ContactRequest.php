<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $fillable = [
        'client_id', 'name', 'email', 'phone', 'message', 'source', 'status', 'meta', 'ip', 'user_agent', 'read_at'
    ];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
    ];


    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

}
