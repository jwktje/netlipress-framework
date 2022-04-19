<?php

function wp_get_attachment_image_uri($url)
{
    if (substr($url, 0, strlen(PUBLIC_DIR)) === PUBLIC_DIR) {
        //Remove public dir from base of URL
        $url = substr($url, strlen(PUBLIC_DIR));
    }
    return $url;
}

function wp_get_attachment_image($url, $size = 'full', $icon = false, $attr = [])
{
    if (!$url) return;
    $url = wp_get_attachment_image_uri($url);
    ;

    if ($size == 'thumb') {
        $url .= '?size=150x150';
        $sizes = 'width="150" height="150"';
    } else {
        $sizes = getimagesize(APP_ROOT . PUBLIC_DIR . $url)[3];
    }

    $defaultsAttr = [
        'class' => '',
        'loading' => 'lazy',
        'alt' => $alt = basename($url)
    ];

    $attr = array_merge(
        $defaultsAttr,
        array_intersect_key($attr, $defaultsAttr)
    );

    $html = "<img src='$url' $sizes";
    foreach($attr as $name => $value) {
        $html .= " $name=" . '"' . $value . '"';
    }
    $html .= "/>";
    return $html;
}

function output_link($link, $class = '')
{
    $link = (object)$link;
    if (empty($link->url)) return;

    $url = $link->url ?? '#0';
    $target = $link->target ?? '_self';
    $title = $link->title ?? '';

    echo "<a class='$class' href='$url' target='$target' title='$title'>$title</a>";
}
