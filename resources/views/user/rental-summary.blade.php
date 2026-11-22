<section class="rounded-xl border border-stone-200 p-5 sm:p-6" aria-labelledby="rental-summary">
    <h2 id="rental-summary" class="text-lg font-semibold">Ringkasan sewa</h2>
    <dl class="mt-4 divide-y divide-stone-100 text-sm">
        <div class="flex flex-wrap justify-between gap-2 py-3">
            <dt class="text-stone-600">Kode unit</dt>
            <dd class="rounded-md bg-stone-100 px-2 font-semibold">{{ $garage->code }}</dd>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-3">
            <dt class="text-stone-600">Periode sewa</dt>
            <dd class="font-medium">{{ $start->format('d/m/Y') }} sampai {{ $end->format('d/m/Y') }}</dd>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-3">
            <dt class="text-stone-600">Tarif harian</dt>
            <dd class="tabular-nums">{{ \App\Support\Rupiah::formatAmount($charge['daily_rate']) }}</dd>
        </div>
        <div class="flex flex-wrap justify-between gap-2 py-3">
            <dt class="text-stone-600">Biaya sewa</dt>
            <dd class="tabular-nums">{{ \App\Support\Rupiah::formatAmount($charge['rental_amount']) }}</dd>
        </div>
        @if ($charge['kwh_rate'] !== null)
            <div class="flex flex-wrap justify-between gap-2 py-3">
                <dt class="text-stone-600">Tarif per kWh</dt>
                <dd class="tabular-nums">{{ \App\Support\Rupiah::formatAmount($charge['kwh_rate']) }}</dd>
            </div>
            <div class="flex flex-wrap justify-between gap-2 py-3">
                <dt class="text-stone-600">Estimasi energi pengisian</dt>
                <dd class="tabular-nums">{{ str_replace('.', ',', rtrim(rtrim($charge['energy_kwh'], '0'), '.')) }} kWh</dd>
            </div>
            <div class="flex flex-wrap justify-between gap-2 py-3">
                <dt class="text-stone-600">Estimasi biaya listrik</dt>
                <dd class="tabular-nums">{{ \App\Support\Rupiah::formatAmount($charge['electricity_amount']) }}</dd>
            </div>
        @endif
        <div class="flex flex-wrap justify-between gap-2 py-3 font-semibold text-red-800">
            <dt>Total pembayaran</dt>
            <dd class="tabular-nums">{{ \App\Support\Rupiah::formatAmount($charge['initial_total']) }}</dd>
        </div>
    </dl>
</section>
