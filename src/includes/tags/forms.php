<?php

function form_init($subject, $schema, $messages, $action = 'contact')
{
    global $FormValidationErrors, $FormOldValues;

    //TODO: Add recaptcha?
    $schemaValue = htmlspecialchars(json_encode($schema), ENT_COMPAT);
    $messagesValue = htmlspecialchars(json_encode($messages), ENT_COMPAT);

    $hiddenFields = "<input type='hidden' name='action' value='$action' />";
    $hiddenFields .= "<input type='hidden' name='subject' value='$subject' />";
    $hiddenFields .= "<input type='hidden' name='schema' value='$schemaValue' />";
    $hiddenFields .= "<input type='hidden' name='messages' value='$messagesValue' />";

    if (isset($_SESSION['FormValidationErrors'])) {
        $FormValidationErrors = $_SESSION['FormValidationErrors'];
        unset($_SESSION['FormValidationErrors']);
    }
    if (isset($_SESSION['FormOldValues'])) {
        $FormOldValues = $_SESSION['FormOldValues'];
        unset($_SESSION['FormOldValues']);
    }

    echo $hiddenFields;
}

function form_success()
{
    return $_GET && $_GET['success'] && $_GET['success'] === '1';
}

function form_error()
{
    if ($_GET && $_GET['success'] === '0') {
        return isset($_GET['error']) ? $_GET['error'] : ' ';
    }
}

function form_field($type, $name, $placeholder)
{
    global $FormOldValues;

    if ($type == 'textarea') {
        //Build textarea element
        echo "<textarea name='$name' placeholder='$placeholder'>";
    } else {
        //Build input element
        echo "<input type='$type' name='$name' placeholder='$placeholder'";
    }

    //Add old value from session if it exists
    if (isset($FormOldValues) && isset($FormOldValues[$name])) {
        if ($type == 'textarea') {
            echo $FormOldValues[$name];
        } else {
            echo " value='" . $FormOldValues[$name] . "'";
        }
    }

    //Error class
    if (form_field_error_key($name) !== false) {
        echo " class='has-error'";
    }

    //Close out element
    if ($type == 'textarea') {
        echo "</textarea>";
    } else {
        echo " />";
    }

    //Possibly output error
    form_field_error($name);
}

function form_field_error_key($name)
{
    global $FormValidationErrors;
    if (isset($FormValidationErrors)) {
        return array_search($name, array_column($FormValidationErrors, 'field'));
    } else {
        return false;
    }
}

function form_field_error($name)
{
    global $FormValidationErrors;
    $key = form_field_error_key($name);
    if ($key !== false) {
        echo "<div class='validation-error'>" . $FormValidationErrors[$key]['error'] . "</div>";
    }
}
