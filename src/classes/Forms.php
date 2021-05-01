<?php


namespace Netlipress;

use Netlipress\Commerce;
use Netlipress\Mail;


class Forms
{
    private $FormIndex;
    private $AjaxAction;

    public function __construct()
    {
        if (isset($_POST['FormIndex'])) {
            $this->FormIndex = $_POST['FormIndex'];
        }
        if (isset($_POST['AjaxAction'])) {
            $this->AjaxAction = $_POST['AjaxAction'];
        } else {
            $this->AjaxAction = false;
        }
    }

    public function handle()
    {
        if (!isset($_SESSION['FormAction'][$this->FormIndex]) && !$this->AjaxAction) {
            $this->redirect(['success' => false, 'error' => 'Action not found']);
            return;
        }

        if (COMMERCE_ACTIVE) {
            $commerce = new Commerce();
            if ($this->AjaxAction) {
                //TODO: CSRF here needed?
                if ($commerce->handleAjaxActions($this->AjaxAction)) {
                    return;
                }
            }
        }

        //Check csrf token
        if (!hash_equals($_SESSION['csrf_token'], $_POST['token'])) {
            $this->redirect(['success' => false, 'error' => 'Token invalid']);
            return;
        }

        //This is so that the form tags know for which index to show errors
        if (isset($_POST['FormIndex'])) {
            $_SESSION['FormIndex'] = $this->FormIndex;
        }
        if ($_SESSION['FormAction'][$this->FormIndex] === 'contact') {
            $this->contactForm();
        }

        if (COMMERCE_ACTIVE && $_SESSION['FormAction'][$this->FormIndex] === 'checkout') {
            $this->commerceForm();
        }
    }

    private function redirect($status = [], $url = false)
    {
        if ($url) {
            $redirect = $url;
        } else {
            //Build redirect url back to previous page with status as parameters
            $redirect = explode('?', $_SERVER['HTTP_REFERER'], 2)[0];
        }

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

    private function initialFormChecking()
    {
        $returnMessage = ['success' => false, 'form' => $this->FormIndex];
        //Validate based on schema
        $validationErrors = $this->validate();

        //Optionally Validate Recaptcha
        if (RECAPTCHA) {
            if (!$this->checkRecaptcha()) {
                $_SESSION['FormOldValues'][$this->FormIndex] = $_POST;
                $returnMessage['error'] = "Recaptcha invalid";
                $this->redirect($returnMessage);
            }
        }

        if (!empty($validationErrors)) {
            //Errors found to redirect back with errors and old data
            $_SESSION['FormValidationErrors'][$this->FormIndex] = $validationErrors;
            $_SESSION['FormOldValues'][$this->FormIndex] = $_POST;
            $this->redirect($returnMessage);
        }
    }

    private function commerceForm()
    {
        $this->initialFormChecking();
        $commerce = new Commerce();
        $commerce->createNewOrderFromCart();
    }

    private function contactForm()
    {
        $mail = new Mail();
        $fields = $_POST;
        $subject = $_SESSION['FormSubject'][$this->FormIndex] ?? 'Contactform';

        $this->initialFormChecking();

        //Remove fields from table
        unset($fields['recaptcha_response']);
        unset($fields['FormIndex']);

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
            $this->redirect(['success' => true, 'form' => $this->FormIndex]);
        } else {
            //TODO: Maybe move error out of the URL and into the SESSION
            $this->redirect(['success' => false,'error' => $response['message'], 'form' => $this->FormIndex]);
        }

    }

    private function validate()
    {
        //Init array
        $validationErrors = [];
        //Go through schema
        if (isset($_SESSION['FormSchema'][$this->FormIndex])) {
            foreach ($_SESSION['FormSchema'][$this->FormIndex] as $field => $ruleString) {
                $ruleErrors = $this->checkSchemaRule($_POST[$field], $ruleString);
                if (!empty($ruleErrors)) {
                    $validationErrors[] = ['field' => $field, 'error' => $ruleErrors['error'], 'rule' => $ruleErrors['rule']];
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
                    return ['error' => $messages[$rule] ?? $defaultError, 'rule' => 'required'];
                }
            }
            if ($rule == 'email') {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ['error' => $messages[$rule] ?? $defaultError, 'rule' => 'invalid'];
                }
            }
        }
    }

    private function checkRecaptcha()
    {
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
