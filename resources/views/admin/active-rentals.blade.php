@extends('layouts.header')
@section('title', 'Perpanjang sewa')

@section('content')
    <h2 class="mt-8 mb-4 text-lg font-semibold">Sewa aktif</h2>
    <div class="my-5 overflow-x-auto rounded-lg border border-stone-200">
        <table class="w-full min-w-max text-left text-sm">
            <thead>
                <tr><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Unit</th><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Penyewa</th><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Tipe sewa</th><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Tanggal selesai</th><th class="border-b border-stone-200 bg-red-50 px-4 py-3 text-xs font-semibold tracking-wide text-red-900">Tindakan</th></tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr class="even:bg-stone-50/70 hover:bg-red-50/50">
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->garage->code }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ $rental->user->name }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \App\Models\Rental::DURATION_LABELS[$rental->rental_type] ?? $rental->rental_type }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700">{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</td>
                        <td class="border-b border-stone-100 px-4 py-4 text-neutral-700"><a class="text-red-700 hover:text-red-800" href="{{ route('extensions.preview', $rental) }}">Perpanjang sewa</a></td>
                    </tr>
                @empty
                    <tr class="even:bg-stone-50/70 hover:bg-red-50/50"><td class="border-b border-stone-100 px-4 py-4 text-neutral-700" colspan="5">Tidak ada sewa aktif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}

    <p class="my-3 text-sm leading-6"><a class="text-red-700 hover:text-red-800" href="{{ route('admin.rentals') }}">Kembali ke daftar sewa</a></p>
@endsection
