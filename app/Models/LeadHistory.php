<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadHistory extends Model
{
    protected $table = 'lead_histories';

    protected $fillable = [
        'lead_id',
        'user_id',
        'status',
        'comment'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 