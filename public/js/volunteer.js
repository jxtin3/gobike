/** Phone photos are often 5-10 MB: scale them down so the upload is quick and fits the limit. */
async function shrinkImage(file) {
    if (!file.type.startsWith('image/')) return file;

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, 1800 / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close?.();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        if (!blob || blob.size >= file.size) return file;

        return new File([blob], file.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' });
    } catch (e) {
        return file;
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.data('volunteerForm', () => {
        const blank = () => ({
            firstName: '',
            lastName: '',
            email: '',
            phone: '',
            age: '',
            status: '',
            school: '',
            barangay: '',
            municipality: '',
            province: 'Pangasinan',
            idType: '',
            availability: '',
            motivation: '',
            emergencyName: '',
            emergencyPhone: '',
            guardianName: '',
            guardianPhone: '',
            guardianConsent: false,
            agree: false,
        });

        return {
            form: blank(),
            loading: false,
            submitted: false,
            // Place in line for the next volunteer class, from the server.
            position: null,
            classMin: 20,
            classMax: 25,
            error: false,
            errorMessage: 'Something went wrong. Please try again.',

            // The uploaded IDs / barangay certificates (each with a preview), and what is wrong with them.
            idFiles: [],
            idError: '',
            idMax: 5,

            /**
             * The form is much taller than the thank-you card, so when one replaces the other the page
             * gets shorter and the browser leaves the visitor near the footer. Bring the card back into
             * view and move focus into it, so screen readers announce it too.
             */
            showCard(target) {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        this.$root.scrollIntoView({ block: 'start', behavior: calm ? 'auto' : 'smooth' });
                        this.$refs[target]?.focus({ preventScroll: true });
                    });
                });
            },

            init() {
                // Reset the form only when the user chooses to submit another one
                this.$watch('submitted', (value) => {
                    if (!value) {
                        this.form = blank();
                        this.clearIds();
                    }
                });
            },

            /** Adds the chosen files to the list. Choosing again adds more, up to idMax. */
            async pickIds(event) {
                const input = event.target;
                const picked = Array.from(input.files ?? []);
                input.value = '';
                if (!picked.length) return;

                this.idError = '';
                const problems = [];

                for (const file of picked) {
                    if (this.idFiles.length >= this.idMax) {
                        problems.push(`You can upload up to ${this.idMax} files.`);
                        break;
                    }
                    if (!['image/jpeg', 'image/png', 'image/webp', 'application/pdf'].includes(file.type)) {
                        problems.push(`${file.name} is not a JPG, PNG, WEBP or PDF.`);
                        continue;
                    }

                    const ready = await shrinkImage(file);
                    if (ready.size > 5 * 1024 * 1024) {
                        problems.push(`${file.name} is over 5 MB.`);
                        continue;
                    }
                    if (this.idFiles.some((f) => f.file.name === ready.name && f.file.size === ready.size)) continue;

                    this.idFiles.push({
                        file: ready,
                        preview: ready.type.startsWith('image/') ? URL.createObjectURL(ready) : null,
                    });
                }

                this.idError = problems.join(' ');
            },

            removeId(index) {
                const [gone] = this.idFiles.splice(index, 1);
                if (gone?.preview) URL.revokeObjectURL(gone.preview);
                this.idError = '';
            },

            clearIds() {
                this.idFiles.forEach((f) => f.preview && URL.revokeObjectURL(f.preview));
                this.idFiles = [];
                this.idError = '';
                const input = document.getElementById('v-id-file');
                if (input) input.value = '';
            },

            sizeLabel(file) {
                const kb = file.size / 1024;
                return kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.max(1, Math.round(kb)) + ' KB';
            },

            // 'empty' | 'young' (<13) | 'minor' (13-17) | 'ok' (18-25) | 'old' (>25)
            get ageState() {
                const n = parseInt(this.form.age, 10);
                if (Number.isNaN(n)) return 'empty';
                if (n < 13) return 'young';
                if (n < 18) return 'minor';
                if (n <= 25) return 'ok';
                return 'old';
            },

            get isMinor() {
                return this.ageState === 'minor';
            },

            get canSubmit() {
                return this.ageState === 'minor' || this.ageState === 'ok';
            },

            async submitForm() {
                if (!this.canSubmit) return;

                if (!this.idFiles.length) {
                    this.idError = 'Please upload your ID or barangay certificate.';
                    document.getElementById('v-id-file')?.focus();
                    return;
                }

                this.error = false;
                this.loading = true;

                try {
                    const body = new FormData();
                    Object.entries(this.form).forEach(([key, value]) => {
                        body.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value);
                    });
                    this.idFiles.forEach(({ file }) => body.append('idDocuments[]', file, file.name));

                    // No Content-Type header: the browser sets it, with the multipart boundary.
                    const res = await fetch('/volunteer', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body,
                    });

                    if (res.status === 422) {
                        const data = await res.json().catch(() => ({}));
                        const errors = data.errors ?? {};
                        const idKey = Object.keys(errors).find((k) => k.startsWith('idDocuments') || k === 'idType');
                        if (idKey) {
                            this.idError = errors[idKey][0];
                            document.getElementById('v-id-file')?.focus();
                        }
                        this.error = true;
                        this.errorMessage = Object.values(errors)[0]?.[0] ?? 'Please check your details and try again.';
                        return;
                    }

                    if (!res.ok) throw new Error('Request failed');

                    const data = await res.json().catch(() => ({}));
                    this.position = data.position ?? null;
                    this.classMin = data.class_min ?? this.classMin;
                    this.classMax = data.class_max ?? this.classMax;
                    this.submitted = true;
                    this.showCard('thanks');
                    // this.form = blank();
                } catch (e) {
                    this.error = true;
                    this.errorMessage = 'We could not send your application. Please try again later.';
                } finally {
                    this.loading = false;
                }
            },
        };
    });
});