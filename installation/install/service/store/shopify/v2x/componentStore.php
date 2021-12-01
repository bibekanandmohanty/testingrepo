<?php
$storePath = ROOTABSPATH ."shopify".DS."lib".DS."config.php";
$storePath = str_replace("//", "/", $storePath);
//echo $storePath; exit;
// error_reporting(0);
require_once $storePath;
class StoreComponent
{
	protected function checkStoreCredential($data){
		extract($data);
		$errorMsg = '';
		try {
	        $shopify = new ShopifyClient($shop . '.myshopify.com', $apipass, $apiuser, $secretkey);
	        $res = $shopify->call('GET', '/admin/products.json');
	        if (is_array($res)) {
	            $status = 1; //'success';
	        }
	    } catch (Exception $e) {
	        $status = 0;
	        $errorMsg = 'AUTHENTICATION_ERROR';
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error :'  . $e->getMessage() . "\n");
	    }
	    return array($status, $errorMsg);
	}

	protected function checkStoreCredWrite($dom){
		$status = false;
		if ($dom->getElementsByTagName('shop')->item(0)->nodeValue != "" && $dom->getElementsByTagName('apiuser')->item(0)->nodeValue != "" && $dom->getElementsByTagName('apipass')->item(0)->nodeValue != "" && $dom->getElementsByTagName('secretkey')->item(0)->nodeValue != "") {
			$status = true;
		}
		return $status;
	}

	protected function storeInstallProcess($dom, $baseURL, $basePATH, $dummyData){
        $baseURL =$dom->getElementsByTagName('api_url')->item(0)->nodeValue;
        $aoiUSER= $dom->getElementsByTagName('apiuser')->item(0)->nodeValue;
        $apiPASS= $dom->getElementsByTagName('apipass')->item(0)->nodeValue;
        $appSECRET= $dom->getElementsByTagName('secretkey')->item(0)->nodeValue;
        $shop = $dom->getElementsByTagName('shop')->item(0)->nodeValue;
        
        //below 3 should be shifted to store.inc.php after qa testing
        $domain = $shop . '.myshopify.com';
        $shopURL= 'https://' . $domain;
        $shopAPIURL= $shopURL . '/admin/oauth/access_token';
        $shopifyPath = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH).SETUPFOLDERNAME.DS."shopify".DS;
        $themeFolder = "theme".DS;

        $shopify = new ShopifyClient($domain, $apiPASS, $aoiUSER, $appSECRET);
		$this->PutUrlInAssets($shopifyPath.$themeFolder."assets/xe_app.js.liquid", $baseURL);
		$this->PutUrlInAssets($shopifyPath.$themeFolder."snippets/xe_script.liquid", $baseURL);
		$this->PutUrlInAssets($shopifyPath.$themeFolder."templates/page.designer.liquid", $baseURL);
		$this->addAssets2theme($shopify, $shopifyPath.$themeFolder);
		$this->createCollection($shopify, 'show-in-designer', true);
		$this->createCollection($shopify, 'customized', false);
		$this->createCollection($shopify, 'all', false);
		if ($dummyData['setup_type'] == "auto") {
			$this->createDummyProduct($shopify);
			$this->assignProductToCollection($shopify, "");
		}else{
			$this->createSampleProducts($dom, $shopify, $dummyData['products']);
		}
		$this->createWebhooks($shopify, 'orders\/create', 'webhook_order_create', $baseURL);
		$this->createWebhooks($shopify, 'orders\/updated', 'webhook_order_update', $baseURL);
		$this->createWebhooks($shopify, 'products\/updated', 'webhook_product_update', $baseURL);
		$returnValue = $this->checkProductCreationStatus($shopify);
		$returnValue['0'] = 1;
		if($returnValue['0'] == 0){
			$response = array("proceed_next" => false, "message" => "DUMMY_PRODUCT_NOT_CREATED");
	 	}else{
			$response = array("proceed_next" => true, "message" => "DUMMY_PRODUCT_CREATED");
		}
		return $response;
	}

	/*
	- Name : checkCreateDummyProduct
	- it will check if dummy produc has been created or not
	- Return status created or not
	 */
	private function checkCreateDummyProduct($shopify, $prodHandle)
	{
	    $dummyProdID = 0;
	    $prodHandle = str_replace(" ", "-", $prodHandle);
	    $products = $shopify->call('GET', '/admin/products.json?handle='.$prodHandle);
	    if (!empty($products)) {
	        foreach ($products as $prod) {
	            if ($prod['handle'] == "imprintNext-tshirt" && ($prod['published_scope'] == "web" || $prod['published_scope'] == "global")) {
	                $dummyProdID = $prod['id'];
	            }
	        }
	    }
	    return $dummyProdID;
	}

	/*
	- Name : checkCreateCollection
	- it will check if custom collection has been created or not
	- Return status created or not
	 */
	private function checkCreateCollection($shopify, $colName, $isCustom)
	{
	    $thisColID = 0;
	    if ($isCustom) {
	        $collections = $shopify->call('GET', '/admin/custom_collections.json?fields=id,handle');
	    } else {
	        $collections = $shopify->call('GET', '/admin/smart_collections.json?fields=id,handle');
	    }

	    foreach ($collections as $col) {
	        if ($col['handle'] == $colName) {
	            $thisColID = $col['id'];
	        }
	    }
	    return $thisColID;
	}

	/*
	- Name : checkCreateWebhooks
	- it will check if webhooks has been created or not
	- Return status created or not
	 */
	private function checkCreateWebhooks($shopify, $event, $name, $baseURL)
	{
	    $thisHookID = 0;
	    $webhooks = $shopify->call('GET', '/admin/webhooks.json');
	    $webTopic = str_replace('\/', '/', $event);
	    $webURL = $baseURL . "shopify/lib/" . $name . ".php";
	    foreach ($webhooks as $wbhk) {
	        if ($wbhk['topic'] == $webTopic && $wbhk['address'] == $webURL) {
	            $thisHookID = $wbhk['id'];
	        }
	    }
	    return $thisHookID;
	}

	/*
	- Name : createDummyProduct
	- it will create a dummy product in store
	- Return procuct details in json
	 */
	private function createDummyProduct($shopify)
	{
	    $msg = '';
	    $status = 0;
	    $prodCheck = $this->checkCreateDummyProduct($shopify, "imprintNext-tshirt");
	    if ($prodCheck == 0) {
	        $prodHndlName = "imprintNext-tshirt";
	        $products_array = array(
	            "product" => array(
	                "title" => "imprintNext Tshirt",
	                "body_html" => "<strong>This is a dummy product, created during imprintNext installation.</strong>",
	                "vendor" => "test",
	                "product_type" => "imprintNext",
	                "handle" => $prodHndlName,
	                "published" => true,
	                "options" => array(
	                    array(
	                        "name" => "size",
	                        "position" => 1,
	                    ),
	                    array(
	                        "name" => "color",
	                        "position" => 2,
	                    ),
	                ),
	                "variants" => array(
	                    array(
	                        "option1" => "XL",
	                        "option2" => "purple",
	                        "sku" => "imprintNext",
	                        "price" => 20.00,
	                        "grams" => 200,
	                        "taxable" => false,
	                    ),
	                ),
	                "images" => array(
	                    array(
	                        "src" => "https://cdn.shopify.com/s/files/1/1284/7279/products/s3_purple_front_1024x1024.png",
	                        "position" => 1,
	                    ),
	                ),
	                "image" => array(
	                    "src" => "https://cdn.shopify.com/s/files/1/1284/7279/products/s3_purple_front_1024x1024.png",
	                    "position" => 1,
	                ),
	            ),
	        );
	        $addProduct = $shopify->call('POST', '/admin/products.json', $products_array);
	        $variant_id = $addProduct['variants'][0]['id'];
	        $variantArr = array(
	            "variant" => array(
	                "id" => $variant_id,
	                "image_id" => $addProduct['image']['id'],
	            ),
	        );
	        $addImg2var = $shopify->call('PUT', '/admin/variants/' . $variant_id . '.json', $variantArr);
	        if (is_array($addImg2var)) {
	            $msg = "Dummy Product added.";
	            return array($status,$msg);
	        }
	    }
	}

	/*
	- Name : createCollection
	- it will create a collection in store and assign the dummy product to it
	- Return collection details in json
	 */
	private function createCollection($shopify, $name, $isCustom)
	{
	    $msg = '';
	    $status = 0;
	    $colID = $this->checkCreateCollection($shopify, $name, $isCustom);
	    if ($colID == 0) {
	        $colHndlName = ($name == "Show in Designer" ? "show-in-designer" : $name);
	        if ($isCustom) {
	            $collectionArray = array(
	                "custom_collection" => array(
	                    "title" => $name,
	                    "handle" => $colHndlName,
	                    "body_html" => "<strong>The products under this collection are allowed to be shown in designer tool</strong>",
	                    "published_scope" => true,
	                ));
	        } else {
	            $basicDataArray = array(
	                "title" => $name,
	                "handle" => $colHndlName,
	            );
	            if ($name == 'customized') {
	                $colAppendArray = array(
	                    "body_html" => "<strong>All products created to add customized price are included in this collection. This will remain hidden and should not be deleted. Product under this collection will be deleted time to time and will not be allowed for 'Add to cart' or 'customize'</strong>",
	                    "published" => false,
	                    "rules" => array(
	                        array(
	                            "column" => "tag",
	                            "relation" => "equals",
	                            "condition" => "customized",
	                        )),
	                );
	            } elseif ($name == 'all') {
	                $colAppendArray = array(
	                    "body_html" => "<strong>This collection removes user created duplicate products from product catalog.</strong>",
	                    "published_scope" => "global",
	                    "rules" => array(
	                        array(
	                            "column" => "vendor",
	                            "relation" => "not_equals",
	                            "condition" => "imprintNext",
	                        )),
	                );

	            }
	           $collectionArray = array("smart_collection" => array_merge($basicDataArray, $colAppendArray));
	        }
	        try {
	            if ($isCustom) {
	                $createCollection = $shopify->call('POST', '/admin/custom_collections.json', $collectionArray);
	            } else {
	                $createCollection = $shopify->call('POST', '/admin/smart_collections.json', $collectionArray);
	            }
	        } catch (Exception $e) {
	            $msg = 'collection creation error: ' . $e->getMessage();
	            $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error : ' . $msg . ' : ' . $e->getMessage() . "\n");
	        }

	        if (is_array($createCollection)) {
	            $msg = $name . " collection has been created.";
	            $status = 1;
	            return array($status,$msg);
	        }
	    }
	}

	/*
	- Name : assignProductToCollection
	- it will assign the dummy product to the colletion
	- Return status assigned or not
	 */
	private function assignProductToCollection($shopify, $dummyProdID)
	{
	    $msg = '';
	    $status = 0;
	    if ($dummyProdID == "") {
	    	$dummyProdID = $this->checkCreateDummyProduct($shopify, "imprintNext-tshirt");
	    }
	    $designColID = $this->checkCreateCollection($shopify, "show-in-designer", true);
	    if ($dummyProdID > 0 && $designColID > 0) {
	        $checkCol = $shopify->call('GET', '/admin/collects.json?product_id='.$dummyProdID);
	        $dummyCols = array_column($checkCol, 'collection_id');
	        if (!in_array($designColID, $dummyCols)) {
	            $collectArray = array(
	                "collect" => array(
	                    "product_id" => $dummyProdID,
	                    "collection_id" => $designColID,
	                ));
	            $addProductInCol = $shopify->call('POST', '/admin/collects.json', $collectArray);
	        }
	    }
	    if (is_array($addProductInCol)) {
	        $status = 1;
	        $msg = 'Dummy Product has been added to Show in Designer colection';
	    } 
	    return array($status,$msg);

	}

	/*
	- Name : createWebhooks
	- it will create webhooks to store
	- Return status created or not
	 */
	private function createWebhooks($shopify, $event, $name, $baseURL)
	{
	    $msg = '';
	    $status = 0;
	    $webhookID = $this->checkCreateWebhooks($shopify, $event, $name, $baseURL);
	    if ($webhookID == 0) {
	        $webhookPATH = $baseURL . "shopify/lib/" . $name . ".php";
	        $webhook_array = array(
	            "webhook" => array(
	                "topic" => $event,
	                "address" => $webhookPATH,
	                "format" => "json",
	            ),
	        );
	        error_reporting(0);
	        try {
	            $addWebhook = $shopify->call('POST', '/admin/webhooks.json', $webhook_array);
	            if (!empty($addWebhook) && isset($addWebhook['id'])) {
	                $status = 1;
	                $msg = $name.' webhook has been created';
	                return array($status,$msg);
	            }
	        } catch (Exception $e) {
	            $msg = 'Webhook error: ' . $e->getMessage();
	            $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error : ' . $msg . ' : ' . $e->getMessage() . "\n");
	        }
	    } else {
	        $msg = $event .'Webhook was already created for this store.';
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error : ' . $msg . "\n");
	    }
	}

	/*
	- Name : checkProductCreationStatus
	- it will check if Step-3 has been completed or not
	- Return status success or error
	 */
	private function checkProductCreationStatus($shopify)
	{
	    $msg = " - Dummy product is not yet created. \n";
	    $status = 0;
	    $dummyProdChk = $this->checkCreateDummyProduct($shopify, "imprintNext-tshirt");
	    if ($dummyProdChk > 0) {
	        $msg = "";
	        $status = 1;
	    }
	        return array($status,$msg);
	}

	private function PutUrlInAssets($file, $baseURL)
	{
	    $path = DS.$file;
	    if (file_exists($path)) {
	        @chmod($path, 0777);
	        $settingStr = @file_get_contents($path);
	        $settingStr = str_replace("XEPATH", $baseURL, $settingStr);
	        @file_put_contents($path, $settingStr);
	    }
	}
	/**
	 *
	 * @param shopify object
	 * @return json
	 */
	private function addAssets2theme($shopify, $themeFolder)
	{
	    $themes = $shopify->call('GET', '/admin/themes.json');
	    $curThemeID = 0;
	    foreach ($themes as $thm) {
	        if ($thm['role'] == "main") {
	            $curThemeID = $thm['id'];
	        }
	    }
	    $this->uploadTheme($shopify, 'assets/', $curThemeID, $themeFolder);
	    $this->uploadTheme($shopify, 'templates/', $curThemeID, $themeFolder);
	    $this->uploadTheme($shopify, 'snippets/', $curThemeID, $themeFolder);
	}
	private function uploadTheme($shopify, $directory, $curThemeID, $themeFolder)
	{
	    $fileExists = false;
	    $base = DS.$themeFolder;
	    $baseURL = $this->getPKGURL()."shopify/theme/";
	    $files = glob($base . $directory . "*.*");
	    foreach ($files as $file) {
	        $fileName = substr(strrchr($file, "/"), 1);
	        try {
	            $check = $shopify->call('GET', '/admin/themes/' . $curThemeID . '/assets.json?asset[key]=' . $directory . $fileName);
	            if ($check) {
	                $dltFile = $shopify->call('DELETE', '/admin/themes/' . $curThemeID . '/assets.json?asset[key]=' . $directory . $fileName);
	            }
	        } catch (Exception $e) {
	            $fileExists = true;
	            $file_array = array(
	                "asset" => array(
	                    "key" => $directory . $fileName,
	                    "src" => $baseURL . $directory . $fileName,
	                ),
	            );
	            $addFile = $shopify->call('PUT', '/admin/themes/' . $curThemeID . '/assets.json', $file_array);
	        }
	        if (!$fileExists) {
	            $file_array = array(
	                "asset" => array(
	                    "key" => $directory . $fileName,
	                    "src" => $baseURL . $directory . $fileName,
	                ),
	            );
	            $addFile = $shopify->call('PUT', '/admin/themes/' . $curThemeID . '/assets.json', $file_array);
	        }
	    }
	}

	protected function getPKGURL(){
		$baseURLData = $this->getBaseUrl();
		$baseURL = $baseURLData[1];
		$inkXEpkgDIR = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH);
		$newXEtoolURL = $baseURL.INSTALLFOLDER. DS;
		return $newXEtoolURL;
	}

	protected function getDummyProductURL($dom){
		$shop = $dom->getElementsByTagName('shop')->item(0)->nodeValue;
        $domain = $shop . '.myshopify.com';
        $shopURL= 'https://' . $domain;
        return $shopURL."/collections/all";
	}

	private function createSampleProducts($dom, $shopify, $prodArr){
		// $baseURL = DEMOAPIURL."decorations/";
		foreach ($prodArr as $productID) {
			// $productData = $this->getContentByCURL($baseURL.$productID);
			$productData = file_get_contents(DUMMYDATADIR."product_".$productID.".json");
			$productData = json_decode($productData, true);
			// print_r($productData);echo "<br>";
			$this->createDemoProduct($dom, $shopify,$productData);
		}
	}

	private function createDemoProduct($dom, $shopify,$productData){
		$productTitle = $productData['data']['product_name'];
		$thisProductID = $this->checkCreateDummyProduct($shopify, $productTitle);
		if ($thisProductID == 0) {
			$prodHndlName = str_replace(' ', '-', $productTitle);
			$optionsArr = array();
			$optionNo = 1;
			$hasSize = false;
			$hasColor = false;
			if (array_key_exists('size', $productData['data'])) {
				$optionsArr[] = array("name" => "size","position" => $optionNo);
				$optionNo++;
				$hasSize = true;
			}
			if (array_key_exists('color', $productData['data'])) {
				$optionsArr[] = array("name" => "color","position" => $optionNo);
				$hasColor = true;
			}
			$variantArr = array();
			if ($hasSize && $hasColor) {
				foreach ($productData['data']['size'] as $size) {
					foreach ($productData['data']['color'] as $color) {
						$thisVar = array("option1" => $size['name'], "option2" => $color['name'], "sku" => "imprintNext_demo", "price" => 20.00, "grams" => 200,"taxable" => false, "inventory_policy" => "continue");
						$variantArr[] = $thisVar;
					}
				}
			}elseif ($hasSize && !$hasColor) {
				foreach ($productData['data']['size'] as $size) {
					$thisVar = array("option1" => $size['name'], "sku" => "imprintNext_demo", "price" => 20.00, "grams" => 200,"taxable" => false, "inventory_policy" => "continue");
					$variantArr[] = $thisVar;

				}
			}elseif (!$hasSize && $hasColor) {
				$thisVar = array("option1" => $color['name'], "sku" => "imprintNext_demo", "price" => 20.00, "grams" => 200, "taxable" => false, "inventory_policy" => "continue");
				$variantArr[] = $thisVar;
			}
			$productImages = array();
			foreach ($productData['data']['store_images'] as $position => $image) {
				$productImages[] = array("src" => $image['src'], "position" => $position+1);
			}
	        $products_array = array(
	            "product" => array(
	                "title" => $productTitle,
	                "body_html" => "<strong>This is a dummy product, created during imprintNext installation.</strong>",
	                "vendor" => "test",
	                "product_type" => "imprintNext_demo",
	                "handle" => $prodHndlName,
	                "published" => true,
	                "options" => $optionsArr,
	                "variants" => $variantArr,
	                "images" => $productImages,
	                "image" => array(
	                    "src" => $productImages[0]['src'],
	                    "position" => 1,
	                )
	            )
	        );
	        $addProduct = $shopify->call('POST', '/admin/products.json', $products_array);
	        $thisProductID = $addProduct['id'];
		}
		$this->assignProductToCollection($shopify, $thisProductID);
		$this->setBoundaryForDummyProduct($dom, $thisProductID, $productData['data']);
	}

	private function getContentByCURL($Url) {
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $Url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;
	}

	private function setBoundaryForDummyProduct($dom, $newProductID, $ParentData) {
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
	        $response = array("proceed_next" => false, "message" => "DATABASE_CONN_ERROR");
	        return $response;exit();
	    }
	    // Insert product id into product_setting table and get xe_id
	    $insertProductSetting = "INSERT INTO product_settings(product_id,is_variable_decoration,is_ruler,is_crop_mark,is_safe_zone,crop_value,safe_value,is_3d_preview,3d_object_file,3d_object,scale_unit_id,store_id) VALUES(".$newProductID."," . $ParentData['is_variable_decoration'] ."," . $ParentData['is_ruler']. "," . $ParentData['is_crop_mark']. "," . $ParentData['is_safe_zone']. "," . $ParentData['crop_value']. "," . $ParentData['safe_value']. "," . $ParentData['is_3d_preview']. ",'" . $ParentData['3d_object_file']. "','" . $ParentData['3d_object']. "'," . $ParentData['scale_unit_id']. " , 1)";
        $queryStatusPS = $conn->query($insertProductSetting);
        $prodSetID = mysqli_insert_id($conn);
        if ($queryStatusPS == false) {
            $errorMsg .= "- Data not inserted to domain_store_rel table. \n";
            $status = 0;
        }
        //Assign product image
        $productimageQRY = "INSERT INTO `product_image_settings_rel` (`product_setting_id`, `product_image_id`) VALUES (".$prodSetID."," . $ParentData['product_image_id'] .")";
        $queryRun = $conn->query($productimageQRY);
        // insert print profile and product id relationship
        $insertRelation = "INSERT INTO print_profile_product_setting_rel(print_profile_id, product_setting_id) VALUES";
        foreach ($ParentData['print_profiles'] as $key => $rel) {
        	if ($key > 0) {
        		$insertRelation .= ", "; 
        	}
        	 $insertRelation .= "(".$rel['id']."," . $prodSetID. ")";
        }
        $queryStatusPPM = $conn->query($insertRelation);

        // Insert sides into product_sides table and get side id
        foreach ($ParentData['sides'] as $side) {
		    $insertSideSetting = "INSERT INTO product_sides(product_setting_id,side_name,side_index,product_image_dimension,is_visible,product_image_side_id) VALUES(".$prodSetID.",'" . $side['name'] ."','" . $side['index']. "','" . $side['dimension']. "'," . $side['is_visible']. "," . $side['image']['id']. ")";
	        $queryStatusS = $conn->query($insertSideSetting);
	        $sideSetID = mysqli_insert_id($conn);
	        if ($queryStatusS == false) {
	            $errorMsg .= "- Data not inserted to domain_store_rel table. \n";
	            $status = 0;
	        }
	        $setting = $side['decoration_settings'][0];
	        // Insert data for each sides decoration settings
	        $insertDecoSetting = "INSERT INTO product_decoration_settings(product_setting_id,product_side_id,name,dimension,print_area_id,sub_print_area_type,custom_min_height,custom_max_height,custom_min_width,custom_max_width,is_border_enable,is_sides_allow) VALUES(".$prodSetID."," . $sideSetID .",'" . $setting['name']. "','" . $setting['dimension']. "','" . $setting['print_area_id']. "','" . $setting['sub_print_area_type']. "','" . $setting['min_height']."','" . $setting['max_height']."','" . $setting['min_width']."','" . $setting['max_width']."','" . $setting['is_border_enable']."','" . $setting['is_sides_allow']. "')";
	        $queryStatusDS = $conn->query($insertDecoSetting);
	        $decoSetID = mysqli_insert_id($conn);

	        $insertMethodSetRel = "INSERT INTO print_profile_decoration_setting_rel(print_profile_id, decoration_setting_id) VALUES";
	        foreach ($setting['print_profiles'] as $key => $rel) {
	        	if ($key > 0) {
	        		$insertMethodSetRel .= ", "; 
	        	}
	        	 $insertMethodSetRel .= "(".$rel['id']."," . $decoSetID. ")";
	        }
	        $queryStatusPDM = $conn->query($insertMethodSetRel);
        }

	}

	public function getStoreLangCurrency($storeId, $dom){
	    $aoiUSER= $dom->getElementsByTagName('apiuser')->item(0)->nodeValue;
        $apiPASS= $dom->getElementsByTagName('apipass')->item(0)->nodeValue;
        $appSECRET= $dom->getElementsByTagName('secretkey')->item(0)->nodeValue;
        $shop = $dom->getElementsByTagName('shop')->item(0)->nodeValue;
        
        //below 3 should be shifted to store.inc.php after qa testing
        $domain = $shop . '.myshopify.com';
        $shopURL= 'https://' . $domain;
        $shopAPIURL= $shopURL . '/admin/oauth/access_token';
        $shopifyPath = str_replace(SETUPFOLDERNAME.DS, '', ROOTABSPATH).SETUPFOLDERNAME.DS."shopify".DS;

        $shopify = new ShopifyClient($domain, $apiPASS, $aoiUSER, $appSECRET);
        $shopData = $shopify->call('GET', '/admin/shop.json');
        $storeInfo = array("currency"=> $shopData['currency'], "language"=> $shopData['primary_locale'], "storeId" => 1);
        return json_encode($storeInfo);
	}
}

?>