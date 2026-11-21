<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Penyewaan Garasi</title>
    <style>
        body { font: 1rem/1.5 system-ui, sans-serif; max-width: 72rem; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin: .75rem 0; }
        input, button { font: inherit; padding: .35rem .55rem; }
        th, td { padding: .5rem; text-align: left; border-bottom: 1px solid #ccc; }
        .table { overflow-x: auto; }
        [role="alert"] { color: #a01824; }
    </style>
</head>
<body>
    <main>
        <h1>@yield('title')</h1>
        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul role="alert">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
        @yield('content')
    </main>
    @auth
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Keluar</button>
        </form>
    @endauth
</body>
</html>
