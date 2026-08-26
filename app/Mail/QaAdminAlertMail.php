<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QaAdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $subjectTitle,
        public string $badge,
        public string $headline,
        public string $messageBody,
        public array $details = [],
        public ?string $actionUrl = null,
        public string $actionText = 'View in QA Portal',
        public string $badgeType = 'info' // info, success, warning, danger
    ) {}

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->subjectTitle)
                    ->view('emails.qa-admin-alert')
                    ->text('emails.qa-admin-alert-plain');
    }
}
