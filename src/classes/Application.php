<?php

namespace Netlipress;

use Netlipress\Router;

class Application
{
    public function __construct()
    {
        $this->setConfig();

        require __DIR__ . '/../includes/debug.php';
        require __DIR__ . '/../includes/templateTags.php';

        $router = new Router();
        $router->run();
    }

    private function setConfig() {
        if(!defined('CONTENT_DIR')) {
            define('CONTENT_DIR', '/content');
        }
        if(!defined('TEMPLATE_DIR')) {
            define('TEMPLATE_DIR', '/theme');
        }
    }
}
