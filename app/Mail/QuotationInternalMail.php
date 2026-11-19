<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationInternalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva cotización '.$this->quotation->code.' · '.($this->quotation->lead?->name ?? 'sin contacto'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotation-internal',
            with: ['quotation' => $this->quotation->loadMissing('items', 'lead')],
        );
    }
}
