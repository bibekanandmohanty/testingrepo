<?php
error_reporting(0);
header('Access-Control-Allow-Origin: *');
require_once "Rest.inc.php";

class Component extends REST
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * A Custom json_encode
     *
     * @param $array an Array
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return Array
     */
    public function json($data)
    {
        if (is_array($data)) {
            $formatted = json_encode($data);
            print_r($this->formatJson($formatted));
        }
    }

    /**
     * A Custom json_encode
     *
     * @param $array an Array
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return Array
     */
    private function formatJson($jsonData)
    {
        $formatted = $jsonData;
        $formatted = str_replace('"{', '{', $formatted);
        $formatted = str_replace('}"', '}', $formatted);
        $formatted = str_replace('\\', '', $formatted);
        return $formatted;
    }

    /**
     * A Custom json_decode function which will remove extra comments and decode to
     * array
     *
     * @param $json    Json Code
     * @param $assoc   If return Associated Array or not
     * @param $depth   User specified recursion depth
     * @param $options Bitmask of JSON_BIGINT_AS_STRING, JSON_INVALID_UTF8_IGNORE,
     *                 JSON_INVALID_UTF8_SUBSTITUTE, JSON_OBJECT_AS_ARRAY,
     *                 JSON_THROW_ON_ERROR.
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return Array
     */
    private function json_clean_decode($json, $assoc = true)
    {
        $assoc = (empty($assoc) || $assoc == null) ? false : $assoc;
        // search and remove comments like /* */ and //
        $json = preg_replace(
            "#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#",
            '', $json
        );
        $json = json_decode($json, $assoc);
        return $json;
    }

    /**
     * A Custom json_encode
     *
     * @param $array an Array
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return Array
     */
    private function json_clean_encode($array)
    {
        if (!empty($array)) {
            return json_encode($array);
        }
        return false;
    }

    /**
     * Get dynamic read/write path for modules
     *
     * @param $mode   Read or Write
     * @param $module Module's Slug name
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return A Valid Read or Write URL
     */
    private function path($mode, $module)
    {
        $moduleFolder = strtoupper($module) . "_FOLDER";
        // If requested Directory not present then, Create that Directory
        $checkDirectory = ASSETS_PATH_W . constant($moduleFolder);
        if (!file_exists($checkDirectory)) {
            create_directory($checkDirectory);
        }
        if ($mode === 'abs') {
            return ASSETS_PATH_W . constant($moduleFolder);
        } elseif ($mode === 'read') {
            return ASSETS_PATH_R . constant($moduleFolder);
        }
    }

    /**
     * Write File
     * Writes data to the file specified in the path.
     * Creates a new file if non-existent.
     *
     * @param $path File path
     * @param $data Data to write
     * @param $mode fopen() mode (default: 'wb')
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return bool
     */
    private function write_file($path, $data, $mode = 'wb')
    {
        if (!$openFilePath = @fopen($path, $mode)) {
            return false;
        }

        flock($openFilePath, LOCK_EX);

        for ($result = $written = 0, $length = strlen($data); $written < $length; $written += $result) {
            if (($result = fwrite($openFilePath, substr($data, $written))) === false) {
                break;
            }
        }

        flock($openFilePath, LOCK_UN);
        fclose($openFilePath);

        return is_int($result);
    }

    /**
     * Read File
     * Opens the file specified in the path and returns it as a string.
     *
     * @param $file Path to file
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return File contents
     */
    private function read_file($file)
    {
        if (file_exists($file)) {
            chmod($file, 0755);
            return @file_get_contents($file);
        }
    }

    /**
     *GET: Simple URl call through curl
     *
     * @param $endpoint API endpoint
     *
     * @author robert@imprintnext.com
     * @date  05 Oct 2020
     * @return Array
     */
    private function call_simple_curl($endpoint, $params)
    {
        $url = $endpoint;
        if (!empty($params) && is_array($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, array());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $data = json_decode($result, true);
    }

    /**
     * Get Check ajax call
     *
     * @param nothing
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return boolean
     */
    private function is_ajax_request()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? true : false === 'XMLHttpRequest';
    }

    /**
     * Get license key
     *
     * @param $return  Boolean value
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return Json Array
     */
    public function getLicenseKey($return = false)
    {
        $licenceKey = '';
        $response = array();
        $configJsFile = DESIGNERABSPATH . DS . 'config.js';
        if (file_exists($configJsFile)) {
            $strconfigJsFile = $this->read_file($configJsFile);
            $tempArr = explode(',', $strconfigJsFile);
            foreach ($tempArr as $key => $value) {
                if (strpos($value, 'LICENCE_KEY') !== false) {
                    $newStr = preg_replace("/\s+/", "", $value);
                    $str = str_replace('LICENCE_KEY=', '', $newStr);
                    $removeSpaceStr = ltrim($str);
                    $licenceKey = substr($removeSpaceStr, 1, -1);
                }
            }
            if ($licenceKey != '') {
                $response = array('status' => 1, 'licence_key' => $licenceKey);
            } else {
                $response = array('status' => 0, 'licence_key' => '');
            }
        } else {
            $response = array('status' => 0, 'licence_key' => '');
        }
        if ($return) {
            return $response;
        } else {
            $this->json($response);
        }
    }
    /**
     *GET: Latest version
     *
     * @param $current_version Current version
     *
     * @author robert@imprintnext.com
     * @date  05 Oct 2020
     * @return Json Array
     */
    public function getLatestVersion()
    {
        $response = [];
        if (isset($this->_request) && $this->_request['current_version'] != '') {
            $param['current_version'] = $this->_request['current_version'];
            $endpoint = API_WORKING_DIR . "latest-version";
            $response = $this->call_simple_curl($endpoint, $param);
        } else {
            $response = array('status' => 0, 'message' => 'Invalid data');
        }
        $this->json($response);
    }

    /**
     *GET: All admin languages
     *
     * @param nothing
     *
     * @author robert@imprintnext.com
     * @date  05 Oct 2020
     * @return Json Array
     */
    public function getLanguages()
    {
        $response = [];
        $param = [];
        $endpoint = API_WORKING_DIR . "admin-language";
        $response = $this->call_simple_curl($endpoint, $param);
        $this->json($response);
    }

    /**
     * Update all related files
     *
     * @param $zip_file  ZIP file
     * @param $current_version  Current version
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return Json Array
     */
    public function updatePackage()
    {
        $licenceKey = '';
        $response = array();
        if (isset($_FILES["zip_file"]["name"]) && isset($_POST['current_version']) && $this->is_ajax_request()) {
            $currentVersion = $_POST['current_version'];
            $filename = $_FILES["zip_file"]["name"];
            $source = $_FILES["zip_file"]["tmp_name"];
            $type = $_FILES["zip_file"]["type"];

            $name = explode(".", $filename);
            $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
            foreach ($accepted_types as $mime_type) {
                if ($mime_type == $type) {
                    $okay = true;
                    break;
                }
            }
            $continue = strtolower($name[1]) == 'zip' ? true : false;
            if (!$continue) {
                $message = "The file you are trying to upload is not a .zip file. Please try again.";
                $response = array('data' => $message, 'status' => 0, 'licence_key' => $licenceKey);
            } else {
                /* PHP current path */
                $path = PKGUPDATEABSPATH . DS . 'extractZip' . DS; // absolute path to the directory where zipper.php is in
                if (!is_dir($path)) {
                    mkdir($path);
                    chmod($path, 0755);
                }
                $filenoext = basename($filename, '.zip'); // absolute path to the directory where zipper.php is in (lowercase)
                $filenoext = basename($filenoext, '.ZIP'); // absolute path to the directory where zipper.php is in (when uppercase)

                $targetdir = $path . $filenoext; // target directory
                $targetzip = $path . $filename; // target zip file

                /* create directory if not exists', otherwise overwrite */
                /* target directory is same as filename without extension */

                if (is_dir($targetdir)) {
                    $this->rmdir_recursive($targetdir);
                }

                mkdir($targetdir, 0777);

                /* Upload .zip folder */
                if (move_uploaded_file($source, $targetzip)) {
                    $zip = new ZipArchive();
                    $x = $zip->open($targetzip); // open the zip file to extract
                    if ($x === true) {

                        $zip->extractTo($targetdir); // place in the directory with same name
                        $zip->close();
                        unlink($targetzip);
                        $imprintDir = $targetdir . DS . 'imprintnext' . DS;
                        $newPackageDesignerDir = $imprintDir . 'designer';
                        $pkgInstallationDir = $imprintDir . 'install';
                        if (is_dir($newPackageDesignerDir) && is_dir($pkgInstallationDir)) {
                            $licenseKeyArr = $this->getLicenseKey(true);
                            if (!empty($licenseKeyArr)) {
                                $licenceKey = $licenseKeyArr['licence_key'];
                            }
                            $pkgInstallationSQLDir = $pkgInstallationDir . DS . 'sql';
                            $newPackageDesignerAssetDir = $newPackageDesignerDir . DS . 'assets';
                            $this->startUpdate(DESIGNERABSPATH, $newPackageDesignerDir);
                            $this->updateAllJsFile(DESIGNERABSPATH);
                            $this->updateSqlQuery($pkgInstallationSQLDir, $currentVersion);
                            $this->updateUnit();
                            $this->updateAdvanceSettings();
                            $this->updateAssets($newPackageDesignerAssetDir . DS);
                            $this->updateSettingsOthers();
                            $this->copyStoreFile($pkgInstallationDir . DS);
                            $message = "Your .zip file was uploaded and unpacked.";
                            $response = array('data' => $message, 'status' => 1, 'licence_key' => $licenceKey);
                        } else {
                            $message = "Invalid uploaded package zip file. Please try again.";
                            $response = array('data' => $message, 'status' => 0, 'licence_key' => $licenceKey);
                        }
                        $this->delete_directory($targetdir);
                    }
                } else {
                    $message = "There was a problem with the upload. Please try again.";
                    $response = array('data' => $message, 'status' => 0, 'licence_key' => $licenceKey);
                }
            }
        } else {
            $response = array('data' => 'Invalid Data', 'status' => 0, 'licence_key' => $licenceKey);
        }
        $this->json($response);
    }

    /**
     * Automate Language and Setting File
     *
     * @param $modifiedAssetsPath  Modified Assets Path patha
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return boolean
     */
    private function updateAssets($modifiedAssetsPath)
    {
        $newAssetsPath = scandir($modifiedAssetsPath);
        if (!empty($newAssetsPath)) {
            foreach ($newAssetsPath as $newFolderKey => $newFolderValue) {
                if ($newFolderValue != '.' && $newFolderValue != '..') {
                    if ($newFolderValue == 'languages') {
                        $newLanguagePath = $modifiedAssetsPath . 'languages/';
                        $newLanguageFolders = scandir($newLanguagePath);
                        $oldLanguageFolders = scandir($this->path('abs', 'language'));
                        if (!empty($newLanguageFolders)) {
                            $diffFolders = array_diff($newLanguageFolders, $oldLanguageFolders);
                            if (!empty($diffFolders)) {
                                foreach ($diffFolders as $diffKey => $languageDiff) {
                                    unset($newLanguageFolders[$diffKey]);
                                    copy($newLanguagePath . $languageDiff, $this->path('abs', 'language') . $languageDiff);
                                }
                            }
                            foreach ($newLanguageFolders as $langFolderLey => $folder) {
                                if (!is_dir($folder)) {
                                    $oldLanguageFiles = scandir($this->path('abs', 'language') . $folder);
                                    $newLanguageFiles = scandir($newLanguagePath . $folder);
                                    if (!empty($newLanguageFiles)) {
                                        $newFileArray = array_diff($newLanguageFiles, $oldLanguageFiles);
                                        foreach ($newFileArray as $fileKey => $files) {
                                            unset($newLanguageFiles[$fileKey]);
                                            copy($newLanguagePath . $folder . '/' . $files, $this->path('abs', 'language') . $folder . '/' . $files);
                                        }
                                        foreach ($newLanguageFiles as $newFileName) {
                                            if (strpos($newFileName, ".json") !== false) {
                                                $oldContent = $this->read_file($this->path('abs', 'language') . $folder . '/' . $newFileName);
                                                $newContent = $this->read_file($newLanguagePath . $folder . '/' . $newFileName);
                                                $oldArrayContent = $this->json_clean_decode($oldContent, true);
                                                $newArrayContent = $this->json_clean_decode($newContent, true);
                                                if ($folder == 'admin') {
                                                    foreach ($oldArrayContent as $oldLangKey => $langValue) {
                                                        foreach ($langValue as $key => $value) {
                                                            if (strpos($value, '<p>') !== false || strpos($value, '</p>') !== false) {
                                                                $value = str_replace('</p>', '', $value);
                                                                $value = str_replace("<p class='mb-1'>", '<p>', $value);
                                                                $tagArray = explode('<p>', $value);
                                                                foreach ($tagArray as $tagKey => $tagValue) {
                                                                    if (!empty($tagValue)) {
                                                                        if ($tagKey == 1) {
                                                                            $oldArrayContent[$oldLangKey][$key] = $tagValue;
                                                                        } else {
                                                                            $oldArrayContent[$oldLangKey][$key . $tagKey] = $tagValue;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                foreach ($newArrayContent as $newLangKey => $langValue) {
                                                    if (!isset($oldArrayContent[$newLangKey])) {
                                                        $oldArrayContent[$newLangKey] = $newArrayContent[$newLangKey];
                                                    } else {
                                                        $diffArrayValue = array_diff_key($newArrayContent[$newLangKey], $oldArrayContent[$newLangKey]);
                                                        foreach ($diffArrayValue as $newKey => $newValue) {
                                                            $oldArrayContent[$newLangKey][$newKey] = $newValue;
                                                        }
                                                    }
                                                }
                                                $finalContent = $this->json_clean_encode($oldArrayContent, true);
                                                $this->write_file($this->path('abs', 'language') . $folder . '/' . $newFileName, $finalContent);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($newFolderValue == 'settings') {
                        $newSettingPath = $modifiedAssetsPath . 'settings/stores/1';
                        $newSettingFolders = scandir($newSettingPath);
                        $oldSettingFolders = scandir($this->path('abs', 'setting') . 'stores/1');

                        if (!empty($newSettingFolders)) {
                            foreach ($newSettingFolders as $settingFolderKey => $settingsFile) {
                                if (strpos($settingsFile, "settings.json") !== false) {
                                    $oldSettingsContent = $this->read_file($this->path('abs', 'setting') . 'stores/1/' . $settingsFile);
                                    $newSettingsContent = $this->read_file($newSettingPath . '/' . $settingsFile);
                                    $oldSettingsArray = $this->json_clean_decode($oldSettingsContent, true);
                                    $newSettingsArray = $this->json_clean_decode($newSettingsContent, true);
                                    foreach ($newSettingsArray as $newSettingsKey => $settingsValue) {
                                        if (is_array($newSettingsArray[$newSettingsKey])) {
                                            foreach ($settingsValue as $innerKey => $innerValue) {
                                                if (!isset($oldSettingsArray[$newSettingsKey][$innerKey])) {
                                                    $oldSettingsArray[$newSettingsKey][$innerKey] = $newSettingsArray[$newSettingsKey][$innerKey];
                                                }
                                            }
                                        } else {
                                            if (!isset($oldSettingsArray[$newSettingsKey])) {
                                                $oldSettingsArray[$newSettingsKey] = $newSettingsArray[$newSettingsKey];
                                            }
                                        }
                                    }
                                    $finalContent = $this->json_clean_encode($oldSettingsArray, true);
                                    $this->write_file($this->path('abs', 'setting') . 'stores/1/' . $settingsFile, $finalContent);
                                }
                                if (strpos($settingsFile, "currencies.json") !== false) {
                                    $newCurrencyContent = $this->read_file($newSettingPath . '/' . $settingsFile);
                                    $newCurrencyArray = $this->json_clean_decode($newCurrencyContent, true);
                                    $this->write_file($this->path('abs', 'setting') . 'stores/1/' . $settingsFile, $newCurrencyContent);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Update all related files and folder in store
     *
     * @param $newImprintStorePath  Update package path
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function copyStoreFile($newImprintStorePath)
    {
        $designerPath = ROOTABSPATH . BASE_DIR;
        $newFolder = 'designer';
        if (STORETYPE == 'magento') {
            if (STORE_VERSION == 'v1x') {
                $this->recurse_copy($newImprintStorePath . 'magento/1.X', ROOTABSPATH);
            } else {
                foreach (glob($newImprintStorePath . 'magento/2.X/app/code/ImprintNext/Cedapi/Observer/*') as $path_to_observer_file) {
                    $observer_contents = file_get_contents($path_to_observer_file);
                    $observer_contents = str_replace("xetool", $newFolder, $observer_contents);
                    file_put_contents($path_to_observer_file, $observer_contents);
                }
                foreach (glob($newImprintStorePath . 'magento/2.X/app/code/ImprintNext/Cedapi/Plugin/*') as $path_to_plugin_file) {
                    $plugin_contents = file_get_contents($path_to_plugin_file);
                    $plugin_contents = str_replace("xetool", $newFolder, $plugin_contents);
                    file_put_contents($path_to_plugin_file, $plugin_contents);
                }
                foreach (glob($newImprintStorePath . 'magento/2.X/app/code/ImprintNext/Cedapi/view/frontend/templates/cart/item/*') as $path_to_cart_file) {
                    $cart_contents = file_get_contents($path_to_cart_file);
                    $cart_contents = str_replace("xetool", $newFolder, $cart_contents);
                    file_put_contents($path_to_cart_file, $cart_contents);
                }
                foreach (glob($newImprintStorePath . 'magento/2.X/app/code/ImprintNext/Cedapi/view/frontend/templates/product/view/*') as $path_to_product_file) {
                    $product_contents = file_get_contents($path_to_product_file);
                    $product_contents = str_replace("xetool", $newFolder, $product_contents);
                    file_put_contents($path_to_product_file, $product_contents);
                }
                $this->recurse_copy($newImprintStorePath . 'magento/2.X', ROOTABSPATH);
            }
        }
        if (STORETYPE == 'prestashop') {
            $this->copyStoreThemeFiles($newImprintStorePath);
        }
        if (STORETYPE == 'shopify') {
            if (!file_exists($newImprintStorePath . "shopify")) {
                mkdir($newImprintStorePath . 'shopify', 0755, true);
            }

            $this->recurse_copy($newImprintStorePath . "shopify", $designerPath . "/shopify");
        }
        if (STORETYPE == 'woocommerce') {
            $this->copyPluginfiles($newImprintStorePath . "woocommerce" . DS);
        }
    }

    /**
     * Update SQL query
     *
     * @param $sqlDIR  SQL file directory
     * @param $version Latest version
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function updateSqlQuery($sqlDIR, $version)
    {
        $version = str_replace(".","",$version);
        $version = intval($version);
        if (is_dir($sqlDIR) && $version > 0) {
            $sqlFiles = scandir($sqlDIR);
            foreach ($sqlFiles as $file) {
                if ($file != '.' && $file != '..' && $file != 'basic_database.sql') {
                    $tmp = explode('_', $file);
                    $vr = intval($tmp[0]);
                    if ($vr > $version) {
                        $fileName = $sqlDIR . DS . $file;
                        $dbs = $this->run_sql_file($fileName);
                    }
                }
            }
        }
    }

    /**
     * Run SQL query
     *
     * @param $filename  SQL file name
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function run_sql_file($filename)
    {
        $errorMsg = "";
        $connectionError = 0;
        try {
            $conn = new mysqli(API_DB_HOST, API_DB_USER, API_DB_PASS, API_DB_NAME);
            if ($conn->connect_error) {
                $error = "Invalid data base details" . "\n";
                $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $error);
            }
        } catch (Exception $e) {
            $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
        }
        if ($connectionError == 0) {
            $commands = @file_get_contents($filename); //load file
            $sqlStatus = $conn->multi_query($commands); //run sql file
            if ($sqlStatus == false) {
                $errorMsg = "\n" . 'Following SQL queries failed to run:' . "\n" . $commands;
                $this->xe_log($errorMsg);
                $status = 0;
            } else {
                return true;
            }
        }
    }

    /**
     * Get base URL
     *
     * @param nothing
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return base url
     */
    private function getBaseUrl()
    {
        $updateFolderName = BASE_DIR . DS . 'update' . DS;
        $path2 = $_SERVER['PHP_SELF'];
        $path = explode($updateFolderName, $path2);
        $path = $path['0'];
        $appname = str_ireplace($updateFolderName, '', $path);
        $appname = substr($appname, 1);

        $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != 'off') ? 'https' : 'http';
        $hostname = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/';
        $base_url = (strlen($appname)) ? $hostname . $appname : $hostname;
        return array($appname, $base_url);
    }

    /**
     * Update all tools JS files
     *
     * @param $toolDIR  Tool directory
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function updateAllJsFile($toolDIR)
    {
        $storeUrl = str_replace(BASE_DIR . DS, '', API_URL);
        $clientAppConfig = $toolDIR . DS . XECONFIGJS;
        if (file_exists($clientAppConfig)) {
            @chmod($clientAppConfig, 0777);
            $settingStr = @file_get_contents($clientAppConfig);
            $settingStr = str_replace(STRINGFORREPLACE, API_URL, $settingStr);
            $settingStr = str_replace(STRINGFORREPLACESTOREURL, $storeUrl, $settingStr);
            @file_put_contents($clientAppConfig, $settingStr);
            // check if XEPATH properly written or not //
            $settingStrCheckStr = @file_get_contents($clientAppConfig);
            if (strpos($settingStrCheckStr, STRINGFORREPLACE) !== false) {
                $errorMsg .= '- Base URL not written properly in config.js \n';
                $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $errorMsg);
            }
        }
        // put base URL in admin main js
        $adminJSdir = $toolDIR . DS . "admin";
        $adminFiles = scandir($adminJSdir);
        foreach ($adminFiles as $file) {
            if (strpos($file, "main") === 0) {
                $fileContent = $newContent = "";
                $thisFile = $adminJSdir . DS . $file;
                $fileContent = file_get_contents($thisFile);
                $newContent = str_replace("BASEURL/", API_URL, $fileContent);
                $fileWrite = file_put_contents($thisFile, $newContent);
            }
        }
        // put base URL in quotation main js
        $quoteJSdir = $toolDIR. DS ."quotation";
        $quoteFiles = scandir($quoteJSdir);
        foreach ($quoteFiles as $file) {
            if (strpos($file, "main") === 0) {
                $fileContent = $newContent = "";
                $thisFile = $quoteJSdir.DS.$file;
                $fileContent = file_get_contents($thisFile);
                $newContent = str_replace("BASEURL/", API_URL, $fileContent);
                $fileWrite = file_put_contents($thisFile, $newContent);
            }
        }
    }

    /**
     * Remove all files and folder
     *
     * @param $dir  Directory
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function rmdir_recursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$dir/$file")) {
                $this->rmdir_recursive("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        rmdir($dir);
    }

    /**
     * Update all tools files and folder
     *
     * @param $oldDesigner  Old tool directory
     * @param $newPackage  New package directory
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function startUpdate($oldDesigner, $newPackage)
    {
        $today = date("d-m-Y");
        $time = date("H-i-s");
        $dateTime = $today . "_" . $time;
        //Start rename old folders and files
        if (is_dir($oldDesigner)) {
            $scanOldDesignerDir = scandir($oldDesigner);
            if (is_array($scanOldDesignerDir)) {
                foreach ($scanOldDesignerDir as $dir) {
                    if ($dir != '.' && $dir != '..' && $dir != 'config.xml' && $dir != 'assets' && $dir != '.htaccess' && $dir != 'update' && $dir != 'custom.css') {
                        $oldDesignerPath = $oldDesigner . DS . $dir;
                        $oldDesignerPathRename = $oldDesigner . DS . $dir . '_' . $dateTime;
                        if (is_dir($oldDesignerPath) || file_exists($oldDesignerPath)) {
                            if (strpos($oldDesignerPath, '_') === false) {
                                rename($oldDesignerPath, $oldDesignerPathRename);
                            }
                        }
                    }
                }
            }
        }

        //Copy new folders and files
        if (is_dir($newPackage)) {
            $scanNewDesignerDir = scandir($newPackage);
            if (is_array($scanNewDesignerDir)) {
                foreach ($scanNewDesignerDir as $newDir) {
                    if ($newDir != '.' && $newDir != '..' && $newDir != 'config.xml' && $newDir != 'assets' && $newDir != '.htaccess' && $newDir != 'update' && $newDir != 'custom.css') {
                        $newDesignerPath = $newPackage . DS . $newDir;
                        $newDesignerPathRename = $oldDesigner . DS . $newDir;
                        if (is_dir($newDesignerPath)) {
                            $this->custom_copy($newDesignerPath, $newDesignerPathRename);
                        }
                        if (file_exists($newDesignerPath)) {
                            @copy($newDesignerPath, $newDesignerPathRename);
                        }
                    }
                }
            }
        }

        //Update current theme color code
        $this->getOldThemeColorCode($dateTime);
    }

    /**
     * Copy all files
     *
     * @param $dir  Directory
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function custom_copy($src, $dst)
    {
        // open the source directory
        $dir = opendir($src);
        // Make the destination directory if not exist
        @mkdir($dst);
        // Loop through the files in source directory
        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    // Recursively calling custom copy function
                    // for sub directory
                    $this->custom_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Remove all files and folder
     *
     * @param $dir  Directory
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        // If the requested path is a file, then delete that file with dedicated function
        if (is_file($dir)) {
            return $this->delete_file($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->delete_directory($dir . DS . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    /**
     * Delete all files
     *
     * @param $dir  Directory
     *
     * @author robert@imprintnext.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function delete_file($location)
    {
        // Path relative to where the php file is or absolute server path
        if (file_exists($location)) {
            // Comment this out if you are on the same folder
            // chdir($location);
            //Insert an Invalid UserId to set to Nobody Owner; for instance 465
            // chown($location, 465);
            if (unlink($location)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update default unit
     *
     * @param Nothing
     *
     * @author robert@imprintnext.com
     * @date   15 Oct 2020
     * @return nothing
     */
    private function updateUnit()
    {
        $settingJson = $this->path('abs', 'setting') . "stores/1/settings.json";
        $settingContent = $this->read_file($settingJson);
        $seetingsArr = json_decode($settingContent, true);
        $defaultUnit = $seetingsArr['unit'] ? $seetingsArr['unit'] : 'Inch';
        try {
            $conn = new mysqli(API_DB_HOST, API_DB_USER, API_DB_PASS, API_DB_NAME);
            if ($conn->connect_error) {
                $error = "Invalid data base details" . "\n";
                $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $error);
            }
        } catch (Exception $e) {
            $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
        }
        $query = "SELECT is_default FROM app_units WHERE name='" . $defaultUnit . "'";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            $appDefaultUnitsSql = "UPDATE app_units SET is_default=0";
            $conn->query($appDefaultUnitsSql);
            $appUnitsSql = "UPDATE app_units SET is_default=1 WHERE name = '" . $defaultUnit . "'";
            $conn->query($appUnitsSql);
        } else {
            $defaultUnitName = $seetingsArr['unit_name'] ? $seetingsArr['unit_name'] : 'Inch';
            $sql = "SELECT is_default FROM app_units WHERE name='" . $defaultUnitName . "'";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                $appDefaultUnitsSql = "UPDATE app_units SET is_default=0";
                $conn->query($appDefaultUnitsSql);
                $appUnitsSql = "UPDATE app_units SET is_default=1 WHERE name = '" . $defaultUnitName . "'";
                $conn->query($appUnitsSql);
            }
        }
        $conn->close();
    }

    /**
     * Update advance settings
     *
     * @param Nothing
     *
     * @author robert@imprintnext.com
     * @date   15 Oct 2020
     * @return nothing
     */
    private function updateAdvanceSettings()
    {
        $settingJson = $this->path('abs', 'setting') . "stores/1/settings.json";
        $settingContent = $this->read_file($settingJson);
        $seetingsArr = json_decode($settingContent, true);
        $advanceSettings = $seetingsArr['advance_settings'];
        try {
            $conn = new mysqli(API_DB_HOST, API_DB_USER, API_DB_PASS, API_DB_NAME);
            if ($conn->connect_error) {
                $error = "Invalid data base details" . "\n";
                $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $error);
            }
        } catch (Exception $e) {
            $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
        }
        $settingSql = "SELECT setting_value FROM settings WHERE setting_key='advance_settings'";
        $settingValues = $conn->query($settingSql);
        $row = mysqli_fetch_array($settingValues);
        $settingValue = $row['setting_value'];
        $settingValue = json_decode($settingValue, true);
        $advanceSettings['social_share'] = $settingValue['social_share'];
        $advanceSettings = json_encode($advanceSettings);
        if (!empty($settingValue) && $advanceSettings != '') {
            $settingsUpdateSql = "UPDATE settings SET setting_value = '" . $advanceSettings . "' WHERE setting_key='advance_settings'";
            $conn->query($settingsUpdateSql);
            $conn->close();
        }
    }

    /**
     * Get old theme color from chunk file
     *
     * @param $oldThemeColorCode      Theme Color from settingsthemeHoverColor
     * @param $themeHoverColorCode Theme Hover Color from settings
     * @param $themeBackgroundColor Theme Background Color from settings
     *
     * @author robert@imprintnext.com
     * @date  15 Oct 2020
     * @return Nothing
     */
    private function getOldThemeColorCode($dateTime)
    {
        $themeBackgroundColor = $themeHoverColorCode = $oldThemeColorCode = '';
        $oldStaicFolder = ROOTABSPATH . BASE_DIR . DS . 'static_' . $dateTime . DS . 'css';
        if (is_dir($oldStaicFolder)) {
            $fileNames = scandir($oldStaicFolder);
            foreach ($fileNames as $fileName) {
                list($firstWord) = explode('.', $fileName);
                if ($firstWord == "main") {
                    $filePath = $oldStaicFolder . '/' . $fileName;
                    $fileContents = file_get_contents($filePath);
                    $colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.imageWrap-box:hover .nf{color:')), ';}'));
                    $oldThemeColorCode = $colorData[1];
                    $themeHoverColor = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.dropdown-item.active{background-color:')), ';}'));
                    $themeHoverColorCode = $themeHoverColor[1];
                    $themeBackground = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.btn-success:focus,.btn-success:hover{background-color:')), ';}'));
                    $themeBackgroundColor = $themeBackground[1];
                }
            }
            $this->updateThemeColorCode($oldThemeColorCode, $themeHoverColorCode, $themeBackgroundColor);
        }
    }

    /**
     * Change theme color in chunk file
     *
     * @param $oldThemeColorCode      Theme Color from settingsthemeHoverColor
     * @param $themeHoverColorCode Theme Hover Color from settings
     * @param $themeBackgroundColor Theme Background Color from settings
     *
     * @author robert@imprintnext.com
     * @date  15 Oct 2020
     * @return Nothing
     */
    private function updateThemeColorCode($themeColor, $themeHoverColor, $themeBackgroundColor)
    {
        $staticFolderPath = ROOTABSPATH . BASE_DIR . DS . 'static' . DS . 'css';
        if (is_dir($staticFolderPath)) {
            $fileNames = scandir($staticFolderPath);
            foreach ($fileNames as $fileName) {
                list($firstWord) = explode('.', $fileName);
                if ($firstWord == "main") {
                    $filePath = $staticFolderPath . '/' . $fileName;
                    $fileContents = file_get_contents($filePath);
                    if ($themeColor != "") {
                        $colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.imageWrap-box:hover .nf{color:')), ';}'));
                        $colorCode = $colorData[1];
                        $fileContents = str_replace($colorCode, $themeColor, $fileContents);
                        file_put_contents($filePath, $fileContents);
                    }
                    if ($themeHoverColor != "") {
                        $colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.dropdown-item.active{background-color:')), ';}'));
                        $colorCode = $colorData[1];
                        $fileContents = str_replace($colorCode, $themeHoverColor, $fileContents);
                        file_put_contents($filePath, $fileContents);
                    }
                    if ($themeBackgroundColor != "") {
                        $colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.btn-success:focus,.btn-success:hover{background-color:')), ';}'));
                        $colorCode = $colorData[1];
                        $fileContents = str_replace($colorCode, $themeBackgroundColor, $fileContents);
                        file_put_contents($filePath, $fileContents);
                    }
                }
            }
        }
    }
    
    /**
     * Update smtp and email settings
     *
     * @param Nothing
     *
     * @author robert@imprintnext.com
     * @date   18 jan 2021
     * @return nothing
     */
    private function updateSettingsOthers()
    {
        $settingJson = $this->path('abs', 'setting') . "stores/1/settings.json";
        $settingContent = $this->read_file($settingJson);
        $seetingsArr = json_decode($settingContent, true);
        $emailSettings = $seetingsArr['email_address_details'];
        $smtpSettings = $seetingsArr['smtp_details'];
        if (empty($emailSettings)) {
            try {
                $conn = new mysqli(API_DB_HOST, API_DB_USER, API_DB_PASS, API_DB_NAME);
                if ($conn->connect_error) {
                    $error = "Invalid data base details" . "\n";
                    $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $error);
                }
            } catch (Exception $e) {
                $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
            }
            $settingSql = "SELECT setting_value FROM settings WHERE setting_key='email_address_details'";
            $settingValues = $conn->query($settingSql);
            $row = mysqli_fetch_array($settingValues);
            if (!empty($row)) {
                $seetingsArr['smpt_email_details']['email_address_details'] = json_decode($row['setting_value'], true);
                file_put_contents($settingJson, json_encode($seetingsArr));
            }
        }

        if (empty($smtpSettings)) {
            try {
                $conn = new mysqli(API_DB_HOST, API_DB_USER, API_DB_PASS, API_DB_NAME);
                if ($conn->connect_error) {
                    $error = "Invalid data base details" . "\n";
                    $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $error);
                }
            } catch (Exception $e) {
                $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
            }
            $settingSql = "SELECT setting_value FROM settings WHERE setting_key='smtp_details'";
            $settingValues = $conn->query($settingSql);
            $row = mysqli_fetch_array($settingValues);
            if (!empty($row)) {
                $seetingsArr['smpt_email_details']['smtp_details'] = json_decode($row['setting_value'], true);
                file_put_contents($settingJson, json_encode($seetingsArr));
            }
        }
    }

}
$api = new Component;
$api->processApi();
