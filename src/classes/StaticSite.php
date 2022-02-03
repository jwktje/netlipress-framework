<?php

namespace Netlipress;

class StaticSite
{

    private string $outputDirectory = APP_ROOT . '/build';
    private string $contentDirectory = APP_ROOT . '/content';
    private Router $router;

    public function __construct()
    {
        //Create router
        $this->router = new Router();

        //Use URL Env for Netlify or the provided domain from the commandline invocation
        if(getenv('URL')) {
            $siteUrl = getenv('URL');
        } else {
            global $argv;
            if(isset($argv[1]) && filter_var($argv[1], FILTER_VALIDATE_URL)) {
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

    private function emptyBuildFolder() {
        $dir = $this->outputDirectory;
        if(file_exists($dir)){
            $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
            $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ( $ri as $file ) {
                $file->isDir() ?  rmdir($file) : unlink($file);
            }
        }
    }

    private function renderBlogHome() {
        ob_start();
        $this->router->blog_home();
        file_put_contents($this->outputDirectory . '/blog.html', ob_get_clean());
    }

    private function createNetlifyCmsAdminFolder() {
        if (!file_exists($this->outputDirectory. '/admin')) {
            mkdir($this->outputDirectory. '/admin');
        }
        ob_start();
        include(APP_ROOT . '/web/admin/index.php');
        file_put_contents($this->outputDirectory . '/admin/index.html', ob_get_clean());
    }

    private function renderContentJsonToHtml($jsonFileAbsolutePath, $stripFromSlug = null) {
        //Absolute to relative path and pathinfo
        $relativeFilePath = str_replace($this->contentDirectory, '', $jsonFileAbsolutePath);
        $pathInfo = pathinfo($relativeFilePath);
        $fileRelativeDirname = $pathInfo['dirname'];

        //Optionally remove part from the output path
        if($stripFromSlug) {
            $fileRelativeDirname = str_replace($stripFromSlug,'',$fileRelativeDirname);
        }

        if ($pathInfo['extension'] === 'json') {

            //Start an object to store the HTML
            ob_start();

            if ($relativeFilePath === '/page/index.json') {
                //Create homepage
                $this->router->handleUtilityPageRequest('/','front-page');
            } else {
                //Create default page
                $this->router->handleCollectionRequest(['path' => $fileRelativeDirname . '/' . $pathInfo['filename']]);
            }

            //Render page
            $pageHTML = ob_get_clean();

            //Write html to output file
            $outputFilePath = $this->outputDirectory . $fileRelativeDirname;
            $outputFilename = $outputFilePath . '/' . $pathInfo['filename'] . '.html';

            //Make dir
            if (!file_exists($outputFilePath)) {
                mkdir($outputFilePath);
            }
            file_put_contents($outputFilename, $pageHTML);
        }

    }

    private function syncDirectoryToBuild($path) {
        if (!file_exists($this->outputDirectory. '/' . $path)) {
            mkdir($this->outputDirectory. '/' . $path);
        }

        $searchDir = APP_ROOT . '/web/' . $path;

        foreach (new \DirectoryIterator($searchDir) as $fileInfo) {
            $name = $fileInfo->getFilename();
            if(!$fileInfo->isDot() && $fileInfo->isFile() && $name !== '.gitkeep')  {
                copy($fileInfo->getPathname(), $this->outputDirectory . '/' . $path . '/' . $name);
            }
        }
    }

    public function generate()
    {
        //Optionally create output dir
        if (!file_exists($this->outputDirectory)) {
            mkdir($this->outputDirectory);
        }

        //Empty output dir
        $this->emptyBuildFolder();

        //Create needed directories in build volder
        mkdir($this->outputDirectory. '/theme');
        mkdir($this->outputDirectory. '/post');

        //TODO: Create Sitemap.xml?

        //Scan through pages and render to HTML
        $pages = $this->getDirContents($this->contentDirectory . '/page');
        foreach ($pages as $page) {
            $this->renderContentJsonToHtml($page, '/page');
        }

        //Scan through posts and render to HTML
        $posts = $this->getDirContents($this->contentDirectory . '/post');
        foreach ($posts as $post) {
            $this->renderContentJsonToHtml($post);
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
