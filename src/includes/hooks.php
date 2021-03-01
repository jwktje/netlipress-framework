<?php

function wp_head()
{
    //Page title
    echo '<title>' . SITE_NAME . ' - ' . get_the_title() . "</title>\n\t";

    //Favicon
    echo '<link rel="icon" href="/theme/img/icon.svg" type="image/svg+xml">' . "\n\t";

    //Mock enqueue of stylesheet
    if (USE_MIX) {
        $cssFile = json_decode(file_get_contents(APP_ROOT . TEMPLATE_DIR . '/dist/mix-manifest.json'))->{'/style.css'};
        echo '<link rel="stylesheet" href="' . TEMPLATE_URI . '/dist' . $cssFile . '">';
    } else {
        echo '<link rel="stylesheet" href="/theme/style.css">';
    }

    //GTM optional include
    if(GTM_ACTIVE) {
        gtm_head();
    }
}

function wp_body_open() {
    //GTM optional include
    if(GTM_ACTIVE) {
        gtm_body();
    }
}

function wp_footer()
{
    //Mock enqueue of JS
    if (USE_MIX) {
        $jsFile = json_decode(file_get_contents(APP_ROOT . TEMPLATE_DIR . '/dist/mix-manifest.json'))->{'/actions.js'};
        echo '<script src="' . TEMPLATE_URI . '/dist' . $jsFile . '"></script>';
    } else {
        echo '<script src="/theme/dist/actions.js"></script>';
    }
}
