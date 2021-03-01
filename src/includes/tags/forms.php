<?php

function form_init($subject, $messages, $action = 'contact')
{
    global $FormIndex;

    if (RECAPTCHA) {
        recaptcha_output_field();
    }

    $FormIndex = isset($FormIndex) ? $FormIndex + 1 : 1;

    //Output form index field which gets added to GET as 'form' on redirect
    echo '<input type="hidden" name="FormIndex" value="' . $FormIndex . '" />';
    //Output an anchor to redirect to
    echo '<div class="form-anchor" id="form-' . $FormIndex . '"></div>';

    //Functionality to check if any SESSION parameters where related to this form
    if (form_redirect_matches()) {

        // If refreshing or navigating to this page, or a submit was successfull
        // clear session from FormValidationErrors and FormOldValues for only this index
        if (!isset($_GET['success']) || $_GET['success'] == 1) {
            unset($_SESSION['FormAction'][$FormIndex]);
            unset($_SESSION['FormMessages'][$FormIndex]);
            unset($_SESSION['FormSubject'][$FormIndex]);
            unset($_SESSION['FormSchema'][$FormIndex]);
            unset($_SESSION['FormValidationErrors'][$FormIndex]);
            unset($_SESSION['FormOldValues'][$FormIndex]);
        }

        //Output any success and error messages
        if (form_success()) {
            echo '<div class="response-message success-message"><div class="check">' . ($messages['success'] ?? 'Success') . '</div></div>';
        }
        if (form_error()) {
            echo '<div class="response-message error-message">';
            if(isset($messages['error'])) {
                echo '<strong>' . $messages['error'] . '</strong>';
            }
            if (form_error() !== ' ') {
                echo '<div class="error">' . form_error() . '</div>';
            } else {
                form_output_unique_validation_errors();
            }
            echo '</div>';
        }

    }

    //Set session data
    $_SESSION['FormAction'][$FormIndex] = $action;
    if ($messages) {
        $_SESSION['FormMessages'][$FormIndex] = $messages;
    }
    if ($subject) {
        $_SESSION['FormSubject'][$FormIndex] = $subject;
    }
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

function form_field($type, $name, $placeholder, $schema = false, $outputValidationError = true)
{
    global $FormIndex;

    //Check if validation schema is provided. If so add it to the session
    if ($schema && !empty($schema)) {
        if (empty($_SESSION['FormSchema'][$FormIndex])) {
            $_SESSION['FormSchema'][$FormIndex] = [];
        }
        $_SESSION['FormSchema'][$FormIndex][$name] = $schema;
    }

    if ($type == 'textarea') {
        //Build textarea element
        if(strpos($schema, 'required') !== false) {
            $placeholder .= ' *';
        }
        echo "<textarea name='$name' placeholder='$placeholder'>";
    } elseif ($type == 'checkbox') {
        //Wrap in label
        echo "<label>";
        echo "<input type='$type' name='$name'";
    } else {
        //Build input element
        echo "<input type='$type' name='$name'";
        if ($placeholder) {
            if(strpos($schema, 'required') !== false) {
                $placeholder .= ' *';
            }
            echo " placeholder='$placeholder'";
        }
    }

    if (form_redirect_matches()) {
        //Add old value from session if it exists
        if (isset($_SESSION['FormOldValues'][$FormIndex]) && isset($_SESSION['FormOldValues'][$FormIndex][$name])) {
            if ($type == 'textarea') {
                echo $_SESSION['FormOldValues'][$FormIndex][$name];
            } else {
                echo " value='" . $_SESSION['FormOldValues'][$FormIndex][$name] . "'";
            }
        }

        //Error class
        if (form_field_error_key($name) !== false) {
            echo " class='has-error'";
        }
    }

    //Close out element
    if ($type == 'textarea') {
        echo "</textarea>";
    } elseif($type == 'checkbox') {
        echo " />";
        echo $placeholder ?? '';
        if(strpos($schema, 'required') !== false) {
            echo ' *';
        }
        echo "</label>";
    } else {
        echo " />";
    }

    if (form_redirect_matches()) {
        //Possibly output error
        form_field_error($name,$outputValidationError);
    }
}

function form_field_error_key($name)
{
    global $FormIndex;
    if (isset($_SESSION['FormValidationErrors'][$FormIndex])) {
        return array_search($name, array_column($_SESSION['FormValidationErrors'][$FormIndex], 'field'));
    } else {
        return false;
    }
}

function form_field_error($name, $outputValidationError)
{
    global $FormIndex;
    $key = form_field_error_key($name);
    if ($key !== false) {
        $class = 'validation-error ';
        $class .= $_SESSION['FormValidationErrors'][$FormIndex][$key]['rule'] ?? '';
        echo "<div class='$class'>";
        if($outputValidationError) {
            $_SESSION['FormValidationErrors'][$FormIndex][$key]['error'];
        }
        echo "</div>";
    }
}

function form_redirect_matches()
{
    global $FormIndex;
    return isset($_SESSION['FormIndex']) && $FormIndex === intval($_SESSION['FormIndex']);
}

function form_output_unique_validation_errors() {
    global $FormIndex;
    $errors = [];
    foreach($_SESSION['FormValidationErrors'][$FormIndex] as $item) {
        if(!in_array($item['error'], $errors)) {
            $errors[] = $item['error'];
            echo "<div class='" . $item['rule'] . "'>";
            echo $item['error'];
            echo "</div>";
        }
    }
}
