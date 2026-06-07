@extends('layouts.app')

@section('content')
    <section class="max-w-2xl">
        <p class="text-sm font-semibold uppercase tracking-normal text-red-700">{{ ucfirst($sweepstake->status) }}</p>
        <h1 class="mt-2 text-3xl font-semibold">{{ $sweepstake->name }}</h1>
        <p class="mt-3 text-zinc-700">Hi {{ $member->name }}. Paid status is managed by the sweepstake admin.</p>

        <div class="mt-6 rounded-lg border border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="font-semibold">Assigned teams</h2>
                @if ($activeDraw)
                    <p class="mt-1 text-sm text-zinc-600">Active draw #{{ $activeDraw->version_number }}</p>
                @endif
            </div>

            @if ($assignments->isEmpty())
                <div class="px-5 py-4 text-sm text-zinc-600">
                    <p class="font-medium text-zinc-800">You're entered. Your teams will appear here after the draw.</p>
                    <p class="mt-1">Keep this private link safe so you can come back after the admin runs the draw.</p>
                </div>
            @else
                <ul class="divide-y divide-zinc-100">
                    @foreach ($assignments as $assignment)
                        <li class="px-5 py-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium">
                                    @if ($assignment->team->flag)
                                        <span aria-hidden="true">{{ $assignment->team->flag }}</span>
                                    @endif
                                    {{ $assignment->team->name }}
                                </span>
                                <span class="text-zinc-600">Pot {{ $assignment->pot_number ?? 'n/a' }}</span>
                            </div>
                            <p class="mt-1 text-xs text-zinc-600">Ranking {{ $assignment->team->fifa_ranking ?? 'n/a' }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($draws->isNotEmpty())
            <div class="mt-6 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Draw history</h2>
                </div>

                <div class="divide-y divide-zinc-100">
                    @foreach ($draws as $draw)
                        <div class="px-5 py-4 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-medium">Draw #{{ $draw->version_number }} — run on {{ $draw->ran_at->format('j M Y \a\t H:i') }}</p>
                                <span class="rounded-lg border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700">
                                    {{ $draw->status === \App\Models\SweepstakeDraw::STATUS_ACTIVE ? 'Active draw' : 'Superseded' }}
                                </span>
                            </div>

                            @if ($draw->reason)
                                <p class="mt-2 text-zinc-600">Reason: {{ $draw->reason }}</p>
                            @endif

                            @if ($draw->assignments->isNotEmpty())
                                <p class="mt-2 text-zinc-600">
                                    Your teams:
                                    {{ $draw->assignments
                                        ->sortBy(fn ($assignment) => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                                        ->map(fn ($assignment) => $assignment->team->name)
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
