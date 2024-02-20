<?php


chdir(dirname(__DIR__));

header('Access-Control-Allow-Origin: *');



// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

//if ($_SERVER['APPLICATION_ENV'] == 'development') {
error_reporting(E_ALL ^ E_DEPRECATED ^ E_USER_DEPRECATED);
ini_set("display_errors", 1);

//}

//ini_set('date.timezone', 'Asia/Tokyo');
ini_set('date.timezone', 'Asia/Tokyo');

$siteurl = explode(".", $_SERVER['SERVER_NAME']);
// $_SERVER['SITE_URL']=$siteurl[sizeof($siteurl)-2].".".$siteurl[sizeof($siteurl)-1];


// Setup autoloading
require 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();
