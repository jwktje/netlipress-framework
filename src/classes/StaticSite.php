<?php

namespace Netlipress;

class StaticSite
{

    private Router $router;
    private Application $app;

    public function __construct()
    {
        //Create application to load constants from config
        $this->app = new Application();
        //Create router
        $this->router = new Router();

        //Use URL Env for Netlify or the provided domain from the commandline invocation
        if (getenv('URL')) {
            $siteUrl = getenv('URL');
        } else {
            global $argv;
            if (isset($argv[1]) && filter_var($argv[1], FILTER_VALIDATE_URL)) {
                $siteUrl = $argv[1];
            } else {
                echo 'ERROR: No URL enviroment variable found. Please provide a full URL to the build command. Example; https://mysite.com';
                die;
            }
        }
        $urlInfo = parse_url($siteUrl);
        $_SERVER['SERVER_NAME'] = $urlInfo['host'];
        $_SERVER['HTTPS'] = ($urlInfo['scheme'] === 'https') ? 'on' : 'off';
    }

    private function getDirContents($dir, &$results = array())
    {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
            }
        }

        return $results;
    }

    private function emptyBuildFolder()
    {
        if (file_exists(SSG_OUTPUT_DIR)) {
            $di = new \RecursiveDirectoryIterator(SSG_OUTPUT_DIR, \FilesystemIterator::SKIP_DOTS);
            $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($ri as $file) {
                $file->isDir() ? rmdir($file) : unlink($file);
            }
        }
    }

    private function renderBlogHome()
    {
        ob_start();
        $this->router->blog_home();
        file_put_contents(SSG_OUTPUT_DIR . '/blog.html', ob_get_clean());
    }

    private function createNetlifyCmsAdminFolder()
    {
        if (!file_exists(SSG_OUTPUT_DIR . '/admin')) {
            mkdir(SSG_OUTPUT_DIR . '/admin');
        }
        ob_start();
        include(APP_ROOT . PUBLIC_DIR . '/admin/index.php');
        file_put_contents(SSG_OUTPUT_DIR . '/admin/index.html', ob_get_clean());
    }

    private function renderContentJsonToHtml($jsonFileAbsolutePath, $stripFromSlug = null)
    {
        //Absolute to relative path and pathinfo
        $relativeFilePath = str_replace(APP_ROOT . CONTENT_DIR, '', $jsonFileAbsolutePath);
        $pathInfo = pathinfo($relativeFilePath);
        $fileRelativeDirname = $pathInfo['dirname'];

        //Optionally remove part from the output path
        if ($stripFromSlug) {
            $fileRelativeDirname = str_replace($stripFromSlug, '', $fileRelativeDirname);
        }

        if ($pathInfo['extension'] === 'json') {

            //Start an object to store the HTML
            ob_start();

            if ($relativeFilePath === '/page/index.json') {
                //Create homepage
                $this->router->handleUtilityPageRequest('/', 'front-page');
            } else {
                //Create default page
                $this->router->handleCollectionRequest(['path' => $fileRelativeDirname . '/' . $pathInfo['filename']]);
            }

            //Render page
            $pageHTML = ob_get_clean();

            //Write html to output file
            $outputFilePath = SSG_OUTPUT_DIR . $fileRelativeDirname;
            $outputFilename = $outputFilePath . '/' . $pathInfo['filename'] . '.html';

            //Make dir
            if (!file_exists($outputFilePath)) {
                mkdir($outputFilePath);
            }
            file_put_contents($outputFilename, $pageHTML);
        }

    }

    private function syncDirectoryToBuild($path)
    {
        if (!file_exists(SSG_OUTPUT_DIR . '/' . $path)) {
            mkdir(SSG_OUTPUT_DIR . '/' . $path);
        }

        $searchDir = APP_ROOT . PUBLIC_DIR . '/' . $path;

        foreach (new \DirectoryIterator($searchDir) as $fileInfo) {
            $name = $fileInfo->getFilename();
            if (!$fileInfo->isDot() && $fileInfo->isFile() && $name !== '.gitkeep') {
                copy($fileInfo->getPathname(), SSG_OUTPUT_DIR . '/' . $path . '/' . $name);
            }
        }
    }

    public function generate()
    {
        //Optionally create output dir
        if (!file_exists(SSG_OUTPUT_DIR)) {
            mkdir(SSG_OUTPUT_DIR);
        }

        //Empty output dir
        $this->emptyBuildFolder();

        //Create needed directories in build volder
        mkdir(SSG_OUTPUT_DIR . '/theme');
        mkdir(SSG_OUTPUT_DIR . '/post');

        //TODO: Create Sitemap.xml?

        //Scan through pages and render to HTML
        if(file_exists(PAGES_DIR)) {
            $pages = $this->getDirContents(PAGES_DIR);
            foreach ($pages as $page) {
                $this->renderContentJsonToHtml($page, '/page');
            }
        }

        //Scan through posts and render to HTML
        if(file_exists(POSTS_DIR)) {
            $posts = $this->getDirContents(POSTS_DIR);
            foreach ($posts as $post) {
                $this->renderContentJsonToHtml($post);
            }
        }

        //Create blog home
        $this->renderBlogHome();

        //Copy theme images to build directory
        $this->syncDirectoryToBuild('uploads');
        $this->syncDirectoryToBuild('theme/img');
        $this->syncDirectoryToBuild('theme/dist');

        //Create Netlify CMS admin index file from dynamic json config
        $this->createNetlifyCmsAdminFolder();
    }
}
