<?php

function wp_head()
{
    //Page title
    $title = get_field('social_title');
    $title = !empty($title) ? $title : SITE_NAME . ' - ' . get_the_title();
    echo '<title>' . $title . "</title>\n\t";

    //Favicon
    echo '<link rel="icon" href="/theme/img/icon.svg" type="image/svg+xml">' . "\n\t";

    //Mock enqueue of stylesheet
    if (USE_MIX) {
        $manifest = @file_get_contents(APP_ROOT . TEMPLATE_DIR . '/dist/mix-manifest.json');
        if ($manifest) {
            $cssFile = json_decode($manifest)->{'/style.css'};
            echo '<link rel="stylesheet" href="' . TEMPLATE_URI . '/dist' . $cssFile . '">';
        }
    } else {
        echo '<link rel="stylesheet" href="/theme/style.css">';
    }
    echo "\n\n";

    //OpenGraph
    output_og_tags();

    //GTM optional include
    if (GTM_ACTIVE) {
        gtm_head();
    }
}

function wp_footer()
{
    //Mock enqueue of JS
    if (USE_MIX) {
        $manifest = @file_get_contents(APP_ROOT . TEMPLATE_DIR . '/dist/mix-manifest.json');
        if ($manifest) {
            $jsFile = json_decode($manifest)->{'/actions.js'};
            echo '<script src="' . TEMPLATE_URI . '/dist' . $jsFile . '"></script>';
        }
    } else {
        echo '<script src="/theme/dist/actions.js"></script>';
    }

    //Conditionally load Netlify CMS Identity Code
    \Netlipress\NetlifyCms::outputNetlifyIdentityWidget();
    \Netlipress\NetlifyCms::outputNetlifyIdentityScript();
}
