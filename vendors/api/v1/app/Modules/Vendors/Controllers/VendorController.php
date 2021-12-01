<?php
/**
 * Manage Vendor
 *
 * PHP version 5.6
 *
 * @category  Vendor
 * @package   Production_Hub
 * @author    Soumya <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Vendors\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Models\VendorCategory;

/**
 * Vendor Controller
 *
 * @category Vendor
 * @package  Production_Hub
 * @author   Soumya <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class VendorController extends ParentController {

	public $logUploadPath;

	/**
	 * Define image upload path
	 **/
	public function __construct() {
		$this->createVendorDirectory(path('abs', 'vendor'));
		$this->logUploadPath = path('abs', 'vendor');
	}
	/**
	 * create vendor directory
	 **/
	public function createVendorDirectory($dir) {
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
	}
	/**
	 * GET: Product Category
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function getProductCategory($request, $response, $args) {
		$endPoint = 'products/categories';
		$jsonResponse = [
			'status' => 0,
			'message' => message('Product category', 'error'),
		];
		$productCatResponse = call_api($endPoint, 'GET', []);
		if (!empty($productCatResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $productCatResponse['data'],
			];

		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);

	}
	/**
	 * POST: Create vendor
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function createVendor($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Vendor', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$companyName = $allPostPutVars['company_name'] ? $allPostPutVars['company_name'] : '';
		$contactName = $allPostPutVars['contact_name'] ? $allPostPutVars['contact_name'] : '';
		$email = $allPostPutVars['email'] ? $allPostPutVars['email'] : '';
		$phone = $allPostPutVars['phone'] ? $allPostPutVars['phone'] : '';
		$countryCode = $allPostPutVars['country_code'] ? $allPostPutVars['country_code'] : '';
		$stateCode = $allPostPutVars['state_code'] ? $allPostPutVars['state_code'] : '';
		$zipCode = $allPostPutVars['zip_code'] ? $allPostPutVars['zip_code'] : '';
		$billingAddress = $allPostPutVars['billing_address'] ? $allPostPutVars['billing_address'] : '';
		$city = $allPostPutVars['city'] ? $allPostPutVars['city'] : '';
		$productCat = $allPostPutVars['product_cat'] ? $allPostPutVars['product_cat'] : '';
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$productCat = json_clean_decode($productCat, true);
		$saveData = [
			'company_name' => $companyName,
			'contact_name' => $contactName,
			'email' => $email,
			'phone' => $phone,
			'zip_code' => $zipCode,
			'country_code' => $countryCode,
			'state_code' => $stateCode,
			'billing_address' => $billingAddress,
			'city' => $city,
			'store_id' => $storeId,
		];
		$allFileNames = do_upload(
			'logo_image', $this->logUploadPath, [200], 'array'
		);
		if (!empty($allFileNames)) {
			$saveData += ['logo' => $allFileNames[0]];
		}
		$emailStatus = $this->checkDuplicateEmail($email);
		if ($emailStatus) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Email id already exists. Please try another one',
			];
		} else {
			$vendorInit = new Vendor($saveData);
			$status = $vendorInit->save();
			if ($status) {
				$lastInsertId = $vendorInit->xe_id;
				if (!empty($productCat)) {
					foreach ($productCat as $catIds) {
						$productCatSaveData = [
							'vendor_id' => $lastInsertId,
							'category_id' => $catIds,
						];
						$vendorCatInit = new VendorCategory($productCatSaveData);
						$vendorCatInit->save();
					}
				}
				$jsonResponse = [
					'status' => 1,
					'message' => 'Vendor created successfully',
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Vendor not created',
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
	 * @param $quoteId
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return array
	 *
	 */
	public function checkDuplicateEmail($emailId) {
		$vendorInit = new Vendor();
		$emailData = $vendorInit->select('email')
			->where('email', $emailId)->get()->toArray();
		if (!empty($emailData)) {
			return true;
		}
		return false;
	}
	/**
	 * GET: Vendor
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response
	 */
	public function getVendorList($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Vendor', 'error'),
		];
		$page = $request->getQueryParam('page');
		$perpage = $request->getQueryParam('perpage');
		$order = $request->getQueryParam('order');
		$sortBy = $request->getQueryParam('sortby');
		$keyword = $request->getQueryParam('keyword');
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$vendorList = array();
		$logFileUrl = ASSETS_PATH_R . 'vendor/';
		$vendorInit = new Vendor();
		$getVendors = $vendorInit
			->select('xe_id as id', 'company_name', 'contact_name', 'email', 'phone', 'logo', 'country_code', 'state_code', 'zip_code', 'billing_address', 'city')->where('store_id', '=', $storeId);

		// Sorting All records by column name and sord order parameter
		if (isset($sortBy) && $sortBy != "" && isset($order) && $order != "") {
			$sortBy = ($sortBy == 'name') ? 'company_name' : $sortBy;
			$getVendors->orderBy($sortBy, $order);
		}
		//Search by vendor company_name and contact_name
		if (isset($keyword) && $keyword != "") {
			$getVendors->where('company_name', 'LIKE', '%' . $keyword . '%')
				->orWhere('contact_name', 'LIKE', '%' . $keyword . '%');
		}
		$getTotalRecords = $getVendors->count();
		$offset = 0;
		// Pagination Data
		if (isset($page) && $page != "") {
			$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
			$offset = $totalItem * ($page - 1);
			$getVendors->skip($offset)->take($totalItem);
		}
		if ($getTotalRecords > 0) {
			$getVendorData = $getVendors->get()->toArray();
			foreach ($getVendorData as $key => $value) {
				$vendorList[$key]['id'] = $value['id'];
				$vendorList[$key]['company_name'] = $value['company_name'];
				$vendorList[$key]['contact_name'] = $value['contact_name'];
				$vendorList[$key]['email'] = $value['email'];
				$vendorList[$key]['phone'] = $value['phone'];
				$vendorList[$key]['country_code'] = $value['country_code'];
				$vendorList[$key]['state_code'] = $value['state_code'];
				$vendorList[$key]['zip_code'] = $value['zip_code'];
				$vendorList[$key]['billing_address'] = $value['billing_address'];
				$vendorList[$key]['city'] = $value['city'];
				$fileExtension = pathinfo(strtolower($value['logo']), PATHINFO_EXTENSION);
				if ($fileExtension == "svg") {
					$vendorList[$key]['logo'] = $logFileUrl . $value['logo'];
				} else if ($fileExtension == "bmp") {
					$vendorList[$key]['logo'] = $logFileUrl . $value['logo'];
				} else {
					$vendorList[$key]['logo'] = $logFileUrl . "thumb_" . $value['logo'];
				}

			}
			$getVendorsCount = $getVendors->get()->toArray();
			$jsonResponse = [
				'status' => 1,
				'total_records' => $getTotalRecords,
				'records' => count($getVendorsCount),
				'data' => $vendorList,
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'No data found',
				'data' => [],
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST: Update vendor
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function updateVendor($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Vendor', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$companyName = $allPostPutVars['company_name'] ? $allPostPutVars['company_name'] : '';
		$contactName = $allPostPutVars['contact_name'] ? $allPostPutVars['contact_name'] : '';
		$email = $allPostPutVars['email'] ? $allPostPutVars['email'] : '';
		$phone = $allPostPutVars['phone'] ? $allPostPutVars['phone'] : '';
		$countryCode = $allPostPutVars['country_code'] ? $allPostPutVars['country_code'] : '';
		$stateCode = $allPostPutVars['state_code'] ? $allPostPutVars['state_code'] : '';
		$zipCode = $allPostPutVars['zip_code'] ? $allPostPutVars['zip_code'] : '';
		$billingAddress = $allPostPutVars['billing_address'] ? $allPostPutVars['billing_address'] : '';
		$city = $allPostPutVars['city'] ? $allPostPutVars['city'] : '';
		$productCat = $allPostPutVars['product_cat'] ? $allPostPutVars['product_cat'] : '';
		$productCat = json_clean_decode($productCat, true);
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			$vendorInit = new Vendor();
			$updateData = [
				'company_name' => $companyName,
				'contact_name' => $contactName,
				'email' => $email,
				'phone' => $phone,
				'country_code' => $countryCode,
				'state_code' => $stateCode,
				'zip_code' => $zipCode,
				'billing_address' => $billingAddress,
				'city' => $city,
			];
			$allFileNames = do_upload(
				'logo_image', $this->logUploadPath, [200], 'array'
			);
			if (!empty($allFileNames)) {
				$updateData += ['logo' => $allFileNames[0]];
			}
			$status = $vendorInit->where('xe_id', '=', $xeId)->update($updateData);
			if (!empty($productCat)) {
				$vendorCatInit = new VendorCategory();
				$vendorId = $vendorCatInit->whereIn('vendor_id', [$xeId])->count();
				if ($vendorId > 0) {
					$vendorCatInit->where('vendor_id', $xeId)->delete();
				}
				foreach ($productCat as $catIds) {
					$productCatSaveData = [
						'vendor_id' => $xeId,
						'category_id' => $catIds,
					];
					$vendorCatSaveInit = new VendorCategory($productCatSaveData);
					$vendorCatSaveInit->save();
				}
			}
			$jsonResponse = [
				'status' => 1,
				'message' => 'Updated successfully',
			];

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Vendor id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * DELETE: Delete vendor
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   17 June 2020
	 * @return json response wheather data is save or not
	 */
	public function deleteVendor($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Vendor', 'error'),
		];
		$vendorStatus = 0;
		$deleteCount = 0;
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			$vendorIds = json_clean_decode($xeId, true);
			if (!empty($vendorIds)) {
				$type = $request->getQueryParam('type') ? $request->getQueryParam('type') : '';
				if ($type == "single") {
					foreach ($vendorIds as $vendorId) {
						$purchaseOrderInit = new PurchaseOrder();
						$vendor_id = $purchaseOrderInit->whereIn('vendor_id', [$vendorId])->count();
						if ($vendor_id == 0) {
							$vendorInit = new Vendor();
							$vendorIdStatus = $vendorInit->whereIn('xe_id', [$vendorId])->count();
							if ($vendorIdStatus > 0) {
								$status = $vendorInit->where('xe_id', $vendorId)->delete();
								if ($status) {
									$vendorCatInit = new VendorCategory();
									$vendorId = $vendorCatInit->whereIn('vendor_id', [$vendorId])->count();
									if ($vendorId > 0) {
										$vendorCatInit->where('vendor_id', $vendorId)->delete();
									}
								}
							}
							$jsonResponse = [
								'status' => 1,
								'count' => 1,
								'message' => 'Deleted successfully',
							];
						} else {
							$jsonResponse = [
								'status' => 0,
								'count' => 0,
								'message' => 'Vendor has been assigned with purchase order',
							];
						}

					}

				} else {
					foreach ($vendorIds as $vendorId) {
						$purchaseOrderInit = new PurchaseOrder();
						$vendor_id = $purchaseOrderInit->whereIn('vendor_id', [$vendorId])->count();
						if ($vendor_id == 0) {
							$vendorInit = new Vendor();
							$vendorIdStatus = $vendorInit->whereIn('xe_id', [$vendorId])->count();
							if ($vendorIdStatus > 0) {
								$status = $vendorInit->where('xe_id', $vendorId)->delete();
								$deleteCount++;
								if ($status) {
									$vendorCatInit = new VendorCategory();
									$vendorId = $vendorCatInit->whereIn('vendor_id', [$vendorId])->count();
									if ($vendorId > 0) {
										$vendorCatInit->where('vendor_id', $vendorId)->delete();
									}
								}
							}
						}
						$vendorStatus = 1;
					}
					if ($vendorStatus == 1) {
						$jsonResponse = [
							'status' => 1,
							'count' => $deleteCount,
							'message' => 'Deleted successfully',
						];
					} else {
						$jsonResponse = [
							'status' => 0,
							'message' => '',

						];
					}
				}
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Vendor id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Get vendor
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumyas@riaxe.com
	 * @date   18 June 2020
	 * @return json response wheather data is save or not
	 */
	public function getVendorDetails($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Vendor', 'error'),
		];
		$logFileUrl = '';
		$vendorResponse = [];
		if (isset($args) && !empty($args)) {
			$xeId = $args['id'];
			$vendorInit = new Vendor();
			$getvendor = $vendorInit->where(['xe_id' => $xeId]);
			$getCategoryIds = $this->getAssignCategoryToVendor($xeId);
			if ($getvendor->count() > 0) {
				$vendorData = $getvendor->get()->toArray();
				$vendorResponse['xe_id'] = $vendorData[0]['xe_id'];
				$vendorResponse['company_name'] = $vendorData[0]['company_name'];
				$vendorResponse['contact_name'] = $vendorData[0]['contact_name'];
				$vendorResponse['email'] = $vendorData[0]['email'];
				$vendorResponse['phone'] = $vendorData[0]['phone'];
				$vendorResponse['country_code'] = $vendorData[0]['country_code'];
				$vendorResponse['state_code'] = $vendorData[0]['state_code'];
				$vendorResponse['zip_code'] = $vendorData[0]['zip_code'];
				$vendorResponse['billing_address'] = $vendorData[0]['billing_address'];
				$vendorResponse['city'] = $vendorData[0]['city'];
				$vendorResponse['is_live'] = $vendorData[0]['is_live'];
				if ($vendorData[0]['logo']) {
					$logFileUrl = ASSETS_PATH_R . 'vendor/';
					$fileExtension = pathinfo(strtolower($vendorData[0]['logo']), PATHINFO_EXTENSION);
					if ($fileExtension == "svg") {
						$vendorResponse['logo'] = $logFileUrl . $vendorData[0]['logo'];
					} else if ($fileExtension == "bmp") {
						$vendorResponse['logo'] = $logFileUrl . $vendorData[0]['logo'];
					} else {
						$vendorResponse['logo'] = $logFileUrl . "thumb_" . $vendorData[0]['logo'];
					}
				} else {
					$vendorResponse['logo'] = '';
				}
				$vendorResponse['product_cat'] = $getCategoryIds;
				$jsonResponse = [
					'status' => 1,
					'total' => $getvendor->count(),
					'data' => $vendorResponse,

				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'No data found',
				];
			}

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Vendor id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Get assign category to vendor
	 *
	 * @param $vendorId
	 * @author soumyas@riaxe.com
	 * @date   23 September 2020
	 * @return json response wheather data is save or not
	 */
	public function getAssignCategoryToVendor($vendorId) {
		$catArray = [];
		$vendorCatInit = new VendorCategory();
		$getCategoryIds = $vendorCatInit->where(['vendor_id' => $vendorId]);
		$getCategoryIds = $getCategoryIds->get()->toArray();
		foreach ($getCategoryIds as $key => $value) {
			$catArray[] = (int) $value['category_id'];
		}
		return $catArray;
	}
}
