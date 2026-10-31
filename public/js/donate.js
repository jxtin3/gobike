// Countries whose currency we offer; eurozone countries all map to EUR.
const DONATE_REGION_CURRENCY = {
    PH: 'PHP', US: 'USD', GB: 'GBP', AU: 'AUD', CA: 'CAD', NZ: 'NZD', SG: 'SGD', JP: 'JPY', HK: 'HKD',
    AT: 'EUR', BE: 'EUR', CY: 'EUR', DE: 'EUR', EE: 'EUR', ES: 'EUR', FI: 'EUR', FR: 'EUR', GR: 'EUR', HR: 'EUR',
    IE: 'EUR', IT: 'EUR', LT: 'EUR', LU: 'EUR', LV: 'EUR', MT: 'EUR', NL: 'EUR', PT: 'EUR', SI: 'EUR', SK: 'EUR',
};

const DONATE_CURRENCY_KEY = 'donate.currency';

function donateApp(config = {}) {
    const currencies = config.currencies ?? { USD: { name: 'US Dollar', symbol: '$', decimals: 2, presets: [25, 50, 165, 1000], min: 2, max: 10000, platform_fee: 1.88 } };

    return {
        step: 1,
        freq: 'once',
        currencies,
        rates: config.rates ?? { USD: 1 },
        currency: Object.keys(currencies)[0],
        amount: 0,
        customMode: false,
        customAmount: '',
        donorType: 'individual',
        coverFee: 'yes',
        paymentMethod: 'gcash',
        checkoutUrl: config.checkoutUrl ?? '/donate/checkout',
        isSubmitting: false,
        submitError: '',
        form: {
            orgName: '', firstName: '', lastName: '', email: '',
            phone: '', address1: '', address2: '',
            city: '', postcode: '', state: '', country: 'PH', otherCountry: '',
        },

        init() {
            if (config.paidDonation) {
                this.applyPaidDonation(config.paidDonation);
            } else if (config.cancelledDonation) {
                this.applyCancelledDonation(config.cancelledDonation);
            } else {
                this.currency = this.guessCurrency();
                this.amount = this.presets[0];
            }
        },

        applyPaidDonation(donation) {
            this.applyAmounts(donation);
            this.step = 4;
        },

        applyCancelledDonation(donation) {
            this.applyAmounts(donation);
            this.paymentMethod = donation.payment_method;
            this.freq = donation.frequency;
            this.donorType = donation.donor_type;
            this.form.orgName = donation.org_name ?? '';
            this.form.firstName = donation.first_name;
            this.form.lastName = donation.last_name;
            this.form.email = donation.email;
            this.form.phone = donation.phone ?? '';
            this.form.address1 = donation.address1;
            this.form.address2 = donation.address2 ?? '';
            this.form.city = donation.city;
            this.form.postcode = donation.postcode;
            this.form.state = donation.state;
            this.form.country = donation.country;
            this.form.otherCountry = donation.other_country ?? '';
            this.submitError = config.cancelMessage ?? 'Payment was cancelled. You can try again.';
            this.step = 3;
        },

        // Older donations have no currency/amount and were in USD.
        applyAmounts(donation) {
            this.currency = this.currencies[donation.currency] ? donation.currency : 'USD';
            const amount = parseFloat(donation.amount ?? donation.amount_usd);
            const fee = parseFloat(donation.platform_fee ?? donation.platform_fee_usd);

            if (this.presets.includes(amount)) {
                this.amount = amount;
            } else {
                this.customMode = true;
                this.customAmount = String(amount);
                this.amount = amount;
            }
            this.coverFee = fee > 0 ? 'yes' : 'no';
        },

        /** The visitor's last pick on this device, else their browser region, else the default. */
        guessCurrency() {
            try {
                const saved = localStorage.getItem(DONATE_CURRENCY_KEY);
                if (saved && this.currencies[saved]) return saved;
            } catch (e) { /* storage blocked */ }

            for (const tag of navigator.languages ?? [navigator.language]) {
                try {
                    const region = new Intl.Locale(tag).maximize().region;
                    const code = DONATE_REGION_CURRENCY[region];
                    if (code && this.currencies[code]) return code;
                } catch (e) { /* unknown language tag */ }
            }

            return Object.keys(this.currencies)[0];
        },

        setCurrency(code) {
            if (!this.currencies[code] || code === this.currency) return;

            // Keep the same preset position (2nd stays 2nd); a typed amount means nothing in another currency.
            const index = this.customMode ? 0 : Math.max(0, this.presets.indexOf(this.amount));
            this.currency = code;
            this.customMode = false;
            this.customAmount = '';
            this.amount = this.presets[index];

            try { localStorage.setItem(DONATE_CURRENCY_KEY, code); } catch (e) { /* storage blocked */ }
        },

        get currencyInfo() {
            return this.currencies[this.currency];
        },

        get presets() {
            return this.currencyInfo.presets;
        },

        get platformFee() {
            return this.coverFee === 'yes' ? this.currencyInfo.platform_fee : 0;
        },

        get effectiveAmount() {
            return this.customMode ? (parseFloat(this.customAmount) || 0) : this.amount;
        },

        get totalAmount() {
            return this.effectiveAmount + this.platformFee;
        },

        /** Estimate only; the server converts with its own rates when the donation is made. */
        convert(amount, to) {
            return amount / (this.rates[this.currency] ?? 1) * (this.rates[to] ?? 1);
        },

        get amountPhp() {
            return this.convert(this.totalAmount, 'PHP');
        },

        /** Each currency has its own round min/max (config/donation.php); the server checks the same. */
        get amountError() {
            const amount = this.effectiveAmount;
            if (!this.customMode || this.customAmount === '') return '';

            const { decimals, min, max } = this.currencyInfo;
            if (decimals === 0 && !Number.isInteger(amount)) {
                return `Please enter a whole amount in ${this.currency}.`;
            }

            if (amount < min || amount > max) {
                return `Please enter an amount between ${this.format(min, 0)} and ${this.format(max, 0)}.`;
            }

            return '';
        },

        /** e.g. "$25.00", "₱1,450.00", "¥4,000". Pass decimals = 0 for whole-number labels. */
        format(amount, decimals = this.currencyInfo.decimals) {
            const number = Number(amount || 0).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });

            return this.currencyInfo.symbol + number;
        },

        formatPhp(amount) {
            return '₱' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        selectPreset(amt) {
            this.amount = amt;
            this.customMode = false;
            this.customAmount = '';
        },

        setFrequency(value) {
            this.freq = value;
            if (value === 'monthly') {
                this.paymentMethod = 'paypal';
            }
        },

        goStep2() {
            if (this.effectiveAmount > 0 && !this.amountError) this.step = 2;
        },

        goStep3() {
            if (this.step2Valid()) this.step = 3;
        },

        step2Valid() {
            const f = this.form;
            if (this.donorType === 'organization' && !f.orgName) return false;
            if (f.country === 'other' && !f.otherCountry) return false;
            return f.firstName && f.lastName && f.email && f.address1 && f.city && f.postcode && f.state;
        },

        step3Valid() {
            return !!this.paymentMethod && !this.isSubmitting;
        },

        paymentMethodLabel() {
            const labels = { gcash: 'GCash', paypal: 'PayPal', bank: 'Online Banking' };
            return labels[this.paymentMethod] ?? 'Payment';
        },

        async submit() {
            if (!this.step3Valid()) return;

            this.isSubmitting = true;
            this.submitError = '';

            const payload = {
                currency: this.currency,
                amount: this.effectiveAmount,
                platform_fee: this.platformFee,
                frequency: this.freq,
                payment_method: this.paymentMethod,
                donor_type: this.donorType,
                org_name: this.form.orgName,
                first_name: this.form.firstName,
                last_name: this.form.lastName,
                email: this.form.email,
                phone: this.form.phone,
                address1: this.form.address1,
                address2: this.form.address2,
                city: this.form.city,
                postcode: this.form.postcode,
                state: this.form.state,
                country: this.form.country,
                other_country: this.form.country === 'other' ? this.form.otherCountry : null,
            };

            try {
                const response = await fetch(this.checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message ?? 'Checkout failed. Please try again.');
                }

                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                    return;
                }

                throw new Error('No redirect URL received.');
            } catch (error) {
                this.submitError = error.message ?? 'Something went wrong. Please try again.';
                this.isSubmitting = false;
            }
        },

        copyLink() {
            navigator.clipboard.writeText(window.location.href).catch(() => { });
        },
    };
}
