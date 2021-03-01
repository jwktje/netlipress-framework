<?php

namespace Netlipress;

use Netlipress\Forms;
use Netlipress\ImageResizer;

class Router
{

    /**
     * Handles the route
     */
    public function run()
    {
        //Parse the incoming request
        $request = parse_url($_SERVER['REQUEST_URI']);
        $requestedFile = pathinfo(($request['path']));

        //If req path root, render the frontpage
        if ($request['path'] == '/') {
            $frontPageFile = APP_ROOT . CONTENT_DIR . '/page/index.json';
            if(file_exists($frontPageFile)) {
                $this->front_page($frontPageFile);
                return;
            }
        }

        //If req path is the blog home, setup the query data and render the post index template
        if ($request['path'] == BLOG_HOME) {
            $this->blog_home();
            return;
        }

        //When POSTing to the form handle URL, pass it over to the form handler
        if ($request['path'] == FORM_HANDLE_URL && $_SERVER['REQUEST_METHOD'] === "POST") {
            $formHandler = new Forms();
            $formHandler->handle();
            return;
        }

        //Handle resized image requests
        if(isset($requestedFile['extension']) && in_array($requestedFile['extension'],['jpg','png']) && strpos($request['query'], 'size') !== false) {
            parse_str($request['query'],$query);
            if(isset($query['size'])) {
                $sizes = explode('x',$query['size']);
                //This returns an image scaled by the server on the fly
                $image = new ImageResizer($request['path']);
                $image->resizeImage($sizes[0],$sizes[1]);
                return;
            }
        }

        //Handle request for a page in a collection by default. Returns 404 if entry doesn't exist
        $this->handleCollectionRequest($request);

    }

    /**
     * Render a page for an entry in a collection
     */

    private function handleCollectionRequest($request) {
        //Make a path array
        $pathArr = explode('/', $request['path']);

        //Get collection
        $collection = $this->getCollectionFromRequestPath($request['path']);

        if($collection !== 'page') {
            //Remove first part from the array because it's the collection base slug, and we already have this as a separate var.
            unset($pathArr[1]);
        }
        //Rebuild path without collection
        $path = implode('/', $pathArr);

        //Build file path
        $reqPath = APP_ROOT . CONTENT_DIR . '/' . $collection . $path;
        //Remove trailing slash if present
        $reqPath = rtrim($reqPath,"/");
        //Build path to file
        $reqFile = is_dir($reqPath) ? $reqPath . '/index.json' : $reqPath . '.json'; //To handle nested collections correctly

        //Render page or 404
        file_exists($reqFile) ? $this->page($reqFile, $collection) : $this->notFound();
    }

    /**
     * Get collection from Request
     */

    public static function getCollectionFromRequestPath($requestPath) {

        $pathArr = explode('/', $requestPath);
        $collection = $pathArr[1];
        $knownCollections = self::getKnownCollections();

        if (!in_array($collection, $knownCollections)) {
            //If the first part of the path isn't a known collection we assume it's a page
            $collection = 'page';
        }

        return $collection;
    }

    /**
     * Gets all collections that are defined in the content folder
     */

    private static function getKnownCollections()
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
     * Renders the static front page
     */

    private function front_page($entry) {
        $tpl = new Template();
        http_response_code(200);

        $template = file_exists(APP_ROOT . TEMPLATE_DIR . '/front-page.php') ? 'front-page' : 'page';
        global $post;
        $post = get_post($entry);
        $tpl->render($template);
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
            global $post, $originalPost;
            $post = get_post($entry);
            //Save post for resetting
            $originalPost = $post;
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
