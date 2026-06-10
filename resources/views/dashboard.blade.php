@extends('layouts.app')

@section('content')
    <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
        <section>
            <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">SweepKit admin</p>
            <h1 class="mt-2 text-3xl font-black text-brand-navy">Dashboard</h1>
            <p class="mt-4 rounded-lg border border-brand-border bg-white/75 px-4 py-3 text-sm leading-6 text-brand-muted">
                SweepKit is currently in private beta. If something does not look right, please <a href="{{ route('feedback') }}" class="font-semibold text-brand-blue underline">send feedback</a> so it can be improved before wider use.
            </p>
            <div id="app" data-page="dashboard" data-props='@json(['totals' => $totals])' class="mt-5"></div>

            <div class="sk-card mt-8 overflow-hidden">
                <div class="sk-card-header">
                    <h2 class="font-semibold text-brand-navy">Your sweepstakes</h2>
                    <p class="mt-1 text-sm text-brand-muted">Open a sweepstake to manage entrants, teams and draw results.</p>
                </div>

                @forelse ($sweepstakes as $sweepstake)
                    <a href="{{ route('sweepstakes.show', $sweepstake) }}" class="grid gap-2 border-b border-brand-border/70 px-5 py-4 last:border-b-0 hover:bg-brand-blue/5 sm:grid-cols-[1fr_auto] sm:items-center">
                        <span>
                            <span class="block font-semibold text-brand-navy">{{ $sweepstake->name }}</span>
                            <span class="mt-1 block text-sm text-brand-muted">
                                {{ $sweepstake->members_count }} entrants, {{ $sweepstake->paid_members_count }} paid
                            </span>
                        </span>
                        <span class="sk-badge sk-badge-navy">{{ ucfirst($sweepstake->status) }}</span>
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-brand-muted">No sweepstakes yet.</p>
                @endforelse
            </div>
        </section>

        <aside>
            <form method="POST" action="{{ route('sweepstakes.store') }}" class="sk-card p-5">
                @csrf
                <h2 class="font-semibold text-brand-navy">Create sweepstake</h2>
                <p class="mt-1 text-sm text-brand-muted">Start with the basics, then invite entrants.</p>

                <label class="mt-4 block">
                    <span class="text-sm font-medium text-brand-navy">Name</span>
                    <input name="name" value="{{ old('name') }}" required class="sk-input">
                </label>

                <div class="mt-4 grid grid-cols-[1fr_90px] gap-3">
                    <label class="block">
                        <span class="text-sm font-medium text-brand-navy">Entry fee</span>
                        <input type="number" name="entry_fee" value="{{ old('entry_fee', 0) }}" min="0" step="0.01" class="sk-input">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-brand-navy">Currency</span>
                        <input name="currency" value="{{ old('currency', 'GBP') }}" maxlength="3" class="sk-input uppercase">
                    </label>
                </div>

                <fieldset class="mt-4">
                    <legend class="text-sm font-medium text-brand-navy">Draw rule</legend>
                    <div class="mt-2 space-y-2">
                        <label class="flex gap-2 rounded-lg border border-brand-border bg-white px-3 py-2 text-sm text-brand-muted">
                            <input type="radio" name="pot_mode" value="{{ \App\Models\Sweepstake::POT_MODE_AUTO }}" class="mt-1 border-brand-border text-brand-green" @checked(old('pot_mode', \App\Models\Sweepstake::POT_MODE_AUTO) === \App\Models\Sweepstake::POT_MODE_AUTO)>
                            <span>
                                <span class="block font-semibold text-brand-navy">Auto pots</span>
                                <span>Use stored rankings.</span>
                            </span>
                        </label>
                        <label class="flex gap-2 rounded-lg border border-brand-border bg-white px-3 py-2 text-sm text-brand-muted">
                            <input type="radio" name="pot_mode" value="{{ \App\Models\Sweepstake::POT_MODE_CUSTOM }}" class="mt-1 border-brand-border text-brand-green" @checked(old('pot_mode') === \App\Models\Sweepstake::POT_MODE_CUSTOM)>
                            <span>
                                <span class="block font-semibold text-brand-navy">Custom pots</span>
                                <span>Assign teams to pots manually.</span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <button class="sk-btn-green mt-5">Create</button>
            </form>
        </aside>
    </div>
@endsection
