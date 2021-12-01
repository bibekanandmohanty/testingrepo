<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
require_once "updateConfig.php";
require_once "store" . STOREAPIDIR . "componentStore.php";
require_once "Utils.php";
require_once "component.php";
