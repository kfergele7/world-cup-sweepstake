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
                    <button class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800" @disabled($sweepstake->isLockedForChanges())>Run draw</button>
                </form>
            </div>

            @if ($prizeWarning)
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ $prizeWarning }}
                </div>
            @endif

            <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Members</h2>
                </div>

                <div class="divide-y divide-zinc-100">
                    @forelse ($sweepstake->members as $member)
                        <div class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <p class="font-medium">{{ $member->name }} @if ($member->is_admin)<span class="text-sm text-zinc-500">(admin)</span>@endif</p>
                                <p class="text-sm text-zinc-600">{{ $member->email ?: 'No email' }}</p>
                            </div>
                            <form method="POST" action="{{ route('sweepstakes.members.update', [$sweepstake, $member]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_paid" value="{{ $member->is_paid ? 0 : 1 }}">
                                <button class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-50" @disabled($sweepstake->isLockedForChanges())>
                                    {{ $member->is_paid ? 'Mark unpaid' : 'Mark paid' }}
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="px-5 py-4 text-sm text-zinc-600">No members yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Draw results</h2>
                </div>

                @forelse ($sweepstake->assignments->sortBy(fn ($assignment) => sprintf('%03d-%08d', $assignment->pot_number, $assignment->id)) as $assignment)
                    <div class="grid gap-2 border-b border-zinc-100 px-5 py-3 text-sm last:border-b-0 sm:grid-cols-[80px_1fr_1fr]">
                        <span class="font-medium">Pot {{ $assignment->pot_number }}</span>
                        <span>{{ $assignment->member->name }}</span>
                        <span>{{ $assignment->team->name }}</span>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-zinc-600">No draw has been run yet.</p>
                @endforelse
            </div>

            <div class="mt-8 rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Selected teams</h2>
                </div>

                <div class="grid divide-y divide-zinc-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                    <div class="divide-y divide-zinc-100">
                        @forelse ($selectedTeams as $sweepstakeTeam)
                            <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span>{{ $sweepstakeTeam->team->name }} <span class="text-zinc-500">#{{ $sweepstakeTeam->team->fifa_ranking ?? 'n/a' }}</span></span>
                                <form method="POST" action="{{ route('sweepstakes.teams.update', [$sweepstake, $sweepstakeTeam]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_included" value="0">
                                    <button class="font-medium text-red-700 hover:text-red-800" @disabled($sweepstake->isLockedForChanges())>Remove</button>
                                </form>
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-zinc-600">No teams selected.</p>
                        @endforelse
                    </div>

                    <div class="divide-y divide-zinc-100">
                        @forelse ($removedTeams as $sweepstakeTeam)
                            <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span>{{ $sweepstakeTeam->team->name }}</span>
                                <form method="POST" action="{{ route('sweepstakes.teams.update', [$sweepstake, $sweepstakeTeam]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_included" value="1">
                                    <button class="font-medium text-zinc-800 hover:text-zinc-950" @disabled($sweepstake->isLockedForChanges())>Restore</button>
                                </form>
                            </div>
                        @empty
                            <p class="px-5 py-4 text-sm text-zinc-600">No teams removed.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-lg border border-zinc-200 bg-white p-5">
                <h2 class="font-semibold">Sweepstake settings</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Entry fee</dt>
                        <dd class="font-medium">{{ $sweepstake->currency }} {{ number_format((float) $sweepstake->entry_fee, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Paid pot</dt>
                        <dd class="font-medium">{{ $sweepstake->currency }} {{ number_format($sweepstake->collectedPot(), 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Draw mode</dt>
                        <dd class="font-medium">Ranked pots</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-zinc-600">Teams per member</dt>
                        <dd class="font-medium">{{ $sweepstake->teams_per_member ?? 'Not drawn' }}</dd>
                    </div>
                </dl>
            </div>

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
