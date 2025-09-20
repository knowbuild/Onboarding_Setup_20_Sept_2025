<?php

namespace App\Mail\Sales;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $DeliveryOrder;
    public $DoProduct;
    public string $orderNO;

    /**
     * Create a new message instance.
     */
    public function __construct($DeliveryOrder, $DoProduct, string $orderNO)
    {
        $this->DeliveryOrder = $DeliveryOrder;
        $this->DoProduct = $DoProduct;
        $this->orderNO = $orderNO;
    }

    /**
     * Define the envelope for the email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Delivery Order {$this->orderNO} has been successfully Created!"
        );
    }

    /**
     * Define the content for the email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Sales.delivery-order',
            with: [
                'DeliveryOrder' => $this->DeliveryOrder,
                'DoProduct' => $this->DoProduct,
                'orderNO' => $this->orderNO
            ]
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
