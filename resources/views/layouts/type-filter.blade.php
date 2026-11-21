<form action="{{ route($filterRoute) }}" method="get" class="flex flex-wrap items-end gap-2">
    <div>
        <label for="type" class="sr-only">Garage type</label>
        <select id="type" name="type" class="rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm focus:border-red-700 focus:outline-red-700">
            <option value="">All types</option>
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">Apply filter</button>
</form>
