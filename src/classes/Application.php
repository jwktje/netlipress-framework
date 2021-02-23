<?php

namespace Netlipress;

use Netlipress\Router;

class Application
{
    public function __construct()
    {
        $this->setConfig();

        require __DIR__ . '/../includes/debug.php';
        require __DIR__ . '/../includes/hooks.php';
        require __DIR__ . '/../includes/template_tags.php';

        $router = new Router();
        $router->run();
    }

    private function setConfig() {
        if(!defined('SITE_NAME')) {
            define('SITE_NAME', 'NetliPress');
        }
        if(!defined('CONTENT_DIR')) {
            define('CONTENT_DIR', '/content');
        }
        if(!defined('PUBLIC_DIR')) {
            define('PUBLIC_DIR', '/web');
        }
        if(!defined('TEMPLATE_DIR')) {
            define('TEMPLATE_DIR', PUBLIC_DIR . '/theme');
        }
        if(!defined('TEMPLATE_URI')) {
            define('TEMPLATE_URI', '/theme');
        }
        if(!defined('BLOG_HOME')) {
            define('BLOG_HOME', '/blog');
        }
        if(!defined('POSTS_DIR')) {
            define('POSTS_DIR', APP_ROOT . CONTENT_DIR . '/post');
        }
    }
}
