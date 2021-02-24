<?php


namespace Netlipress;

use Netlipress\Mail;


class Forms
{
    public function __construct()
    {
    }

    public function handle()
    {
        if ($_POST['action'] == 'contact') {
            $this->contactForm();
        }
    }

    private function contactForm()
    {
        $mail = new Mail();
        $returnMessage = ['success' => false];

        $fields = $_POST;
        $subject = $_POST['subject'] ?? 'Contactform';

        unset($fields['action']);
        unset($fields['subject']);

        $body = "<h3>$subject</h3>";
        $body .= "<table>";
        foreach($fields as $key => $value) {
            $body .= "<tr>";
            $body .= "<td style='padding-right:15px'><strong>" . ucfirst($key) . ": </strong></td>";
            $body .= "<td>" . $value . "</td>";
            $body .= "</tr>";
        }
        $body .= "</table>";

        $response = $mail->send($subject, $body);
        if ($response['success']) {
            $returnMessage['success'] = true;
        } else {
            $returnMessage['error'] = $response['message'];
        }
        echo json_encode($returnMessage, JSON_PRETTY_PRINT);
        die();
    }
}
