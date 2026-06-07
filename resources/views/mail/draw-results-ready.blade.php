<p>Hi {{ $member->name }},</p>

<p>
    The draw for {{ $draw->sweepstake->name }} has been run on {{ $draw->ran_at->format('j M Y \a\t H:i') }}.
    Your teams are listed below.
</p>

@if ($draw->reason)
    <p><strong>Reason for re-running:</strong> {{ $draw->reason }}</p>
@endif

<ul>
    @foreach ($assignments as $assignment)
        <li>
            {{ $assignment->team->name }}
            @if ($assignment->pot_number)
                (Pot {{ $assignment->pot_number }})
            @endif
        </li>
    @endforeach
</ul>

<p>
    You can also view your team page here:
    <a href="{{ route('entrants.show', $member->join_token) }}">{{ route('entrants.show', $member->join_token) }}</a>
</p>

<p>Good luck.</p>
