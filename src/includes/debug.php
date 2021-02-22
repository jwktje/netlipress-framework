<?php
if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function debug($var)
{
    if (DEBUG) {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
    }
}
