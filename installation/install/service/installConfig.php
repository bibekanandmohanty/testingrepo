<?php 
$installFolderName = 'imprintnext/install/';
$queryString = explode($installFolderName, substr($_SERVER['SCRIPT_NAME'], 1));
$appname = (isset($queryString['0']) && $queryString['0'] != '') ? $queryString['0'] : '';
$documentRoot = (isset($appname) && $appname != '') ? $_SERVER['DOCUMENT_ROOT'] . "/" . $appname."/" : $_SERVER['DOCUMENT_ROOT'] . "/";
$docAbsPath = str_replace("/", DIRECTORY_SEPARATOR, $documentRoot);
$docAbsPath = str_replace("//", DIRECTORY_SEPARATOR, $docAbsPath);
// $absPath = str_replace("/", DIRECTORY_SEPARATOR, 'xetool/install/shopify/');
$absPath = str_replace("/", DIRECTORY_SEPARATOR, $installFolderName);
$rootAbsPath = $docAbsPath . $absPath;
$rootAbsPath = str_replace("//", DIRECTORY_SEPARATOR, $rootAbsPath);
$redirectPath = 'index.php';
//get pachage info json
$infoFile = $rootAbsPath."info.json";
$packageInfo = json_decode(file_get_contents($infoFile), true);
define('INSTALLPATH', $absPath);
define('DS', DIRECTORY_SEPARATOR);
define('DOCABSPATH',$docAbsPath);
define('ROOTABSPATH', $rootAbsPath);
define('REDIRECTPATH', $redirectPath);
define('PKGINFOFILE', $infoFile);
define('SETUPFOLDERNAME', "install");
define('DEFAULTXEFOLDER', "designer");
define('XECONFIGXML', "config.xml");
define('XECONFIGJS', "config.js");
define('SQLFILE', "sql/basic_database.sql");
define('STRINGFORREPLACE', "XEPATH");
define('INSTALLFOLDER', $installFolderName);
define('STORETYPE', $packageInfo['store']);
define('STOREVERSION', $packageInfo['store_version']);
define('STOREAPIVERSION', $packageInfo['store_api_ver']);
define('XEVERSION', $packageInfo['imprint_next_version']);
define('INSTALLDOMAIN', $packageInfo['registered_domain']);
define('STOREAPIDIR', DS.STORETYPE.DS.STOREAPIVERSION.DS);
define('IMAGEPATH', 'wizard/images/install_image/');
define('DEMOAPIURL', '');
define('PROFILEJSON', ROOTABSPATH."data".DS."print_methods.json");
define('PRODUCTJSON', ROOTABSPATH."data".DS."dummy_products.json");
define('DUMMYDATADIR', ROOTABSPATH."data".DS);
define('STRINGFORREPLACESTOREURL', "STOREURL");
?>