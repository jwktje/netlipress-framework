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

    public function send($subject, $body, $toAddress = false, $toName = false, $rawFormFields = false)
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
                $this->saveMailToFS($subject, $body, $this->toAddress, $this->toName, $rawFormFields);
            }
            return ['success' => true];
        }
    }

    private function saveMailToFS($subject, $body, $toAddress, $toName, $rawFormFields = false)
    {
        $date = date("d-m-Y H:i:s");
        $filename = $toName . ' - ' . $date;

        $mailData = [
            'email' => $toAddress,
            'name' => $toName,
            'subject' => $subject,
            'date' => $date,
            'fields' => $rawFormFields,
            'body' => $body
        ];

        //When using Apache Dir Index to view submissions, it's convenient to have one dir for each submission type
        if (MAIL_DIR_PER_SUBJECT) {
            $subjectDir = MAIL_DIR . '/' . $subject;
            $htmlDir = $subjectDir . '/html/';
            $jsonDir = $subjectDir . '/json/';
            if (!is_dir($subjectDir)) {
                mkdir($subjectDir);
            }
            if (!is_dir($htmlDir)) {
                mkdir($htmlDir);
            }
            if (!is_dir($jsonDir)) {
                mkdir($jsonDir);
            }

            //We assume you'd want both HTML and JSON when using Indexes
            $htmlPath = $htmlDir . $filename . '.html';
            $jsonPath = $jsonDir . $filename . '.json';

            //Save HTML
            file_put_contents($htmlPath, $mailData['body']);

            //When saving both email body is not needed in the json
            unset($mailData['body']);

            //Save JSON
            $json = json_encode($mailData, JSON_PRETTY_PRINT);
            file_put_contents($jsonPath, $json);
        } else {
            //Otherwise just save JSON to the configured mail dir
            $newFilePath = MAIL_DIR . '/' . $filename . '.json';
            $json = json_encode($mailData, JSON_PRETTY_PRINT);
            file_put_contents($newFilePath, $json);
        }

    }

    private function parseEmailTemplate($content)
    {
        $template = file_get_contents(__DIR__ . '/../includes/templates/fancy-email.html');
        return str_replace('{{content}}', $content, $template);
    }
}
