@extends('layouts.app')

@section('content')
    <section class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
        <div>
            <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">SweepKit for football pools</p>
            <h1 class="mt-3 max-w-3xl text-4xl font-black leading-tight text-brand-navy sm:text-5xl">
                Create fair football sweepstakes in minutes.
            </h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-brand-muted">
                Invite entrants, track paid status and run a balanced ranked pot draw with clear results everyone can trust.
            </p>
            <p class="mt-4 max-w-2xl rounded-lg border border-brand-border bg-white/75 px-4 py-3 text-sm leading-6 text-brand-muted">
                SweepKit is currently in private beta for selected tester groups. If something does not look right, please let the organiser know or <a href="{{ route('feedback') }}" class="font-semibold text-brand-blue underline">send feedback</a>.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="sk-btn-green py-2.5">Open dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="sk-btn-green py-2.5">Create admin account</a>
                    <a href="{{ route('login') }}" class="sk-btn-secondary py-2.5">Sign in</a>
                @endauth
            </div>
        </div>

        <div class="sk-card overflow-hidden">
            <div class="bg-gradient-to-br from-brand-navy to-[#0b2d54] p-5 text-white">
                <p class="text-sm font-semibold text-brand-green">2026 ready</p>
                <h2 class="mt-2 text-2xl font-bold">Run a fair pot-based draw.</h2>
                <p class="mt-2 text-sm leading-6 text-white/75">SweepKit keeps the setup tidy for admins and makes results easy for entrants to view.</p>
            </div>
            <ul class="space-y-3 p-5 text-sm text-brand-muted">
                <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-brand-green"></span>Ranked pot draw logic with draw history.</li>
                <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-brand-blue"></span>Private join links and entrant team pages.</li>
                <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-brand-green"></span>Payment tracking without payment processing.</li>
                <li class="flex gap-3"><span class="mt-1 h-2 w-2 rounded-full bg-brand-blue"></span>Email notifications when teams are ready.</li>
            </ul>
        </div>
    </section>
@endsection
