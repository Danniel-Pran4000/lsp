@extends('layouts.header')
@section('title', 'Tambah unit garasi')

@section('content')
    <form method="post" action="{{ route('garages.store-batch') }}" data-garage-form>
        @csrf

        <div class="grid gap-6 lg:grid-cols-2">
            <fieldset class="min-w-0 rounded-xl border border-stone-200 p-4 sm:p-5">
                <legend class="px-2 text-sm font-semibold">Rincian unit</legend>
                <label class="block text-sm font-medium">Tipe garasi
                    <select name="type" class="mt-2 w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm">
                        @foreach ($types as $code => $label)
                            <option value="{{ $code }}" @selected($type === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="mt-4 block text-sm font-medium">Jumlah unit
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="quantity" min="1" max="{{ $maxUnits }}" value="{{ old('quantity') }}" required>
                </label>
            </fieldset>

            <fieldset class="min-w-0 space-y-4 rounded-xl border border-stone-200 p-4 sm:p-5">
                <legend class="px-2 text-sm font-semibold">Tarif</legend>
                <label class="block text-sm font-medium">Tarif harian (Rp)
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="daily_rate" min="0" max="999999999999" step="1" value="{{ old('daily_rate') }}" required>
                </label>

                {{-- JavaScript mengaktifkan input ini saat tipe garasi mobil listrik dipilih. --}}
                <label class="block text-sm font-medium" data-electricity-rate>Tarif per kWh (Rp)
                    <span class="block text-xs text-stone-600">Khusus garasi mobil listrik.</span>
                    <input class="mt-2 block w-full rounded-lg border border-stone-300 bg-white px-3 py-2.5 text-sm text-neutral-900 focus:outline-2 focus:outline-offset-2 focus:outline-red-700 disabled:bg-stone-100" type="number" name="kwh_rate" min="0" max="999999999999" step="1"
                        value="{{ old('kwh_rate') }}">
                </label>
            </fieldset>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-4">
            <a class="text-sm font-medium text-red-700 underline-offset-4 hover:underline" href="{{ route('garages.index') }}">Kembali ke garasi</a>
            <button class="inline-block rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700" type="submit">Simpan unit</button>
        </div>
    </form>
@endsection
