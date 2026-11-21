@extends('layouts.account')
@section('title', 'Sewa baru')

@section('content')
    @php
        $vehicles = [
            'bicycle' => 'Sepeda',
            'motorcycle' => 'Sepeda motor',
            'gas_car' => 'Mobil bensin',
            'electric_car' => 'Mobil listrik',
        ];
        $garageLabels = ['S' => 'Sepeda (S)', 'C' => 'Mobil bensin (C)', 'E' => 'Mobil listrik (E)'];
    @endphp

    <p>Tanggal mulai <strong>{{ $start->format('d/m/Y') }}</strong></p>
    <form method="post" action="{{ route('rentals.create') }}">
        @csrf
        <label for="vehicle-category">Kategori kendaraan</label>
        <select id="vehicle-category" name="vehicle_category" required>
            @foreach ($vehicles as $category => $label)
                <option value="{{ $category }}" @selected(old('vehicle_category', $details['vehicle_category'] ?? '') === $category)>{{ $label }}</option>
            @endforeach
        </select>
        <label>Merek
            <input name="brand" maxlength="255" value="{{ old('brand', $details['brand'] ?? '') }}" required>
        </label>
        <label>Model kendaraan
            <input name="vehicle_model" maxlength="255" value="{{ old('vehicle_model', $details['vehicle_model'] ?? '') }}" required>
        </label>
        <fieldset id="plate-fields">
            <legend>Selain sepeda</legend>
            <label>Nomor plat
                <input name="plate" maxlength="255" value="{{ old('plate', $details['plate'] ?? '') }}">
            </label>
        </fieldset>
        <fieldset id="battery-fields">
            <legend>Khusus mobil listrik</legend>
            <label>Kapasitas baterai (kWh)
                <input type="number" name="battery_kwh" min="0.001" step="0.001" value="{{ old('battery_kwh', $details['battery_kwh'] ?? '') }}">
            </label>
            <label>Sisa baterai (%)
                <input type="number" name="remaining_percent" min="0" max="100" step="0.01" value="{{ old('remaining_percent', $details['remaining_percent'] ?? '') }}">
            </label>
        </fieldset>
        <label for="rental-type">Tipe sewa</label>
        <select id="rental-type" name="rental_type" required>
            <option value="daily" @selected(old('rental_type', $details['rental_type'] ?? '') === 'daily')>Harian</option>
            <option value="monthly" @selected(old('rental_type', $details['rental_type'] ?? '') === 'monthly')>Bulanan</option>
        </select>
        <fieldset id="monthly-fields">
            <legend>Khusus sewa bulanan</legend>
            <label>Jumlah bulan
                <input type="number" name="months" min="1" step="1" value="{{ old('months', $details['months'] ?? '') }}">
            </label>
        </fieldset>
        <label for="days" id="days-label">Jumlah hari (tambahan hari untuk bulanan)</label>
        <input id="days" type="number" name="days" min="0" step="1" value="{{ old('days', $details['days'] ?? '') }}" required>
        <p id="daily-limit" data-max="{{ $start->daysInMonth - 1 }}">Batas sewa harian bulan ini {{ $start->daysInMonth - 1 }} hari.</p>
        <button type="submit">Hitung tanggal selesai</button>
    </form>

    @if ($end)
        <section aria-label="Hasil perhitungan">
            <p>Tanggal selesai <strong>{{ $end->format('d/m/Y') }}</strong></p>
            <form method="post" action="{{ route('rentals.available') }}">
                @csrf
                @foreach ($details as $field => $value)
                    @continue($field === 'garage_type')
                    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                @endforeach
                <label for="garage-type">Tipe garasi yang cocok</label>
                <select id="garage-type" name="garage_type" required>
                    @foreach ($garageTypes as $type)
                        <option value="{{ $type }}" @selected(($selectedGarageType ?? null) === $type)>{{ $garageLabels[$type] }}</option>
                    @endforeach
                </select>
                <button type="submit">Cari unit</button>
            </form>

            @isset($garages)
                @if ($garages->isEmpty())
                    <p role="status">Tidak ada unit {{ $garageLabels[$selectedGarageType] }} yang tersedia selama periode ini.</p>
                @else
                    <form method="post" action="{{ route('rentals.preview') }}">
                        @csrf
                        @foreach ($details as $field => $value)
                            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        @endforeach
                        <label for="garage-id">Pilih unit yang tersedia</label>
                        <select id="garage-id" name="garage_id" required>
                            <option value="">Pilih kode unit</option>
                            @foreach ($garages as $garage)
                                <option value="{{ $garage->id }}">{{ $garage->code }}</option>
                            @endforeach
                        </select>
                        <button type="submit">Lihat rincian biaya</button>
                    </form>
                @endif
            @endisset
        </section>
    @endif

    <p><a href="{{ route('account.show') }}">Kembali ke akun</a></p>

    <script>
        const category = document.querySelector('#vehicle-category');
        const rentalType = document.querySelector('#rental-type');
        const plateFields = document.querySelector('#plate-fields');
        const batteryFields = document.querySelector('#battery-fields');
        const monthlyFields = document.querySelector('#monthly-fields');
        const days = document.querySelector('#days');
        const dailyLimit = document.querySelector('#daily-limit');

        function updateFields() {
            plateFields.hidden = plateFields.disabled = category.value === 'bicycle';
            batteryFields.hidden = batteryFields.disabled = category.value !== 'electric_car';
            const isMonthly = rentalType.value === 'monthly';
            monthlyFields.hidden = monthlyFields.disabled = !isMonthly;
            dailyLimit.hidden = isMonthly;
            days.min = isMonthly ? '0' : '1';
            days.max = isMonthly ? '' : dailyLimit.dataset.max;
            document.querySelector('#days-label').textContent = isMonthly ? 'Tambahan hari' : 'Jumlah hari';
        }

        category.addEventListener('change', updateFields);
        rentalType.addEventListener('change', updateFields);
        updateFields();
    </script>
@endsection
