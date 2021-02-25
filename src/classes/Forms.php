<?php


namespace Netlipress;

use Netlipress\Mail;


class Forms
{
    private $FormIndex;

    public function __construct()
    {
        if(isset($_POST['FormIndex'])) {
            $this->FormIndex = intval($_POST['FormIndex']);
        }
    }

    public function handle()
    {
        if (!isset($_SESSION['FormAction'][$this->FormIndex])) {
            $this->redirect(['success' => false, 'error' => 'Action not found']);
        }
        //This is so that the form tags know for which index to show errors
        if(isset($_POST['FormIndex'])) {
            $_SESSION['FormIndex'] = $this->FormIndex;
        }
        if ($_SESSION['FormAction'][$this->FormIndex] === 'contact') {
            $this->contactForm();
        }
    }

    private function redirect($status = [])
    {
        //Build redirect url back to previous page with status as parameters
        $redirect = explode('?', $_SERVER['HTTP_REFERER'], 2)[0];

        //Add query params
        if (!empty($status)) {
            $redirect .= '?' . http_build_query($status);
        }

        //Add form id hash if index was posted
        $redirect .= '#form-' . $this->FormIndex;

        //Perform redirect
        header("Location: $redirect");
        die();
    }

    private function contactForm()
    {

        //Init
        $mail = new Mail();
        $returnMessage = ['success' => false, 'form' => $this->FormIndex];
        $fields = $_POST;
        $subject = $_SESSION['FormSubject'][$this->FormIndex] ?? 'Contactform';

        //Validate based on schema
        $validationErrors = $this->validate();

        //Optionally Validate Recaptcha
        if(RECAPTCHA) {
            if(!$this->checkRecaptcha()){
                $_SESSION['FormOldValues'][$this->FormIndex] = $fields;
                $returnMessage['error'] = "Recaptcha invalid";
                $this->redirect($returnMessage);
            }
        }

        //Remove fields from table
        unset($fields['recaptcha_response']);
        unset($fields['FormIndex']);

        if (!empty($validationErrors)) {
            //Errors found to redirect back with errors and old data
            $_SESSION['FormValidationErrors'][$this->FormIndex] = $validationErrors;
            $_SESSION['FormOldValues'][$this->FormIndex] = $fields;
            $this->redirect($returnMessage);
        }

        //If no errors found, build table
        $body = "<h1>$subject</h1>";
        $body .= "<table>";
        foreach ($fields as $key => $value) {
            $body .= "<tr>";
            $body .= "<td style='padding-right:15px'><strong>" . ucfirst($key) . ": </strong></td>";
            $body .= "<td>" . $value . "</td>";
            $body .= "</tr>";
        }
        $body .= "</table>";

        //Attempt to send email
        $response = $mail->send($subject, $body, null, null, $fields);

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
        if (isset($_SESSION['FormSchema'][$this->FormIndex])) {
            foreach ($_SESSION['FormSchema'][$this->FormIndex] as $field => $ruleString) {
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

        $messages = isset($_SESSION['FormMessages'][$this->FormIndex]) ? $_SESSION['FormMessages'][$this->FormIndex] : [];

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

    private function checkRecaptcha() {
        // Build POST request:
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_secret = RECAPTCHA_SECRET;
        $recaptcha_response = $_POST['recaptcha_response'];

        // Make and decode POST request:
        $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
        $recaptcha = json_decode($recaptcha);

        return $recaptcha->score >= 0.5;
    }
}
