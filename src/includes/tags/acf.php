<?php

use Netlipress\TemplateFormatter;

function get_settings($settingsGroup)
{
    $settings = [];
    $settingsFile = APP_ROOT . CONTENT_DIR . '/settings/' . $settingsGroup . '.json';
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile));
    }
    return $settings;
}

function get_field($fieldName, $from = false)
{
    $formatter = new TemplateFormatter();

    if ($from === 'option' && strpos($fieldName, '_') !== false) {
        //Settings are assumed to be prefixed by a group corresponding to the settings json filename. eg; socials_facebook
        $fieldNameArr = explode('_', $fieldName);
        $settings = get_settings($fieldNameArr[0]);
        unset($fieldNameArr[0]);
        $fieldName = implode('_', $fieldNameArr);
        if (isset($settings->{$fieldName})) {
            return $settings->{$fieldName};
        }
    } elseif ($from !== false) {
        if (isset($from->path) && file_exists($from->path)) {
            //A post object was passed
            $post = $from;
        } else {
            //A path was probably passed
            if (file_exists($from)) {
                $post = get_post($from);
            }
        }
        //Formatting can work, but doesn't yet on getting fields from custom entries. TODO: Possibly make casting work here
        if (isset($post, $post->{$fieldName})) {
            return $post->{$fieldName};
        }
        return false; //something was passed but a field wasn't found. So don't keep getting it from globals
    }

    if (!empty($GLOBALS['block']->{$fieldName})) {
        //Getting field inside a block partial
        return $formatter->formatField($fieldName, $GLOBALS['block']->{$fieldName}, $GLOBALS['block']->type);
    }

    if (!empty($GLOBALS['post']->{$fieldName})) {
        //Getting field on a post
        return $formatter->formatField($fieldName, $GLOBALS['post']->{$fieldName});
    }
    return false;
}

function the_field($fieldName, $from = false)
{
    echo get_field($fieldName, $from);
}
