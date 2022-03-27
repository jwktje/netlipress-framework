<?php
function get_template_part($slug)
{
    $templatefile = APP_ROOT . TEMPLATE_DIR . '/' . $slug . '.php';
    if (file_exists($templatefile)) {
        include($templatefile);
    }
}

function get_template_directory()
{
    return TEMPLATE_DIR;
}

function get_template_directory_uri()
{
    return TEMPLATE_URI;
}


function get_header()
{
    if (file_exists(APP_ROOT . TEMPLATE_DIR . '/header.php')) {
        include(APP_ROOT . TEMPLATE_DIR . '/header.php');
    }
}

function get_footer()
{
    if (file_exists(APP_ROOT . TEMPLATE_DIR . '/footer.php')) {
        include(APP_ROOT . TEMPLATE_DIR . '/footer.php');
    }
}

function body_class() {
    echo ''; //TODO: Implement body classes?
}

function render_blocks()
{
    foreach ($GLOBALS['post']->blocks as $currentBlock) {
        $blockFile = APP_ROOT . TEMPLATE_DIR . '/template-parts/blocks/' . $currentBlock->type . '.php';
        if (file_exists($blockFile)) {
            global $block;
            $block = $currentBlock;
            include($blockFile);
        }
    }
}

function the_content()
{
    if (empty($GLOBALS['post']->blocks)) {
        the_field('body');
    } else {
        render_blocks();
    }
}

function is_404() {
    global $is404;
    return $is404;
}
