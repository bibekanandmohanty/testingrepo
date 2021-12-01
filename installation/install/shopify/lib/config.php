<?php
//include other files
require_once("shopify.php");	
require_once("utils.php");	
require_once("shopify_api.php");	
//set default timezone : date_default_timezone_set('America/New_York');
//domains/protocol settings
$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" ? 's' : '';
//$app_domains = array('testipsstore.myshopify.com',SHOPIFYURL);//,'https://inkxefy.myshopify.com','https://inkxe.com/','https://www.inkxe.com/');
?>