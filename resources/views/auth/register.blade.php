@extends('layouts.app')

@section('content')
    <section class="max-w-md">
        <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">SweepKit</p>
        <h1 class="mt-2 text-2xl font-black text-brand-navy">Create admin account</h1>

        <form method="POST" action="{{ route('register') }}" class="sk-card mt-6 space-y-4 p-5">
            @csrf

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Name</span>
                <input name="name" value="{{ old('name') }}" required autofocus class="sk-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required class="sk-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Password</span>
                <input type="password" name="password" required class="sk-input">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-brand-navy">Confirm password</span>
                <input type="password" name="password_confirmation" required class="sk-input">
            </label>

            <button class="sk-btn-green">Create account</button>
        </form>

        <p class="mt-4 text-center text-sm text-brand-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-brand-blue transition hover:text-brand-navy">
                Sign in here.
            </a>
        </p>
    </section>
@endsection
