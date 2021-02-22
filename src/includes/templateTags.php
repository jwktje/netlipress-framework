<?php
use Netlipress\TemplateFormatter;

function get_header()
{
    if (file_exists(APP_ROOT . TEMPLATE_DIR . '/header.php')) {
        include(APP_ROOT . TEMPLATE_DIR . '/header.php');
    }
}

function get_footer()
{
    if (file_exists(APP_ROOT . TEMPLATE_DIR . '/footer.php')) {
        include(APP_ROOT . TEMPLATE_DIR . '/footer.php');
    }
}

function get_the_title()
{
    return $GLOBALS['post']->title;
}

function the_title()
{
    echo '<h1>' . get_the_title() . '</h1>';
}

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

function render_blocks()
{
    foreach ($GLOBALS['post']->sections as $section) {
        $blockFile = APP_ROOT . TEMPLATE_DIR . '/template-parts/blocks/' . $section->type . '.php';
        if (file_exists($blockFile)) {
            global $block;
            $block = $section;
            include($blockFile);
        }
    }
}

function the_content()
{
    if (empty($GLOBALS['post']->sections)) {
        echo $GLOBALS['post']->body;
    } else {
        render_blocks();
    }
}

function wp_get_attachment_image($url)
{
    if (!$url) return;
    $alt = basename($url);
    $sizes = getimagesize(APP_ROOT . PUBLIC_DIR . $url);
    return "<img src='$url' alt='$alt' $sizes[3]/>";
}

function output_link($link, $class = '')
{
    if (empty($link->url)) return;
    $url = $link->url ?? '#0';
    $target = $link->target ?? '_self';
    $title = $link->title ?? '';
    echo "<a class='$class' href='$url' target='$target' title='$title'>$title</a>";
}

function get_template_part($slug) {
    $templatefile = APP_ROOT . TEMPLATE_DIR . '/' . $slug . '.php';
    if (file_exists($templatefile)) {
        include($templatefile);
    }
}

function get_template_directory() {
    return TEMPLATE_DIR;
}

function get_template_directory_uri() {
    return TEMPLATE_URI;
}
