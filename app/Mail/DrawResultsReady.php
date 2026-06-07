<?php

namespace App\Mail;

use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use App\Models\TeamAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DrawResultsReady extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, TeamAssignment>  $assignments
     */
    public function __construct(
        public SweepstakeDraw $draw,
        public SweepstakeMember $member,
        public Collection $assignments,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your World Cup sweepstake teams are ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.draw-results-ready',
        );
    }
}
