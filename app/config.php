<?php

define('APP_ROOT', dirname(__FILE__));
$uri = rawurldecode("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
$root = $_GET['rt'] ? substr($uri, 0, strpos($uri, $_GET['rt'])) : $uri;
define('WEB_ROOT', rtrim($root, '/'));

date_default_timezone_set('America/Toronto');

session_set_cookie_params(604800); // 1 week lifetime
session_name('advsession');


function __autoload($class) {
	if (!class_exists($class, 0)) {
		include APP_ROOT . "/classes/" . strtolower(substr($class, 9)) . ".php";
	}
}
