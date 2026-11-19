<?php

namespace App\Mail;

use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quotation $quotation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu cotización HIVEDFOOD · '.$this->quotation->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotation-customer',
            with: ['quotation' => $this->quotation->loadMissing('items', 'lead')],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $service = app(QuotationService::class);

        return [
            Attachment::fromData(
                fn () => $service->pdf($this->quotation)->output(),
                $service->pdfFilename($this->quotation),
            )->withMime('application/pdf'),
        ];
    }
}
