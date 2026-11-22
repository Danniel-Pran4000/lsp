<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | GarageRental</title>
    @vite(['resources/css/app.css', 'resources/js/forms.js'])
</head>
<body class="m-0 bg-stone-50 font-[Poppins,sans-serif] text-neutral-900">
    <header class="border-b border-stone-200/80 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8">
            <a class="text-xl font-semibold tracking-tight text-black" href="{{ url('/') }}"><span class="text-red-700">Garage</span>Rental</a>
            @auth
                <div class="flex min-w-0 items-center gap-4 sm:gap-6">
                    <div class="min-w-0 text-right text-xs">
                        <p class="truncate font-semibold">{{ auth()->user()->name }}</p>
                        <p class="mt-0.5 text-stone-600">{{ auth()->user()->isAdmin() ? 'Admin' : 'Penyewa' }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="whitespace-nowrap rounded-full border border-stone-200 px-3 py-2 text-xs font-medium text-neutral-700 hover:border-red-200 hover:bg-red-50 hover:text-red-800 sm:px-4" type="submit">Keluar</button>
                    </form>
                </div>
            @endauth
        </div>
    </header>
    @auth
        @php
            $activeTab = 'whitespace-nowrap border-b-2 border-red-700 px-2 py-2 text-sm font-semibold text-red-800';
            $inactiveTab = 'whitespace-nowrap border-b-2 border-transparent px-2 py-2 text-sm font-medium text-stone-600 hover:text-red-800';
        @endphp
        <nav class="mx-auto mt-5 flex max-w-7xl gap-5 overflow-x-auto bg-transparent px-5 sm:gap-8 sm:px-8" aria-label="Navigasi utama">
            @if (auth()->user()->isAdmin())
                <a class="{{ request()->routeIs('registration.index') ? $activeTab : $inactiveTab }}" href="{{ route('registration.index') }}">Pengguna</a>
                <a class="{{ request()->routeIs('admin.rentals', 'extensions.*') ? $activeTab : $inactiveTab }}" href="{{ route('admin.rentals') }}">Sewa</a>
                <a class="{{ request()->routeIs('garages.*') ? $activeTab : $inactiveTab }}" href="{{ route('garages.index') }}" @if(request()->routeIs('garages.index')) aria-current="page" @endif>Garasi</a>
                <a class="{{ request()->routeIs('reports.*') ? $activeTab : $inactiveTab }}" href="{{ route('reports.index') }}">Laporan</a>
            @else
                <a class="{{ $activeTab }}" href="{{ route('rentals.index') }}" @if(request()->routeIs('rentals.index')) aria-current="page" @endif>Sewa saya</a>
            @endif
        </nav>
    @endauth
    <main class="mx-auto px-5 sm:px-8 @guest max-w-xl py-16 @else max-w-7xl py-9 @endguest">
        @if (session('status')) <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"><strong>Mohon periksa data berikut</strong><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div> @endif
        <h1 class="mb-6 text-2xl font-semibold tracking-tight">@yield('title')</h1>
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm sm:p-7">@yield('content')</div>
    </main>
</body>
</html>
