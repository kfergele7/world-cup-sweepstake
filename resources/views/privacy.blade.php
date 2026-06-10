@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl">
        <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">Privacy</p>
        <h1 class="mt-2 text-3xl font-black text-brand-navy">Privacy Policy</h1>
        <p class="mt-3 text-sm text-brand-muted">Last updated: 10 June 2026</p>

        <div class="sk-card mt-6 space-y-6 p-5 text-sm leading-7 text-brand-muted">
            <section>
                <h2 class="text-base font-semibold text-brand-navy">About SweepKit</h2>
                <p class="mt-2">
                    SweepKit is a tool for private groups to organise and manage football sweepstakes. Admin users can create accounts, set up sweepstakes and invite entrants. Entrants may be added manually by an organiser or may join using a private join link or PIN where available.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Information We May Store</h2>
                <p class="mt-2">Depending on how a sweepstake is set up, SweepKit may store:</p>
                <ul class="mt-3 list-disc space-y-1 pl-5">
                    <li>Admin name and email address.</li>
                    <li>Entrant name and email address, if provided.</li>
                    <li>Sweepstake name, entry fee, currency and other setup details.</li>
                    <li>Prize labels, positions and amounts.</li>
                    <li>Paid or unpaid status for entrants.</li>
                    <li>Selected teams, removed teams and custom pot settings.</li>
                    <li>Draw results, draw history and re-run or cancellation reasons.</li>
                    <li>Basic technical and log data used to keep the service secure and working.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">How Information Is Used</h2>
                <p class="mt-2">
                    SweepKit uses this information to create and manage sweepstakes, show private entrant result pages, record draw history and help organisers keep their sweepstake tidy. If an entrant email is provided, it may be used for draw notifications, cancellation notices, re-run notices and private team or result links.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Payments And Paid Status</h2>
                <p class="mt-2">
                    SweepKit does not currently process payments, collect entry fees or pay out prizes. Paid or unpaid status is manual tracking only, controlled by the organiser.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Private Entrant Pages</h2>
                <p class="mt-2">
                    Entrant result pages are scoped by private tokens. Anyone with a valid private link may be able to view that entrant page, so organisers and entrants should keep those links safe. These pages are intended for the relevant entrant and organiser, not for public sharing.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Updating Or Removing Details</h2>
                <p class="mt-2">
                    Entrants who want their details updated or removed should contact the organiser of their sweepstake, or email <a href="mailto:{{ config('support.email') }}" class="font-semibold text-brand-blue underline">{{ config('support.email') }}</a> if they need help reaching support. Organisers are responsible for keeping their sweepstake records appropriate and up to date.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Private Beta</h2>
                <p class="mt-2">
                    SweepKit is currently in private beta and may change as the product develops. The organiser is responsible for running their sweepstake appropriately and following applicable rules, including workplace rules and any relevant local laws. If something does not look right, you can <a href="{{ route('feedback') }}" class="font-semibold text-brand-blue underline">send feedback</a> or read the <a href="{{ route('terms') }}" class="font-semibold text-brand-blue underline">Terms</a>.
                </p>
            </section>
        </div>
    </section>
@endsection
