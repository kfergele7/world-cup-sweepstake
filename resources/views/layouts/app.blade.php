<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SweepKit</title>
        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="sk-shell font-sans">
        <header class="border-b border-brand-border bg-white/90 backdrop-blur">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <x-wordmark />
                    <span class="hidden text-sm font-medium text-brand-muted sm:inline">Fair football sweepstakes</span>
                </a>

                <div class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-semibold text-brand-muted transition hover:text-brand-navy">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="font-semibold text-brand-muted transition hover:text-brand-navy">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="font-semibold text-brand-muted transition hover:text-brand-navy">Sign in</a>
                        <a href="{{ route('register') }}" class="sk-btn-green px-3 py-2">Create admin account</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-900 shadow-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm">
                    <p class="font-semibold">Please check the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </body>
</html>
