<?php

namespace App\Services;

use App\Support\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Exchange rates for donation currencies, as units per 1 USD.
 *
 * Rates are fetched once a day. If the rates API is down we keep using the last rates
 * we fetched, and before the first successful fetch the fallback rates in config.
 * Checkout never fails because of rates.
 */
class ExchangeRateService
{
    private const CACHE_KEY = 'donation.rates';

    private const LAST_GOOD_KEY = 'donation.rates.last_good';

    /** @var array<string, float>|null */
    private ?array $rates = null;

    /** @return array<string, float> e.g. ['USD' => 1.0, 'PHP' => 58.2, ...] */
    public function rates(): array
    {
        return $this->rates ??= array_merge($this->fallbackRates(), $this->liveRates());
    }

    public function rate(string $currency): float
    {
        return $this->rates()[$currency] ?? throw new RuntimeException("No exchange rate for {$currency}.");
    }

    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        return $amount / $this->rate($from) * $this->rate($to);
    }

    /** @return array<string, float> */
    private function liveRates(): array
    {
        if (! config('donation.live_rates')) {
            return [];
        }

        $rates = Cache::get(self::CACHE_KEY);

        if (is_array($rates)) {
            return $rates;
        }

        try {
            $rates = $this->fetch();
            Cache::put(self::CACHE_KEY, $rates, now()->addDay());
            Cache::forever(self::LAST_GOOD_KEY, $rates);

            return $rates;
        } catch (Throwable $e) {
            Log::warning('Could not fetch donation exchange rates', ['error' => $e->getMessage()]);

            // Try again in an hour rather than on every page view.
            $rates = Cache::get(self::LAST_GOOD_KEY, []);
            Cache::put(self::CACHE_KEY, $rates, now()->addHour());

            return $rates;
        }
    }

    /** @return array<string, float> */
    private function fetch(): array
    {
        $targets = array_values(array_diff(Currency::codes(), ['USD']));

        $response = Http::timeout(5)->acceptJson()->get(config('donation.rates_url'), [
            'from' => 'USD',
            'to' => implode(',', $targets),
        ]);

        $rates = $response->throw()->json('rates');

        if (! is_array($rates)) {
            throw new RuntimeException('Unexpected rates response.');
        }

        // Keep only sensible positive numbers for currencies we offer.
        return collect($rates)
            ->only($targets)
            ->filter(fn ($rate) => is_numeric($rate) && $rate > 0)
            ->map(fn ($rate) => (float) $rate)
            ->put('USD', 1.0)
            ->all();
    }

    /** @return array<string, float> */
    private function fallbackRates(): array
    {
        return collect(config('donation.currencies'))
            ->map(fn (array $currency) => (float) $currency['fallback_rate'])
            ->all();
    }
}
