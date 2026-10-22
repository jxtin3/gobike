<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Donation extends Model
{
    protected $fillable = [
        'uuid',
        'donor_type',
        'org_name',
        'first_name', 'last_name',
        'email',
        'phone',
        'address1', 'address2',
        'city',
        'postcode',
        'state', 'country', 'other_country',
        'amount_usd', 'amount_php',
        'platform_fee_usd',
        'frequency',
        'payment_method',
        'status',
        'paypal_order_id', 'paypal_subscription_id',
        'subscription_status', 'cancelled_at',
        'paymongo_session_id', 'paymongo_payment_id',
        'receipt_email',
    ];

    /** Readable labels for a monthly donation's PayPal subscription state. */
    public const SUBSCRIPTION_LABELS = [
        'active' => 'Active',
        'suspended' => 'Paused (payment failed)',
        'cancelled' => 'Cancelled',
        'expired' => 'Ended',
    ];

    /** Pill colour for each payment status in the admin. */
    public const STATUS_PILLS = [
        'paid' => 'ok',
        'pending' => 'warn',
        'failed' => 'bad',
        'cancelled' => 'neutral',
    ];

    public const METHOD_LABELS = [
        'paypal' => 'PayPal',
        'gcash' => 'GCash',
        'bank' => 'Online banking',
    ];

    protected function casts(): array
    {
        return [
            'amount_usd' => 'decimal:2',
            'amount_php' => 'decimal:2',
            'platform_fee_usd' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DonationPayment::class);
    }

    /** A confirmed monthly donation backed by a PayPal subscription. */
    public function isMonthlySubscription(): bool
    {
        return $this->frequency === 'monthly'
            && $this->status === 'paid'
            && filled($this->paypal_subscription_id);
    }

    /** Monthly donations can be cancelled while PayPal may still charge them. */
    public function canCancelSubscription(): bool
    {
        return $this->isMonthlySubscription()
            && in_array($this->subscription_status ?? 'active', ['active', 'suspended'], true);
    }

    public function getSubscriptionLabelAttribute(): ?string
    {
        if (! $this->isMonthlySubscription()) {
            return null;
        }

        return self::SUBSCRIPTION_LABELS[$this->subscription_status ?? 'active'] ?? ucfirst((string) $this->subscription_status);
    }

    /** Short reference donors can quote when they contact us, e.g. GBD-3F2A9C1D. */
    public function getReferenceAttribute(): string
    {
        return 'GBD-'.strtoupper(substr(str_replace('-', '', (string) $this->uuid), 0, 8));
    }

    protected static function booted(): void
    {
        static::creating(function (Donation $donation) {
            if (empty($donation->uuid)) {
                $donation->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getTotalUsdAttribute(): float
    {
        return (float) $this->amount_usd + (float) $this->platform_fee_usd;
    }

    public function getStatusPillAttribute(): string
    {
        return self::STATUS_PILLS[$this->status] ?? 'neutral';
    }

    public function getSubscriptionPillAttribute(): string
    {
        return ['active' => 'ok', 'suspended' => 'warn'][$this->subscription_status ?? 'active'] ?? 'neutral';
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHOD_LABELS[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return $this->frequency === 'monthly' ? 'Monthly' : 'One-time';
    }

    public function getFullAddressAttribute(): string
    {
        $country = strtolower((string) $this->country) === 'other' && $this->other_country ? $this->other_country : $this->country;

        return collect([$this->address1, $this->address2, $this->city, trim("{$this->state} {$this->postcode}"), $country])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');
    }

    public function getDonorNameAttribute(): string
    {
        if ($this->donor_type === 'organization' && $this->org_name) {
            return $this->org_name;
        }

        return trim("{$this->first_name} {$this->last_name}");
    }

    public function markPaid(?string $gatewayPaymentId = null): void
    {
        $updates = ['status' => 'paid'];

        if ($gatewayPaymentId && $this->payment_method !== 'paypal') {
            $updates['paymongo_payment_id'] = $gatewayPaymentId;
        }

        $this->update($updates);
    }
}
