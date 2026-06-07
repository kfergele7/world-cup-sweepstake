@extends('layouts.app')

@section('content')
    <div class="grid gap-8 xl:grid-cols-[1fr_360px]">
        <section>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">{{ ucfirst($sweepstake->status) }}</p>
                    <h1 class="mt-2 text-3xl font-black text-brand-navy">{{ $sweepstake->name }}</h1>
                    <p class="mt-2 text-sm text-brand-muted">Join link: <a class="font-semibold text-brand-blue underline" href="{{ route('join.show', $sweepstake->join_code) }}">{{ route('join.show', $sweepstake->join_code) }}</a></p>
                </div>

                @if ($activeDraw)
                    <p class="sk-badge sk-badge-green px-4 py-2 text-sm">Active draw #{{ $activeDraw->version_number }}</p>
                @else
                    <form method="POST" action="{{ route('sweepstakes.draw.store', $sweepstake) }}">
                        @csrf
                        <button class="sk-btn-green" @disabled($sweepstake->isLockedForChanges())>Run ranked pot draw</button>
                    </form>
                @endif
            </div>

            @if ($prizeWarning)
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 shadow-sm">
                    {{ $prizeWarning }}
                </div>
            @endif

            <div class="sk-card mt-8 overflow-hidden">
                <div class="sk-card-header">
                    <h2 class="font-semibold text-brand-navy">Entrants</h2>
                    <p class="mt-1 text-sm text-brand-muted">All entrants are included in the draw. Paid status is just for your own tracking.</p>
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
                        <div class="px-5 py-4">
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
                                    @endif

                                    <details class="group basis-full sm:basis-auto">
                                        <summary class="sk-btn-pill list-none">Manage</summary>
                                        <div class="mt-4 w-full rounded-lg border border-brand-border bg-brand-soft p-4 sm:w-[32rem]">
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
                                                <form method="POST" action="{{ route('sweepstakes.members.destroy', [$sweepstake, $member]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="sk-btn-danger" @disabled($sweepstake->isLockedForChanges())>Remove entrant</button>
                                                </form>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-brand-muted">No entrants yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="sk-card mt-8 overflow-hidden">
                <div class="sk-card-header">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-brand-navy">Draw results</h2>
                            <p class="mt-1 text-sm text-brand-muted">
                                {{ $activeDraw ? 'Active draw #' . $activeDraw->version_number . ' results grouped by entrant.' : 'Assigned teams grouped by entrant.' }}
                            </p>
                        </div>

                        @if ($activeDraw)
                            <details class="w-full sm:w-auto">
                                <summary class="sk-btn-danger list-none">Re-run draw</summary>
                                <form method="POST" action="{{ route('sweepstakes.draw.rerun', $sweepstake) }}" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
                                    @csrf
                                    <p class="font-medium text-amber-950">This will replace the active draw and notify entrants with email addresses. Previous draw results will be kept in the draw history.</p>
                                    <label class="mt-3 block">
                                        <span class="font-medium text-amber-950">Reason for re-running</span>
                                        <textarea name="reason" required rows="3" maxlength="1000" class="sk-input">{{ old('reason') }}</textarea>
                                    </label>
                                    <button class="sk-btn-primary mt-3">Confirm re-run draw</button>
                                </form>
                            </details>
                        @endif
                    </div>
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
                                        <a class="sk-btn-pill" href="{{ route('entrants.show', $member->join_token) }}">View drawn teams</a>
                                    @endif
                                </div>

                                @if ($memberAssignments->isNotEmpty())
                                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                        @foreach ($memberAssignments as $assignment)
                                            <li class="rounded-lg border border-brand-border bg-brand-soft px-3 py-2 text-sm">
                                                <div class="flex items-center justify-between gap-3">
                                                    <span class="font-semibold text-brand-navy">
                                                        @if ($assignment->team->flag)
                                                            <span aria-hidden="true">{{ $assignment->team->flag }}</span>
                                                        @endif
                                                        {{ $assignment->team->name }}
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

                @if ($draws->isNotEmpty())
                    <div class="border-t border-brand-border px-5 py-4">
                        <h3 class="font-semibold text-brand-navy">Draw history</h3>
                        <div class="mt-4 space-y-4">
                            @foreach ($draws->sortByDesc('version_number') as $draw)
                                <details class="rounded-lg border border-brand-border bg-brand-soft p-4" {{ $draw->status === \App\Models\SweepstakeDraw::STATUS_ACTIVE ? 'open' : '' }}>
                                    <summary class="list-none">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-brand-navy">Draw #{{ $draw->version_number }} — run on {{ $draw->ran_at->format('j M Y \a\t H:i') }}</p>
                                                @if ($draw->reason)
                                                    <p class="mt-1 text-sm text-brand-muted">Reason: {{ $draw->reason }}</p>
                                                @endif
                                            </div>
                                            <span class="sk-badge {{ $draw->status === \App\Models\SweepstakeDraw::STATUS_ACTIVE ? 'sk-badge-green' : 'sk-badge-neutral' }}">
                                                {{ $draw->status === \App\Models\SweepstakeDraw::STATUS_ACTIVE ? 'Active draw' : 'Superseded' }}
                                            </span>
                                        </div>
                                    </summary>

                                    <div class="mt-4 grid gap-3">
                                        @foreach ($sweepstake->members as $member)
                                            @php
                                                $drawMemberAssignments = $draw->assignments
                                                    ->where('sweepstake_member_id', $member->id)
                                                    ->sortBy(fn ($assignment) => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                                                    ->values();
                                            @endphp

                                            <div class="rounded-lg border border-brand-border bg-white px-3 py-2 text-sm">
                                                <p class="font-semibold text-brand-navy">{{ $member->name }}</p>
                                                @if ($drawMemberAssignments->isNotEmpty())
                                                    <p class="mt-1 text-brand-muted">
                                                        {{ $drawMemberAssignments->map(fn ($assignment) => $assignment->team->name . ' (Pot ' . ($assignment->pot_number ?? 'n/a') . ')')->join(', ') }}
                                                    </p>
                                                @else
                                                    <p class="mt-1 text-brand-muted">No teams assigned.</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="sk-card mt-8 overflow-hidden">
                <div class="sk-card-header">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-brand-navy">Team selection</h2>
                            <p class="mt-1 text-sm text-brand-muted">
                                Included teams: {{ $selectedTeams->count() }} · Removed teams: {{ $removedTeams->count() }}
                            </p>
                        </div>

                        @if ($sweepstake->isLockedForChanges())
                            <p class="sk-badge sk-badge-neutral px-3 py-2 text-sm">Team selection is locked after the draw.</p>
                        @endif
                    </div>
                </div>

                <div class="grid divide-y divide-brand-border/70 md:grid-cols-2 md:divide-x md:divide-y-0">
                    <form method="POST" action="{{ route('sweepstakes.teams.bulk.update', $sweepstake) }}" data-bulk-team-form class="flex flex-col">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="remove">

                        <div class="border-b border-brand-border bg-green-50/50 px-5 py-4">
                            <h3 class="font-semibold text-brand-navy">Included teams</h3>
                            <p class="mt-1 text-sm text-brand-muted">Select teams to remove.</p>
                            <p class="mt-1 text-xs font-semibold text-brand-green">Scroll to see all teams.</p>
                            <p class="mt-2 text-sm font-semibold text-brand-navy"><span data-selected-count>0</span> selected to remove</p>
                        </div>

                        <div class="max-h-[22.75rem] flex-1 overflow-y-auto divide-y divide-brand-border/70">
                            @forelse ($selectedTeams as $sweepstakeTeam)
                                <label class="flex items-center gap-3 px-5 py-3 text-sm transition hover:bg-green-50/70">
                                    <input type="checkbox" name="team_ids[]" value="{{ $sweepstakeTeam->id }}" class="rounded border-brand-border text-brand-green" @disabled($sweepstake->isLockedForChanges())>
                                    <span class="min-w-0 flex-1">
                                        <span class="font-semibold text-brand-navy">
                                            @if ($sweepstakeTeam->team->flag)
                                                <span aria-hidden="true">{{ $sweepstakeTeam->team->flag }}</span>
                                            @endif
                                            {{ $sweepstakeTeam->team->name }}
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

                    <form method="POST" action="{{ route('sweepstakes.teams.bulk.update', $sweepstake) }}" data-bulk-team-form class="flex flex-col">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="restore">

                        <div class="border-b border-brand-border bg-blue-50/50 px-5 py-4">
                            <h3 class="font-semibold text-brand-navy">Removed teams</h3>
                            <p class="mt-1 text-sm text-brand-muted">These teams will not be included in the draw.</p>
                            <p class="mt-1 text-xs font-semibold text-brand-blue">Scroll to see all teams.</p>
                            <p class="mt-2 text-sm font-semibold text-brand-navy"><span data-selected-count>0</span> selected to restore</p>
                        </div>

                        <div class="max-h-[22.75rem] flex-1 overflow-y-auto divide-y divide-brand-border/70">
                            @forelse ($removedTeams as $sweepstakeTeam)
                                <label class="flex items-center gap-3 px-5 py-3 text-sm transition hover:bg-blue-50/70">
                                    <input type="checkbox" name="team_ids[]" value="{{ $sweepstakeTeam->id }}" class="rounded border-brand-border text-brand-blue" @disabled($sweepstake->isLockedForChanges())>
                                    <span class="min-w-0 flex-1">
                                        <span class="font-semibold text-brand-navy">
                                            @if ($sweepstakeTeam->team->flag)
                                                <span aria-hidden="true">{{ $sweepstakeTeam->team->flag }}</span>
                                            @endif
                                            {{ $sweepstakeTeam->team->name }}
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
        </section>

        <aside class="space-y-6">
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
                        <dt class="text-brand-muted">Draw mode</dt>
                        <dd class="font-semibold text-brand-navy">Ranked pots</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-brand-muted">Teams per entrant</dt>
                        <dd class="font-semibold text-brand-navy">{{ $sweepstake->teams_per_member ?? 'Not drawn' }}</dd>
                    </div>
                </dl>
            </form>

            <form method="POST" action="{{ route('sweepstakes.prizes.store', $sweepstake) }}" class="sk-card p-5">
                @csrf
                <h2 class="font-semibold text-brand-navy">Prize payout</h2>
                <p class="mt-1 text-sm text-brand-muted">Track payouts without taking payments in SweepKit.</p>

                <div class="mt-4 grid grid-cols-[90px_1fr] gap-3">
                    <label>
                        <span class="text-sm font-medium text-brand-navy">Position</span>
                        <input type="number" name="position" min="1" value="{{ old('position', 1) }}" class="sk-input">
                    </label>
                    <label>
                        <span class="text-sm font-medium text-brand-navy">Label</span>
                        <input name="label" value="{{ old('label', 'Winner') }}" class="sk-input">
                    </label>
                </div>

                <label class="mt-4 block">
                    <span class="text-sm font-medium text-brand-navy">Amount</span>
                    <input type="number" name="amount" min="0" step="0.01" value="{{ old('amount', 0) }}" class="sk-input">
                </label>

                <button class="sk-btn-green mt-5" @disabled($sweepstake->isLockedForChanges())>Save prize</button>

                @if ($sweepstake->prizes->isNotEmpty())
                    <ul class="mt-5 divide-y divide-brand-border/70 text-sm">
                        @foreach ($sweepstake->prizes as $prize)
                            <li class="flex justify-between gap-3 py-2">
                                <span class="text-brand-navy">{{ $prize->position }}. {{ $prize->label }}</span>
                                <span class="font-semibold text-brand-navy">{{ $sweepstake->currency }} {{ number_format((float) $prize->amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </form>
        </aside>
    </div>
@endsection
