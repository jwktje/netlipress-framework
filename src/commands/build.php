<?php
require './config/netlipress.php';
require './vendor/autoload.php';

//Only run Laravel Mix when committing yourself, not the CMS. Use [skip-mix] if you want to also skip it on your own commit
exec(" git log -1 --pretty=%B", $commitMessage);
if(isset($commitMessage[0]) && str_contains($commitMessage[0], '[skip-mix]')) {
    exec('npm run prod');
}

//Run the static site generator
$ssg = new Netlipress\StaticSite();
$ssg->generate();
