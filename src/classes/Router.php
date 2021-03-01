<?php

namespace Netlipress;

use Netlipress\Forms;

class Router
{

    /**
     * Handles the route
     */
    public function run()
    {
        //Parse the incoming request
        $req = parse_url($_SERVER['REQUEST_URI']);

        //If req path is the blog home, setup the query data and render the post index template
        if ($req['path'] == BLOG_HOME) {
            $this->blog_home();
            return;
        }

        //When POSTing to the form handle URL, pass it over to the form handler
        if ($req['path'] == FORM_HANDLE_URL && $_SERVER['REQUEST_METHOD'] === "POST") {
            $formHandler = new Forms();
            $formHandler->handle();
            return;
        }

        //Use first part of the path for collection matching
        $pathArr = explode('/', $req['path']);
        $collection = $pathArr[1];
        $knownCollections = $this->getKnownCollections();

        if (!in_array($collection, $knownCollections)) {
            //If the first part of the path isn't a known collection we assume it's a page
            $collection = 'page';
        } else {
            //Remove first part from the array because it's the collection base slug
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
     * Gets all collections that are defined in the content folder
     */

    private function getKnownCollections()
    {
        $collections = [];
        //Core collections without single
        $excluded = ['menu','settings'];

        //Get paths
        $dirs = array_filter(glob(APP_ROOT . CONTENT_DIR . '/*'), 'is_dir');
        foreach($dirs as $dir) {
            $dirSlug = str_replace(APP_ROOT . CONTENT_DIR . '/', '', $dir);
            if(!in_array($dirSlug, $excluded)) {
                $collections[] = $dirSlug;
            }
        }
        return $collections;
    }

    /**
     * Renders the blog home page and sets up loop globals
     */

    private function blog_home()
    {
        $tpl = new Template();
        http_response_code(200);

        //Create a global loop array with entries for use in templates
        $foundPosts = [];
        foreach (new \DirectoryIterator(POSTS_DIR) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            $foundPosts[] = $fileInfo->getPathname();
        }

        global $loop, $post;
        $loop = $foundPosts;
        $post = (object)['title' => 'Blog']; //TODO: Possibly improve. This makes the page title work but maybe it should be a config value

        $tpl->render('index');
    }

    /**
     * Renders a page with the entry data
     * @param $entry
     * @param string $collection
     */
    private function page($entry, $collection = 'page')
    {
        $tpl = new Template();
        http_response_code(200);
        if ($collection == 'page') {
            $templateToUse = 'page';
        } elseif ($collection == 'post') {
            $templateToUse = 'single';
        } else {
            $templateToUse = 'single-' . $collection;
        }

        if (!$templateToUse) {
            $this->notFound('Collection not mapped to a template');
        } else {
            //Make data available as global for use in template
            $data = json_decode(file_get_contents($entry));
            global $post;
            $post = $data;

            //Define extra meta about this entry
            $post->path = $entry; //For use with the permalink
            $post->post_type = $collection; //for use in templates for conditional rendering

            $tpl->render($templateToUse);
        }
    }

    /**
     * Returns the 404 page
     * @param string $error
     */
    public function notFound($error = '')
    {
        global $is404, $post;
        $post = (object)['title' => '404']; //TODO: Possibly improve. Just to make header title work on 404
        $is404 = true;
        http_response_code(404);
        include(APP_ROOT . TEMPLATE_DIR . '/404.php');
    }
}
