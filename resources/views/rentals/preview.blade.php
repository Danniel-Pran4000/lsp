@extends('layouts.account')
@section('title', 'Review payment')

@section('content')
    <dl>
        <dt>Unit code</dt>
        <dd>{{ $garage->code }}</dd>
        <dt>Rental period</dt>
        <dd>{{ $start->format('d/m/Y') }} to {{ $end->format('d/m/Y') }}</dd>
        <dt>Daily rate</dt>
        <dd>IDR {{ str_replace('.', ',', $charge['daily_rate']) }}</dd>
        <dt>Rental charge</dt>
        <dd>IDR {{ str_replace('.', ',', $charge['rental_amount']) }}</dd>
        @if ($charge['kwh_rate'] !== null)
            <dt>Rate per kWh</dt>
            <dd>IDR {{ str_replace('.', ',', $charge['kwh_rate']) }}</dd>
            <dt>Estimated energy</dt>
            <dd>{{ str_replace('.', ',', rtrim(rtrim($charge['energy_kwh'], '0'), '.')) }} kWh</dd>
            <dt>Estimated electricity charge</dt>
            <dd>IDR {{ str_replace('.', ',', rtrim(rtrim($charge['electricity_amount'], '0'), '.')) }}</dd>
        @endif
        <dt>Total payment</dt>
        <dd><strong>IDR {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['initial_total']) }}</strong></dd>
    </dl>

    <form method="post" action="{{ route('rentals.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="quote_id" value="{{ $quoteId }}">
        <label>Payment proof (image, maximum 2 MB)
            <input type="file" name="proof" accept="image/*" required>
        </label>
        <button type="submit">Confirm rental</button>
    </form>

    <p><a href="{{ route('rentals.create') }}">Back to rental form</a></p>
@endsection
