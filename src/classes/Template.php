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
        //Allow for optional functions file that holds template functions
        $themeFunctionsFile = APP_ROOT . TEMPLATE_DIR . '/functions.php';
        if (file_exists($themeFunctionsFile)) {
            include($themeFunctionsFile);
        }
        //Include the template file to render the HTML
        $templateFile = APP_ROOT . TEMPLATE_DIR . '/' . $template . '.php';
        if (file_exists($templateFile)) {
            include($templateFile);
        } else {
            $router = new Router();
            $router->notFound('Template not found');
        }
    }
}
