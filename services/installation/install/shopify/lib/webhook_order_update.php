<?php
require_once('../../api/v1/config/constants.php');
	$hookResponse = json_decode(file_get_contents('php://input'));
	header('HTTP/1.0 200 OK');

	$fulfillment = json_encode($hookResponse->fulfillments);
	$cancelOrder = json_encode($hookResponse->refunds);

	$isCustom = 0;
	if (!empty($cancelOrder)) {
		$cancelOrder = json_decode($cancelOrder, true);
		foreach ($cancelOrder[0]['refund_line_items'] as $refund) {
			foreach ($refund['line_item']['properties'] as $item) {
						if($item){
							$isCustom = 1;
						}
				if ($isCustom == 1) {
					$custProdID = $refund['line_item']['product_id'];
			   		$apiUrl = API_URL."api/v1/edit-shopify?pid=".$custProdID.'&delete=1';
			   		$res = file_get_contents($apiUrl);
			   		$isCustom = 0;
				}
			}
		}
	}
	$isCustom = 0;
	if (!empty($fulfillment)) {
		$fulfillment = json_decode($fulfillment, true);
        foreach ($fulfillment[0]['line_items'] as $item) {
				foreach ($item['properties'] as $prop ) {
						if($prop['value']){
							$isCustom = 1;
						}
				}
				if ($isCustom == 1) {
					$custProdID = $item['product_id'];
			   		$apiUrl = API_URL."api/v1/edit-shopify?pid=".$custProdID.'&delete=1';;
			   		$res = file_get_contents($apiUrl);
			   		$isCustom = 0;
				}
		}
	}

?>