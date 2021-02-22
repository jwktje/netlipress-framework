<?php

namespace Netlipress;

class Router
{

    private $collectionTemplateMapping = [
        'page' => 'page',
        'post' => 'single',
    ];

    /**
     * Handles the route
     */
    public function run()
    {
        //Turn requested path into a collection + a path var
        $req = parse_url($_SERVER['REQUEST_URI']);
        $pathArr = explode('/', $req['path']);

        //Use first part of the path for collection matching
        $collection = $pathArr[1];

        if(!isset($this->collectionTemplateMapping[$collection])) {
            //If the first part of the path isn't a mapped collection we assume it's a page
            $collection = 'page';
        } else {
            //Remove first parth from the array because it's the collection base slug
            unset($pathArr[1]);
        }

        $path = implode('/', $pathArr);

        //Build file path
        $reqPath = APP_ROOT . CONTENT_DIR . '/' . $collection . $path;
        $reqFile = is_dir($reqPath) ? $reqPath . '/index.json' : $reqPath . '.json'; //To handle nested collections correctly

        //Render page or 404
        file_exists($reqFile) ? $this->page($reqFile, $collection) : $this->notFound();
    }

    /**
     * Returns a page with the entry data
     * @param $entry
     * @param string $collection
     */
    private function page($entry, $collection = 'page')
    {
        $tpl = new Template();
        http_response_code(200);
        $templateToUse = $this->collectionTemplateMapping[$collection];

        if (!$templateToUse) {
            $this->notFound('Collection not mapped to a template');
        } else {
            $tpl->render($templateToUse, json_decode(file_get_contents($entry)));
        }

    }

    /**
     * Returns the 404 page
     * @param string $error
     */
    public function notFound($error = '')
    {
        http_response_code(404);
        include(APP_ROOT . TEMPLATE_DIR . '/404.php');
    }

}
