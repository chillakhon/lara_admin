<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'name',
        'rating'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
} 