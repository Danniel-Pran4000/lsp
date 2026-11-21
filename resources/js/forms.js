function setFieldsVisible(section, visible) {
    section.hidden = !visible;
    for (const input of section.querySelectorAll('input')) {
        input.disabled = !visible;
        input.required = visible;
    }
}

const garageForm = document.querySelector('[data-garage-form]');
if (garageForm) {
    const type = garageForm.elements.namedItem('type');
    const electricity = garageForm.querySelector('[data-electricity-rate]');
    const update = () => setFieldsVisible(electricity, type.value === 'E');
    type.addEventListener('change', update);
    update();
}

const rentalForm = document.querySelector('[data-rental-form]');
if (rentalForm) {
    const field = name => rentalForm.elements.namedItem(name);
    const vehicle = field('vehicle_category');
    const duration = field('rental_type');
    const garage = field('garage_type');
    const unit = field('garage_id');
    const status = rentalForm.querySelector('[data-availability-status]');
    const review = rentalForm.querySelector('[data-review]');
    const previewUrl = rentalForm.action;
    const payment = rentalForm.querySelector('[data-inline-payment]');
    const paymentFields = payment.querySelector('[data-payment-fields]');
    const paymentSummary = payment.querySelector('[data-payment-summary]');
    const paymentStatus = payment.querySelector('[data-payment-status]');
    const paymentPrompt = paymentStatus.textContent;
    const quoteId = field('quote_id');
    const availabilityFields = ['vehicle_category', 'rental_type', 'starts_on', 'months', 'days', 'garage_type'];
    let pending;
    let quoting = false;
    let revision = 0;
    payment.hidden = false;

    function resetQuote() {
        revision++;
        paymentFields.disabled = true;
        paymentSummary.hidden = true;
        quoteId.value = '';
        field('proof').value = '';
        payment.querySelector('[data-proof-name]').textContent = 'Belum ada gambar yang dipilih.';
        paymentStatus.textContent = paymentPrompt;
        review.textContent = 'Tinjau pembayaran';
        rentalForm.action = previewUrl;
    }

    function updateFields() {
        const monthly = duration.value === 'monthly';
        setFieldsVisible(rentalForm.querySelector('[data-plate]'), vehicle.value !== 'bicycle');
        setFieldsVisible(rentalForm.querySelector('[data-battery]'), vehicle.value === 'electric_car');
        setFieldsVisible(rentalForm.querySelector('[data-months]'), monthly);
        const days = field('days');
        days.required = !monthly;
        days.min = monthly ? '0' : '1';
        days.max = monthly ? '3652058' : '30';
        rentalForm.querySelector('[data-days-label]').textContent = monthly ? 'Tambahan hari (opsional)' : 'Jumlah hari';

        const compatible = vehicle.selectedOptions[0].dataset.garageTypes.split(',');
        for (const option of garage.options) {
            option.disabled = !compatible.includes(option.value);
            option.hidden = option.disabled;
        }
        if (!compatible.includes(garage.value)) garage.value = compatible[0];
    }

    async function loadUnits() {
        pending?.abort();
        const selected = unit.value || unit.dataset.selectedUnit;
        unit.replaceChildren(new Option('Pilih unit', ''));
        unit.disabled = true;
        review.disabled = true;

        const inputs = availabilityFields.map(field).filter(input => !input.disabled);
        if (!inputs.every(input => input.validity.valid)) {
            status.textContent = 'Isi tanggal dan durasi yang valid untuk melihat unit yang tersedia.';
            return;
        }

        const request = new AbortController();
        pending = request;
        status.textContent = 'Memeriksa ketersediaan unit…';
        const data = new URLSearchParams(inputs.map(input => [input.name, input.value]));
        data.set('_token', field('_token').value);

        try {
            const response = await fetch(rentalForm.dataset.availabilityUrl, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: data,
                signal: request.signal,
            });
            const result = await response.json();
            if (request.signal.aborted) return;
            if (!response.ok) {
                status.textContent = Object.values(result.errors ?? {}).flat()[0]
                    ?? 'Ketersediaan belum dapat diperiksa. Muat ulang halaman lalu coba lagi.';
                return;
            }
            for (const available of result.units) {
                unit.add(new Option(available.code, available.id));
            }
            unit.value = result.units.some(available => String(available.id) === selected) ? selected : '';
            unit.disabled = result.units.length === 0;
            review.disabled = unit.disabled || quoting;
            status.textContent = result.units.length
                ? `Tanggal selesai ${result.end}.`
                : `Tanggal selesai ${result.end}. Tidak ada unit kosong pada periode ini.`;
        } catch {
            if (!request.signal.aborted) {
                status.textContent = 'Ketersediaan belum dapat diperiksa. Muat ulang halaman lalu coba lagi.';
            }
        }
    }

    rentalForm.addEventListener('change', event => {
        if (!availabilityFields.includes(event.target.name)) return;
        resetQuote();
        if (event.target === duration) field('days').value = '';
        updateFields();
        loadUnits();
    });

    rentalForm.addEventListener('input', event => {
        if (['brand', 'vehicle_model', 'plate', 'battery_kwh', 'remaining_percent', 'garage_id'].includes(event.target.name)) {
            resetQuote();
        }
    });

    rentalForm.addEventListener('submit', async event => {
        if (quoteId.value) return;
        event.preventDefault();
        if (quoting || unit.disabled || !unit.value) return;

        const original = revision;
        quoting = true;
        review.disabled = true;
        paymentStatus.textContent = 'Menghitung biaya sewa…';
        try {
            const response = await fetch(previewUrl, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(rentalForm),
            });
            const result = await response.json();
            if (revision !== original) return;
            if (!response.ok) {
                paymentStatus.textContent = Object.values(result.errors ?? {}).flat()[0]
                    ?? 'Rincian pembayaran belum dapat ditampilkan.';
                return;
            }

            paymentSummary.innerHTML = result.summary;
            paymentSummary.hidden = false;
            paymentFields.disabled = false;
            quoteId.value = result.quoteId;
            paymentStatus.textContent = 'Periksa rincian biaya lalu unggah bukti pembayaran.';
            review.textContent = 'Konfirmasi sewa';
            rentalForm.action = rentalForm.dataset.storeUrl;
        } catch {
            if (revision === original) paymentStatus.textContent = 'Rincian pembayaran belum dapat ditampilkan. Silakan coba lagi.';
        } finally {
            quoting = false;
            review.disabled = unit.disabled;
        }
    });
    updateFields();
    loadUnits();
}

for (const filter of document.querySelectorAll('[data-auto-filter]')) {
    filter.addEventListener('change', () => filter.form.requestSubmit());
}

for (const input of document.querySelectorAll('[data-proof-input]')) {
    const name = input.closest('[data-proof-picker]').querySelector('[data-proof-name]');
    input.addEventListener('change', () => {
        name.textContent = input.files[0]?.name ?? 'Belum ada gambar yang dipilih.';
    });
}
