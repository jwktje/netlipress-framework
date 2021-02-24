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
        if (!isset($_POST['action'])) {
            $this->redirect(['success' => false, 'error' => 'Action not found']);
        }
        if ($_POST['action'] == 'contact') {
            $this->contactForm();
        }
    }

    private function redirect($status = [])
    {
        //Build redirect url back to previous page with status as parameters
        $redirectBaseUrl = explode('?', $_SERVER['HTTP_REFERER'], 2)[0];
        $redirect = $redirectBaseUrl . '?' . http_build_query($status);

        //Perform redirect
        header("Location: $redirect");
        die();
    }

    private function contactForm()
    {
        //Init
        $mail = new Mail();
        $returnMessage = ['success' => false];
        $fields = $_POST;
        $subject = $_POST['subject'] ?? 'Contactform';

        //Remove fields that shouldn't be shown in the email table
        unset($fields['action']);
        unset($fields['subject']);

        //Validate based on schema
        $validationErrors = $this->validate();
        if (!empty($validationErrors)) {
            //Errors found to redirect back with errors and old data
            $_SESSION['FormValidationErrors'] = $validationErrors;
            $_SESSION['FormOldValues'] = $fields;
            $this->redirect();
        }

        //If no errors found, build table
        $body = "<h3>$subject</h3>";
        $body .= "<table>";
        foreach ($fields as $key => $value) {
            $body .= "<tr>";
            $body .= "<td style='padding-right:15px'><strong>" . ucfirst($key) . ": </strong></td>";
            $body .= "<td>" . $value . "</td>";
            $body .= "</tr>";
        }
        $body .= "</table>";

        //Attempt to send email
        $response = $mail->send($subject, $body);

        //Check if that worked
        if ($response['success']) {
            $returnMessage['success'] = true;
        } else {
            $returnMessage['error'] = $response['message'];
        }

        $this->redirect($returnMessage);

    }

    private function validate()
    {
        //Init array
        $validationErrors = [];
        //Go through schema
        if (isset($_POST['schema'])) {
            $schema = json_decode($_POST['schema']);
            foreach ($schema as $field => $ruleString) {
                if (isset($_POST[$field])) {
                    $ruleErrors = $this->checkSchemaRule($_POST[$field], $ruleString);
                    if (!empty($ruleErrors)) {
                        $validationErrors[] = ['field' => $field, 'error' => $ruleErrors];
                    }
                }
            }
        }
        return $validationErrors;
    }

    private function checkSchemaRule($value, $ruleString)
    {
        $defaultError = 'Error';

        $messages = isset($_POST['messages']) ? (array) json_decode($_POST['messages']) : [];

        $ruleArr = explode('|', $ruleString);

        foreach ($ruleArr as $rule) {
            if ($rule == 'required') {
                if (empty($value)) {
                    return $messages[$rule] ?? $defaultError;
                }
            }
            if ($rule == 'email') {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $messages[$rule] ?? $defaultError;
                }
            }
        }
    }
}
