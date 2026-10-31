<?php

namespace App\Http\Requests;

use App\Support\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currency = Currency::supports($this->input('currency')) ? $this->input('currency') : null;

        return [
            'currency' => ['required', Rule::in(Currency::codes())],
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,'.($currency ? Currency::decimals($currency) : 2),
                function (string $attribute, mixed $value, \Closure $fail) use ($currency) {
                    if (! $currency || ! is_numeric($value)) {
                        return;
                    }

                    [$min, $max] = Currency::limits($currency);

                    if ((float) $value < $min || (float) $value > $max) {
                        $fail('Please enter an amount between '.Currency::format($min, $currency, 0).' and '.Currency::format($max, $currency, 0).'.');
                    }
                },
            ],
            'platform_fee' => [
                'nullable',
                'numeric',
                function (string $attribute, mixed $value, \Closure $fail) use ($currency) {
                    // Either nothing, or exactly the fee shown for this currency.
                    if ($currency && (float) $value != 0 && abs((float) $value - Currency::platformFee($currency)) > 0.001) {
                        $fail('The platform fee is not valid.');
                    }
                },
            ],
            'frequency' => ['required', Rule::in(['once', 'monthly'])],
            'payment_method' => [
                'required',
                Rule::in(['gcash', 'paypal', 'bank']),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($this->input('frequency') === 'monthly' && $value !== 'paypal') {
                        $fail('Monthly donations are only available via PayPal.');
                    }
                },
            ],
            'donor_type' => ['required', Rule::in(['individual', 'organization'])],
            'org_name' => ['required_if:donor_type,organization', 'nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:10'],
            'other_country' => ['nullable', 'required_if:country,other', 'string', 'max:100'],
            'receipt_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
