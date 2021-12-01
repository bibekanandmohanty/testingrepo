<?php
/**
 * Manage Ship  address
 *
 * PHP version 5.6
 *
 * @category  Ship To address
 * @package   Production_Hub
 * @author    Soumya Swain <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\ShipAddress\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\ShipAddress\Models\ShipAddress;

/**
 * ShipAddress Controller
 *
 * @category Quotations
 * @package  Production_Hub
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class ShipAddressController extends ParentController {
	/**
	 * POST: Create ship to address
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function createShipAddress($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Ship to address', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$name = $allPostPutVars['name'] ? $allPostPutVars['name'] : '';
		$email = $allPostPutVars['email'] ? $allPostPutVars['email'] : '';
		$phone = $allPostPutVars['phone'] ? $allPostPutVars['phone'] : '';
		$companyName = $allPostPutVars['company_name'] ? $allPostPutVars['company_name'] : '';
		$countryCode = $allPostPutVars['country_code'] ? $allPostPutVars['country_code'] : '';
		$stateCode = $allPostPutVars['state_code'] ? $allPostPutVars['state_code'] : '';
		$zipCode = $allPostPutVars['zip_code'] ? $allPostPutVars['zip_code'] : '';
		$shipAddress = $allPostPutVars['ship_address'] ? $allPostPutVars['ship_address'] : '';
		$city = $allPostPutVars['city'] ? $allPostPutVars['city'] : '';
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$saveData = [
			'name' => $name,
			'email' => $email,
			'phone' => $phone,
			'company_name' => $companyName,
			'country_code' => $countryCode,
			'state_code' => $stateCode,
			'zip_code' => $zipCode,
			'ship_address' => $shipAddress,
			'city' => $city,
			'store_id' => $storeId,
		];
		$emailStatus = $this->checkDuplicateEmail(trim($email));
		if ($emailStatus) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Sorry, that email already exists',
			];
		} else {
			$shipToAddressInit = new ShipAddress($saveData);
			$status = $shipToAddressInit->save();
			if ($status) {
				$jsonResponse = [
					'status' => 1,
					'message' => 'Ship to address created successfully',
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Ship to address not created',
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 *
	 * Check duplicate email id
	 * @param $emailId
	 * @author soumyas@riaxe.com
	 * @date   30 June 2020
	 * @return array
	 *
	 */
	public function checkDuplicateEmail($emailId) {
		$shipToAddressInit = new ShipAddress();
		$emailData = $shipToAddressInit->select('email')
			->where('email', $emailId)->get()->toArray();
		if (!empty($emailData)) {
			return true;
		}
		return false;
	}
	/**
	 * GET: Ship To address
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function getShipAddressList($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Ship To address', 'error'),
		];

		$page = $request->getQueryParam('page') ? $request->getQueryParam('page') : 1;
		$perpage = $request->getQueryParam('perpage') ? $request->getQueryParam('perpage') : 20;
		$order = $request->getQueryParam('order') ? $request->getQueryParam('order') : 'DESC';
		$keyword = $request->getQueryParam('keyword');
		$isPurchaseOrder = $request->getQueryParam('is_purchase_order') ? $request->getQueryParam('is_purchase_order') : false;

		$addressList = array();
		$shipToAddressInit = new ShipAddress();
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			$getshipAddress = $shipToAddressInit->where(['xe_id' => $xeId]);
			if ($getshipAddress->count() > 0) {
				$addressData = $getshipAddress->get()->toArray();
				$countryName = '';
				$stateName = '';
				if ($addressData[0]['country_code']) {
					$countryEndPoint = 'country';
					$getcountryResponse = call_api($countryEndPoint, 'GET', []);
					if (!empty($getcountryResponse['data'])) {
						foreach ($getcountryResponse['data'] as $countryKey => $countryValue) {
							if ($countryValue['countries_code'] == $addressData[0]['country_code']) {
								$countryName = $countryValue['countries_name'];
								break;
							} else {
								$countryName = $addressData[0]['country_code'];
							}
						}
						if ($addressData[0]['state_code']) {
							$stateEndPoint = 'state/' . $addressData[0]['country_code'];
							$getStateResponse = call_api($stateEndPoint, 'GET', []);
							if (!empty($getStateResponse['data'])) {
								foreach ($getStateResponse['data'] as $stateKey => $stateValue) {
									if ($stateValue['state_code'] == $addressData[0]['state_code']) {
										$stateName = $stateValue['state_name'];
										break;
									} else {
										$stateName = $addressData[0]['state_code'];
									}
								}
							}
						}
					}
				}
				$addressList['id'] = $addressData[0]['xe_id'];
				$addressList['name'] = $addressData[0]['name'];
				$addressList['email'] = $addressData[0]['email'];
				$addressList['phone'] = $addressData[0]['phone'];
				$addressList['company_name'] = $addressData[0]['company_name'];
				$addressList['country_code'] = $addressData[0]['country_code'];
				$addressList['country_name'] = $countryName;
				$addressList['state_code'] = $addressData[0]['state_code'];
				$addressList['state_name'] = $stateName;
				$addressList['zip_code'] = $addressData[0]['zip_code'];
				$addressList['ship_address'] = $addressData[0]['ship_address'];
				$addressList['city'] = $addressData[0]['city'];
				$jsonResponse = [
					'status' => 1,
					'total' => $getshipAddress->count(),
					'data' => $addressList,
				];

			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'No data found',
					'data' => [],
				];
			}

		} else if ($isPurchaseOrder == true) {
			$getshipToAddress = $shipToAddressInit
				->select('xe_id as id', 'name', 'email', 'phone', 'company_name');
			$getTotalRecords = $getshipToAddress->count();
			if ($getTotalRecords > 0) {
				$getAddressData = $getshipToAddress->get()->toArray();
				$jsonResponse = [
					'status' => 1,
					'total' => count($getAddressData),
					'data' => $getAddressData,
				];

			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => '',
					'data' => [],
				];
			}
		} else {
			$getshipToAddress = $shipToAddressInit
				->select('xe_id as id', 'name', 'email', 'phone', 'company_name', 'country_code', 'state_code', 'zip_code', 'ship_address', 'city');
			$getTotalRecords = $getshipToAddress->count();
			// Pagination Data
			if (isset($page) && $page != "") {
				$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
				$offset = $totalItem * ($page - 1);
				$getshipToAddress->skip($offset)->take($totalItem);
			}
			// Sorting All records by column name and sord order parameter
			if (isset($order) && $order != "") {
				$getshipToAddress->orderBy("xe_id", $order);
			}
			//Search by quote company_name and contact_name
			if (isset($keyword) && $keyword != "") {
				$getshipToAddress->where('name', 'LIKE', '%' . $keyword . '%')
					->orWhere('name', 'LIKE', '%' . $keyword . '%');
			}
			if ($getTotalRecords > 0) {
				$getAddressData = $getshipToAddress->get()->toArray();
				foreach ($getAddressData as $key => $value) {
					$countryName = '';
					$stateName = '';
					if ($value['country_code']) {
						$countryEndPoint = 'country';
						$getcountryResponse = call_api($countryEndPoint, 'GET', []);
						if (!empty($getcountryResponse['data'])) {
							foreach ($getcountryResponse['data'] as $countryKey => $countryValue) {
								if ($countryValue['countries_code'] == $value['country_code']) {
									$countryName = $countryValue['countries_name'];
									break;
								} else {
									$countryName = $value['country_code'];
								}
							}
							if ($value['state_code']) {
								$stateEndPoint = 'state/' . $value['country_code'];
								$getStateResponse = call_api($stateEndPoint, 'GET', []);
								if (!empty($getStateResponse['data'])) {
									foreach ($getStateResponse['data'] as $stateKey => $stateValue) {
										if ($stateValue['state_code'] == $value['state_code']) {
											$stateName = $stateValue['state_name'];
											break;
										} else {
											$stateName = $value['state_code'];
										}
									}
								}
							}
						}
					}

					$addressList[$key]['id'] = $value['id'];
					$addressList[$key]['name'] = $value['name'];
					$addressList[$key]['email'] = $value['email'];
					$addressList[$key]['phone'] = $value['phone'];
					$addressList[$key]['company_name'] = $value['company_name'];
					$addressList[$key]['country_code'] = $value['country_code'];
					$addressList[$key]['country_name'] = $countryName;
					$addressList[$key]['state_code'] = $value['state_code'];
					$addressList[$key]['state_name'] = $stateName;
					$addressList[$key]['zip_code'] = $value['zip_code'];
					$addressList[$key]['ship_address'] = $value['ship_address'];
					$addressList[$key]['city'] = $value['city'];
				}
				$jsonResponse = [
					'status' => 1,
					'total' => $getTotalRecords,
					'data' => $addressList,
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'data' => [],
					'message' => 'No data found',
				];
			}

		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST: Update Ship To address
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function updateShipAddress($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Ship To address', 'error'),
		];
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			$shipToAddressInit = new ShipAddress();
			$allPostPutVars = $request->getParsedBody();
			$name = $allPostPutVars['name'] ? $allPostPutVars['name'] : '';
			$email = $allPostPutVars['email'] ? $allPostPutVars['email'] : '';
			$phone = $allPostPutVars['phone'] ? $allPostPutVars['phone'] : '';
			$companyName = $allPostPutVars['company_name'] ? $allPostPutVars['company_name'] : '';
			$countryCode = $allPostPutVars['country_code'] ? $allPostPutVars['country_code'] : '';
			$stateCode = $allPostPutVars['state_code'] ? $allPostPutVars['state_code'] : '';
			$zipCode = $allPostPutVars['zip_code'] ? $allPostPutVars['zip_code'] : '';
			$shipAddress = $allPostPutVars['ship_address'] ? $allPostPutVars['ship_address'] : '';
			$city = $allPostPutVars['city'] ? $allPostPutVars['city'] : '';
			$updateData = [
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'company_name' => $companyName,
				'country_code' => $countryCode,
				'state_code' => $stateCode,
				'zip_code' => $zipCode,
				'ship_address' => $shipAddress,
				'city' => $city,
			];
			$status = $shipToAddressInit->where('xe_id', '=', $xeId)->update($updateData);
			if ($status) {
				$jsonResponse = [
					'status' => 1,
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
				'message' => 'Address id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * DELETE: Delete ship address
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function deleteShipAddress($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Ship address', 'error'),
		];
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			/** check ship to addres before deleting  */
			$purchaseOrderInit = new PurchaseOrder();
			$idResponse = $purchaseOrderInit->select('vendor_id')->where(['ship_address_id' => $xeId]);
			if (empty($idResponse->get()->toArray())) {
				$shipToAddressInit = new ShipAddress();
				$addressId = $shipToAddressInit->whereIn('xe_id', [$xeId])
					->count();
				if ($addressId > 0) {
					$status = $shipToAddressInit->where('xe_id', $xeId)->delete();
					if ($status) {
						$jsonResponse = [
							'status' => 1,
							'message' => 'Deleted successfully',
						];
					} else {
						$jsonResponse = [
							'status' => 0,
							'message' => 'No data deleted',
						];
					}
				} else {
					$jsonResponse = [
						'status' => 0,
						'message' => 'Address id not found',
					];
				}
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'ship to address assign to purchase order',
				];
			}

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Address id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
}
