<?php
require_once('../../api/v1/config/constants.php');
	$hookResponse = file_get_contents('php://input');
	header('HTTP/1.0 200 OK');
	$baseCacheDIR = str_replace("shopify/lib/", "", SHOPIFY_CACHE_FOLDER);
	$variantsDIR = $baseCacheDIR . "variants/";
	if (!empty($hookResponse)) {
		$productData = json_decode($hookResponse, true);
        $productID = $productData['id'];
        $thisProdCacheFile = $baseCacheDIR . $productID . ".json";
        if (file_exists($thisProdCacheFile)) {
        	file_put_contents($thisProdCacheFile, json_encode($productData));
        }
        foreach ($productData['variants'] as $variant) {
        	$thisVarCacheFile = $variantsDIR . $variant['id'] . ".json";
        	if (file_exists($thisVarCacheFile)) {
	        	file_put_contents($thisVarCacheFile, json_encode($variant));
	        }
        }
        header('HTTP/1.0 200 OK');
    }

?>