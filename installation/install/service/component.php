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

    public function processApi()
    {
        $func = '';
        if (isset($_REQUEST['service'])) {
            $func = strtolower(trim(str_replace("/", "", $_REQUEST['service'])));
        } else if (isset($_REQUEST['reqmethod'])) {
            $func = strtolower(trim(str_replace("/", "", $_REQUEST['reqmethod'])));
        }

        if ($func) {
            if (method_exists($this, $func)) {
                $this->$func();
            } else {
                $this->log('invalid service:' . $func, true, 'log_invalid.txt');
                $this->response('invalid service', 406);
            }
        }
    }

    public function json($data)
    {
        if (is_array($data)) {
            $formatted = json_encode($data);
            print_r($this->formatJson($formatted));
        }
    }

    private function formatJson($jsonData)
    {
        $formatted = $jsonData;
        $formatted = str_replace('"{', '{', $formatted);
        $formatted = str_replace('}"', '}', $formatted);
        $formatted = str_replace('\\', '', $formatted);
        return $formatted;
    }
    /**
     * Getting information about the related domain, store, version of xetool package.
     * This will also check if the package has a info file mentioning the details given above.
     * @author: riaxe.com
     * @date: 03 Jan 2020
     * @input: None
     * @return: The total data that was fetched along with message code for languages
     */
    public function getPackageInfo()
    {
        //get version details
        $packageInfoFile = PKGINFOFILE;
        $packageInfo = array();
        $response = array();
        if (file_exists($packageInfoFile)) {
            $packageInfo["store"] = STORETYPE;
            $packageInfo["store_version"] = STOREVERSION;
            $packageInfo["inkXE_version"] = XEVERSION;
            $packageInfo["registered_domain"] = INSTALLDOMAIN;

            $messageCode = "PACKAGE_VERSION_DETAILS";
            $proceedNext = true;
        } else {
            $messageCode = "INVALID_PACKAGE";
            $proceedNext = false;
            $response = array('message_code' => $messageCode, 'proceed_next' => $proceedNext);
            $this->json($response);exit();
        }
        //check domain with license
        if (strpos($_SERVER['HTTP_HOST'], INSTALLDOMAIN) !== false) {
            $response = array('data' => $packageInfo, 'message_code' => $messageCode, 'proceed_next' => $proceedNext);
            $this->json($response);
        } else {
            $messageCode = "DOMAIN_PACKAGE_MISMATCH";
            $proceedNext = false;
            $response = array('data' => $packageInfo, 'message_code' => $messageCode, 'proceed_next' => $proceedNext);
            $this->json($response);
        }

    }
    /**
     * Getting list of languages available for installation.
     *
     * @author: riaxe.com
     * @date: 03 Jan 2020
     * @input: None
     * @return: Available list of languages
     */
    public function getLanguages()
    {
        $languageDIR = ROOTABSPATH . "languages/";
        $langFiles = scandir($languageDIR);
        $languages = array();
        foreach ($langFiles as $lang) {
            if ($lang != "." && $lang != "..") {
                $thisLang = array();
                $language = explode("_", substr($lang, 0, strpos($lang, ".")));
                $thisLang['name'] = ucfirst($language[1]) . " (" . $language[2] . ")";
                $thisLang['file'] = $lang;
                $languages['languages'][] = $thisLang;
            }
        }
        $this->json($languages);
    }
    /**
     * Saving Selected language and chosen xetool root folder for future usage.
     * It will create an XML file to write up the content in install package and transfer to parent DIR in 2nd step..
     * @author: riaxe.com
     * @date: 03 Jan 2020
     * @input: None
     * @return: status and message code for xetool root directory check
     */
    public function saveInstallationSettings()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $selectedLanguage = $this->_request['lang'];
            $designerFolder = (isset($this->_request['root']) && $this->_request['root'] != '') ? $this->_request['root'] : '';
            $response = array();
            if (!isset($designerFolder) || $designerFolder == '') {
                $designerFolder = DEFAULTXEFOLDER;
            }
            //set language in imprint_details.xml
            if (is_dir(DOCABSPATH . $designerFolder)) {
                $messageCode = "FOLDER_ALREADY_EXISTS";
                $proceedNext = false;
                $response = array('message_code' => $messageCode, 'proceed_next' => $proceedNext);
                $this->json($response);
            } else {
                $inkXEpkgDIR = str_replace(SETUPFOLDERNAME . DS, '', ROOTABSPATH);
                if (!is_writable($inkXEpkgDIR)) {
                    chmod($inkXEpkgDIR, 0755);
                }
                $file = $inkXEpkgDIR . "imprint_details.json";
                $settingsFile = fopen($file, 'w');
                $setupDetails = array("language" => $selectedLanguage, "designer_dir" => $designerFolder);
                // because few server does not support file put content
                fwrite($settingsFile, json_encode($setupDetails));
                fclose($settingsFile);
                //only for owner reading the designer root directory.
                chmod($inkXEpkgDIR . "imprint_details.json", 0600);
                $messageCode = "STEP_ONE_COMPLETED";
                $proceedNext = true;
                $response = array('message_code' => $messageCode, 'proceed_next' => $proceedNext);
                $this->json($response);
            }
        } else {
            echo "invalid call";
        }
        exit();
    }

    public function checkServerCompatibility()
    {
        // Check common PHP settings
        $serverStatus = array();
        $commonSettings = array('checkMysqli', 'checkPDO', 'curl', 'checkMbstring', 'checkIconv', 'checkHash', 'checkGDLibrary', 'checkMemoryLimit', 'checkPostMaxSize', 'checkUploadMaxFilesize', 'checkMaxExecutionTime', 'checkMaxInputTime', 'checkDefaultSockettimeOut', 'checkZipExtension', 'checkMysqlNd', 'checkXml','checkFileInfo','checkServerModules');
        $checkPointOne = $this->checkServerSettings($commonSettings);
        $statusOne = $this->processReport($checkPointOne);
        $serverStatus['php_settings'] = $statusOne;
        $filePermissionArr = array('checkFilePermission');
        $checkPointFile = $this->checkServerSettings($filePermissionArr);
        $statusFile = $this->processReport($checkPointFile);
        $serverStatus['file_permission'] = $statusFile;
        //Check ecommerce related settings and dependent libraries
        $storeSettings = array();
        switch (strtolower(STORETYPE)) {
            case 'shopify':
                $storeSettings = array('checkSSL', 'checkPHPVersion');
                break;
            case 'magento':
                $storeSettings = array('checkPHPVersion', 'checkSSL');
                break;
            case 'woocommerce':
                $storeSettings = array('checkPHPVersion', 'checkRestAPI', 'checkWPversion', 'checkWCversion');
                break;
            case 'prestashop':
                $storeSettings = array('checkPHPVersion');
                break;
            case 'bigcommerce':
                $storeSettings = array('checkSSL', 'checkPHPVersion');
                break;
            case 'opencart':
                $storeSettings = array('checkPHPVersion', 'checkOpencartVersion', 'checkVQmod');
                break;
            case 'others':
                $storeSettings = array('checkPHPVersion');
                break;
            default:
                $storeSettings = array('checkSSL', 'checkPHPVersion');
                break;
        }
        $checkPointTwo = $this->checkServerSettings($storeSettings);
        $statusTwo = $this->processReport($checkPointTwo);
        $serverStatus['ecomm_settings'] = $statusTwo;
        //check third party dependent apps
        $dependentApps = array('checkImageMagick', 'checkGhostScript', 'checkInkScape', 'checkShellExec');
        $checkPointThree = $this->checkServerSettings($dependentApps);
        $statusThree = $this->processReport($checkPointThree);
        $serverStatus['apps_settings'] = $statusThree;
        $proceedStatus = array_column($serverStatus, 'proceed_next');
        $warningStatus = array_column($serverStatus, 'warning_status');
        $errorStatus = array_column($serverStatus, 'error_status');
        if (in_array(false, $proceedStatus)) {
            $serverStatus['proceed_next'] = false;
        } else {
            $serverStatus['proceed_next'] = true;
        }

        if (in_array(false, $warningStatus)) {
            $serverStatus['is_warning'] = false;
        } else {
            $serverStatus['is_warning'] = true;
        }

        if (in_array(false, $errorStatus)) {
            $serverStatus['is_error'] = false;
        } else {
            $serverStatus['is_error'] = true;
        }

        $this->json($serverStatus);
    }

    private function processReport($response)
    {
        $serverStatus = array();
        if (empty($response)) {
            $serverStatus['warning_status'] = false;
            $serverStatus['error_status'] = false;
            $serverStatus['proceed_next'] = true;
        } else {
            $proceedStatus = array_column($response, 'proceed_next');
            $serverStatus['warning_status'] = in_array(true, $proceedStatus) ? true : false;
            $serverStatus['error_status'] = in_array(false, $proceedStatus) ? true : false;
            $serverStatus['proceed_next'] = !in_array(false, $proceedStatus) ? true : false;
            $serverStatus['report'] = $response;

        }
        return $serverStatus;
    }

    public function createXEtoolFolder()
    {

    }

    public function extractPackage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // check folder
            $inkXEpkgDIR = str_replace(SETUPFOLDERNAME . DS, '', ROOTABSPATH);
            $pkgXEfolder = $inkXEpkgDIR . DEFAULTXEFOLDER;
            $file = $inkXEpkgDIR . "imprint_details.json";
            if (file_exists($file)) {
                $setupDetails = json_decode(file_get_contents($file), true);
                $newFolder = $setupDetails['designer_dir'] != '' ? $setupDetails['designer_dir'] : DEFAULTXEFOLDER;
                $designerPath = DOCABSPATH . $newFolder;
                if (!is_dir($designerPath)) {
                    mkdir($designerPath);
                    chmod($designerPath, 0755);
                    // copy inkxe package to user set folder and start inkXE set up.
                    $this->recurse_copy($pkgXEfolder, $designerPath);
                }
                if (STORETYPE == 'magento') {
                    if (STOREAPIVERSION == 'v1x') {
                        $this->recurse_copy(ROOTABSPATH . 'magento/1.X', DOCABSPATH);
                    } else {
                        foreach (glob(ROOTABSPATH . 'magento/2.X/app/code/ImprintNext/Cedapi/Observer/*') as $path_to_observer_file) {
                            $observer_contents = file_get_contents($path_to_observer_file);
                            $observer_contents = str_replace("xetool", $newFolder, $observer_contents);
                            file_put_contents($path_to_observer_file, $observer_contents);
                        }
                        foreach (glob(ROOTABSPATH . 'magento/2.X/app/code/ImprintNext/Cedapi/Plugin/*') as $path_to_plugin_file) {
                            $plugin_contents = file_get_contents($path_to_plugin_file);
                            $plugin_contents = str_replace("xetool", $newFolder, $plugin_contents);
                            file_put_contents($path_to_plugin_file, $plugin_contents);
                        }
                        foreach (glob(ROOTABSPATH . 'magento/2.X/app/code/ImprintNext/Cedapi/view/frontend/templates/cart/item/*') as $path_to_cart_file) {
                            $cart_contents = file_get_contents($path_to_cart_file);
                            $cart_contents = str_replace("xetool", $newFolder, $cart_contents);
                            file_put_contents($path_to_cart_file, $cart_contents);
                        }
                        foreach (glob(ROOTABSPATH . 'magento/2.X/app/code/ImprintNext/Cedapi/view/frontend/templates/product/view/*') as $path_to_product_file) {
                            $product_contents = file_get_contents($path_to_product_file);
                            $product_contents = str_replace("xetool", $newFolder, $product_contents);
                            file_put_contents($path_to_product_file, $product_contents);
                        }
                        $this->recurse_copy(ROOTABSPATH . 'magento/2.X', DOCABSPATH);
                    }
                }
                if (STORETYPE == 'prestashop') {
                    $fileStatus = 0;
                    $fileStatus = $this->checkAllStoreFiles();
                    //var_dump($fileStatus);exit;
                    if ($fileStatus) {
                        $this->copyStoreThemeFiles();
                    }
                }
                if (STORETYPE == 'shopify') {
                    if (!file_exists(DOCABSPATH . "shopify")) {
                        mkdir(DOCABSPATH . 'shopify', 0755, true);
                    }

                    $this->recurse_copy(ROOTABSPATH . "shopify", $designerPath . "/shopify");
                }
                if (STORETYPE == 'woocommerce') {
                    $this->copyPluginfiles($newFolder);
                }
                if (STORETYPE == 'opencart') {
                    $this->copyEntenstionfiles($newFolder);
                }
                if (STORETYPE == 'others') {
                    $this->copyOtherStorefiles();
                    $this->recurse_copy(ROOTABSPATH . 'others/v2x/mockupData', DOCABSPATH.'mockupData');
                }
                //$this->checkPackageFiles($newFolder);
                $setupStatus = $this->startFileSetup($newFolder);
                $responseArr = array("proceed_next" => $setupStatus[0], "msg" => $setupStatus[1]);
                $this->json($responseArr);
            } else {
                echo "setup json file not found";exit();
            }

        } else {
            echo "invalid call";
        }
        exit();

    }

    public function checkCurrentStep()
    {
        $stepCount = 5;
        for ($counter = 1; $counter <= $stepCount; $counter++) {
            $thisStepFunction = "checkStep" . $counter;
            $status = $this->$thisStepFunction();
            if ($status['proceed_next'] && $status['stop_at'] == "") {
                continue;
            } else {
                $response = array("show_step" => $counter, "stop_at" => $status['stop_at']);
                $this->json($response);exit();
            }
        }
    }

    public function saveConfiguration()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $this->_request['type'];
            $data = json_decode(base64_decode($this->_request['data']), true);
            $status = '';
            if ($type != '' && $data != '') {
                switch ($type) {
                    case 'db':
                        $status = $this->createXEdatabase($data);
                        break;
                    case 'store':
                        if (STORETYPE == 'magento') {
                            $data += ['type' => 'n'];
                            $data = $this->CreateStoreCredential($data);
                        }
                        if (STORETYPE == 'prestashop') {
                            $xetoolDir = $this->getXetoolDir();
                            $data = $this->addWebServiceKey($xetoolDir);
                            $this->installCustomModules();
                        }
                        $status = $this->saveStoreCredential($data);
                        break;
                    case 'admin':
                        $status = $this->createAdminCredential($data);
                        break;
                }
                if (is_array($status) && !empty($status)) {
                    $this->json($status);exit();
                }
            }
        } else {
            echo "invalid call";
        }
        exit();
    }

    public function completeXESetup()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(base64_decode($this->_request['data']), true);
            $storeStatus = $this->storeOperation($data);
            if ($storeStatus['proceed_next']) {
                $inkXEsetup = $this->XESettings($data);
                echo $this->json($inkXEsetup);exit();
            } else {
                echo $this->json($storeStatus);
            }
            exit();
        } else {
            echo "invalid call";
        }
        exit();
    }

    private function XESettings($data)
    {
        $response = array("proceed_next" => true, "message" => "XE_SETTINGS_COMPLETED");
        return $response;
    }

    public function getXEDetails()
    {
        $baseURL = $this->getNewXEURL();
        $response['admin_url'] = rtrim($baseURL, "/") . "/admin/index.html";
        $response['tool_url'] = $this->getToolURL();
        $this->json($response);exit();
    }

    public function getLanguageSelected()
    {
        $installDIR = str_replace(SETUPFOLDERNAME . DS, '', ROOTABSPATH);
        $inFoJSONFile = $installDIR . "imprint_details.json";
        if (file_exists($inFoJSONFile)) {
            $settings = json_decode(file_get_contents($inFoJSONFile), true);
            $selectedLangFile = $installDIR . SETUPFOLDERNAME . "/languages/" . $settings['language'];
        } else {
            $selectedLangFile = $installDIR . SETUPFOLDERNAME . "/languages/language_english_en.json";
        }
        if (file_exists($selectedLangFile)) {
            $langFile = $this->sanitizePath($selectedLangFile);
            $language = file_get_contents($langFile);
        } else {echo "Language Files not found";exit();}
        print_r($language);exit();
    }

    public function getSelectedLanguageName()
    {
        $installDIR = str_replace(SETUPFOLDERNAME . DS, '', ROOTABSPATH);
        $inFoJSONFile = $installDIR . "imprint_details.json";
        if (file_exists($inFoJSONFile)) {
            $langFile = file_get_contents($inFoJSONFile);
        } else {
            $langFile = 0;
        }
        print_r($langFile);exit();
    }
    public function getPrintMethods()
    {
        $dummyData = file_get_contents(PROFILEJSON);
        print_r($dummyData);exit();
    }

    public function getDummyProducts()
    {
        $dummyData = file_get_contents(PRODUCTJSON);
        print_r($dummyData);exit();
    }

    public function getSecurityQuestions()
    {
        $configXMLpath = $this->getNewXEpath() . XECONFIGXML; // xeconfig xml file
        $dom = new DomDocument();
        $dom->load($configXMLpath) or die("Unable to load xml");
        $host = $dom->getElementsByTagName('host')->item(0)->nodeValue;
        $user = $dom->getElementsByTagName('dbuser')->item(0)->nodeValue;
        $password = $dom->getElementsByTagName('dbpass')->item(0)->nodeValue;
        $dbName = $dom->getElementsByTagName('dbname')->item(0)->nodeValue;
        $port = $dom->getElementsByTagName('port')->item(0)->nodeValue;
        error_reporting(0);
        if (isset($port) && $port != '') {
            $conn = new mysqli($host, $user, $password, $dbName, $port);
        } else {
            $conn = new mysqli($host, $user, $password);
            $conn->select_db($dbName);
        }
        $secQuestionSQL = "SELECT * FROM security_questions";
        $questions = $conn->query($secQuestionSQL);
        if ($questions->num_rows > 0) {
            while ($row = $questions->fetch_assoc()) {
                $thisQsn['id'] = $row["xe_id"];
                $thisQsn['question'] = $row["question"];
                $SecQuestions['questions'][] = $thisQsn;
            }
        } else {
            echo "No security questions found.";exit();
        }
        print_r(json_encode($SecQuestions));exit();
    }

    public function getStoreResponseServer()
    {
        $configXMLpath = $this->getNewXEURL() ."api/v1/tags/cliparts?store_id=1";
        print_r(json_encode($configXMLpath));exit();
    }
}
$api = new Component;
$api->processApi();
