@extends('layouts.app')

@section('content')
    <section class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
        <div>
            <p class="text-sm font-semibold uppercase tracking-normal text-red-700">2026 FIFA World Cup</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-semibold leading-tight text-zinc-950 sm:text-5xl">
                World Cup Sweepstake App
            </h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-zinc-700">
                Create a private sweepstake, invite entrants, track payments and run a balanced ranked pot draw.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-800">Open dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="rounded-lg bg-zinc-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-800">Create admin account</a>
                    <a href="{{ route('login') }}" class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-800 hover:bg-white">Sign in</a>
                @endauth
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5">
            <h2 class="text-base font-semibold">MVP foundation</h2>
            <ul class="mt-4 space-y-3 text-sm text-zinc-700">
                <li>Ranked pot draw logic.</li>
                <li>Admin authentication.</li>
                <li>Public join link and PIN-style code.</li>
                <li>Per-sweepstake team selection.</li>
                <li>Prize and payment tracking.</li>
            </ul>
        </div>
    </section>
@endsection
