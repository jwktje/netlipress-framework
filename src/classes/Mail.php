<?php

namespace Netlipress;

use PHPMailer\PHPMailer\PHPMailer;

class Mail
{
    private $toAddress;
    private $toName;
    private $fromAddress;
    private $fromName;

    public function __construct()
    {
        $this->toName = MAIL_TO_NAME;
        $this->toAddress = MAIL_TO_ADDRESS;
        $this->fromName = MAIL_FROM_NAME;
        $this->fromAddress = MAIL_FROM_ADDRESS;
    }

    public function test($toAddress)
    {
        $this->send("Test", "Test", $toAddress, "Test");
    }

    public function send($subject, $body, $toAddress = false, $toName = false)
    {
        //Fallback to default recipient for emails
        $this->toAddress = $toAddress ? $toAddress : $this->toAddress;
        $this->toName = $toName ? $toName : $this->toName;

        //Put body inside mail template
        $body = $this->parseEmailTemplate($body);

        //Build mail object
        $mail = new PHPMailer();
        $mail->setFrom($this->fromAddress, $this->fromName);
        $mail->addAddress($this->toAddress, $this->toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        //Send
        if (!$mail->send()) {
            return ['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo];
        } else {
            if (is_dir(MAIL_DIR)) {
                $this->saveMailToFS($subject, $body, $this->toAddress, $this->toName);
            }
            return ['success' => true];
        }
    }

    private function saveMailToFS($subject, $body, $toAddress, $toName)
    {
        $date = date("d-m-Y H:i:s");
        $filename = $toName . '_' . time();
        $mailData = [
            'email' => $toAddress,
            'name' => $toName,
            'subject' => $subject,
            'body' => $body,
            'date' => $date
        ];
        $json = json_encode($mailData, JSON_PRETTY_PRINT);
        $newFilePath = APP_ROOT . CONTENT_DIR . '/mail/' . $filename . '.json';
        file_put_contents($newFilePath, $json);
    }

    private function parseEmailTemplate($content) {
        $template = file_get_contents(__DIR__ . '/../includes/templates/email.html');
        return str_replace('{{content}}' , $content, $template);
    }
}
