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
    <body class="sk-shell flex min-h-screen flex-col font-sans">
        <header class="border-b border-brand-border bg-white/90 backdrop-blur">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <x-wordmark />
                </a>

                <div class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-semibold text-brand-muted transition hover:text-brand-navy">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="whitespace-nowrap rounded-full border border-red-200 px-3 py-1.5 font-semibold text-red-700 transition hover:border-red-300 hover:bg-red-50 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-200">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="font-semibold text-brand-muted transition hover:text-brand-navy">Sign in</a>
                        <a href="{{ route('register') }}" class="sk-btn-green px-3 py-2">Create admin account</a>
                    @endauth
                </div>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
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

        <footer class="border-t border-brand-border bg-white/80 text-xs text-brand-muted">
            <div class="mx-auto flex min-h-[30px] max-w-6xl flex-col items-center justify-between gap-1 px-4 py-1.5 sm:flex-row">
                <p class="flex flex-wrap items-center justify-center gap-2">
                    <span>&copy; {{ now()->year }} SweepKit</span>
                    <span aria-hidden="true">&middot;</span>
                    <a href="{{ route('privacy') }}" class="font-medium transition hover:text-brand-navy">Privacy Policy</a>
                    <span aria-hidden="true">&middot;</span>
                    <a href="{{ route('terms') }}" class="font-medium transition hover:text-brand-navy">Terms</a>
                </p>

                <p>
                    Built by
                    <a href="https://elementseven.co" class="font-medium transition hover:text-brand-navy">Element Seven</a>
                </p>
            </div>
        </footer>

        <dialog id="confirm-dialog" class="w-[min(92vw,28rem)] rounded-lg border border-brand-border bg-white p-0 text-brand-navy shadow-xl backdrop:bg-brand-navy/50">
            <div class="p-5">
                <h2 class="text-lg font-bold text-brand-navy" data-confirm-title>Are you sure?</h2>
                <p class="mt-2 text-sm leading-6 text-brand-muted" data-confirm-message>Please confirm this action.</p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <form method="dialog">
                        <button class="sk-btn-secondary" value="cancel">Cancel</button>
                    </form>
                    <button type="button" class="sk-btn-green" data-confirm-submit>Confirm</button>
                </div>
            </div>
        </dialog>
    </body>
</html>
