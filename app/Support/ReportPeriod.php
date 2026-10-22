<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * A week (Monday to Sunday) or a calendar month chosen on a report page,
 * from ?period=week|month&date=YYYY-MM-DD. Defaults to the current month.
 */
class ReportPeriod
{
    public const TYPES = ['week' => 'Week', 'month' => 'Month'];

    private function __construct(
        public readonly string $type,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:week,month'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $type = $data['period'] ?? 'month';
        $anchor = isset($data['date']) ? CarbonImmutable::parse($data['date']) : CarbonImmutable::now();

        return self::for($type, $anchor);
    }

    public static function for(string $type, CarbonImmutable $anchor): self
    {
        return $type === 'week'
            ? new self('week', $anchor->startOfWeek(CarbonImmutable::MONDAY), $anchor->endOfWeek(CarbonImmutable::SUNDAY))
            : new self('month', $anchor->startOfMonth(), $anchor->endOfMonth());
    }

    /** "October 2026" or "Oct 20 – 26, 2026". */
    public function label(): string
    {
        if ($this->type === 'month') {
            return $this->from->format('F Y');
        }

        if ($this->from->year !== $this->to->year) {
            return $this->from->format('M j, Y').' – '.$this->to->format('M j, Y');
        }

        return $this->from->month === $this->to->month
            ? $this->from->format('M j').' – '.$this->to->format('j, Y')
            : $this->from->format('M j').' – '.$this->to->format('M j, Y');
    }

    /** Query string for this period, merged with the other filters on the page. */
    public function query(array $extra = []): array
    {
        return array_filter(['period' => $this->type, 'date' => $this->from->toDateString(), ...$extra], fn ($value) => filled($value));
    }

    public function previous(): self
    {
        return self::for($this->type, $this->type === 'week' ? $this->from->subWeek() : $this->from->subMonth());
    }

    public function next(): ?self
    {
        $next = self::for($this->type, $this->type === 'week' ? $this->from->addWeek() : $this->from->addMonth());

        return $next->from->isFuture() ? null : $next;
    }

    public function switchTo(string $type): self
    {
        // Switching to a week shows the week of today when this month is the current one.
        $anchor = $this->to->isFuture() ? CarbonImmutable::now() : $this->from;

        return self::for($type, $anchor);
    }

    public function isCurrent(): bool
    {
        return CarbonImmutable::now()->betweenIncluded($this->from, $this->to);
    }

    /**
     * Rows of the breakdown table: each day of a week, or each week of a month.
     *
     * @return list<array{label: string, from: CarbonImmutable, to: CarbonImmutable}>
     */
    public function buckets(): array
    {
        $buckets = [];

        if ($this->type === 'week') {
            for ($day = $this->from; $day->lte($this->to); $day = $day->addDay()) {
                $buckets[] = ['label' => $day->format('D, M j'), 'from' => $day->startOfDay(), 'to' => $day->endOfDay()];
            }

            return $buckets;
        }

        for ($start = $this->from; $start->lte($this->to); $start = $end->addDay()->startOfDay()) {
            $end = $start->endOfWeek(CarbonImmutable::SUNDAY)->min($this->to);
            $buckets[] = [
                'label' => $start->day === $end->day ? $start->format('M j') : $start->format('M j').' – '.$end->format('j'),
                'from' => $start->startOfDay(),
                'to' => $end->endOfDay(),
            ];
        }

        return $buckets;
    }
}
