<?php

namespace Netlipress;

class Sitemap
{
    public static function returnSitemap()
    {
        $output = APP_ROOT . PUBLIC_DIR;
        $sitemapFile = $output . '/sitemap.xml';
        $cacheTime = 3600; //in seconds

        //Check if sitemap is older than 1 hour
        if (file_exists($sitemapFile) && time() - filemtime($sitemapFile) < $cacheTime) {
            //file was generated in last hour, so return it
            self::outputSitemap();
        } else {
            //Create a fresh sitemap
            $baseUrl = home_url();
            $paths = [];
            $generator = new \Icamys\SitemapGenerator\SitemapGenerator($baseUrl, $output);

            //Get all known collections
            $collections = Router::getKnownCollections();

            //If there are blog posts, add the blog home to the sitemap
            if (!empty(self::getPathsFromDir('post'))) {
                $paths[] = self::formatPath(BLOG_HOME);
            }

            //Get paths under each collection
            foreach ($collections as $collection) {
                $paths = array_merge($paths, self::getPathsFromDir($collection));
            }

            //Add all paths to the sitemap
            foreach ($paths as $path) {
                $path = self::formatPath($path);
                $generator->addURL($path, new \DateTime());
            }

            if(!empty($paths)) {
                //Write the sitemap
                $generator->flush();
                $generator->finalize();
                self::outputSitemap();
            } else {
                exit('No content found to add to the sitemap');
            }
        }
    }

    private static function getPathsFromDir($dir)
    {
        $paths = [];
        foreach (new \DirectoryIterator(APP_ROOT . CONTENT_DIR . '/' . $dir) as $fileInfo) {
            if ($fileInfo->isDot()) continue;
            if ($fileInfo->isDir()) {
                $paths = array_merge($paths, self::getPathsFromDir($dir . '/' . $fileInfo->getBasename()));
                continue;
            } else {
                if ($fileInfo->getExtension() !== 'json') continue;
            }
            $filePath = '/' . $dir . '/' . $fileInfo->getBasename('.' . $fileInfo->getExtension());
            $paths[] = $filePath;
        }
        return $paths;
    }

    private static function formatPath($path)
    {
        /*
         * Some notes on the Router;
         * Page collection assumes no base slug.
         * Also nested collections are directories with an index file, so getting the path will append 'index'.
         * Router discards these so the sitemap should too
         */
        $pathArr = explode('/', $path);
        //Remove 'page' base slug
        if ($pathArr[1] == 'page') {
            unset($pathArr[1]);
        }
        //Remove trailing 'index'
        if (end($pathArr) == 'index') {
            array_pop($pathArr);
        }
        $filteredPath = implode('/', $pathArr);
        $filteredPath = !empty($filteredPath) ? $filteredPath : '/';
        return $filteredPath;
    }

    private static function outputSitemap()
    {
        $output = APP_ROOT . PUBLIC_DIR;
        header('Content-Type: application/xml; charset=utf-8');
        echo file_get_contents($output . '/sitemap.xml');
    }
}
