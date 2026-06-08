@extends('layouts.app')

@section('content')
    <section class="max-w-2xl">
        <nav aria-label="Breadcrumb" class="mb-5 text-sm">
            @if ($canViewAdminLinks)
                <ol class="flex flex-wrap items-center gap-2 text-brand-muted">
                    <li><a class="font-semibold text-brand-blue hover:underline" href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a class="font-semibold text-brand-blue hover:underline" href="{{ route('sweepstakes.show', $sweepstake) }}">{{ $sweepstake->name }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="font-semibold text-brand-navy">Entrant teams</li>
                </ol>
            @else
                <p class="font-semibold text-brand-muted">Entrant teams</p>
            @endif
        </nav>

        <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">{{ ucfirst($sweepstake->status) }}</p>
        <h1 class="mt-2 text-3xl font-black text-brand-navy">{{ $sweepstake->name }}</h1>
        <p class="mt-3 text-brand-muted">Hi {{ $member->name }}. Paid status is just for the sweepstake admin's own tracking.</p>

        <div class="sk-card mt-6 overflow-hidden">
            <div class="sk-card-header bg-gradient-to-r from-brand-navy to-[#0b2d54] text-white">
                <h2 class="font-semibold">{{ $activeDraw ? 'Your teams are ready' : "You're entered" }}</h2>
                @if ($activeDraw)
                    <p class="mt-1 text-sm text-white/70">Active draw #{{ $activeDraw->version_number }}</p>
                @endif
            </div>

            @if ($assignments->isEmpty())
                <div class="px-5 py-4 text-sm text-brand-muted">
                    <p class="font-semibold text-brand-navy">You're entered. Your teams will appear here after the draw.</p>
                    <p class="mt-1">Keep this private link safe so you can come back after the admin runs the draw.</p>
                </div>
            @else
                <ul class="grid gap-3 p-5 sm:grid-cols-2">
                    @foreach ($assignments as $assignment)
                        <li class="rounded-lg border border-brand-border bg-brand-soft px-4 py-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-brand-navy">
                                    <x-team-name :team="$assignment->team" />
                                </span>
                                <span class="sk-badge sk-badge-blue">Pot {{ $assignment->pot_number ?? 'n/a' }}</span>
                            </div>
                            <p class="mt-2 text-xs text-brand-muted">Ranking {{ $assignment->team->fifa_ranking ?? 'n/a' }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($draws->isNotEmpty())
            <div class="sk-card mt-6">
                <div class="sk-card-header">
                    <h2 class="font-semibold text-brand-navy">Draw history</h2>
                    <p class="mt-1 text-sm text-brand-muted">You can see when your draw changed and why.</p>
                </div>

                <div class="divide-y divide-brand-border/70">
                    @foreach ($draws as $draw)
                        <div class="px-5 py-4 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold text-brand-navy">Draw #{{ $draw->version_number }} — run on {{ $draw->ran_at->format('j M Y \a\t H:i') }}</p>
                                <span class="sk-badge {{ $draw->status === \App\Models\SweepstakeDraw::STATUS_ACTIVE ? 'sk-badge-green' : ($draw->status === \App\Models\SweepstakeDraw::STATUS_CANCELLED ? 'sk-badge-amber' : 'sk-badge-neutral') }}">
                                    {{ $draw->statusLabel() }}
                                </span>
                            </div>

                            @if ($draw->reason)
                                <p class="mt-2 text-brand-muted">Reason: {{ $draw->reason }}</p>
                            @endif

                            @if ($draw->cancelled_reason)
                                <p class="mt-2 text-brand-muted">Cancellation reason: {{ $draw->cancelled_reason }}</p>
                            @endif

                            @if ($draw->assignments->isNotEmpty())
                                <p class="mt-2 text-brand-muted">
                                    Your teams:
                                    {{ $draw->assignments
                                        ->sortBy(fn ($assignment) => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                                        ->map(fn ($assignment) => trim(($assignment->team->displayFlag() ? $assignment->team->displayFlag() . ' ' : '') . $assignment->team->name))
                                        ->join(', ') }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
