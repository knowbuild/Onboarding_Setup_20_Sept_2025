<?php
namespace App\Services;

use SendGrid;

class SendGridService
{
    protected $sendgrid;

    public function __construct()
    {
        $this->sendgrid = new SendGrid(env('SENDGRID_API_KEY'));
    }

    public function sendMail($to, $subject, $view, $data)
    {
        $from = new SendGrid\Mail\From(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        $to = new SendGrid\Mail\To($to);
        $htmlContent = view($view, compact('data'))->render();
        $email = new SendGrid\Mail\Mail($from, $to);
        $email->setSubject($subject);
        $email->addContent("text/html", $htmlContent);

        try {
            $response = $this->sendgrid->send($email);
            return $response->statusCode();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
