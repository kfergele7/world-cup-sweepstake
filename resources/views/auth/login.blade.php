@extends('layouts.app')

@section('content')
    <section class="max-w-md">
        <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">SweepKit</p>
        <h1 class="mt-2 text-2xl font-black text-brand-navy">Sign in</h1>

        <form method="POST" action="{{ route('login') }}" class="sk-card mt-6 space-y-4 p-5">
            @csrf

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="sk-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Password</span>
                <input type="password" name="password" required class="sk-input">
            </label>

            <label class="flex items-center gap-2 text-sm text-brand-muted">
                <input type="checkbox" name="remember" value="1" class="rounded border-brand-border text-brand-green">
                Remember me
            </label>

            <button class="sk-btn-green">Sign in</button>
        </form>

        <p class="mt-4 text-center text-sm text-brand-muted">
            Don&rsquo;t have an organiser account?
            <a href="{{ route('register') }}" class="font-semibold text-brand-blue transition hover:text-brand-navy">
                Create one here.
            </a>
        </p>
    </section>
@endsection
