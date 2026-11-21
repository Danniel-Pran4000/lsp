<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Garage Rental</title>
    @vite('resources/css/app.css')
</head>
<body class="m-0 bg-stone-50 font-[Poppins,sans-serif] text-neutral-900">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a class="flex items-center gap-3 text-lg font-semibold tracking-tight text-neutral-900" href="{{ auth()->check() ? (auth()->user()->role === 'admin' ? route('registration.index') : route('rentals.index')) : route('login') }}"><span class="grid h-10 w-10 place-items-center rounded-xl bg-red-700 text-lg font-semibold text-white">G</span><span>Garage<span class="text-red-700">Rental</span></span></a>
            @auth
                <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-full bg-red-50 text-sm font-semibold text-red-700">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="text-xs grid"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'admin' ? 'Admin' : 'User' }}</small></span><form action="{{ route('logout') }}" method="post">@csrf <button class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm text-neutral-700 hover:bg-stone-50" type="submit">Sign out</button></form></div>
            @endauth
        </div>
    </header>
    @auth
        @php
            $activeTab = 'whitespace-nowrap border-b-2 border-red-700 py-4 text-sm font-medium text-red-700';
            $inactiveTab = 'whitespace-nowrap border-b-2 border-transparent py-4 text-sm font-medium text-stone-500 hover:text-red-700';
        @endphp
        <nav class="border-b border-stone-200 bg-white" aria-label="Main navigation"><div class="mx-auto flex max-w-7xl gap-8 overflow-x-auto px-5 sm:px-8">
            @if (auth()->user()->role === 'admin')
                <a class="{{ request()->routeIs('registration.index') ? $activeTab : $inactiveTab }}" href="{{ route('registration.index') }}">Users</a>
                <a class="{{ request()->routeIs('admin.rentals', 'garages.*', 'extensions.*') ? $activeTab : $inactiveTab }}" href="{{ route('admin.rentals') }}">Rent</a>
                <a class="{{ request()->routeIs('reports.*') ? $activeTab : $inactiveTab }}" href="{{ route('reports.index') }}">Reports</a>
            @else
                <a class="{{ $activeTab }}" href="{{ route('rentals.index') }}">My rentals</a>
            @endif
        </div></nav>
    @endauth
    <main class="mx-auto px-5 sm:px-8 @guest max-w-xl py-16 @else max-w-7xl py-9 @endguest">
        @if (session('status')) <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"><strong>Please check the following</strong><ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div> @endif
        @unless (request()->routeIs('admin.rentals')) <div class="mb-6 flex flex-wrap items-center justify-between gap-4"><div><h1 class="text-2xl font-semibold tracking-tight">@yield('title')</h1></div></div> @endunless
        <div class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm sm:p-7">@yield('content')</div>
    </main>
</body>
</html>
