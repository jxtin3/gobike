function donateApp(config = {}) {
    return {
        step: 1,
        freq: 'once',
        presets: [25, 50, 165, 1000],
        amount: 25,
        customMode: false,
        customAmount: '',
        donorType: 'individual',
        platformFee: 1.88,
        paymentMethod: 'gcash',
        usdToPhp: config.usdToPhp ?? 58,
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
            }
        },

        applyPaidDonation(donation) {
            this.amount = parseFloat(donation.amount_usd);
            this.platformFee = parseFloat(donation.platform_fee_usd);
            this.customMode = false;
            this.step = 4;
        },

        applyCancelledDonation(donation) {
            this.amount = parseFloat(donation.amount_usd);
            this.platformFee = parseFloat(donation.platform_fee_usd);
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

        get effectiveAmount() {
            return this.customMode ? (parseFloat(this.customAmount) || 0) : this.amount;
        },

        get totalAmount() {
            return this.effectiveAmount + parseFloat(this.platformFee || 0);
        },

        get amountPhp() {
            return this.totalAmount * this.usdToPhp;
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
            if (this.effectiveAmount > 0) this.step = 2;
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
