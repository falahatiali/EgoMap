<?php

namespace App\Mail;

use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('auth.verification_email_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.verification-code',
            with: [
                'code' => $this->code,
                'expiresMinutes' => EmailVerificationService::EXPIRY_MINUTES,
            ],
        );
    }
}
