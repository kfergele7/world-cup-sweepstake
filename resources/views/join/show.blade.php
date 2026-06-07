@extends('layouts.app')

@section('content')
    <section class="max-w-xl">
        <p class="text-sm font-semibold uppercase tracking-normal text-red-700">Join code {{ $sweepstake->join_code }}</p>
        <h1 class="mt-2 text-3xl font-semibold">{{ $sweepstake->name }}</h1>
        <p class="mt-3 text-zinc-700">Enter your details and the admin will mark you as paid when your entry is confirmed.</p>

        <form method="POST" action="{{ route('join.store', $sweepstake->join_code) }}" class="mt-6 space-y-4 rounded-lg border border-zinc-200 bg-white p-5">
            @csrf

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Name</span>
                <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <button class="rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Join sweepstake</button>
        </form>
    </section>
@endsection
