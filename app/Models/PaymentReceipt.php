<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'provider',
        'status',
        'receipt_data',
        'error_message'
    ];

    protected $casts = [
        'receipt_data' => 'array'
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
} 