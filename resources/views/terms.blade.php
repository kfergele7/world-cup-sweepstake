@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl">
        <p class="text-sm font-bold uppercase tracking-normal text-brand-blue">Terms</p>
        <h1 class="mt-2 text-3xl font-black text-brand-navy">Terms</h1>
        <p class="mt-3 text-sm text-brand-muted">Last updated: 10 June 2026</p>

        <div class="sk-card mt-6 space-y-6 p-5 text-sm leading-7 text-brand-muted">
            <section>
                <h2 class="text-base font-semibold text-brand-navy">Using SweepKit</h2>
                <p class="mt-2">
                    SweepKit is provided as a simple tool for private groups that want to organise football sweepstakes. Admin users are responsible for the information they add, the entrants they invite and the way they use the draw results.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Organiser Responsibility</h2>
                <p class="mt-2">
                    SweepKit helps private groups organise and manage their own sweepstakes. The organiser is responsible for making sure their sweepstake follows local rules, workplace rules and any relevant gambling or lottery laws. SweepKit does not collect entry fees or run the sweepstake on your behalf.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Payments And Prizes</h2>
                <p class="mt-2">
                    SweepKit can record entry fees, paid status and prize details for tracking, but it does not process payments, hold funds or pay out prizes. Any money collection or prize handling is arranged outside SweepKit by the organiser.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Private Links</h2>
                <p class="mt-2">
                    Entrant result pages use private token links. Organisers and entrants should avoid sharing private links with people who should not see those results.
                </p>
            </section>

            <section>
                <h2 class="text-base font-semibold text-brand-navy">Product Changes</h2>
                <p class="mt-2">
                    SweepKit is currently in private beta, so features, wording and workflows may change. For more detail on how information is handled, read the <a href="{{ route('privacy') }}" class="font-semibold text-brand-blue underline">Privacy Policy</a>.
                </p>
            </section>
        </div>
    </section>
@endsection
