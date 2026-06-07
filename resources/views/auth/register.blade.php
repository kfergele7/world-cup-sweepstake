@extends('layouts.app')

@section('content')
    <section class="max-w-md">
        <h1 class="text-2xl font-semibold">Create admin account</h1>

        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4 rounded-lg border border-zinc-200 bg-white p-5">
            @csrf

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Name</span>
                <input name="name" value="{{ old('name') }}" required autofocus class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Password</span>
                <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Confirm password</span>
                <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <button class="rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Create account</button>
        </form>
    </section>
@endsection
