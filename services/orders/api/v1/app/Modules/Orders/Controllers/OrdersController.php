<?php
/**
 * Manage Order Logs from Store end and Admin end
 *
 * PHP version 5.6
 *
 * @category  Store_Order
 * @package   Order
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Orders\Controllers;

use App\Components\Models\ProductionAbbriviations;
use App\Modules\Orders\Controllers\OrderDownloadController;
use App\Modules\Orders\Models\OrderItemToken;
use App\Modules\Orders\Models\OrderLog;
use App\Modules\Orders\Models\OrderLogFiles;
use App\Modules\Orders\Models\Orders;
use App\Modules\PurchaseOrder\Models\PurchaseOrderDetails;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLineItemStatus;
use OrderStoreSpace\Controllers\StoreOrdersController;
use App\Modules\Settings\Models\Setting;

/**
 * Order Log Controller
 *
 * @category Store_Order
 * @package  Order
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class OrdersController extends StoreOrdersController {
	/**
	 * Get: Get Total Orders
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Total Orders in Json format
	 */
	public function getOrderList($request, $response, $args, $returnType = 0) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Order Log', 'not_found'),
			'data' => [],
		];
		$storeDetails = get_store_details($request);
		$jsonContent = "";
		$designId = [];
		$notes = [];
		$isPurchaseorder = $request->getQueryParam('isPurchaseorder') ? $request->getQueryParam('isPurchaseorder') : false;
		$storeResponse = $this->getOrders($request, $response, $args);
		$value = ['order_artwork_status'];
		$isArtworkEnabled = $this->getSettingStatus('artwork_approval', 6, $value);
		$ordersInit = new Orders();
		if (!empty($args['id']) && !empty($storeResponse)) {
			//Get artwork status
			$ordersArtworkStatus = $ordersInit->where('order_id', $args['id']);
			/****************
				             *
				             * Need to delete after devlopment
			*/
			if ($ordersArtworkStatus->count() == 0 && $isArtworkEnabled == 1) {
				$saveOrderArtworStatus = new Orders(
					[
						'order_id' => $args['id'],
						'artwork_status' => 'pending',
					]
				);
				$saveOrderArtworStatus->save();
			}
			/*********************************/
			$artworkStatusData = $ordersArtworkStatus->first();
			if ($isArtworkEnabled == 1) {
				$token = 'order_id=' . $args['id'].'&store_id='.$storeDetails['store_id'];
				$token = base64_encode($token);
				// $url = QUOTATION_REVIEW . '/quotation/art-work?token=' . $token . '&artwork_type=order';
				$url = API_URL . 'quotation/art-work?token=' . $token . '&artwork_type=order';
				$storeResponse['order_details'] += ['public_url' => $url];
				$storeResponse['order_details'] += ['artwork_status' => $artworkStatusData->artwork_status];
			}
			$storeResponse['order_details'] += ['po_status' => (!empty($artworkStatusData->po_status)) ? $artworkStatusData->po_status : 0];
			$storeResponse['order_details'] += ['production_status' => (!empty($artworkStatusData->production_status)) ? $artworkStatusData->production_status : 0];
			$po_status_name = '';
			$po_status_color = '';
			if ($artworkStatusData->po_status == 0) {
				$storeResponse['order_details'] += ['po_status_name' => $po_status_name];
				$storeResponse['order_details'] += ['po_status_color' => $po_status_color];
			} else {
				$poStatusDetails = $this->getOrderPoStatusDetails($args['id'], $artworkStatusData->po_status);
				//$poStatusDetails = [];
				if (!empty($poStatusDetails)) {
					$storeResponse['order_details'] += ['po_status_name' => $poStatusDetails['status_name']];
					$storeResponse['order_details'] += ['po_status_color' => $poStatusDetails['color_code']];
				} else {
					$storeResponse['order_details'] += ['po_status_name' => $po_status_name];
					$storeResponse['order_details'] += ['po_status_color' => $po_status_color];
				}
			}
			foreach ($storeResponse['order_details']['orders'] as $orderDetailsKey => $orderDetails) {
				$designImages = [];
				$productDecoData = [];
				if ($orderDetails['custom_design_id'] > 0 && $orderDetails['custom_design_id'] != '-1') {
					$orderFolderDir = path('abs', 'order') . $args['id'] . '/order.json';
					$jsonFile = read_file($orderFolderDir);
					$jsonFileContent = json_clean_decode($jsonFile, true);
					$quoteSource =  isset($jsonFileContent['order_details']['quote_source']) ? $jsonFileContent['order_details']['quote_source'] : '';

					$customDesignId = $orderDetails['custom_design_id'];
					$deisgnStatePath = path('abs', 'design_state') . 'carts';
					$predecoPath = path('abs', 'design_state') . 'predecorators';
					$quotationPath = path('abs', 'design_state') . 'artworks';
					$orderJsonPath = $deisgnStatePath . '/' . $customDesignId . ".json";
					$orderPredecoPath = $predecoPath . '/' . $customDesignId . ".json";
					$orderQuotationPath = $quotationPath . '/' . $customDesignId . ".json";
					if (file_exists($orderJsonPath)) {
						$orderJson = read_file($orderJsonPath);
						$jsonContent = json_clean_decode($orderJson, true);
					} elseif (file_exists($orderPredecoPath)) {
						$orderJson = read_file($orderPredecoPath);
						$jsonContent = json_clean_decode($orderJson, true);
					} elseif (file_exists($orderQuotationPath)) {
						$orderJson = read_file($orderQuotationPath);
						$jsonContent = json_clean_decode($orderJson, true);
					}

					// code for svg configurator data fetcehd from desoginstate.json//
                    if (!empty($jsonContent['configurator_svg_info'])) {
                        $svgConfiguratorArr = $jsonContent['configurator_svg_info'];
                    }

					if ($isPurchaseorder == false) {
						if (!empty($jsonContent['design_product_data'])) {
							$variantIdArr = [];
							foreach ($jsonContent['design_product_data'] as $designImage) {
								// Added for same product image for artwork
								if ((file_exists($orderQuotationPath) && ($quoteSource == '' && $quoteSource == 'admin')) || file_exists($orderPredecoPath)) {
									$designImages = [];
									if (!empty($designImage['design_urls'])) {
										foreach ($designImage['design_urls'] as $image) {
											$designImages[] = [
												'src' => $image,
												'thumbnail' => $image,
											];
										}
									}
								} else {
									if ($orderDetails['variant_id'] == 0 || in_array($orderDetails['variant_id'], $designImage['variant_id'])) {
										if (!in_array($orderDetails['variant_id'], $variantIdArr)) {
											array_push($variantIdArr, $orderDetails['variant_id']);
											if (!empty($designImage['design_urls'])) {
												foreach ($designImage['design_urls'] as $image) {
													$designImages[] = [
														'src' => $image,
														'thumbnail' => $image,
													];
												}
											}
										}
									}
								}
								$storeResponse['order_details']['orders'][$orderDetailsKey]['variableDecorationSize'] = isset($designImage['variable_decoration_size']) ? $designImage['variable_decoration_size'] : '';
								$storeResponse['order_details']['orders'][$orderDetailsKey]['variableDecorationUnit'] = isset($designImage['variable_decoration_unit']) ? $designImage['variable_decoration_unit'] : '';
								$storeResponse['order_details']['orders'][$orderDetailsKey]['configurator_svg_info'] = $svgConfiguratorArr;
							}
						}
					}

					if (!empty($jsonContent['sides'])) {
						$i = 1;
						foreach ($jsonContent['sides'] as $sideDetailsKey => $sideDetails) {
							$configurator = [];
							if (isset($sideDetails['configurator']) && !empty($sideDetails['configurator'])) {
								$configurator = $sideDetails['configurator'];
							}
							$sideName = !empty($sideDetails['side_name']) ? $sideDetails['side_name'] : "";
							$isDesign = !empty($sideDetails['is_designed']) ? $sideDetails['is_designed'] : 0;
							$decorationData = [];
							if (!empty($sideDetails['print_area'])) {
								$j = 0;
								foreach ($sideDetails['print_area'] as $profile) {
									$orderDwonloadObj = new OrderDownloadController();
									$svgUrl = ASSETS_PATH_R . 'orders/' . $args['id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $args['id'] . '.svg';
									$svgPath = ASSETS_PATH_W . 'orders/' . $args['id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $args['id'] . '.svg';
									$pngPath = ASSETS_PATH_W . 'orders/' . $args['id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $args['id'] . '.png';
									$pngUrl = ASSETS_PATH_R . 'orders/' . $args['id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $args['id'] . '.png';
									if (!file_exists($pngPath)) {
										$orderDwonloadObj->svgConvertToPng($pngPath, $svgPath);
									}
									if ($profile['isDesigned'] > 0) {
										$decorationData[] = [
											'decoration_name' => isset($profile['name']) ? $profile['name'] : $profile['name'],
											'print_area_id' => $profile['print_area']['id'],
											'print_area_name' => $profile['print_area']['name'],
											'print_profile_id' => $profile['print_method_id'],
											'print_profile_name' => $profile['print_method_name'],
											'print_unit' => $sideDetails['print_unit'],
											'print_area_height' => $profile['print_area']['height'],
											'print_area_width' => $profile['print_area']['width'],
											'design_width' => isset($profile['design_width']) ? $profile['design_width'] : "",
											'design_height' => isset($profile['design_height']) ? $profile['design_height'] : "",
											//'svg_url' => $svgUrl,
											'png_url' => $pngUrl,
											'used_colors' => $profile['used_colors'] ? $profile['used_colors'] : [],
											'x_location' => isset($profile['design_x']) ? $profile['design_x'] : "",
											'y_location' => isset($profile['design_y']) ? $profile['design_y'] : "",
										];
									}
									$j++;
								}
							}
							$productDecoData[] = [
								'is_design' => $isDesign,
								'name' => $sideName,
								'decoration_data' => $decorationData,
                                'configurator' => $configurator,
                                'stickerInfo' => $sideDetails['stickerInfo'],
							];
							$i++;
						}
					}
				} else if ($orderDetails['custom_design_id'] == '-1') {
					$orderFolderDir = path('abs', 'order') . $args['id'] . '/order.json';
					$orderJson = read_file($orderFolderDir);
					$jsonContent = json_clean_decode($orderJson, true);
					//echo'<pre>';print_r($jsonContent);exit;
					$orderItemArr = $jsonContent['order_details']['order_items'];
					$itemId = $orderDetails['id'];
					$itemArr = array_filter($orderItemArr, function ($item) use ($itemId) {
						return ($item['item_id'] == $itemId);
					});
					$itemArr = $itemArr[array_keys($itemArr)[0]];
					$filesDataArr = $itemArr['file_data'];
					if (!empty($filesDataArr)) {
						foreach ($filesDataArr as $files) {
							$decorationData = [];
							foreach ($files['decoration_area'] as $decorationArea) {
								$designImages[] = [
									'src' => $decorationArea['upload_preview_url'],
									'thumbnail' => $decorationArea['upload_preview_url'],
								];
								$decorationData[] = [
									'decoration_name' => $decorationArea['decoration_area'],
									'print_area_id' => $decorationArea['print_area_id'],
									'print_area_name' => $decorationArea['print_area_name'],
									'print_profile_id' => $decorationArea['print_method_id'],
									'print_profile_name' => $decorationArea['print_methods'],
									'print_unit' => $decorationArea['measurement_unit'],
									'print_area_height' => $decorationArea['height'],
									'print_area_width' => $decorationArea['width'],
									'design_width' => $decorationArea['design_width'],
									'design_height' => $decorationArea['design_height'],
									//'svg_url' => $decorationArea['upload_design_url'],
									'png_url' => $decorationArea['upload_design_url'],
								];
							}
							$productDecoData[] = [
								'is_design' => 1,
								'name' => $files['side_name'],
								'decoration_data' => $decorationData,
							];
						}
					}

				}
				$storeResponse['order_details']['orders'][$orderDetailsKey] += [
					'decoration_settings_data' => $productDecoData,
				];
				if (count($designImages) > 0 && strtolower(STORE_NAME) != "shopify") {
					$storeResponse['order_details']['orders'][$orderDetailsKey]['images'] = $designImages;
				}
				if (count($designId) === 0 || !in_array($customDesignId, $designId)) {
					$notes[] = $jsonContent['notes'];
					$designId[] = $customDesignId;
				}
				if (strtolower(STORE_NAME) == "shopify") {
					$ordersStatus = $ordersInit->where('order_id', $storeResponse['order_details']['id'])->first();
					$storeResponse['order_details']['status'] = (!empty($ordersStatus->order_status)) ? $ordersStatus->order_status : 'received';
				}
				$poStatus = $this->getOrderPoStatus($args['id'], $orderDetails['id']);
				$storeResponse['order_details']['orders'][$orderDetailsKey]['po_status'] = $poStatus;
				$storeResponse['order_details']['orders'][$orderDetailsKey]['store_image'] = $orderDetails['images'];
			}
			$storeResponse['order_details']['notes'] = $notes;
			if (isset($args['is_return']) && $args['is_return']) {
				return $storeResponse;
			} else {
				$jsonResponse = [
					'status' => 1,
					'total_records' => $storeResponse['total_records'],
					'data' => $storeResponse['order_details'],
				];
			}
		} else {
			if (!empty($storeResponse)) {
				foreach ($storeResponse['order_details'] as $orderDetailsKey => $orderDetails) {
					if ($isArtworkEnabled == 1) {
						$ordersArtworkStatus = $ordersInit->where('order_id', $orderDetails['id'])->first();
						$storeResponse['order_details'][$orderDetailsKey] += ['artwork_status' => (!empty($ordersArtworkStatus->artwork_status)) ? $ordersArtworkStatus->artwork_status : 'pending'];
					}
					if (strtolower(STORE_NAME) == "shopify") {
						$ordersStatus = $ordersInit->where('order_id', $orderDetails['id'])->first();
						$storeResponse['order_details'][$orderDetailsKey]['status'] = (!empty($ordersStatus->order_status)) ? $ordersStatus->order_status : 'received';
					}
					$ordersPoStatus = $ordersInit
						->select('orders.po_status', 'orders.production_status', 'quotations.xe_id as quotation_id', 'quotations.quote_id as quotation_number')
						->join(
			            'quotations',
			            'orders.order_id',
			            '=',
			            'quotations.order_id')
						->where('orders.order_id', $orderDetails['id'])->first();
					$storeResponse['order_details'][$orderDetailsKey] += ['po_status' => (!empty($ordersPoStatus->po_status)) ? $ordersPoStatus->po_status : 0];
					$storeResponse['order_details'][$orderDetailsKey] += ['production_status' => (!empty($ordersPoStatus->production_status)) ? $ordersPoStatus->production_status : 0];
					$storeResponse['order_details'][$orderDetailsKey] += ['quotation_id' => (isset($ordersPoStatus->quotation_id) && $ordersPoStatus->quotation_id != '') ? $ordersPoStatus->quotation_id : 0];
					$storeResponse['order_details'][$orderDetailsKey] += ['quotation_number' => (isset($ordersPoStatus->quotation_number) && $ordersPoStatus->quotation_number != '') ? $ordersPoStatus->quotation_number : ''];
				}
			}
			if (!empty($storeResponse)) {
				$jsonResponse = [
					'status' => 1,
					'total_records' => $storeResponse['total_records'],
					'records' => count($storeResponse['order_details']),
					'data' => $storeResponse['order_details'],
				];
			}
		}
		if ($returnType == 1) {
			return $jsonResponse;
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * Post: Save Order Log data
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Order logs in Json format
	 */
	public function saveOrderLogs($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$jsonResponse = [
			'status' => 0,
			'message' => message('Order Log', 'error'),
		];
		$storeDetails = get_store_details($request);
		$orderLogsJson = $allPostPutVars['log_data'];
		$orderLogsArray = json_clean_decode($orderLogsJson, true);
		if (!empty($orderLogsArray['order_id'])) {
			$saveOrderLog = new OrderLog(
				[
					'order_id' => $orderLogsArray['order_id'],
					'agent_type' => $orderLogsArray['agent_type'],
					'agent_id' => $orderLogsArray['agent_id'],
					'store_id' => $orderLogsArray['store_id'],
					'message' => $orderLogsArray['message'],
					'log_type' => $orderLogsArray['log_type'],
					'artwork_status' => $orderLogsArray['artwork_status'],
					'status' => $orderLogsArray['status'],
				]
			);

			if ($saveOrderLog->save()) {
				$orderLogInsertId = $saveOrderLog->xe_id;
				if (!empty($orderLogsArray['files'])) {
					foreach ($orderLogsArray['files'] as $fileData) {
						// Start saving each sides
						$imageUploadIndex = $fileData['image_upload_data'];
						// If image resource was given then upload the image
						// into the specified folder
						$getFiles = do_upload(
							$imageUploadIndex, path('abs', 'order_log'), [150], 'string'
						);
						// Setup data for Saving/updating
						$orderLogFiles = [
							'order_log_id' => $orderLogInsertId,
						];

						// If File was choosen from frontend then only
						// save/update the image or skip the image saving
						if (!empty($getFiles)) {
							$orderLogFiles['file_name'] = $getFiles;
						}
						// Insert Order Log Files
						$saveOrderLogFile = new OrderLogFiles($orderLogFiles);
						$saveOrderLogFile->save();
					}
				}
				$jsonResponse = [
					'status' => 1,
					'order_log_id' => $orderLogInsertId,
					'message' => message('Order Log', 'saved'),
				];
				if ($orderLogsArray['log_type'] == 'artwork'
					&& $orderLogsArray['agent_type'] == 'admin'
				) {
					// Sending mail
					$token = 'order_id=' . $orderLogsArray['order_id']. '&store_id='. $storeDetails['store_id'];
					$token = base64_encode($token);
					// $url = QUOTATION_REVIEW . '/quotation/art-work?token=' . $token . '&artwork_type=order';
					$url = API_URL . 'quotation/art-work?token=' . $token . '&artwork_type=order';
					// Send mail
					$mailData = [
						'agent_email' => $orderLogsArray['agent_email'],
						'agent_name' => $orderLogsArray['agent_name'],
						'customer_email' => $orderLogsArray['customer_email'],
						'customer_name' => $orderLogsArray['customer_name'],
						'url' => $url,
					];
					//Send approval mail to customer
					if ($this->orderApproveMail($mailData)) {
						$jsonResponse = [
							'status' => 1,
							'order_log_id' => $orderLogInsertId,
							'url' => $url,
							'message' => message('Order Log', 'saved'),
						];
					}
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Get all Order Logs of a single order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Order Logs in Json format
	 */
	public function getOrderLogs($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$totalOrderLogs = [];
		$processInAppLog = [];
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('Order Log', 'not_found'),
		];
		$orderLogInit = new OrderLog();
		$initOrderLog = $orderLogInit->with('files');
		if (isset($args['id']) && $args['id'] > 0) {
			$initOrderLog->where('order_id', $args['id']);
		}
		//For customer view
		$viewType = $request->getQueryParam('type');
		if (isset($viewType) && $viewType == 'customer') {
			$initOrderLog->where('log_type', 'artwork');
		}
		// Check Artwork Setting is enabled or not
		$settingValue = ['order_artwork_status'];
		$isArtworkEnabled = $this->getSettingStatus('artwork_approval', 6, $settingValue);
		if ($isArtworkEnabled == 0) {
			$initOrderLog->whereNotIn('log_type', ['artwork']);
		}
		if ($initOrderLog->count() > 0) {
			$inAppOrderLogs = $initOrderLog->orderBy('xe_id', 'desc')
				->get()->toArray();
			foreach ($inAppOrderLogs as $inAppLogkey => $inAppLog) {
				$processInAppLog[$inAppLogkey] = [
					'id' => $inAppLog['xe_id'],
					'order_id' => $inAppLog['order_id'],
					'agent_type' => $inAppLog['agent_type'],
					'agent_id' => $inAppLog['agent_id'],
					'store_id' => $inAppLog['store_id'],
					'message' => $inAppLog['message'],
					'log_type' => $inAppLog['log_type'],
					'status' => $inAppLog['status'],
					'artwork_status' => $inAppLog['artwork_status'],
					'created_at' => $inAppLog['created_at'],
					'updated_at' => $inAppLog['updated_at'],
					'files' => $inAppLog['files'],
				];
			}
		}

		// Get Logs from Store
		if (isset($viewType) && $viewType == 'customer') {
			$totalOrderLogs = $processInAppLog;
		} else {
			$storeLogs = $this->getStoreLogs($request, $response, $args);
			foreach ($storeLogs as $logKey => $log) {
				if ($log['log_type'] == 'order_status') {
					$storeLogs[$logKey]['log_type'] = 'Order status';
				} else if ($log['log_type'] == 'payment_status') {
					$storeLogs[$logKey]['log_type'] = 'Payment status';
				}
			}

			if (is_array($storeLogs) && !empty($storeLogs) > 0) {
				$totalOrderLogs = array_merge($processInAppLog, $storeLogs);
			}
		}

		// Sort the array by Created Date and time
		usort($totalOrderLogs, 'date_compare');

		if (is_array($totalOrderLogs) && !empty($totalOrderLogs) > 0) {
			$jsonResponse = [
				'status' => 1,
				'data' => $totalOrderLogs,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: get order details for dashboard
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Args object
	 *
	 * @author debashrib@riaxe.com
	 * @date   27 Jan 2020
	 * @return Order data in Json format
	 */
	public function getOrdersGraph($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'message' => message('Order Graph', 'not_found'),
		];
		$refundArr = [];
		$totalRevenue = 0.00;
		$totalRefund = 0.00;
		$ordersDataArr = $this->getOrders($request, $response, $args);
		if (is_valid_array($ordersDataArr)) {
			$orderData = $ordersDataArr['order_details'];
			$totalAmountArr = array_column($orderData, 'total_amount');
			$totalRevenue = array_sum($totalAmountArr);

			$refundArr = array_filter(
				$orderData, function ($item) {
					if ($item['status'] == 'refunded') {
						return true;
					}
				}
			);
			if (is_valid_array($refundArr)) {
				$totalRefundAmountArr = array_column($refundArr, 'total_amount');
				$totalRefund = array_sum($totalRefundAmountArr);
			}
			$ordersData = [
				"total_revenue" => number_format((float) $totalRevenue, 2, '.', ''),
				"refunds" => number_format((float) $totalRefund, 2, '.', ''),
				"repeat_customer_rate" => 5.43,
				"order_data" => $orderData,
			];
			$jsonResponse = [
				'status' => 1,
				'data' => $ordersData,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Generate Order Files
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Args object
	 *
	 * @author malay@riaxe.com
	 * @date   05 March 2020
	 * @return array json
	 */
	public function generateOrderFiles($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$orderDetails = $this->orderItemDetails($request, $response, $args);
		$orderId = isset($orderDetails['order_details']['order_id']) ? $orderDetails['order_details']['order_id'] : 0;
		$orderNumber = isset($orderDetails['order_details']['order_incremental_id']) ? $orderDetails['order_details']['order_incremental_id'] : 0;
		$storeId = isset($orderDetails['order_details']['store_id']) ? $orderDetails['order_details']['store_id'] : 1;
		$customerId = (isset($orderDetails['order_details']['customer_id']) && $orderDetails['order_details']['customer_id'] != '' && $orderDetails['order_details']['customer_id'] != null) ? $orderDetails['order_details']['customer_id'] : 0;
		if (!empty($orderDetails['order_details']['order_items'])) {
			foreach ($orderDetails['order_details']['order_items'] as $itemKey => $items) {
				$this->updateMostusedAssets($items['ref_id']);
			}
		}
		if (!empty($orderDetails)) {
			//Initiate order download controller
			$orderDwonloadObj = new OrderDownloadController();
			$status = $orderDwonloadObj->createOrderAssetFile($args, $orderDetails);
			$isS3Enabled = $this->checkS3Settings($storeId);
			if ($isS3Enabled) {
				$thisOrderDIR = path('abs', 'order') . $orderId;
				$s3Upload = $this->uploadDIRToS3Recurse("order", $thisOrderDIR, $storeId);
			}
		}
		if ($orderId > 0 && $storeId > 0 && $customerId >= 0) {
			//add data to orders table
			$this->saveDataForOrder($orderId, $storeId, $customerId, $orderNumber);
		}
		return response(
			$response, ['data' => $orderDetails, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Send Order Approval Mail
	 *
	 * @param $mailData Array of all mail information
	 *
	 * @author debashrib@riaxe.com
	 * @date   18 March 2020
	 * @return boolean
	 */
	private function orderApproveMail($mailData) {
		$prev = "<!DOCTYPE html><html><title>Quote</title><style>a{color:#1b41c7}</style><body><table style='border-collapse: collapse; width: 100%; max-width: 835px; min-width: 320px;'><tbody><tr><td valign='top' style='padding:0 0px'><table cellspacing='0' cellpadding='0' border='0' align='center' style='border-collapse:collapse;border-radius:3px;color:#545454;font-family:Helvetica Neue,Arial,sans-serif;font-size:13px;line-height:20px;margin:0 auto;width:100%'><tbody><tr><td valign='top'>";

		$next = "</td></tr></tbody></table></td></tr><tr><td valign='top' height='20'/></tr></tbody></table></body></html>";

		$html = "<table cellspacing='0' cellpadding='0' border='0' style='border-collapse:collapse;border-color:#dddddd;border-radius:0 0 3px 3px;border-style:solid solid none; padding: 10px; display: block;color:#525252;'><tbody><tr><td bgcolor='white' style='background:white;border-radius:0 0 3px 3px;color:#525252;font-family:Helvetica Neue,Arial,sans-serif;font-size:15px;line-height:22px;overflow:hidden;padding:10px 10px 10px'><p align='left' style='line-height:1.5;margin:0 0 5px;text-align:left!important;color:#525252;'>Thank you for your recent order request.</strong></p><p align='left' style='line-height:1.5;margin:0 0 5px;text-align:left!important;color:#525252;'>We have evaluated your project details and have created a artwork for your review. Please click the link below to review your order artwork:</strong></p><p align='left' style='line-height:1.5;margin:0 0 5px;text-align:left!important;color:#525252;'><br/>Link : <a target='_blank' href='" . $mailData['url'] . "'> " . $mailData['url'] . " </a> <br/></p><p align='left' style='line-height:1.5;margin:0 0 5px;text-align:left!important;color:#525252;'>Once you have reviewed your order, approve the order. If you have any questions on your artwork, please chat with the sales associate.</p><p align='left' style='line-height:1.5;margin:10px 0 5px;text-align:left!important;color:#525252'>Thanks!</p><p align='left' style='line-height:1.5;margin:0 0 5px;text-align:left!important;color:#525252'>" . $admin['name'] . "</p></td></tr></tbody></table>";
		$emailBody = $prev . $html . $next;
		//Get smtp email setting data for sending email
		$smtpEmailSettingData = call_curl([],
			'settings', 'GET'
		);
		$smtpData = $smtpEmailSettingData['general_settings']['smtp_details'];
		$emailData = $smtpEmailSettingData['general_settings']['email_address_details'];
		$fromEmail = $emailData['from_email'];
		$replyToEmail = $emailData['to_email'];
		$mailContaint = ['from' => ['email' => $fromEmail, 'name' => $fromEmail],
			'recipients' => ['to' => ['email' => $mailData['customer_email'],
				'name' => $mailData['customer_name']],
				'reply_to' => ['email' => $replyToEmail,
					'name' => $replyToEmail],
			],
			'subject' => 'Approve your order',
			'body' => $emailBody,
			'smptData' => $smtpData,
		];
		$mailResponse = email($mailContaint);
		if (!empty($mailResponse['status']) && $mailResponse['status'] == 1) {
			return true;
		}
		return false;
	}

	/**
	 * PUT : Change Artwork Status
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   18 March 2020
	 * @return json response wheather data is updated or not
	 */
	public function updateOrderArtworkStatus($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Artwork Status ', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$updateData = $request->getParsedBody();
		$orderStatus = ['artwork_status' => $updateData['artwork_status']];
		if (!empty($args['id']) && $args['id'] > 0) {
			$isCreateProductionJob = false;
			$ordersInit = new Orders();
			$getArtworkStatus = $ordersInit->where('order_id', $args['id']);
			if ($getArtworkStatus->count() > 0) {
				$ordersInit->where('order_id', $args['id'])
					->update($orderStatus);
				//Create production job
				if ($updateData['artwork_status'] == 'approved') {
					//Get global order setting
					$settingInit = new Setting();
					$orderSetting =  $settingInit->select('setting_value')->where([
						'type' => 6,
						'store_id' => $getStoreDetails['store_id'],
						'setting_key' => 'artwork_approval'
					]);
					$isArtworkApproval = 0;
					if ($orderSetting->count() > 0) {
						$orderSettingData = json_clean_decode($orderSetting->first(), true);
						$orderSettingData = json_clean_decode($orderSettingData['setting_value'],true);
						$isArtworkApproval = $orderSettingData['order_artwork_status'];
					}
					//Get production setting
					$productionSettingData = $this->getProductionSetting($request, $response, ['module_id' => 4, 'return_type' => 1]);
					$productionSettingData = $productionSettingData['data'];
					$isAutomaticJobCreation = (isset($productionSettingData['is_automatic_job_creation'])) ? $productionSettingData['is_automatic_job_creation'] : 0;
					$purchaseOrderMandetory = (isset($productionSettingData['purchase_order_mandetory'])) ? $productionSettingData['purchase_order_mandetory'] : 0;
					if ($isAutomaticJobCreation == 1 && $isArtworkApproval == 1) {
						$checkPoStatus = $ordersInit->where([
							'order_id' => $args['id'],
							'po_status' => 3,
						]);
						if ($purchaseOrderMandetory != 1) {
							$isCreateProductionJob = true;
						} else if ($purchaseOrderMandetory == 1 && $checkPoStatus->count() > 0) {
							$isCreateProductionJob = true;
						}
					}
				}

			}
			$jsonResponse = [
				'status' => 1,
				'order_id' => $args['id'],
				'is_create_production_job' => $isCreateProductionJob,
				'message' => message('Artwork Status', 'updated'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET : Only for Shopify use to delete/hide duplicate products
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashisd@riaxe.com
	 * @date   21 April 2020
	 * @return json response wheather product is updated or not
	 */
	public function editShopifyProduct($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Shopify Product Status ', 'error'),
		];
		$updateData = $this->editCustomProduct($request, $response, $args);

		return json_encode($updateData);
	}

	/**
	 * GET: Generate Order Packing Slip
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Args object
	 *
	 * @author radhanatham@riaxe.com
	 * @date   21 May 2020
	 * @return array json
	 */
	public function downloadPackingSlip($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Packing Slip Download', 'error'),
		];
		$orderIncrementId = $request->getQueryParam('order_increment_id') ? $request->getQueryParam('order_increment_id') : 0;
		$orderAssetPath = path('abs', 'order') . $orderIncrementId;
		if (is_dir($orderAssetPath)) {
			$orderId = $orderIncrementId;
		} else {
			$orderId = $args['id'];
		}
		$getStoreDetails = get_store_details($request);
		$storeId = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
		//Get order seeting from general settings
		$settingInit = new \App\Modules\Settings\Models\Setting();
		$getSettings = $settingInit->where('type', '>', 0);
		$orderSetting = [];
		$packageSlipNotes = $pakageSlipAddress = $packingSlipLogo = '';
		$isProductImage = false;
		$isPackageSlipNotes = false;
		$packageSlipStoreUrl = '';

		if ($getSettings->count() > 0) {
			$data = $getSettings->get();
			foreach ($data as $value) {
				if ($value['type'] == 6) {
					$packingSlipLogo = $this->getPackingSlipLogo($storeId);
					$orderSetting['packing_slip_logo'] = $packingSlipLogo;
					$orderSetting[$value['setting_key']] = json_clean_decode(
						$value['setting_value'], true
					) ? json_clean_decode(
						$value['setting_value'], true
					) : $value['setting_value'];
				}
			}
			if ($orderSetting['package_slip']['is_package_slip_notes']) {
				$isPackageSlipNotes = true;
				$packageSlipNotes = $orderSetting['package_slip']['package_slip_notes'] ? $orderSetting['package_slip']['package_slip_notes'] : '';
				$packageSlipNotes = str_replace('  ', ' &nbsp;', nl2br(htmlentities($packageSlipNotes)));
			}
			$pakageSlipAddress = $orderSetting['package_slip']['package_slip_address'] ? $orderSetting['package_slip']['package_slip_address'] : '';
			$pakageSlipAddress = str_replace('  ', ' &nbsp;', nl2br(htmlentities($pakageSlipAddress)));
			$isProductImage = $orderSetting['package_slip']['is_package_slip_image_inlude'] ? true : false;
			$packageSlipStoreUrl = $orderSetting['package_slip']['package_slip_url'] ? $orderSetting['package_slip']['package_slip_url'] : '';
		}

		if (!empty($args) && $orderId && !empty($orderSetting)) {
			$args['is_return'] = true;
			$order = $this->getOrderList($request, $response, $args);
			if (!empty($order) && $order['order_details']) {
				$barcode = generate_barcode($orderId);
				$barcodeImageSrc = 'data:image/png;base64,' . base64_encode($barcode);
				$orderDetails = $order['order_details'];
				$totalPrice = $orderDetails['total_amount'] + $orderDetails['total_tax'];
				$totalQty = 0;
				$createDate = $orderDetails['created_date'];
				$orderDate = date('jS F, Y', strtotime($createDate));
				$storeUrl = $packageSlipStoreUrl ? $packageSlipStoreUrl : $orderDetails['store_url'];
				$html = '';
				$html .= '<!doctype html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Packing Slip</title>
                    <style>
                        @media print, screen {
                            .invoice-box {
                                max-width: 800px;
                                margin: auto;
                                font-size: 16px;
                                line-height: 24px;
                                font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
                                color: #555;
                            }

                            .invoice-box table {
                                width: 100%;
                                line-height: inherit;
                                text-align: left;
                            }

                            .invoice-box table.borderTable {
                                border: 1px solid #ddd;
                                margin-bottom: 20px;
                            }

                            .invoice-box table td {
                                padding: 5px;
                                vertical-align: top;
                            }

                            .invoice-box table tr td:last-child {
                                text-align: right;
                            }

                            .invoice-box table tr td:only-child {
                                text-align: left;
                            }

                            .invoice-box table tr.top table td {
                                padding-bottom: 20px;
                            }

                            .invoice-box table tr.top table td.title {
                                color: #333;
                                text-align: left;
                            }

                            .invoice-box table tr.information table td {
                                padding-bottom: 40px;
                            }

                            .invoice-box table tr.heading td {
                                background: #eee;
                                border-bottom: 1px solid #ddd;
                                font-weight: bold;
                            }

                            .invoice-box table tr.item td{
                                border-bottom: 1px solid #eee;
                                vertical-align: middle;
                            }

                            .invoice-box table tr.item.last td {
                                border-bottom: none;
                            }

                            .invoice-box table tr.total td {
                                border-top: 2px solid #eee;
                                font-weight: bold;
                            }

                            /** RTL **/
                            .rtl {
                                direction: rtl;
                                font-family: Tahoma, "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
                            }

                            .rtl table {
                                text-align: right;
                            }

                            .rtl table tr td:nth-child(2) {
                                text-align: left;
                            }
                            .price{
                                font-family: "Helvetica Neue", "Helvetica", Helvetica, Arial, sans-serif;
                                color: #555;
                                border: none;
                            }
                        }

                        @media only screen and (max-width: 600px) {
                            .invoice-box table tr.top table td {
                                width: 100%;
                                display: block;
                                text-align: center;
                            }

                            .invoice-box table tr.information table td {
                                width: 100%;
                                display: block;
                                text-align: center;
                            }
                        }
                    </style>
                </head>

                <body>
                    <div class="invoice-box">
                        <table cellpadding="0" cellspacing="0">
                            <tr class="top">
                                <td colspan="2">
                                    <table>
                                        <tr>
                                            <td class="title">
                                                <img src="' . $packingSlipLogo . '" style="height:100%; max-height:30px; margin-bottom: 20px;"><br>
                                                ' . $pakageSlipAddress . ' <br>
                                                ' . $storeUrl . '
                                            </td>

                                            <td class="information">
                                                <h3 style="margin: 0 0 10px 0;">Packing Slip</h3>
                                                Oder Id: <strong>#' . $orderDetails['id'] . '</strong><br>
                                                Order Date: <strong>' . $orderDate . ' </strong><br>
                                                Payment mode: <strong>' . $orderDetails['payment'] . '</strong><br>
                                                <img src="' . $barcodeImageSrc . '" style="height:100%; max-height:100px; margin-top: 20px;">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <table class="borderTable" cellpadding="0" cellspacing="0">
                            <tr class="heading">
                                <td>
                                    Bill to
                                </td>
                                <td>
                                    Ship to
                                </td>
                            </tr>

                            <tr class="details">
                                <td>
                                    ' . $orderDetails['billing']['first_name'] . ' ' . $orderDetails['billing']['last_name'] . '<br>
                                    ' . $orderDetails['billing']['address_1'] . ' ' . $orderDetails['billing']['address_2'] . '<br>
                                    ' . $orderDetails['billing']['city'] . ', ' . $orderDetails['billing']['state'] . ', ' . $orderDetails['billing']['postcode'] . ',' . $orderDetails['billing']['phone'] . '
                                </td>
                                <td>
                                    ' . $orderDetails['shipping']['first_name'] . ' ' . $orderDetails['shipping']['last_name'] . '<br>
                                    ' . $orderDetails['shipping']['address_1'] . ' ' . $orderDetails['shipping']['address_2'] . '<br>
                                    ' . $orderDetails['shipping']['city'] . ', ' . $orderDetails['shipping']['state'] . ', ' . $orderDetails['shipping']['postcode'] . ',' . $orderDetails['shipping']['phone'] . '
                                </td>
                            </tr>
                        </table>

                        <table class="borderTable" cellpadding="0" cellspacing="0" nobr="true">
                            <tr class="heading">
                                <td>Item</td>';
                if ($isProductImage) {
                    $html .= '<td>Image</td>';
                }
                $html .= '<td>Product</td>
                                <td>Quantity</td>
                                <td>Price</td>
                            </tr>';
                if (!empty($orderDetails['orders'])) {
                    $tr = '';
                    foreach ($orderDetails['orders'] as $k => $item) {
                        $totalQty += $item['quantity'];
                        $td = '';
                        $no = $k + 1;
                        $tr .= '<tr>
                                <td>
                                ' . $no . '
                                </td>';
                        if ($isProductImage) {
                            $tdinner = '';
                            $td .= '<td><table style="width:auto"><tr>';
                            if (!empty($item['images'])) {
                                foreach ($item['images'] as $key => $image) {
                                    $tdinner .= '<td><img src="' . $image['thumbnail'] . '" alt="image" style="height:100%; max-height:30px;margin: 1px;border:none;" ></td>';
                                }
                            }
                            $td .= $tdinner . '</tr></table></td>';
                        }
                        $td .= '<td>
                                ' . $item['name'] . '
                                </td>
                                <td>
                                ' . $item['quantity'] . '
                                </td>
                                <td>
                                ' . number_format($item['price'], 2) . ' ' . $orderDetails['currency'] . '
                                </td>
                                </tr>';
                        $tr .= $td;
                    }
                }
                $html .= $tr . ' <tr class="total">
                                <td colspan="3"></td>
                                <td colspan="2">
                                <table class="price"><tbody><tr><td style="border: none;">Subtotal:</td><td style="border: none;"> ' . number_format($orderDetails['total_amount'], 2) . ' ' . $orderDetails['currency'] . '</td></tr>
                                <tr><td style="border: none;"> Tax:</td><td style="border: none;"> ' . number_format($orderDetails['total_tax'], 2) . ' ' . $orderDetails['currency'] . '</td></tr>
                                <tr style="font-weight: bold;"><td>Total:</td><td> ' . number_format($totalPrice, 2) . ' ' . $orderDetails['currency'] . '</td></tr> </tbody></table>
                                </td>
                            </tr>
                        </table>';
                if ($isPackageSlipNotes) {
                    $html .= '<table class="borderTable" cellpadding="0" cellspacing="0" nobr="true">
                                <tr class="heading">
                                    <td>
                                        Note
                                    </td>
                                    <td>
                                    </td>
                                </tr>
                                <tr class="details">
                                    <td>
                                       ' . $packageSlipNotes . '
                                    </td>
                                    <td>
                                    </td>
                                </tr>
                            </table>';
                }
                $html .= '<table cellpadding="0" cellspacing="0" nobr="true">
                            <tr class="details">
                                <td>
                                   <strong>Thank you for your business!</strong><br>
                                   Please let us know if you have any questions. We are here to help!
                                </td>
                                <td>
                                </td>
                            </tr>
                        </table>
                    </div>
                </body>
                </html>';
				$orderPath = path('abs', 'order');
				$orderIdPath = $orderPath . $orderId . '/';
				$fileName = create_pdf($html, $orderIdPath);
				$dir = $orderIdPath . $fileName;
				//Download file in local system
				if (file_download($dir)) {
					$serverStatusCode = OPERATION_OKAY;
					$jsonResponse = [
						'status' => 1,
					];
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Order seeting packing slip logo
	 *
	 * @author radhanatham@riaxe.com
	 * @date   28 May 2020
	 * @return String
	 */
	private function getPackingSlipLogo($storeId) {
		$packingSlipLogo = '';
		$orderSettingPath = path('abs', 'setting') . 'order_setting';
		if (is_dir($orderSettingPath)) {
			$orderSettingPath = $orderSettingPath . '/' . $storeId;
			$scanDir = scandir($orderSettingPath);
			if (is_array($scanDir)) {
				foreach ($scanDir as $dir) {
					if ($dir != '.' && $dir != '..' && (strpos($dir, "thumb_") === false)) {
						$packingSlipLogo = path('read', 'setting') . 'order_setting/' . $storeId . '/' . $dir;
					}
				}
			}
		}
		return $packingSlipLogo;
	}
	/**
	 * GET : Get all order status
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumyas@riaxe.com
	 * @date   03 June 2020
	 * @return json response wheather data is updated or not
	 */
	public function getAllOrderStatus($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Order Status ', 'error'),
		];
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$orderStatuses = $this->getDefaultOrderStatuses($storeId);
		if (!empty($orderStatuses)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $orderStatuses,
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'No order status found',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST : Update order status
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumyas@riaxe.com
	 * @date   03 June 2020
	 * @return json response wheather data is updated or not
	 */
	public function updateOrderStatus($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Update Order Status ', 'error'),
		];
		if (isset($args) && !empty($args)) {
			$orderId = $args['id'];
			$updateData = $request->getParsedBody();
			$storeDetails = get_store_details($request);
			$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
			if (!empty($updateData)) {
				$updateResponse = $this->updateStoreOrderStatus($orderId, $updateData, $storeId);
				if ($updateResponse['id'] > 0 || $updateResponse == 'success') {
					$jsonResponse = [
						'status' => 1,
						'message' => 'Order status updated successfully',
					];
				} else {
					$jsonResponse = [
						'status' => 0,
						'message' => 'Order status not updated',
					];
				}

			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Order status empty',
				];
			}

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Order id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST : Artwork send to vendor
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumyas@riaxe.com
	 * @date   04 June 2020
	 * @return json response wheather data is updated or not
	 */
	public function sendToPrintShop($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Save order token ', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$fromEmail = $allPostPutVars['fromEmail'] ? $allPostPutVars['fromEmail'] : '';
		$printShopEmail = $allPostPutVars['printShopEmail'] ? $allPostPutVars['printShopEmail'] : '';
		$customerEmail = $allPostPutVars['customerEmail'];
		$orderId = $allPostPutVars['orderId'];
		$orderItemId = $allPostPutVars['orderItemId'];
		$subject = $allPostPutVars['subject'] ? $allPostPutVars['subject'] : '';
		$message = $allPostPutVars['description'] ? $allPostPutVars['description'] : '';
		$responseData = [];
		$moduleId = 2; /* Order module  */
		$getStoreDetails = get_store_details($request) ? get_store_details($request) : 1;
		if (!empty($customerEmail) && !empty($orderId) && !empty($orderItemId)) {
			if (strtolower(STORE_NAME) == "prestashop") {
				$smtpEmailSettingData = call_curl([], 'settings', 'GET', true);
			} else {
				$smtpEmailSettingData = call_api(
					'settings', 'GET', []
				);
			}
			$replyToEmail = '';
			$attachments = [];
			$emailData = $smtpEmailSettingData['general_settings']['email_address_details'];
			$smtpData = $smtpEmailSettingData['general_settings']['smtp_details'];
			$fromEmail = $emailData['from_email'];
			$mailContaint = ['from' => ['email' => $fromEmail, 'name' => $fromEmail],
				'recipients' => [
					'to' => [
						'email' => $printShopEmail,
						'name' => '',
					],
					'reply_to' => [
						'email' => $replyToEmail,
						'name' => $replyToEmail,
					],
				],
				'attachments' => ($attachments != '') ? $attachments : [],
				'subject' => $subject,
				'body' => $message,
				'smptData' => $smtpData,
			];
			if ($smtpData['smtp_host'] != '' && $smtpData['smtp_user'] != '' && $smtpData['smtp_pass'] != '') {
				$mailResponse = email($mailContaint);
			} else {
				$mailResponse['status'] = 0;
			}
			if (!empty($mailResponse['status']) && $mailResponse['status'] == 1) {
				$jsonResponse = [
					'status' => 1,
					'message' => 'Email sent successfully ',
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Email can not send',
				];
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Customer email empty',
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST : Archive Orders
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   19 July 2020
	 * @return json response wheather product is updated or not
	 */
	public function archiveOrders($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Archile Orders', 'error'),
		];
		$storeResponse = $this->archiveOrderById($request, $response, $args);
		if ($storeResponse['status'] == 1) {
			$jsonResponse = [
				'status' => 1,
				'message' => message('Archile Orders', 'done'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST : Convert to order
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumyas@riaxe.com
	 * @date   27 May 2020
	 * @return json response wheather data is updated or not
	 */
	public function convertToOrder($request, $response, $args) {
		$allPostPutVars = $request->getParsedBody();
		$fetchData = $allPostPutVars['data'];
		$decodeData = json_clean_decode($fetchData, true);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Order Status ', 'error'),
		];
		if (!empty($decodeData)) {
			if ($decodeData['quote_id'] && $decodeData['customer_id']) {
				$storeResponseOrder = $this->storeOrder($decodeData);
				if (!empty($storeResponseOrder)) {
					if ($storeResponseOrder['id'] > 0) {
						$jsonResponse = [
							'status' => 1,
							'data' => $storeResponseOrder['id'],
							'order_number' => (isset($storeResponseOrder['order_number']) && $storeResponseOrder['order_number'] != '') ? $storeResponseOrder['order_number'] : $storeResponseOrder['id'],
							'message' => 'Order placed successfully',
						];

					} else {
						$jsonResponse = [
							'status' => 0,
							'message' => 'Order placed error',
						];
					}

				} else {
					$jsonResponse = [
						'status' => 0,
						'message' => 'Order not created',
					];
				}
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Quote id / Customer id  are empty ',
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Store Items Details
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Args object
	 *
	 * @author debashrib@riaxe.com
	 * @date   12 July 2020
	 * @return array json
	 */
	public function getStoreItemsDetails($request, $response, $args, $returnType = 0) {
		$serverStatusCode = OPERATION_OKAY;
		$orderDetails = $this->orderItemDetails($request, $response, $args);
		if ($returnType == 1) {
			return $orderDetails;
		}
		return response(
			$response, ['data' => $orderDetails, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Generate Work Order Slip
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Args object
	 *
	 * @author debashrib@riaxe.com
	 * @date   07 Sept 2020
	 * @return array json
	 */
	public function downloadWorkOrderSlip($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Work Order Slip Download', 'error'),
		];
		$orderId = $args['id'];
		if (!empty($args) && $orderId && $orderId > 0) {
			if (!empty($_REQUEST['_token'])) {
				$getToken = $_REQUEST['_token'];
			} else {
				$getToken = '';
			}
			$args['is_return'] = true;
			$order = $this->getOrderList($request, $response, $args);

			if (!empty($order) && $order['order_details']) {
				$orderDetails = $order['order_details'];
				$html = '<body style="margin: 0; padding: 0;">
                <div style="margin: 0px; padding: 0px; background: #fff; -webkit-box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); position: relative; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif;">

                <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%;">
              <tr>
                <td style="vertical-align: top;">
                  <h3 class="title mb-3">Work order slip</h3>
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Order Number</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>#' . $orderDetails['order_number'] . '</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Purchase Date</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . date("M d,Y h:i a", strtotime($orderDetails['created_date'])) . '</strong>
                      </td>
                    </tr>
                  </table>
                </td>
                <td style="vertical-align: top; text-align: right; font-size: 14px;">';
                $html .= '<address style="font-size: 14px; line-height: 22px;">
                    ' . $orderDetails['customer_first_name'] . ' ' . $orderDetails['customer_last_name'] . '<br/>
                    ' . $orderDetails['customer_email'] . '<br/>
                    ' . $orderDetails['billing']['phone'] . '
                  </address>
                </td>
              </tr>
            </table>
            <hr style="margin-bottom: 30px; margin-top: 30px; width: 100%; border:1px solid #e3e3e3" />
            <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%; margin-bottom: 30px;">
              <tr>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  <small>Billing Address</small>
                  <address>
                    ' . $orderDetails['billing']['address_1'] . ', ' . $orderDetails['billing']['address_2'] . '<br/>
                    ' . $orderDetails['billing']['city'] . ', ' . $orderDetails['billing']['state'] . '<br/>
                    ' . $orderDetails['billing']['country'] . '-' . $orderDetails['billing']['postcode'] . '
                  </address>
                </td>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  <small>Shipping Address</small>';
				if ($orderDetails['shipping']['address_1'] != '') {
					$html .= '<address>
                    ' . $orderDetails['shipping']['address_1'] . ', ' . $orderDetails['shipping']['address_2'] . '<br/>
                    ' . $orderDetails['shipping']['city'] . ', ' . $orderDetails['shipping']['state'] . '<br/>
                    ' . $orderDetails['shipping']['country'] . '-' . $orderDetails['shipping']['postcode'] . '
                  </address>';
				}
				$html .= '</td>
              </tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; line-height: 24px;">
              <thead>
                <tr>
                <th width="20" style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Sl No.
                  </th>
                  <th width="200" style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Product Name
                  </th>
                  <th width="70" style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Unit Price
                  </th>
                  <th width="50" style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Total Qty
                  </th>
                  <th width="100" style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left;">
                    Total Price
                  </th>
                </tr>
              </thead>
              <tbody>';
                $subtotal = 0;
                foreach ($orderDetails['orders'] as $orderKey => $orders) {
                    $slNo = $orderKey + 1;
                    $background = (($slNo % 2) == 0) ? 'background-color: rgba(0, 0, 0, 0.05);' : '';
                    $subtotal = $subtotal + $orders['total'];
                    $html .= '<tr>
                  <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-top:1px solid #e3e3e3; border-bottom:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $background . '">' . $slNo . '</td>
                  <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-top:1px solid #e3e3e3; border-bottom:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $background . '">' . $orders['name'] . '</td>
                  <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-top:1px solid #e3e3e3; border-bottom:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $background . '">' . number_format($orders['price'], 2) . ' ' . $orderDetails['currency'] . '</td>
                  <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-top:1px solid #e3e3e3; border-bottom:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $background . '">' . $orders['quantity'] . '</td>
                  <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-top:1px solid #e3e3e3; border-right:1px solid #e3e3e3; border-bottom:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $background . '">' . number_format($orders['total'], 2) . ' ' . $orderDetails['currency'] . '</td>
                </tr>';
                }
                $totalAmount = ($orderDetails['total_amount'] + $orderDetails['total_shipping'] + $orderDetails['total_tax']) - $orderDetails['total_discounts'];
                $display = ($orderDetails['note'] == '') ? 'display: none;' : '';
                $html .= '</tbody>
            </table>
            <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
              <tr>
                <td>
                  <h4 style="' . $display . '">Note to Recipient / Terms & Conditions</h4>
                  <p style="font-size: 14px; line-height: 22px; ' . $display . '">
                    ' . $orderDetails['note'] . '
                  </p>
                </td>
                <td style="width: 50%; text-align: right;">
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-right:0; border-bottom:0;
                            text-align: right;">Subtotal</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-bottom:0"><strong>' . number_format($orderDetails['total_amount'], 2) . ' ' . $orderDetails['currency'] . '</strong></td>
                    </tr>';
                $html .= '<tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-right:0; border-bottom:0;">Discount</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-bottom:0;"><strong>-' . number_format($orderDetails['total_discounts'], 2) . ' ' . $orderDetails['currency'] . '</strong></td>
                    </tr>
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-right:0; border-bottom:0;">Shipping</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3;text-align: right; border-bottom:0;"><strong>' . number_format($orderDetails['total_shipping'], 2) . ' ' . $orderDetails['currency'] . '</strong></td>
                    </tr>';

                $html .= '<tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; border-right:0; text-align: right;">Tax</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; text-align: right;"><strong>' . number_format($orderDetails['total_tax'], 2) . ' ' . $orderDetails['currency'] . '</strong></td>
                    </tr>
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-right:0; text-align: right;">Total</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; font-size: 20px;">
                        <strong>' . number_format($totalAmount, 2) . ' ' . $orderDetails['currency'] . '</strong>
                      </td>
                    </tr>
                  </table>
                  <small>
                    (All prices are shown in ' . $orderDetails['currency'] . ')
                  </small>
                </td>
              </tr>
            </table>';
				foreach ($orderDetails['orders'] as $orderKey => $orders) {
					$slNo = $orderKey + 1;
					$productId = $orders['product_id'];
					$html .= '<table width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td colspan="2">
                  <h3 class="title mb-4">Order item #' . $slNo . '</h3>
                </td>
              </tr>
              <tr>
                <td style="vertical-align: top;">
                  <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="padding: 5px;">
                        <figure style="width: 100px; margin: 0;">
                          <img src="' . $orders['store_image'][0]['thumbnail'] . '" style="width: 100%;" alt=""/>
                        </figure>
                      </td>
                    </tr>
                  </table>
                </td>
                <td style="vertical-align: top; padding-left: 40px;">
                  <h3 style="font-size: 18px; margin-bottom: 20px;">
                    ' . $orders['name'] . '<br><small>sku:'.$orders['sku'].'</small>
                  </h3>

                  <table width="100%" cellspacing="0" cellpadding="0" style="border: 0px solid #e3e3e3; font-size: 14px;">
                    <tr>
                      <td style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">Quantity</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                        ' . $orders['quantity'] . '
                      </td>
                    </tr>
                    <tr>
                      <td style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">Price</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                        ' . number_format($orders['price'], 2) . ' ' . $orderDetails['currency'] . '
                      </td>
                    </tr>
                  </table></td>
              </tr>
              <tr>
                <td colspan="2">
                  <h4 style="font-size: 16px; margin-top: 20px;">
                    Artwork used
                  </h4>
                  ';
                    foreach ($orders['decoration_settings_data'] as $key => $decorationSettingsData) {
                        if (!empty($decorationSettingsData['decoration_data'])) {
                            $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="border: 0px solid #e3e3e3; font-size: 14px;">
                    <tr>
                      <td style="padding: 12px; border: 1px solid #e3e3e3;">
                        ';
                            foreach ($decorationSettingsData['decoration_data'] as $decoKey => $decorationData) {
                                $html .= '<table width="100%" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="vertical-align: top;">
                            <h3>Preview</h3>
                              <figure style="margin: 0; width: 150px; height: 150px;" >
                                <img src="' . $orders['images'][$key]['src'] . '" alt="" style="width: 150px; height: 150px;" />
                              </figure>
                            </td>
                             <td style="vertical-align: top;">
                             <h3>Design</h3>
                              <figure style="margin: 0; width: 150px; height: 150px;">';
								$html .= "<img src='" . $decorationData['png_url'] . "' alt='' style='width: 150px; height: 150px;' /></figure>";
								if ($decorationData['design_width'] != '' && $decorationData['design_height'] != '') {
									$html .= '<p>' . $decorationData['design_width'] . ' X ' . $decorationData['design_height'] . ' (W X H) ' . $decorationData['print_unit'] . ' </p>';
								}
								$html .= '
                            </td>
                            <td style="vertical-align: top;">
                              <table>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Decoration area name</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['decoration_name'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Decoration area</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_area_name'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Print method</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_profile_name'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Width</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_area_width'] . ' ' . $decorationData['print_unit'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Height</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_area_height'] . ' ' . $decorationData['print_unit'] . '</strong></td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                          </table>';
                            }
                            $html .= '</td></tr></table>';
                        }
                    }

                    $html .= '</td>
              </tr>
            </table>';

                }
                $html .= '</div>
        </body>';
				$orderPath = path('abs', 'order');
				$orderIdPath = $orderPath . $orderId . '/';
				$fileName = create_pdf($html, $orderIdPath, $orderId, "portrait");
				$dir = $orderIdPath . $fileName;
				$fileUrl = path('read', 'order') . $orderId . '/' . $fileName;
				//Download file in local system
				if (file_download($dir)) {
					$serverStatusCode = OPERATION_OKAY;
					$jsonResponse = [
						'status' => 1,
					];
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST : Order abbriviation values
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumyas@riaxe.com
	 * @date   04 June 2020
	 * @return json response wheather data is updated or not
	 */
	public function getOrderAbbriviationValues($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Save order token ', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$customerEmail = $allPostPutVars['customerEmail'];
		$orderId = $allPostPutVars['orderId'];
		$orderItemId = $allPostPutVars['orderItemId'];
		$responseData = [];
		$moduleId = 2; /* Order module  */
		$getStoreDetails = get_store_details($request) ? get_store_details($request) : 1;
		if (!empty($customerEmail) && !empty($orderId) && !empty($orderItemId)) {
			/** get template data */
			$templateData = $this->getEmailTemplate($moduleId, $getStoreDetails, 'order_email_send');
			//$templateData = json_clean_decode($templateData, true);
			if (!empty($templateData)) {
				$abbrivationsInit = new ProductionAbbriviations();
				$getAbbrivations = $abbrivationsInit->where('module_id', $moduleId)->get();
				$abbriviationData = $getAbbrivations->toArray();
				$storeResponse = $this->getOrders($request, $response, ['id' => $orderId, 'store_id' => $getStoreDetails['store_id']]);
				if (!empty($abbriviationData)) {
					foreach ($abbriviationData as $abbrData) {
						$abbrValue = $this->getAbbriviationValue($abbrData['abbr_name'], $storeResponse['order_details']);
						$templateData[0]['message'] = str_replace($abbrData['abbr_name'], $abbrValue, $templateData[0]['message']);
						$templateData[0]['subject'] = str_replace($abbrData['abbr_name'], $abbrValue, $templateData[0]['subject']);
					}
					$responseData = $templateData[0];
				}
				$jsonResponse = [
					'status' => 1,
					'data' => $responseData,
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Order template empty',
				];
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Customer email empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET :Abbriviation values
	 *
	 * @param $orderId
	 * @param $orderItemId
	 * @param $customerEmailId
	 * @param $abbrName
	 *
	 * @author soumyas@riaxe.com
	 * @date   26 June 2020
	 * @return String
	 */
	public function getAbbriviationValue($abbrName, $getOrderDetails) {
		$abbrValue = '';
		$orderItemId = '';
		$orderItems = $getOrderDetails['orders'];
		$orderItemId = $orderItems[0]['id'];
		$productName = $orderItems[0]['name'] . ' SKU ' . $orderItems[0]['sku'];
		if ($abbrName == '{order_id}') {
			$abbrValue = $getOrderDetails['id'];
		}
		if ($abbrName == '{product_name}') {
			$abbrValue = $productName;
		}
		if ($abbrName == '{customer_name}') {
			if (!empty($getOrderDetails)) {
				$abbrValue = ($getOrderDetails['customer_first_name'] != '') ? $getOrderDetails['customer_first_name'] . " " . $getOrderDetails['customer_last_name'] . ',' : $getOrderDetails['customer_email'];
			}
		}
		if ($abbrName == '{public_url}') {
			$this->deleteOrderTokenData($getOrderDetails['id'], $orderItemId);
			$artworkLink = '';
			$tokenData = "emailId=" . $customerEmailId . "&orderId=" . $getOrderDetails['id'] . "&orderItemId=" . $orderItemId;
			$token = base64_encode($tokenData);
			$saveData = array(
				'order_id' => $getOrderDetails['id'],
				'order_item_id' => $orderItemId,
				'token' => $token,
			);
			$orderItemTokenInit = new OrderItemToken($saveData);
			$status = $orderItemTokenInit->save();
			if ($status) {
				$artworkLink = BASE_URL . "download-artwork/" . $token;
			}
			$abbrValue = $artworkLink;
		}
		if ($abbrName == '{customer_address}') {
			$shippingAddress = $getOrderDetails['shipping'];
			if (!empty($shippingAddress)) {
				$customerName = $shippingAddress['first_name'] . ' ' . $shippingAddress['last_name'];
				$companyName = $shippingAddress['company'] ? $shippingAddress['company'] : '';
				$address_1 = $shippingAddress['address_1'] ? $shippingAddress['address_1'] : '';
				$city = $shippingAddress['city'] ? $shippingAddress['city'] : '';
				$state = $shippingAddress['state'] ? $shippingAddress['state'] : '';
				$country = $shippingAddress['country'] ? $shippingAddress['country'] : '';
				$postCode = $shippingAddress['postcode'] ? $shippingAddress['postcode'] : '';
				$abbrValue = $customerName . ' ' . $companyName . ' ' . $address_1 . ' ' . $city . '' . $state . ' ' . $postCode . ' ' . $country;
			}

		}
		if ($abbrName == '{order_status}') {
			$abbrValue = $getOrderDetails['status'];
		}
		if ($abbrName == '{artwork_status}') {
			$abbrValue = $getOrderDetails['artwork_status'];
		}
		if ($abbrName == '{order_created_date}') {
			$abbrValue = $getOrderDetails['created_date'];
		}
		if ($abbrName == '{order_value}') {
			$abbrValue = $getOrderDetails['total_amount'] . ' ' . $getOrderDetails['currency'];
		}
		if ($abbrName == '{payment_type}') {
			$abbrValue = $getOrderDetails['payment'];
		}
		if ($abbrName == '{order_notes}') {
			$abbrValue = $getOrderDetails['note'];
		}
		if ($abbrName == '{mobile_no}') {
			$abbrValue = $getOrderDetails['billing']['phone'];
		}
		if ($abbrName == '{customer_email}') {
			$abbrValue = $getOrderDetails['customer_email'];
		}
		return $abbrValue;
	}

	/**
	 *
	 * Delete token
	 * @param $orderId
	 * @param $orderItemId
	 * @author soumyas@riaxe.com
	 * @date   21 september 2020
	 * @return array
	 *
	 */
	public function deleteOrderTokenData($orderId, $orderItemId) {
		$tokenInit = new OrderItemToken();
		$tokenDelete = $tokenInit->where(
			['order_id' => $orderId, 'order_item_id' => $orderItemId]
		);
		return $tokenDelete->delete();
	}
	/**
	 * GET: Order line item po status
	 *
	 * @param $orderId
	 * @param $orderItemId
	 *
	 * @author soumyas@riaxe.com
	 * @date   12 October 2020
	 * @return
	 */
	public function getOrderPoStatus($orderId, $orderItemId) {
		$poStatusArray = array();
		$purchaseOrderDetailsInit = new PurchaseOrderDetails();
		$getPurchaseOrder = $purchaseOrderDetailsInit->where(['order_id' => $orderId])->where(['order_item_id' => $orderItemId]);
		if ($getPurchaseOrder->count() > 0) {
			$purchaseStatusIds = $getPurchaseOrder->get()->toArray();
			if (!empty($purchaseStatusIds[0]['status_id'])) {
				$statusId = $purchaseStatusIds[0]['status_id'];
				$moduleId = 3;
				$storeId = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
				$statusInit = new PurchaseOrderLineItemStatus();
				$statusArr = $statusInit
					->select('xe_id as id', 'store_id', 'status_name', 'color_code', 'is_default')
					->where(
						[
							'xe_id' => $statusId,
							'store_id' => $storeId,
						]
					)->orderBy('sort_order', 'ASC');
				if ($statusArr->count() > 0) {
					$statusData = $statusArr->get()->toArray();
					$poStatusArray = $statusData[0];
				}
			}
		}
		return $poStatusArray;
	}
	/**
	 * GET: Order po status
	 *
	 * @param $orderId
	 * @param $orderItemId
	 *
	 * @author soumyas@riaxe.com
	 * @date   12 October 2020
	 * @return
	 */
	public function getOrderPoStatusDetails($orderId, $poStatusId) {
		$statusDetails = [];
		$statusIdArray = [];
		$storeId = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
		$moduleId = 3;
		$pendingStatusId = 1;
		$statusInit = new PurchaseOrderLineItemStatus();
		$checkStatusReceived = false;
		$purchaseOrderDetailsInit = new PurchaseOrderDetails();
		$getPurchaseOrder = $purchaseOrderDetailsInit->where(['order_id' => $orderId]);
		if ($getPurchaseOrder->count() > 0) {
			$purchaseStatusIds = $getPurchaseOrder->get()->toArray();
			foreach ($purchaseStatusIds as $purchaseStatusId) {
				$statusId = $purchaseStatusId['status_id'];
				$statusIdArray[] = $statusId;
			}
			if (count(array_unique($statusIdArray)) === 1 && end($statusIdArray) === 3) {
				$checkStatusReceived = true;
			}
		}
		if ($checkStatusReceived) {
			$statusArr = $statusInit
				->select('xe_id as id', 'status_name', 'color_code', 'is_default')
				->where(
					[
						'xe_id' => $poStatusId,
						'store_id' => $storeId,
					]
				)->orderBy('sort_order', 'ASC');
			if ($statusArr->count() > 0) {
				$statusData = $statusArr->get()->toArray();
				$statusDetails = $statusData[0];
			}
		} else {
			if (in_array(3, $statusIdArray)) {
				$partiallyReceived = 5;
				$statusArr = $statusInit
					->select('xe_id as id', 'status_name', 'color_code', 'is_default')
					->where(
						['xe_id' => $partiallyReceived,
							'store_id' => $storeId,
						]
					)->orderBy('sort_order', 'ASC');
				if ($statusArr->count() > 0) {
					$statusData = $statusArr->get()->toArray();
					$statusDetails = $statusData[0];
				}
			} else {
				$createdStatusId = 4;
				$statusArr = $statusInit
					->select('xe_id as id', 'status_name', 'color_code', 'is_default')
					->where(
						['xe_id' => $createdStatusId,
							'store_id' => $storeId,
						]
					)->orderBy('sort_order', 'ASC');
				if ($statusArr->count() > 0) {
					$statusData = $statusArr->get()->toArray();
					$statusDetails = $statusData[0];
				}
			}

		}
		return $statusDetails;
	}

	/**
	 * Add data to orders table
	 *
	 * @param $orderId  Order id
	 * @param $$storeId Store id
	 * @param $customerId   Customer id
	 * @param $orderNumber   Order Number
	 *
	 * @author debashrib@riaxe.com
	 * @date   27 Oct 2020
	 * @return array json
	 */
	public function saveDataForOrder($orderId, $storeId, $customerId, $orderNumber = '') {
		$ordersInit = new Orders();
		$result = false;
		if ($orderId > 0 && $storeId > 0 && $customerId >= 0) {
			//check for order id
			$checkOrder = $ordersInit->where('order_id', $orderId);
			if ($checkOrder->count() == 0) {
				$saveOrderData = new Orders(
					[
						'order_id' => $orderId,
						'order_number' => $orderNumber,
						'artwork_status' => 'pending',
						'store_id' => $storeId,
						'customer_id' => $customerId,
					]
				);
				if ($saveOrderData->save()) {
					$result = true;
				}
			}
		}
		return $result;
	}

	/**
	 * Gte ordrer design data
	 *
	 * @param $orderResponse
	 *
	 * @author soumays@riaxe.com
	 * @date   18 Dec 2020
	 * @return array
	 */
	public function getOrderItemDesignData($orderResponse) {
		$ordersInit = new Orders();
		foreach ($orderResponse as $orderDetailsKey => $orderDetails) {
			$designImages = [];
			$productDecoData = [];
			if ($orderDetails['custom_design_id'] > 0 && $orderDetails['custom_design_id'] != '-1') {
				$orderFolderDir = path('abs', 'order') . $orderDetails['order_id'] . '/order.json';
				$jsonFile = read_file($orderFolderDir);
				$jsonFileContent = json_clean_decode($jsonFile, true);
				$quoteSource =  isset($jsonFileContent['order_details']['quote_source']) ? $jsonFileContent['order_details']['quote_source'] : '';
				$customDesignId = $orderDetails['custom_design_id'];
				$deisgnStatePath = path('abs', 'design_state') . 'carts';
				$predecoPath = path('abs', 'design_state') . 'predecorators';
				$quotationPath = path('abs', 'design_state') . 'artworks';
				$orderJsonPath = $deisgnStatePath . '/' . $customDesignId . ".json";
				$orderPredecoPath = $predecoPath . '/' . $customDesignId . ".json";
				$orderQuotationPath = $quotationPath . '/' . $customDesignId . ".json";
				if (file_exists($orderJsonPath)) {
					$orderJson = read_file($orderJsonPath);
					$jsonContent = json_clean_decode($orderJson, true);
				} elseif (file_exists($orderPredecoPath)) {
					$orderJson = read_file($orderPredecoPath);
					$jsonContent = json_clean_decode($orderJson, true);
				} elseif (file_exists($orderQuotationPath)) {
					$orderJson = read_file($orderQuotationPath);
					$jsonContent = json_clean_decode($orderJson, true);
				}
				if (!empty($jsonContent['design_product_data'])) {
					$variantIdArr = [];
					foreach ($jsonContent['design_product_data'] as $designImage) {
						// Added for same product image for artwork
						if ((file_exists($orderQuotationPath) && ($quoteSource == '' && $quoteSource == 'admin')) || file_exists($orderPredecoPath)) {
							$designImages = [];
							if (!empty($designImage['design_urls'])) {
								foreach ($designImage['design_urls'] as $image) {
									$designImages[] = [
										'src' => $image,
										'thumbnail' => $image,
									];
								}
							}
						} else {
							if ($orderDetails['variant_id'] == 0 || in_array($orderDetails['variant_id'], $designImage['variant_id'])) {
								if (!in_array($orderDetails['variant_id'], $variantIdArr)) {
									array_push($variantIdArr, $orderDetails['variant_id']);
									if (!empty($designImage['design_urls'])) {
										foreach ($designImage['design_urls'] as $image) {
											$designImages[] = [
												'src' => $image,
												'thumbnail' => $image,
											];
										}
									}
								}
							}
						}
						$orderResponse[$orderDetailsKey]['variableDecorationSize'] = isset($designImage['variable_decoration_size']) ? $designImage['variable_decoration_size'] : '';
						$orderResponse[$orderDetailsKey]['variableDecorationUnit'] = isset($designImage['variable_decoration_unit']) ? $designImage['variable_decoration_unit'] : '';
					}
				}
				if (!empty($jsonContent['sides'])) {
					$i = 1;
					foreach ($jsonContent['sides'] as $sideDetailsKey => $sideDetails) {
						$configurator = [];
						if (isset($sideDetails['configurator']) && !empty($sideDetails['configurator'])) {
							$configurator = $sideDetails['configurator'];
						}
						$sideName = !empty($sideDetails['side_name']) ? $sideDetails['side_name'] : "";
						$isDesign = !empty($sideDetails['is_designed']) ? $sideDetails['is_designed'] : 0;
						$decorationData = [];
						if (!empty($sideDetails['print_area'])) {
							$j = 0;
							foreach ($sideDetails['print_area'] as $profile) {
								$orderDwonloadObj = new OrderDownloadController();
								$svgUrl = ASSETS_PATH_R . 'orders/' . $orderDetails['order_id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $orderDetails['order_id'] . '.svg';
								$svgPath = ASSETS_PATH_W . 'orders/' . $orderDetails['order_id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $orderDetails['order_id'] . '.svg';
								$pngPath = ASSETS_PATH_W . 'orders/' . $orderDetails['order_id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $orderDetails['order_id'] . '.png';
								$pngUrl = ASSETS_PATH_R . 'orders/' . $orderDetails['order_id'] . '/' . $orderDetails['id'] . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $orderDetails['id'] . '_' . $orderDetails['order_id'] . '.png';
								if (!file_exists($pngPath)) {
									$orderDwonloadObj->svgConvertToPng($pngPath, $svgPath);
								}
								if ($profile['isDesigned'] > 0) {
									$decorationData[] = [
										'decoration_name' => isset($profile['name']) ? $profile['name'] : $profile['name'],
										'print_area_id' => $profile['print_area']['id'],
										'print_area_name' => $profile['print_area']['name'],
										'print_profile_id' => $profile['print_method_id'],
										'print_profile_name' => $profile['print_method_name'],
										'print_unit' => $sideDetails['print_unit'],
										'print_area_height' => $profile['print_area']['height'],
										'print_area_width' => $profile['print_area']['width'],
										'design_width' => isset($profile['design_width']) ? $profile['design_width'] : "",
										'design_height' => isset($profile['design_height']) ? $profile['design_height'] : "",
										//'svg_url' => $svgUrl,
										'png_url' => $pngUrl,
										'used_colors' => $profile['used_colors'] ? $profile['used_colors'] : [],
										'x_location' => isset($profile['design_x']) ? $profile['design_x'] : "",
										'y_location' => isset($profile['design_y']) ? $profile['design_y'] : "",
									];
								}
								$j++;
							}
						}
						$productDecoData[] = [
							'is_design' => $isDesign,
							'name' => $sideName,
							'decoration_data' => $decorationData,
							'configurator' => $configurator,
						];
						$i++;
					}
				}
			} else if ($orderDetails['custom_design_id'] == '-1') {
				$orderFolderDir = path('abs', 'order') . $orderDetails['order_id'] . '/order.json';
				$orderJson = read_file($orderFolderDir);
				$jsonContent = json_clean_decode($orderJson, true);

				$orderItemArr = $jsonContent['order_details']['order_items'];
				$itemId = $orderDetails['id'];
				$itemArr = array_filter($orderItemArr, function ($item) use ($itemId) {
					return ($item['item_id'] == $itemId);
				});
				$itemArr = $itemArr[array_keys($itemArr)[0]];
				$filesDataArr = $itemArr['file_data'];
				if (!empty($filesDataArr)) {
					foreach ($filesDataArr as $files) {
						$decorationData = [];
						foreach ($files['decoration_area'] as $decorationArea) {
							$designImages[] = [
								'src' => $decorationArea['upload_preview_url'],
								'thumbnail' => $decorationArea['upload_preview_url'],
							];
							$decorationData[] = [
								'decoration_name' => $decorationArea['decoration_area'],
								'print_area_id' => $decorationArea['print_area_id'],
								'print_area_name' => $decorationArea['print_area_name'],
								'print_profile_id' => $decorationArea['print_method_id'],
								'print_profile_name' => $decorationArea['print_methods'],
								'print_unit' => $decorationArea['measurement_unit'],
								'print_area_height' => $decorationArea['height'],
								'print_area_width' => $decorationArea['width'],
								'design_width' => $decorationArea['design_width'],
								'design_height' => $decorationArea['design_height'],
								//'svg_url' => $decorationArea['upload_design_url'],
								'png_url' => $decorationArea['upload_design_url'],
							];
						}
						$productDecoData[] = [
							'is_design' => 1,
							'name' => $files['side_name'],
							'decoration_data' => $decorationData,
						];
					}
				}

			}

			$orderResponse[$orderDetailsKey] += [
				'decoration_settings_data' => $productDecoData,
			];

			if (count($designImages) > 0 && strtolower(STORE_NAME) != "shopify") {
				$orderResponse[$orderDetailsKey]['images'] = $designImages;
			}
			if (count($designId) === 0 || !in_array($orderDetails['custom_design_id'], $designId)) {
				$notes[] = $jsonContent['notes'];
				$designId[] = $orderDetails['custom_design_id'];
			}
			if (strtolower(STORE_NAME) == "shopify") {
				$ordersStatus = $ordersInit->where('order_id', $storeResponse['order_details']['id'])->first();
				$orderResponse[$orderDetailsKey]['status'] = (!empty($ordersStatus->order_status)) ? $ordersStatus->order_status : 'received';
			}
			$orderResponse[$orderDetailsKey]['store_image'] = $orderDetails['images'];
		}
		return $orderResponse;
	}
}