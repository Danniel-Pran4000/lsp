@extends('layouts.account')
@section('title', 'Buat unit garasi')

@section('content')
    @if (session('garage_codes'))
        <p>Kode unit yang dibuat</p>
        <p>{{ implode(', ', session('garage_codes')) }}</p>
    @endif

    <form method="post" action="{{ route('garages.store-batch') }}">
        @csrf
        <label for="type">Tipe garasi</label>
        <select id="type" name="type" required>
            <option value="S" @selected(old('type') === 'S')>Sepeda</option>
            <option value="C" @selected(old('type') === 'C')>Mobil bensin</option>
            <option value="E" @selected(old('type') === 'E')>Mobil listrik</option>
        </select>
        <label>Jumlah unit
            <input type="number" name="quantity" min="1" max="999" value="{{ old('quantity') }}" required>
        </label>
        <label>Tarif harian (Rp)
            <input type="number" name="daily_rate" min="0" max="999999999999.99" step="0.01" value="{{ old('daily_rate') }}" required>
        </label>
        <label id="kwh-rate-field">Tarif per kWh (Rp, khusus mobil listrik)
            <input type="number" name="kwh_rate" min="0" max="999999999999.99" step="0.01" value="{{ old('kwh_rate') }}">
        </label>
        <button type="submit">Buat unit</button>
    </form>
    <p><a href="{{ route('registration.pending') }}">Verifikasi pendaftar</a></p>

    <script>
        const garageType = document.querySelector('#type');
        const electricityRateField = document.querySelector('#kwh-rate-field');
        const electricityRate = electricityRateField.querySelector('input');

        const updateElectricityField = () => {
            const needsElectricityRate = garageType.value === 'E';
            electricityRateField.hidden = !needsElectricityRate;
            electricityRate.disabled = !needsElectricityRate;
            electricityRate.required = needsElectricityRate;
        };

        garageType.addEventListener('change', updateElectricityField);
        updateElectricityField();
    </script>
@endsection
