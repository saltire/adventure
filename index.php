<?php

date_default_timezone_set('America/Toronto');
error_reporting(E_ALL);

session_set_cookie_params(604800); // 1 week lifetime
session_name('advsession');

// app root
$approot = __DIR__ . "/app";

// get route from URL passed
$route = array_key_exists('rt', $_GET) ? "{$_GET['rt']}" : '';
$args = explode('/', $route);
$page = $args[0] ?: 'main';

// web root
$uri = preg_replace('`[^/]+\.php$`', '', rawurldecode("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"));
$root = $route ? substr($uri, 0, strpos($uri, $route)) : $uri;
$webroot = rtrim($root, '/');

if (!file_exists("$approot/pages/$page.php")) {
 	//header("Location: $webroot");

} else {
	include "$approot/pages/$page.php";
}
