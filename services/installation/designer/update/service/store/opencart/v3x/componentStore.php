<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$configPath = DOCABSPATH.'config.php';
require_once $configPath;
$ocPath = DOCABSPATH.INSTALLPATH.STORETYPE.DS;
$opencartUrl = (stripos($_SERVER['SERVER_PROTOCOL'],'https') === true) ? HTTPS_SERVER : HTTP_SERVER;
define('STOREURL',$opencartUrl);
define('STOREINSTALLPATH',$ocPath);
if(!file_exists(DOCABSPATH."vcheck.php"))
	@copy($ocPath."vcheck.php", DOCABSPATH."vcheck.php");
else 
if(!file_exists($docAbsPath."qvcheck.php"))
	@copy($ocPath."qvcheck.php", DOCABSPATH."qvcheck.php");
if (extension_loaded('mysqli')) {
	$StoreDBConn = mysqli_connect(DB_HOSTNAME,DB_USERNAME,DB_PASSWORD,DB_DATABASE);
} else {
	$StoreDBConn = '';
}
class StoreComponent {

	protected function storeInstallProcess($dom, $baseURL, $basePATH, $dummyData){
		$this->createTable('product_variant');
		$this->alterTable('product');
		$this->createCategory('Tshirt');
		$this->createAttributes();
		$returnValue = $this->createSampleProducts($dom,  $dummyData);
		if($returnValue){
			$response = array("proceed_next" => true, "message" => "DUMMY_PRODUCT_CREATED");
	 	}else{
			$response = array("proceed_next" => false, "message" => "DUMMY_PRODUCT_NOT_CREATED");
		}
		return $response;
	}

	/*
	- Name : getDummyProductURL
	- it will fetch and return store URL.
	- Return store URL
	 */
	protected function getDummyProductURL($dom){
		global $StoreDBConn;
		$productURL = "";
        $productURL = STOREURL;

    	$sql = mysqli_query($StoreDBConn, "SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id)WHERE p.is_variant= '0' AND pd.name = 'Men Tshirt'");
		if(mysqli_num_rows($sql)>0)
		{
			$row = mysqli_fetch_assoc($sql);
			$product_id = $row['product_id'];
			$productURL = STOREURL.'index.php?route=product/product&product_id='.$product_id;
		}
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
			$createdProductId = $this->createDummyProduct(array('color','size','xe_is_design','refid'),$productData);
			$status = $this->setBoundaryForDummyProduct($dom, $createdProductId, $productData['data'], $dummyData);
		}
		return $status;
	}

	public function createAttributes()
	{
		$color = 'color';
        $size = 'size';
		$this->createAttribute($color,array('Black','White'), 'select');
		$this->createAttribute($size, array('XXL','XL','L','M','S'), 'select');
		$this->createAttribute('xe_is_design', array(), 'text');
		$this->createAttribute('disable_addtocart', array(), 'text');
		$this->createAttribute('refid', array(), 'text');
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
	    // Insert product id into product_setting table and get xe_id
	    $insertProductSetting = "INSERT INTO product_settings(product_id,is_variable_decoration,is_ruler,is_crop_mark,is_safe_zone,crop_value,safe_value,is_3d_preview,3d_object_file,3d_object,scale_unit_id) VALUES(".$newProductID."," . $ParentData['is_variable_decoration'] ."," . $ParentData['is_ruler']. "," . $ParentData['is_crop_mark']. "," . $ParentData['is_safe_zone']. "," . $ParentData['crop_value']. "," . $ParentData['safe_value']. "," . $ParentData['is_3d_preview']. ",'" . $ParentData['3d_object_file']. "','" . $ParentData['3d_object']. "'," . $ParentData['scale_unit_id']. ")";
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
        $conn->query("SET sql_mode=''");
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

	/*
	- Name : checkCreateDummyProduct
	- it will check if dummy produc has been created or not
	- Return status created or not
	 */
	public function checkCreateDummyProduct($productTitle)
	{
		global $StoreDBConn;
	    $dummyProdID = 0;
		$sql = mysqli_query($StoreDBConn, "SELECT p.product_id FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_description pd ON(p.product_id = pd.product_id) WHERE pd.name='".$productTitle."' OR pd.name='".$productTitle."-Simple'");
		if (mysqli_num_rows($sql) > 0){
			$row = mysqli_fetch_assoc($sql);
			$dummyProdID = $row['product_id'];
		}	
		return $dummyProdID;
	}

	/*
	- Name : checkCreateCollection
	- it will check if custom collection has been created or not
	- Return status created or not
	 */
	public function checkCreateAttribute($colName)
	{   
		global $StoreDBConn;
	    $sql = mysqli_query($StoreDBConn,"SELECT * FROM " . DB_PREFIX . "option_description WHERE name = '" . $colName . "'");
		$thisColID = mysqli_num_rows($sql);
	    return $thisColID;
	}

	/*
	- Name : checkCreateCategory
	- it will check if custom collection has been created or not
	- Return status created or not
	 */
	public function checkCreateCategory($colName)
	{
		global $StoreDBConn;
	    $sql = mysqli_query($StoreDBConn,"SELECT category_id FROM " . DB_PREFIX . "category_description WHERE name = '".$colName."'");	
		$thisColID = mysqli_num_rows($sql);
	    return $thisColID;
	}


	/*
	- Name : createDummyProduct
	- it will create a dummy product in store
	- Return procuct details in json
	 */
	public function createDummyProduct($attr,$productData)
	{
		global $StoreDBConn;
	    $msg = '';
	    $status = 0;$configid=0;$simpleProductId=0;
    	$productTitle = $productData['data']['product_name'];
		$productImage = $productData['data']['store_images'][0]['src'];
	    $prodCheck = $this->checkCreateDummyProduct($productTitle);
	    $imageName = $this->saveProductImage($productImage);
	    if ($prodCheck == 0) {
			$configid = $this->addProduct($productTitle, $imageName, $attr);
			if($configid)
			{
				$simpleProductId = $this->addProduct($productTitle, $imageName, $attr, $configid);
				$simpleProductId1 = $this->addProduct($productTitle, $imageName, $attr, $configid,1);
			}        
	        if ($configid!=0 && $simpleProductId!=0 && $simpleProductId1!=0) {
	    		return $configid;
	        }
	    } else {
	    	return $prodCheck;
	    }
	}

	public function addProduct($productTitle, $imageName, $attr, $configid="", $isSecondVariant=0)
	{
		global $StoreDBConn;
		$language_id = $this->getLanguageID();
		$category_id =  $this->getCategoryId();
		/* Add Test product */
		$qty = 1000;
		$price = 20;
		$name = ($configid!='')?$productTitle."-Simple":$productTitle;
		$description = $productTitle ."-Product";
		$sku = "0120120";		
		$is_variant = ($configid!='')?1:0;
		mysqli_query($StoreDBConn,"SET sql_mode=''");
		$sql = mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product SET model = 'test-model',sku = '" . $sku . "', upc = '', ean = '', jan = '', isbn = '', mpn = '', location = '', quantity = '" . (int)$qty . "', minimum = '1', subtract = '1', stock_status_id = '6', date_available = NOW(), manufacturer_id = '', shipping = '1', price = '" . (float)$price . "', points = '', weight = '', weight_class_id = '1', length = '', width = '', height = '', length_class_id = '1', status = '1', tax_class_id = '0', sort_order = '1', date_added = NOW(), is_variant= '".(int)$is_variant."'");
		$product_id = mysqli_insert_id($StoreDBConn);
		
		if($is_variant)
			mysqli_query($StoreDBConn, "INSERT INTO `" . DB_PREFIX . "product_variant` SET `product_id` = " . (int)$product_id . ", `variant_id` = " . (int)$configid);
		
		mysqli_query($StoreDBConn,"UPDATE " . DB_PREFIX . "product SET image = 'catalog/".$imageName."' WHERE product_id = '" . (int)$product_id . "'");
		
		mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $name . "', description = '" . $description . "', tag = '', meta_title = '".$name."', meta_description = '', meta_keyword = ''");
		//Insert Store Product
		mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = 0");
		
		//Adding xe_size
		foreach ($attr as $atribute)
		{
			$query = mysqli_query($StoreDBConn,"SELECT option_id FROM " . DB_PREFIX . "option_description WHERE language_id = '" . (int)$language_id . "' AND name = '" . $atribute . "'");	
			$option = mysqli_fetch_assoc($query);
			$option_id = $option['option_id'];
			$required = ($atribute!='refid' && $atribute!='xe_is_design')?1:0;
			$o_value = ($atribute!='xe_is_design')?'':1;
			mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$option_id . "', value = '".$o_value."', required = '".$required."'");
			$product_option_id = mysqli_insert_id($StoreDBConn);
			
			if ($atribute!='refid' && $atribute!='xe_is_design') {
				if ($configid != '') {
					if ($atribute == 'color') {
						$sql = mysqli_query($StoreDBConn,"SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE language_id = '" . (int)$language_id . "' AND option_id = '" . (int)$option_id . "' LIMIT 2");
						$i=0;
						while ($option_value = mysqli_fetch_array($sql)) {
							if ($isSecondVariant) {
								if ($i>0) {
									$option_value_id = $option_value['option_value_id'];
									mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$option_id . "', option_value_id = '" . (int)$option_value_id . "', quantity = '" . (int)$qty . "', subtract = '1', price = '" . (float)0 . "', price_prefix = '+', points = '', points_prefix = '+', weight = '" . (float)0 . "', weight_prefix = '+'");
								}
							} else {
								if ($i==0) {
									$option_value_id = $option_value['option_value_id'];
									mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$option_id . "', option_value_id = '" . (int)$option_value_id . "', quantity = '" . (int)$qty . "', subtract = '1', price = '" . (float)0 . "', price_prefix = '+', points = '', points_prefix = '+', weight = '" . (float)0 . "', weight_prefix = '+'");
								}
							}
							$i++;
						}
					} else {
						$sql = mysqli_query($StoreDBConn,"SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE language_id = '" . (int)$language_id . "' AND option_id = '" . (int)$option_id . "' LIMIT 2");
						while ($option_value = mysqli_fetch_array($sql)) {
							$option_value_id = $option_value['option_value_id'];
							mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$option_id . "', option_value_id = '" . (int)$option_value_id . "', quantity = '" . (int)$qty . "', subtract = '1', price = '" . (float)0 . "', price_prefix = '+', points = '', points_prefix = '+', weight = '" . (float)0 . "', weight_prefix = '+'");
				        }
					}
				} else {
					$sql = mysqli_query($StoreDBConn,"SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE language_id = '" . (int)$language_id . "' AND option_id = '" . (int)$option_id . "' LIMIT 2");
					while ($option_value = mysqli_fetch_array($sql)) {
						$option_value_id = $option_value['option_value_id'];
						mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$option_id . "', option_value_id = '" . (int)$option_value_id . "', quantity = '" . (int)$qty . "', subtract = '1', price = '" . (float)0 . "', price_prefix = '+', points = '', points_prefix = '+', weight = '" . (float)0 . "', weight_prefix = '+'");
					}
				}
			}
		}
		//Adding product category
		mysqli_query($StoreDBConn, "INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
		
		//adding Variant Image
		// mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = 'catalog/".$imageName."', sort_order = '0'");		
		return $product_id;
	}
	/*
	- Name : createAttribute
	- it will create a attribute in store and assign the dummy product to it
	- Return attribute details in json
	 */
	public function createAttribute($name, $attr, $type)
	{
		global $StoreDBConn;
	    $msg = '';
	    $status = 0;
		$sort_order = 0;
	    $colID = $this->checkCreateAttribute($name);
		$attrArray = array();
	    if ($colID == 0) {
	        $language_id = $this->getLanguageID();
			mysqli_query($StoreDBConn,"INSERT INTO `" . DB_PREFIX . "option` SET type = '" . $type . "', sort_order = '" . (int)$sort_order . "'");
			$option_id = mysqli_insert_id($StoreDBConn);
			$query = mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "option_description SET option_id = '" . (int)$option_id . "', language_id = '" . (int)$language_id . "', name = '" . $name . "'");
			if (!empty($attr)) {
				foreach ($attr as $value) {
					mysqli_query($StoreDBConn, "INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '', sort_order = '" . (int)$sort_order . "'");					
					$option_value_id = mysqli_insert_id($StoreDBConn);				
					mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . (int)$language_id . "', option_id = '" . (int)$option_id . "', name = '" . $value . "'");
				}
			}
	        
	        if ($option_id != 0) {
	            $msg = $name . " attribute has been created.";
	            $status = 1;
	            return array($status,$msg);
	        }
	    }
	}

	public function getLanguageID() {
		global $StoreDBConn;
		$sql = "select language_id from ".DB_PREFIX."language where status=1";
		$row = mysqli_fetch_assoc(mysqli_query($StoreDBConn, $sql));
		return $row['language_id'];
	}

	/*
	- Name : saveProductImage
	- it will upload and save the product image.
	- Return image file path
	 */
	private function saveProductImage($img)
	{
	    $imageFilename = basename($img);
	    // $image_type = substr(strrchr($imageFilename, "."), 1);
	    // $filename = md5($img . strtotime('now')) . '.' . $image_type;
	    $imgDir = DOCABSPATH."image/catalog";
	    $filepath = $imgDir . '/' . $imageFilename;
	    file_put_contents($filepath, file_get_contents(trim($img)));
	    return $imageFilename;
	}
	/*
	- Name : createCategory
	- it will create a category in the store.
	- Return collection details in json
	 */
	public function createCategory($name)
	{
		global $StoreDBConn;
		$msg = '';
	    $status = 0;
		$colID = $this->checkCreateCategory($name);
		if ($colID == 0) {
			$language_id = $this->getLanguageID();
			mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "category SET image = '', parent_id = '0', `top` = '1', `column` = '1', sort_order = '0', status = '1', date_modified = NOW(), date_added = NOW()");
			$category_id = mysqli_insert_id($StoreDBConn);
			
			mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', name = '".$name."', description = 'Test category for inkxe tool', meta_title = 'tshirt', meta_description = '', meta_keyword = ''"); 
			
			if (mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = 0")){
				if (mysqli_query($StoreDBConn,"INSERT INTO " . DB_PREFIX . "category_path SET category_id = '" . (int)$category_id . "', path_id = '" . (int)$category_id . "', level=0")){
					$status = 1;
				}
			}else{
				$msg = $name . " category not created.";
				$this->xe_log("\n" . date("Y-m-d H:i:s") . $msg . "\n");
				$status = 0;
			}
		}
		return $status;
	}

	public function getCategoryId()
	{
		global $StoreDBConn;
		$dummyCategoryId = 0;
		$sql = mysqli_query($StoreDBConn, "SELECT c.category_id FROM " . DB_PREFIX . "category c INNER JOIN " . DB_PREFIX . "category_description cd ON(c.category_id = cd.category_id) WHERE cd.name='Tshirt'");
		if (mysqli_num_rows($sql) > 0){
			$row = mysqli_fetch_assoc($sql);
			$dummyCategoryId = $row['category_id'];
		}	
		return $dummyCategoryId;
	}

	public function createTable($name) 
	{
		global $StoreDBConn;
		$msg = '';
	    $status = 0;
		if (mysqli_query($StoreDBConn,"CREATE TABLE IF NOT EXISTS " . DB_PREFIX ."". $name." ( product_id int(11) NOT NULL, variant_id int(11) NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8")) {
			$msg = $name . " table created successfully.";
			$status = 1;
		}
		return $status;
	}

	public function alterTable($name)
	{
		global $StoreDBConn;
		$msg = '';
	    $status = 0;
		$result = mysqli_query($StoreDBConn,"SHOW COLUMNS FROM " . DB_PREFIX ."". $name. " LIKE 'is_variant'");
		$exists = (mysqli_num_rows($result))?TRUE:FALSE;
		if(!$exists) {
			if (mysqli_query($StoreDBConn,"ALTER TABLE  " . DB_PREFIX ."". $name. " ADD  is_variant ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0'"))	{
				$status = 1;
			}
		}
		return $status;
	}

	public function checkOpencartVersion(){
		$url = STOREURL.'vcheck.php';
		if (function_exists('curl_version')) {
			$ch = curl_init();  		 
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$version=curl_exec($ch);						 
			curl_close($ch);
		}
		if ($version >= '3.0.3.2'){
			$isEnabled = 'Enabled';
			$status = 1;
		} else {
			$isEnabled = 'Disabled';
			$status = 0;
		}
		return $this->showSettingValues($isEnabled, $status);
	}

	public function checkVQmod(){
		$url = STOREURL.'qvcheck.php';
		if (function_exists('curl_version')) {
			$ch = curl_init();  		 
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$vqmod=curl_exec($ch);						 
			curl_close($ch);	
		}
		if($vqmod == 'VQMOD ALREADY INSTALLED!'){
			$isEnabled = 'Enabled';
			$status = 1;
		} else {
			$isEnabled = 'Disabled';
			$status = 0;
		}
		return $this->showSettingValues($isEnabled, $status);
	}

    public function copyEntenstionfiles($newFolder) {
    	$this->updateImprintnextDir($newFolder);
		if(!@copy(STOREINSTALLPATH.'frontendlc.php', DOCABSPATH.'frontendlc.php')){
			$errorMsg ='- frontendlc.php file did not copy. \n';
			$this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error :'  . $errorMsg . "\n");
		}

		$this->recurse_copy(STOREINSTALLPATH."feed", DOCABSPATH."catalog/controller/extension/feed");	
		$this->recurse_copy(STOREINSTALLPATH."xml", DOCABSPATH."vqmod/xml");
	
		//check if designer-tool files and folders are copied or not
		$fileArrayOpencart = array(
			DOCABSPATH . "catalog/controller/extension/feed/web_api.php",
			DOCABSPATH . "vqmod/xml/Riaxe_Product_Designer.xml"
		);
		foreach ($fileArrayOpencart as $fileFolderOpencart){
			if (!file_exists($fileFolderShopify)){
				$errorMsg.="- ".$fileFolderOpencart." file did not copy. \n";
				$this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error :'  . $errorMsg . "\n");
			}
		}
    }

    public function updateImprintnextDir($newFolder){
		global $StoreDBConn;
    	$sql = mysqli_query($StoreDBConn, "SELECT s.value FROM " . DB_PREFIX . "setting s WHERE s.key = 'imprintnext_default_directory'");
		if(mysqli_num_rows($sql)>0)
		{
			mysqli_query($StoreDBConn,"UPDATE " . DB_PREFIX . "setting SET `code` = 'config', `value` = '".$newFolder."', `serialized` = 0 WHERE `key` = 'imprintnext_default_directory'");
		} else {
			$sql = mysqli_query($StoreDBConn,"INSERT INTO oc_setting set `store_id`='0',`serialized`='0',`code` = 'config', `key` = 'imprintnext_default_directory', `value` = '".$newFolder."'");
			$dirId = mysqli_insert_id($StoreDBConn);
		}
	}
}

?>