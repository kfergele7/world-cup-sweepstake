@extends('layouts.app')

@section('content')
    <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
        <section>
            <h1 class="text-2xl font-semibold">Dashboard</h1>
            <div id="app" data-page="dashboard" data-props='@json(['totals' => $totals])' class="mt-5"></div>

            <div class="mt-8 overflow-hidden rounded-lg border border-zinc-200 bg-white">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="font-semibold">Your sweepstakes</h2>
                </div>

                @forelse ($sweepstakes as $sweepstake)
                    <a href="{{ route('sweepstakes.show', $sweepstake) }}" class="grid gap-2 border-b border-zinc-100 px-5 py-4 last:border-b-0 hover:bg-zinc-50 sm:grid-cols-[1fr_auto] sm:items-center">
                        <span>
                            <span class="block font-medium">{{ $sweepstake->name }}</span>
                            <span class="mt-1 block text-sm text-zinc-600">
                                {{ $sweepstake->members_count }} entrants, {{ $sweepstake->paid_members_count }} paid
                            </span>
                        </span>
                        <span class="text-sm font-medium text-zinc-700">{{ ucfirst($sweepstake->status) }}</span>
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-zinc-600">No sweepstakes yet.</p>
                @endforelse
            </div>
        </section>

        <aside>
            <form method="POST" action="{{ route('sweepstakes.store') }}" class="rounded-lg border border-zinc-200 bg-white p-5">
                @csrf
                <h2 class="font-semibold">Create sweepstake</h2>

                <label class="mt-4 block">
                    <span class="text-sm font-medium text-zinc-700">Name</span>
                    <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                </label>

                <div class="mt-4 grid grid-cols-[1fr_90px] gap-3">
                    <label class="block">
                        <span class="text-sm font-medium text-zinc-700">Entry fee</span>
                        <input type="number" name="entry_fee" value="{{ old('entry_fee', 0) }}" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-zinc-700">Currency</span>
                        <input name="currency" value="{{ old('currency', 'GBP') }}" maxlength="3" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm uppercase">
                    </label>
                </div>

                <button class="mt-5 rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Create</button>
            </form>
        </aside>
    </div>
@endsection
