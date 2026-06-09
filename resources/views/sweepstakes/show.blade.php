@extends('layouts.app')

@section('content')
    @php
        $removeLeftoversStrategy = \App\Models\SweepstakeDraw::LEFTOVER_STRATEGY_REMOVE_LOWEST_RANKED;
        $assignLeftoversStrategy = \App\Models\SweepstakeDraw::LEFTOVER_STRATEGY_ASSIGN_RANDOMLY;
        $isCustomPotMode = $sweepstake->pot_mode === \App\Models\Sweepstake::POT_MODE_CUSTOM;
        $hasLeftoverTeams = ! $isCustomPotMode && $memberCount > 0 && $leftoverTeamCount > 0;
        $activeCustomPotCount = $customPotSummaries->where('is_active', true)->count();
        $customPotDrawnTeamCount = $customPotSummaries->sum('drawn_team_count');
        $customPotUnusedTeamCount = $customPotSummaries->sum('unused_team_count');
        $totalAssignedCustomPotTeams = $customPotSummaries->sum('assigned_team_count');
        $nextStep = match (true) {
            $memberCount < 2 => 'Add at least two entrants before running the draw.',
            $isCustomPotMode && $sweepstake->pots->isEmpty() => 'Create custom pots, then assign teams to them.',
            $isCustomPotMode && count($customPotWarnings) > 0 => 'Review the Pots tab and resolve the custom pot warnings.',
            ! $activeDraw => 'Run the draw when entrants, teams and prizes look right.',
            default => 'Review results and share entrant links if needed.',
        };
    @endphp

    <section>
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">{{ ucfirst($sweepstake->status) }}</p>
                <h1 class="mt-2 text-3xl font-black text-brand-navy">{{ $sweepstake->name }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="sk-badge sk-badge-blue">Join code {{ $sweepstake->join_code }}</span>
                    <a class="sk-btn-pill" href="{{ route('join.show', $sweepstake->join_code) }}">Open join page</a>
                    <x-copy-button
                        :value="route('join.show', $sweepstake->join_code)"
                        label="Copy public join link"
                    />
                </div>
            </div>

            @if ($activeDraw)
                <p class="sk-badge sk-badge-green px-4 py-2 text-sm">Active draw #{{ $activeDraw->version_number }}</p>
            @else
                <a href="#draw-results" class="sk-btn-green">Go to draw</a>
            @endif
        </div>

        @if (! $activeDraw && $latestCancelledDraw)
            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm">
                <p class="font-semibold">The previous draw was cancelled. Setup is open again.</p>
                @if ($latestCancelledDraw->cancelled_reason)
                    <p class="mt-1">Reason: {{ $latestCancelledDraw->cancelled_reason }}</p>
                @endif
            </div>
        @endif

        @if ($prizeWarning)
            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 shadow-sm">
                {{ $prizeWarning }}
            </div>
        @endif

        <div class="mt-8" data-tabs data-default-tab="overview">
            <nav class="flex gap-2 overflow-x-auto rounded-lg border border-brand-border bg-white p-2 text-sm" aria-label="Sweepstake admin sections">
                @foreach ([
                    'overview' => 'Overview',
                    'entrants' => 'Entrants',
                    'teams' => 'Teams',
                    'pots' => 'Pots',
                    'draw-results' => 'Draw & Results',
                    'settings-prizes' => 'Settings & Prizes',
                ] as $tabId => $tabLabel)
                    <a
                        href="#{{ $tabId }}"
                        class="shrink-0 rounded-lg border border-brand-border bg-white px-3 py-2 font-semibold text-brand-muted transition hover:border-brand-blue/40 hover:bg-brand-blue/5"
                        data-tab-target="{{ $tabId }}"
                    >
                        {{ $tabLabel }}
                    </a>
                @endforeach
            </nav>

            <div id="overview" class="mt-6 space-y-6 scroll-mt-6" data-tab-panel="overview">
                <div class="sk-card p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-brand-navy">Overview</h2>
                            <p class="mt-1 text-sm text-brand-muted">Set up your entrants, choose your teams, check your pots, then run the draw.</p>
                        </div>
                        <span class="sk-badge {{ $activeDraw ? 'sk-badge-green' : 'sk-badge-blue' }}">{{ $activeDraw ? 'Draw run' : 'Setup open' }}</span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-lg border border-brand-border bg-brand-soft p-4">
                            <p class="text-sm text-brand-muted">Entrants</p>
                            <p class="mt-1 text-2xl font-black text-brand-navy">{{ $memberCount }}</p>
                            <p class="mt-1 text-xs text-brand-muted">Capacity {{ $memberCount }} / {{ $entrantCapacity }}</p>
                        </div>
                        <div class="rounded-lg border border-brand-border bg-brand-soft p-4">
                            <p class="text-sm text-brand-muted">Teams included</p>
                            <p class="mt-1 text-2xl font-black text-brand-navy">{{ $selectedTeamCount }}</p>
                            <p class="mt-1 text-xs text-brand-muted">{{ $removedTeams->count() }} removed</p>
                        </div>
                        <div class="rounded-lg border border-brand-border bg-brand-soft p-4">
                            <p class="text-sm text-brand-muted">Draw rule</p>
                            <p class="mt-1 text-2xl font-black text-brand-navy">{{ $sweepstake->potModeLabel() }}</p>
                            <p class="mt-1 text-xs text-brand-muted">{{ $activeCustomPotCount }} active custom {{ \Illuminate\Support\Str::plural('pot', $activeCustomPotCount) }}</p>
                        </div>
                        <div class="rounded-lg border border-brand-border bg-brand-soft p-4">
                            <p class="text-sm text-brand-muted">Entry fee</p>
                            <p class="mt-1 text-2xl font-black text-brand-navy">{{ $sweepstake->currency }} {{ number_format((float) $sweepstake->entry_fee, 2) }}</p>
                            <p class="mt-1 text-xs text-brand-muted">Collected {{ $sweepstake->currency }} {{ number_format($sweepstake->collectedPot(), 2) }}</p>
                        </div>
                        <div class="rounded-lg border border-brand-border bg-brand-soft p-4">
                            <p class="text-sm text-brand-muted">Draw status</p>
                            <p class="mt-1 text-2xl font-black text-brand-navy">{{ $activeDraw ? 'Active' : 'Not run' }}</p>
                            <p class="mt-1 text-xs text-brand-muted">{{ $draws->count() }} draw {{ \Illuminate\Support\Str::plural('version', $draws->count()) }}</p>
                        </div>
                        <div class="rounded-lg border border-brand-border bg-brand-soft p-4">
                            <p class="text-sm text-brand-muted">Prize total</p>
                            <p class="mt-1 text-2xl font-black text-brand-navy">{{ $sweepstake->currency }} {{ number_format($totalPrizePayout, 2) }}</p>
                            <p class="mt-1 text-xs text-brand-muted">Expected pot {{ $sweepstake->currency }} {{ number_format($expectedEntryPot, 2) }}</p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-lg border border-brand-border bg-white px-4 py-3 text-sm">
                        <p class="font-semibold text-brand-navy">Next recommended step</p>
                        <p class="mt-1 text-brand-muted">{{ $nextStep }}</p>
                    </div>
                </div>
            </div>

            <div id="entrants" class="mt-6 scroll-mt-6" data-tab-panel="entrants">
                <div class="sk-card overflow-hidden">
                    <div class="sk-card-header">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="font-semibold text-brand-navy">Entrants</h2>
                                <p class="mt-1 text-sm text-brand-muted">All entrants are included in the draw. Paid status is just for your own tracking. Capacity: {{ $memberCount }} / {{ $entrantCapacity }} entrants.</p>
                            </div>
                            <x-copy-button
                                :value="route('join.show', $sweepstake->join_code)"
                                label="Copy public join link"
                                button-label="Copy join link"
                            />
                        </div>
                    </div>

                    <form method="POST" action="{{ route('sweepstakes.members.store', $sweepstake) }}" class="grid gap-3 border-b border-brand-border bg-brand-soft px-5 py-4 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
                        @csrf
                        <label>
                            <span class="text-sm font-medium text-brand-navy">Name</span>
                            <input name="name" value="{{ old('name') }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                        </label>
                        <label>
                            <span class="text-sm font-medium text-brand-navy">Email</span>
                            <input type="email" name="email" value="{{ old('email') }}" class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border border-brand-border bg-white px-3 py-2 text-sm text-brand-muted">
                            <input type="checkbox" name="is_paid" value="1" class="rounded border-brand-border text-brand-green" @checked(old('is_paid')) @disabled($sweepstake->isLockedForChanges())>
                            Paid
                        </label>
                        <button class="sk-btn-green" @disabled($sweepstake->isLockedForChanges())>Add entrant</button>
                        <p class="lg:col-span-4 text-sm text-brand-muted">You can add people manually if they do not want to use the join link.</p>
                    </form>

                    <div class="divide-y divide-brand-border/70">
                        @forelse ($sweepstake->members as $member)
                            <div class="px-5 py-4" data-manage-container>
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-brand-navy">{{ $member->name }}</p>
                                            <span class="sk-badge {{ $member->is_paid ? 'sk-badge-green' : 'sk-badge-amber' }}">{{ $member->is_paid ? 'Paid' : 'Not paid yet' }}</span>
                                            <span class="sk-badge {{ $member->source === \App\Models\SweepstakeMember::SOURCE_JOIN_LINK ? 'sk-badge-blue' : 'sk-badge-navy' }}">{{ $member->sourceLabel() }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-brand-muted">
                                            @if ($member->email)
                                                <a class="text-brand-blue underline" href="mailto:{{ $member->email }}">{{ $member->email }}</a>
                                            @else
                                                No email
                                            @endif
                                            · Joined {{ $member->created_at->format('j M Y') }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($member->join_token)
                                            <a class="sk-btn-pill" href="{{ route('entrants.show', $member->join_token) }}">
                                                {{ $activeDraw ? 'View drawn teams' : 'View team page' }}
                                            </a>
                                            <x-copy-button
                                                :value="route('entrants.show', $member->join_token)"
                                                label="Copy private team link"
                                                button-label="Copy link"
                                            />
                                        @endif

                                        <button type="button" class="sk-btn-pill" data-manage-toggle aria-expanded="false">
                                            <span data-manage-open-label>Manage</span>
                                            <span class="hidden" data-manage-close-label>Cancel</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 hidden rounded-lg border border-brand-border bg-brand-soft p-4" data-manage-panel>
                                    <form id="member-edit-{{ $member->id }}" method="POST" action="{{ route('sweepstakes.members.update', [$sweepstake, $member]) }}" class="grid gap-3 sm:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <label>
                                            <span class="text-sm font-medium text-brand-navy">Name</span>
                                            <input name="name" value="{{ old('name', $member->name) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                        </label>
                                        <label>
                                            <span class="text-sm font-medium text-brand-navy">Email</span>
                                            <input type="email" name="email" value="{{ old('email', $member->email) }}" class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                        </label>
                                    </form>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button form="member-edit-{{ $member->id }}" class="sk-btn-secondary" @disabled($sweepstake->isLockedForChanges())>Save entrant</button>

                                        <form method="POST" action="{{ route('sweepstakes.members.payment.update', [$sweepstake, $member]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_paid" value="{{ $member->is_paid ? 0 : 1 }}">
                                            <button class="sk-btn-secondary" @disabled($sweepstake->isLockedForChanges())>
                                                {{ $member->is_paid ? 'Mark as unpaid' : 'Mark as paid' }}
                                            </button>
                                        </form>
                                    </div>

                                    <div class="mt-4 border-t border-brand-border pt-4">
                                        <form
                                            method="POST"
                                            action="{{ route('sweepstakes.members.destroy', [$sweepstake, $member]) }}"
                                            data-confirm-form
                                            data-confirm-title="Remove entrant"
                                            data-confirm-message="This will remove the entrant from the sweepstake before the draw."
                                            data-confirm-label="Remove entrant"
                                            data-confirm-variant="danger"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="sk-btn-danger" @disabled($sweepstake->isLockedForChanges())>Remove entrant</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-brand-muted">No entrants yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div id="teams" class="mt-6 scroll-mt-6" data-tab-panel="teams">
                <div class="sk-card overflow-hidden">
                    <div class="sk-card-header">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-brand-navy">Team selection</h2>
                                <p class="mt-1 text-sm text-brand-muted">
                                    Included teams: {{ $selectedTeams->count() }} · Removed teams: {{ $removedTeams->count() }}
                                </p>
                                <p class="mt-1 text-xs text-brand-muted">Removed teams are never included in the draw.</p>
                            </div>

                            @if ($sweepstake->isLockedForChanges())
                                <p class="sk-badge sk-badge-neutral px-3 py-2 text-sm">Team selection is locked after the draw.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid divide-y divide-brand-border/70 md:grid-cols-2 md:divide-x md:divide-y-0">
                        <form
                            method="POST"
                            action="{{ route('sweepstakes.teams.bulk.update', $sweepstake) }}"
                            data-bulk-team-form
                            data-confirm-form
                            data-confirm-title="Remove selected teams"
                            data-confirm-message="Removed teams will not be included when the draw runs."
                            data-confirm-label="Remove selected teams"
                            data-confirm-variant="danger"
                            class="flex flex-col"
                        >
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="remove">

                            <div class="border-b border-brand-border bg-green-50/50 px-5 py-4">
                                <h3 class="font-semibold text-brand-navy">Included teams</h3>
                                <p class="mt-1 text-sm text-brand-muted">Select teams to remove.</p>
                                <p class="mt-1 text-xs font-semibold text-brand-green">Scroll to see all teams.</p>
                                <p class="mt-2 text-sm font-semibold text-brand-navy"><span data-selected-count>0</span> selected to remove</p>
                            </div>

                            <div class="max-h-[30rem] flex-1 overflow-y-auto divide-y divide-brand-border/70">
                                @forelse ($selectedTeams as $sweepstakeTeam)
                                    <label class="flex items-center gap-3 px-5 py-3 text-sm transition hover:bg-green-50/70">
                                        <input type="checkbox" name="team_ids[]" value="{{ $sweepstakeTeam->id }}" class="rounded border-brand-border text-brand-green" @disabled($sweepstake->isLockedForChanges())>
                                        <span class="min-w-0 flex-1">
                                            <span class="font-semibold text-brand-navy">
                                                <x-team-name :team="$sweepstakeTeam->team" />
                                            </span>
                                            <span class="text-brand-muted">#{{ $sweepstakeTeam->team->fifa_ranking ?? 'n/a' }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-5 py-4 text-sm text-brand-muted">No teams selected.</p>
                                @endforelse
                            </div>

                            <div class="border-t border-brand-border px-5 py-4">
                                <button class="sk-btn-danger" @disabled($sweepstake->isLockedForChanges())>Remove selected teams</button>
                            </div>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('sweepstakes.teams.bulk.update', $sweepstake) }}"
                            data-bulk-team-form
                            data-confirm-form
                            data-confirm-title="Restore selected teams"
                            data-confirm-message="Restored teams will become available for the next draw."
                            data-confirm-label="Restore selected teams"
                            class="flex flex-col"
                        >
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="restore">

                            <div class="border-b border-brand-border bg-blue-50/50 px-5 py-4">
                                <h3 class="font-semibold text-brand-navy">Removed teams</h3>
                                <p class="mt-1 text-sm text-brand-muted">These teams will not be included in the draw.</p>
                                <p class="mt-1 text-xs font-semibold text-brand-blue">Scroll to see all teams.</p>
                                <p class="mt-2 text-sm font-semibold text-brand-navy"><span data-selected-count>0</span> selected to restore</p>
                            </div>

                            <div class="max-h-[30rem] flex-1 overflow-y-auto divide-y divide-brand-border/70">
                                @forelse ($removedTeams as $sweepstakeTeam)
                                    <label class="flex items-center gap-3 px-5 py-3 text-sm transition hover:bg-blue-50/70">
                                        <input type="checkbox" name="team_ids[]" value="{{ $sweepstakeTeam->id }}" class="rounded border-brand-border text-brand-blue" @disabled($sweepstake->isLockedForChanges())>
                                        <span class="min-w-0 flex-1">
                                            <span class="font-semibold text-brand-navy">
                                                <x-team-name :team="$sweepstakeTeam->team" />
                                            </span>
                                            <span class="text-brand-muted">#{{ $sweepstakeTeam->team->fifa_ranking ?? 'n/a' }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="px-5 py-4 text-sm text-brand-muted">No teams removed.</p>
                                @endforelse
                            </div>

                            <div class="border-t border-brand-border px-5 py-4">
                                <button class="sk-btn-secondary" @disabled($sweepstake->isLockedForChanges())>Restore selected teams</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="pots" class="mt-6 scroll-mt-6" data-tab-panel="pots">
                @if (! $isCustomPotMode)
                    <div class="sk-card p-5">
                        <h2 class="font-semibold text-brand-navy">Pots</h2>
                        <p class="mt-1 text-sm text-brand-muted">SweepKit will create pots automatically using stored rankings.</p>
                        <p class="mt-3 text-sm text-brand-muted">Auto pots use the included teams from the Teams tab. If the teams do not divide evenly by entrants, choose a leftover team option when you run the draw.</p>
                        @if (! $sweepstake->isLockedForChanges())
                            <a href="#settings-prizes" class="sk-btn-secondary mt-4 inline-flex">Switch to Custom pots in settings</a>
                        @endif
                    </div>
                @else
                    @php
                        $customPotAssignedIds = $sweepstake->pots
                            ->flatMap(fn ($pot) => $pot->potTeams)
                            ->filter(fn ($potTeam) => $potTeam->sweepstakeTeam && $potTeam->sweepstakeTeam->is_included && ! $potTeam->sweepstakeTeam->is_removed)
                            ->pluck('sweepstake_team_id')
                            ->unique();
                        $unassignedCustomTeams = $selectedTeams
                            ->reject(fn ($sweepstakeTeam) => $customPotAssignedIds->contains($sweepstakeTeam->id))
                            ->values();
                    @endphp

                    <div id="custom-pots" class="sk-card overflow-hidden">
                        <div class="sk-card-header">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h2 class="font-semibold text-brand-navy">Custom pots</h2>
                                    <p class="mt-1 text-sm text-brand-muted">
                                        Only teams assigned to a custom pot are included in a custom draw. Unassigned teams will be ignored unless you add them to a pot.
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-brand-navy">
                                        {{ $unassignedCustomTeamCount }} {{ \Illuminate\Support\Str::plural('team', $unassignedCustomTeamCount) }} are currently unassigned and will not be included in the custom draw.
                                    </p>
                                </div>

                                @if ($sweepstake->isLockedForChanges())
                                    <p class="sk-badge sk-badge-neutral px-3 py-2 text-sm">Custom pots are locked after the draw.</p>
                                @endif
                            </div>
                        </div>

                        @if (count($customPotWarnings) > 0)
                            <div class="border-b border-brand-border bg-amber-50 px-5 py-4 text-sm text-amber-950">
                                <p class="font-semibold">Resolve these before running a custom draw.</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($customPotWarnings as $customPotWarning)
                                        <li>{{ $customPotWarning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid divide-y divide-brand-border/70 lg:grid-cols-[0.9fr_1.1fr] lg:divide-x lg:divide-y-0">
                            <div class="p-5">
                                <form method="POST" action="{{ route('sweepstakes.pots.store', $sweepstake) }}" class="rounded-lg border border-brand-border bg-brand-soft p-4">
                                    @csrf
                                    <h3 class="font-semibold text-brand-navy">Add pot</h3>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_9rem]">
                                        <label class="block">
                                            <span class="text-sm font-medium text-brand-navy">Pot name</span>
                                            <input name="name" value="{{ old('name') }}" maxlength="80" placeholder="Pot {{ $sweepstake->pots->count() + 1 }}" class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                        </label>
                                        <label class="block">
                                            <span class="text-sm font-medium text-brand-navy">Teams per entrant</span>
                                            <input type="number" name="teams_per_entrant" value="{{ old('teams_per_entrant', 1) }}" min="0" max="48" step="1" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                        </label>
                                    </div>
                                    <button class="sk-btn-green mt-3" @disabled($sweepstake->isLockedForChanges())>Create pot</button>
                                </form>

                                <div class="mt-5 space-y-3">
                                    @forelse ($sweepstake->pots as $pot)
                                        @php
                                            $summary = $customPotSummaries->firstWhere('id', $pot->id) ?? [
                                                'number' => $pot->position,
                                                'assigned_team_count' => 0,
                                                'teams_per_entrant' => $pot->teams_per_entrant,
                                                'needed_team_count' => 0,
                                                'drawn_team_count' => 0,
                                                'unused_team_count' => 0,
                                                'has_enough_teams' => true,
                                            ];
                                            $includedPotTeams = $pot->potTeams
                                                ->filter(fn ($potTeam) => (bool) $potTeam->sweepstakeTeam?->is_included && ! $potTeam->sweepstakeTeam?->is_removed)
                                                ->values();
                                        @endphp

                                        <div class="rounded-lg border border-brand-border bg-white p-4">
                                            <form method="POST" action="{{ route('sweepstakes.pots.update', [$sweepstake, $pot]) }}" class="grid gap-3 sm:grid-cols-[5rem_1fr_10rem_auto] sm:items-end">
                                                @csrf
                                                @method('PATCH')
                                                <label>
                                                    <span class="text-sm font-medium text-brand-navy">Order</span>
                                                    <input type="number" name="position" min="1" max="99" value="{{ old("pots.{$pot->id}.position", $pot->position) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                                </label>
                                                <label>
                                                    <span class="text-sm font-medium text-brand-navy">Pot name</span>
                                                    <input name="name" value="{{ old("pots.{$pot->id}.name", $pot->name) }}" required maxlength="80" class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                                </label>
                                                <label>
                                                    <span class="text-sm font-medium text-brand-navy">Teams per entrant</span>
                                                    <input type="number" name="teams_per_entrant" min="0" max="48" step="1" value="{{ old("pots.{$pot->id}.teams_per_entrant", $pot->teams_per_entrant) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                                </label>
                                                <button class="sk-btn-secondary" @disabled($sweepstake->isLockedForChanges())>Save</button>
                                            </form>

                                            <div class="mt-3 rounded-lg border border-brand-border bg-brand-soft px-3 py-2 text-sm">
                                                <p class="font-semibold text-brand-navy">Pot {{ $summary['number'] }} - {{ $pot->name }}</p>
                                                <p class="mt-1 text-brand-muted">{{ $summary['assigned_team_count'] }} {{ \Illuminate\Support\Str::plural('team', $summary['assigned_team_count']) }} assigned · {{ $summary['teams_per_entrant'] }} {{ \Illuminate\Support\Str::plural('team', $summary['teams_per_entrant']) }} per entrant</p>
                                                @if ($summary['teams_per_entrant'] > 0 && ! $summary['has_enough_teams'])
                                                    <p class="mt-1 font-semibold text-brand-danger">{{ $pot->name }} has {{ $summary['assigned_team_count'] }} {{ \Illuminate\Support\Str::plural('team', $summary['assigned_team_count']) }} but needs {{ $summary['needed_team_count'] }} to give each entrant {{ $summary['teams_per_entrant'] }} {{ \Illuminate\Support\Str::plural('team', $summary['teams_per_entrant']) }}.</p>
                                                @elseif ($summary['teams_per_entrant'] > 0)
                                                    <p class="mt-1 text-brand-muted">With {{ $memberCount }} {{ \Illuminate\Support\Str::plural('entrant', $memberCount) }}, {{ $summary['drawn_team_count'] }} {{ \Illuminate\Support\Str::plural('team', $summary['drawn_team_count']) }} will be drawn and {{ $summary['unused_team_count'] }} will be left out.</p>
                                                @else
                                                    <p class="mt-1 text-brand-muted">This pot is inactive. Its assigned teams will not be used in the custom draw.</p>
                                                @endif
                                                @if ($includedPotTeams->isNotEmpty())
                                                    <ul class="mt-2 space-y-1 text-brand-muted">
                                                        @foreach ($includedPotTeams as $potTeam)
                                                            <li><x-team-name :team="$potTeam->sweepstakeTeam->team" /></li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>

                                            <form
                                                method="POST"
                                                action="{{ route('sweepstakes.pots.destroy', [$sweepstake, $pot]) }}"
                                                data-confirm-form
                                                data-confirm-title="Delete custom pot"
                                                data-confirm-message="Only empty custom pots can be deleted."
                                                data-confirm-label="Delete pot"
                                                data-confirm-variant="danger"
                                                class="mt-3"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="sk-btn-danger" @disabled($sweepstake->isLockedForChanges() || $pot->potTeams->isNotEmpty())>Delete empty pot</button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-sm text-brand-muted">No custom pots yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <form method="POST" action="{{ route('sweepstakes.pots.assignments', $sweepstake) }}" class="flex flex-col">
                                @csrf
                                @method('PATCH')

                                <div class="border-b border-brand-border bg-blue-50/50 px-5 py-4">
                                    <h3 class="font-semibold text-brand-navy">Team pot assignments</h3>
                                    <p class="mt-1 text-sm text-brand-muted">Assign teams to pots if you want them to be eligible for the custom draw.</p>
                                    <p class="mt-1 text-sm text-brand-muted">Teams left as Unassigned will not be used in a custom draw. Removed teams are never included in the draw.</p>
                                </div>

                                <div class="max-h-[36rem] flex-1 overflow-y-auto divide-y divide-brand-border/70">
                                    @forelse ($selectedTeams as $sweepstakeTeam)
                                        @php
                                            $selectedPotId = old("assignments.{$sweepstakeTeam->id}", $sweepstakeTeam->potAssignment?->sweepstake_pot_id);
                                        @endphp

                                        <label class="grid gap-3 px-5 py-3 text-sm transition hover:bg-blue-50/70 sm:grid-cols-[1fr_13rem] sm:items-center">
                                            <span class="min-w-0 font-semibold text-brand-navy">
                                                <x-team-name :team="$sweepstakeTeam->team" />
                                                <span class="text-brand-muted">#{{ $sweepstakeTeam->team->fifa_ranking ?? 'n/a' }}</span>
                                            </span>
                                            <select name="assignments[{{ $sweepstakeTeam->id }}]" class="sk-input" @disabled($sweepstake->isLockedForChanges() || $sweepstake->pots->isEmpty())>
                                                <option value="">Unassigned</option>
                                                @foreach ($sweepstake->pots as $pot)
                                                    <option value="{{ $pot->id }}" @selected((string) $selectedPotId === (string) $pot->id)>{{ $pot->name }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @empty
                                        <p class="px-5 py-4 text-sm text-brand-muted">No included teams available.</p>
                                    @endforelse
                                </div>

                                <div class="border-t border-brand-border px-5 py-4">
                                    <button class="sk-btn-green" @disabled($sweepstake->isLockedForChanges() || $sweepstake->pots->isEmpty())>Save pot assignments</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div id="draw-results" class="mt-6 space-y-6 scroll-mt-6" data-tab-panel="draw-results">
                <div class="sk-card overflow-hidden">
                    <div class="sk-card-header">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="font-semibold text-brand-navy">Run draw</h2>
                                <p class="mt-1 text-sm text-brand-muted">{{ $isCustomPotMode ? 'Custom pots draw only from assigned teams in active pots.' : 'Auto pots use stored rankings and the included team list.' }}</p>
                            </div>
                            @if ($activeDraw)
                                <span class="sk-badge sk-badge-green">Active draw #{{ $activeDraw->version_number }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="px-5 py-4">
                        @if (! $activeDraw && $memberCount > 0 && $selectedTeamCount < $memberCount)
                            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                                You currently have {{ $memberCount }} entrants but only {{ $selectedTeamCount }} teams available. Remove entrants or restore teams before running the draw.
                            </div>
                        @elseif (! $activeDraw && $isCustomPotMode && count($customPotWarnings) > 0)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                                <p class="font-semibold">Custom pots need attention before the draw can run.</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($customPotWarnings as $customPotWarning)
                                        <li>{{ $customPotWarning }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="mt-3 text-sm font-semibold text-brand-blue underline" data-scroll-to="#pots">Review custom pots</button>
                            </div>
                        @elseif (! $activeDraw && $isCustomPotMode)
                            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                                <p class="font-semibold">Custom pots are ready.</p>
                                <p class="mt-1">{{ $customPotDrawnTeamCount }} {{ \Illuminate\Support\Str::plural('team', $customPotDrawnTeamCount) }} will be drawn from {{ $activeCustomPotCount }} active {{ \Illuminate\Support\Str::plural('pot', $activeCustomPotCount) }}. {{ $customPotUnusedTeamCount }} assigned {{ \Illuminate\Support\Str::plural('team', $customPotUnusedTeamCount) }} will be left out.</p>
                            </div>
                        @elseif (! $activeDraw && $hasLeftoverTeams)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                                <p class="font-semibold">With {{ $memberCount }} entrants and {{ $selectedTeamCount }} teams, everyone can receive {{ $baseTeamsPerMember }} teams each. There will be {{ $leftoverTeamCount }} teams left over.</p>
                                <p class="mt-1">Choose a leftover team option when you run the draw, or review the team selection first.</p>
                                <button type="button" class="mt-3 text-sm font-semibold text-brand-blue underline" data-scroll-to="#teams">Review team selection</button>
                            </div>
                        @endif

                        @if ($activeDraw)
                            <div class="flex w-full flex-wrap gap-2">
                                <details class="w-full sm:w-auto">
                                    <summary class="sk-btn-danger list-none">Re-run draw</summary>
                                    <form
                                        method="POST"
                                        action="{{ route('sweepstakes.draw.rerun', $sweepstake) }}"
                                        class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm sm:w-[22rem]"
                                        data-confirm-form
                                        data-confirm-title="Re-run draw"
                                        data-confirm-message="This will replace the active draw and notify entrants with email addresses. Previous results will remain in draw history."
                                        data-confirm-label="Re-run draw"
                                        data-confirm-variant="danger"
                                    >
                                        @csrf
                                        <p class="font-medium text-amber-950">This will replace the active draw and notify entrants with email addresses. Previous draw results will be kept in the draw history.</p>
                                        @if (! $isCustomPotMode && ! $hasLeftoverTeams)
                                            <input type="hidden" name="leftover_team_strategy" value="{{ $removeLeftoversStrategy }}">
                                        @endif
                                        @if ($hasLeftoverTeams)
                                            <fieldset class="mt-3 rounded-lg border border-amber-200 bg-white/60 p-3 text-sm text-amber-950">
                                                <legend class="font-semibold">Leftover teams</legend>
                                                <label class="mt-2 flex gap-2">
                                                    <input type="radio" name="leftover_team_strategy" value="{{ $assignLeftoversStrategy }}" required class="mt-1 border-amber-300 text-brand-green">
                                                    <span>Randomly assign the leftover teams</span>
                                                </label>
                                                <label class="mt-2 flex gap-2">
                                                    <input type="radio" name="leftover_team_strategy" value="{{ $removeLeftoversStrategy }}" required class="mt-1 border-amber-300 text-brand-green">
                                                    <span>Remove leftover teams for an even draw</span>
                                                </label>
                                            </fieldset>
                                        @endif
                                        <label class="mt-3 block">
                                            <span class="font-medium text-amber-950">Reason for re-running</span>
                                            <textarea name="reason" required rows="3" maxlength="1000" class="sk-input">{{ old('reason') }}</textarea>
                                        </label>
                                        <button class="sk-btn-danger mt-3">Confirm re-run draw</button>
                                    </form>
                                </details>

                                <details class="w-full sm:w-auto">
                                    <summary class="sk-btn-danger list-none">Cancel current draw</summary>
                                    <form
                                        method="POST"
                                        action="{{ route('sweepstakes.draw.cancel', $sweepstake) }}"
                                        class="mt-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm sm:w-[22rem]"
                                        data-confirm-form
                                        data-confirm-title="Cancel current draw"
                                        data-confirm-message="Cancelling the current draw will reopen setup. The previous draw will remain in draw history for transparency."
                                        data-confirm-label="Cancel draw"
                                        data-confirm-variant="danger"
                                    >
                                        @csrf
                                        <p class="font-medium text-red-950">Cancelling the current draw will reopen setup. You can add entrants or change teams, then run a new draw. The previous draw will remain in draw history for transparency.</p>
                                        <label class="mt-3 block">
                                            <span class="font-medium text-red-950">Reason for cancelling</span>
                                            <textarea name="reason" required rows="3" maxlength="1000" class="sk-input">{{ old('reason') }}</textarea>
                                        </label>
                                        <button class="sk-btn-danger mt-3">Cancel draw</button>
                                    </form>
                                </details>
                            </div>
                        @else
                            <form
                                method="POST"
                                action="{{ route('sweepstakes.draw.store', $sweepstake) }}"
                                data-confirm-form
                                data-confirm-title="Run draw"
                                data-confirm-message="{{ $isCustomPotMode ? 'This will assign teams from active custom pots and notify entrants with email addresses.' : 'This will assign teams to every entrant and notify entrants with email addresses.' }}"
                                data-confirm-label="Run draw"
                                class="mt-4 max-w-xl space-y-3"
                            >
                                @csrf
                                @if (! $isCustomPotMode && ! $hasLeftoverTeams)
                                    <input type="hidden" name="leftover_team_strategy" value="{{ $removeLeftoversStrategy }}">
                                @endif
                                @if ($isCustomPotMode)
                                    <div class="rounded-lg border border-brand-border bg-brand-soft p-3 text-sm text-brand-muted">
                                        <p class="font-semibold text-brand-navy">Custom pots selected</p>
                                        <p class="mt-1">Each entrant receives the configured number of teams from every active custom pot.</p>
                                    </div>
                                @endif
                                @if ($hasLeftoverTeams)
                                    <fieldset class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                                        <legend class="font-semibold">Leftover teams</legend>
                                        <label class="mt-2 flex gap-2">
                                            <input type="radio" name="leftover_team_strategy" value="{{ $assignLeftoversStrategy }}" required class="mt-1 border-amber-300 text-brand-green">
                                            <span>Randomly assign the leftover teams</span>
                                        </label>
                                        <label class="mt-2 flex gap-2">
                                            <input type="radio" name="leftover_team_strategy" value="{{ $removeLeftoversStrategy }}" required class="mt-1 border-amber-300 text-brand-green">
                                            <span>Remove leftover teams for an even draw</span>
                                        </label>
                                    </fieldset>
                                @endif
                                <button class="sk-btn-green" @disabled($sweepstake->isLockedForChanges())>{{ $isCustomPotMode ? 'Run custom pot draw' : 'Run ranked pot draw' }}</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="sk-card overflow-hidden">
                    <div class="sk-card-header">
                        <h2 class="font-semibold text-brand-navy">Draw results</h2>
                        <p class="mt-1 text-sm text-brand-muted">
                            {{ $activeDraw ? 'Active draw #' . $activeDraw->version_number . ' results grouped by entrant.' : 'Assigned teams grouped by entrant.' }}
                        </p>
                    </div>

                    @if ($activeDraw && $drawAssignmentCount > 0)
                        <div class="divide-y divide-brand-border/70">
                            @foreach ($sweepstake->members as $member)
                                @php
                                    $memberAssignments = $activeDraw->assignments
                                        ->where('sweepstake_member_id', $member->id)
                                        ->sortBy(fn ($assignment) => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                                        ->values();
                                @endphp

                                <div class="px-5 py-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-brand-navy">{{ $member->name }} has {{ $memberAssignments->count() }} {{ \Illuminate\Support\Str::plural('team', $memberAssignments->count()) }}</h3>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <span class="sk-badge {{ $member->is_paid ? 'sk-badge-green' : 'sk-badge-amber' }}">{{ $member->is_paid ? 'Paid' : 'Not paid yet' }}</span>
                                                <span class="sk-badge {{ $member->source === \App\Models\SweepstakeMember::SOURCE_JOIN_LINK ? 'sk-badge-blue' : 'sk-badge-navy' }}">{{ $member->sourceLabel() }}</span>
                                            </div>
                                        </div>

                                        @if ($member->join_token)
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <a class="sk-btn-pill" href="{{ route('entrants.show', $member->join_token) }}">View drawn teams</a>
                                                <x-copy-button
                                                    :value="route('entrants.show', $member->join_token)"
                                                    label="Copy private team link"
                                                    button-label="Copy link"
                                                />
                                            </div>
                                        @endif
                                    </div>

                                    @if ($memberAssignments->isNotEmpty())
                                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                            @foreach ($memberAssignments as $assignment)
                                                <li class="rounded-lg border border-brand-border bg-brand-soft px-3 py-2 text-sm">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <span class="font-semibold text-brand-navy">
                                                            <x-team-name :team="$assignment->team" />
                                                        </span>
                                                        <span class="sk-badge sk-badge-blue">Pot {{ $assignment->pot_number ?? 'n/a' }}</span>
                                                    </div>
                                                    <p class="mt-1 text-xs text-brand-muted">Ranking {{ $assignment->team->fifa_ranking ?? 'n/a' }}</p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="mt-3 text-sm text-brand-muted">No teams assigned to this entrant.</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-5 py-4 text-sm text-brand-muted">
                            <p class="font-semibold text-brand-navy">The draw has not been run yet.</p>
                            <p class="mt-1">Run the draw to assign teams to entrants.</p>
                        </div>
                    @endif
                </div>

                <div class="sk-card p-5">
                    <h2 class="font-semibold text-brand-navy">Draw history</h2>

                    @if ($draws->isEmpty())
                        <p class="mt-3 text-sm text-brand-muted">No draws yet. History will appear here after the first draw.</p>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($draws->sortByDesc('version_number') as $draw)
                                <div class="rounded-lg border border-brand-border bg-brand-soft p-3 text-sm">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="font-semibold text-brand-navy">Draw #{{ $draw->version_number }}</p>
                                            <p class="mt-1 text-brand-muted">{{ $draw->ran_at->format('j M Y \a\t H:i') }}</p>
                                        </div>
                                        <span class="sk-badge {{ $draw->status === \App\Models\SweepstakeDraw::STATUS_ACTIVE ? 'sk-badge-green' : ($draw->status === \App\Models\SweepstakeDraw::STATUS_CANCELLED ? 'sk-badge-amber' : 'sk-badge-neutral') }}">
                                            {{ $draw->statusLabel() }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-brand-muted">{{ $draw->assignments->count() }} {{ \Illuminate\Support\Str::plural('assignment', $draw->assignments->count()) }}</p>
                                    <p class="mt-1 text-brand-muted">Draw rule: {{ $draw->potModeLabel() }}</p>
                                    @if ($draw->leftover_strategy)
                                        <p class="mt-1 text-brand-muted">{{ $draw->leftoverStrategyLabel() }}</p>
                                    @endif
                                    @if ($draw->custom_pot_summary)
                                        <ul class="mt-2 space-y-1 text-brand-muted">
                                            @foreach ($draw->custom_pot_summary as $summary)
                                                <li>{{ $summary['name'] }} drew {{ $summary['drawn_team_count'] }} of {{ $summary['assigned_team_count'] }} teams; {{ $summary['unused_team_count'] }} left out.</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($draw->reason)
                                        <p class="mt-2 text-brand-muted">Reason: {{ $draw->reason }}</p>
                                    @endif
                                    @if ($draw->cancelled_reason)
                                        <p class="mt-2 text-brand-muted">Cancellation reason: {{ $draw->cancelled_reason }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div id="settings-prizes" class="mt-6 grid gap-6 xl:grid-cols-2 scroll-mt-6" data-tab-panel="settings-prizes">
                <form method="POST" action="{{ route('sweepstakes.settings.update', $sweepstake) }}" class="sk-card p-5">
                    @csrf
                    @method('PATCH')

                    <h2 class="font-semibold text-brand-navy">Sweepstake settings</h2>
                    <p class="mt-1 text-sm text-brand-muted">
                        {{ $sweepstake->isLockedForChanges() ? 'Settings are locked after the draw.' : 'These settings can be changed before the draw.' }}
                    </p>

                    <div class="mt-4 space-y-4">
                        <label class="block">
                            <span class="text-sm font-medium text-brand-navy">Sweepstake name</span>
                            <input name="sweepstake_name" value="{{ old('sweepstake_name', $sweepstake->name) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                        </label>

                        <div class="grid grid-cols-[1fr_90px] gap-3">
                            <label class="block">
                                <span class="text-sm font-medium text-brand-navy">Entry fee</span>
                                <input type="number" name="entry_fee" value="{{ old('entry_fee', (float) $sweepstake->entry_fee) }}" min="0" step="0.01" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-brand-navy">Currency</span>
                                <input name="currency" value="{{ old('currency', $sweepstake->currency) }}" maxlength="3" required class="sk-input uppercase" @disabled($sweepstake->isLockedForChanges())>
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-sm font-medium text-brand-navy">Status</span>
                            <select name="status" class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                <option value="{{ \App\Models\Sweepstake::STATUS_DRAFT }}" @selected(old('status', $sweepstake->status) === \App\Models\Sweepstake::STATUS_DRAFT)>Draft</option>
                                <option value="{{ \App\Models\Sweepstake::STATUS_OPEN }}" @selected(old('status', $sweepstake->status) === \App\Models\Sweepstake::STATUS_OPEN)>Open</option>
                            </select>
                        </label>

                        <fieldset>
                            <legend class="text-sm font-medium text-brand-navy">Draw rule</legend>
                            <div class="mt-2 space-y-2">
                                <label class="flex gap-2 rounded-lg border border-brand-border bg-white px-3 py-2 text-sm text-brand-muted">
                                    <input type="radio" name="pot_mode" value="{{ \App\Models\Sweepstake::POT_MODE_AUTO }}" class="mt-1 border-brand-border text-brand-green" @checked(old('pot_mode', $sweepstake->pot_mode) === \App\Models\Sweepstake::POT_MODE_AUTO) @disabled($sweepstake->isLockedForChanges())>
                                    <span>
                                        <span class="block font-semibold text-brand-navy">Auto pots</span>
                                        <span>Use stored rankings to create even pots.</span>
                                    </span>
                                </label>
                                <label class="flex gap-2 rounded-lg border border-brand-border bg-white px-3 py-2 text-sm text-brand-muted">
                                    <input type="radio" name="pot_mode" value="{{ \App\Models\Sweepstake::POT_MODE_CUSTOM }}" class="mt-1 border-brand-border text-brand-green" @checked(old('pot_mode', $sweepstake->pot_mode) === \App\Models\Sweepstake::POT_MODE_CUSTOM) @disabled($sweepstake->isLockedForChanges())>
                                    <span>
                                        <span class="block font-semibold text-brand-navy">Custom pots</span>
                                        <span>Use manually assigned pot groups.</span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>
                    </div>

                    <button class="sk-btn-green mt-5" @disabled($sweepstake->isLockedForChanges())>Save settings</button>

                    <dl class="mt-5 space-y-3 border-t border-brand-border pt-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Entry fee</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->currency }} {{ number_format((float) $sweepstake->entry_fee, 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Collected pot</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->currency }} {{ number_format($sweepstake->collectedPot(), 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Entrants in draw</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->members->count() }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Paid entrants</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->members->where('is_paid', true)->count() }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Draw rule</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->potModeLabel() }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Teams per entrant</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->teams_per_member ?? 'Not drawn' }}</dd>
                        </div>
                    </dl>
                </form>

                <div class="sk-card p-5">
                    <h2 class="font-semibold text-brand-navy">Prizes</h2>
                    <p class="mt-1 text-sm text-brand-muted">Track payouts without taking payments in SweepKit.</p>

                    <dl class="mt-4 space-y-3 border-y border-brand-border py-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Total prizes</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->currency }} {{ number_format($totalPrizePayout, 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Collected pot</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->currency }} {{ number_format($sweepstake->collectedPot(), 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-brand-muted">Expected entry pot</dt>
                            <dd class="font-semibold text-brand-navy">{{ $sweepstake->currency }} {{ number_format($expectedEntryPot, 2) }}</dd>
                        </div>
                    </dl>

                    @if ($sweepstake->prizes->isNotEmpty())
                        <form method="POST" action="{{ route('sweepstakes.prizes.update', $sweepstake) }}" class="mt-5 space-y-4">
                            @csrf
                            @method('PATCH')

                            @foreach ($sweepstake->prizes as $prize)
                                <div class="rounded-lg border border-brand-border bg-brand-soft p-3">
                                    <input type="hidden" name="prizes[{{ $prize->id }}][id]" value="{{ $prize->id }}">
                                    <div class="grid grid-cols-[76px_1fr] gap-3">
                                        <label>
                                            <span class="text-sm font-medium text-brand-navy">Position</span>
                                            <input type="number" name="prizes[{{ $prize->id }}][position]" min="1" max="48" value="{{ old("prizes.{$prize->id}.position", $prize->position) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                        </label>
                                        <label>
                                            <span class="text-sm font-medium text-brand-navy">Label</span>
                                            <input name="prizes[{{ $prize->id }}][label]" value="{{ old("prizes.{$prize->id}.label", $prize->label) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                        </label>
                                    </div>
                                    <label class="mt-3 block">
                                        <span class="text-sm font-medium text-brand-navy">Prize amount</span>
                                        <input type="number" name="prizes[{{ $prize->id }}][amount]" min="0" step="0.01" value="{{ old("prizes.{$prize->id}.amount", (float) $prize->amount) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                                    </label>
                                </div>
                            @endforeach

                            <button class="sk-btn-green" @disabled($sweepstake->isLockedForChanges())>Save prizes</button>
                        </form>

                        <div class="mt-4 space-y-2 border-t border-brand-border pt-4">
                            @foreach ($sweepstake->prizes as $prize)
                                <form
                                    method="POST"
                                    action="{{ route('sweepstakes.prizes.destroy', [$sweepstake, $prize]) }}"
                                    data-confirm-form
                                    data-confirm-title="Remove prize"
                                    data-confirm-message="This will remove {{ $prize->label }} from the prize list before the draw."
                                    data-confirm-label="Remove prize"
                                    data-confirm-variant="danger"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button class="sk-btn-danger" @disabled($sweepstake->isLockedForChanges())>Remove {{ $prize->label }}</button>
                                </form>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-5 text-sm text-brand-muted">No prizes yet.</p>
                    @endif

                    <form method="POST" action="{{ route('sweepstakes.prizes.store', $sweepstake) }}" class="mt-5 border-t border-brand-border pt-5">
                        @csrf
                        <h3 class="font-semibold text-brand-navy">Add prize</h3>
                        <div class="mt-3 grid grid-cols-[76px_1fr] gap-3">
                            <label>
                                <span class="text-sm font-medium text-brand-navy">Position</span>
                                <input type="number" name="position" min="1" max="48" value="{{ old('position', $sweepstake->prizes->count() + 1) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                            </label>
                            <label>
                                <span class="text-sm font-medium text-brand-navy">Label</span>
                                <input name="label" value="{{ old('label', $sweepstake->prizes->isEmpty() ? 'Winner' : 'Runner-up') }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                            </label>
                        </div>

                        <label class="mt-3 block">
                            <span class="text-sm font-medium text-brand-navy">Prize amount</span>
                            <input type="number" name="amount" min="0" step="0.01" value="{{ old('amount', 0) }}" required class="sk-input" @disabled($sweepstake->isLockedForChanges())>
                        </label>

                        <button class="sk-btn-green mt-4" @disabled($sweepstake->isLockedForChanges())>Add prize</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
