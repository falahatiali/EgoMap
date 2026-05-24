<?php

namespace App\Mail;

use App\Models\QuizSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizFullReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuizSession $session) {}

    public function envelope(): Envelope
    {
        $title = $this->session->result?->free_report['title'] ?? __('quiz.your_result');

        return new Envelope(
            subject: __('quiz.email_subject', ['title' => $title]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quiz.full-report',
            with: [
                'session' => $this->session,
                'report' => $this->session->result?->free_report ?? [],
                'resultUrl' => route('quiz.result', ['uuid' => $this->session->uuid]),
            ],
        );
    }
}
