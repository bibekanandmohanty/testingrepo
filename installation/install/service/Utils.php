<?php  

class Utils extends StoreComponent {
	public function __construct(){
		parent::__construct();
	} 

	protected function checkServerSettings($array)
	{
	    $status = array();
	    $errorStatus = $warningStatus = 0;
	    foreach ($array as $functionName) {
	        if (method_exists($this, $functionName)) {
	            $result = $this->$functionName(1);

	            $thisStatus = array();
	            if ($result['1'] == 0) {
	            	$thisStatus['proceed_next'] = false;
	            	$thisStatus['settingName'] = $functionName;
	            	//help doc link will be sent here
	            } else if ($result['1'] == 2) {
	                $thisStatus['proceed_next'] = true;
	            	$thisStatus['settingName'] = $functionName;
	            	//help doc link will be sent here
	            }
	            if (!empty($thisStatus)) {
	            	$status[] = $thisStatus;
	            }
	        }
	    }

	    return $status;
	}

	protected function serverSettings($functionName)
	{
	    if (function_exists($functionName)) {
	        return $result = $this->$functionName();
	    }
	}

	protected function showSettingValues($outPutText, $status)
	{
	    return array($outPutText, $status);
	}

	protected function checkSSL($type = false)
	{
	    $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != 'off') ? 'https' : 'http';
	    if ($protocol == 'http') {
	        $status = 0;
	    } else {
	        $status = 1;
	    }
	    return $this->showSettingValues($protocol, $status);
	}
	protected function checkPHPVersion($type = false)
	{
	    $phpversion = phpversion();
	    $varr = explode('.', $phpversion);
	    if ($varr[0] > 5) {
	        $status = 1;
	    } else {
	        $status = 0;
	    }

	    return $this->showSettingValues($phpversion, $status);
	}
	protected function checkMysqli($type = false)
	{
	    if (extension_loaded('mysqli')) {
	        $isEnabled = "Enabled";
	        $status = 1;
	    } else {
	        $isEnabled = "Disabled";
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	protected function checkFilePermission($type = false)
	{
	    $str = 'Check Writable Permission';
	    $data = '';
	    try {
	        $data = file_put_contents(DOCABSPATH . 'test.php', $str);
	        if ($data) {
	            $isAllowed = "Allowed";
	            $status = 1;
	        } else {
	            $isAllowed = "Dis-Allowed";
	            $status = 0;
	        }
	    } catch (Exception $e) {
	        $isAllowed = "Dis-Allowed";
	        $status = 0;
	    }
	    return $this->showSettingValues($isAllowed, $status);
	}
	protected function checkPDO($type = false)
	{
	    if (class_exists('PDO')) {
	        $isEnabled = 'Enabled';
	        $status = 1;
	    } else {
	        $isEnabled = 'Disabled';
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	protected function curl($type = false)
	 {
	     if (function_exists('curl_version')) {
	         $isEnabled = 'Enabled';
	         $status = 1;
	     } else {
	         $isEnabled = 'Disabled';
	         $status = 0;
	     }
	     return $this->showSettingValues($isEnabled, $status);
	 }
	protected function checkMbstring($type = false)
	{
	    if (extension_loaded('mbstring')) {
	        $isEnabled = 'Enabled';
	        $status = 1;
	    } else {
	        $isEnabled = 'Disabled';
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	protected function checkIconv($type = false)
	{
	    if (extension_loaded('iconv')) {
	        $isEnabled = 'Enabled';
	        $status = 1;
	    } else {
	        $isEnabled = 'Disabled';
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	protected function checkHash($type = false)
	{
	    if (extension_loaded('hash')) {
	        $isEnabled = 'Enabled';
	        $status = 1;
	    } else {
	        $isEnabled = 'Disabled';
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	protected function checkGDLibrary($type = false)
	{
	    if (extension_loaded('gd')) {
	        $isEnabled = 'Enabled';
	        $status = 1;
	    } else {
	        $isEnabled = 'Disabled';
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	protected function checkKeepAlive($type = false)
	{
	    $apache_settings = apache_request_headers();
	    $conType = $apache_settings['Connection'];
	    if ($conType == 'keep-alive' || $conType == 'Keep-Alive') {
	        $keepAlive = 'Keep-Alive';
	        $status = 1;
	    } else {
	        $keepAlive = 'Not-Alive';
	        $status = 2;
	    }
	    return $this->showSettingValues($keepAlive, $status);
	}
	protected function checkMemoryLimit($type = false)
	{
	    $mlv = ini_get('memory_limit');
	    $pmsov = ini_get('post_max_size');
	    $pmsv = (int) substr($pmsov, 0, -1);
	    $umfov = ini_get('upload_max_filesize');
	    $umfv = (int) substr($umfov, 0, -1);
	    $mlv = (int) substr($mlv, 0, -1);
	    if ($mlv > 255 && $mlv > $pmsv && $pmsv > $umfv) {
	        $status = 1;
	    } else {
	        $status = 2;
	    }
	    return $this->showSettingValues($mlv, $status);
	}
	protected function checkPostMaxSize($type = false)
	{
	    $mlv = ini_get('memory_limit');
	    $pmsov = ini_get('post_max_size');
	    $pmsv = (int) substr($pmsov, 0, -1);
	    $umfov = ini_get('upload_max_filesize');
	    $umfv = (int) substr($umfov, 0, -1);
	    $mlv = (int) substr($mlv, 0, -1);

	    if ($pmsv > 59 && $pmsv > $umfv) {
	        $status = 1;
	    } else {
	        $status = 2;
	    }
	    return $this->showSettingValues($pmsov, $status);
	}
	protected function checkUploadMaxFilesize($type = false)
	{
	    $mlv = ini_get('memory_limit');
	    $pmsov = ini_get('post_max_size');
	    $pmsv = (int) substr($pmsov, 0, -1);
	    $umfov = ini_get('upload_max_filesize');
	    $umfv = (int) substr($umfov, 0, -1);
	    $mlv = (int) substr($mlv, 0, -1);
	    if ($umfv > 9) {
	        $status = 1;
	    } else {
	        $status = 2;
	    }
	    return $this->showSettingValues($umfov, $status);
	}
	protected function checkMaxExecutionTime($type = false)
	{
	    $metValue = ini_get('max_execution_time');
	    $metv = (int) $metValue;
	    if ($metv < 100) {
	        $status = 0;
	    } else if (100 <= $metv && $metv < 300) {
	        $status = 2;
	    } else {
	        $status = 1;
	    }
	    return $this->showSettingValues($metValue, $status);
	}
	protected function checkMaxInputTime($type = false)
	{
	    $mitValue = ini_get('max_input_time');
	    $mit = (int) $mitValue;
	    if ($mit == 60 || $mit > 60) {
	        $status = 1;
	    } else {
	        $status = 2;
	    }
	    return $this->showSettingValues($mitValue, $status);
	}
	protected function checkDefaultSockettimeOut($type = false)
	{
	    $dstoValue = ini_get('default_socket_timeout');
	    $dsto = (int) $dstoValue;
	    if ($dsto == 60 || $dsto > 60) {
	        $status = 1;
	    } else {
	        $status = 2;
	    }
	    return $this->showSettingValues($dstoValue, $status);
	}

	protected function checkZipExtension($type = false)
	{
	    $zipExt = extension_loaded('zip');
	    $zipExtValue = ($zipExt) ? 'True' : 'False';
	    $zipExt = (int) $zipExt;
	    if ($zipExt) {
	        $status = 1;
	    } else {
	        $status = 0;
	    }
	    return $this->showSettingValues($zipExtValue, $status);
	}
	protected function checkMysqlNd($type = false)
	{
	    if (function_exists('mysqli_get_client_stats')) {
	        $isEnabled = "Enabled";
	        $status = 1;
	    } else {
	        $isEnabled = "Disabled";
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	protected function checkXml($type = false)
	{
	    if (extension_loaded('xml')) {
	        $isEnabled = 'Enabled';
	        $status = 1;
	    } else {
	        $isEnabled = 'Disabled';
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}

	protected function checkFileInfo($type = false)
	{
	    if (extension_loaded('fileinfo')) {
	        $isEnabled = 'Enabled';
	        $status = 1;
	    } else {
	        $isEnabled = 'Disabled';
	        $status = 0;
	    }
	    return $this->showSettingValues($isEnabled, $status);
	}
	
	protected function checkServerModules()
	{
		// Check which webserver installed in the server
		$mystring=$_SERVER['SERVER_SOFTWARE'];
		if(strpos($mystring, 'Apache') !== false){
			if(function_exists('apache_get_modules')){
	    	 	$apachemodules = apache_get_modules();
	    		if ((in_array("mod_headers", $apachemodules))&&(in_array("mod_rewrite", $apachemodules))){
	    			$isEnabled = 'Enabled';
		      		$status = 1;
	  		    }else{
		    		$isEnabled = 'Disabled';
			        $status = 0;
	   			}
	   		} else{
				$isEnabled = 'Enabled';
		        $status = 1;
			}
		} else{
			$isEnabled = 'Enabled';
	        $status = 1;
		}
		return $this->showSettingValues($isEnabled, $status);
	}

	protected function checkShellExec(){
		$function = 'shell_exec';
		$isEnabled = 'Disabled';
	    $status = 2;
		if (is_callable($function) &&  stripos(ini_get('disable_functions'), $function) === false) {
			$isEnabled = 'Enabled';
	        $status = 1;
		}
		return $this->showSettingValues($isEnabled, $status);
	}

	protected function checkImageMagick(){
		$isEnabled = 'Disabled';
	    $status = 0;
		if (extension_loaded('imagick')) {
			$isEnabled = 'Enabled';
	        $status = 1;
		}
		return $this->showSettingValues($isEnabled, $status);
	}

	protected function checkGhostScript(){
		$isEnabled = 'Disabled';
	    $status = 2;
		if ( shell_exec("gs --version") > 0 ) {
			$isEnabled = 'Enabled';
	        $status = 1;
		}
		return $this->showSettingValues($isEnabled, $status);
	}

	protected function checkInkScape(){
		$isEnabled = 'Disabled';
	    $status = 2;
	    $version = shell_exec( "inkscape -V");
		if ( strpos($version, "Inkscape 0.9") !== false || strpos($version, "Inkscape 1.0") !== false ) {
			$isEnabled = 'Enabled';
	        $status = 1;
		}
		return $this->showSettingValues($isEnabled, $status);
	}

	protected function getBaseUrl()
	{
	    $path2 = $_SERVER['PHP_SELF'];
	    $path = explode(INSTALLFOLDER, $path2);
	    $path = $path['0'];
	    //$path = str_ireplace('/'.INSTALLPATH.'index.php','',$path);
	    $appname = str_ireplace(INSTALLFOLDER, '', $path);
	    $appname = substr($appname, 1);

	    $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != 'off') ? 'https' : 'http';
	    $hostname = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/';
	    $base_url = (strlen($appname)) ? $hostname . $appname : $hostname;
	    return array($appname, $base_url);
	}

	protected function startFileSetup($newFolder){
		$status = 1;
		$errorMsg = '';
		//check if package is correct or required files present
		$PKGfileArray = array(
			DOCABSPATH .$newFolder.DS."api",
			DOCABSPATH .$newFolder.DS."admin",
			DOCABSPATH .$newFolder.DS."assets",
			DOCABSPATH .$newFolder.DS. "index.html",
			DOCABSPATH .$newFolder.DS.XECONFIGXML,
			DOCABSPATH .$newFolder.DS. XECONFIGJS
		);
		foreach ($PKGfileArray as $xeFile){
			if (!file_exists($xeFile)){
				$errorMsg.="- ".$xeFile." not found...\n";
				$status = 0;
			}
		}
		/*
			@ Purpose : App name fetched from the url & config xml is updated in step1
		*/
		$toolDIR = DOCABSPATH.$newFolder.DS;
		$xeConfigFile = $toolDIR.XECONFIGXML;
		$dom = new DomDocument();
		$dom->load($xeConfigFile) or die("error opening config XML");

		$urlDet = $this->getBaseUrl();
		$base_url = $urlDet['1'];
		$base_url .= $newFolder.DS;
		$dom->getElementsByTagName('api_url')->item(0)->nodeValue = $base_url;
		$dom->getElementsByTagName('xetool_dir')->item(0)->nodeValue = $newFolder;
		$dom->getElementsByTagName('store_directory')->item(0)->nodeValue = ucfirst(STORETYPE);
		$dom->getElementsByTagName('store_version')->item(0)->nodeValue = STOREAPIVERSION;
		$dom->save($xeConfigFile);

		// check whether appname and base_url successfully write or not //
		if($dom->getElementsByTagName('api_url')->item(0)->nodeValue == ""){
			$errorMsg.='- Error in writing "Store URL" to config.xml file. \n';
			$status = 0;
			$this->xe_log("\n" . date("Y-m-d H:i:s") . ':'.$errorMsg);
		}

		$clientAppConfig = $toolDIR.XECONFIGJS;
		if (file_exists($clientAppConfig)){
			@chmod($clientAppConfig, 0777);
			$settingStr = @file_get_contents($clientAppConfig);
			$settingStr = str_replace(STRINGFORREPLACE,$base_url,$settingStr);
			$settingStr = str_replace(STRINGFORREPLACESTOREURL,$urlDet['1'],$settingStr);
			@file_put_contents($clientAppConfig,$settingStr);

			// check if XEPATH properly written or not //
			$settingStrCheckStr = @file_get_contents($clientAppConfig);

			if(strpos($settingStrCheckStr, STRINGFORREPLACE) !== false){
				$errorMsg.='- Base URL not written properly in config.js \n';
				$status = 0;
				$this->xe_log("\n" . date("Y-m-d H:i:s") . ':'.$errorMsg);
			}
		}
		// put base URL in admin main js
		$adminJSdir = $toolDIR."admin";
		$adminFiles = scandir($adminJSdir);
		foreach ($adminFiles as $file) {
			if (strpos($file, "main") === 0) {
				$fileContent = $newContent = "";
			    $thisFile = $adminJSdir.DS.$file;
			    $fileContent = file_get_contents($thisFile);
			    $newContent = str_replace("BASEURL/", $base_url, $fileContent);
			    $fileWrite = file_put_contents($thisFile, $newContent);
			}
		}

		// put base URL in quotation main js
		$quoteJSdir = $toolDIR."quotation";
		$quoteFiles = scandir($quoteJSdir);
		foreach ($quoteFiles as $file) {
			if (strpos($file, "main") === 0) {
				$fileContent = $newContent = "";
			    $thisFile = $quoteJSdir.DS.$file;
			    $fileContent = file_get_contents($thisFile);
			    $newContent = str_replace("BASEURL/", $base_url, $fileContent);
			    $fileWrite = file_put_contents($thisFile, $newContent);
			}
		}

		// if(!@copy("frontendlc.php", DOCABSPATH."frontendlc.php")){
		// 	$errorMsg.='- frontendlc.php file didn\'t copy. \n';
		// 	$status = 0;
		// 	$this->xe_log("\n" . date("Y-m-d H:i:s") . ':'.$errorMsg);
		// };
		return array($status,$errorMsg);
	}
	// Check if user set language and xetool directory is set with install package or not
	Protected function checkStep1(){
		$proceedNext = false;
		$inkXEpkgDIR = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH);
		$tempConfigFile = $inkXEpkgDIR."imprint_details.json";
		if (file_exists($tempConfigFile)) {
			$setupDetails = json_decode(file_get_contents($tempConfigFile), true) ;
			if (is_array($setupDetails) && $setupDetails['language'] != '' && $setupDetails['designer_dir'] != '') {
				$proceedNext = true;
			}
		}
		return array('proceed_next' => $proceedNext, 'stop_at' => "");
	}
	// Check if xetool folder copied to user set dir or not
	// pending: better error log in each step
	Protected function checkStep2(){
		$proceedNext = false;
		$inkXEpkgDIR = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH);
		$tempConfigFile = $inkXEpkgDIR."imprint_details.json";
		$fileCheck = true;
		$dom = new DomDocument();
		if (file_exists($tempConfigFile)) {
			$setupDetails = json_decode(file_get_contents($tempConfigFile), true) ;
			if (is_array($setupDetails) && $setupDetails['designer_dir'] != '') {
				$newXEtoolDIR = DOCABSPATH.$setupDetails['designer_dir']. DS;
				//check if package is copied correct to the new dir.
				$PKGfileArray = array(
					$newXEtoolDIR."api",
					$newXEtoolDIR."admin",
					$newXEtoolDIR."assets",
					$newXEtoolDIR. "index.html",
					$newXEtoolDIR. XECONFIGJS,
					$newXEtoolDIR. XECONFIGXML,
				);
				foreach ($PKGfileArray as $xeFile){
					$thisFile = $this->sanitizePath($xeFile);
					if (!file_exists($thisFile)){
						$fileCheck = false;
					}
				}
				// check config xml and js
				// check whether base_url successfully written or not //
				if ($fileCheck) {
					$xmlCheck = false;
					$toolDIR = DOCABSPATH.$setupDetails['designer_dir'].DS;
					$xeConfigFile = $toolDIR.XECONFIGXML;
					$dom->load($xeConfigFile) or die("error");
					if($dom->getElementsByTagName('api_url')->item(0)->nodeValue != ""){
						$xmlCheck = true;
					}
					// check if client side config js is written with base url
					if ($xmlCheck) {
						$clientAppConfig = $toolDIR.XECONFIGJS;
						if (file_exists($clientAppConfig)){
							$settingStrCheckStr = @file_get_contents($clientAppConfig);
							$urlDet = $this->getBaseUrl();
							$base_url = $urlDet['1'].$setupDetails['designer_dir'].DS;
							if(strpos($settingStrCheckStr, $base_url) !== false){
								$proceedNext = true;
							}
						}
					}
				}
			}
		}
		return array('proceed_next' => $proceedNext , 'stop_at' => "");
	}
	Protected function checkStep3(){
		$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
		$dom = new DomDocument();
		$dom->load($configXMLpath) or die("Unable to load xml");
		$proceedNext = false;
		$adminStatus = false;
		$storeStatus = false;
		$stopAt = 1;
		if ($dom->getElementsByTagName('host')->item(0)->nodeValue != "" && $dom->getElementsByTagName('dbuser')->item(0)->nodeValue != "" && $dom->getElementsByTagName('dbname')->item(0)->nodeValue != "" /*&& $dom->getElementsByTagName('dbpass')->item(0)->nodeValue != ""*/) {
			if(STORETYPE == 'opencart' || STORETYPE == 'others'){
				$stopAt = 3;
			} else {
				$stopAt = 2;
			}
		}
		// check from respective store function if store cred is written to XML
		if ($stopAt > 1) {
			if(STORETYPE == 'opencart' || STORETYPE == 'others'){
				$storeStatus = 1;
			} else {
				$storeStatus = $this->checkStoreCredWrite($dom);
				if ($storeStatus) {
					$stopAt = 3;
				}
			}
			// check if admin user created
			$adminStatus = $this->checkAdminCreated($dom);
			if (!$adminStatus && $stopAt > 2) {
				$stopAt = 3;
			}
		}

		if ($adminStatus && $storeStatus) {
			$stopAt = "";
			$proceedNext = true;
		}
		return array('proceed_next' => $proceedNext, 'stop_at' => $stopAt);
	}
	Protected function checkStep4(){
		if(STORETYPE == 'others'){
			$proceedNext = true;
		} else {
			$proceedNext = false;
		}
		return array('proceed_next' => $proceedNext, 'stop_at' => "");
	}
	Protected function checkStep5(){
		$proceedNext = false;
		return array('proceed_next' => $proceedNext, 'stop_at' => "");
	}

	/*
		- Name : updateDBAccessToXML
		- it will write DB access to XML
		- copy xml file to app folder
		- Return status success or error
	*/
	protected function createXEdatabase($postData){
		extract($postData);
		$response = '';
		$errorMsg = '';
		$status = 1;
		$connectionError = 0;
		// Check given DB connection 
		try {
	        error_reporting(0);
	        if (isset($port) && $port != '') {
	            $conn = new mysqli($host, $user, $pwd, $dbname, $port);
	        } else {
	            $conn = new mysqli($host, $user, $pwd, $dbname);
	        }
	        if ($conn->connect_error) {
			    $response = array("proceed_next" => false, "message" => "INCORRECT_DB_INPUT");
		        $connectionError = 1;
		        return $response;exit();
			}
	    } catch (Exception $e) {
	        $error = "- Database Connection failed. Error: " . $e->getMessage() . "\n";
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
	    }
	    if ($connectionError == 0) {
	    	$inkXEpkgDIR = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH).SETUPFOLDERNAME.DS;
	    	$sqlFile = $inkXEpkgDIR.SQLFILE;
	    	if (file_exists($sqlFile)) {
	    		if ($conn->select_db($dbname)) {
	                $sqlStatus = $this->run_sql_file($sqlFile, $conn);
	            }else{
	            	$response = array("proceed_next" => false, "message" => "DB_NOT_FOUND");
			        return $response;exit();
	            }
                if ($sqlStatus['0'] == 0) {
                    $errorMsg .= $sqlStatus['1'];
                    $status = 0;
                    $response = array("proceed_next" => false, "message" => $errorMsg);
			        return $response;exit();
                }
            }else{
            	$response = array("proceed_next" => false, "message" => "BASIC_DB_NOT_FOUND");
			    return $response;exit();
            } 
	    }

		// Update DB info to config XML
		$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
		$dom = new DomDocument();
		$dom->load($configXMLpath) or die("Unable to load xml");
		$dom->getElementsByTagName('host')->item(0)->nodeValue = $host;
		$dom->getElementsByTagName('dbuser')->item(0)->nodeValue = $user;
		if (isset($pwd) && $pwd) {
			$cdataDb = $dom->createCDATASection($pwd);
			$dom->getElementsByTagName("dbpass")->item(0)->appendChild($cdataDb);
		}
		$cdata = $dom->createCDATASection($dbname);
		$dom->getElementsByTagName("dbname")->item(0)->appendChild($cdata);
		$dom->save($configXMLpath);
		$response = array("proceed_next" => true, "message" => "DATABASE_CREATED");
	    return $response;
	}

	protected function getNewXEpath(){
		$inkXEpkgDIR = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH);
		$tempConfigFile = $inkXEpkgDIR."imprint_details.json";
		if (file_exists($tempConfigFile)) {
			$setupDetails = json_decode(file_get_contents($tempConfigFile), true) ;
			if (is_array($setupDetails) && $setupDetails['designer_dir'] != '') {
				$newXEtoolDIR = DOCABSPATH.$setupDetails['designer_dir']. DS;
			}
		}
		return $newXEtoolDIR;
	}

	protected function getNewXEURL(){
		$baseURLData = $this->getBaseUrl();
		$baseURL = $baseURLData[1];
		$inkXEpkgDIR = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH);
		$tempConfigFile = $inkXEpkgDIR."imprint_details.json";
		if (file_exists($tempConfigFile)) {
			$setupDetails = json_decode(file_get_contents($tempConfigFile), true) ;
			if (is_array($setupDetails) && $setupDetails['designer_dir'] != '') {
				$newXEtoolURL = $baseURL.$setupDetails['designer_dir']. DS;
			}
		}
		return $newXEtoolURL;
	}

	protected function saveStoreCredential($data){
		$storeCheck = $this->checkStoreCredential($data);
		if ($storeCheck[0] == 1) {
			$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
			$dom = new DomDocument();
			$dom->load($configXMLpath) or die("Unable to load xml");
			foreach ($data as $key => $value) {
				$dataElement = $dom->createElement("$key", $value);
				$dom->getElementsByTagName('url_detail')->item(0)->appendChild($dataElement);
			}
			$dom->save($configXMLpath);
			$response = array("proceed_next" => true, "message" => "STORE_CRED_SAVED");
		}else{
			$response = array("proceed_next" => false, "message" => $storeCheck[1]);
		}
		return $response;
	}

	protected function createAdminCredential($data){
		extract($data);
		if (!filter_var($adminUser, FILTER_VALIDATE_EMAIL)) {
		  $response = array("proceed_next" => false, "message" => "INVALID_USER_NAME");
	      return $response;exit();
		}
		$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
		$dom = new DomDocument();
		$dom->load($configXMLpath) or die("Unable to load xml");
		$host = $dom->getElementsByTagName('host')->item(0)->nodeValue;
		$user = $dom->getElementsByTagName('dbuser')->item(0)->nodeValue;
		$password = $dom->getElementsByTagName('dbpass')->item(0)->nodeValue;
		$dbName = $dom->getElementsByTagName('dbname')->item(0)->nodeValue;
		$port = $dom->getElementsByTagName('port')->item(0)->nodeValue;
		try {
	        error_reporting(0);
	        if (isset($port) && $port != '') {
	            $conn = new mysqli($host, $user, $password, $dbName, $port);
	        } else {
	            $conn = new mysqli($host, $user, $password);
	            $conn->select_db($dbName);
	        }
	    } catch (Exception $e) {
	        $error = "- Database Connection failed. Error: " . $e->getMessage() . "\n";
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
	        $response = array("proceed_next" => false, "message" => $error);
	        return $response;exit();
	    }
	    /*
        @ Purpose : Code to update stores and ids in domain_store_rel table
         */
        $domainStoreSql = "INSERT INTO stores(store_name,store_url) VALUES('".STORETYPE."','" . INSTALLDOMAIN . "')";
        $queryStatusDS = $conn->query($domainStoreSql);
        $storeID = $conn->insert_id;
        if ($queryStatusDS == false) {
            $errorMsg .= "- Data not inserted to domain_store_rel table. \n";
            $status = 0;
        }
        /*
        @ Purpose : Code to update user access Info in user table
         */
        $sqlUser = "INSERT INTO admin_users(name,email,password,first_question_id,first_answer,second_question_id,second_answer,store_id,language_selected) VALUES('Super Admin','" . $adminUser . "','" . password_hash($adminPassword, PASSWORD_BCRYPT) . "','" . $question_id1 . "','" . $securAns1 . "','" . $question_id2 . "','" . $securAns2 . "','" . $storeID . "','" . $language_selected . "')";
        $queryStatusUser = $conn->query($sqlUser);
        if ($queryStatusUser == false) {
            $errorMsg .= "- Designer admin account access creation failed. \n";
            $status = 0;
        }
        $conn->close();
        //Update Default Language as per Store Language
      	$LangCurrencycode = (array)json_decode($this->getStoreLangCurrency(1, $dom));		
		$lanuageCode = $LangCurrencycode['language'];
		$currencyCode = $LangCurrencycode['currency'];
		$languageStatus = $this->setDefaultLanguage('tool', '1', $lanuageCode, $currencyCode);
        $response = array("proceed_next" => true, "message" => "");
	    return $response;exit();

	}

	protected function getXePath()
	{
	    $requestUrl = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	    $protocol = strchr($requestUrl, '//', true);
	    $xePathDomain = str_replace($protocol . '//', '', $requestUrl);
	    $xePathDomain = strchr($xePathDomain, '/', true);
	    $xePath = str_replace(array('www.', '.'), array('', '_'), $xePathDomain);
	    return array($xePathDomain, $xePath);
	}

	protected function checkAdminCreated($dom){
		$adminStatus = false;
		$host = $dom->getElementsByTagName('host')->item(0)->nodeValue;
		$user = $dom->getElementsByTagName('dbuser')->item(0)->nodeValue;
		$password = $dom->getElementsByTagName('dbpass')->item(0)->nodeValue;
		$dbName = $dom->getElementsByTagName('dbname')->item(0)->nodeValue;
		$port = $dom->getElementsByTagName('port')->item(0)->nodeValue;
		 $status = 1;
		try {
	        error_reporting(0);
	        if (isset($port) && $port != '') {
	            $conn = new mysqli($host, $user, $password, $dbName, $port);
	        } else {
	            $conn = new mysqli($host, $user, $password);
	            $conn->select_db($dbName);
	        }
	    } catch (Exception $e) {
	        $error = "- Database Connection failed. Error: " . $e->getMessage() . "\n";
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
	        $response = array("proceed_next" => false, "message" => $error);
	        return $response;exit();
	    }
	    $sqlUser = "SELECT * FROM admin_users";
        $queryStatusUser = $conn->query($sqlUser);
        if ($queryStatusUser->num_rows > 0) {
        	$adminStatus = true;
        }
        return $adminStatus;
	}

	/*
	@ Purpose : For those server which doesn't provide 'apache_request_headers' method in their request header.
	 */
    protected function apache_request_headers()
    {
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                $out[$key] = $value;
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

	/*
	@ Purpose : Recursively copy all the files & folders
	@ Param : SourceFolder and DestinationFolder with path
	 */
	protected function recurse_copy($src, $dst)
	{
	    $dir = opendir($src);
	    @mkdir($dst);
	    while (false !== ($file = readdir($dir))) {
	        if (($file != '.') && ($file != '..')) {
	            if (is_dir($src . '/' . $file)) {
	                $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
	            } else {
	                @copy($src . '/' . $file, $dst . '/' . $file);
	            }
	        }
	    }
	    closedir($dir);
	}

	/*
	@ Purpose : To log errors during installation
	@ Param : Text(what to log),append(whether append or replace),fileName(where to log)
	 */
	protected function xe_log($text, $append = true, $fileName = '')
	{
	    $file = DOCABSPATH . 'xetool_log.log';
	    if ($fileName) {
	        $file = $fileName;
	    }

	    // Append the contents to the file to the end of the file and the LOCK_EX flag to prevent anyone else writing to the file at the same time
	    if ($append) {
	        @file_put_contents($file, $text . PHP_EOL, FILE_APPEND | LOCK_EX);
	    } else {
	        @file_put_contents($file, $text);
	    }
	}

	/*
	@ Purpose : To run the basic script required for our designer tool
	@ Param : sqlFileName with path, dbConnectionObject
	 */
	protected function run_sql_file($filename, $conn)
	{
	    $errorMsg = "";
	    $status = 1;
	    $errorSql = '';

	    $commands = @file_get_contents($filename); //load file
	    //delete comments
	    $lines = explode("\n", $commands);
	    $commands = '';
	    foreach ($lines as $line) {
	        $line = trim($line);
	        if ($line && !$this->startsWith($line, '--') && !$this->startsWith($line, '/*')) {
	            $commands .= $line . "\n";
	        }
	    }
	    $commands = explode(";", $commands); //convert to array
	    //run commands
	    $total = $success = 0;
	    foreach ($commands as $command) {
	        if (trim($command)) {
				$sqlStatus = $conn->query($command);
				if($sqlStatus == false){
					$errorSql.= $command."\n";
				}
	        }
	    }
	    if ($errorSql !== '' && strlen($errorSql) > 0) {
	        $errorMsg = "\n" . 'Following SQL queries failed to run:' . "\n" . $errorSql;
	        $this->xe_log($errorMsg);
	        $status = 0;
	    }
	    return array($status, $errorMsg);
	}
	/*
	@ This is a helper protected function to the above function
	 */
	protected function startsWith($haystack, $needle)
	{
	    $length = strlen($needle);
	    return (substr($haystack, 0, $length) === $needle);
	}

	protected function sanitizePath($path)
	{
	    return $path = str_replace("/", DIRECTORY_SEPARATOR, $path);
	}

	protected function storeOperation($dummyData){
		$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
		$dom = new DomDocument();
		$dom->load($configXMLpath) or die("Unable to load xml");
		$baseURL = $this->getNewXEURL();
		$basePATH = $this->getNewXEpath();
		$this->doAdminSettings($dom, $dummyData, $basePATH);
		$storeWork = $this->storeInstallProcess($dom, $baseURL, $basePATH, $dummyData);
		return $storeWork;
	}

	protected function doAdminSettings($dom, $data, $basePath){
		$settingJson = $basePath."assets/settings/stores/1/settings.json";
		$settingContent = file_get_contents($settingJson);
		$host = $dom->getElementsByTagName('host')->item(0)->nodeValue;
		$user = $dom->getElementsByTagName('dbuser')->item(0)->nodeValue;
		$password = $dom->getElementsByTagName('dbpass')->item(0)->nodeValue;
		$dbName = $dom->getElementsByTagName('dbname')->item(0)->nodeValue;
		$port = $dom->getElementsByTagName('port')->item(0)->nodeValue;
		 $status = 1;
		try {
	        error_reporting(0);
	        if (isset($port) && $port != '') {
	            $conn = new mysqli($host, $user, $password, $dbName, $port);
	        } else {
	            $conn = new mysqli($host, $user, $password);
	            $conn->select_db($dbName);
	        }
	    } catch (Exception $e) {
	        $error = "- Database Connection failed. Error: " . $e->getMessage() . "\n";
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
	        $response = array("proceed_next" => false, "message" => $error);
	        return $response;exit();
	    }
	    /*
        @ Purpose : Code to update enabled print profiles
         */
        $printProfileSql = "UPDATE print_profiles SET is_disabled='0' WHERE xe_id = ".$data['print_methods'][0];
        if (count($data['print_methods']) > 1) {
	        foreach (array_slice($data['print_methods'],1) as $methodID) {
	        	$printProfileSql = $domainStoreSql . " OR xe_id = ".$methodID;
	        }
        }
        $queryStatusPP = $conn->query($printProfileSql);
        if ($queryStatusPP == false) {
            $errorMsg .= "- Data not inserted to print_profiles table. \n";
            $status = 0;
        }
        /*
        @ Purpose : Code to update Theme color and theme id
         */
        $themeColorSQL = "UPDATE settings SET setting_value = '".$data['themeCol']."' WHERE setting_key = 'theme_color'";
        $queryThemeColor = $conn->query($themeColorSQL);
        if ($queryThemeColor == false) {
            $errorMsg .= "- Theme color could not be updated. \n";
            $status = 0;
        }

        $layoutQRY = "UPDATE settings SET setting_value = ".$data['themeID']." WHERE setting_key = 'theme_layouts'";
        $queryTheme = $conn->query($layoutQRY);
        if ($queryTheme == false) {
            $errorMsg .= "- Theme layout could not be updated. \n";
            $status = 0;
        }

        $conn->close();
        if (!empty($settingContent)) {
        	$colorContent = '"theme_color": "'.$data['themeCol'].'"';
        	$layOutContent = '"theme_layouts": '.(int)$data['themeID'];
        	$mewSettingData = str_replace('"theme_color": "#f50000"', $colorContent, $settingContent);
        	$newSettingData = str_replace('"theme_layouts": 1', $layOutContent, $mewSettingData);
        	file_put_contents($settingJson, $newSettingData);
        	$this ->replceInAdminFile($data['themeCol'], $basePath);
        }
        if ($status == 0) {
	        $response = array("proceed_next" => false, "message" => "ERROR_IN_DB_UPDATE");
        }else $response = array("proceed_next" => true, "message" => "");
		return $response;
	}

	private function replceInAdminFile($themeColor, $basePath)
    {
    	$themeHoverColor = $themeBackgroundColor = $themeColor;
    	$rvnNumber = "11";
        $staticFolderPath = $basePath . 'static/css/';
        $fileNames = scandir($staticFolderPath);
        foreach ($fileNames as $fileName) {
            list($firstWord) = explode('.', $fileName);
            if ($firstWord == "main") {
                $filePath = $staticFolderPath . '/' . $fileName;
                if ($themeColor != "") {
                    $fileContents = file_get_contents($filePath);
                    $colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.imageWrap-box:hover .nf{color:')), ';}'));
                    $colorCode = $colorData[1];
                    $fileContents = str_replace($colorCode, $themeColor, $fileContents);
                    file_put_contents($filePath, $fileContents);
                }
                if ($themeHoverColor != "") {
                    $fileContents = file_get_contents($filePath);
                    $colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.dropdown-item.active{background-color:')), ';}'));
                    $colorCode = $colorData[1];
                    $fileContents = str_replace($colorCode, $themeHoverColor, $fileContents);
                    file_put_contents($filePath, $fileContents);
                }
                if ($themeBackgroundColor != "") {
                    $fileContents = file_get_contents($filePath);
                    $colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.btn-success:focus,.btn-success:hover{background-color:')), ';}'));
                    $colorCode = $colorData[1];
                    $fileContents = str_replace($colorCode, $themeBackgroundColor, $fileContents);
                    file_put_contents($filePath, $fileContents);
                }
                // Adding RVN Number in index.html page for fixing cache issue
                $indexFile = $basePath . '/index.html';
                $getIndexContents = htmlspecialchars(file_get_contents($indexFile));
                $stringAfterFile = substr($getIndexContents, strpos($getIndexContents, $fileName));
                $fileWithRvn = substr($stringAfterFile, 0, strpos($stringAfterFile, ' '));
                $getIndexContents = str_replace($fileWithRvn, $fileName . '?rvn=' . $rvnNumber . '" ', $getIndexContents);
                file_put_contents($indexFile, htmlspecialchars_decode($getIndexContents));
            }
        }
    }
	
	protected function getToolURL(){
		$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
		$dom = new DomDocument();
		$dom->load($configXMLpath) or die("Unable to load xml");
		return $this->getDummyProductURL($dom);
	}
	
	protected function getXetoolDir(){
		$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
		$dom = new DomDocument();
		$dom->load($configXMLpath) or die("Unable to load xml");
		return $xetoolDir = $dom->getElementsByTagName('xetool_dir')->item(0)->nodeValue;
	}

	protected function setDefaultLanguage($type,$storeId,$lanuageCode,$currencyCode){    	
	    $configXMLpath = $this->getNewXEpath().XECONFIGXML;
		$dom = new DomDocument();
		$dom->load($configXMLpath) or die("Unable to load xml");
		$host = $dom->getElementsByTagName('host')->item(0)->nodeValue;
		$user = $dom->getElementsByTagName('dbuser')->item(0)->nodeValue;
		$password = $dom->getElementsByTagName('dbpass')->item(0)->nodeValue;
		$dbName = $dom->getElementsByTagName('dbname')->item(0)->nodeValue;
		$port = $dom->getElementsByTagName('port')->item(0)->nodeValue;
		try {
	        if (isset($port) && $port != '') {
	            $conn = new mysqli($host, $user, $password, $dbName, $port);
	        } else {
	            $conn = new mysqli($host, $user, $password);
	            $conn->select_db($dbName);
	        }
		} catch (Exception $e) {
	        $error = "- Database Connection failed. Error: " . $e->getMessage() . "\n";
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
	        $response = array("proceed_next" => false, "message" => $error);
	        return $response;exit();
	    }
		//Compare language in Language list Json
    	$languageListPathJson = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH)."install/languageList.json";
		$languageListContent=file_get_contents($languageListPathJson);
		$languageListArray = json_decode($languageListContent, true);
		$languageName="";
		foreach( $languageListArray as $jsonval => $key){
			if($jsonval == $lanuageCode)
			{
				$languageName=$key;
			}
		}
		if($languageName!=""){
			$set_is_default = "Update `languages` set `is_default`= '0' where `type` = '$type' and `store_id` = '$storeId' ";
			if(!$conn->query($set_is_default))
			{
			 echo $conn -> error;
			}
			$languageStoreSql = "Update `languages` set `is_enable`= '1',`is_default`= '1' where `type` = '$type' and `store_id` = '$storeId' and `name` = '$languageName' ";
			if(!$conn->query($languageStoreSql))
			{
			 echo $conn -> error;
			}
		}
		//Get Detail from Currency json then compre with store default currency
		$settingLocation = $this->getNewXEpath(). 'assets/settings/stores/' . $storeId;
		$currencyFilePath = $settingLocation . '/currencies.json';
		$currencyContent=file_get_contents($currencyFilePath);
		$currencyArray = json_decode($currencyContent, true);
		$args=[];
		foreach( $currencyArray as $jsonval=>$key)
		{
			if($key['code']==$currencyCode)
			{
				$args=$key;
			}
		}
		if(!empty($args)){
	    	$sql_currency_val= [
				'currencyId' => $args['xe_id'],
				'currency' => $args['symbol'],
				'separator' => '.',
				'post_fix' => $args['code'],
				'is_postfix_symbol' => false,
			];
			$sql_currency_val=addslashes(json_encode($sql_currency_val,JSON_PRETTY_PRINT));
			$languageStoreSql="update `settings` set `setting_value` ='$sql_currency_val' where `setting_key` ='currency'";
		   if(!$conn->query($languageStoreSql)){
				echo $conn -> error;
			}
		}
		if($languageName != '' || !empty($args) ){
			$response =  $this->writeOnJsonFile($storeId, $languageName, $args);
		}
		return $response;
	}

	protected function writeOnJsonFile($storeId,$languageName,$args) {
	    $settingLocation =$this->getNewXEpath();
	    $settingLocation = $settingLocation . 'assets/settings/stores/' . $storeId;
	   	$jsonFilePath = $settingLocation . '/settings.json';
		$settingJsonContent=file_get_contents($jsonFilePath);
		$settingJsonArray = json_decode($settingJsonContent, true);
		if($languageName!=''){
			$languageImg=strtolower($languageName).".png";
			$settingJsonArray['lanuage']['default']['name'] = $languageName;
			$flagPath =  $settingJsonArray['lanuage']['default']['flag'];
			$flagPath = str_replace('english.png',$languageImg,$flagPath);
			$settingJsonArray['lanuage']['default']['flag'] = $flagPath;
			$settingJsonArray['lanuage']['lang_list'][0]['name'] = $languageName;
			$settingJsonArray['lanuage']['lang_list'][0]['flag'] = $flagPath; // Here Update complete of language change'
		}
		if(!empty($args)){
		    $settingJsonArray['currency']['value'] = $args['symbol'];
			$settingJsonArray['currency']['post_fix'] = $args['code'];
			$settingJsonArray['currency']['unicode_character'] = utf8_encode($args['symbol']);
		}
		$settingJsonArray=json_encode($settingJsonArray, JSON_PRETTY_PRINT);
	    file_put_contents($jsonFilePath, $settingJsonArray);
	    return true;
	}
}

?>