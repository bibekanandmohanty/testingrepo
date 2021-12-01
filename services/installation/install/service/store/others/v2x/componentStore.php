<?php

class StoreComponent
{
	public function copyOtherStorefiles() {
		if(!@copy(ROOTABSPATH.STORETYPE.'/'.STOREAPIVERSION.'/frontendlc.php', DOCABSPATH.'frontendlc.php')){
			$errorMsg ='- frontendlc.php file didn\'t copy. \n';
			$this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error :'  . $errorMsg . "\n");
		}	
    }

    /*
	- Name : getDummyProductURL
	- it will fetch and return store URL.
	- Return store URL
	 */
	protected function getDummyProductURL($dom){
		$this->createSampleProducts($dom);
		$toolURL = $dom->getElementsByTagName('api_url')->item(0)->nodeValue;
        return $toolURL;
	}

	/*
	- Name : createSampleProducts
	- it will create dummy products.
	- Return status created or not
	 */
	private function createSampleProducts($dom)
	{
		$status = 0;
		$productData = file_get_contents(DUMMYDATADIR."product_9214.json");
		$productData = json_decode($productData, true);
		$createdProductId = 20;
		$status = $this->setBoundaryForDummyProduct($dom, $createdProductId, $productData['data']);
		return $status;
	}

	/*
	- Name : setBoundaryForDummyProduct
	- it will set boundary for newly created product.
	- Return status set or not
	 */
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
	        $insertDecoSetting = "INSERT INTO product_decoration_settings(product_setting_id,product_side_id,name,dimension,print_area_id,sub_print_area_type,custom_min_height,custom_max_height,custom_min_width,custom_max_width,is_border_enable,is_sides_allow) VALUES(".$prodSetID."," . $sideSetID .",'" . $setting['name']. "','" . $setting['dimension']. "','" . $setting['print_area_id']. "','" . $setting['sub_print_area_type']. "','" . 0 ."','" . 0 ."','" . 0 ."','" . 0 ."','" . $setting['is_border_enable']."','" . $setting['is_sides_allow']. "')";
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
        return $status;
	}

	public function getStoreLangCurrency($storeId, $dom = "")
    {
        $response = [
            'currency' => "INR",
            'language' => "en",
            'storeId'  => $storeId,
        ];
        return json_encode($response);
    }
}
?>