<?php

namespace Netlipress;

use Netlipress\Router;

class Template
{
    /**
     * Renders a template
     * @param $template
     * @param $data
     */
    public function render($template)
    {
        $templateFile = APP_ROOT . TEMPLATE_DIR . '/' . $template . '.php';
        if (file_exists($templateFile)) {
            include($templateFile);
        } else {
            $router = new Router();
            $router->notFound('Template not found');
        }
    }
}
