<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'World Cup Sweepstake') }}</title>
        @fonts
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-zinc-50 font-sans text-zinc-950 antialiased">
        <header class="border-b border-zinc-200 bg-white">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <a href="{{ route('home') }}" class="text-base font-semibold">World Cup Sweepstake</a>

                <div class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-medium text-zinc-700 hover:text-zinc-950">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="font-medium text-zinc-700 hover:text-zinc-950">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-zinc-700 hover:text-zinc-950">Sign in</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-zinc-950 px-3 py-2 font-medium text-white hover:bg-zinc-800">Create admin account</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
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
