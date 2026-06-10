@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl">
        <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">Private beta feedback</p>
        <h1 class="mt-2 text-3xl font-black text-brand-navy">Send feedback</h1>

        <div class="sk-card mt-6 space-y-5 p-5 text-sm leading-7 text-brand-muted">
            <p>
                Found a bug or have feedback? Let us know so we can improve SweepKit before wider use.
            </p>

            <p>
                Please include what you were trying to do, what happened and the name of your sweepstake if it is relevant. Do not send passwords, payment details or anything you would not want shared with the support team.
            </p>

            <p>
                Email
                <a href="mailto:{{ config('support.email') }}" class="font-semibold text-brand-blue underline">{{ config('support.email') }}</a>
                and we will review it as part of the private beta.
            </p>
        </div>
    </section>
@endsection
