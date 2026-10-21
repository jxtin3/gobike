<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One charge of a monthly (PayPal subscription) donation, recorded from PayPal's webhook.
 */
class DonationPayment extends Model
{
    protected $fillable = [
        'donation_id',
        'gateway',
        'gateway_payment_id',
        'amount',
        'currency',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }
}
