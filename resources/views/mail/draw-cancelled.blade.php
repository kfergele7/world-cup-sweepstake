<p>Hi {{ $member->name }},</p>

<p>
    The current draw for {{ $draw->sweepstake->name }} has been cancelled by the sweepstake admin.
    Setup is open again, so entrants, teams or prizes may change before a new draw is run.
</p>

@if ($draw->cancelled_reason)
    <p><strong>Reason for cancelling:</strong> {{ $draw->cancelled_reason }}</p>
@endif

<p>
    You can keep using your private team page here:
    <a href="{{ route('entrants.show', $member->join_token) }}">{{ route('entrants.show', $member->join_token) }}</a>
</p>

<p>We will let you know when the next draw is ready.</p>
