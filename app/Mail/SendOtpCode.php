<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpCode extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;

    public function __construct(string $otpCode)
    {
        $this->otpCode = $otpCode;
    }

    public function getEnvelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Security Verification Code',
        );
    }

    public function getContent(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
    }
}