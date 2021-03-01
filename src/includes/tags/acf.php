<?php

use Netlipress\TemplateFormatter;

function get_settings($settingsGroup) {
    $settings = [];
    $settingsFile = APP_ROOT . CONTENT_DIR . '/settings/' . $settingsGroup . '.json';
    if(file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile));
    }
    return $settings;
}

function get_field($fieldName, $from = false)
{
    $formatter = new TemplateFormatter();

    if ($from === 'option' && strpos($fieldName, '_') !== false) {
        //Settings are assumed to be prefixed by a group corresponding to the settings json filename
        $fieldNameArr = explode('_',$fieldName);
        $settings = get_settings($fieldNameArr[0]);
        if(isset($settings->{$fieldNameArr[1]})) {
            return $settings->{$fieldNameArr[1]};
        }
    } elseif($from !== false) {
        //Possibly get field from specific entry/post
        if(file_exists($from)) {
            $post = get_post($from);
            //Formatting can work, but doesn't yet on getting fields from custom entries. TODO: Possibly make casting work here
            if(isset($post->{$fieldName})) {
                return $post->{$fieldName};
            }
        }
    }

    if (!empty($GLOBALS['block']->{$fieldName})) {
        if ($formatter->shouldFormatField($GLOBALS['block']->type, $fieldName, 'sections')) {
            return $formatter->formatField($GLOBALS['block']->type, $fieldName, $GLOBALS['block']->{$fieldName}, 'sections');
        }
        return $GLOBALS['block']->{$fieldName};
    }
    if (!empty($GLOBALS['post']->{$fieldName})) {
        if ($formatter->shouldFormatField(false, $fieldName, $GLOBALS['post']->post_type)) {
            return $formatter->formatField(false, $fieldName, $GLOBALS['post']->{$fieldName}, $GLOBALS['post']->post_type);
        }
        return $GLOBALS['post']->{$fieldName};
    }
    return false;
}

function the_field($fieldName, $from = false)
{
    echo get_field($fieldName, $from);
}
