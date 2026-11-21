@extends('layouts.account')
@section('title', 'Extend rental')

@section('content')
    <dl>
        <dt>Unit</dt>
        <dd>{{ $rental->garage->code }}</dd>
        <dt>Renter</dt>
        <dd>{{ $rental->user->name }}</dd>
        <dt>Rental type awal</dt>
        <dd>{{ $rental->rental_type === 'monthly' ? 'Monthly' : 'Daily' }}</dd>
        <dt>Current end date</dt>
        <dd>{{ \Carbon\CarbonImmutable::parse($rental->ends_on)->format('d/m/Y') }}</dd>
        <dt>Original daily rate</dt>
        <dd>IDR {{ str_replace('.', ',', $rental->daily_rate) }}</dd>
    </dl>

    <form action="{{ route('extensions.preview', $rental) }}" method="get">
        <label>New end date
            <input type="date" name="ends_on" min="{{ $earliestEnd }}" max="9999-12-31"
                value="{{ is_string(old('ends_on')) ? old('ends_on') : $newEnd }}" required>
        </label>
        <button type="submit">Calculate additional charge</button>
    </form>

    @if ($charge)
        <dl>
            <dt>End date yang dikonfirmasi</dt>
            <dd>{{ \Carbon\CarbonImmutable::parse($newEnd)->format('d/m/Y') }}</dd>
            <dt>Extension length</dt>
            <dd>{{ $charge['days'] }} days</dd>
            <dt>Additional charge</dt>
            <dd><strong>IDR {{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $charge['amount']) }}</strong></dd>
        </dl>
        <form action="{{ route('extensions.store', $rental) }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="quote_id" value="{{ $quoteId }}">
            <label>Payment proof (image, maximum 2 MB)
                <input type="file" name="proof" accept="image/*" required>
            </label>
            <button type="submit">Save extension</button>
        </form>
    @endif

    <p><a href="{{ route('extensions.create') }}">Back to rentals</a></p>
@endsection
