<?php

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
