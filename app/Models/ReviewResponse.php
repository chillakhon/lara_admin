<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewResponse extends Model
{
    use HasFactory, SoftDeletes;

    // protected $fillable = [
    //     'review_id',
    //     'user_id',
    //     'content',
    //     'is_published'
    // ];

    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean'
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function responded_to()
    {
        return $this->hasOne(ReviewResponse::class, 'id', 'review_response_id');
    }
}