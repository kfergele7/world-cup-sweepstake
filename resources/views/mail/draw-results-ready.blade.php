@php
    $teamPageUrl = route('entrants.show', $member->join_token);
@endphp

<p>Hi {{ $member->name }},</p>

<p>
    The draw for {{ $draw->sweepstake->name }} has been run in SweepKit.
    Your teams are listed below.
</p>

@if ($draw->reason)
    <p><strong>The organiser re-ran the draw because:</strong> {{ $draw->reason }}</p>
@endif

<p><strong>Your teams are:</strong></p>

<ul>
    @foreach ($assignments as $assignment)
        <li>
            @if ($assignment->team->displayFlag())
                {{ $assignment->team->displayFlag() }}
            @endif
            {{ $assignment->team->name }}
            @if ($assignment->pot_number)
                (Pot {{ $assignment->pot_number }})
            @endif
        </li>
    @endforeach
</ul>

<p>
    View your team page:
    <a href="{{ $teamPageUrl }}">{{ $teamPageUrl }}</a>
</p>

<p>If something does not look right, please contact the organiser.</p>

<p>Good luck,<br>SweepKit</p>
