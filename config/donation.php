<?php

return [

    /*
        Donation Currencies

        Donors pick one of these on the donate page and PayPal charges them in it.
        GCash and online banking (PayMongo) always charge in PHP, converted at today's rate.

        The first currency is the default. For each one:
        - decimals:      0 for currencies without cents (PayPal rejects decimals for JPY)
        - presets:       the four amount buttons
        - min / max:     the smallest and largest donation accepted
        - platform_fee:  the optional "cover platform costs" amount
        - fallback_rate: units per 1 USD, used only when live rates can't be fetched
    */

    'currencies' => [
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'presets' => [25, 50, 165, 1000], 'min' => 2, 'max' => 10000, 'platform_fee' => 1.88, 'fallback_rate' => 1],
        'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱', 'decimals' => 2, 'presets' => [500, 1000, 5000, 10000], 'min' => 100, 'max' => 500000, 'platform_fee' => 100, 'fallback_rate' => (float) env('DONATION_USD_TO_PHP', 58.00)],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimals' => 2, 'presets' => [25, 50, 150, 1000], 'min' => 2, 'max' => 10000, 'platform_fee' => 1.75, 'fallback_rate' => 0.92],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'decimals' => 2, 'presets' => [20, 40, 130, 800], 'min' => 2, 'max' => 8000, 'platform_fee' => 1.50, 'fallback_rate' => 0.78],
        'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'decimals' => 2, 'presets' => [40, 80, 250, 1500], 'min' => 3, 'max' => 15000, 'platform_fee' => 2.90, 'fallback_rate' => 1.52],
        'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimals' => 2, 'presets' => [35, 70, 225, 1400], 'min' => 3, 'max' => 14000, 'platform_fee' => 2.60, 'fallback_rate' => 1.38],
        'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'decimals' => 2, 'presets' => [40, 85, 275, 1700], 'min' => 3, 'max' => 17000, 'platform_fee' => 3.20, 'fallback_rate' => 1.68],
        'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimals' => 2, 'presets' => [35, 70, 220, 1350], 'min' => 3, 'max' => 13000, 'platform_fee' => 2.50, 'fallback_rate' => 1.32],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 0, 'presets' => [4000, 8000, 25000, 150000], 'min' => 300, 'max' => 1500000, 'platform_fee' => 290, 'fallback_rate' => 150],
        'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'decimals' => 2, 'presets' => [200, 400, 1300, 8000], 'min' => 15, 'max' => 80000, 'platform_fee' => 15, 'fallback_rate' => 7.8],
    ],

    /*
        Exchange Rates

        Fetched once a day from Frankfurter (European Central Bank rates, no API key).
        Set DONATION_LIVE_RATES=false to always use the fallback rates above.
    */

    'live_rates' => (bool) env('DONATION_LIVE_RATES', true),
    'rates_url' => 'https://api.frankfurter.app/latest',

    /*
        Supported Banks (online banking via PayMongo)
    */

    'banks' => [
        'BPI',
        'UnionBank',
        'BDO',
        'Metrobank',
        'Landbank',
    ],

    /*
        PayPal monthly donation plans are created automatically, one per currency
        (see PayPalDonationService::planIdFor). PAYPAL_DONATION_PRODUCT_ID is an
        optional override to reuse an existing PayPal catalog product.
    */

    'paypal' => [
        'product_id' => env('PAYPAL_DONATION_PRODUCT_ID', ''),
    ],

];
