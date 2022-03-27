<?php

namespace Netlipress;

class Application
{
    public function __construct()
    {
        $this->setConfig();

        require __DIR__ . '/../includes/debug.php';
        require __DIR__ . '/../includes/hooks.php';
        require __DIR__ . '/../includes/template_tags.php';
    }

    public function run()
    {
        $router = new Router();
        $router->run();
    }

    private function setConfig()
    {
        //Default values for static/string constant values
        $staticConstantDefaults = [
            'SITE_NAME' => 'NetliPress',
            'CONTENT_DIR' => '/content',
            'PUBLIC_DIR' => '/public',
            'TEMPLATE_URI' => '/theme',
            'BLOG_HOME' => '/blog',
            'USE_MIX' => false,
            'GTM_ACTIVE' => defined('GTM_CONTAINER_ID')
        ];

        foreach ($staticConstantDefaults as $constantName => $defaultValue) {
            if (!defined($constantName)) {
                define($constantName, $defaultValue);
            }
        }

        //Default values for dynamic constant values that use static constants
        $dynamicConstantDefaults = [
            'TEMPLATE_DIR' =>  PUBLIC_DIR . TEMPLATE_URI,
            'POSTS_DIR' => APP_ROOT . CONTENT_DIR . '/post',
            'SSG_OUTPUT_DIR' => APP_ROOT . '/build',
            'TEMPLATE_BLOCKS_DIR' =>  APP_ROOT . PUBLIC_DIR . TEMPLATE_URI . '/template-parts/blocks',
        ];

        foreach ($dynamicConstantDefaults as $constantName => $defaultValue) {
            if (!defined($constantName)) {
                define($constantName, $defaultValue);
            }
        }

    }
}
