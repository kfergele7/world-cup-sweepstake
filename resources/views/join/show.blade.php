@extends('layouts.app')

@section('content')
    <section class="max-w-xl">
        <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">Join code {{ $sweepstake->join_code }}</p>
        <h1 class="mt-2 text-3xl font-black text-brand-navy">{{ $sweepstake->name }}</h1>
        <p class="mt-3 text-brand-muted">Enter your details and the admin will mark you as paid when your entry is confirmed.</p>

        <form method="POST" action="{{ route('join.store', $sweepstake->join_code) }}" class="sk-card mt-6 space-y-4 p-5">
            @csrf

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Name</span>
                <input name="name" value="{{ old('name') }}" required class="sk-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" class="sk-input">
            </label>

            <button class="sk-btn-green">Join sweepstake</button>
        </form>
    </section>
@endsection
