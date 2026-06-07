@extends('layouts.app')

@section('content')
    <section class="max-w-md">
        <h1 class="text-2xl font-semibold">Sign in</h1>

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4 rounded-lg border border-zinc-200 bg-white p-5">
            @csrf

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <label class="block">
                <span class="text-sm font-medium text-zinc-700">Password</span>
                <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
            </label>

            <label class="flex items-center gap-2 text-sm text-zinc-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300">
                Remember me
            </label>

            <button class="rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Sign in</button>
        </form>
    </section>
@endsection
