@extends('layouts.app')

@section('content')
    <div class="grid gap-8 xl:grid-cols-[1fr_360px]">
        <section>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-normal text-red-700">{{ ucfirst($sweepstake->status) }}</p>
                    <h1 class="mt-2 text-3xl font-semibold">{{ $sweepstake->name }}</h1>
                    <p class="mt-2 text-sm text-zinc-600">Join link: <a class="font-medium text-zinc-950 underline" href="{{ route('join.show', $sweepstake->join_code) }}">{{ route('join.show', $sweepstake->join_code) }}</a></p>
                </div>

                <form method="POST" action="{{ route('sweepstakes.draw.store', $sweepstake) }}">
                    @csrf
                    <button class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800" @disabled($sweepstake->isLockedForChanges())>Run ranked pot draw</button>
                </form>
            </div>

            @if ($prizeWarning)
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ $prizeWarning }}
                </div>
            @endif

            <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Entrants</h2>
                    <p class="mt-1 text-sm text-zinc-600">All entrants are included in the draw. Paid status is for admin tracking only.</p>
                </div>

                <form method="POST" action="{{ route('sweepstakes.members.store', $sweepstake) }}" class="grid gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-4 lg:grid-cols-[1fr_1fr_auto_auto] lg:items-end">
                    @csrf
                    <label>
                        <span class="text-sm font-medium text-zinc-700">Name</span>
                        <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" @disabled($sweepstake->isLockedForChanges())>
                    </label>
                    <label>
                        <span class="text-sm font-medium text-zinc-700">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" @disabled($sweepstake->isLockedForChanges())>
                    </label>
                    <label class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700">
                        <input type="checkbox" name="is_paid" value="1" class="rounded border-zinc-300" @checked(old('is_paid')) @disabled($sweepstake->isLockedForChanges())>
                        Paid
                    </label>
                    <button class="rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800" @disabled($sweepstake->isLockedForChanges())>Add entrant</button>
                    <p class="lg:col-span-4 text-sm text-zinc-600">You can add people manually if they do not want to use the join link.</p>
                </form>

                <div class="divide-y divide-zinc-100">
                    @forelse ($sweepstake->members as $member)
                        <div class="grid gap-4 px-5 py-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium">{{ $member->name }}</p>
                                    <span class="rounded-lg border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700">{{ $member->is_paid ? 'Paid' : 'Not paid yet' }}</span>
                                    <span class="rounded-lg border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700">{{ $member->sourceLabel() }}</span>
                                </div>
                                <p class="mt-1 text-sm text-zinc-600">
                                    {{ $member->email ?: 'No email' }} · Joined {{ $member->created_at->format('j M Y') }}
                                </p>
                                @if ($member->join_token)
                                    <p class="mt-1 text-sm text-zinc-600">
                                        Private teams link:
                                        <a class="font-medium text-zinc-950 underline" href="{{ route('entrants.show', $member->join_token) }}">{{ route('entrants.show', $member->join_token) }}</a>
                                    </p>
                                @endif
                            </div>

                            <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto] lg:items-end">
                                <form id="member-edit-{{ $member->id }}" method="POST" action="{{ route('sweepstakes.members.update', [$sweepstake, $member]) }}" class="grid gap-3 sm:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <label>
                                        <span class="text-sm font-medium text-zinc-700">Name</span>
                                        <input name="name" value="{{ old('name', $member->name) }}" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" @disabled($sweepstake->isLockedForChanges())>
                                    </label>
                                    <label>
                                        <span class="text-sm font-medium text-zinc-700">Email</span>
                                        <input type="email" name="email" value="{{ old('email', $member->email) }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" @disabled($sweepstake->isLockedForChanges())>
                                    </label>
                                </form>

                                <button form="member-edit-{{ $member->id }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" @disabled($sweepstake->isLockedForChanges())>Save entrant</button>

                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('sweepstakes.members.payment.update', [$sweepstake, $member]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_paid" value="{{ $member->is_paid ? 0 : 1 }}">
                                        <button class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" @disabled($sweepstake->isLockedForChanges())>
                                            {{ $member->is_paid ? 'Mark as unpaid' : 'Mark as paid' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('sweepstakes.members.destroy', [$sweepstake, $member]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50" @disabled($sweepstake->isLockedForChanges())>Remove entrant</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-zinc-600">No entrants yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Draw results</h2>
                    <p class="mt-1 text-sm text-zinc-600">Assigned teams grouped by entrant.</p>
                </div>

                @if ($drawAssignmentCount > 0)
                    <div class="divide-y divide-zinc-100">
                        @foreach ($sweepstake->members as $member)
                            @php
                                $memberAssignments = $member->assignments
                                    ->sortBy(fn ($assignment) => sprintf('%03d-%08d', $assignment->pot_number ?? 0, $assignment->id))
                                    ->values();
                            @endphp

                            <div class="px-5 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-medium">{{ $member->name }} has {{ $memberAssignments->count() }} {{ \Illuminate\Support\Str::plural('team', $memberAssignments->count()) }}</h3>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="rounded-lg border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700">{{ $member->is_paid ? 'Paid' : 'Not paid yet' }}</span>
                                            <span class="rounded-lg border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700">{{ $member->sourceLabel() }}</span>
                                        </div>
                                    </div>

                                    @if ($member->join_token)
                                        <a class="text-sm font-medium text-zinc-700 underline hover:text-zinc-950" href="{{ route('entrants.show', $member->join_token) }}">Private entrant view</a>
                                    @endif
                                </div>

                                @if ($memberAssignments->isNotEmpty())
                                    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                                        @foreach ($memberAssignments as $assignment)
                                            <li class="rounded-lg border border-zinc-200 px-3 py-2 text-sm">
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
                                @else
                                    <p class="mt-3 text-sm text-zinc-600">No teams assigned to this entrant.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-5 py-4 text-sm text-zinc-600">
                        <p class="font-medium text-zinc-800">The draw has not been run yet.</p>
                        <p class="mt-1">Run the draw to assign teams to entrants.</p>
                    </div>
                @endif
            </div>

            <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">Team selection</h2>
                            <p class="mt-1 text-sm text-zinc-600">
                                Included teams: {{ $selectedTeams->count() }} · Removed teams: {{ $removedTeams->count() }}
                            </p>
                        </div>

                        @if ($sweepstake->isLockedForChanges())
                            <p class="rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700">Team selection is locked after the draw.</p>
                        @endif
                    </div>
                </div>

                <div class="grid divide-y divide-zinc-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                    <form method="POST" action="{{ route('sweepstakes.teams.bulk.update', $sweepstake) }}" data-bulk-team-form class="flex flex-col">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="remove">

                        <div class="border-b border-zinc-100 px-5 py-4">
                            <h3 class="font-medium">Included teams</h3>
                            <p class="mt-1 text-sm text-zinc-600">Select teams to remove.</p>
                            <p class="mt-2 text-sm font-medium text-zinc-700"><span data-selected-count>0</span> selected to remove</p>
                        </div>

                        <div class="max-h-[28rem] flex-1 overflow-y-auto divide-y divide-zinc-100">
                            @forelse ($selectedTeams as $sweepstakeTeam)
                                <label class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-zinc-50">
                                    <input type="checkbox" name="team_ids[]" value="{{ $sweepstakeTeam->id }}" class="rounded border-zinc-300" @disabled($sweepstake->isLockedForChanges())>
                                    <span class="min-w-0 flex-1">
                                        <span class="font-medium">
                                            @if ($sweepstakeTeam->team->flag)
                                                <span aria-hidden="true">{{ $sweepstakeTeam->team->flag }}</span>
                                            @endif
                                            {{ $sweepstakeTeam->team->name }}
                                        </span>
                                        <span class="text-zinc-500">#{{ $sweepstakeTeam->team->fifa_ranking ?? 'n/a' }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-5 py-4 text-sm text-zinc-600">No teams selected.</p>
                            @endforelse
                        </div>

                        <div class="border-t border-zinc-100 px-5 py-4">
                            <button class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800" @disabled($sweepstake->isLockedForChanges())>Remove selected teams</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('sweepstakes.teams.bulk.update', $sweepstake) }}" data-bulk-team-form class="flex flex-col">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="restore">

                        <div class="border-b border-zinc-100 px-5 py-4">
                            <h3 class="font-medium">Removed teams</h3>
                            <p class="mt-1 text-sm text-zinc-600">These teams will not be included in the draw.</p>
                            <p class="mt-2 text-sm font-medium text-zinc-700"><span data-selected-count>0</span> selected to restore</p>
                        </div>

                        <div class="max-h-[28rem] flex-1 overflow-y-auto divide-y divide-zinc-100">
                            @forelse ($removedTeams as $sweepstakeTeam)
                                <label class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-zinc-50">
                                    <input type="checkbox" name="team_ids[]" value="{{ $sweepstakeTeam->id }}" class="rounded border-zinc-300" @disabled($sweepstake->isLockedForChanges())>
                                    <span class="min-w-0 flex-1">
                                        <span class="font-medium">
                                            @if ($sweepstakeTeam->team->flag)
                                                <span aria-hidden="true">{{ $sweepstakeTeam->team->flag }}</span>
                                            @endif
                                            {{ $sweepstakeTeam->team->name }}
                                        </span>
                                        <span class="text-zinc-500">#{{ $sweepstakeTeam->team->fifa_ranking ?? 'n/a' }}</span>
                                    </span>
                                </label>
                            @empty
                                <p class="px-5 py-4 text-sm text-zinc-600">No teams removed.</p>
                            @endforelse
                        </div>

                        <div class="border-t border-zinc-100 px-5 py-4">
                            <button class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50" @disabled($sweepstake->isLockedForChanges())>Restore selected teams</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <aside class="space-y-6">
            <form method="POST" action="{{ route('sweepstakes.settings.update', $sweepstake) }}" class="rounded-lg border border-zinc-200 bg-white p-5">
                @csrf
                @method('PATCH')

                <h2 class="font-semibold">Sweepstake settings</h2>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $sweepstake->isLockedForChanges() ? 'Settings are locked after the draw.' : 'These settings can be changed before the draw.' }}
                </p>

                <div class="mt-4 space-y-4">
                    <label class="block">
                        <span class="text-sm font-medium text-zinc-700">Sweepstake name</span>
                        <input name="sweepstake_name" value="{{ old('sweepstake_name', $sweepstake->name) }}" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" @disabled($sweepstake->isLockedForChanges())>
                    </label>

                    <div class="grid grid-cols-[1fr_90px] gap-3">
                        <label class="block">
                            <span class="text-sm font-medium text-zinc-700">Entry fee</span>
                            <input type="number" name="entry_fee" value="{{ old('entry_fee', (float) $sweepstake->entry_fee) }}" min="0" step="0.01" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" @disabled($sweepstake->isLockedForChanges())>
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-zinc-700">Currency</span>
                            <input name="currency" value="{{ old('currency', $sweepstake->currency) }}" maxlength="3" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm uppercase" @disabled($sweepstake->isLockedForChanges())>
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-zinc-700">Status</span>
                        <select name="status" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm" @disabled($sweepstake->isLockedForChanges())>
                            <option value="{{ \App\Models\Sweepstake::STATUS_DRAFT }}" @selected(old('status', $sweepstake->status) === \App\Models\Sweepstake::STATUS_DRAFT)>Draft</option>
                            <option value="{{ \App\Models\Sweepstake::STATUS_OPEN }}" @selected(old('status', $sweepstake->status) === \App\Models\Sweepstake::STATUS_OPEN)>Open</option>
                        </select>
                    </label>
                </div>

                <button class="mt-5 rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800" @disabled($sweepstake->isLockedForChanges())>Save settings</button>

                <dl class="mt-5 border-t border-zinc-100 pt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Entry fee</dt>
                        <dd class="font-medium">{{ $sweepstake->currency }} {{ number_format((float) $sweepstake->entry_fee, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Collected pot</dt>
                        <dd class="font-medium">{{ $sweepstake->currency }} {{ number_format($sweepstake->collectedPot(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Entrants in draw</dt>
                        <dd class="font-medium">{{ $sweepstake->members->count() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Paid entrants</dt>
                        <dd class="font-medium">{{ $sweepstake->members->where('is_paid', true)->count() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Draw mode</dt>
                        <dd class="font-medium">Ranked pots</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Teams per entrant</dt>
                        <dd class="font-medium">{{ $sweepstake->teams_per_member ?? 'Not drawn' }}</dd>
                    </div>
                </dl>
            </form>

            <form method="POST" action="{{ route('sweepstakes.prizes.store', $sweepstake) }}" class="rounded-lg border border-zinc-200 bg-white p-5">
                @csrf
                <h2 class="font-semibold">Prize payout</h2>

                <div class="mt-4 grid grid-cols-[90px_1fr] gap-3">
                    <label>
                        <span class="text-sm font-medium text-zinc-700">Position</span>
                        <input type="number" name="position" min="1" value="{{ old('position', 1) }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-medium text-zinc-700">Label</span>
                        <input name="label" value="{{ old('label', 'Winner') }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                    </label>
                </div>

                <label class="mt-4 block">
                    <span class="text-sm font-medium text-zinc-700">Amount</span>
                    <input type="number" name="amount" min="0" step="0.01" value="{{ old('amount', 0) }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                </label>

                <button class="mt-5 rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800" @disabled($sweepstake->isLockedForChanges())>Save prize</button>

                @if ($sweepstake->prizes->isNotEmpty())
                    <ul class="mt-5 divide-y divide-zinc-100 text-sm">
                        @foreach ($sweepstake->prizes as $prize)
                            <li class="flex justify-between gap-3 py-2">
                                <span>{{ $prize->position }}. {{ $prize->label }}</span>
                                <span>{{ $sweepstake->currency }} {{ number_format((float) $prize->amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </form>
        </aside>
    </div>
@endsection
