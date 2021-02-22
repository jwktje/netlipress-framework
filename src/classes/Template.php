<?php

namespace Netlipress;

class Template
{
    /**
     * Renders a template
     * @param $template
     * @param $data
     */
    public function render($template, $data) {
        $templateFile = APP_ROOT . TEMPLATE_DIR . '/' . $template . '.php';
        if (file_exists($templateFile)) {
            //Make data available as global for use in template
            global $post;
            $post = $data;
            include($templateFile);
        } else {
            return_404('Template not found');
        }
    }
}
