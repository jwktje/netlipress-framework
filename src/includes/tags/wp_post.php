<?php

function have_posts()
{
    //Should return an array of posts that still need to be looped through on current request
    return $GLOBALS['loop'];
}

function the_post()
{
    //Should setup the globals for the next posts in the array
    $currentPost = $GLOBALS['loop'][0];
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

function get_the_permalink($file = false)
{
    $entryFile = $file ? $file : $GLOBALS['post']->path;
    $path_parts = pathinfo($entryFile);
    $slug_base = str_replace(APP_ROOT . CONTENT_DIR, '', $path_parts['dirname']);
    return $slug_base . '/' . $path_parts['filename'];
}

function the_permalink($file = false)
{
    echo get_the_permalink($file);
}

function get_post_type()
{
    return $GLOBALS['post']->post_type;
}

function get_posts($args = [])
{
    $defaults = array(
        'numberposts' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_type' => 'post'
    );
    // Merge options with defaults
    $args = array_merge($defaults, $args);
    // debug($args);

    //Get files
    $files = glob(APP_ROOT . CONTENT_DIR . '/' . $args['post_type'] . '/*');

    //Sort options
    //TODO: More sort options?
    if ($args['orderby'] == 'date') {
        $sort = $args['order'] == 'DESC' ? SORT_DESC : SORT_ASC;
        array_multisort(array_map('filectime', $files), $sort, $files);
    }
    if ($args['orderby'] == 'rand') {
        shuffle($files);
    }
    if ($args['numberposts'] !== -1) {
        $files = array_slice($files, 0, $args['numberposts']);
    }

    return $files;
}

function get_post($file)
{
    $data = json_decode(file_get_contents($file));
    $data->path = $file;
    //This turns the absolute path into a relative path, and then calls a static router function to filter out which collection we're handling
    $data->post_type = \Netlipress\Router::getCollectionFromRequestPath(str_replace(APP_ROOT . CONTENT_DIR,'',$file));
    return $data;
}

function setup_postdata($entry)
{
    global $post;
    $post = get_post($entry);
}

function wp_reset_postdata()
{
    global $post, $originalPost;
    $post = $originalPost;
}

function wp_trim_words( string $text, int $num_words = 55, string $more = null ) {
    $words_array = explode(' ', $text);
    $words_array = array_slice($words_array, 0, $num_words);
    $text = implode(' ', $words_array);
    if($more) {
        $text .= $more;
    }
    return $text;
}

function home_url() {
    $base = $_SERVER['HTTPS'] ? 'https' : 'http';
    $base .= '://' . $_SERVER['SERVER_NAME'];
    return $base;
}
