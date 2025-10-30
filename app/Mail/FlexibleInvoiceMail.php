<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FlexibleInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The invoice data payload.
     *
     * @var array
     */
    public array $invoiceData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $invoiceData)
    {
        $this->invoiceData = $invoiceData;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $appName = config('app.name');
        $date = $this->invoiceData['invoice_date'] ?? now()->format('Y-m-d');
        $subject = "Monthly Tax Invoice – {$date} | {$appName}";

        return $this->subject($subject)
            ->view('emails.flexible-invoice-mail')
            ->with($this->invoiceData);
    }
}


