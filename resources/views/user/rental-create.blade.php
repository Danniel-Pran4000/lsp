@extends('layouts.header')
@section('title', 'Sewa baru')

@section('content')
    @php
        $field = 'mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm focus:border-red-700 focus:outline-red-700 disabled:bg-stone-100';
    @endphp

    <form method="post" action="{{ route('rentals.preview') }}" enctype="multipart/form-data" data-rental-form
        data-availability-url="{{ route('rentals.available') }}" data-store-url="{{ route('rentals.store') }}" class="space-y-6">
        @csrf

        <div class="grid gap-6 lg:grid-cols-2">
            <fieldset class="min-w-0 space-y-4 rounded-xl border border-stone-200 p-5">
                <legend class="px-2 text-sm font-semibold">Data kendaraan</legend>

                <label class="block text-sm font-medium">Kategori kendaraan
                    <select class="{{ $field }}" name="vehicle_category">
                        @foreach ($vehicles as $category => $label)
                            <option value="{{ $category }}" data-garage-types="{{ implode(',', $compatibleGarages[$category]) }}"
                                @selected(old('vehicle_category', $details['vehicle_category'] ?? 'bicycle') === $category)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium">Merek
                        <input class="{{ $field }}" name="brand" maxlength="255" value="{{ old('brand', $details['brand'] ?? '') }}" required>
                    </label>
                    <label class="block text-sm font-medium">Model kendaraan
                        <input class="{{ $field }}" name="vehicle_model" maxlength="255" value="{{ old('vehicle_model', $details['vehicle_model'] ?? '') }}" required>
                    </label>
                </div>

                <label class="block text-sm font-medium" data-plate>Nomor polisi
                    <input class="{{ $field }}" name="plate" maxlength="255" value="{{ old('plate', $details['plate'] ?? '') }}">
                </label>

                <fieldset data-battery>
                    <legend class="text-sm font-medium">Baterai mobil listrik</legend>
                    <div class="mt-2 grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-medium">Kapasitas (kWh)
                            <input class="{{ $field }}" type="number" name="battery_kwh" min="0.001" max="9999999.999" step="0.001" value="{{ old('battery_kwh', $details['battery_kwh'] ?? '') }}">
                        </label>
                        <label class="block text-sm font-medium">Sisa baterai (%)
                            <input class="{{ $field }}" type="number" name="remaining_percent" min="0" max="100" step="0.01" value="{{ old('remaining_percent', $details['remaining_percent'] ?? '') }}">
                        </label>
                    </div>
                </fieldset>
            </fieldset>

            <fieldset class="min-w-0 space-y-4 rounded-xl border border-stone-200 p-5">
                <legend class="px-2 text-sm font-semibold">Periode dan unit</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium">Tanggal mulai
                        <input class="{{ $field }}" name="starts_on" type="date" min="{{ today()->toDateString() }}" max="9999-12-31"
                            value="{{ old('starts_on', $details['starts_on'] ?? $start->toDateString()) }}" required>
                    </label>
                    <label class="block text-sm font-medium">Tipe sewa
                        <select class="{{ $field }}" name="rental_type">
                            @foreach ($durations as $duration => $label)
                                <option value="{{ $duration }}" @selected(old('rental_type', $details['rental_type'] ?? 'daily') === $duration)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium" data-months>Jumlah bulan
                        <input class="{{ $field }}" name="months" type="number" min="1" max="119987" step="1" value="{{ old('months', $details['months'] ?? '') }}">
                    </label>
                    <label class="block text-sm font-medium"><span data-days-label>Jumlah hari</span>
                        <input class="{{ $field }}" name="days" type="number" min="0" step="1" value="{{ old('days', $details['days'] ?? '') }}">
                    </label>
                    <label class="block text-sm font-medium">Tipe garasi
                        <select class="{{ $field }}" name="garage_type">
                            @foreach ($garageLabels as $type => $label)
                                <option value="{{ $type }}" @selected(old('garage_type', $selectedGarageType ?? $details['garage_type'] ?? 'S') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium">Unit garasi
                        <select class="{{ $field }}" name="garage_id" required data-selected-unit="{{ old('garage_id', $details['garage_id'] ?? '') }}">
                            <option value="">Pilih unit</option>
                            @foreach ($garages ?? [] as $garage)
                                <option value="{{ $garage->id }}" @selected((string) old('garage_id', $details['garage_id'] ?? '') === (string) $garage->id)>{{ $garage->code }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <p class="text-sm text-stone-600" role="status" data-availability-status>
                    @if ($end)
                        Tanggal selesai {{ $end->format('d/m/Y') }}.
                        @if (isset($garages) && $garages->isEmpty()) Tidak ada unit kosong pada periode ini. @endif
                    @else
                        Isi tanggal dan durasi untuk melihat unit yang tersedia.
                    @endif
                </p>
                <noscript>
                    <button class="rounded-lg border px-3 py-2 text-sm" type="submit" formaction="{{ route('rentals.available') }}" formnovalidate>Cari unit tersedia</button>
                </noscript>
            </fieldset>
        </div>

        <section class="rounded-xl border border-red-100 bg-red-50/50 p-5" data-inline-payment hidden>
            <h2 class="text-lg font-semibold">Pembayaran</h2>
            <p class="mt-2 text-sm text-stone-600" role="status" data-payment-status>Tinjau biaya sebelum mengunggah bukti pembayaran.</p>
            <div class="mt-4 grid gap-6 lg:grid-cols-2">
                <div data-payment-summary hidden></div>
                <fieldset data-payment-fields disabled>
                    <input type="hidden" name="quote_id" value="">
                    @include('layouts.payment-proof', ['id' => 'inline-payment-proof'])
                </fieldset>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-4">
            <a class="text-sm font-medium text-red-700 hover:underline" href="{{ route('rentals.index') }}">Kembali ke sewa saya</a>
            <button data-review class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:bg-stone-300" type="submit">Tinjau pembayaran</button>
        </div>
    </form>
@endsection
