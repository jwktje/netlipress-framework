<?php

use Netlipress\TemplateFormatter;

function get_field($fieldName)
{
    $formatter = new TemplateFormatter();

    if (!empty($GLOBALS['block']->{$fieldName})) {
        if ($formatter->shouldFormatField($GLOBALS['block']->type, $fieldName, 'sections')) {
            return $formatter->formatField($GLOBALS['block']->type, $fieldName, $GLOBALS['block']->{$fieldName}, 'sections');
        }
        return $GLOBALS['block']->{$fieldName};
    }
    if (!empty($GLOBALS['post']->{$fieldName})) {
        if ($formatter->shouldFormatField($GLOBALS['post']->type, $fieldName)) {
            return $formatter->formatField($GLOBALS['post']->type, $fieldName, $GLOBALS['post']->{$fieldName});
        }
        return $GLOBALS['post']->{$fieldName};
    }
    return false;
}

function the_field($fieldName)
{
    echo get_field($fieldName);
}
