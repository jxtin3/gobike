<?php

namespace App\Http\Requests;

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
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:10000'],
            'platform_fee' => ['nullable', 'numeric', 'min:0', 'max:1000'],
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
