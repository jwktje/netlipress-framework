<?php
function output_og_tags() {
    //Title tag
    $title = get_field('social_title');
    $title = !empty($title) ? $title : SITE_NAME . ' - ' . get_the_title();
    echo "\t<meta property='og:title' content='$title' />\n";

    //Optional description tag
    if($desc = get_field('social_desc')) {
        echo "\t<meta property='og:description' content='$desc' />\n";
    }

    //Optional image tag with fallback to home
    $image = get_field('social_image');
    $imageHome = get_field('social_image',APP_ROOT . CONTENT_DIR . '/page/index.json');
    $image = empty($image) ? $imageHome : $image;
    if(!empty($image)) {
        $image = wp_get_attachment_image_uri($image);
        echo "\t<meta property='og:image' content='$image' />";
    }

    echo "\n\n";
}
