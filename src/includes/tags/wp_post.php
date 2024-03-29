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
    $GLOBALS['post']->post_type = \Netlipress\Router::getCollectionFromRequestPath(str_replace(APP_ROOT . CONTENT_DIR, '', $currentPost));
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
    $path = $GLOBALS['post']->path ?? null; //Maybe post is in the global
    $entryFile = $file ?? $path; //Maybe a filepath was passed
    if (isset($file->path)) $entryFile = $file->path; //Maybe an object was passed
    if (!$entryFile) return null;
    $path_parts = pathinfo($entryFile);
    $filename = $path_parts['filename'];
    $slug_base = str_replace(APP_ROOT . CONTENT_DIR, '', $path_parts['dirname']);
    //Remove page base slug root + when nested
    if ($slug_base === '/page') {
        $slug_base = '';
    }
    if (str_contains($slug_base, '/page')) {
        $slug_base = str_replace('/page', '', $slug_base);
    }
    $filename = $filename === 'index' ? '' : $filename;
    return $slug_base . '/' . $filename;
}

function get_permalink($file = false)
{
    return get_the_permalink($file);
}

function the_permalink($file = false)
{
    echo get_the_permalink($file);
}

function get_slug_from_entry($file)
{
    $path_parts = pathinfo($file);
    $path_parts = explode('/', $path_parts['dirname']);
    return end($path_parts);
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
        'post_type' => 'post',
    );
    // Merge options with defaults
    $args = array_merge($defaults, $args);

    //Get files
    $files = glob(APP_ROOT . CONTENT_DIR . '/' . $args['post_type'] . '/*');

    //Filter on category
    if (isset($args['category'])) {
        foreach ($files as $idx => $file) {
            $post = get_post($file);
            if (!isset($post->category) || $args['category'] !== $post->category) {
                unset($files[$idx]);
            }
        }
    }

    //Sort options
    //TODO: More sort options?
    if ($args['orderby'] === 'date') {
        $sort = $args['order'] === 'DESC' ? SORT_DESC : SORT_ASC;

        //Sort by date field, if none found, do filectime
        $foundDateField = false;

        $postsSortArray = [];
        foreach ($files as $idx => $file) {
            $post = get_post($file);
            if (isset($post->date)) {
                $foundDateField = true;
            }
            $postsSortArray[] = ['file' => $file, 'date' => $post->date ?? null];
        }
        if ($foundDateField) {
            usort($postsSortArray, function ($a, $b) {
                return strcmp($a['date'] ?? null, $b['date'] ?? null);
            });
            $files = array_column($postsSortArray, 'file');
            if ($sort === SORT_DESC) {
                $files = array_reverse($files);
            }
        } else {
            array_multisort(array_map('filectime', $files), $sort, $files);
        }
    }
    if ($args['orderby'] === 'rand') {
        shuffle($files);
    }
    if ($args['orderby'] === 'menu_order') {
        usort($files, function ($a, $b) {
            return strcmp($a->menu_order, $b->menu_order);
        });
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
    $data->post_type = \Netlipress\Router::getCollectionFromRequestPath(str_replace(APP_ROOT . CONTENT_DIR, '', $file));
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

function wp_trim_words(string $text, int $num_words = 55, string $more = null)
{
    $words_array = explode(' ', $text);
    $words_array = array_slice($words_array, 0, $num_words);
    $text = implode(' ', $words_array);
    if ($more) {
        $text .= $more;
    }
    return $text;
}

function home_url()
{
    //Check protocol
    $isSecure = false;
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $isSecure = true;
    } elseif ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')) {
        $isSecure = true;
    }
    //Build full URL
    $base = $isSecure ? 'https' : 'http';
    $base .= '://' . $_SERVER['SERVER_NAME'];
    return $base;
}

function the_date($format = 'F j, Y')
{
    echo get_the_date($format);
}

function get_the_date($format = 'F j, Y')
{
    global $post;
    if (is_string($post)) {
        $post = get_post($post);
    }
    if (isset($post->date)) {
        return date($format, strtotime($post->date));
    }
    return date($format, filemtime($post->path));
}

function get_the_category($post)
{
    if (!$post) return null;
    $cat = get_field('category', $post);
    $catFilePath = APP_ROOT . CONTENT_DIR . '/category/' . $cat . '.json';
    if (!file_exists($catFilePath)) return null;
    return get_post($catFilePath);
}
