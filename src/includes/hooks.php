<?php
function wp_head() {
    //Page title
    echo '<title>'. SITE_NAME . ' - ' . get_the_title() . "</title>\n\t";

    //Favicon
    echo '<link rel="icon" href="/theme/img/icon.svg" type="image/svg+xml">' . "\n\t";

    //Mock enqueue of stylesheet
    echo '<link rel="stylesheet" href="/theme/dist/style.css?v=1.0">';
}

function wp_footer() {
    //Mock enqueue of JS
    echo '<script src="/theme/dist/actions.js"></script>';
}
