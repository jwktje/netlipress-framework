<?php
function form_init($subject, $messages, $action = 'contact', $outputUniqueErrorMessages = true)
{
    global $FormCount, $FormIndex;

    $FormCount = isset($FormCount) ? $FormCount + 1 : 1;
    $FormIndex = $action . '_' . $FormCount;

    //CSRF Token
    if (!isset($_SESSION['csrf_token']) || (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1))) {
        // last request was more than 5 minutes ago or no token was set yet
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
    echo '<input type="hidden" name="token" value="' . $_SESSION['csrf_token'] . '" />';

    //Output form index field which gets added to GET as 'form' on redirect
    echo '<input type="hidden" name="FormIndex" value="' . $FormIndex . '" />';

    if (RECAPTCHA) {
        recaptcha_output_field();
    }

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
            if (isset($messages['error'])) {
                echo '<strong>' . $messages['error'] . '</strong>';
            }
            if (form_error() !== ' ') {
                echo '<div class="error">' . form_error() . '</div>';
            } elseif ($outputUniqueErrorMessages) {
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

function form_field_append_schema($name, $schema)
{
    global $FormIndex;

    //Check if validation schema is provided. If so add it to the session
    if ($schema && !empty($schema)) {
        if (empty($_SESSION['FormSchema'][$FormIndex])) {
            $_SESSION['FormSchema'][$FormIndex] = [];
        }
        $_SESSION['FormSchema'][$FormIndex][$name] = $schema;
    }

}

function form_field($type, $name, $placeholder, $schema = false, $outputValidationError = true)
{
    global $FormIndex;

    form_field_append_schema($name, $schema);

    if ($type == 'textarea') {
        //Build textarea element
        if (strpos($schema, 'required') !== false) {
            $placeholder .= ' *';
        }
        echo "<textarea name='$name' placeholder='$placeholder'>";
    } else {
        //Build input element
        echo "<input type='$type' name='$name'";
        if ($placeholder) {
            if (strpos($schema, 'required') !== false) {
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
    } else {
        echo " />\n";
    }

    if (form_redirect_matches()) {
        //Possibly output error
        form_field_error($name, $outputValidationError);
    }
}

function form_field_checkbox($name, $value, $labelText, $schema = false, $outputValidationError = true)
{
    global $FormIndex;
    form_field_append_schema($name, $schema);

    //Wrap in label
    echo "<label>";
    echo "<input type='checkbox' name='$name' value='$value'";

    if (form_redirect_matches()) {
        //Add old value from session if it exists
        if (isset($_SESSION['FormOldValues'][$FormIndex]) && isset($_SESSION['FormOldValues'][$FormIndex][$name])) {
            echo " checked='true'";
        }
        //Error class
        if (form_field_error_key($name) !== false) {
            echo " class='has-error'";
        }
    }

    //Close elements
    echo " />";
    echo $labelText ?? '';
    if (strpos($schema, 'required') !== false) {
        echo ' *';
    }
    echo "</label>\n";

    if (form_redirect_matches()) {
        //Possibly output error
        form_field_error($name, $outputValidationError);
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
        if ($outputValidationError) {
            echo $_SESSION['FormValidationErrors'][$FormIndex][$key]['error'];
        }
        echo "</div>";
    }
}

function form_redirect_matches()
{
    global $FormIndex;
    return isset($_SESSION['FormIndex']) && $FormIndex === $_SESSION['FormIndex'];
}

function form_output_unique_validation_errors()
{
    global $FormIndex;
    $errors = [];
    foreach ($_SESSION['FormValidationErrors'][$FormIndex] as $item) {
        if (!in_array($item['error'], $errors)) {
            $errors[] = $item['error'];
            echo "<div class='" . $item['rule'] . "'>";
            echo $item['error'];
            echo "</div>";
        }
    }
}
