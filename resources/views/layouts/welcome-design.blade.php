<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | GarageRental</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-white font-[Poppins,sans-serif] text-neutral-900">
    <div class="grid min-h-screen lg:grid-cols-[2fr_3fr]">
        {{-- Shared welcome panel and decorative garage illustration --}}
        <div class="relative isolate flex min-w-0 flex-col justify-center overflow-hidden bg-red-700 px-6 py-10 text-white sm:px-12 lg:px-16">
            <div aria-hidden="true" class="pointer-events-none absolute -top-36 -right-24 -z-10 size-96 rounded-full border border-white/20"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-36 -left-28 -z-10 size-[28rem] rounded-full border border-white/20"></div>

            <p class="text-3xl leading-tight font-semibold tracking-tight sm:text-4xl xl:text-5xl">
                Welcome to<br>GarageRental
            </p>

            <div aria-hidden="true" class="mt-12 hidden max-w-sm border-b border-white/30 lg:flex lg:items-end lg:gap-4">
                <div class="h-24 flex-1 rounded-t-lg border border-b-0 border-white/30 bg-white/5 p-3">
                    <div class="h-full rounded-t bg-[repeating-linear-gradient(0deg,transparent_0_9px,#ffffff22_9px_10px)]"></div>
                </div>
                <div class="h-32 flex-1 rounded-t-lg border border-b-0 border-white/50 bg-white/10 p-3">
                    <div class="h-full rounded-t bg-[repeating-linear-gradient(0deg,transparent_0_9px,#ffffff33_9px_10px)]"></div>
                </div>
                <div class="h-24 flex-1 rounded-t-lg border border-b-0 border-white/30 bg-white/5 p-3">
                    <div class="h-full rounded-t bg-[repeating-linear-gradient(0deg,transparent_0_9px,#ffffff22_9px_10px)]"></div>
                </div>
            </div>
        </div>

        {{-- Page-specific heading, form and navigation link --}}
        <main class="flex min-w-0 items-center justify-center px-6 py-10 sm:px-12 lg:py-16">
            <div class="w-full max-w-xl">
                @session('status')
                    <p class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="status">
                        {{ $value }}
                    </p>
                @endsession

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
