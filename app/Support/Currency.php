<?php

namespace App\Support;

/**
 * The donation currencies from config/donation.php: their codes, rounding and display.
 */
class Currency
{
    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(config('donation.currencies'));
    }

    public static function default(): string
    {
        return self::codes()[0];
    }

    public static function supports(?string $code): bool
    {
        return is_string($code) && array_key_exists($code, config('donation.currencies'));
    }

    public static function decimals(string $code): int
    {
        return (int) (config("donation.currencies.{$code}.decimals") ?? 2);
    }

    public static function symbol(string $code): string
    {
        return (string) (config("donation.currencies.{$code}.symbol") ?? $code.' ');
    }

    public static function platformFee(string $code): float
    {
        return (float) config("donation.currencies.{$code}.platform_fee", 0);
    }

    /** @return array{0: float, 1: float} the smallest and largest donation accepted */
    public static function limits(string $code): array
    {
        return [
            (float) config("donation.currencies.{$code}.min"),
            (float) config("donation.currencies.{$code}.max"),
        ];
    }

    public static function round(float $amount, string $code): float
    {
        return round($amount, self::decimals($code));
    }

    /** The amount as a gateway expects it, e.g. "25.00" or "4000" for yen. */
    public static function toDecimalString(float $amount, string $code): string
    {
        return number_format($amount, self::decimals($code), '.', '');
    }

    /** e.g. "$25.00", "₱1,450.00", "¥4,000". Pass $decimals = 0 for whole-number labels like "₱500,000". */
    public static function format(float $amount, string $code, ?int $decimals = null): string
    {
        return self::symbol($code).number_format($amount, $decimals ?? self::decimals($code));
    }
}
