<?php
/*
 * Run this command with PHP from the commandline from the root of your project to generate a static version in a "build" directory
 */
require './config.php';
require './vendor/autoload.php';
$ssg = new Netlipress\StaticSite();
$ssg->generate();
