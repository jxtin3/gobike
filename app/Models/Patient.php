<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patient extends Model
{
    /**
     * The abnormal readings the admin screens for. One definition, used by the dashboard counts,
     * the patient list filter and the pills on each record. `sql` and abnormalReadings() must agree.
     */
    public const READINGS = [
        'bp_high' => ['label' => 'High BP', 'phrase' => 'high blood pressure', 'sql' => 'sys >= 140 OR dia >= 90'],
        'bp_low' => ['label' => 'Low BP', 'phrase' => 'low blood pressure', 'sql' => 'sys < 90 OR dia < 60'],
        'pulse_high' => ['label' => 'Fast pulse', 'phrase' => 'a fast pulse', 'sql' => 'pulse > 100'],
        'pulse_low' => ['label' => 'Slow pulse', 'phrase' => 'a slow pulse', 'sql' => 'pulse < 60'],
        'resp_high' => ['label' => 'Fast breathing', 'phrase' => 'fast breathing', 'sql' => 'resp > 20'],
        'resp_low' => ['label' => 'Slow breathing', 'phrase' => 'slow breathing', 'sql' => 'resp < 12'],
        'temp_high' => ['label' => 'High temp', 'phrase' => 'a high temperature', 'sql' => 'temp >= 37.5'],
        'temp_low' => ['label' => 'Low temp', 'phrase' => 'a low temperature', 'sql' => 'temp < 36'],
    ];

    protected $fillable = [
        'user_id', 'barangay', 'name', 'address', 'contact', 'age',
        'sys', 'dia', 'pulse', 'resp', 'temp', 'height', 'weight', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'age' => 'integer',
            'sys' => 'integer',
            'dia' => 'integer',
            'pulse' => 'integer',
            'resp' => 'integer',
            'temp' => 'float',
            'height' => 'float',
            'weight' => 'float',
        ];
    }

    /**
     * Check-ups carry no patient ID, so the same person is recognised by name and contact number
     * (ignoring case and surrounding spaces).
     */
    private const PERSON_SQL = "LOWER(TRIM(name)), TRIM(COALESCE(contact, ''))";

    /** Keep one check-up per person: their most recently saved one among the rows already filtered. */
    public function scopeOnePerPerson(Builder $query): Builder
    {
        $latest = (clone $query)->toBase()
            ->cloneWithout(['columns', 'orders', 'limit', 'offset'])
            ->selectRaw('MAX(id)')
            ->groupByRaw(self::PERSON_SQL);

        return $query->whereIn('id', $latest);
    }

    /** Check-ups with one of the abnormal readings in READINGS. */
    public function scopeWithReading(Builder $query, string $reading): Builder
    {
        return $query->whereRaw('('.self::READINGS[$reading]['sql'].')');
    }

    /**
     * Which abnormal readings this check-up has, as reading key => short label.
     *
     * @return array<string, string>
     */
    public function abnormalReadings(): array
    {
        $found = [
            'bp_high' => $this->sys >= 140 || $this->dia >= 90,
            'bp_low' => $this->sys < 90 || $this->dia < 60,
            'pulse_high' => $this->pulse > 100,
            'pulse_low' => $this->pulse < 60,
            'resp_high' => $this->resp > 20,
            'resp_low' => $this->resp < 12,
            'temp_high' => $this->temp >= 37.5,
            'temp_low' => $this->temp < 36,
        ];

        return collect(self::READINGS)
            ->filter(fn ($definition, $key) => $found[$key])
            ->map(fn ($definition) => $definition['label'])
            ->all();
    }

    /** The GoBiker who recorded this check-up. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
