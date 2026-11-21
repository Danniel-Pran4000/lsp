@extends('layouts.header')
@section('title', 'Tinjau pembayaran')

@section('content')
    <form method="post" action="{{ route('rentals.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="quote_id" value="{{ $quoteId }}">

        <div class="grid gap-6 lg:grid-cols-2">
            @include('user.rental-summary')
            <section class="rounded-xl border border-red-100 bg-red-50/50 p-5 sm:p-6">
                @include('layouts.payment-proof', ['id' => 'proof'])
            </section>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-4">
            <a class="text-sm font-medium text-red-700 underline-offset-4 hover:underline" href="{{ route('rentals.create') }}">Kembali ke formulir sewa</a>
            <button class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800" type="submit">Konfirmasi sewa</button>
        </div>
    </form>
@endsection
