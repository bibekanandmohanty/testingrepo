<?php
error_reporting(0);
$domainUrl = (isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$domainUrl .= "://" . $_SERVER['HTTP_HOST'];
// Read XML File
$baseDIR = getcwd();
$xmlPath = $baseDIR . '/../../config.xml';
$getXMLContent = simplexml_load_file($xmlPath);
$storeConst = get_object_vars($getXMLContent->url_detail);
defined('BASE_DIR') or define('BASE_DIR', !empty($storeConst['xetool_dir']) ? $storeConst['xetool_dir'] : 'xetool');
define('DS', DIRECTORY_SEPARATOR);
defined('API_URL') or define('API_URL', !empty($storeConst['api_url']) ? $storeConst['api_url'] : '');
defined('WORKING_DIR') or define('WORKING_DIR', 'update' . DS . 'service');
defined('API_WORKING_DIR') or define('API_WORKING_DIR', API_URL . 'api/v1/');
defined('RELATIVE_PATH') or define('RELATIVE_PATH', $baseDIR);
defined('BASE_URL') or define('BASE_URL', API_URL . WORKING_DIR);
$docAbsPath = str_replace("/", DS, RELATIVE_PATH);
$baseCurrentPath = str_replace(WORKING_DIR, '', $docAbsPath);
$baseCurrentPath = rtrim($baseCurrentPath, '/');
$baseDocpath = rtrim($baseCurrentPath, BASE_DIR);
defined('ROOTABSPATH') or define('ROOTABSPATH', $baseDocpath);
defined('DESIGNERABSPATH') or define('DESIGNERABSPATH', $baseCurrentPath);
defined('PKGUPDATEABSPATH') or define('PKGUPDATEABSPATH', $baseCurrentPath . DS . 'update');
defined('ASSETS_PATH_W') or define('ASSETS_PATH_W', $baseCurrentPath . DS . 'assets' . DS);
defined('ASSETS_PATH_R') or define('ASSETS_PATH_R', API_URL . 'assets' . DS);
defined('STORE_NAME') or define('STORE_NAME', !empty($storeConst['store_directory']) ? $storeConst['store_directory'] : '');
defined('STORE_VERSION') or define('STORE_VERSION', !empty($storeConst['store_version']) ? $storeConst['store_version'] : '');
define('STOREAPIDIR', DS . strtolower(STORE_NAME) . DS . STORE_VERSION . DS);
define('STORETYPE', strtolower(STORE_NAME));
define('STRINGFORREPLACESTOREURL', "STOREURL");
define('XECONFIGJS', "config.js");
define('STRINGFORREPLACE', "XEPATH");
defined('LANGUAGE_FOLDER') or define('LANGUAGE_FOLDER', 'languages/');
defined('SETTING_FOLDER') or define('SETTING_FOLDER', 'settings/');

// Database Constants
$dom = new DomDocument();
$dom->load($xmlPath);
defined('API_DB_HOST') or define('API_DB_HOST', !empty($dom->getElementsByTagName('host')->item(0)->nodeValue) ? $dom->getElementsByTagName('host')->item(0)->nodeValue : '');
defined('API_DB_NAME') or define('API_DB_NAME', !empty($dom->getElementsByTagName('dbname')->item(0)->nodeValue) ? $dom->getElementsByTagName('dbname')->item(0)->nodeValue : '');
defined('API_DB_USER') or define('API_DB_USER', !empty($dom->getElementsByTagName('dbuser')->item(0)->nodeValue) ? $dom->getElementsByTagName('dbuser')->item(0)->nodeValue : '');
defined('API_DB_PASS') or define('API_DB_PASS', !empty($dom->getElementsByTagName('dbpass')->item(0)->nodeValue) ? $dom->getElementsByTagName('dbpass')->item(0)->nodeValue : '');
