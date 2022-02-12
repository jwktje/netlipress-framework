<?php
require './config/netlipress.php';
require './vendor/autoload.php';

/**
 * LARAVEL MIX
 * Only run when the commit contains js or scss files
 */
$filesToRunMixFor = ['js', 'scss'];
//Get files changed in the latest commit
exec("git diff --name-only HEAD~1..HEAD", $changedFiles);
foreach ($changedFiles as $changedFile) {
    if (in_array(pathinfo($changedFile, PATHINFO_EXTENSION), $filesToRunMixFor)) {
        exec('npm run prod');
        break;
    }
}

/**
 * STATIC SITE GENERATOR
 */
$ssg = new Netlipress\StaticSite();
$ssg->generate();
