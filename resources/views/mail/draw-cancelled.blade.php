@php
    $teamPageUrl = route('entrants.show', $member->join_token);
@endphp

<p>Hi {{ $member->name }},</p>

<p>
    The organiser has cancelled the current SweepKit draw for {{ $draw->sweepstake->name }}.
    Setup is open again, so entrants, teams or prizes may change before a new draw is run.
</p>

@if ($draw->cancelled_reason)
    <p><strong>The organiser gave this reason:</strong> {{ $draw->cancelled_reason }}</p>
@endif

<p>
    You can keep using your private team page here:
    <a href="{{ $teamPageUrl }}">{{ $teamPageUrl }}</a>
</p>

<p>We will let you know when the next draw is ready.</p>

<p>SweepKit</p>
