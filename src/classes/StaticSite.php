<?php

namespace Netlipress;

class StaticSite
{

    private string $outputDirectory = APP_ROOT . '/build';
    private string $pagesDirectory = APP_ROOT . '/content/page';

    public function __construct()
    {
        if(getenv('URL')) {
            $urlInfo = parse_url(getenv('URL'));
            $_SERVER['SERVER_NAME'] = $urlInfo['host'];
            $_SERVER['HTTPS'] = ($urlInfo['scheme'] === 'https') ? 'on' : 'off';
        }
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

    private function renderContentJsonToHtml($jsonFileAbsolutePath) {
        //Create router
        $router = new Router();

        //Absolute to relative path and pathinfo
        $relativeFilePath = str_replace($this->pagesDirectory, '', $jsonFileAbsolutePath);
        $pathInfo = pathinfo($relativeFilePath);
        $urlPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'];

        if ($pathInfo['extension'] === 'json') {
            //Start an object to store the HTML
            ob_start();

            if ($relativeFilePath === '/index.json') {
                //Create homepage
                $router->handleUtilityPageRequest('/','front-page');
            } else {
                //Create default page
                $router->handleCollectionRequest(['path' => $urlPath]);
            }

            //TODO: Create Blog home?
            //TODO: Create Sitemap.xml?

            //Render page
            $pageHTML = ob_get_clean();

            //Write html to output file
            $outputFilePath = $this->outputDirectory . $pathInfo['dirname'];
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
        //Create output dir
        if (!file_exists($this->outputDirectory)) {
            mkdir($this->outputDirectory);
        }
        //Empty output dir
        $this->emptyBuildFolder();

        //Scan through pages and render to HTML
        $pages = $this->getDirContents($this->pagesDirectory);
        foreach ($pages as $page) {
            $this->renderContentJsonToHtml($page);
        }

        //Create theme/asset output dir
        if (!file_exists($this->outputDirectory. '/theme/dist')) {
            mkdir($this->outputDirectory. '/theme');
        }

        //Copy theme images to build directory
        $this->syncDirectoryToBuild('theme/img');
        $this->syncDirectoryToBuild('theme/dist');
    }
}
