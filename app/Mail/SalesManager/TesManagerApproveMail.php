<?php
namespace App\Mail\SalesManager;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TesManagerApproveMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tesManager;
    public $tesData;
    public string $accountManagerName;
    public string $financialYearName;

    public function __construct($tesManager, $tesData, string $accountManagerName, string $financialYearName)
    {
        $this->tesManager = $tesManager;
        $this->tesData = $tesData;
        $this->accountManagerName = $accountManagerName;
        $this->financialYearName = $financialYearName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TES Target Approval - ' . $this->accountManagerName
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'SalesManager.tes-manager-approve',
            with: [
                'tesManager' => $this->tesManager,
                'tesData' => $this->tesData,
                'accountManagerName' => $this->accountManagerName,
                'financialYearName' => $this->financialYearName,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
