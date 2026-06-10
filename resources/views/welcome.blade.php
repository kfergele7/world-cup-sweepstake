@extends('layouts.app')

@section('content')
    @php
        $createSweepstakeUrl = auth()->check() ? route('dashboard') : route('register');
        $createSweepstakeLabel = auth()->check() ? 'Open dashboard' : 'Create a sweepstake';
        $steps = [
            [
                'label' => 'Step 1',
                'title' => 'Create your sweepstake',
                'body' => 'Set up your sweepstake name, entry fee, prizes and draw rules.',
            ],
            [
                'label' => 'Step 2',
                'title' => 'Add your entrants',
                'body' => 'Invite people with a private link or add entrants manually. Track who has paid and keep everything organised in one place.',
            ],
            [
                'label' => 'Step 3',
                'title' => 'Run the draw',
                'body' => 'Use automatic pots for a balanced draw, or create your own custom pots if you want more control. Once the draw is complete, entrants can view their teams from their private results page.',
            ],
        ];
        $features = [
            [
                'title' => 'Private invite links',
                'body' => 'Let people join without creating an account. Share one simple link with your group.',
            ],
            [
                'title' => 'Manual entrant management',
                'body' => 'Add people yourself, update their details and keep the organiser in control.',
            ],
            [
                'title' => 'Paid/unpaid tracking',
                'body' => 'Track who has paid their entry fee without needing payment processing inside the app.',
            ],
            [
                'title' => 'Prize setup',
                'body' => 'Add your prizes before the draw so everyone knows what the prizes are.',
            ],
            [
                'title' => 'Auto pots',
                'body' => 'SweepKit can group teams by ranking to help create a fairer draw.',
            ],
            [
                'title' => 'Custom pots',
                'body' => 'Prefer your own judgement? Build custom pots for favourites, contenders and outsiders.',
            ],
        ];
    @endphp

    <div class="space-y-24 sm:space-y-32">
        <section class="grid gap-10 py-8 sm:py-12 lg:grid-cols-[1.04fr_0.96fr] lg:items-center lg:gap-14">
            <div>
                <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">SweepKit for private football sweepstakes</p>
                <h1 class="mt-4 max-w-4xl text-4xl font-black leading-tight text-brand-navy sm:text-5xl">
                    Run a football sweepstake without the spreadsheet chaos.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-brand-muted">
                    SweepKit helps you create private football sweepstakes, invite entrants, track payments, set prizes and run a fair team draw in minutes.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $createSweepstakeUrl }}" class="sk-btn-green inline-flex justify-center px-5 py-3 sm:w-auto">{{ $createSweepstakeLabel }}</a>
                    @guest
                        <a href="{{ route('login') }}" class="sk-btn-secondary inline-flex justify-center px-5 py-3 sm:w-auto">Log in</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="sk-btn-secondary inline-flex justify-center px-5 py-3 sm:w-auto">Dashboard</a>
                    @endguest
                </div>

                <p class="mt-5 text-sm font-medium text-brand-muted">
                    Built for private groups, workplaces, clubs and friends.
                </p>
            </div>

            <div class="rounded-lg border border-brand-border/70 bg-white/85 p-5 shadow-sm shadow-brand-navy/5">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-brand-border pb-5">
                    <div>
                        <p class="text-sm font-semibold text-brand-blue">Example setup</p>
                        <h2 class="mt-2 text-2xl font-black text-brand-navy">Office World Cup sweepstake</h2>
                    </div>
                    <span class="sk-badge sk-badge-green">Ready to draw</span>
                </div>

                <div class="mt-6 grid gap-5 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                    <div>
                        <p class="text-sm text-brand-muted">Entrants</p>
                        <p class="mt-1 text-3xl font-black text-brand-navy">24</p>
                    </div>
                    <div>
                        <p class="text-sm text-brand-muted">Teams</p>
                        <p class="mt-1 text-3xl font-black text-brand-navy">48</p>
                    </div>
                    <div>
                        <p class="text-sm text-brand-muted">Prizes</p>
                        <p class="mt-1 text-3xl font-black text-brand-navy">3</p>
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-brand-blue/10 bg-brand-blue/5 p-4">
                    <p class="text-sm font-semibold text-brand-navy">Balanced pots</p>
                    <p class="mt-2 text-sm leading-6 text-brand-muted">
                        Keep favourites, contenders and outsiders spread more evenly across your group.
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white px-5 py-12 shadow-sm shadow-brand-navy/5 sm:px-8 sm:py-16">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">How it works</p>
                <h2 class="mt-3 text-3xl font-black text-brand-navy">From setup to draw in three steps.</h2>
            </div>

            <div class="mt-10 grid gap-8 md:grid-cols-3">
                @foreach ($steps as $step)
                    <article class="border-t border-brand-border/80 pt-5">
                        <p class="text-xs font-bold uppercase tracking-normal text-brand-green">{{ $step['label'] }}</p>
                        <h3 class="mt-3 text-lg font-bold text-brand-navy">{{ $step['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-brand-muted">{{ $step['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="py-2">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">What you can manage</p>
                <h2 class="mt-3 text-3xl font-black text-brand-navy">Everything the organiser needs in one place.</h2>
            </div>

            <div class="mt-10 grid gap-x-8 gap-y-7 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $feature)
                    <article class="rounded-lg border border-brand-border/70 bg-white/80 p-6 shadow-sm shadow-brand-navy/5">
                        <h3 class="font-bold text-brand-navy">{{ $feature['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-brand-muted">{{ $feature['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-brand-blue/10 bg-[#eef7ff] px-5 py-12 sm:px-8 sm:py-16">
            <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-12">
                <div class="rounded-lg bg-white/75 p-5 shadow-sm shadow-brand-navy/5">
                    <div class="space-y-3">
                        <div class="rounded-lg border border-brand-border/70 bg-white px-4 py-3">
                            <p class="text-sm font-bold text-brand-navy">Pot 1 - Favourites</p>
                            <p class="mt-1 text-sm text-brand-muted">Top-ranked teams spread across entrants.</p>
                        </div>
                        <div class="rounded-lg border border-brand-blue/20 bg-brand-blue/5 px-4 py-3">
                            <p class="text-sm font-bold text-brand-navy">Pot 2 - Contenders</p>
                            <p class="mt-1 text-sm text-brand-muted">Strong teams balanced into the draw.</p>
                        </div>
                        <div class="rounded-lg border border-brand-green/20 bg-green-50 px-4 py-3">
                            <p class="text-sm font-bold text-brand-navy">Pot 3 - Outsiders</p>
                            <p class="mt-1 text-sm text-brand-muted">Each entrant gets a balanced mix.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">Fairer draws</p>
                    <h2 class="mt-3 text-3xl font-black text-brand-navy">A fairer way to draw teams</h2>
                    <div class="mt-5 space-y-4 text-base leading-7 text-brand-muted">
                        <p>Sweepstakes are more fun when the draw feels balanced.</p>
                        <p>
                            SweepKit can use ranking-based pots so entrants get a fairer spread of teams. If rankings do not match how your group sees the tournament, you can create custom pots and decide how many teams each entrant receives from each pot.
                        </p>
                        <p>No more messy spreadsheets. No more arguments about who got all the favourites.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-10 py-2 lg:grid-cols-[1.04fr_0.96fr] lg:items-center lg:gap-14">
            <div>
                <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">Results everyone can see</p>
                <h2 class="mt-3 text-3xl font-black text-brand-navy">Clear results for everyone</h2>
                <div class="mt-5 space-y-4 text-base leading-7 text-brand-muted">
                    <p>
                        After the draw, each entrant gets a private page showing their teams. They can also see the full draw results so everyone knows who drew what.
                    </p>
                    <p>
                        Admins can cancel and re-run a draw if needed, with draw history kept for transparency.
                    </p>
                </div>
            </div>

            <div class="rounded-lg border border-brand-border/70 bg-white/85 p-5 shadow-sm shadow-brand-navy/5">
                <p class="text-sm font-semibold text-brand-blue">Private entrant page</p>
                <h3 class="mt-2 text-xl font-black text-brand-navy">Your teams</h3>
                <ul class="mt-5 divide-y divide-brand-border/70 text-sm text-brand-muted">
                    <li class="flex items-center justify-between gap-4 py-3">
                        <span class="font-semibold text-brand-navy">Japan</span>
                        <span class="sk-badge sk-badge-blue">Group draw</span>
                    </li>
                    <li class="flex items-center justify-between gap-4 py-3">
                        <span class="font-semibold text-brand-navy">Portugal</span>
                        <span class="sk-badge sk-badge-green">Pot 1</span>
                    </li>
                    <li class="flex items-center justify-between gap-4 py-3">
                        <span class="font-semibold text-brand-navy">Canada</span>
                        <span class="sk-badge sk-badge-neutral">Pot 3</span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="border-y border-brand-border/80 py-12 sm:py-16">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">Private beta</p>
                <h2 class="mt-3 text-3xl font-black text-brand-navy">Built for private sweepstakes</h2>
                <div class="mt-5 space-y-4 text-base leading-7 text-brand-muted">
                    <p>
                        SweepKit is designed for private groups organising their own sweepstakes. It does not collect entry fees or run the sweepstake on your behalf.
                    </p>
                    <p>
                        Organisers are responsible for making sure their sweepstake follows any local rules, workplace rules and relevant laws.
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-brand-navy px-6 py-12 text-white shadow-sm shadow-brand-navy/20 sm:px-10 sm:py-14">
            <div class="flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold text-brand-green">Ready when your group is</p>
                    <h2 class="mt-3 text-3xl font-black">Ready to run your draw?</h2>
                    <p class="mt-4 text-base leading-7 text-white/75">
                        Create your sweepstake, invite your group and run a fair football draw in minutes.
                    </p>
                </div>

                <a href="{{ $createSweepstakeUrl }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-green px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-brand-green/20 transition hover:bg-[#119640]">
                    {{ $createSweepstakeLabel }}
                </a>
            </div>
        </section>
    </div>
@endsection
