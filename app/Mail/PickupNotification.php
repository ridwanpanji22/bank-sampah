<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Schedule;

class PickupNotification extends Mailable
{
    use Queueable, SerializesModels;
    
    public $schedule;
    /**
     * Create a new message instance.
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Get the message envelope.
     */

     public function build()
     {
        return $this->view('emails.pickup-notification')
                    ->with([
                        'schedule' => $this->schedule,
                    ]);
     }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pickup Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.pickup-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
