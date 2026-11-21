<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Garage Rental</title>
    @vite('resources/css/app.css')
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ auth()->check() ? (auth()->user()->role === 'admin' ? route('registration.index') : route('rentals.index')) : route('login') }}"><span class="brand-mark">G</span><span>Garage<span class="brand-accent">Rental</span></span></a>
            @auth
                <div class="identity"><span class="avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="identity-text"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'admin' ? 'Admin' : 'User' }}</small></span><form action="{{ route('logout') }}" method="post">@csrf <button class="signout" type="submit">Sign out</button></form></div>
            @endauth
        </div>
    </header>
    @auth
        <nav class="navigation" aria-label="Main navigation"><div class="nav-inner">
            @if (auth()->user()->role === 'admin')
                <a class="{{ request()->routeIs('registration.index') ? 'active' : '' }}" href="{{ route('registration.index') }}">Users</a>
                <a class="{{ request()->routeIs('admin.rentals', 'garages.*', 'extensions.*') ? 'active' : '' }}" href="{{ route('admin.rentals') }}">Rent</a>
                <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
            @else
                <a class="active" href="{{ route('rentals.index') }}">My rentals</a>
            @endif
        </div></nav>
    @endauth
    <main class="page @guest guest-page @endguest">
        @if (session('status')) <div class="notice" role="status">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="error" role="alert"><strong>Please check the following</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div> @endif
        @unless (request()->routeIs('admin.rentals')) <div class="page-heading"><div><h1>@yield('title')</h1></div></div> @endunless
        <div class="panel">@yield('content')</div>
    </main>
</body>
</html>
