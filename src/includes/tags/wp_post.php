<?php

function have_posts() {
    //Should return an array of posts that still need to be looped through on current request
    return $GLOBALS['loop'];
}

function the_post() {
    //Should setup the globals for the next posts in the array
    $currentPost =  $GLOBALS['loop'][0];
    $GLOBALS['post'] = json_decode(file_get_contents($currentPost));
    $GLOBALS['post']->path = $currentPost;
    unset($GLOBALS['loop'][0]);
    $GLOBALS['loop'] = array_values($GLOBALS['loop']);
}

function get_the_title()
{
    return $GLOBALS['post']->title;
}

function the_title()
{
    echo '<h1>' . get_the_title() . '</h1>';
}

function get_the_permalink()
{
    $path_parts = pathinfo($GLOBALS['post']->path);
    $slug_base = str_replace(APP_ROOT . CONTENT_DIR , '', $path_parts['dirname']);
    return $slug_base . '/' . $path_parts['filename'];
}

function the_permalink()
{
    echo get_the_permalink();
}

