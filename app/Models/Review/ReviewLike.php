<?php

namespace App\Models\Review;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'review_id',
    ];

    /**
     * Клиент, который поставил лайк
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Отзыв, которому поставлен лайк
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
