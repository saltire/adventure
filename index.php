<?php

include './app/config.php';

$rt = $_GET['rt'] ? explode('/',$_GET['rt']) : array('main');
if (!file_exists(APP_ROOT . "/pages/{$rt[0]}.php") || key($_GET) == 'main') {
 	header("Location: " . WEB_ROOT);

} else {
	include APP_ROOT . "/pages/{$rt[0]}.php";
}
