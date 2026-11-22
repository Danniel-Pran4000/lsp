// Nonaktifkan input yang tersembunyi agar nilai dari kategori sebelumnya tidak ikut dikirim.
function toggleFields(section, visible) {
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
    // Tarif per kWh hanya berlaku untuk unit garasi listrik.
    const toggleRate = () => toggleFields(electricity, type.value === 'E');
    type.addEventListener('change', toggleRate);
    toggleRate();
}

const rentalForm = document.querySelector('[data-rental-form]');
if (rentalForm) {
    // Ambil kontrol berdasarkan nama input yang juga digunakan server.
    const findField = name => rentalForm.elements.namedItem(name);
    const vehicle = findField('vehicle_category');
    const duration = findField('rental_type');
    const garage = findField('garage_type');
    const unit = findField('garage_id');
    const status = rentalForm.querySelector('[data-availability-status]');
    const review = rentalForm.querySelector('[data-review]');
    const previewUrl = rentalForm.action;
    const payment = rentalForm.querySelector('[data-inline-payment]');
    const paymentFields = payment.querySelector('[data-payment-fields]');
    const paymentSummary = payment.querySelector('[data-payment-summary]');
    const paymentStatus = payment.querySelector('[data-payment-status]');
    const paymentPrompt = paymentStatus.textContent;
    const quoteId = findField('quote_id');
    const availabilityFields = ['vehicle_category', 'rental_type', 'starts_on', 'months', 'days', 'garage_type'];
    let pending;
    let quoting = false;
    let revision = 0;
    payment.hidden = false;

    // Batalkan pratinjau lama agar bukti bayar selalu terkait dengan rincian terbaru.
    function resetQuote() {
        revision++;
        paymentFields.disabled = true;
        paymentSummary.hidden = true;
        quoteId.value = '';
        findField('proof').value = '';
        payment.querySelector('[data-proof-name]').textContent = 'Belum ada gambar yang dipilih.';
        paymentStatus.textContent = paymentPrompt;
        review.textContent = 'Tinjau pembayaran';
        rentalForm.action = previewUrl;
    }

    // Sesuaikan isian wajib dan pilihan garasi saat kendaraan atau durasi berubah.
    function refreshFields() {
        const monthly = duration.value === 'monthly';
        toggleFields(rentalForm.querySelector('[data-plate]'), vehicle.value !== 'bicycle');
        toggleFields(rentalForm.querySelector('[data-battery]'), vehicle.value === 'electric_car');
        toggleFields(rentalForm.querySelector('[data-months]'), monthly);
        const days = findField('days');
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

    // Muat unit untuk periode terbaru dan abaikan respons pencarian yang sudah digantikan.
    async function loadUnits() {
        pending?.abort();
        const selected = unit.value || unit.dataset.selectedUnit;
        unit.replaceChildren(new Option('Pilih unit', ''));
        unit.disabled = true;
        review.disabled = true;

        const inputs = availabilityFields.map(findField).filter(input => !input.disabled);
        if (!inputs.every(input => input.validity.valid)) {
            status.textContent = 'Isi tanggal dan durasi yang valid untuk melihat unit yang tersedia.';
            return;
        }

        const request = new AbortController();
        pending = request;
        status.textContent = 'Memeriksa ketersediaan unit…';
        const data = new URLSearchParams(inputs.map(input => [input.name, input.value]));
        data.set('_token', findField('_token').value);

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

    // Perubahan periode atau tipe memicu pencarian ulang karena unit yang tersedia bisa ikut berubah.
    rentalForm.addEventListener('change', event => {
        if (!availabilityFields.includes(event.target.name)) return;
        resetQuote();
        if (event.target === duration) findField('days').value = '';
        refreshFields();
        loadUnits();
    });

    // Detail kendaraan atau unit yang berubah membuat pratinjau dan bukti sebelumnya kedaluwarsa.
    rentalForm.addEventListener('input', event => {
        if (['brand', 'vehicle_model', 'plate', 'battery_kwh', 'remaining_percent', 'garage_id'].includes(event.target.name)) {
            resetQuote();
        }
    });

    // Kiriman pertama meminta pratinjau. ID pratinjau mengizinkan kiriman berikutnya menyimpan sewa.
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
    refreshFields();
    loadUnits();
}

// Kirim filter saat pilihan berubah agar daftar langsung mengikuti nilai dropdown.
for (const filter of document.querySelectorAll('[data-auto-filter]')) {
    filter.addEventListener('change', () => filter.form.requestSubmit());
}

for (const input of document.querySelectorAll('[data-proof-input]')) {
    const name = input.closest('[data-proof-picker]').querySelector('[data-proof-name]');
    // Tampilkan nama berkas agar pengguna dapat memeriksa bukti sebelum mengirim formulir.
    input.addEventListener('change', () => {
        name.textContent = input.files[0]?.name ?? 'Belum ada gambar yang dipilih.';
    });
}
