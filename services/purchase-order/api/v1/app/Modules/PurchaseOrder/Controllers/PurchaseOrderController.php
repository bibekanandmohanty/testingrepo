<?php
/**
 * Manage Purchase Order
 *
 * PHP version 5.6
 *
 * @category  Purchase Order
 * @package   Production_Hub
 * @author    Soumya Swain <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\PurchaseOrder\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Orders\Controllers\OrdersController;
use App\Modules\Orders\Models\Orders;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderDetails;
use App\Modules\PurchaseOrder\Models\PurchaseOrderInternalNote;
use App\Modules\PurchaseOrder\Models\PurchaseOrderInternalNoteFile;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLineItemStatus;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLog;
use App\Modules\Quotations\Models\ProductionHubSetting;
use App\Modules\Settings\Models\Setting;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Models\VendorCategory;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Purchase Order Controller
 *
 * @category Purchase Order
 * @package  Production_Hub
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class PurchaseOrderController extends ParentController {
	public $pdfFilePath;
	/**
	 * Define image upload path
	 **/
	public function __construct() {
		$this->createPurchaseOrderDirectory(path('abs', 'purchase_order'));
		$this->pdfFilePath = path('abs', 'purchase_order');
	}
	/**
	 * create Purchase order directory
	 **/
	public function createPurchaseOrderDirectory($dir) {
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
	}
	/**
	 * GET: Purchase order id
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   23 June 2020
	 * @return json response
	 */
	public function getPurchaseOrderId($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order id', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$purchaseOrderInit = new PurchaseOrder();
		$lastPoId = '';
		$lastRecord = $purchaseOrderInit->select('po_id')->where('store_id', '=', $getStoreDetails['store_id'])->latest()->first();
		if (!empty($lastRecord)) {
			$lastPoId = $lastRecord->po_id;
		}
		//Generate Purchase Order Id
		$poId = $this->generatePurchaseOrderId($request, $lastPoId);
		if ($poId != '') {
			$jsonResponse = [
				'status' => 1,
				'data' => $poId,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST: Generate purchase Order id
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   23 June 2020
	 * @return  string
	 */
	public function generatePurchaseOrderId($request, $lastPoId = '') {

		//Get purchase order setting data
		$getStoreDetails = get_store_details($request);
		$settingInit = new ProductionHubSetting();
		$settingData = $settingInit->select('setting_value', 'flag')
			->where([
				'module_id' => 3,
				'setting_key' => 'purchase_order_id',
				'store_id' => $getStoreDetails['store_id'],
			]);
		if ($settingData->count() > 0) {
			$settingDataArr = $settingData->first()->toArray();
			$settingValue = json_clean_decode($settingDataArr['setting_value'], true);
			$preFix = $settingValue['prefix'];
			$startingNum = $settingValue['starting_number'];
			$postFix = $settingValue['postfix'];
			$flag = 0;
			if ($settingDataArr['flag'] == 1 && $flag == 1) {
				$flag = 1;
				$newPoId = $preFix . $startingNum . $postFix;
			} else if ($lastPoId == '') {
				$newPoId = $preFix . $startingNum . $postFix;
			} else {
				$postFixLen = strlen($postFix);
				if(0 === strpos($lastPoId, $preFix)){
                    $withoutPrefix = substr($lastPoId, strlen($preFix)).'';
                }
                $poNum = substr($withoutPrefix, 0, -$postFixLen);
				//$poNum = preg_replace('/[^0-9]/', '', $lastPoId);
				$newPoNum = $poNum + 1;
				$newPoId = $preFix . $newPoNum . $postFix;

			}
			$purchaseOrderInit = new PurchaseOrder();
			$poData = $purchaseOrderInit->where(
				[
					'store_id' => $getStoreDetails['store_id'],
					'po_id' => $newPoId,
				]);

			if ($poData->count() > 0) {
				return $this->generatePurchaseOrderId($request, $newPoId);
			} else {
				return $newPoId;
			}

		}
	}
	/**
	 * POST: Create Purchase Order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   18 June 2020
	 * @return json response wheather data is save or not
	 */
	public function createPurchaseOrder($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('PurchaseOrder', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$poId = $allPostPutVars['po_id'] ? $allPostPutVars['po_id'] : '';
		$expectedDeliveryDate = $allPostPutVars['expected_delivery_date'] ? $allPostPutVars['expected_delivery_date'] : '';
		$poNotes = $allPostPutVars['po_notes'] ? $allPostPutVars['po_notes'] : '';
		$createdDate = date('Y-m-d');
		$getStoreDetails = get_store_details($request);
		$storeId = $allPostPutVars['store_id'] ? $allPostPutVars['store_id'] : 1;
		$statusType = $allPostPutVars['status_type'] ? $allPostPutVars['status_type'] : '';
		$purchase_order_id = $allPostPutVars['purchase_order_id'] ? $allPostPutVars['purchase_order_id'] : '';
		$poItems = json_clean_decode($allPostPutVars['po_items'], true);
		$poStatusId = 0;
		$module_id = 3;
		$lastPurchaseOrder = '';
		$settingInit = new ProductionHubSetting();
		$settingData = $settingInit->select('setting_value', 'setting_key')
			->where([
				'module_id' => $module_id,
				'setting_key' => 'last_po_date',
				'store_id' => $storeId,
			]);
		if ($settingData->count() > 0) {
			$settingDataArr = $settingData->first()->toArray();
			$settingDataValue = json_clean_decode($settingDataArr['setting_value'], true);
			$lastPurchaseOrder = $settingDataValue['date'];

		}
		$isPurchaseOrder = 1;
		$statusId = $this->getPurchaseOrderStatusIdBySlug('po-sent', $storeId, $module_id);
		if ($statusType == "draft") {
			$poStatusId = $statusId['id'] ? $statusId['id'] : 0; /*purchase order status Draft*/
		} else {
			$poStatusId = $statusId['id'] ? $statusId['id'] : 0; /*purchase order status Draft*/
		}
		$description = 'PO created : Purchase order created by admin.';
		//$settingInit = new ProductionHubSetting();
		//Check for po_id
		$lastPoId = '';
		$newPoId = '';
		$purchaseOrderInit = new PurchaseOrder();
		if ($isPurchaseOrder == 1) {
			if (isset($purchase_order_id) && !empty($purchase_order_id)) {
				$poIdCount = $purchaseOrderInit->where('xe_id', $purchase_order_id)->count();
				if ($poIdCount > 0) {
					$purchaseOrderInit->where('xe_id', $purchase_order_id)->delete();
					$purchaseOrderDetailsInit = new PurchaseOrderDetails();
					$purchaseOrderId = $purchaseOrderDetailsInit->whereIn('purchase_order_id', [$purchase_order_id])->count();
					if ($purchaseOrderId > 0) {
						$purchaseOrderDetailsInit->whereIn('purchase_order_id', [$purchase_order_id])->delete();
					}
				}
			}
			/* insert into purchase order table  */
			$vendorId = [];
			$shipAddressId = [];
			$i = 0;
			$responseArray = [];
			foreach ($poItems as $key => $value) {
				$combinationId = $value['vendor_id'] . '_' . $value['ship_address_id'];
				if (!in_array($combinationId, $shipAddressId)) {
					array_push($shipAddressId, $combinationId);
					$responseArray[$i]['vendor_id'] = $value['vendor_id'];
					$responseArray[$i]['ship_address_id'] = $value['ship_address_id'];
					$poItemsList = $this->getPurchaseOrderItemData($poItems, $value['vendor_id'], $value['ship_address_id']);
					$responseArray[$i]['po_items'] = $poItemsList;
					$i++;
				}
			}
			if (!empty($responseArray)) {
				$status = 0;
				$po_count = 0;
				$emailData = [];
				foreach ($responseArray as $key => $value) {
					$purchaseOrderInit = new PurchaseOrder();
					$poIdData = $purchaseOrderInit->where(
						[
							'po_id' => $poId,
							'store_id' => $storeId,
						]);
					if ($poIdData->count() > 0) {
						$lastRecord = $purchaseOrderInit->select('po_id')->latest()->first();
						if (!empty($lastRecord)) {
							$newPoId = $this->generatePurchaseOrderId($request, $lastRecord->po_id);
						}
					} else {
						$newPoId = $this->generatePurchaseOrderId($request, $lastPoId);
					}
					/** insert into purchase order table */
					$saveData = [
						'po_id' => $newPoId,
						'status_id' => $poStatusId,
						'store_id' => $storeId,
						'vendor_id' => $value['vendor_id'],
						'ship_address_id' => $value['ship_address_id'],
						'po_notes' => $poNotes,
						'expected_delivery_date' => $expectedDeliveryDate,
						'created_at' => $createdDate,
					];
					$purchaseSaveOrderInit = new PurchaseOrder($saveData);
					$purchaseSaveOrderInit->save();
					$lastInsertId = $purchaseSaveOrderInit->xe_id;
					//Change the production setting flag value after production job is created
    				$this->changeSettingFlagValue($storeId, 3, 'purchase_order_id'); 
					/** insert into po_log table */
					$logData = [
						'po_id' => $lastInsertId,
						'description' => $description,
						'user_type' => 'admin',
						'user_id' => 1,
						'created_date' => date_time(
							'today', [], 'string'
						)
					];
					$this->addingPurchaseOrderLog($logData);
					$poLineItemStatus = 1; /** pending */
					$this->savePurchaseOrderItems($lastInsertId, $poLineItemStatus, $value['po_items']);
					$status = 1;
					$po_count++;
					array_push($emailData, $lastInsertId);
				}
				if ($status) {
					/** update last purchase order date  */
					if (empty($lastPurchaseOrder)) {
						/** insret data into production_hub_settings table */
						$updateData = [
							'setting_value' => json_clean_encode(array('date' => date('Y-m-d'))),

						];
						$status = $settingInit->where('module_id', '=', $module_id)
							->where('store_id', '=', $storeId)
							->where('setting_key', '=', 'last_po_date')
							->update($updateData);
					} else {
						/** update data into production_hub_settings table */
						$updateData = [
							'setting_value' => json_clean_encode(array('date' => date('Y-m-d'))),

						];
						$status = $settingInit->where('module_id', '=', $module_id)
							->where('store_id', '=', $storeId)
							->where('setting_key', '=', 'last_po_date')
							->update($updateData);

					}
					$data = [];
					if (!empty($emailData)) {
						$emailData = json_encode($emailData);
						$res = $this->sendToVendor($request, $response, ['id'=>$emailData], 1);
						if ($res['status'] == 1) {
							$data = [
								'success_emails' => $res['success_emails'],
								'unsuccess_emails' => $res['unsuccess_emails'],
							];
						}
					}
					$jsonResponse = [
						'status' => 1,
						'po_count' => $po_count,
						'message' => "Purchase order created successfully",
						'data' => $data
					];
				} else {
					$jsonResponse = [
						'status' => 0,
						'message' => "Purchase order created in error",
					];
				}
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => "Purchase order items are empty",
				];
			}

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => "Please enable purchase order settings",
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 *
	 * $poItems
	 * $vendorId
	 * $shipAddressId
	 * @author soumyas@riaxe.com
	 * @date   01 October 2020
	 * @return Array
	 */
	public function getPurchaseOrderItemData($poItems, $vendorId, $shipAddressId) {
		$i = 0;
		$itemArray = array();
		foreach ($poItems as $key => $value) {
			if ($vendorId == $value['vendor_id'] && $shipAddressId == $value['ship_address_id']) {
				$itemArray[$i]['order_id'] = $value['order_id'];
				$itemArray[$i]['item_id'] = $value['item_id'];
				$i++;
			}
		}
		return $itemArray;
	}
	/**
	 *
	 * $poId
	 * $poStatusId
	 * $items
	 * @author soumyas@riaxe.com
	 * @date   01 October 2020
	 * @return boolean
	 */
	public function savePurchaseOrderItems($poId, $poStatusId, $items) {
		$itemSaveStatus = false;
		$orderIdArray = array();
		if (!empty($items)) {
			foreach ($items as $key => $value) {
				$orderIdArray[] = $value['order_id'];
				$saveData = [
					'purchase_order_id' => $poId,
					'order_id' => $value['order_id'],
					'order_item_id' => $value['item_id'],
					'status_id' => $poStatusId,
				];
				$purchaseOrderDetailsInit = new PurchaseOrderDetails($saveData);
				$purchaseOrderDetailsInit->save();
			}
			if (!empty($orderIdArray)) {
				$uniqOrderId = array_unique($orderIdArray);
				foreach ($uniqOrderId as $order_id) {
					$ordersInit = new Orders();
					$OrderCount = $ordersInit->whereIn('order_id', [$order_id])->count();
					if ($OrderCount > 0) {
						/** update data in orders table */
						$updateData = [
							'po_status' => $poStatusId,
						];
						$ordersInit->where('order_id', '=', $order_id)->update($updateData);
					} else {
						/** insert  data in orders table */
						$saveData = [
							'order_id' => $order_id,
							'artwork_status' => 'pending',
							'po_status' => $poStatusId,
							'order_status' => '',
							'production_status' => '0',
							'production_percentage' => '0',
						];
						$vendorSaveInit = new Orders($saveData);
						$vendorSaveInit->save();
					}
				}
			}
			$itemSaveStatus = true;
		}
		return $itemSaveStatus;
	}
	/**
	 * GET: Get previous order list
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   10 July 2020
	 * @return json response wheather data is save or not
	 */
	public function gePreviousOrderList($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order action', 'error'),
		];
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;

		$orderArray = array();
		if (isset($args) && !empty($args)) {
			$orderIds = $args['id'];
			$orderIds = str_replace(array('[', ']'), '', $orderIds);

			$orderIdArray = explode(",", $orderIds);
			if (!empty($orderIdArray)) {
				foreach ($orderIdArray as $key => $values) {

					$orderArray[$key]['order_id'] = $values;
					$orderArray[$key]['line_items'] = $this->getOrderLineItemDetails($request, $response, $args, $values, $storeId);
				}
				$jsonResponse = [
					'status' => 1,
					'data' => $orderArray,
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Order id empty',
				];
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Args empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Get: Order line items
	 *
	 * @param $orderId
	 * @author soumyas@riaxe.com
	 * @date   10 July 2020
	 * @return Array
	 */
	public function getOrderLineItemDetails($request, $response, $args, $orderId, $storeId) {
		$orderItemArray = array();
		$orderInit = new OrdersController();
		//$storeResponse = $orderInit->getStoreOrderLineItem($orderId, $store_id);

		$storeResponse = $orderInit->orderItemDetails($request, $response, ['id' => $orderId, 'is_purchase_order' => true, 'store_id' => $storeId]);
		if (!empty($storeResponse['order_details']['order_items'])) {
			foreach ($storeResponse['order_details']['order_items'] as $lineKey => $lineValues) {
				/*
					$attributeName = $this->getAttributeName();
					if (!empty($lineValues['attributes'][$attributeName['color']])){
						$colorData = $lineValues['attributes'][$attributeName['color']];
						if (!empty($colorData)) {
							$attr[0]['id'] = $colorData['id'];
							$attr[0]['name'] = $colorData['name'];
							$variantData = $this->getColorSwatchData($attr);
							$storeResponse[$lineKey]['attributes'][$attributeName['color']] = $variantData[0];
						}
					}
				*/
				$vendorList = $this->getVendorByPrdouctId($lineValues['categories']);
				$storeResponse['order_details']['order_items'][$lineKey]['vendor_list'] = !empty($vendorList[0]) ? $vendorList[0] : [];
			}
		}

		return $storeResponse['order_details']['order_items'];
	}
	/**
	 * GET: Purchase Order List
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   05 October 2020
	 * @return json response
	 */
	public function getPurchaseOrderList($request, $response, $args, $returnType = false) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('PurchaseOrder', 'error'),
		];
		// Collect all Filter columns from url
		$page = $request->getQueryParam('page') ? $request->getQueryParam('page') : 1;
		$perpage = $request->getQueryParam('perpage') ? $request->getQueryParam('perpage') : 20;
		$order = $request->getQueryParam('order') ? $request->getQueryParam('order') : 'DESC';
		$keyword = $request->getQueryParam('keyword');
		$status_id = $request->getQueryParam('status_id');
		$vendor_ids = json_clean_decode($request->getQueryParam('vendor'));
		$ship_ids = json_clean_decode($request->getQueryParam('ship'));
		$from = $request->getQueryParam('from');
		$to = $request->getQueryParam('to');
		$getStoreDetails = get_store_details($request);
		$storeId = $getStoreDetails['store_id'];
		$purchaseOrderInit = new PurchaseOrder();
		$detailsArray = array();
		if (isset($args) && !empty($args)) {
			/** get single purchase order details  */
			$purchaseOrderArray = array();
			$xeId = $args['id'];
			$type = $args['type'];
			$getPurchaseOrder = $purchaseOrderInit
				->join('purchase_order_status', 'purchase_order.status_id', '=', 'purchase_order_status.xe_id')
				->join('ship_to_address', 'purchase_order.ship_address_id', '=', 'ship_to_address.xe_id')
				->select(
					'purchase_order.xe_id', 'purchase_order.po_id', 'purchase_order.status_id', 'purchase_order.store_id',
					'purchase_order.po_notes', 'purchase_order.vendor_id', 'purchase_order.ship_address_id', 'purchase_order.created_at', 'purchase_order.expected_delivery_date', 'purchase_order_status.status_name', 'purchase_order_status.color_code', 'ship_to_address.name', 'ship_to_address.email', 'ship_to_address.phone', 'ship_to_address.company_name', 'ship_to_address.country_code', 'ship_to_address.state_code', 'ship_to_address.zip_code', 'ship_to_address.ship_address', 'ship_to_address.city AS ship_city'
				)
				->where(['purchase_order.xe_id' => $xeId])->where(['purchase_order.store_id' => $storeId]);
			if ($getPurchaseOrder->count() > 0) {
				$purchaseOrderResponse = $getPurchaseOrder->get()->toArray();
				$purchaseOrderArray = $purchaseOrderResponse[0];
				$detailsArray['xe_id'] = $purchaseOrderArray['xe_id'];
				$detailsArray['po_id'] = $purchaseOrderArray['po_id'];
				$detailsArray['status_id'] = $purchaseOrderArray['status_id'];
				$detailsArray['store_id'] = $purchaseOrderArray['store_id'];
				$detailsArray['vendor_id'] = $purchaseOrderArray['vendor_id'];
				$detailsArray['ship_address_id'] = $purchaseOrderArray['ship_address_id'];
				$detailsArray['po_notes'] = $purchaseOrderArray['po_notes'];
				$detailsArray['expected_delivery_date'] = $purchaseOrderArray['expected_delivery_date'];
				$detailsArray['created_at'] = $purchaseOrderArray['created_at'];
				$detailsArray['status_name'] = $purchaseOrderArray['status_name'];
				$detailsArray['color_code'] = $purchaseOrderArray['color_code'];
				if ($type == "view") {
					$detailsArray['ship_to_address'] = [
						'name' => $purchaseOrderArray['name'],
						'email' => $purchaseOrderArray['email'],
						'phone' => $purchaseOrderArray['phone'],
						'company_name' => $purchaseOrderArray['company_name'],
						'country' => $purchaseOrderArray['country_code'],
						'state' => $purchaseOrderArray['state_code'],
						'city' => $purchaseOrderArray['ship_city'],
						'zip_code' => $purchaseOrderArray['zip_code'],
						'address' => $purchaseOrderArray['ship_address'],
					];
					$vendorResposne = $this->getVendorDetailsById($purchaseOrderArray['vendor_id']);
					$detailsArray['vendor_details'] = $vendorResposne;
				}
				$jsonResponse = [
					'status' => 1,
					'data' => $detailsArray,

				];
			}

		} else {
			/** get all purchase order list  */
			$getPurchaseOrders = $purchaseOrderInit
				->join('purchase_order_status', 'purchase_order.status_id', '=', 'purchase_order_status.xe_id')
				->join('vendor', 'purchase_order.vendor_id', '=', 'vendor.xe_id')
				->select(
					'purchase_order.xe_id', 'purchase_order.po_id', 'purchase_order.status_id', 'purchase_order.vendor_id', 'purchase_order.store_id',
					'purchase_order.po_notes', 'purchase_order.expected_delivery_date', 'purchase_order.ship_address_id', 'purchase_order.created_at', 'purchase_order_status.status_name', 'purchase_order_status.color_code', 'vendor.company_name', 'vendor.contact_name'
				)->where(['purchase_order.store_id' => $storeId]);
			// Pagination Data

			// Sorting All records by column name and sord order parameter
			if (isset($order) && $order != "") {
				$getPurchaseOrders->orderBy("xe_id", $order);
			}
			//Search by Purchase order po_id , company_name,contact_name
			if (isset($keyword) && $keyword != "") {
				$getPurchaseOrders->where('purchase_order.po_id', 'LIKE', '%' . $keyword . '%')
					->orWhere('vendor.company_name', 'LIKE', '%' . $keyword . '%')
					->orWhere('vendor.contact_name', 'LIKE', '%' . $keyword . '%');
			}
			//Filter by status
			if ($status_id > 0 && isset($status_id)) {
				$getPurchaseOrders->where('purchase_order.status_id', $status_id);
			}
			// Filter by vendor
			if (!empty($vendor_ids)) {
				$getPurchaseOrders->whereIn('purchase_order.vendor_id', $vendor_ids);

			}
			// Filter by ship address
			if (!empty($ship_ids)) {
				$getPurchaseOrders->whereIn('purchase_order.ship_address_id', $ship_ids);
			}
			// Filter by date
			if (!empty($from) && !empty($to)) {
				if ($from != "" && $to == '') {
					//Filter by only from date
					$getPurchaseOrders->where('purchase_order.created_at', '>=', $from);
				} else if ($from == "" && $to != '') {
					//Filter by only to date
					$getPurchaseOrders->where('purchase_order.created_at', '<=', $to);
				} else if ($from != "" && $to != '') {
					//Filter by from and to date
					$getPurchaseOrders->where('purchase_order.created_at', '>=', $from)
						->where('purchase_order.created_at', '<=', $to);
				}
			}
			$getTotalRecords = $getPurchaseOrders->count();
			$offset = 0;
			if (isset($page) && $page != "") {
				$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
				$offset = $totalItem * ($page - 1);
				$getPurchaseOrders->skip($offset)->take($totalItem);
			}
			if ($getTotalRecords > 0) {
				$getPurchaseOrderData = $getPurchaseOrders->get()->toArray();
				$jsonResponse = [
					'status' => 1,
					'total_records' => $getTotalRecords,
					'records' => count($getPurchaseOrderData),
					'data' => $getPurchaseOrderData,
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => "No data found",
					'data' => [],
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Get: Purchase order line items
	 *
	 * @param $poId
	 * @author soumyas@riaxe.com
	 * @date   05 October 2020
	 * @return Array
	 */
	public function getPurchaseOrderLineItems($poId, $type, $storeId) {
		$lineItemArray = array();
		$purchaseOrderDetailsInit = new PurchaseOrderDetails();
		$getPurchaseOrderDetails = $purchaseOrderDetailsInit->where(['purchase_order_id' => $poId]);
		if ($getPurchaseOrderDetails->get()->count() > 0) {
			$itemsResponse = $getPurchaseOrderDetails->get()->toArray();
			foreach ($itemsResponse as $key => $value) {
				$lineItemArray[$key]['xe_id'] = $value['xe_id'];
				$lineItemArray[$key]['purchase_order_id'] = $value['purchase_order_id'];
				$lineItemArray[$key]['order_id'] = $value['order_id'];
				$lineItemArray[$key]['order_item_id'] = $value['order_item_id'];
				$lineItemArray[$key]['status_id'] = $value['status_id'];
				$statusResponse = $this->getPurchaseOrderLineItemStatusDetails($value['status_id']);
				$lineItemArray[$key]['status_color_code'] = $statusResponse[0]['color_code'] ? $statusResponse[0]['color_code'] : '';
				$items = $this->purchaseOrderItemDetails($value['order_id'], $value['order_item_id'], $type, $storeId);
				$lineItemArray[$key]['product_id'] = $items[0]['product_id'];
				$lineItemArray[$key]['variant_id'] = $items[0]['variant_id'] ? $items[0]['variant_id'] : 0;
				$lineItemArray[$key]['name'] = $items[0]['name'];
				$lineItemArray[$key]['sku'] = $items[0]['sku'] ? $items[0]['sku'] : '';
				$lineItemArray[$key]['quantity'] = $items[0]['quantity'];
				$lineItemArray[$key]['images'] = $items[0]['images'];
				$lineItemArray[$key]['attributes'] = !empty($items[0]['attributes']) ? $items[0]['attributes'] : [];
				$lineItemArray[$key]['vendors'] = [];

			}
		}
		return $lineItemArray;
	}
	/**
	 * Get: Purchase order line items deatils
	 *
	 * @param $orderId
	 * @param $orderItemId
	 * @author soumyas@riaxe.com
	 * @date   05 October 2020
	 * @return Array
	 */
	public function purchaseOrderItemDetails($orderId, $orderItemId, $type, $storeId) {
		$itemDetailsArray = [];
		if ($orderId && $orderItemId) {
			$orderInit = new OrdersController();
			//echo $orderItemId;
			$storeResponse = $orderInit->getStoreOrderLineItemDetails($orderId, $orderItemId, $is_customer = false, $storeId);
			$itemDetailsArray[] = $storeResponse;

			/*
				if (!empty($storeResponse)) {
					foreach ($storeResponse as $lineKey => $lineValues) {
						if($lineValues['item_id'] == $orderItemId) {
							$attributeName = $this->getAttributeName();
							if (!empty($lineValues['attributes'][$attributeName['color']])){
								$colorData = $lineValues['attributes'][$attributeName['color']];
								if (!empty($colorData)) {
									$attr[0]['id'] = $colorData['id'];
									$attr[0]['name'] = $colorData['name'];
									$variantData = $this->getColorSwatchData($attr);
								}
							}
							$itemDetailsArray[$lineKey] = $lineValues;
							$itemDetailsArray[$lineKey]['attributes'][$attributeName['color']] = $variantData[0] ? $variantData[0]:[];
							$itemDetailsArray[$lineKey]['vendor_list']=[];
						}
					}
				}
			*/
		}

		return $itemDetailsArray;
	}
	/**
	 * Get: productId  categories
	 *
	 * @param $productId
	 * @author soumyas@riaxe.com
	 * @date   05 October 2020
	 * @return Array
	 */
	public function getVendorByPrdouctId($categoryIds) {
		$vendorArray = array();
		if (!empty($categoryIds)) {
			foreach ($categoryIds as $id) {
				$vendorCatInit = new VendorCategory();
				$getCategoryId = $vendorCatInit
					->join('vendor', 'vendor_category_rel.vendor_id', '=', 'vendor.xe_id')
					->select('vendor.xe_id AS id', 'vendor.company_name', 'vendor.contact_name')
					->where(['vendor_category_rel.category_id' => $id]);
				if ($getCategoryId->count() > 0) {
					$vendorList = $getCategoryId->get()->toArray();
					$vendorArray[] = $vendorList;
				}
			}
		}
		return $vendorArray;
	}
	/**
	 * POST: Update purchase order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   19 June 2020
	 * @return json
	 */
	public function updatePurchaseOrderStatus($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order status', 'error'),
		];
		$productionJobData = [];
		if (isset($args) && !empty($args)) {

			$xeId = $args['id'];
			$allPostPutVars = $request->getParsedBody();
			$poStatusId = $allPostPutVars['status_id'] ? $allPostPutVars['status_id'] : '';
			$currentStatusName = $allPostPutVars['status_name'] ? $allPostPutVars['status_name'] : '';
			$previousStatusName = $allPostPutVars['prev_status_name'] ? $allPostPutVars['prev_status_name'] : '';
			$storeId = $allPostPutVars['store_id'] ? $allPostPutVars['store_id'] : 1;
			$moduleId = 3;
			$poLineItemStatusId = 1;
			$poStatusType = $this->getPurchaseOrderStatusType($poStatusId, $storeId, $moduleId);
			$statusType = '';
			if (!empty($poStatusType)) {
				if ($poStatusType['type'] == 'received') {
					$poLineItemStatusId = 3;
					$statusType = $poStatusType['type'];
				}
			}
			$purchaseOrderInit = new PurchaseOrder();
			$poId = $purchaseOrderInit->whereIn('xe_id', [$xeId])->count();
			if ($poId > 0) {
				$updateData = [
					'status_id' => $poStatusId,
				];
				$getPurchaseOrder = $purchaseOrderInit->where(['xe_id' => $xeId]);
				$getPurchaseOrderDetails = $getPurchaseOrder->get()->toArray();
				$getPurchaseOrderData = $getPurchaseOrderDetails[0];
				$poId = $getPurchaseOrderData['po_id'] ? $getPurchaseOrderData['po_id'] : '0';
				$status = $purchaseOrderInit->where('xe_id', '=', $xeId)->update($updateData);
				$purchaseOrderDetailsInit = new PurchaseOrderDetails();
				if ($status) {
					$purchaseOrderId = $purchaseOrderDetailsInit->whereIn('purchase_order_id', [$xeId])->count();
					if ($purchaseOrderId > 0) {
						$updateData = [
							'status_id' => $poLineItemStatusId,
						];
						$purchaseOrderDetailsInit->where('purchase_order_id', '=', $xeId)->update($updateData);
					}
					$orderIds = $this->getOrderIdByPurchaseOrderId($xeId);
					if (!empty($orderIds)) {
						foreach ($orderIds as $orderId) {
							$returnData = $this->updateOrderPoStatus($orderId, $poStatusId, $statusType, $poLineItemStatusId);
							array_push($productionJobData, $returnData);
						}
					}
					/** insert into po_log table */
					$description = 'PO status changed : Status of PO #' . $poId . ' is changed from ' . $previousStatusName . ' to ' . $currentStatusName . ' by admin.';
					$logData = [
						'po_id' => $xeId,
						'description' => $description,
						'user_type' => 'admin',
						'user_id' => 1,
						'created_date' => date_time(
							'today', [], 'string'
						)
					];
					$this->addingPurchaseOrderLog($logData);
					$jsonResponse = [
						'status' => 1,
						'production_job_data' => $productionJobData,
						'message' => 'Updated successfully',
					];
				} else {
					$jsonResponse = [
						'status' => 0,
						'message' => 'No data updated',
					];
				}
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Purchase order id not found',
				];
			}

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Purchase order id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Update purchase order line item
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   19 June 2020
	 * @return json
	 */
	public function updatePurchaseOrderLineItemStatus($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order status', 'error'),
		];
		$productionJobData = [];
		$allPostPutVars = $request->getParsedBody();
		$statusId = $allPostPutVars['status_id'] ? $allPostPutVars['status_id'] : 1;
		$purchaseOrderId = $allPostPutVars['purchase_order_id'] ? $allPostPutVars['purchase_order_id'] : '';
		$orderIds = json_clean_decode($allPostPutVars['order_id']);
		$orderItemIds = json_clean_decode($allPostPutVars['order_item_id']);
		$currentStatusName = $allPostPutVars['status_name'] ? $allPostPutVars['status_name'] : '';
		$previousStatusName = $allPostPutVars['prev_status_name'] ? $allPostPutVars['prev_status_name'] : '';
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$orderIdArray = array();
		$resposeArray = [];
		if (!empty($orderIds) && !empty($orderItemIds) && !empty($purchaseOrderId)) {
			$i = 0;
			foreach ($orderIds as $orderId) {
				$orderIdArray[$i]['order_id'] = $orderId;
				$orderIdItemArray = [];
				$poItemId = [];
				$purchaseOrderDetailsInit = new PurchaseOrderDetails();
				$getOrderId = $purchaseOrderDetailsInit->where(['order_id' => $orderId]);
				if ($getOrderId->count() > 0) {
					$getOrderIds = $getOrderId->get()->toArray();
					foreach ($getOrderIds as $key => $value) {
						$orderIdItemArray[$key]['order_id'] = $orderId;
						$orderIdItemArray[$key]['order_item_id'] = $value['order_item_id'];
						/** get po item id */

					}
					$orderIdArray[$i]['order_item_id'] = $orderIdItemArray;
				}
				$i++;
			}
			if (!empty($orderIdArray)) {
				$orderResposeArray = [];
				foreach ($orderIdArray as $key => $value) {
					foreach ($value['order_item_id'] as $keyItems => $valueItems) {
						if (in_array($valueItems['order_item_id'], $orderItemIds)) {
							$orderResposeArray[] = $valueItems;
						}
					}
				}
			}
			$orderResposne = [];
			if (!empty($orderResposeArray)) {
				foreach ($orderResposeArray as $key => $value) {
					$order_id = $value['order_id'];
					$order_item_id = $value['order_item_id'];
					if ($order_id & $order_item_id) {
						$updateData = [
							'status_id' => $statusId,
						];
						$purchaseOrderDetailsInit->where('order_id', '=', $order_id)->where('order_item_id', '=', $order_item_id)->update($updateData);

						/** po linr item id */
						$poLineItemIds = $purchaseOrderDetailsInit->where(['order_id' => $orderId])->where(['order_item_id' => $order_item_id]);
						if ($poLineItemIds->count() > 0) {
							$poLineItemId = $poLineItemIds->get()->toArray();
							$poItemId[] = $poLineItemId[0];
						}

						/** update order status */
						$returnData = $this->updateOrderPoStatus($order_id, $statusId, null, $statusId);
						array_push($productionJobData, $returnData);
					}
					$orderResposne[] = $this->purchaseOrderItemDetails($order_id, $order_item_id, '', $storeId);
				}
				if (!empty($orderResposne)) {
					$lineItemArray = array();

					foreach ($orderResposne as $orderKey => $orderValue) {
						$combinationValues = $orderValue['product_id'] . '_' . $orderValue['variant_id'];
						if (!in_array($combinationValues, $lineItemArray)) {
							array_push($lineItemArray, $combinationValues);
							$resposeArray[] = $orderValue['name'];
						}
					}
				}
			}
			$currentPoStatus = '';
			if ($purchaseOrderId) {
				$moduleId = 3;
				/** po status Received */
				$po_status_id = 3;
				$statusIds = [];
				$receivedStatus = false;
				$purchaseOrderDetailsInit = new PurchaseOrderDetails();
				$getPurchaseOrderDetails = $purchaseOrderDetailsInit->where(['purchase_order_id' => $purchaseOrderId]);
				if ($getPurchaseOrderDetails->count() > 0) {
					$purchaseOrderDetails = $getPurchaseOrderDetails->get()->toArray();
					foreach ($purchaseOrderDetails as $key => $value) {
						$statusIds[] = $value['status_id'];
					}
					if (array_unique($statusIds) === array($po_status_id)) {
						$receivedStatus = true;
					}
				}
				if ($receivedStatus == 1) {
					$poStatusId = $this->getPurchaseOrderStatusIdBySlug('received', $storeId, $moduleId);
					if (!empty($poStatusId)) {
						$purchaseOrderInit = new PurchaseOrder();
						$updateData = [
							'status_id' => $poStatusId['id'], /** Received */
						];
						$purchaseOrderInit->where('xe_id', '=', $purchaseOrderId)->update($updateData);
						$purchaseOrderInit->where(['xe_id' => $purchaseOrderId]);
						$getpurchaseOrder = $purchaseOrderInit->where(['xe_id' => $purchaseOrderId]);
						if ($getpurchaseOrder->count() > 0) {
							$poStatus = $getpurchaseOrder->get()->toArray();
							$currentPoStatus = $poStatus[0]['status_id'];
						}
					}

				} else {
					if (in_array(3, $statusIds)) {
						$poStatusId = $this->getPurchaseOrderStatusIdBySlug('partially_received', $storeId, $moduleId);
						if (!empty($poStatusId)) {
							$purchaseOrderInit = new PurchaseOrder();
							$updateData = [
								'status_id' => $poStatusId['id'], /** Partially received */
							];
							$purchaseOrderInit->where('xe_id', '=', $purchaseOrderId)->update($updateData);
							$getpurchaseOrder = $purchaseOrderInit->where(['xe_id' => $purchaseOrderId]);
							if ($getpurchaseOrder->count() > 0) {
								$poStatus = $getpurchaseOrder->get()->toArray();
								$currentPoStatus = $poStatus[0]['status_id'];
							}
						}

					} else {
						$poStatusId = $this->getPurchaseOrderStatusIdBySlug('pending', $storeId, $moduleId);
						if (!empty($poStatusId)) {
							$purchaseOrderInit = new PurchaseOrder();
							$updateData = [
								'status_id' => $poStatusId['id'], /** Pending */
							];
							$purchaseOrderInit->where('xe_id', '=', $purchaseOrderId)->update($updateData);
							$getpurchaseOrder = $purchaseOrderInit->where(['xe_id' => $purchaseOrderId]);
							if ($getpurchaseOrder->count() > 0) {
								$poStatus = $getpurchaseOrder->get()->toArray();
								$currentPoStatus = $poStatus[0]['status_id'];
							}
						}

					}
				}
			}
			$productName = $resposeArray[0] ? $resposeArray[0] : '';
			$description = 'PO item status changed  : Status of PO item   ' . $productName . ' changed from ' . $previousStatusName . ' to ' . $currentStatusName . ' by admin.';
			/** insert into po_log table */
			$logData = [
				'po_id' => $purchaseOrderId,
				'description' => $description,
				'user_type' => 'admin',
				'user_id' => 1,
				'created_date' => date_time(
					'today', [], 'string'
				)
			];
			$this->addingPurchaseOrderLog($logData);
			$jsonResponse = [
				'status' => 1,
				'production_job_data' => $productionJobData,
				'message' => 'Updated successfully',
				'po_status' => $currentPoStatus,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Create Purchase Order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   07 October 2020
	 * @return json response wheather data is save or not
	 */
	public function getOrderListFromPreviousPoDate($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order status', 'error'),
		];
		$module_id = 3;
		$orderResponseArray = array();
		$conversion_days = 0;
		$order_qty = 0;
		$last_po_date = '';
		$getStoreDetails = get_store_details($request);
		$settingInit = new ProductionHubSetting();
		$settingData = $settingInit->select('setting_value', 'setting_key')
			->where([
				'module_id' => $module_id,
				'store_id' => $getStoreDetails['store_id'],
			]);
		if ($settingData->count() > 0) {
			$settingDataArr = $settingData->get()->toArray();
			if (!empty($settingDataArr)) {
				foreach ($settingDataArr as $key => $value) {
					if ($value['setting_key'] == 'convert_order') {
						$convertOrder = json_clean_decode($value['setting_value'], true);
						$conversion_days = $convertOrder['conversion_days'];
						$order_qty = $convertOrder['order_qty'];
					}
					if ($value['setting_key'] == 'last_po_date') {
						$latPoDate = json_clean_decode($value['setting_value'], true);
						$last_po_date = $latPoDate['date'];
					}
				}
			}

		}
		$currentDate = date('Y-m-d');
		$currentTime = date('h.i.s', time());
		if (!empty($last_po_date) && !empty($conversion_days)) {
			$startTimeStamp = strtotime($last_po_date);
			$endTimeStamp = strtotime($currentDate);
			$timeDiff = abs($endTimeStamp - $startTimeStamp);
			$numberDays = intval($timeDiff / 86400);
			if ($numberDays == $conversion_days) {
				$endPoint = 'orders-graph?from=' . $last_po_date . $currentTime . '&to=' . $currentDate . $currentTime . '&store_id=' . $getStoreDetails['store_id'];
				if (strtolower(STORE_NAME) == "prestashop") {
					$orderArray = call_curl([], $endPoint, 'GET', true);
				} else {
					$orderArray = call_api($endPoint, 'GET', []);
				}

				if (!empty($orderArray['data']['order_data'])) {
					$ordersInit = new Orders();
					foreach ($orderArray['data']['order_data'] as $orderDetailsKey => $orderDetails) {
						$orderPoStatus = $ordersInit->where('order_id', $orderDetails['id'])->first();
						$orderArray['data']['order_data'][$orderDetailsKey] += ['po_status' => (!empty($orderPoStatus->po_status)) ? $orderPoStatus->po_status : '0'];
					}
					$orderList = $orderArray['data']['order_data'];
					foreach ($orderList as $key => $value) {
						if ($value['po_status'] == 0 && $value['order_total_quantity'] >= $order_qty) {
							$orderResponseArray[] = $value;
						}
					}
					$jsonResponse = [
						'status' => 1,
						'data' => $orderResponseArray,

					];
				} else {
					$jsonResponse = [
						'status' => 0,
						'message' => 'Order List empty',
					];
				}
				$jsonResponse = [
					'status' => 1,
					'data' => $orderResponseArray,
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Order List not found',
				];
			}

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Last Purchase order empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);

	}
	/**
	 * Delete : Purchase Order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   19 June 2020
	 * @return json
	 */
	public function deletePurchaseOrder($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order delete', 'error'),
		];
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			$getDeleteIdsToArray = json_clean_decode($xeId, true);
			$this->updateOrderStatusForPo($getDeleteIdsToArray); /** update order po status */
			$purchaseOrderInit = new PurchaseOrder();
			$poCount = $purchaseOrderInit->whereIn('xe_id', $getDeleteIdsToArray)
				->count();
			if ($poCount > 0) {
				$status = $purchaseOrderInit->whereIn('xe_id', $getDeleteIdsToArray)->delete();
				if ($status > 0) {
					$purchaseOrderDetailsInit = new PurchaseOrderDetails();
					$purchaseOrderId = $purchaseOrderDetailsInit->whereIn('purchase_order_id', $getDeleteIdsToArray)
						->count();
					if ($purchaseOrderId > 0) {
						$detailsStatus = $purchaseOrderDetailsInit->whereIn('purchase_order_id', $getDeleteIdsToArray)->delete();
					}
					$jsonResponse = [
						'status' => 1,
						'message' => 'Deleted successfully',
					];
				}
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Purchase order id do not match',
				];
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Purchase order id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST : Update Purchase Order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   08 October 2020
	 * @return json
	 */
	public function updatePurchaseOrder($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order update', 'error'),
		];
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			$purchaseOrderInit = new PurchaseOrder();
			$xeIdCount = $purchaseOrderInit->where(['xe_id' => $xeId]);
			if ($xeIdCount->count() > 0) {
				$allPostPutVars = $request->getParsedBody();
				$vendorId = $allPostPutVars['vendor_id'] ? $allPostPutVars['vendor_id'] : '';
				$expectedDeliveryDate = $allPostPutVars['expected_delivery_date'] ? $allPostPutVars['expected_delivery_date'] : '';
				$poNotes = $allPostPutVars['po_notes'] ? $allPostPutVars['po_notes'] : '';
				$shipAddressId = $allPostPutVars['ship_address_id'] ? $allPostPutVars['ship_address_id'] : '';
				$updateData = [
					'vendor_id' => $vendorId,
					'ship_address_id' => $shipAddressId,
					'po_notes' => $poNotes,
					'expected_delivery_date' => $expectedDeliveryDate,
				];
				$purchaseOrderInit->where('xe_id', '=', $xeId)->update($updateData);
				$jsonResponse = [
					'status' => 1,
					'message' => 'Updated successfully',
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST : Update Purchase Order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   09 October 2020
	 * @return json
	 */
	public function getOrderListForPo($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order update', 'error'),
		];
		//Get purchase order setting data
		$getStoreDetails = get_store_details($request);
		$settingInit = new ProductionHubSetting();
		$settingData = $settingInit->select('setting_value', 'flag')
			->where([
				'module_id' => 3,
				'setting_key' => 'convert_order',
				'store_id' => $getStoreDetails['store_id'],
			]);
		$orderQty = 1;
		if ($settingData->count() > 0) {
			$settingDataArr = $settingData->first()->toArray();
			$settingValue = json_clean_decode($settingDataArr['setting_value'], true);
			$orderQty = $settingValue['order_qty'] ? $settingValue['order_qty'] : 1;

		}
		$orderInit = new OrdersController();
		$storeResponse = $orderInit->getOrders($request, $response, ['store_id' => $getStoreDetails['store_id']]);
		$ordersInit = new Orders();
		$ordersIds = [];
		$ordersData = $ordersInit->select('order_id')
			->where([
				'store_id' => $getStoreDetails['store_id'],
				'po_status' => !0,
			]);
		if ($ordersData->count() > 0) {
			foreach ($ordersData->get()->toArray() as $key => $value) {
				$ordersIds[$key] = $value['order_id'];
			}

		}
		$orderArray = array();
		if (!empty($storeResponse['order_details'])) {
			foreach ($storeResponse['order_details'] as $key => $value) {
				if (!in_array($value['id'], $ordersIds) && $value['order_total_quantity'] >= $orderQty) {
					$orderArray[] = $value;
				}
			}
			$jsonResponse = [
				'status' => 1,
				'data' => $orderArray,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Update : Purchase Order status
	 *
	 * @param $purchase_order_id  Slim's Request object
	 *
	 * @author soumyas@riaxe.com
	 * @date   09 October 2020
	 * @return json
	 */
	public function updateOrderStatusForPo($purchase_order_ids) {
		$poOrderId = array();
		$updateStatus = false;
		if (!empty($purchase_order_ids)) {
			$purchaseOrderDetailsInit = new PurchaseOrderDetails();
			foreach ($purchase_order_ids as $purchase_order_id) {
				$getPurchaseOrderDetails = $purchaseOrderDetailsInit->where(['purchase_order_id' => $purchase_order_id]);
				if ($getPurchaseOrderDetails->get()->count() > 0) {
					$itemsResponse = $getPurchaseOrderDetails->get()->toArray();
					foreach ($itemsResponse as $key => $value) {
						$poOrderId[] = $value['order_id'];
					}
				}
			}
			if (!empty($poOrderId)) {
				$uniqueOrderId = array_unique($poOrderId);
				$ordersInit = new Orders();
				foreach ($uniqueOrderId as $order_id) {
					$orderCount = $ordersInit->whereIn('order_id', [$order_id])->count();
					if ($orderCount > 0) {
						/** update data in orders table */
						$updateData = [
							'po_status' => '0',
						];
						$ordersInit->where('order_id', '=', $order_id)->update($updateData);
						$updateStatus = true;
					}
				}
			}
		}
		return $updateStatus;
	}
	/**
	 * Get : Vendor details
	 *
	 * @param $purchase_order_id  Slim's Request object
	 *
	 * @author soumyas@riaxe.com
	 * @date   09 October 2020
	 * @return Array
	 */
	public function getVendorDetailsById($vendorId) {
		$vendorDtails = array();
		if ($vendorId > 0) {
			$vendorInit = new Vendor();
			$getvendor = $vendorInit->where(['xe_id' => $vendorId]);
			if ($getvendor->count() > 0) {
				$vendorData = $getvendor->get()->toArray();
				$vendorDtails = $vendorData[0];
			}
		}
		return $vendorDtails;
	}
	/**
	 * GET: Purchase Order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   09 October 2020
	 * @return json response wheather data is save or not
	 */
	public function purchaseOrderAction($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order action', 'error'),
		];
		if (isset($args) && !empty($args)) {
			if (!empty($_REQUEST['_token'])) {
				$getToken = $_REQUEST['_token'];
			} else {
				$getToken = '';
			}
			$storeDetails = get_store_details($request);
			$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;

			$action = $args['type'];
			if ($action == "download") {
				$xeId = $args['id'];
				$purchaseOrderInit = new PurchaseOrder();
				$getPurchaseOrder = $purchaseOrderInit->where(['xe_id' => $xeId]);
				if ($getPurchaseOrder->count() > 0) {
					$purchaseOrderData = $getPurchaseOrder->get()->toArray();
					$filePath = $this->pdfFilePath . $purchaseOrderData[0]['po_id'] . '.pdf';
					$dir = '';
					if (file_exists($filePath)) {
						$dir = $filePath;
					} else {
						$pdfResponse = $this->createPurchaseOrderPdf($purchaseOrderData[0]['xe_id'], $getToken, $storeId);
						if ($pdfResponse['status'] == 1) {
							$dir = $pdfResponse['file_path'];
						}
					}
					if (file_download($dir)) {
						$jsonResponse = [
							'status' => 1,
							'message' => 'File download successfully',
						];
					} else {
						$jsonResponse = [
							'status' => 0,
							'message' => 'File download faild',
						];
					}
				}
			}
			if ($action == "email") {
				$xeId = $args['id'];
			}

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Purchase order  id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Purchase Order List
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   22 June 2020
	 * @return json response wheather data is save or not
	 */
	public function createPurchaseOrderPdf($poId, $getToken, $storeId) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Purchase order PDF', 'error'),
		];
		$fileResponse = array();
		//echo $poId;exit;
		if ($poId) {
			$html = '';
			$purchaseOrderDetails = $this->purchaseOrderPdfData($poId, $getToken, $storeId);
			if (!empty($purchaseOrderDetails)) {
				$total_quantity = array_sum(array_column($purchaseOrderDetails['line_items'], 'quantity'));
				$pdf_file_name = $purchaseOrderDetails['po_id'];
				$dateFormat = date('jS F , Y', strtotime(date('Y-m-d')));
				$html .= '';
				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 16px;">';
				/** hreader section */
				$html .= '<tr>';
				$html .= '<td>';
				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
				$html .= '<tr>';
				$html .= '<td>';
				$html .= '<h2>Purchase Order</h2>';
				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
				$html .= '<tr><td><b>PO:</b> #' . $purchaseOrderDetails['po_id'] . '</td></tr>';
				$html .= '<tr><td><b>Created Date:</b> ' . date('jS F , Y', strtotime($purchaseOrderDetails['created_at'])) . '</td></tr>';
				$html .= '<tr><td><b>Exp Delevery Date:</b> ' . date('jS F , Y', strtotime($purchaseOrderDetails['expected_delivery_date'])) . '</td></tr>';
				$html .= '</table>';
				$html .= '</td>';
				/** logo section */
				$html .= '<td align="right" valign="top">';
				$html .= '<img alt="logo" src="' . $purchaseOrderDetails['vendor_details']['logo'] . '" width="125px" height="125px">';
				$html .= '</td>';
				/** logo section */
				$html .= '</tr>';
				$html .= '</table>';
				$html .= '</td>';
				$html .= '</tr>';
				/** hreader section */
				/** header margin bottom */
				$html .= '<tr><td>&nbsp;</td></tr>';
				/** header margin bottom */
				/** Vendor & shipping address */
				$html .= '<tr>';
				$html .= '<td>';
				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
				$html .= '<tr>';
				/** Vendor address */
				$html .= '<td width="50%" style="border:1px solid #ddd; padding:10px;">';
				$html .= '<h3>Vendor Address</h3>';
				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';

				$html .= '<tr>';
				$html .= '<td width="30%"><b>Supplier</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['vendor_details']['company_name'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Contact Person</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['vendor_details']['contact_name'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Email</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['vendor_details']['email'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Phone</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['vendor_details']['phone'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Address</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['vendor_details']['billing_address'] . '</td>';
				$html .= '</tr>';

				$html .= '</table>';
				$html .= '</td>';
				/** Vendor address */

				/** Ship address */
				$html .= '<td align="left" valign="top" width="50%" style="border:1px solid #ddd; padding:10px;">';
				$html .= '<h3>Ship To Address</h3>';

				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';

				$html .= '<tr>';
				$html .= '<td width="30%"><b>Company Name</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['ship_to_address']['company'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Contact Person</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['ship_to_address']['name'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Email</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['ship_to_address']['email'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Phone</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['ship_to_address']['phone'] . '</td>';
				$html .= '</tr>';

				$html .= '<tr>';
				$html .= '<td><b>Address</b></td>';
				$html .= '<td>' . $purchaseOrderDetails['ship_to_address']['ship_address'] . '</td>';
				$html .= '</tr>';

				$html .= '</table>';
				$html .= '</td>';
				/** Ship address */
				$html .= '</tr>';
				$html .= '</table>';

				$html .= '</td>';
				$html .= '</tr>';
				/** Vendor & shipping address */
				/** address margin bottom */
				$html .= '<tr><td>&nbsp;</td></tr>';
				/** address margin bottom */
				/** product section */
				$html .= '<tr>';
				$html .= '<td align="left" valign="top">';
				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
				$html .= '<tr>';
				$html .= '<td>';
				$html .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
				/** static section */
				$html .= '<tr style="background-color: #efefef;">';
				$html .= '<td width="25%" style="border-bottom:1px solid #ddd; padding:5px;"><b>Product Name</b></td>';
				if(strtolower(STORE_NAME) !='shopify') {
					$html .= '<td width="25%" style="border-bottom:1px solid #ddd; padding:5px;"><b>Product Image</b></td>';
				}
				$html .= '<td width="25%" style="border-bottom:1px solid #ddd; padding:5px;"><b>SKU</b></td>';
				$html .= '<td width="25%" style="border-bottom:1px solid #ddd; padding:5px;"><b>Quantity</b></td>';
				$html .= '</tr>';
				/** static section */
				/** dynamic section */
				if (!empty($purchaseOrderDetails['line_items'])) {
					foreach ($purchaseOrderDetails['line_items'] as $key => $value) {
						$name = $value['name'];
						$sku = $value['sku'];
						$image = $value['image'];
						$quantity = $value['quantity'];
						$html .= '<tr>';
						$html .= '<td style="border-bottom:1px solid #ddd; padding:5px;">' . $name . '</td>';
						if(strtolower(STORE_NAME) !='shopify'){
							$html .= '<td style="border-bottom:1px solid #ddd; padding:5px;"><img src="' . $image . '" width="40px" height="40px"></td>';
						}
						$html .= '<td style="border-bottom:1px solid #ddd; padding:5px;">' . $sku . '</td>';
						$html .= '<td style="border-bottom:1px solid #ddd; padding:5px;">' . $quantity . '</td>';
						$html .= '</tr>';
					}
				}
				/** dynamic section */
				$html .= '</table>';
				$html .= '</td>';
				$html .= '</tr>';
				$html .= '</table>';
				$html .= '</td>';
				$html .= ' </tr>';
				/** product section */
				$html .= '<tr><td>&nbsp;</td></tr>';
				/** po notes setion */
				$html .= '<tr>';
				$html .= '<td style="text-align: justify">';
				$html .= '<h3 style="margin-bottom: 5px;">PO Notes</h3>';
				$html .= '<p>' . $purchaseOrderDetails['po_note'] . '</p>';
				$html .= '</td>';
				$html .= '</tr>';
				/** po notes setion */
				$html .= '</table>';

				//echo $html;exit;
				$orientation = "portrait";
				$fileNames = create_pdf($html, $this->pdfFilePath, $pdf_file_name, $orientation);
				if ($fileNames) {
					$dir = $this->pdfFilePath . $pdf_file_name . '.pdf';
					$fileResponse = array(
						'status' => 1,
						'file_path' => $dir,
					);

				}
			} else {
				$fileResponse = array(
					'status' => 0,
					'file_path' => '',
				);
			}
		}
		return $fileResponse;
	}
	/**
	 * Get: Purchase Order Details
	 *
	 * @param $poId  Slim's Request object
	 * @author soumyas@riaxe.com
	 * @date   10 July 2020
	 * @return Array
	 */
	public function purchaseOrderPdfData($poId, $getToken, $storeId) {
		$detailsArray = array();
		$purchaseOrderInit = new PurchaseOrder();
		$getPurchaseOrder = $purchaseOrderInit
			->join('vendor', 'purchase_order.vendor_id', '=', 'vendor.xe_id')
			->join('purchase_order_status', 'purchase_order.status_id', '=', 'purchase_order_status.xe_id')
			->join('ship_to_address', 'purchase_order.ship_address_id', '=', 'ship_to_address.xe_id')
			->select(
				'purchase_order.xe_id', 'purchase_order.po_id',
				'purchase_order.vendor_id', 'purchase_order.status_id', 'purchase_order.store_id',
				'purchase_order.po_notes', 'purchase_order.expected_delivery_date', 'purchase_order.ship_address_id', 'purchase_order.created_at', 'vendor.company_name', 'vendor.contact_name', 'vendor.email', 'vendor.phone', 'vendor.logo', 'vendor.country_code', 'vendor.state_code', 'vendor.zip_code', 'vendor.billing_address', 'purchase_order_status.status_name', 'purchase_order_status.color_code', 'ship_to_address.name As ship_to_name', 'ship_to_address.email As ship_to_email', 'ship_to_address.company_name As ship_to_company', 'ship_to_address.phone As ship_to_phone', 'ship_to_address.country_code As ship_to_country_code', 'ship_to_address.state_code As ship_to_state_code', 'ship_to_address.zip_code As ship_to_zip_code', 'ship_to_address.ship_address'
			)
			->where(['purchase_order.xe_id' => $poId]);
		if ($getPurchaseOrder->count() > 0) {
			$logFileUrl = ASSETS_PATH_R . 'vendor/';
			$purchaseOrderResponse = $getPurchaseOrder->get()->toArray();
			$purchaseOrderArray = $purchaseOrderResponse[0];
			$detailsArray['po_id'] = $purchaseOrderArray['po_id'];
			$detailsArray['po_note'] = $purchaseOrderArray['po_notes'];
			$detailsArray['expected_delivery_date'] = date('jS F Y', strtotime($purchaseOrderArray['expected_delivery_date']));
			$detailsArray['created_at'] = date('jS F Y', strtotime($purchaseOrderArray['created_at']));
			$detailsArray['color_code'] = $purchaseOrderArray['color_code'];
			$detailsArray['status_name'] = $purchaseOrderArray['status_name'];
			/** get vendor details */
			$detailsArray['vendor_details']['vendor_id'] = $purchaseOrderArray['vendor_id'];
			$detailsArray['vendor_details']['company_name'] = $purchaseOrderArray['company_name'];
			$detailsArray['vendor_details']['contact_name'] = $purchaseOrderArray['contact_name'];
			$detailsArray['vendor_details']['email'] = $purchaseOrderArray['email'];
			$detailsArray['vendor_details']['phone'] = $purchaseOrderArray['phone'];
			$fileExtension = pathinfo(strtolower($purchaseOrderArray['logo']), PATHINFO_EXTENSION);
			if ($fileExtension == "svg") {
				$detailsArray['vendor_details']['logo'] = $logFileUrl . $purchaseOrderArray['logo'];
			} else if ($fileExtension == "bmp") {
				$detailsArray['vendor_details']['logo'] = $logFileUrl . $purchaseOrderArray['logo'];
			} else {
				$detailsArray['vendor_details']['logo'] = $logFileUrl . "thumb_" . $purchaseOrderArray['logo'];
			}
			$vendorCountryName = '';
			$vendorStateName = '';
			if ($purchaseOrderArray['country_code']) {
				$countryEndPoint = 'country';
				$getcountryResponse = call_api($countryEndPoint, 'GET', []);
				if (!empty($getcountryResponse['data'])) {
					foreach ($getcountryResponse['data'] as $countryKey => $countryValue) {
						if ($countryValue['countries_code'] == $purchaseOrderArray['country_code']) {
							$vendorCountryName = $countryValue['countries_name'];
							break;
						} else {
							$vendorCountryName = $purchaseOrderArray['country_code'];
						}
					}
					if ($purchaseOrderArray['state_code']) {
						$stateEndPoint = 'state/' . $purchaseOrderArray['country_code'];
						$getStateResponse = call_api($stateEndPoint, 'GET', []);

						if (!empty($getStateResponse['data'])) {
							foreach ($getStateResponse['data'] as $stateKey => $stateValue) {
								if ($stateValue['state_code'] == $purchaseOrderArray['state_code']) {
									$vendorStateName = $stateValue['state_name'];
									break;
								} else {
									$vendorStateName = $purchaseOrderArray['state_code'];
								}
							}
						}
					}
				}
			}

			$detailsArray['vendor_details']['country'] = $vendorCountryName;
			$detailsArray['vendor_details']['state'] = $vendorStateName;
			$detailsArray['vendor_details']['zip_code'] = $purchaseOrderArray['zip_code'];
			$detailsArray['vendor_details']['billing_address'] = $purchaseOrderArray['billing_address'];

			/** get ship to address details */

			$countryName = '';
			$stateName = '';
			if ($purchaseOrderArray['ship_to_country_code']) {
				$countryEndPoint = 'country';
				$getcountryResponse = call_api($countryEndPoint, 'GET', []);
				if (!empty($getcountryResponse['data'])) {
					foreach ($getcountryResponse['data'] as $countryKey => $countryValue) {
						if ($countryValue['countries_code'] == $purchaseOrderArray['ship_to_country_code']) {
							$countryName = $countryValue['countries_name'];
							break;
						} else {
							$countryName = $purchaseOrderArray['country_code'];
						}
					}
					if ($purchaseOrderArray['ship_to_state_code']) {
						$stateEndPoint = 'state/' . $purchaseOrderArray['ship_to_country_code'];
						$getStateResponse = call_api($stateEndPoint, 'GET', []);

						if (!empty($getStateResponse['data'])) {
							foreach ($getStateResponse['data'] as $stateKey => $stateValue) {
								if ($stateValue['state_code'] == $purchaseOrderArray['ship_to_state_code']) {
									$stateName = $stateValue['state_name'];
									break;
								} else {
									$stateName = $purchaseOrderArray['ship_to_state_code'];
								}
							}
						}
					}
				}
			}
			$detailsArray['ship_to_address']['ship_address_id'] = $purchaseOrderArray['ship_address_id'];
			$detailsArray['ship_to_address']['name'] = $purchaseOrderArray['ship_to_name'];
			$detailsArray['ship_to_address']['email'] = $purchaseOrderArray['ship_to_email'];
			$detailsArray['ship_to_address']['company'] = $purchaseOrderArray['ship_to_company'];
			$detailsArray['ship_to_address']['phone'] = $purchaseOrderArray['ship_to_phone'];
			$detailsArray['ship_to_address']['country'] = $countryName;
			$detailsArray['ship_to_address']['state'] = $stateName;
			$detailsArray['ship_to_address']['zip_code'] = $stateName;
			$detailsArray['ship_to_address']['ship_address'] = $purchaseOrderArray['ship_address'];
			if ($poId) {
				$purchaseOrderDetailsInit = new PurchaseOrderDetails();
				$getPurchaseOrderDetails = $purchaseOrderDetailsInit->where(['purchase_order_id' => $poId]);
				$purchaseOrderDetails = $getPurchaseOrderDetails->get()->toArray();
				foreach ($purchaseOrderDetails as $key => $value) {
					$detailsArray['line_items'][$key]['purchase_order_id'] = $value['purchase_order_id'];
					$detailsArray['line_items'][$key]['order_id'] = $value['order_id'];
					$detailsArray['line_items'][$key]['order_item_id'] = $value['order_item_id'];
					$detailsArray['line_items'][$key]['status_id'] = $value['status_id'];
					$items = $this->purchaseOrderItemDetails($value['order_id'], $value['order_item_id'], "pdf", $storeId);
					$detailsArray['line_items'][$key]['product_id'] = $items[0]['product_id'];
					$detailsArray['line_items'][$key]['variant_id'] = ($items[0]['variant_id'] != 0 ? $items[0]['variant_id'] : $items[0]['product_id']);
					$detailsArray['line_items'][$key]['name'] = $items[0]['name'];
					$detailsArray['line_items'][$key]['sku'] = $items[0]['sku'] ? $items[0]['sku'] : 'N/A';
					$detailsArray['line_items'][$key]['quantity'] = $items[0]['quantity'];
					$detailsArray['line_items'][$key]['image'] = $items[0]['images'][0]['thumbnail'];
				}
				/** unique product id & variant id */
				$uniqueId = [];
				$responseArray = [];
				$quantity = 0;
				$quantityArray = [];
				$result = [];
				$i = 0;
				$uniqueIds = [];
				if (!empty($detailsArray['line_items'])) {
					foreach ($detailsArray['line_items'] as $key => $value) {
						$combinationId = $value['product_id'] . '_' . $value['variant_id'];
						array_push($uniqueId, $combinationId);
						if (in_array($combinationId, $uniqueId)) {
							$quantity = $value['quantity'];
							$responseArray[$combinationId][] = $quantity;
						}
					}
					foreach ($detailsArray['line_items'] as $itemKey => $itemValue) {
						$combinationIds = $itemValue['product_id'] . '_' . $itemValue['variant_id'];
						if (!in_array($combinationIds, $uniqueIds)) {
							array_push($uniqueIds, $combinationIds);
							$itemValue['quantity'] = array_sum($responseArray[$combinationIds]);
							$result[$i] = $itemValue;
							$i++;
						}

					}
				}
			}
			$detailsArray['line_items'] = $result;
		}
		return $detailsArray;
	}
	/**
	 * GET: Send to vendor
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   10 October 2020
	 * @return json response wheather data is save or not
	 */
	public function sendToVendor($request, $response, $args, $returnType = 0) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Email send ', 'error'),
		];
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$emailSendArray = array();
		$description = 'PO sent : Purchase order sent to vendor by admin.';
		if (isset($args) && !empty($args)) {
			$poIds = $args['id'];
			$poIdsToArray = json_clean_decode($poIds, true);
			$purchaseOrderInit = new PurchaseOrder();
			$i = 0;
			foreach ($poIdsToArray as $poId) {
				$getPurchaseOrder = $purchaseOrderInit
					->join('vendor', 'purchase_order.vendor_id', '=', 'vendor.xe_id')
					->select(
						'purchase_order.xe_id', 'purchase_order.po_id', 'purchase_order.status_id', 'purchase_order.store_id', 'purchase_order.vendor_id','purchase_order.expected_delivery_date', 'purchase_order.created_at', 'vendor.email AS vendor_email', 'vendor.contact_name','vendor.company_name', DB::raw("(SELECT order_id FROM purchase_order_items WHERE purchase_order_id = purchase_order.xe_id LIMIT 1) as order_id ")
					)
					->where(['purchase_order.xe_id' => $poId]);
				if ($getPurchaseOrder->count() > 0) {
					$purchaseOrderResponse = $getPurchaseOrder->get()->toArray();
					$emailSendArray[$i]['vendor_email'] = $purchaseOrderResponse[0]['vendor_email'];
					$emailSendArray[$i]['contact_name'] = $purchaseOrderResponse[0]['contact_name'];
					$emailSendArray[$i]['vendor_name'] = $purchaseOrderResponse[0]['company_name'];
					$emailSendArray[$i]['order_id'] = $purchaseOrderResponse[0]['order_id'];
					$emailSendArray[$i]['po_id'] = $purchaseOrderResponse[0]['po_id'];
					$emailSendArray[$i]['expected_delivery_date'] = $purchaseOrderResponse[0]['expected_delivery_date'];
					$emailSendArray[$i]['created_at'] = $purchaseOrderResponse[0]['created_at'];
					$pdfResponse = $this->createPurchaseOrderPdf($purchaseOrderResponse[0]['xe_id'], $getToken = '', $storeId);
					if ($pdfResponse['status'] == 1) {
						$emailSendArray[$i]['file_path'] = $pdfResponse['file_path'];
					} else {
						$emailSendArray[$i]['file_path'] = $pdfResponse['file_path'];
					}

					/** insert into po_log table */
					$logData = [
						'po_id' => $purchaseOrderResponse[0]['xe_id'],
						'description' => $description,
						'user_type' => 'admin',
						'user_id' => 1,
						'created_date' => date_time(
							'today', [], 'string'
						)
					];
					$this->addingPurchaseOrderLog($logData);
					$i++;
				}
			}
			if (!empty($emailSendArray)) {
				$mailResponseArray = $this->emailSendToVendor($emailSendArray, $storeDetails);
				if (!empty($mailResponseArray)) {
					$jsonResponse = [
						'status' => 1,
						'success_emails' => $mailResponseArray['success_emails'] ? $mailResponseArray['success_emails'] : [],
						'unsuccess_emails' => $mailResponseArray['unsuccess_emails'] ? $mailResponseArray['unsuccess_emails'] : [],
					];

				}
			}
		}
		if ($returnType == 1) {
			return $jsonResponse;
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Email send
	 *
	 * @param $emailSendArray
	 *
	 * @author soumyas@riaxe.com
	 * @date   10 October 2020
	 * @return
	 */
	public function emailSendToVendor($emailSendArray, $storeDetails) {
		//Get smtp email setting data for sending email
		$emailData = [];
		$smtpData = [];
		$settingInit = new Setting();
		$getSettings = $settingInit->where('store_id', '=', $storeDetails['store_id']);
		if ($getSettings->count() > 0) {
			$smtpEmailSettingData = $getSettings->get()->toArray();
			foreach ($smtpEmailSettingData as $key => $value) {
				if ($value['setting_key'] == 'email_address_details') {
					$emailData = json_clean_decode($value['setting_value'], true);
				}
				if ($value['setting_key'] == 'smtp_details') {
					$smtpData = json_clean_decode($value['setting_value'], true);
				}
			}
		}
		if (!empty($emailData) && !empty($smtpData)) {
			$file_url = '';
			$fromEmail = $emailData['from_email'];
			$replyToEmail = $emailData['to_email'];
			$mailResponseArray = [];
			$unsuccessEmails = [];
			$successEmails = [];
			foreach ($emailSendArray as $key => $value) {
				$templateData = $this->bindEmailTemplate('send_po', $value, $storeDetails);
				$templateData = $templateData[0];
				$mailContaint = ['from' => ['email' => $fromEmail, 'name' => $fromEmail],
					'recipients' => [
						'to' => [
							'email' => $value['vendor_email'],
							'name' => $value['contact_name'],
						],
						'reply_to' => [
							'email' => $replyToEmail,
							'name' => $replyToEmail,
						],
					],
					'attachments' => array($value['file_path'] ? $value['file_path'] : ''),
					'subject' => $templateData['subject'],
					'body' => $templateData['message'],
					'smptData' => $smtpData,
				];
				if ($smtpData['smtp_host'] != '' && $smtpData['smtp_user'] != '' && $smtpData['smtp_pass'] != '') {
					$mailResponse = email($mailContaint);
					if (!empty($mailResponse['status']) && $mailResponse['status'] == 1) {
						$mailResponseArray['success_emails'][$key] = $value['vendor_email'];

					} else {
						$mailResponseArray['unsuccess_emails'][$key] = $value['vendor_email'];
					}
				} else {
					$mailResponseArray[] = [];
				}
			}
		}

		return $mailResponseArray;
	}

	public function getOrderIdByPurchaseOrderId($poId) {
		$orderIds = [];
		$purchaseOrderDetailsInit = new PurchaseOrderDetails();
		$getPurchaseOrderDetails = $purchaseOrderDetailsInit->where(['purchase_order_id' => $poId]);
		if ($getPurchaseOrderDetails->count() > 0) {
			$purchaseOrderDetails = $getPurchaseOrderDetails->get()->toArray();
			foreach ($purchaseOrderDetails as $key => $value) {
				$orderIds[] = $value['order_id'];
			}
		}
		return $orderIds;
	}
	/**
	 * GET: Purchase order lineItem status List
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   10 October 2020
	 * @return json
	 */
	public function getPurchaseOrderLineItemStatus($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Data fetch ', 'error'),
		];
		$lineItemInit = new PurchaseOrderLineItemStatus();
		$getLineItemInit = $lineItemInit
			->select('xe_id as id', 'store_id', 'status_name', 'color_code', 'is_default', 'sort_order', 'status')->where(['status' => '1']);
		if ($getLineItemInit->count() > 0) {
			$getLineItemStatus = $getLineItemInit->get()->toArray();
			$jsonResponse = [
				'status' => 1,
				'data' => $getLineItemStatus,
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'No data found',
			];

		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Purchase order lineItem status Details
	 *
	 * @param $status_id
	 *
	 * @author soumyas@riaxe.com
	 * @date   20 October 2020
	 * @return Array
	 */
	public function getPurchaseOrderLineItemStatusDetails($status_id) {
		$statusArray = [];
		$lineItemInit = new PurchaseOrderLineItemStatus();
		$getLineItemInit = $lineItemInit->select('xe_id as id', 'store_id', 'color_code')->where(['xe_id' => $status_id]);
		if ($getLineItemInit->count() > 0) {
			$statusArray = $getLineItemInit->get()->toArray();
		}
		return $statusArray;
	}
	/**
	 * Update: Order po status
	 *
	 * @param $orderId
	 *
	 * @author soumyas@riaxe.com
	 * @date   20 October 2020
	 * @return boolean
	 */
	public function updateOrderPoStatus($orderId, $statusId, $statusType = null, $poLineItemStatusId) {
		$returnData = [];
		$status = 0;
		$isCreateProductionJob = false;
		$itemStatusArray = array();
		/** po status Received */
		if ($statusType == 'received' || $poLineItemStatusId == 3) {
			$status_id = 3;
		} else {
			$status_id = 1;
		}

		$receivedStatus = false;
		$purchaseOrderDetailsInit = new PurchaseOrderDetails();
		$getPurchaseOrderDetails = $purchaseOrderDetailsInit->where(['order_id' => $orderId]);
		$purchaseOrderDetails = $getPurchaseOrderDetails->get()->toArray();
		if (!empty($purchaseOrderDetails)) {
			foreach ($purchaseOrderDetails as $key => $value) {
				$itemStatusArray[] = $value['status_id'];
			}
			if (array_unique($itemStatusArray) === array($status_id)) {
				$receivedStatus = true;
			}
			if ($receivedStatus == 1) {
				$ordersInit = new Orders();
				$orderCount = $ordersInit->whereIn('order_id', [$orderId])->count();
				if ($orderCount > 0) {
					$updateData = [
						'po_status' => $status_id,
					];
					$ordersInit->where('order_id', '=', $orderId)->update($updateData);
					$status = 1;
					//Create Production Job
					//Get global order setting
					$orderSetting = call_api(
						'settings', 'GET', []
					);
					$orderSetting = $orderSetting['order_setting']['artwork_approval'];
					$isArtworkApproval = $orderSetting['order_artwork_status'];
					//Get production setting
					$productionSettingData = call_api(
						'production/settings?module_id=4', 'GET', []
					);
					$productionSettingData = $productionSettingData['data'];
					if ($productionSettingData['is_automatic_job_creation'] == 1 && $productionSettingData['purchase_order_mandetory'] == 1) {
						$checkPoStatus = $ordersInit->where([
							'order_id' => $orderId,
							'po_status' => 3,
						]);
						if ($checkPoStatus->count() > 0) {
							$checkPoStatusData = $checkPoStatus->get();
							$checkPoStatusData = json_clean_decode($checkPoStatusData, true);
							$checkPoStatusData = $checkPoStatusData[0];

						}
						if ($checkPoStatus->count() > 0 && $isArtworkApproval == 1 && $checkPoStatusData['artwork_status'] == 'approved') {
							$isCreateProductionJob = true;
						} else if ($checkPoStatus->count() > 0 && $isArtworkApproval != 1) {
							$isCreateProductionJob = true;
						}

					}

				} else {
					$saveData = [
						'order_id' => $orderId,
						'artwork_status' => 'pending',
						'po_status' => 1,
						'order_status' => '',
						'production_status' => '0',
						'production_percentage' => '0.0',
					];
					$vendorSaveInit = new Orders($saveData);
					$vendorSaveInit->save();
					$status = 1;
					$isCreateProductionJob = false;
				}
			}
		}
		$returnData = [
			'status' => $status,
			'is_create_production_job' => $isCreateProductionJob,
			'order_id' => $orderId,
		];
		return $returnData;
	}
	/**
	 * POST: Purchase order lineItem status List
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   21 October 2020
	 * @return json
	 */
	public function saveInternalNote($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Internal note save ', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$po_id = $allPostPutVars['po_id'] ? $allPostPutVars['po_id'] : '';
		$user_type = $allPostPutVars['user_type'] ? $allPostPutVars['user_type'] : 'admin';
		$user_id = $allPostPutVars['user_id'] ? $allPostPutVars['user_id'] : 1;
		$note = $allPostPutVars['note'] ? $allPostPutVars['note'] : '';
		$created_date = date_time('today', [], 'string');
		$saveInternalNoteData = [
			'po_id' => $po_id,
			'user_type' => $user_type,
			'user_id' => $user_id,
			'note' => 'Note Added by @admin : ' . $note . '.',
			'seen_flag' => '0',
			'created_date' => $created_date,
		];
		$purchaseOrderDirPath = path('abs', 'purchase_order') . '/internal_note/';
		if (!is_dir($purchaseOrderDirPath)) {
			mkdir($purchaseOrderDirPath, 0755, true);
		}

		$allFileNames = do_upload(
			'upload', $purchaseOrderDirPath, [200], 'array'
		);
		$purchaseOrderInternalNoteInit = new PurchaseOrderInternalNote($saveInternalNoteData);
		$status = $purchaseOrderInternalNoteInit->save();
		if ($status) {
			$lastInsertId = $purchaseOrderInternalNoteInit->xe_id;
			if (!empty($allFileNames)) {
				foreach ($allFileNames as $fileName) {
					$saveInternalNoteFile = [
						'note_id' => $lastInsertId,
						'file' => $fileName,
					];
					$purchaseOrderInternalNoteFileInit = new PurchaseOrderInternalNoteFile($saveInternalNoteFile);
					$fileStatus = $purchaseOrderInternalNoteFileInit->save();
				}

			}
			$jsonResponse = [
				'status' => 1,
				'message' => 'Internal note saved successfully',
			];

		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Purchase order lineItem status List
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   21 October 2020
	 * @return json
	 */
	public function getPurchaseOrderLog($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Internal note save ', 'error'),
		];
		if (isset($args) && !empty($args)) {
			$purchaseOrderLogArray = [];
			$purchaseOrderInternalNoteArray = [];
			$poId = $args['id'];
			/** fetch data from po_logo table */
			$purchaseOrderLogInit = new PurchaseOrderLog();
			$getPurchaseOrderLog = $purchaseOrderLogInit->where(['po_id' => $poId]);
			if ($getPurchaseOrderLog->count() > 0) {
				$getPurchaseOrderLogData = $getPurchaseOrderLog->get()->toArray();
				foreach ($getPurchaseOrderLogData as $key => $value) {
					$getPurchaseOrderLogData[$key]['log_type'] = 'po_log';
					$description = $value['description'];
					$poDescription = explode(" : ", $description);
					$getPurchaseOrderLogData[$key]['title'] = $poDescription[0] ? $poDescription[0] : '';
					$getPurchaseOrderLogData[$key]['description'] = $poDescription[1] ? $poDescription[1] : '';
					$getPurchaseOrderLogData[$key]['created_at'] = $value['created_date'];
				}
				$purchaseOrderLogArray = $getPurchaseOrderLogData;
			}
			$purchaseOrderInternalNoteInit = new PurchaseOrderInternalNote();
			$getpurchaseOrderInternalNote = $purchaseOrderInternalNoteInit->where(['po_id' => $poId]);
			if ($getpurchaseOrderInternalNote->count() > 0) {
				$getpurchaseOrderInternalNoteData = $getpurchaseOrderInternalNote->get()->toArray();
				foreach ($getpurchaseOrderInternalNoteData as $key => $value) {
					$purchaseOrderInternalNoteArray[$key]['xe_id'] = $value['xe_id'];
					$purchaseOrderInternalNoteArray[$key]['po_id'] = $value['po_id'];
					$purchaseOrderInternalNoteArray[$key]['user_type'] = $value['user_type'];
					$purchaseOrderInternalNoteArray[$key]['user_id'] = $value['user_id'];
					$purchaseOrderInternalNoteArray[$key]['log_type'] = 'internal_note';
					$note = $value['note'];
					$poNote = explode(" : ", $note);
					$purchaseOrderInternalNoteArray[$key]['description'] = $poNote[1] ? $poNote[1] : '';
					$purchaseOrderInternalNoteArray[$key]['title'] = $poNote[0] ? $poNote[0] : '';
					$purchaseOrderInternalNoteArray[$key]['created_date'] = $value['created_date'];
					$purchaseOrderInternalNoteArray[$key]['created_at'] = $value['created_date'];
					$fileData = $this->getInternalNoteFileByNoteId($value['xe_id']);
					$purchaseOrderInternalNoteArray[$key]['files'] = $fileData;
				}
			}
			$responseArray = array_merge($purchaseOrderLogArray, $purchaseOrderInternalNoteArray);
			// Sort the array by Created Date and time
			usort($responseArray, 'date_compare');
			if (!empty($responseArray)) {
				$jsonResponse = [
					'status' => 1,
					'data' => $responseArray,
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'data' => [],
				];
			}

		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: InternalNoteFile
	 *
	 * @param $noteId
	 *
	 * @author soumyas@riaxe.com
	 * @date   21 October 2020
	 * @return Array
	 */
	public function getInternalNoteFileByNoteId($noteId) {
		$fileArray = [];
		$imageFileUrl = ASSETS_PATH_R . 'purchase_order/internal_note/';
		$purchaseOrderInternalNoteFileInit = new PurchaseOrderInternalNoteFile();
		$getpurchaseOrderInternalFileNote = $purchaseOrderInternalNoteFileInit->where(['note_id' => $noteId]);
		if ($getpurchaseOrderInternalFileNote->count() > 0) {
			$getpurchaseOrderInternalNoteFileData = $getpurchaseOrderInternalFileNote->get()->toArray();
			foreach ($getpurchaseOrderInternalNoteFileData as $key => $value) {
				$fileExtension = pathinfo($value['file'], PATHINFO_EXTENSION);
				$fileName = strtolower($fileExtension);
				$fileArray[$key]['xe_id'] = $value['xe_id'];
				$fileArray[$key]['note_id'] = $value['note_id'];
				$fileArray[$key]['file'] = $value['file'];
				if ($fileName == 'jpg' || $fileName == 'jpeg' || $fileName == 'png') {
					$fileArray[$key]['thumbnail'] = $imageFileUrl . 'thumb_' . $value['file'];
					$fileArray[$key]['file_name'] = $imageFileUrl . $value['file'];
				} else if ($fileName == "pdf") {
					$fileArray[$key]['thumbnail'] = ASSETS_PATH_R . 'common/pdf-logo.png';
					$fileArray[$key]['file_name'] = $imageFileUrl . $value['file'];
				} else if ($fileName == "zip") {
					$fileArray[$key]['thumbnail'] = ASSETS_PATH_R . 'common/zip-logo.png';
					$fileArray[$key]['file_name'] = $imageFileUrl . $value['file'];
				} else if ($fileName == 'svg') {
					$fileArray[$key]['thumbnail'] = $imageFileUrl . $value['file'];
					$fileArray[$key]['file_name'] = $imageFileUrl . $value['file'];
				} else {
					$fileArray[$key]['thumbnail'] = ASSETS_PATH_R . 'common/txt-logo.png';
					$fileArray[$key]['file_name'] = $imageFileUrl . $value['file'];
				}
			}

		}
		return $fileArray;
	}
	/**
	 * Adding data to Purchase order log
	 *
	 * @param $logData  Log data array
	 *
	 * @author soumyas@riaxe.com
	 * @date   21 October 2020
	 * @return boolean
	 */
	public function addingPurchaseOrderLog($logData) {
		if (!empty($logData)) {
			$purchaseOrderLog = new PurchaseOrderLog($logData);
			if ($purchaseOrderLog->save()) {
				return true;
			}
		}
		return false;
	}
	/**
	 * GET: Purchase order line item details
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   21 October 2020
	 * @return json
	 */
	public function getPurchaseOrderLineItemDetails($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('item list ', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$storeId = $getStoreDetails['store_id'];
		$lineItemArray = [];
		$type = 'view';
		$lineItems = $this->getPurchaseOrderLineItems($args['id'], $type, $storeId);
		if (!empty($lineItems)) {
			$uniqueId = [];
			$responseArray = [];
			$quantity = 0;
			$quantityArray = [];
			foreach ($lineItems as $key => $value) {
				$combinationId = $value['product_id'] . '_' . $value['variant_id'];
				array_push($uniqueId, $combinationId);
				if (in_array($combinationId, $uniqueId)) {
					$order_id = $value['order_id'];
					$order_item_id = $value['order_item_id'];
					$quantity = $value['quantity'];
					$responseArray[$combinationId]['quantity'][] = $quantity;
					$responseArray[$combinationId]['order_id'][] = $order_id;
					$responseArray[$combinationId]['order_item_id'][] = $order_item_id;
				}
			}
			$result = [];
			$i = 0;
			$uniqueIds = [];
			foreach ($lineItems as $uniqueKey => $uniqueValue) {
				$combinationIds = $uniqueValue['product_id'] . '_' . $uniqueValue['variant_id'];
				if (!in_array($combinationIds, $uniqueIds)) {
					array_push($uniqueIds, $combinationIds);
					$uniqueValue['quantity'] = array_sum($responseArray[$combinationIds]['quantity']);
					$uniqueValue['order_id'] = $responseArray[$combinationIds]['order_id'];
					$uniqueValue['order_item_id'] = $responseArray[$combinationIds]['order_item_id'];
					$result[$i] = $uniqueValue;
					$i++;
				}

			}
		}
		if (!empty($result)) {
			$jsonResponse = [
				'status' => 1,
				'line_items' => $result,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
     * Get Email Template data
     *
     * @param $emailType  Email Template Type
     * @param $poDeatils  PO Details Array
     * @param $getStoreDetails Store Id
     *
     * @author debashrib@riaxe.com
     * @date   22 April 2021
     * @return array response
     */
    public function bindEmailTemplate($emailType, $poDeatils, $getStoreDetails)
    {
        $resData = [];
        if ($emailType != '') {
            //Bind email template
            $templateData = $this->getEmailTemplate(3, $getStoreDetails, $emailType);
            $string = $templateData[0]['message'];
            $ldelim = "{";
            $rdelim = "}";
            $pattern = "/" . preg_quote($ldelim) . "(.*?)" . preg_quote($rdelim) . "/";
            preg_match_all($pattern, $string, $matches);
            $abbriviationData = $matches[1];
            foreach ($abbriviationData as $abbrData) {
                $abbrName = '{'.$abbrData.'}';
                if (strpos($templateData[0]['message'], $abbrName) !== false) {
                    $abbrValue = $this->getAbbriviationValue($abbrName, $poDeatils);
                    $templateData[0]['message'] = str_replace($abbrName, $abbrValue, $templateData[0]['message']);
                }
            }
            $resData = $templateData;
        }
        return $resData;
    }

    /**
     * Get Email Template Abbriviation Value
     *
     * @param $abbrName  Abbriviation Name
     * @param $poDeatils  PO Details
     *
     * @author debashrib@riaxe.com
     * @date   22 April 2021
     * @return array response
     */

    public function getAbbriviationValue($abbrName, $poDeatils)
    {
        $abbrValue = '';
        //switch case
        switch ($abbrName) {
            case "{po_id}":
                $abbrValue = $poDeatils['po_id'];
                break;
            case "{contact_name}":
                $abbrValue = $poDeatils['contact_name'];
                break;
            case "{vendor_name}":
                $abbrValue = $poDeatils['vendor_name'];
                break;
            case "{vendor_email}":
                $abbrValue = $poDeatils['vendor_email'];
                break;
            case "{created_date}":
                $abbrValue = date('m-d-Y', strtotime($poDeatils['created_at']));
                break;
            case "{expected_date_of_delivery}":
                $abbrValue = date('m-d-Y', strtotime($poDeatils['expected_delivery_date']));
                break;
            default:
                $abbrValue = $abbrName;
        }
        return $abbrValue;
    }
}