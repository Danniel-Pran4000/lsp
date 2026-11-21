<form action="{{ route($filterRoute) }}" method="get" class="flex flex-wrap items-end gap-2">
    <div>
        <label for="type" class="sr-only">Tipe garasi</label>
        <select id="type" name="type" data-auto-filter class="rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm focus:border-red-700 focus:outline-red-700">
            <option value="">Semua tipe</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <noscript><button type="submit" class="rounded-lg border px-3 py-2 text-sm">Terapkan filter</button></noscript>
</form>
