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
        //Mail config
        if(!defined('MAIL_DIR')) {
            define('MAIL_DIR', APP_ROOT . CONTENT_DIR . '/mail');
        }
        if(!defined('FORM_HANDLE_URL')) {
            define('FORM_HANDLE_URL', '/handle-form');
        }
        if(!defined('MAIL_TO_NAME')) {
            define('MAIL_TO_NAME', 'NetliPress Recipient');
        }
        if(!defined('MAIL_TO_ADDRESS')) {
            define('MAIL_TO_ADDRESS', 'receiver@netlipress.test');
        }
        if(!defined('MAIL_FROM_NAME')) {
            define('MAIL_FROM_NAME', 'NetliPress Sender');
        }
        if(!defined('MAIL_FROM_ADDRESS')) {
            define('MAIL_FROM_ADDRESS', 'noreply@netlipress.test');
        }
    }
}
