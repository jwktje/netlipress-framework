<?php

namespace Netlipress;

class Router
{

    /**
     * Handles the route
     */
    public function run()
    {
        $req = parse_url($_SERVER['REQUEST_URI']);
        $reqPath = APP_ROOT . PAGE_DIR . $req['path'];
        $reqFile = is_dir($reqPath) ? $reqPath . '/index.json' : $reqPath . '.json'; //To handle nested collections correctly
        file_exists($reqFile) ? $this->page($reqFile) : $this->notFound();
    }

    /**
     * Returns a page with the entry data
     * @param $entry
     * @param string $template
     */
    private function page($entry, $template = 'page')
    {
        $tpl = new Template();
        http_response_code(200);
        $tpl->render($template, json_decode(file_get_contents($entry)));
    }

    /**
     * Returns the 404 page
     * @param string $error
     */
    private function notFound($error = '')
    {
        http_response_code(404);
        include(APP_ROOT . TEMPLATE_DIR . '/404.php');
    }

}
