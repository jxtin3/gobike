<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * An inquiry from an organization that wants to replicate the Go Bike Project,
 * optionally with the Go Biker's Training fee paid early online through PayMongo.
 */
class ReplicationRequest extends Model
{
    public const ORGANIZATION_TYPES = [
        'sk' => 'Sangguniang Kabataan (SK)',
        'barangay' => 'Barangay',
        'lgu' => 'Municipal or City LGU',
        'school' => 'School or University',
        'youth_org' => 'Youth or Student Organization',
        'ngo' => 'NGO or Foundation',
        'other' => 'Other',
    ];

    public const STATUSES = ['pending', 'contacted', 'scheduled', 'completed', 'cancelled'];

    public const PAYMENT_STATUSES = ['unpaid', 'pending', 'paid', 'refunded'];

    protected $fillable = [
        'organization',
        'organization_type',
        'contact_name',
        'position',
        'email',
        'phone',
        'municipality',
        'province',
        'trainees',
        'preferred_date',
        'message',
        'amount_php',
        'payment_status',
        'paymongo_session_id',
        'paymongo_payment_id',
        'paid_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'trainees' => 'integer',
            'preferred_date' => 'date',
            'amount_php' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $inquiry) {
            $inquiry->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Training fee in pesos: one class covers up to `class_size` trainees,
     * and each trainee beyond that adds `additional_trainee_fee`.
     */
    public static function feeFor(int $trainees): int
    {
        $extra = max(0, $trainees - (int) config('replication.class_size'));

        return (int) config('replication.class_fee') + $extra * (int) config('replication.additional_trainee_fee');
    }

    /** Short reference people can quote when they contact us, e.g. GBT-3F2A9C1D. */
    public function getReferenceAttribute(): string
    {
        return 'GBT-'.strtoupper(substr(str_replace('-', '', (string) $this->uuid), 0, 8));
    }

    public function getOrganizationTypeLabelAttribute(): string
    {
        return self::ORGANIZATION_TYPES[$this->organization_type] ?? ucfirst((string) $this->organization_type);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canPayOnline(): bool
    {
        return ! $this->isPaid()
            && $this->payment_status !== 'refunded'
            && ! in_array($this->status, ['completed', 'cancelled'], true);
    }

    public function markPaid(?string $gatewayPaymentId = null): void
    {
        $this->update(array_filter([
            'payment_status' => 'paid',
            'paid_at' => $this->paid_at ?? now(),
            'paymongo_payment_id' => $gatewayPaymentId,
        ], fn ($value) => $value !== null));
    }
}
