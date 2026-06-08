<?php

namespace App\Mail;

use App\Models\SweepstakeDraw;
use App\Models\SweepstakeMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DrawCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SweepstakeDraw $draw,
        public SweepstakeMember $member,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SweepKit draw was cancelled',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.draw-cancelled',
        );
    }
}
