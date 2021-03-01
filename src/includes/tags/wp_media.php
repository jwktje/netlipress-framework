<?php

function wp_get_attachment_image($url)
{
    if (!$url) return;
    if(substr($url, 0, strlen(PUBLIC_DIR)) === PUBLIC_DIR) {
        //Remove public dir from base of URL
        $url = substr($url, strlen(PUBLIC_DIR));
    }
    $alt = basename($url);
    $sizes = getimagesize(APP_ROOT . PUBLIC_DIR . $url);
    return "<img src='$url' alt='$alt' $sizes[3]/>";
}

function output_link($link, $class = '')
{
    $link = (object) $link;
    if (empty($link->url)) return;

    $url = $link->url ?? '#0';
    $target = $link->target ?? '_self';
    $title = $link->title ?? '';

    echo "<a class='$class' href='$url' target='$target' title='$title'>$title</a>";
}
