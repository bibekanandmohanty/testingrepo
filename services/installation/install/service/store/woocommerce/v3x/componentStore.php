<?php

$docAbsPath = DOCABSPATH . "wp-blog-header.php";
$restApiPath = DOCABSPATH.INSTALLPATH.STORETYPE.'/'.STOREAPIVERSION.'/vendor/autoload.php'; 
error_reporting(0);
require_once $docAbsPath;
require_once $restApiPath;
use Automattic\WooCommerce\Client;
class StoreComponent {
	protected function checkStoreCredential($data) {
		global $wpdb;
		extract($data);
		$errorMsg = '';
		try {
			$key = wc_api_hash( sanitize_text_field( $data['api_key'] ) );
	        $secret = $data['api_secret'];
			$table_name = $wpdb->prefix . "woocommerce_api_keys";
			$permissions = $wpdb->get_var("SELECT permissions FROM $table_name WHERE consumer_key='$key' AND consumer_secret='$secret'");
			if($permissions!='' && $permissions=='read_write') {
				$status = 1; //'success';

			} else {
				$status = 0;
				$errorMsg = 'AUTHENTICATION_ERROR';
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
		if ($dom->getElementsByTagName('api_key')->item(0)->nodeValue != "" && $dom->getElementsByTagName('api_secret')->item(0)->nodeValue != "") {
			$status = true;
		}
		return $status;
	}

	protected function storeInstallProcess($dom, $baseURL, $basePATH, $dummyData){
		/*Activate inkxe product designer plugin*/
		$this->activateAllPlugins();
		/*Create a CMS page with an iFrame to load designer tool*/
		$this->createCmsPage();
		/*Create Products*/
		$returnValue = $this->createSampleProducts($dom,  $dummyData);
		if($returnValue){
			$response = array("proceed_next" => true, "message" => "DUMMY_PRODUCT_CREATED");
	 	}else{
			$response = array("proceed_next" => false, "message" => "DUMMY_PRODUCT_NOT_CREATED");
		}
		return $response;
	}

	/*
	- Name : checkCreateDummyProduct
	- it will check if dummy produc has been created or not
	- Return status created or not
	 */
	private function checkCreateDummyProduct($dummyProd) {
	    global $wpdb;
		$dummyProdId = 0;
		$products = $dummyProd;
		$dummyProdName = '';
	    if (!empty($products)) {
			if($products['product_id']) {
           		$dummyProdName = $products['product_name'];
           	}
	    }
		$table_post = $wpdb->prefix . "posts";
		if (!empty($dummyProdName)) {
			$productID = $wpdb->get_var("SELECT ID FROM $table_post WHERE post_title = '$dummyProdName'");
		}
		if ($productID) {
			$dummyProdId = $productID;
		}
	    return $dummyProdId;
	}

	private function PutUrlInAssets($file, $baseURL)
	{
	    $path = $file;
	    if (file_exists($path)) {
	        @chmod($path, 0777);
	        $settingStr = @file_get_contents($path);
	        $settingStr = str_replace("XEPATH", $baseURL, $settingStr);
	        @file_put_contents($path, $settingStr);
	    }
	}
	
	/*
	- Name : getDummyProductURL
	- it will fetch and return store URL.
	- Return store URL
	 */
	protected function getDummyProductURL($dom){
		$productURL= "";
        $productURL= get_permalink( WC()->session->get('newProdId') );
        return $productURL;
	}
	/*
	- Name : createSampleProducts
	- it will create dummy products.
	- Return status created or not
	 */
	private function createSampleProducts($dom, $dummyData)
	{
		$status = 0;
		$prodArr = $dummyData['products'];
		foreach ($prodArr as $productID) {
			$productData = file_get_contents(DUMMYDATADIR."product_".$productID.".json");
			$productData = json_decode($productData, true);
			$createdProductId = $this->createDemoProduct($dom, $productData);
			$status = $this->setBoundaryForDummyProduct($dom, $createdProductId, $productData['data'], $dummyData);
		}
		return $status;
	}

	private function createDemoProduct($dom, $productData){
		global $wpdb;
		$configXMLpath = $this->getNewXEpath().XECONFIGXML; // xeconfig xml file
		$dom->load($configXMLpath) or die("Unable to load xml");
		$baseUrl = get_site_url();
		$consumerKey = $dom->getElementsByTagName('api_key')->item(0)->nodeValue;
		$secretKey = $dom->getElementsByTagName('api_secret')->item(0)->nodeValue;
		$productTitle = $productData['data']['product_name'];
		$productImage = $productData['data']['store_images'][0]['src'];
		$prodCheck = $this->checkCreateDummyProduct($productData['data']);
		if ($prodCheck == 0) {
			$table_post = $wpdb->prefix . "posts";
			$product_data = $wpdb->get_row("SELECT ID, post_status FROM $table_post WHERE post_title = '$productTitle'");
			if ($product_data) {
				if($product_data->post_status == 'publish') {
					WC()->session->set( 'newProdId', $product_data->ID );
					$status = 1;
				} else {
					$update_product = $wpdb->query("UPDATE $table_post SET post_status='publish' WHERE post_title = '$productTitle'");
					if ($update_product) {
						WC()->session->set( 'newProdId', $product_data->ID );
						$status = 1;
					} else {
						$this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error in creating demo product.' . "\n");
						$status = 0;
					}
				}
			
			} else {
				try {
					$path = $baseUrl;
					$key = $consumerKey;
					$secret = $secretKey;
					$wcNewApi = new Client(
					            $path,
					            $key,
					            $secret,
					            [
					                'wp_api' => true,
					                'version' => 'wc/v3',
					                'verify_ssl' => false,
					            ]
					        );
					/*Create Category*/
					$term_cat = array('name'=>'Tshirt','slug'=>'tshirt','term_group'=>'0');
					$cat_id = $this->createCategory($term_cat);
					$category = array(array('id' => $cat_id));
					// Create Attributes
					$attributeList = $this->createproductAttributes($wcNewApi,$productData['data']);
					$product_array = array();
					$product_array['name'] = $productTitle;
					$product_array['type'] = 'variable';
					$product_array['sku'] = "XETESTPRO".rand(100,999);
					$product_array['virtual'] = false;
					$product_array['price'] = "20.00";
					$product_array['manage_stock'] = true;
					$product_array['stock_quantity'] = "1000";
					$product_array['in_stock'] = true;
					$product_array['visible'] = true;
					$product_array['catalog_visibility'] = "visible";
					$product_array['weight'] = "";               
					$product_array['description'] = $productTitle ." Product is a test product to demonstrate the designer-tool.";
					$product_array['short_description'] = $productTitle ." Product is a test product";
					$product_array['categories'] = $category;
					$product_array['images'] = [ [ 'src' => $productImage ] ];
					$product_array['attributes'] = $attributeList['defaultAttributes'];
					$storeProductData = $wcNewApi->post('products', $product_array);
					$this->createproductVariations($wcNewApi,$storeProductData['id'],$attributeList);
					// Storing session data
					WC()->session->set( 'newProdId', $storeProductData['id'] );
					$status = $storeProductData['id'];
				} catch (Exception $e) {
					$this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error in creating demo product. ' . $e->getMessage() . "\n");
					
					$status = 0;
				}
			}
		} else {
			$status = $prodCheck;
			// Storing session data
			WC()->session->set( 'newProdId', $prodCheck );
		}
		return $status;
	}

	public function createproductAttributes($wcNewApi,$dummyProd) {
		$isColorAttrExist = $this->checkCreateAttribute('color');
		$isSizeAttrExist = $this->checkCreateAttribute('size');
		$isDesignerAttrExist = $this->checkCreateAttribute('xe_is_designer');
		$colorAttrData = "";
		$sizeAttrData = "";
		$isDesgnAttrData = "";
		$colorOptVal = "";
		$sizeOptVal	= "";
		$data = [];
		$attributeVariantList = [];
		$attributeVariantList['defaultAttributes'] = [];
		$attributeVariantList['color'] = [];
		$attributeVariantList['size'] = [];
		if(!$isColorAttrExist)
		{
			$colorAttrData = [
						    'name' => 'color',
						    'slug' => 'pa_color',
						    'type' => 'select',
						    'order_by' => 'menu_order',
						    'has_archives' => true
						];
			array_push($data,$colorAttrData);
		}
		if(!$isSizeAttrExist)
		{
			$sizeAttrData = [
						    'name' => 'size',
						    'slug' => 'pa_size',
						    'type' => 'select',
						    'order_by' => 'menu_order',
						    'has_archives' => true
						];
			array_push($data,$sizeAttrData);
		}
		if(!$isDesignerAttrExist)
		{
			$isDesgnAttrData = [
						    'name' => 'xe_is_designer',
						    'slug' => 'pa_xe_is_designer',
						    'type' => 'select',
						    'order_by' => 'menu_order',
						    'has_archives' => true
						];
			array_push($data,$isDesgnAttrData);
		}
		
		$attributesData = array();
		if (!empty($data)) 
		{
			foreach ($data as $key => $value) {
				$attributesData[$key] = $wcNewApi->post('products/attributes', $value);
			}
		}
		if (!empty($dummyProd)) {
			$sizeData = [];
			$colorData = [];
			if (!empty($dummyProd['size'])) {
				foreach ($dummyProd['size'] as $key => $value) {
					$sizeData[$key]['name'] = $value['name'];
				}
			}
			if (!empty($dummyProd['color'])) {
				foreach ($dummyProd['color'] as $key => $value) {
					$colorData[$key]['name'] = $value['name'];
				}
			}
		} else {
			$sizeData = [
				[
					'name' => 'S'
				],
				[
			    	'name' => 'M'
				]
			];
			$colorData = [
				[
			    	'name' => 'Black'
				],
				[
			    	'name' => 'Blue'
				]
			];
		}
		$isDesignData = [
			[
		    	'name' => '1'
			]
		];
		if(!empty($attributesData)) {
			foreach ($attributesData as $attributesKey => $attributesValue) {
				$attributesId = $attributesValue['id'];
				$attributesSlug = $attributesValue['slug'];
				if($attributesSlug == 'pa_color') {
					$cv = 0;
					foreach ($colorData as $colorKey => $colorValue) {
						$wcNewApi->post('products/attributes/'.$attributesId.'/terms', $colorValue);
						if ($cv == 0) {
							$colorOptVal = $colorValue['name'];
						} else {
							$colorOptVal .= ','. $colorValue['name'];
						}
						$cv++;
					}
					$colorAttrData = 
					['id' => $attributesId,
					  'position' => 0,
					  'visible' => true,
					  'variation' => true,
					  'options' => $colorOptVal,
					];
					array_push($attributeVariantList['defaultAttributes'],$colorAttrData);
					$attributeVariantList['color'] = 
										['id' => $attributesId,
										  'options' => $colorOptVal,
										];
				}
				if($attributesSlug == 'pa_size') {
					$sv = 0;
					foreach ($sizeData as $sizeKey => $sizeValue) {
						$wcNewApi->post('products/attributes/'.$attributesId.'/terms', $sizeValue);
						if ($sv == 0) {
							$sizeOptVal = $sizeValue['name'];
						} else {
							$sizeOptVal .= ','. $sizeValue['name'];
						}
						$sv++;
					}
					$sizeAttrData = 
					['id' => $attributesId,
					  'position' => 0,
					  'visible' => true,
					  'variation' => true,
					  'options' => $sizeOptVal,
					];
					array_push($attributeVariantList['defaultAttributes'],$sizeAttrData);
					$attributeVariantList['size'] = 
										['id' => $attributesId,
										  'options' => $sizeOptVal,
										];
				}
				if($attributesSlug == 'pa_xe_is_designer') {
					foreach ($isDesignData as $isDesignKey => $isDesignValue) {
						$wcNewApi->post('products/attributes/'.$attributesId.'/terms', $isDesignValue);
					}
					$designAttrData = 
					['id' => $attributesId,
					  'position' => 0,
					  'visible' => false,
					  'variation' => false,
					  'options' => $isDesignValue['name'],
					];
					array_push($attributeVariantList['defaultAttributes'],$designAttrData);
				}
			}
		} else {
			$colorAttrId = $this->getAttributeIdByName($wcNewApi,'color');
			$sizeAttrId = $this->getAttributeIdByName($wcNewApi,'size');
			$designAttrId = $this->getAttributeIdByName($wcNewApi,'xe_is_designer');
			if($colorAttrId) {
				$cv = 0;
				foreach ($colorData as $colorKey => $colorValue) {
					$attrTermColorDetails = get_term_by('name', $colorValue['name'], 'pa_color');
					if (!$attrTermColorDetails->term_id) {
						$wcNewApi->post('products/attributes/'.$colorAttrId.'/terms', $colorValue);
					}
					if ($cv == 0) {
						$colorOptVal = $colorValue['name'];
					} else {
						$colorOptVal .= ','. $colorValue['name'];
					}
					$cv++;
				}
				$colorAttrData = 
					['id' => $colorAttrId,
					  'position' => 0,
					  'visible' => true,
					  'variation' => true,
					  'options' => $colorOptVal,
					];
				array_push($attributeVariantList['defaultAttributes'],$colorAttrData);
				$attributeVariantList['color'] = 
										['id' => $colorAttrId,
										  'options' => $colorOptVal,
										];
			}
			if($sizeAttrId) {
				$sv = 0;
				foreach ($sizeData as $sizeKey => $sizeValue) {
					$attrTermSizeDetails = get_term_by('name', $sizeValue['name'], 'pa_size');
					if (!$attrTermSizeDetails->term_id) {
						$wcNewApi->post('products/attributes/'.$sizeAttrId.'/terms', $sizeValue);
					}
					if ($sv == 0) {
						$sizeOptVal = $sizeValue['name'];
					} else {
						$sizeOptVal .= ','. $sizeValue['name'];
					}
					$sv++;
				}
				$sizeAttrData = 
					['id' => $sizeAttrId,
					  'position' => 0,
					  'visible' => true,
					  'variation' => true,
					  'options' => $sizeOptVal,
					];
				array_push($attributeVariantList['defaultAttributes'],$sizeAttrData);
				$attributeVariantList['size'] = 
										['id' => $sizeAttrId,
										  'options' => $sizeOptVal,
										];
			}
			if($designAttrId) {
				foreach ($isDesignData as $isDesignKey => $isDesignValue) {
					$attrTermDesDetails = get_term_by('name', $isDesignValue['name'], 'pa_xe_is_designer');
					if (!$attrTermDesDetails->term_id) {
						$wcNewApi->post('products/attributes/'.$designAttrId.'/terms', $isDesignValue);
					}
				}
				$designAttrData = 
					['id' => $designAttrId,
					  'position' => 0,
					  'visible' => false,
					  'variation' => false,
					  'options' => $isDesignValue['name'],
					];
				array_push($attributeVariantList['defaultAttributes'],$designAttrData);
			}
		}

		return $attributeVariantList;
	}

	private function getAttributeIdByName($wcNewApi,$attr_name){
		$attrId = 0;
		try {
			$attributeList = $wcNewApi->get('products/attributes');
			foreach ($attributeList as $val) {
				$val = (object) $val;
				if($val->name == $attr_name)
				{
					$attrId = $val->id;
				}
			}

			return $attrId;
		} catch (Exception $e) {
			return $attrId;
		}
	}
	
	private function getPrepareVariationCombination($attributeList){
		try {
			$attributes = [];
			$i = 0;
			if (!empty($attributeList['color']['options'])) {
				$colorOption = explode(',', $attributeList['color']['options']);
				foreach ($colorOption as $colorKey => $colorValue) {
					if (!empty($attributeList['size']['options'])) {
						$sizeOption = explode(',', $attributeList['size']['options']);
						foreach ($sizeOption as $sizeKey => $sizeValue) {
							$attributes[$i] = [
					            'regular_price' => '10.00',
					            'attributes' => [[
					                    'id' => $attributeList['color']['id'],
					                    'option' => $colorValue
					                ],[
					                    'id' => $attributeList['size']['id'],
					                    'option' => $sizeValue
					                ]]
					        ];
					        $i++;
						}
					} else {
						$attributes[$i] = [
				            'regular_price' => '10.00',
				            'attributes' => [[
				                    'id' => $attributeList['color']['id'],
				                    'option' => $colorValue
				                ]]
				        ];
					    $i++;
					}
				}
			} else {
				if (!empty($attributeList['size']['options'])) {
					$sizeOption = explode(',', $attributeList['size']['options']);
					foreach ($sizeOption as $sizeKey => $sizeValue) {
						$attributes[$i] = [
				            'regular_price' => '10.00',
				            'attributes' => [[
				                    'id' => $attributeList['size']['id'],
				                    'option' => $sizeValue
				                ]]
				        ];
				        $i++;
					}
				}
			}
	        return $attributes;
		} catch (Exception $e) {
			return array();
		}
	}

	public function createproductVariations($wcNewApi,$productId,$attributeList) {
		$variationList = $this->getPrepareVariationCombination($attributeList);
		$data = [ 'create' => $variationList ];
		$wcNewApi->post('products/'.$productId.'/variations/batch', $data);
	}
	private function getContentByCURL($Url) {
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $Url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    return $output;
	}

	/*
	- Name : setBoundaryForDummyProduct
	- it will set boundary for newly created product.
	- Return status set or not
	 */
	private function setBoundaryForDummyProduct($dom, $newProductID, $ParentData, $dummyData) {
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
	    // Removed strict mode.
	    $conn->query("SET sql_mode=''");
	    // Insert product id into product_setting table and get xe_id
	    $insertProductSetting = "INSERT INTO product_settings(product_id,is_variable_decoration,is_ruler,is_crop_mark,is_safe_zone,crop_value,safe_value,is_3d_preview,3d_object_file,3d_object,scale_unit_id,store_id) VALUES(".$newProductID."," . $ParentData['is_variable_decoration'] ."," . $ParentData['is_ruler']. "," . $ParentData['is_crop_mark']. "," . $ParentData['is_safe_zone']. "," . $ParentData['crop_value']. "," . $ParentData['safe_value']. "," . $ParentData['is_3d_preview']. ",'" . $ParentData['3d_object_file']. "','" . $ParentData['3d_object']. "'," . $ParentData['scale_unit_id']. ", 1)";
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
        foreach ($dummyData['print_methods'] as $key => $rel) {
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
	        foreach ($dummyData['print_methods'] as $key => $rel) {
	        	if ($key > 0) {
	        		$insertMethodSetRel .= ", "; 
	        	}
	        	 $insertMethodSetRel .= "(".$rel['id']."," . $decoSetID. ")";
	        }
	        $queryStatusPDM = $conn->query($insertMethodSetRel);
        }
        return $status;
	}

	public function checkRestAPI() {
		global $wpdb;
		$api = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name='woocommerce_api_enabled'");
		if ($api=='yes') {
			$isEnabled = "Enabled";
			$status = 1;
		} else {
			$isEnabled = "Disabled";
			$status = 0;
			
		}                           
		return $this->showSettingValues($isEnabled, $status);
	}

	public function checkWPversion ($version = '4.5') {
		global $wp_version; 
		if ( version_compare( $wp_version, $version, ">=" ) ) {
			$isEnabled = 'Enabled';
	        $status = 1;
		} else {
			$isEnabled = 'Disabled';
	        $status = 0;
		}
		return $this->showSettingValues($isEnabled, $status);
	}
	
	public function checkWCversion( $version = '3.5' ) {
		if ( class_exists( 'WooCommerce' ) ) {
			global $woocommerce;
			if ( version_compare( $woocommerce->version, $version, ">=" ) ) {
				$isEnabled = 'Enabled';
	         	$status = 1;
			} else {
				$isEnabled = 'Disabled';
	         	$status = 0;
			}
		}
		return $this->showSettingValues($isEnabled, $status);
	}

    public function activateAllPlugins() {
    	$pluginsStatus = 0;
    	$current_plugin = get_option("active_plugins");
 		$myplugin = 'inkxe_product_designer/inkxe_product_designer.php';
 		$plugin = plugin_basename( trim( $myplugin ) );
 		if ( !in_array( $plugin, $current_plugin ) ) {
		    $current_plugin[] = $plugin;
		    sort( $current_plugin );
		    do_action( 'activate_plugin', trim( $plugin ) );
		    update_option( 'active_plugins', $current_plugin );
		    do_action( 'activate_' . trim( $plugin ) );
		    do_action( 'activated_plugin', trim( $plugin) );
		    $pluginsStatus = 1;
 		} 
 		return $pluginsStatus;
    }

	/*
	- Name : checkCreateCollection
	- it will check if custom collection has been created or not
	- Return status created or not
	 */
	private function checkCreateAttribute($attName)
	{  
		$isExistAttr = false;
		if(taxonomy_exists( wc_attribute_taxonomy_name($attName) ))
		{
			$isExistAttr = true;
		}
		return $isExistAttr;
	}

	/*
	- Name : createCategory
	- it will create a category in the store.
	- Return collection details in json
	 */
	private function createCategory($term_cat)
	{
		global $wpdb;
		$msg = '';
		// $status = 0;
		$cat_id = 0;
		$catData = $this->checkCreateCategory($term_cat['name']);
		if (empty($catData)) {
			$wpdb->insert( $wpdb->prefix . 'terms', $term_cat );
			$cat_id = $wpdb->insert_id;
			$term_taxonomy_cat = array('term_id'=>$cat_id,'taxonomy'=>'product_cat','description'=>'','parent'=>'0','count'=>'0');
			$wpdb->insert( $wpdb->prefix . 'term_taxonomy', $term_taxonomy_cat );			
		} else {
			$cat_id = $catData['term_id'];
		}
		return $cat_id;
	}

	private function checkCreateCategory($catName)
	{
		$isExistCategories = true;
		$term = term_exists($catName, 'product_cat');
		if($term == 0 && $term == null) {
			$isExistCategories = false;
		}
		return $term;
	}

	/*
	- Name : createCmsPage
	- it will create a CMS page for designer tool.
	- Return status created or not
	 */
	private function createCmsPage()
	{
		$msg = '';
		$status = 0;
		$title = "Product Designer";
		if( null == get_page_by_title( $title ) ) 
		{
			global $user_ID;
			$inkXEpkgDIR = rtrim(ROOTABSPATH, SETUPFOLDERNAME.DS) . DS;
			$file = $inkXEpkgDIR."imprint_details.json";
			if (file_exists($file)) {
				$setupDetails = json_decode(file_get_contents($file), true) ;
				$newFolder = $setupDetails['designer_dir'] != ''? $setupDetails['designer_dir']:DEFAULTXEFOLDER;
				$designerURL = site_url().'/'.$newFolder.'/index.html';
			} else {
				$designerURL = site_url().'/'.DEFAULTXEFOLDER.'/index.html';
			}    
			$page = array();
			$page['post_type']    = 'page';
			$page['post_content'] = '<iframe id="tshirtIFrame" style="width: 100%; height: 776px; margin-top: 50px; border: 1px solid #e5e5e5; box-shadow: 0px 0px 24px -6px rgba(0,0,0,0.1);" src="'.$designerURL.'" frameborder="0" scrolling="no"><span data-mce-type="bookmark" style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" class="mce_SELRES_start">﻿</span><span data-mce-type="bookmark" style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" class="mce_SELRES_start">﻿</span><span data-mce-type="bookmark" style="display: inline-block; width: 0px; overflow: hidden; line-height: 0;" class="mce_SELRES_start">﻿</span></iframe>';
			$page['post_parent']  = 0;
			$page['post_author']  = $user_ID;
			$page['post_status']  = 'publish';
			$page['post_title']   = $title;
			remove_all_filters("content_save_pre");
			$pageid = wp_insert_post ($page);
			if ($pageid == 0) 
			{ 
				$this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error in cms page creating.' . "\n");
				$status = 0;
			}
			else
			{
				$status = 1;
			}
		} 
		else
		{
			$this->xe_log("\n" . date("Y-m-d H:i:s") . ': You have already created this CMS Page.' . "\n");
			$status = 1;       
		}

		return $status;
	}

    public function copyPluginfiles() {
    	$this->updateInkXEPkgDir($inkXEpkgDIR);
		$this->recurse_copy(ROOTABSPATH.STORETYPE.'/'.STOREAPIVERSION.'/plugins', WP_PLUGIN_DIR);
		if(!@copy(ROOTABSPATH.STORETYPE.'/'.STOREAPIVERSION.'/frontendlc.php', DOCABSPATH.'frontendlc.php')){
			$errorMsg ='- frontendlc.php file didn\'t copy. \n';
			$this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error :'  . $errorMsg . "\n");
		}
    }

    /**
	 * Update inkXe Package Directory in the store.
	 */
	private function updateInkXEPkgDir($inkXEpkgDIR) {
		delete_option( 'inkxe_dir' );
		add_option( 'inkxe_dir', $inkXEpkgDIR );
	}

	public function getStoreLangCurrency($storeId, $dom = "")
	{
		$currency = get_option('woocommerce_currency');
		$lang = get_locale();
		if ( strlen( $lang ) > 0 ) {
			$language = explode( '_', $lang )[0];
		}
		$response= [
			'currency' => $currency,
			'language' => $language,
			'storeId'  => $storeId,
		];
		$jsonResponse = json_encode($response);
    	return($jsonResponse);
	}
}

?>