<?php


$_SERVER["SERVER_PORT"] = "443";
require_once __DIR__ . "/config.php";
require_once DIR_SYSTEM . "startup.php";
$registry = new Registry();
$config = new Config();
$registry->set("config", $config);
$event = new Event($registry);
$registry->set("event", $event);
$loader = new Loader($registry);
$registry->set("load", $loader);
$registry->set("request", new Request());
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT);
$registry->set("db", $db);
$task = "catalog/simplepars/cronstart";
$loader->controller($task);

?>