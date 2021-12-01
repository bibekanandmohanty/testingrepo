<?php
/**
 * Manage Vendor
 *
 * PHP version 5.6
 *
 * @category  MultiStore
 * @package   Multi_Store
 * @author    Soumya <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\MultiStore\Controllers;

use App\Modules\MultiStore\Models\Stores;
use App\Modules\Users\Models\UserStoreRel;
use Illuminate\Database\Capsule\Manager as DB;
use MultiStoreStoreSpace\Controllers\StoreMultiStoreController;

/**
 * MultiStore Controller
 *
 * @category MultiStore
 * @package  Multi_Store
 * @author   Soumya <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class MultiStoreController extends StoreMultiStoreController {

	/**
	 * GET: Pending store list
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   09 October 2020
	 * @return json
	 */
	public function getPendingStoreList($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Pending store listh', 'error'),
		];
		$removeChar = ["https://", "http://", "/"];
		$storeResponse = $this->getAllStores();
		$storeIdList = [];
		$pendingStoreList = [];
		if (!empty($storeResponse)) {
			$storesInit = new Stores();
			$getStoreIds = $storesInit->select('xe_id');
			if ($getStoreIds > 0) {
				$storeResponseIds = $getStoreIds->get()->toArray();
				foreach ($storeResponseIds as $key => $value) {
					$storeIdList[$key] = $value['xe_id'];
				}
			}
			foreach ($storeResponse as $key => $value) {
				if (!in_array($value['store_id'], $storeIdList)) {
					$http_referer = str_replace($removeChar, "", $value['store_url']);
					$value['store_url'] = $http_referer;
					$pendingStoreList[$key] = $value;
				}
			}
			$jsonResponse = [
				'status' => 1,
				'data' => array_values($pendingStoreList),
			];

		} else {
			$jsonResponse = [
				'status' => 1,
				'data' => [],
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Available store list
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   09 October 2020
	 * @return json
	 */
	public function getAvailableStoreList($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Available store list', 'error'),
		];
		$removeChar = ["https://", "http://", "/"];
		$storesInit = new Stores();
		$getStores = $storesInit->select('xe_id as store_id', 'store_name', 'store_url', 'created_date', 'status', 'is_active');
		$getTotalRecords = $getStores->count();
		if ($getTotalRecords > 0) {
			$getStoresData = $getStores->get()->toArray();
			foreach ($getStoresData as $key => $value) {
				$http_referer = str_replace($removeChar, "", $value['store_url']);
				$getStoresData[$key]['store_url'] = $http_referer;
			}
			$jsonResponse = [
				'status' => 1,
				'data' => $getStoresData,
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'data' => [],
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Available store list
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   09 October 2020
	 * @return json
	 */
	public function getActiveStoreList($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Active store list', 'error'),
		];

		$removeChar = ["https://", "http://", "/"];
		$agentId = $request->getQueryParam('agent_id') ? $request->getQueryParam('agent_id') : 1;
		$storesInit = new Stores();
		$getStores = $storesInit->select('xe_id as store_id', 'store_name', 'store_url', 'created_date', 'status', 'is_active')->where('is_active', '=', 1);
		$getTotalRecords = $getStores->count();
		if ($getTotalRecords > 0) {
			$getStoresData = [];
			if ($agentId > 1) {
				$agentStoreList = $this->getAgentStoreList($agentId);
				foreach ($getStores->get()->toArray() as $key => $value) {
					if (in_array($value['store_id'], $agentStoreList)) {
						$http_referer = str_replace($removeChar, "", $value['store_url']);
						$value['store_url'] = $http_referer;
						$getStoresData[] = $value;
					}

				}
			} else {
				foreach ($getStores->get()->toArray() as $key => $value) {
					$http_referer = str_replace($removeChar, "", $value['store_url']);
					$value['store_url'] = $http_referer;
					$getStoresData[] = $value;
				}
			}

			$jsonResponse = [
				'status' => 1,
				'data' => $getStoresData,
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'data' => [],
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * POST: Update store status
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   09 October 2020
	 * @return json
	 */
	public function updateStoreStatus($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Update store status', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$storeId = $allPostPutVars['updated_store_id'] ? $allPostPutVars['updated_store_id'] : '';
		$storeStatus = $allPostPutVars['store_status'] ? $allPostPutVars['store_status'] : 0;
		if (!empty($storeId)) {
			$storesInit = new Stores();
			$store_id = $storesInit->whereIn('xe_id', [$storeId])->count();
			if ($store_id > 0) {
				$updateData = [
					'is_active' => $storeStatus,

				];
				$status = $storesInit->where('xe_id', '=', $storeId)->update($updateData);
				$jsonResponse = [
					'status' => 1,
					'message' => 'Updated successfully',
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Store id not found',
				];
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Store id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Get store list
	 *
	 * @param $userId
	 *
	 * @author soumays@riaxe.com
	 * @date   29 May 2020
	 * @return int
	 */
	public function getAgentStoreList($userId) {
		$storeIdList = array();
		$userStoreRelInit = new UserStoreRel();
		$getStoreIds = $userStoreRelInit->select('store_id')->where(['user_id' => $userId]);
		if ($getStoreIds > 0) {
			$getStoreResposne = $getStoreIds->get()->toArray();
			foreach ($getStoreResposne as $key => $value) {
				$storeIdList[$key] = $value['store_id'];
			}
		}
		return $storeIdList;
	}

	/**
	 * GET: Import store
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   03 Dec 2020
	 * @return json
	 */
	public function importStore($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('importStore', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$defaultStoreId = $allPostPutVars['default_store_id'] ? $allPostPutVars['default_store_id'] : 1;
		$currentStoreId = $allPostPutVars['current_store_id'];
		$storeUrl = $allPostPutVars['store_url'];
		$isAssetsCopy = $allPostPutVars['is_assets_copy'] ? $allPostPutVars['is_assets_copy'] : 'false';

		if (!empty($defaultStoreId) && !empty($currentStoreId) && !empty($storeUrl)) {
			$storeName = ucfirst(STORE_NAME);
			$status = 0;
			$settings = 'null';
			$is_active = 1;
			$storeTable = 'stores';
			$storeTableStatus = DB::insert('INSERT INTO ' . $storeTable . ' (`xe_id`,`store_name` ,`store_url`,`status`,`settings` , `is_active`) VALUES (?,?,?,?,?,?)', [$currentStoreId, $storeName, $storeUrl, $status, $settings, $is_active]);

			$modulesList = ['PrintProfiles', 'Backgrounds', 'Cliparts', 'ColorPalettes', 'DecorationAreas', 'DesignStates', 'Fonts', 'Languages', 'Masks', 'PrintAreas', 'Settings', 'Shapes', 'Users', 'GraphicFonts', 'Images', 'UserDesigns', 'AugmentedRealities', 'Quotations', 'Productions', 'Vendors', 'ShipAddress', 'PurchaseOrder', 'Products'];

			$printProfilesIdsArray = [];
			$oldCategoriesIds = [];
			$productionProfilesIdsArray = [];
			$supportedImage = array(
				'gif',
				'jpg',
				'jpeg',
				'png',
				'svg',
				'bmp',
				'ttf',
				'json',
			);

			if ($isAssetsCopy == 'true') {
				$tagsIds = $this->copyDataFromParentTable('tags', $defaultStoreId, $currentStoreId, '');
			}
			foreach ($modulesList as $module) {
				switch ($module) {
				case "PrintProfiles":
					$printProfilePath = ASSETS_PATH_W . 'print_profile/';

					//$printProfilesIds = $this->copyDataFromParentTable('print_profiles', $defaultStoreId, $currentStoreId, '');

					$getPrintProfiles = DB::table("print_profiles")->where('store_id', '=', $defaultStoreId);
					$result = DB::select(DB::raw("SHOW KEYS FROM print_profiles WHERE Key_name = 'PRIMARY' "));
					$columnsList = DB::getSchemaBuilder()->getColumnListing('print_profiles');
					$primaryKey = $result[0]->Column_name;
					if ($primaryKey) {
						if (($key = array_search($primaryKey, $columnsList)) !== false) {
							unset($columnsList[$key]);
						}
					}
					if ($getPrintProfiles->count() > 0) {
						$printProfilesData = $getPrintProfiles->get()->toArray();
						foreach ($printProfilesData as $key => $value) {
							$value = (array) $value;
							$oldPrintProfileId = $value['xe_id']; /* old print profile id */
							if ($value['store_id'] == $defaultStoreId) {
								$value['store_id'] = $currentStoreId;
							}
							$insertSQL = "INSERT INTO print_profiles (" . implode(", ", $columnsList) . ") VALUES (";
							$count = count($columnsList);
							$colList = [];
							foreach ($columnsList as $counter => $col) {
								$colList[] = $value[$col];
								$insertSQL .= "?";
								if ($counter < $count - 0) {$insertSQL .= ", ";}
							}
							$insertSQL .= ")";
							DB::insert($insertSQL, $colList);
							$newPrintProfileId = DB::getPdo()->lastInsertId(); /* new print profile id */
							$printProfilesIdsArray[] = $newPrintProfileId;
							/*  Print Profile Pricings relations  */
							$sql = "SELECT * FROM print_profile_pricings WHERE print_profile_id=" . $oldPrintProfileId;
							$getPrintProfilePricings = DB::select($sql);
							if (!empty($getPrintProfilePricings)) {
								foreach ($getPrintProfilePricings as $priceKey => $priceValue) {
									$priceValue = (array) $priceValue;
									$oldPriceId = $priceValue['xe_id'];
									DB::insert('INSERT INTO print_profile_pricings (`print_profile_id`, `is_white_base`,`white_base_type`,`is_setup_price`,`setup_price` , `setup_type_product`, `setup_type_order`) VALUES (?,?,?,?,?,?,?)', [$newPrintProfileId, $priceValue['is_white_base'], $priceValue['white_base_type'], $priceValue['is_setup_price'], $priceValue['setup_price'], $priceValue['setup_type_product'], $priceValue['setup_type_order']]);
									$newPriceId = DB::getPdo()->lastInsertId();

									$advancedPriceSql = "SELECT distinct a.print_profile_pricing_id, a.advance_price_settings_id, v.xe_id,v.advanced_price_type,v.no_of_colors_allowed,v.is_full_color,v.area_calculation_type,v.min_price FROM price_module_settings a INNER JOIN  price_advanced_price_settings v ON a.advance_price_settings_id = v.xe_id WHERE print_profile_pricing_id=" . $oldPriceId;
									$getAdvancedPrice = DB::select($advancedPriceSql);
									if (!empty($getAdvancedPrice)) {
										$newAdvancedPriceSettingsId = '';
										foreach ($getAdvancedPrice as $advancedPriceKey => $advancedPriceValue) {
											$advancedPriceValue = (array) $advancedPriceValue;
											DB::insert('INSERT INTO price_advanced_price_settings (`advanced_price_type`, `no_of_colors_allowed`,`is_full_color`,`area_calculation_type`,`min_price` ) VALUES (?,?,?,?,?)', [$advancedPriceValue['advanced_price_type'], $advancedPriceValue['no_of_colors_allowed'], $advancedPriceValue['is_full_color'], $advancedPriceValue['area_calculation_type'], $advancedPriceValue['min_price']]);
											$newAdvancedPriceSettingsId = DB::getPdo()->lastInsertId();
										}
										if (!empty($newAdvancedPriceSettingsId)) {
											$priceModuleSql = "SELECT * FROM price_module_settings WHERE print_profile_pricing_id=" . $oldPriceId;
											$getPriceModule = DB::select($priceModuleSql);
											if (!empty($getPriceModule)) {

												foreach ($getPriceModule as $priceModuleKey => $priceModuleValue) {
													$priceModuleValue = (array) $priceModuleValue;
													$oldPriceModuleSettingsId = $priceModuleValue['xe_id'];
													$oldAdvancePriceSettingsId = $priceModuleValue['advance_price_settings_id'];
													DB::insert('INSERT INTO price_module_settings (`print_profile_pricing_id`, `price_module_id`,`module_status`,`is_default_price`,`is_quote_enabled` , `is_advance_price`, `advance_price_settings_id`,`is_quantity_tier`,`quantity_tier_type`) VALUES (?,?,?,?,?,?,?,?,?)', [$newPriceId, $priceModuleValue['price_module_id'], $priceModuleValue['module_status'], $priceModuleValue['is_default_price'], $priceModuleValue['is_quote_enabled'], $priceModuleValue['is_advance_price'], $newAdvancedPriceSettingsId, $priceModuleValue['is_quantity_tier'], $priceModuleValue['quantity_tier_type']]);
													$newPriceModuleSettingsId = DB::getPdo()->lastInsertId();
													/* price default settings */
													$priceDefaultSettingssql = "SELECT p.xe_id, pd.price_module_setting_id,pd.price_key,pd.price_value FROM price_module_settings p INNER JOIN  price_default_settings pd ON p.xe_id =  pd.price_module_setting_id WHERE p.xe_id =" . $oldPriceModuleSettingsId;
													$getpriceDefaultSetting = DB::select($priceDefaultSettingssql);
													if (!empty($getpriceDefaultSetting)) {
														foreach ($getpriceDefaultSetting as $defaultSettingKey => $defaultSettingValue) {
															$defaultSettingValue = (array) $defaultSettingValue;
															$oldDefaultSettingsId = $defaultSettingValue['xe_id'];
															DB::insert('INSERT INTO price_default_settings (`price_module_setting_id`, `price_key`,`price_value`) VALUES (?,?,?)', [$newPriceModuleSettingsId, $defaultSettingValue['price_key'], $defaultSettingValue['price_value']]);
															$newDefaultSettingsId = DB::getPdo()->lastInsertId();
														}
													}

													/*price_tier_values*/
													$priceTierSql = "SELECT pt.xe_id,pt.attribute_type,pt.price_module_setting_id,pt.print_area_index,pt.color_index,pt.print_area_id,pt.range_from,pt.range_to,pt.key_name,pt.screen_cost,pm.xe_id as price_module_settings_id,pm.print_profile_pricing_id FROM price_tier_values pt LEFT JOIN price_module_settings pm ON pt.price_module_setting_id=pm.xe_id WHERE pm.xe_id=" . $oldPriceModuleSettingsId;
													$getTierPrice = DB::select($priceTierSql);
													if (!empty($getTierPrice)) {
														foreach ($getTierPrice as $tierKey => $tierValue) {
															$tierValue = (array) $tierValue;
															$oldTierId = $tierValue['xe_id'];
															DB::insert('INSERT INTO price_tier_values (`attribute_type`, `price_module_setting_id`,`print_area_index`,`color_index`,`print_area_id` , `range_from`, `range_to`,`key_name`,`screen_cost`) VALUES (?,?,?,?,?,?,?,?,?)', [$tierValue['attribute_type'], $newPriceModuleSettingsId, $tierValue['print_area_index'], $tierValue['color_index'], $tierValue['print_area_id'], $tierValue['range_from'], $tierValue['range_to'], $tierValue['key_name'], $tierValue['screen_cost']]);
															$newTierId = DB::getPdo()->lastInsertId();
															/* price_tier_whitebases */
															$tierWhitebasesSql = "SELECT * FROM price_tier_whitebases WHERE price_tier_value_id=" . $oldTierId;
															$getTierWhitebases = DB::select($tierWhitebasesSql);
															if (!empty($getTierWhitebases)) {
																foreach ($getTierWhitebases as $whitebasesKey => $whitebasesValue) {
																	$whitebasesValue = (array) $whitebasesValue;
																	DB::insert('INSERT INTO price_tier_whitebases (`price_tier_value_id`, `tier_range_id`,`white_base_type`,`price`) VALUES (?,?,?,?)', [$newTierId, $whitebasesValue['tier_range_id'], $whitebasesValue['white_base_type'], $whitebasesValue['price']]);
																}
															}
														}
													}

												}
											}
										}
									}

								}
							}
							/* Print Production status relations  */
							$sqlProductionStatus = "SELECT * FROM production_status_print_profile_rel WHERE print_profile_id=" . $oldPrintProfileId;
							$getProductionStatus = DB::select($sqlProductionStatus);
							if(!empty($getProductionStatus)) {
								foreach ($getProductionStatus as $productionKey => $productionValue) {
									$productionValue = (array) $productionValue;
									$productionProfilesIdsArray[$productionValue['print_profile_id']] = $newPrintProfileId;
									
								}
							}
						}
					}

					//$printProfilesIdsArray = $printProfilesIds;
					if (!empty($printProfilesIdsArray)) {
						$this->copyAssetsFiles('print_profiles', $printProfilesIdsArray, 'file_name', $printProfilePath, $currentStoreId, $supportedImage);
					}

					$srcPath = ASSETS_PATH_W . 'settings/stores/' . $defaultStoreId . '/print_profile/';
					$desPath = ASSETS_PATH_W . 'settings/stores/' . $currentStoreId . '/print_profile/';
					if (!file_exists($desPath)) {
						mkdir($desPath, 0777, true);
					}
					$this->recurse_copy($srcPath, $desPath);
					$allFiles = scandir($desPath);
					$files = array_diff(scandir($desPath), array('.', '..'));
					$filesArrayNewIndex = array_values($files);
					foreach ($filesArrayNewIndex as $key => $value) {
						$ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
						rename($desPath . $value, $desPath . $printProfilesIdsArray[$key] . '.' . $ext);
					}
					/** Production status  */
					$moduleIds = [1,4];
					$this->copyProductionStatus($defaultStoreId , $currentStoreId , $moduleIds , $productionProfilesIdsArray );
					/** copy currencies json */
					copy(ASSETS_PATH_W . 'settings/stores/' . $defaultStoreId . '/currencies.json', ASSETS_PATH_W . 'settings/stores/' . $currentStoreId . '/currencies.json');
					/** copy settings json */
					copy(ASSETS_PATH_W . 'settings/stores/' . $defaultStoreId . '/settings.json', ASSETS_PATH_W . 'settings/stores/' . $currentStoreId . '/settings.json');
					/** print profile featuresy rel */
					$this->printProfileFeaturesyRel($printProfilesIdsArray, 'print_profile_feature_rel');

					break;

				case "Backgrounds":
					if ($isAssetsCopy == 'true') {
						$backgroundsPath = ASSETS_PATH_W . 'backgrounds/';
						$type = 1;
						$table = 'categories';
						$parentCatIdArray = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', 0);
						if ($parentCatIdArray->count() > 0) {
							$getPatentCatData = $parentCatIdArray->get()->toArray();
							foreach ($getPatentCatData as $key => $value) {
								$value = (array) $value;
								$parentCatId = $value['xe_id'];
								DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$value['asset_type_id'], $value['parent_id'], $value['name'], $value['sort_order'], $value['is_default'], $currentStoreId]);
								$lastInsertId = DB::getPdo()->lastInsertId();
								$sql = 'SELECT  b_cat.background_id , b_cat.category_id,b.xe_id,b.name,b.value,b.price,b.type,b.store_id  FROM background_category_rel b_cat JOIN backgrounds b on  b_cat.background_id=b.xe_id WHERE b_cat.category_id=' . $parentCatId;
								$getBackgroundsCategoryParentRel = DB::select($sql);
								if (!empty($getBackgroundsCategoryParentRel)) {
									foreach ($getBackgroundsCategoryParentRel as $parentKey => $parentValue) {
										$parentValue = (array) $parentValue;
										DB::insert('INSERT INTO backgrounds (`name`, `value`,`price`,`type`,`store_id`) VALUES (?,?,?,?,?)', [$parentValue['name'], $parentValue['value'], $parentValue['price'], $parentValue['type'], $currentStoreId]);
										$lastInsertIdParent = DB::getPdo()->lastInsertId();
										DB::insert('INSERT INTO background_category_rel (`background_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdParent, $lastInsertId]);
									}
								}
								/** child realtions */
								$categoriesDetails = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', $parentCatId);
								if ($categoriesDetails->get()->count() > 0) {
									$childCategories = $categoriesDetails->get()->toArray();
									foreach ($childCategories as $categoriesKey => $categoriesValue) {
										$chlidCatId = $categoriesValue->xe_id;
										DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`, `is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$categoriesValue->asset_type_id, $lastInsertId, $categoriesValue->name, $categoriesValue->sort_order, $categoriesValue->is_default, $currentStoreId]);
										$childCatlastInsertId = DB::getPdo()->lastInsertId();
										$childSql = 'SELECT  b_cat.background_id , b_cat.category_id,b.xe_id,b.name,b.value,b.price,b.type,b.store_id  FROM background_category_rel b_cat JOIN backgrounds b on  b_cat.background_id=b.xe_id WHERE b_cat.category_id=' . $childCatlastInsertId;
										$getBackgroundsCategoryChildRel = DB::select($childSql);
										if (!empty($getBackgroundsCategoryChildRel)) {
											foreach ($getBackgroundsCategoryChildRel as $chlidKey => $chlidValue) {
												$chlidValue = (array) $chlidValue;
												$sqlChlidBackground = "SELECT * FROM fonts WHERE value='" . $chlidValue['value'] . "' AND store_id=" . $currentStoreId;
												$getChlidBackgroundData = DB::select($sqlChlidBackground);
												if (!empty($getChlidBackgroundData)) {
													foreach ($getChlidBackgroundData as $backgroundKey => $backgroundValue) {
														$backgroundValue = (array) $backgroundValue;
														DB::insert('INSERT INTO background_category_rel (`background_id`, `category_id`) VALUES (?, ?)', [$backgroundValue['xe_id'], $childCatlastInsertId]);
													}
												} else {
													DB::insert('INSERT INTO backgrounds (`name`, `value`,`price`,`type`,`store_id`) VALUES (?,?,?,?,?)', [$chlidValue['name'], $chlidValue['value'], $chlidValue['price'], $chlidValue['type'], $currentStoreId]);
													$lastInsertIdChlid = DB::getPdo()->lastInsertId();
													DB::insert('INSERT INTO background_category_rel (`background_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdChlid, $childCatlastInsertId]);
												}
											}
										}
									}
								}
							}
							/**  Assets  with empty categories */
							$sql = "SELECT b.xe_id,b.name,b.value,b.price,b.type,b.store_id,b_cat.background_id , b_cat.category_id FROM backgrounds b LEFT JOIN background_category_rel b_cat on b.xe_id=b_cat.background_id WHERE store_id= " . $defaultStoreId . " AND b_cat.category_id is null";
							$getNotCategories = DB::select($sql);
							if (!empty($getNotCategories)) {
								foreach ($getNotCategories as $noKey => $noValue) {
									$noValue = (array) $noValue;
									DB::insert('INSERT INTO backgrounds (`name`, `value`,`price`,`type`,`store_id`) VALUES (?,?,?,?,?)', [$noValue['name'], $noValue['value'], $noValue['price'], $noValue['type'], $currentStoreId]);
								}
							}
							$categoriesIds = $this->getCurrentStoreXeId('categories', 'xe_id', $currentStoreId, $type);
							$this->printProfileAssetsCategoryRel($categoriesIds, $printProfilesIdsArray, 'print_profile_assets_category_rel', $type);
						}

					}

					break;
				case "Cliparts":
					if ($isAssetsCopy == 'true') {
						$clipartsPath = ASSETS_PATH_W . 'vectors/';
						$type = 2;
						$table = 'categories';
						$parentCatIdArray = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', 0);
						if ($parentCatIdArray->count() > 0) {
							$getPatentCatData = $parentCatIdArray->get()->toArray();
							foreach ($getPatentCatData as $key => $value) {
								$value = (array) $value;
								$parentCatId = $value['xe_id'];
								DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$value['asset_type_id'], $value['parent_id'], $value['name'], $value['sort_order'], $value['is_default'], $currentStoreId]);
								$lastInsertId = DB::getPdo()->lastInsertId();
								$sql = 'SELECT  c_cat.clipart_id , c_cat.category_id,c.xe_id,c.name,c.price,c.width,c.height,c.file_name,c.is_scaling,c.total_used,c.store_id  FROM clipart_category_rel c_cat JOIN cliparts c on  c_cat.clipart_id=c.xe_id WHERE c_cat.category_id=' . $parentCatId;
								$getClipartCategoryParentRel = DB::select($sql);
								if (!empty($getClipartCategoryParentRel)) {
									foreach ($getClipartCategoryParentRel as $parentKey => $parentValue) {
										$parentValue = (array) $parentValue;
										DB::insert('INSERT INTO cliparts (`name`, `price`,`width`,`height`,`file_name`,`is_scaling`,`total_used`,`store_id`) VALUES (?,?,?,?,?,?,?,?)', [$parentValue['name'], $parentValue['price'], $parentValue['width'], $parentValue['height'], $parentValue['file_name'], $parentValue['is_scaling'], $parentValue['total_used'], $currentStoreId]);
										$lastInsertIdParent = DB::getPdo()->lastInsertId();
										DB::insert('INSERT INTO clipart_category_rel (`clipart_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdParent, $lastInsertId]);
									}
								}
								/** child realtions */
								$categoriesDetails = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', $parentCatId);
								if ($categoriesDetails->get()->count() > 0) {
									$childCategories = $categoriesDetails->get()->toArray();
									foreach ($childCategories as $categoriesKey => $categoriesValue) {
										$chlidCatId = $categoriesValue->xe_id;
										DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$categoriesValue->asset_type_id, $lastInsertId, $categoriesValue->name, $categoriesValue->sort_order, $categoriesValue->is_default, $currentStoreId]);
										$childCatlastInsertId = DB::getPdo()->lastInsertId();
										$childSql = 'SELECT  c_cat.clipart_id , c_cat.category_id,c.xe_id,c.name,c.price,c.width,c.height,c.file_name,c.is_scaling,c.total_used,c.store_id  FROM clipart_category_rel c_cat JOIN cliparts c on  c_cat.clipart_id=c.xe_id WHERE c_cat.category_id=' . $chlidCatId;
										$getClipartCategoryChildRel = DB::select($childSql);
										if (!empty($getClipartCategoryChildRel)) {
											foreach ($getClipartCategoryChildRel as $chlidKey => $chlidValue) {
												$chlidValue = (array) $chlidValue;

												$sqlCliparts = "SELECT * FROM cliparts WHERE file_name='" . $chlidValue['file_name'] . "' AND store_id=" . $currentStoreId;
												$getClipartsData = DB::select($sqlCliparts);
												if (!empty($getClipartsData)) {
													foreach ($getClipartsData as $clipartsKey => $clipartsValue) {
														$clipartsValue = (array) $clipartsValue;
														DB::insert('INSERT INTO clipart_category_rel (`clipart_id`, `category_id`) VALUES (?, ?)', [$clipartsValue['xe_id'], $childCatlastInsertId]);
													}
												} else {
													DB::insert('INSERT INTO cliparts (`name`, `price`,`width`,`height`,`file_name`,`is_scaling`,`total_used`,`store_id`) VALUES (?,?,?,?,?,?,?,?)', [$chlidValue['name'], $chlidValue['price'], $chlidValue['width'], $chlidValue['height'], $chlidValue['file_name'], $chlidValue['is_scaling'], $chlidValue['total_used'], $currentStoreId]);
													$lastInsertIdChlid = DB::getPdo()->lastInsertId();
													DB::insert('INSERT INTO clipart_category_rel (`clipart_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdChlid, $childCatlastInsertId]);
												}
											}
										}
									}
								}
							}
							$sql = "SELECT c.xe_id,c.name,c.price,c.width,c.height,c.file_name,c.is_scaling,c.total_used,c.store_id FROM cliparts c LEFT JOIN clipart_category_rel cat_rel ON c.xe_id = cat_rel.clipart_id WHERE store_id= " . $defaultStoreId . " AND cat_rel.clipart_id IS NULL ";
							$noClipartCategories = DB::select($sql);
							if (!empty($noClipartCategories)) {
								foreach ($noClipartCategories as $noKey => $noValue) {
									$noValue = (array) $noValue;
									DB::insert('INSERT INTO cliparts (`name`, `price`,`width`,`height`,`file_name`,`is_scaling`,`total_used`,`store_id`) VALUES (?,?,?,?,?,?,?,?)', [$noValue['name'], $noValue['price'], $noValue['width'], $noValue['height'], $noValue['file_name'], $noValue['is_scaling'], $noValue['total_used'], $currentStoreId]);
								}
							}
							$clipartsIds = $this->getCurrentStoreXeId('cliparts', 'xe_id', $currentStoreId, '');
							$categoriesIds = $this->getCurrentStoreXeId('categories', 'xe_id', $currentStoreId, $type);
							if (!empty($categoriesIds) && !empty($clipartsIds)) {
								$this->copyAssetsFiles('cliparts', $clipartsIds, 'file_name', $clipartsPath, $currentStoreId, $supportedImage);
								$this->printProfileAssetsCategoryRel($categoriesIds, $printProfilesIdsArray, 'print_profile_assets_category_rel', 2);
							}
						}
					}

					break;
				case "ColorPalettes":
					//if ($isAssetsCopy == 'true') {
					$colorPalettesPath = ASSETS_PATH_W . 'color_palettes/';
					$type = 3;
					$table = 'categories';
					$parentCatIdArray = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', 0);
					if ($parentCatIdArray->count() > 0) {
						$getPatentCatData = $parentCatIdArray->get()->toArray();
						foreach ($getPatentCatData as $key => $value) {
							$value = (array) $value;
							$oldParentCatId = $value['xe_id'];
							DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$value['asset_type_id'], $value['parent_id'], $value['name'], $value['sort_order'], $value['is_default'], $currentStoreId]);
							$newParentCatId = DB::getPdo()->lastInsertId();

							$colorPalettesDataArray = DB::table('color_palettes')->where('store_id', '=', $defaultStoreId)->where('category_id', '=', $oldParentCatId);
							if ($colorPalettesDataArray->count() > 0) {
								$colorPalettesData = $colorPalettesDataArray->get()->toArray();
								foreach ($colorPalettesData as $key => $value) {
									$value = (array) $value;
									DB::insert('INSERT INTO color_palettes (`category_id`, `subcategory_id` , `name`, `price`,`value`,`hex_value`,`store_id`) VALUES (?,?,?,?,?,?,?)', [$newParentCatId, $value['subcategory_id'], $value['name'], $value['price'], $value['value'], $value['hex_value'], $currentStoreId]);
									$newcolorPalettesId = DB::getPdo()->lastInsertId();
									$sql = "SELECT category_id FROM color_palettes WHERE xe_id=" . $newcolorPalettesId;

									$getCategoryIdsArray = DB::select($sql);
									if (!empty($getCategoryIdsArray)) {
										$newCategoryIdArray = (array) $getCategoryIdsArray[0];
										$newCategoryId = $newCategoryIdArray['category_id'];

									}
									foreach ($printProfilesIdsArray as $printProfilesId) {
										DB::insert('INSERT INTO print_profile_assets_category_rel (`print_profile_id`, `asset_type_id` , `category_id`) VALUES (?, ? ,? )', [$printProfilesId, $type, $newCategoryId]);
									}
								}
							}
							$chlidCatIdArray = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', $oldParentCatId);
							if ($chlidCatIdArray->count() > 0) {
								$getChlidCatData = $chlidCatIdArray->get()->toArray();
								foreach ($getChlidCatData as $chlidKey => $chlidValue) {
									$chlidValue = (array) $chlidValue;
									$oldChlidCatId = $chlidValue['xe_id'];
									DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$chlidValue['asset_type_id'], $newParentCatId, $chlidValue['name'], $chlidValue['sort_order'], $chlidValue['is_default'], $currentStoreId]);
									$newParentChlidCatId = DB::getPdo()->lastInsertId();
									DB::table('color_palettes')->where('subcategory_id', $oldChlidCatId)->where('store_id', $currentStoreId)->where('category_id', $newCategoryId)->update(['subcategory_id' => $newParentChlidCatId]);
									foreach ($printProfilesIdsArray as $printProfilesId) {
										DB::insert('INSERT INTO print_profile_assets_category_rel (`print_profile_id`, `asset_type_id` , `category_id`) VALUES (?, ? ,? )', [$printProfilesId, $type, $newParentChlidCatId]);
									}
								}
							}
						}
					}
					//}

					break;
				case "DesignStates":
					if ($isAssetsCopy == 'true') {
						$backgroundsIds = $this->copyDataFromParentTable('design_states', $defaultStoreId, $currentStoreId, '');
					}

					break;
				case "Fonts":
					if ($isAssetsCopy == 'true') {
						$oldFontCatId = [];
						$newFontCatId = [];
						$fontsPath = ASSETS_PATH_W . 'fonts/';
						$type = 6;
						$table = 'categories';
						$parentCatIdArray = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', 0);
						if ($parentCatIdArray->count() > 0) {
							$getPatentCatData = $parentCatIdArray->get()->toArray();
							foreach ($getPatentCatData as $key => $value) {
								$value = (array) $value;
								$parentCatId = $value['xe_id'];
								$oldFontCatId[] = $parentCatId;
								DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$value['asset_type_id'], $value['parent_id'], $value['name'], $value['sort_order'], $value['is_default'], $currentStoreId]);
								$lastInsertId = DB::getPdo()->lastInsertId();
								$newFontCatId[] = $lastInsertId;

							}

							$fontsCategoryId = [];
							$sql = "SELECT  f_cat.font_id , f_cat.category_id,f.xe_id,f.name,f.price,f.font_family,f.file_name,f.total_used,f.store_id  FROM font_category_rel f_cat JOIN fonts f on  f_cat.font_id=f.xe_id WHERE f_cat.category_id IN (" . implode(',', $oldFontCatId) . ")";

							$getFontCategoryParentRel = DB::select($sql);
							if (!empty($getFontCategoryParentRel)) {
								foreach ($getFontCategoryParentRel as $parentKey => $parentValue) {
									$parentValue = (array) $parentValue;
									$fontId = $parentValue['xe_id'];
									if (!in_array($fontId, $fontsCategoryId)) {
										array_push($fontsCategoryId, $fontId);
										DB::insert('INSERT INTO fonts (`name`, `price`,`font_family`,`file_name`,`total_used`,`store_id`) VALUES (?,?,?,?,?,?)', [$parentValue['name'], $parentValue['price'], $parentValue['font_family'], $parentValue['file_name'], $parentValue['total_used'], $currentStoreId]);
										$lastInsertIdParent = DB::getPdo()->lastInsertId();
										DB::insert('INSERT INTO font_category_rel (`font_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdParent, $lastInsertId]);
									}

								}
							}
							/** child realtions */
							foreach ($oldFontCatId as $parentCatId) {
								$categoriesDetails = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', $parentCatId);
								if ($categoriesDetails->get()->count() > 0) {
									$childCategories = $categoriesDetails->get()->toArray();
									foreach ($childCategories as $categoriesKey => $categoriesValue) {
										$chlidCatId = $categoriesValue->xe_id;
										DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$categoriesValue->asset_type_id, $lastInsertId, $categoriesValue->name, $categoriesValue->sort_order, $categoriesValue->is_default, $currentStoreId]);
										$childCatlastInsertId = DB::getPdo()->lastInsertId();

										$childSql = 'SELECT  f_cat.font_id , f_cat.category_id,f.xe_id,f.name,f.price,f.font_family,f.file_name,f.total_used,f.store_id  FROM font_category_rel f_cat JOIN fonts f on  f_cat.font_id=f.xe_id WHERE f_cat.category_id=' . $chlidCatId;

										$getClipartCategoryChildRel = DB::select($childSql);
										if (!empty($getClipartCategoryChildRel)) {
											foreach ($getClipartCategoryChildRel as $chlidKey => $chlidValue) {
												$chlidValue = (array) $chlidValue;
												$sqlChlidFont = "SELECT * FROM fonts WHERE file_name='" . $chlidValue['file_name'] . "' AND store_id=" . $currentStoreId;
												$getChlidFontData = DB::select($sqlChlidFont);
												if (!empty($getChlidFontData)) {
													foreach ($getChlidFontData as $fontKey => $fontValue) {
														$fontValue = (array) $fontValue;
														DB::insert('INSERT INTO font_category_rel (`font_id`, `category_id`) VALUES (?, ?)', [$fontValue['xe_id'], $childCatlastInsertId]);
													}
												} else {

													DB::insert('INSERT INTO fonts (`name`, `price`,`font_family`,`file_name`,`total_used`,`store_id`) VALUES (?,?,?,?,?,?)', [$chlidValue['name'], $chlidValue['price'], $chlidValue['font_family'], $chlidValue['file_name'], $chlidValue['total_used'], $currentStoreId]);
													$lastInsertIdChlid = DB::getPdo()->lastInsertId();
													DB::insert('INSERT INTO font_category_rel (`font_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdChlid, $childCatlastInsertId]);

												}
											}
										}

									}
								}
							}

							/**  Assets  with empty categories */

							$sql = 'SELECT  f_cat.font_id , f_cat.category_id,f.xe_id,f.name,f.price,f.font_family,f.file_name,f.total_used,f.store_id  FROM font_category_rel f_cat JOIN fonts f on  f_cat.font_id=f.xe_id WHERE f_cat.category_id is null ';
							$noFontCategories = DB::select($sql);
							if (!empty($noFontCategories)) {
								foreach ($noFontCategories as $noKey => $noValue) {
									$noValue = (array) $noValue;
									DB::insert('INSERT INTO fonts (`name`, `price`,`font_family`,`file_name`,`total_used`,`store_id`) VALUES (?,?,?,?,?,?)', [$noValue['name'], $noValue['price'], $noValue['font_family'], $noValue['file_name'], $noValue['total_used'], $currentStoreId]);
								}
							}

							$fontsIds = $this->getCurrentStoreXeId('fonts', 'xe_id', $currentStoreId, '');
							$categoriesIds = $this->getCurrentStoreXeId('categories', 'xe_id', $currentStoreId, $type);
							if (!empty($categoriesIds) && !empty($fontsIds)) {
								$this->copyAssetsFiles('fonts', $fontsIds, 'file_name', $fontsPath, $currentStoreId, $supportedImage);
								$this->printProfileAssetsCategoryRel($categoriesIds, $printProfilesIdsArray, 'print_profile_assets_category_rel', $type);
							}
						}
					}

					break;
				case "Languages":
					$languagesAdminFilePath = ASSETS_PATH_W . 'languages/admin/';
					$languagesToolFilePath = ASSETS_PATH_W . 'languages/tool/';
					$backgroundsIds = $this->copyDataFromParentTable('languages', $defaultStoreId, $currentStoreId, '');
					/** admin  */
					$this->copyAssetsFiles('languages', $backgroundsIds, 'flag', $languagesAdminFilePath, $currentStoreId, $supportedImage);
					$this->copyAssetsFiles('languages', $backgroundsIds, 'file_name', $languagesAdminFilePath, $currentStoreId, $supportedImage);
					/** tool */
					$adminLanguages = DB::select("SELECT * FROM languages WHERE store_id=" . $defaultStoreId . " AND type='tool'");
					foreach ($adminLanguages as $key => $value) {
						$value = (array) $value;
						$file_name = $value['file_name'];
						$jsonExt = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
						if (in_array($jsonExt, $supportedImage)) {
							copy($languagesToolFilePath . $file_name, $languagesToolFilePath . $currentStoreId . '_' . $file_name);
						}
						if (in_array(strtolower(pathinfo($value['flag'], PATHINFO_EXTENSION)), $supportedImage)) {
							copy($languagesToolFilePath . $value['flag'], $languagesToolFilePath . $currentStoreId . '_' . $value['flag']);
						}
					}
					$newPrintProfilesArray = [];
					$result = DB::select("SELECT * FROM print_profiles WHERE store_id=" . $currentStoreId);
					if (!empty($result)) {
						foreach ($result as $key => $value) {
							$value = (array) $value;
							$newPrintProfilesArray[$key] = $value['xe_id'];
						}
					}
					if (!empty($newPrintProfilesArray)) {
						$this->updateLanguageFile(ASSETS_PATH_W . 'languages/tool', '.json', $currentStoreId, $newPrintProfilesArray);
						$this->updateLanguageFile(ASSETS_PATH_W . 'languages/admin', '.json', $currentStoreId, $newPrintProfilesArray);
					}
					break;
				case "Masks":
					if ($isAssetsCopy == 'true') {
						$masksFilePath = ASSETS_PATH_W . 'masks/';
						$categoriesIds = $this->copyDataFromParentTable('categories', $defaultStoreId, $currentStoreId, 8);
						$masksIds = $this->copyDataFromParentTable('masks', $defaultStoreId, $currentStoreId, '');
						$this->copyAssetsFiles('masks', $masksIds, 'mask_name', $masksFilePath, $currentStoreId, $supportedImage);
						$this->copyAssetsFiles('masks', $masksIds, 'file_name', $masksFilePath, $currentStoreId, $supportedImage);
						$this->printProfileAssetsCategoryRel($categoriesIds, $printProfilesIdsArray, 'print_profile_assets_category_rel', 8);
					}

					break;
				case "PrintAreas":
					$printAreaTypesIds = $this->copyDataFromParentTable('print_area_types', $defaultStoreId, $currentStoreId, '');
					$printAreaTypesFilePath = ASSETS_PATH_W . 'print_area_types/';
					$this->copyAssetsFiles('print_area_types', $printAreaTypesIds, 'file_name', $printAreaTypesFilePath, $currentStoreId, $supportedImage);
					if (!empty($printAreaTypesIds)) {
						$getFirstPrintArrayId = $printAreaTypesIds[0];
						$printAreasIds = $this->copyDataFromParentTable('print_areas', $defaultStoreId, $currentStoreId, '');
						/** update print_area_type_id in print_areas table */
						//$updatePrintAreas = DB::table('print_areas')->where('store_id', '=', $currentStoreId)->update(['print_area_type_id' => $getFirstPrintArrayId]);
					}
					break;
				case "Settings":
					$settingsIds = $this->copyDataFromParentTable('settings', $defaultStoreId, $currentStoreId, '');
					$appUnits = $this->copyDataFromParentTable('app_units', $defaultStoreId, $currentStoreId, '');
					$dynamicFormIds = $this->copyDataFromParentTable('quote_dynamic_form_values', $defaultStoreId, $currentStoreId, '');
					$isDir = ASSETS_PATH_W . 'settings/order_setting/' . $defaultStoreId;
					if (is_dir($isDir)) {
						if (!file_exists(ASSETS_PATH_W . 'settings/order_setting/' . $currentStoreId)) {
							mkdir(ASSETS_PATH_W . 'settings/order_setting/' . $currentStoreId, 0777, true);
						}
						$this->recurse_copy(ASSETS_PATH_W . 'settings/order_setting/' . $defaultStoreId . '/', ASSETS_PATH_W . 'settings/order_setting/' . $currentStoreId . '/');
					}
					break;
				case "Shapes":
					if ($isAssetsCopy == 'true') {
						$shapesFilePath = ASSETS_PATH_W . 'shapes/';
						$type = 9;
						$table = 'categories';
						$parentCatIdArray = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', 0);
						if ($parentCatIdArray->count() > 0) {
							$getPatentCatData = $parentCatIdArray->get()->toArray();
							foreach ($getPatentCatData as $key => $value) {
								$value = (array) $value;
								$parentCatId = $value['xe_id'];
								DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$value['asset_type_id'], $value['parent_id'], $value['name'], $value['sort_order'], $value['is_default'], $currentStoreId]);
								$lastInsertId = DB::getPdo()->lastInsertId();
								$sql = 'SELECT  s_cat.shape_id ,s_cat.category_id,s.xe_id,s.name,s.file_name,s.store_id  FROM shape_category_rel s_cat JOIN shapes s on  s_cat.shape_id=s.xe_id WHERE s_cat.category_id=' . $parentCatId;
								$getShapCategoryParentRel = DB::select($sql);
								if (!empty($getShapCategoryParentRel)) {
									foreach ($getShapCategoryParentRel as $parentKey => $parentValue) {
										$parentValue = (array) $parentValue;
										DB::insert('INSERT INTO shapes (`name`, `file_name`,`store_id`) VALUES (?,?,?)', [$parentValue['name'], $parentValue['file_name'], $currentStoreId]);
										$lastInsertIdParent = DB::getPdo()->lastInsertId();
										DB::insert('INSERT INTO shape_category_rel (`shape_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdParent, $lastInsertId]);
									}
								}
								/** child realtions */
								$categoriesDetails = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', $parentCatId);
								if ($categoriesDetails->get()->count() > 0) {
									$childCategories = $categoriesDetails->get()->toArray();
									foreach ($childCategories as $categoriesKey => $categoriesValue) {
										$chlidCatId = $categoriesValue->xe_id;
										DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`is_default`,`store_id`) VALUES (?,?,?,?,?,?)', [$categoriesValue->asset_type_id, $lastInsertId, $categoriesValue->name, $categoriesValue->sort_order, $categoriesValue->is_default, $currentStoreId]);
										$childCatlastInsertId = DB::getPdo()->lastInsertId();
										$childSql = 'SELECT  s_cat.shape_id ,s_cat.category_id,s.xe_id,s.name,s.file_name,s.store_id  FROM shape_category_rel s_cat JOIN shapes s on  s_cat.shape_id=s.xe_id WHERE s_cat.category_id=' . $chlidCatId;
										$getShapCategoryChildRel = DB::select($childSql);
										if (!empty($getShapCategoryChildRel)) {
											foreach ($getShapCategoryChildRel as $chlidKey => $chlidValue) {
												$chlidValue = (array) $chlidValue;
												$sqlChlidShape = "SELECT * FROM shapes WHERE file_name='" . $chlidValue['file_name'] . "' AND store_id=" . $currentStoreId;
												$getChlidShapeData = DB::select($sqlChlidShape);
												if (!empty($getChlidShapeData)) {
													foreach ($getChlidShapeData as $shapeKey => $shapeValue) {
														$shapeValue = (array) $shapeValue;
														DB::insert('INSERT INTO shape_category_rel (`shape_id`, `category_id`) VALUES (?, ?)', [$shapeValue['xe_id'], $childCatlastInsertId]);
													}
												} else {
													DB::insert('INSERT INTO shapes (`name`, `file_name`,`store_id`) VALUES (?,?,?)', [$chlidValue['name'], $chlidValue['file_name'], $currentStoreId]);
													$lastInsertIdParent = DB::getPdo()->lastInsertId();
													DB::insert('INSERT INTO shape_category_rel (`shape_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdParent, $childCatlastInsertId]);
												}
											}
										}
									}
								}
							}

							/**  Assets  with empty categories */
							$sql = "SELECT s.xe_id,s.name,s.file_name,s.store_id,s_cat.shape_id , s_cat.category_id FROM shapes s LEFT JOIN shape_category_rel s_cat on s.xe_id=s_cat.shape_id WHERE store_id= 1 AND s_cat.category_id is null";
							$noShapeCategories = DB::select($sql);
							if (!empty($noShapeCategories)) {
								foreach ($noShapeCategories as $noKey => $noValue) {
									$noValue = (array) $noValue;
									DB::insert('INSERT INTO shapes (`name`, `file_name`,`store_id`) VALUES (?,?,?)', [$noValue['name'], $noValue['file_name'], $currentStoreId]);
								}
							}

							$shapesIds = $this->getCurrentStoreXeId('shapes', 'xe_id', $currentStoreId, '');
							$categoriesIds = $this->getCurrentStoreXeId('categories', 'xe_id', $currentStoreId, $type);
							if (!empty($shapesIds) && !empty($categoriesIds)) {
								$this->copyAssetsFiles('shapes', $shapesIds, 'file_name', $shapesFilePath, $currentStoreId, $supportedImage);
								$this->printProfileAssetsCategoryRel($categoriesIds, $printProfilesIdsArray, 'print_profile_assets_category_rel', $type);
							}
						}
					}
					break;
				case "Templates":
					/*
						$categoriesIds = $this->copyDataFromParentTable('categories', $defaultStoreId, $currentStoreId, 11);
						$templatesIds = $this->copyDataFromParentTable('templates', $defaultStoreId, $currentStoreId, '');
						if (!empty($templatesIds) && !empty($categoriesIds)) {
							$this->savetemplateCategoryRel('template_category_rel', $templatesIds, $categoriesIds);
						}
					*/
					$type = 11;
					$table = 'categories';
					$parentCatIdArray = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', 0);
					if ($parentCatIdArray->count() > 0) {
						$getPatentCatData = $parentCatIdArray->get()->toArray();
						foreach ($getPatentCatData as $key => $value) {
							$value = (array) $value;
							$parentCatId = $value['xe_id'];
							DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`store_id`) VALUES (?,?,?,?,?)', [$value['asset_type_id'], $value['parent_id'], $value['name'], $value['sort_order'], $currentStoreId]);
							$lastInsertId = DB::getPdo()->lastInsertId();
							$sql = 'SELECT t_cat.template_id , t_cat.category_id,t.xe_id,t.ref_id,t.name,t.description,t.no_of_colors,t.color_hash_codes,t.template_index,t.is_easy_edit,t.total_used,t.store_id  FROM template_category_rel t_cat JOIN templates t on  t_cat.template_id=t.xe_id WHERE t_cat.category_id=' . $parentCatId;
							$getTemplateCategoryParentRel = DB::select($sql);
							if (!empty($getTemplateCategoryParentRel)) {
								foreach ($getTemplateCategoryParentRel as $parentKey => $parentValue) {
									$parentValue = (array) $parentValue;
									DB::insert('INSERT INTO templates (`ref_id`, `name`,`description`,`no_of_colors`,`color_hash_codes`,`template_index`,`is_easy_edit`,`total_used` , `store_id`) VALUES (?,?,?,?,?,?,?,?,?)', [$parentValue['ref_id'], $parentValue['name'], $parentValue['description'], $parentValue['no_of_colors'], $parentValue['color_hash_codes'], $parentValue['template_index'], $parentValue['is_easy_edit'], $parentValue['total_used'], $currentStoreId]);
									$lastInsertIdParent = DB::getPdo()->lastInsertId();
									DB::insert('INSERT INTO template_category_rel (`template_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdParent, $lastInsertId]);

								}
							}
							/** child realtions */
							$categoriesDetails = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', $parentCatId);
							if ($categoriesDetails->get()->count() > 0) {
								$childCategories = $categoriesDetails->get()->toArray();
								foreach ($childCategories as $categoriesKey => $categoriesValue) {
									$chlidCatId = $categoriesValue->xe_id;
									DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`store_id`) VALUES (?,?,?,?,?)', [$categoriesValue->asset_type_id, $lastInsertId, $categoriesValue->name, $categoriesValue->sort_order, $currentStoreId]);
									$childCatlastInsertId = DB::getPdo()->lastInsertId();
									$childSql = 'SELECT t_cat.template_id , t_cat.category_id,t.xe_id,t.ref_id,t.name,t.description,t.no_of_colors,t.color_hash_codes,t.template_index,t.is_easy_edit,t.total_used,t.store_id  FROM template_category_rel t_cat JOIN templates t on  t_cat.template_id=t.xe_id WHERE t_cat.category_id=' . $chlidCatId;
									$getTemplateCategoryChildRel = DB::select($childSql);
									if (!empty($getTemplateCategoryChildRel)) {
										foreach ($getTemplateCategoryChildRel as $chlidKey => $chlidValue) {
											$chlidValue = (array) $chlidValue;
											DB::insert('INSERT INTO templates (`ref_id`, `name`,`description`,`no_of_colors`,`color_hash_codes`,`template_index`,`is_easy_edit`,`total_used` , `store_id`) VALUES (?,?,?,?,?,?,?,?,?)', [$chlidValue['ref_id'], $chlidValue['name'], $chlidValue['description'], $chlidValue['no_of_colors'], $chlidValue['color_hash_codes'], $chlidValue['template_index'], $chlidValue['is_easy_edit'], $chlidValue['total_used'], $currentStoreId]);
											$lastInsertIdParent = DB::getPdo()->lastInsertId();
											DB::insert('INSERT INTO template_category_rel (`template_id`, `category_id`) VALUES (?, ?)', [$lastInsertIdParent, $childCatlastInsertId]);
										}
									}
								}
							}
						}
					}
					break;
				case "Users":
					//$userPrivilegesIds = $this->copyDataFromParentTable('user_privileges', $defaultStoreId, $currentStoreId, '');
					$table = 'user_privileges';
					$privilegesSubModuleTable = 'privileges_sub_modules';
					$parentUserPrivilegesList = DB::table($table)->where('store_id', '=', $defaultStoreId);
					if ($parentUserPrivilegesList->count() > 0) {
						$getparentUserPrivilegesListData = $parentUserPrivilegesList->get()->toArray();
						foreach ($getparentUserPrivilegesListData as $key => $value){
							$value = (array) $value;
							$parentUserPrivilegesId = $value['xe_id'];
							DB::insert('INSERT INTO ' . $table . ' (`module_name`, `store_id`,`status`) VALUES (?,?,?)', [$value['module_name'], $currentStoreId, $value['status'],$currentStoreId]);
							$lastInsertId = DB::getPdo()->lastInsertId();
							/*get data from user_privileges*/
							$sql = "SELECT * FROM " . $privilegesSubModuleTable. " WHERE user_privilege_id=".$parentUserPrivilegesId;
							$getprivileges = DB::select($sql);
							if (!empty($getprivileges)) {
								foreach ($getprivileges as $parentKey => $parentValue){
									$parentValue = (array) $parentValue;
									DB::insert('INSERT INTO '.$privilegesSubModuleTable.' (`user_privilege_id`, `type`,`slug`,`comments`,`is_default`) VALUES (?,?,?,?,?)', [$lastInsertId, $parentValue['type'], $parentValue['slug'], $parentValue['comments'], $parentValue['is_default']]);
								}
							}
						}
					}
					break;
				case "Productions":
					$productionHubSettingsIds = $this->copyDataFromParentTable('production_hub_settings', $defaultStoreId, $currentStoreId, '');
					//$productionStatusIds = $this->copyDataFromParentTable('production_status', $defaultStoreId, $currentStoreId, '');
					$productionTagsIds = $this->copyDataFromParentTable('production_tags', $defaultStoreId, $currentStoreId, '');
					$productionEmailTemplatesIds = $this->copyDataFromParentTable('production_email_templates', $defaultStoreId, $currentStoreId, '');
					$quotePaymentMethodsIds = $this->copyDataFromParentTable('quote_payment_methods', $defaultStoreId, $currentStoreId, '');
					$purchaseOrderStatus = $this->copyDataFromParentTable('purchase_order_status', $defaultStoreId, $currentStoreId, '');
					break;
				case "GraphicFonts":
					if ($isAssetsCopy == 'true') {
						$graphicFontsFilePath = ASSETS_PATH_W . 'graphics/';
						$graphicFontsDetails = DB::table("graphic_fonts")->where('store_id', '=', $defaultStoreId);
						if ($graphicFontsDetails->count() > 0) {
							$graphicFontsData = $graphicFontsDetails->get()->toArray();
							foreach ($graphicFontsData as $key => $value) {
								$value = (array) $value;
								$oldGraphicFontsId = $value['xe_id'];
								DB::insert('INSERT INTO graphic_fonts (`name`, `price`,`is_letter_style`,`is_number_style`,`is_special_character_style`,`store_id`) VALUES (?,?,?,?,?,?)', [$value['name'], $value['price'], $value['is_letter_style'], $value['is_number_style'], $value['is_special_character_style'], $currentStoreId]);
								$newGraphicFontsId = DB::getPdo()->lastInsertId();
								$graphicFontLettersDetails = DB::table("graphic_font_letters")->where('graphic_font_id', '=', $oldGraphicFontsId);
								if ($graphicFontLettersDetails->count() > 0) {
									$graphicFontLettersData = $graphicFontLettersDetails->get()->toArray();
									foreach ($graphicFontLettersData as $letterKey => $letterValue) {
										$letterValue = (array) $letterValue;
										$old_file_name = $letterValue['file_name'];
										DB::insert('INSERT INTO graphic_font_letters (`graphic_font_id`, `name`,`file_name`,`font_type`) VALUES (?,?,?,?)', [$newGraphicFontsId, $letterValue['name'], $currentStoreId . '_' . $letterValue['file_name'], $letterValue['font_type']]);
										$ext = strtolower(pathinfo($old_file_name, PATHINFO_EXTENSION));
										if (in_array($ext, $supportedImage)) {
											copy($graphicFontsFilePath . $old_file_name, $graphicFontsFilePath . $currentStoreId . '_' . $letterValue['file_name']);
										}
									}
								}
							}
						}
					}
					break;
				case "Products":
					$productsFilePath = ASSETS_PATH_W . 'products/';
					$productImageIds = [];
					$productImagesInfo = DB::table('product_images')->where('store_id', '=', $defaultStoreId);
					if ($productImagesInfo->count() > 0) {
						$productImages = $productImagesInfo->get()->toArray();
						foreach ($productImages as $key => $value) {
							$productImageIds[] = $value->xe_id;
						}
					}
					if (!empty($productImageIds)) {
						$imageSideDataArray = [];
						foreach ($productImageIds as $productImageId) {
							$databaseStoreInfo = DB::table('product_image_sides')->where('product_image_id', '=', $productImageId);
							if ($databaseStoreInfo->count() > 0) {
								$imageData = $databaseStoreInfo->get()->toArray();
								foreach ($imageData as $key => $value) {
									$imageSideDataArray[$key]['side_name'] = $value->side_name;
									$imageSideDataArray[$key]['sort_order'] = $value->sort_order;
									$imageSideDataArray[$key]['file_name'] = $value->file_name;
								}

							}
						}

					}
					$productImagesIds = $this->copyDataFromParentTable('product_images', $defaultStoreId, $currentStoreId, '');
					if (!empty($productImagesIds) && !empty($imageSideDataArray)) {
						foreach ($productImagesIds as $productImagesId) {
							foreach ($imageSideDataArray as $key => $value) {
								$side_name = $value['side_name'];
								$sort_order = $value['sort_order'];
								$old_file_name = $value['file_name'];
								$new_file_name = $currentStoreId . '_' . $value['file_name'];
								$ext = strtolower(pathinfo($old_file_name, PATHINFO_EXTENSION));
								if (in_array($ext, $supportedImage)) {
									copy($productsFilePath . $old_file_name, $productsFilePath . $new_file_name);
								}
								/** thum image */
								$thumbOldImage = $productsFilePath . "thumb_" . $value['file_name'];
								if (file_exists($thumbOldImage)) {
									$thumbNewImage = $productsFilePath . "thumb_" . $currentStoreId . "_" . $value['file_name'];
									copy($thumbOldImage, $thumbNewImage);
								}
								DB::insert('INSERT INTO `product_image_sides` (`product_image_id`, `side_name` , `sort_order` , `file_name`) VALUES (?, ?,?,?)', [$productImagesId, $side_name, $sort_order, $new_file_name]);
							}
						}

					}

					break;

				}
			}
			$jsonResponse = [
				'status' => 1,
				'message' => 'New store successfully',
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => message('importStore', 'error'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Copy Parent table data
	 *
	 * @param $table name
	 * @param $defaultStoreId
	 * @param $currentStoreId
	 * * @param $type
	 *
	 * @author soumya@riaxe.com
	 * @date   03 Dec 2020
	 * @return Array
	 */
	public function copyDataFromParentTable($table, $defaultStoreId, $currentStoreId, $type = null) {
		$returnIds = [];
		if ($table == 'categories') {
			$databaseStoreInfo = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', 0);
		} else {
			$databaseStoreInfo = DB::table($table)->where('store_id', '=', $defaultStoreId);
		}
		$result = DB::select(DB::raw("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY' "));
		$columnsList = DB::getSchemaBuilder()->getColumnListing($table);
		$primaryKey = $result[0]->Column_name;
		if ($primaryKey) {
			if (($key = array_search($primaryKey, $columnsList)) !== false) {
				unset($columnsList[$key]);
			}
		}
		if ($databaseStoreInfo->count() > 0) {
			$insertDataArray = [];
			$insertData = [];
			$storeData = $databaseStoreInfo->get()->toArray();
			foreach ($storeData as $key => $value) {
				$value = (array) $value;

				if ($value['store_id'] == $defaultStoreId) {
					$value['store_id'] = $currentStoreId;
				}
				$insertSQL = "INSERT INTO " . $table . " (" . implode(", ", $columnsList) . ") VALUES (";
				$count = count($columnsList);
				$colList = [];
				foreach ($columnsList as $counter => $col) {
					$colList[] = $value[$col];
					$insertSQL .= "?";
					if ($counter < $count - 0) {$insertSQL .= ", ";}
				}
				$insertSQL .= ")";
				DB::insert($insertSQL, $colList);
				$lastInsertId = DB::getPdo()->lastInsertId();
				if ($table == 'categories') {
					$categoriesArray = [];
					$categoriesDetails = DB::table($table)->where('store_id', '=', $defaultStoreId)->where('asset_type_id', '=', $type)->where('parent_id', '=', $value['xe_id']);
					if ($categoriesDetails->get()->count() > 0) {
						$childCategories = $categoriesDetails->get()->toArray();
						foreach ($childCategories as $categoriesKey => $categoriesValue) {
							if (!in_array($lastInsertId, $categoriesArray)) {
								DB::insert('INSERT INTO ' . $table . ' (`asset_type_id`, `parent_id`,`name`,`sort_order`,`store_id`) VALUES (?,?,?,?,?)', [$type, $lastInsertId, $categoriesValue->name, $categoriesValue->sort_order, $currentStoreId]);
							}
						}
					}
				}
				$returnIds[] = $lastInsertId;
			}
		}
		return $returnIds;
	}
	/**
	 * Copy all assets files
	 *
	 * @param $table name
	 * @param $asetsIds
	 * @param $ColumnName
	 * @param $asetsPath
	 * @param $currentStoreId
	 * @param $supportedImage
	 *
	 * @author soumya@riaxe.com
	 * @date   03 Dec 2020
	 * @return Array
	 */
	public function copyAssetsFiles($tableName, $asetsIds, $ColumnName, $asetsPath, $currentStoreId, $supportedImage) {
		foreach ($asetsIds as $asetsId) {
			$databaseStoreInfo = DB::table($tableName)->where('xe_id', '=', $asetsId);
			if ($databaseStoreInfo->count() > 0) {
				$assetsDataArray = [];
				$storeData = $databaseStoreInfo->get()->toArray();
				foreach ($storeData as $key => $value) {
					$assetsDataArray[] = (array) $value;
				}
				foreach ($assetsDataArray as $assetsKey => $assetsValue) {
					$ext = strtolower(pathinfo($assetsValue[$ColumnName], PATHINFO_EXTENSION));
					if (in_array($ext, $supportedImage)) {
						$xe_id = $assetsValue['xe_id'];
						$file_name = $assetsValue[$ColumnName];
						$oldFile = $asetsPath . $file_name;
						$thumbOldImage = $asetsPath . "thumb_" . $file_name;
						$newfile = $asetsPath . $currentStoreId . '_' . $file_name;
						copy($oldFile, $newfile);
						$updatePrintAreas = DB::table($tableName)->where('xe_id', '=', $asetsId)->update([$ColumnName => $currentStoreId . '_' . $file_name]);
						if (file_exists($thumbOldImage)) {
							$thumbNewImage = $asetsPath . "thumb_" . $currentStoreId . "_" . $file_name;
							copy($thumbOldImage, $thumbNewImage);
						}
					}
				}
			}
		}
	}
	/**
	 *
	 * Recurse copy
	 * @param $src
	 * @param $dst
	 * @author soumyas@riaxe.com
	 * @date   03 Dec 2020
	 * @return true
	 *
	 */
	protected function recurse_copy($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file)) {
					$this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
				} else {
					@copy($src . '/' . $file, $dst . '/' . $file);

				}
			}
		}
		closedir($dir);
	}
	/**
	 *
	 * update print print profile data in  language file
	 * @param $languageFilePath
	 * @param $fileType
	 * @param $currentStoreId
	 * @param $printProfileData
	 * @author soumyas@riaxe.com
	 * @date   18 Dec 2020
	 * @return true
	 *
	 */
	public function updateLanguageFile($languageFilePath, $fileType, $currentStoreId, $newPrintProfilesArray) {
		$updateStatus = 0;
		foreach (glob($languageFilePath . "/*" . $fileType) as $file) {
			if (strpos($file, "" . $currentStoreId . "") !== false) {
				$data = file_get_contents($file);
				$json_arr = json_decode($data, true);
				$i = 0;
				$newArr = array();
				foreach ($json_arr['print_profiles'] as $key => $value) {
					$newKey = str_replace($key, $newPrintProfilesArray[$i], $key);
					$newArr[$newKey] = $value;
					$i++;
				}
				$json_arr['print_profiles'] = $newArr;
				file_put_contents($file, json_encode($json_arr));
			}

		}
		return $updateStatus;
	}
	/**
	 *
	 * Print Profile assets category rel
	 * @param $CategoryIds
	 * @param $printProfileIds
	 * @param $assetsType
	 * @author soumyas@riaxe.com
	 * @date   21 Dec 2020
	 * @return true
	 *
	 */
	public function printProfileAssetsCategoryRel($categoryIds, $printProfileIds, $tableName, $assetsType) {
		$categoriesStatus = 0;
		foreach ($categoryIds as $categoryId) {
			foreach ($printProfileIds as $printProfileId) {
				DB::insert('INSERT INTO ' . $tableName . ' (`print_profile_id`, `asset_type_id` , `category_id`) VALUES (?, ? ,? )', [$printProfileId, $assetsType, $categoryId]);
			}
			$categoriesStatus = 1;
		}
		return $categoriesStatus;
	}
	/**
	 *
	 * Print Profile features rel
	 * @param $CategoryIds
	 * @param $printProfileIds
	 * @param $assetsType
	 * @author soumyas@riaxe.com
	 * @date   28 Dec 2020
	 * @return true
	 *
	 */
	public function printProfileFeaturesyRel($printProfileIds, $tableName) {
		$featureStatus = 0;
		foreach ($printProfileIds as $printProfileId) {
			$sql = "SELECT * FROM features WHERE slug='clipart' OR slug='template' OR slug='background' OR slug='shape' OR slug='image' OR slug='text'";
			$featuresData = DB::select($sql);
			if (!empty($featuresData)) {
				foreach ($featuresData as $key => $value) {
					$value = (array) $value;
					$featureId = $value['xe_id'];
					$featureStatus = DB::insert('INSERT INTO ' . $tableName . ' (`print_profile_id`,`feature_id`) VALUES (?,?)', [$printProfileId, $featureId]);
				}
			}
		}
		return $featureStatus;
	}

	/**
	 * GET: xe_id
	 *
	 * @param $tableName
	 * @param $columnNmae
	 * @param $storeId
	 *
	 * @author soumya@riaxe.com
	 * @date   12 Jan 2020
	 *
	 */
	public function getCurrentStoreXeId($tableName, $columnName, $storeId, $type = null) {
		$xeIdsArray = [];
		if ($tableName == 'categories') {
			$sql = 'SELECT ' . $columnName . ' FROM  ' . $tableName . ' WHERE store_id=' . $storeId . ' AND asset_type_id=' . $type;
		} else {
			$sql = 'SELECT ' . $columnName . ' FROM  ' . $tableName . ' WHERE store_id=' . $storeId;
		}
		$getData = DB::select($sql);
		if (!empty($getData)) {
			foreach ($getData as $key => $value) {
				$value = (array) $value;
				$xeIdsArray[] = $value[$columnName];
			}

		}
		return $xeIdsArray;
	}

	/**
	 * Delete: Delete store
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   12 Jan 2020
	 *
	 */

	public function deleteStore($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('deleteStore', 'error'),
		];
		if (isset($args) && !empty($args)) {
			$storeId = $args['id'];
			$storeDeletedStatus = DB::table('stores')->where('xe_id', $storeId)->delete();
			if ($storeDeletedStatus > 0) {
				$modulesList = ['PrintProfiles', 'Backgrounds', 'Cliparts', 'ColorPalettes', 'DecorationAreas', 'DesignStates', 'Fonts', 'Languages', 'Masks', 'PrintAreas', 'Settings', 'Shapes', 'Templates', 'Users', 'GraphicFonts', 'Images', 'UserDesigns', 'AugmentedRealities', 'Quotations', 'Productions', 'Vendors', 'ShipAddress', 'PurchaseOrder', 'Products'];
				foreach ($modulesList as $module) {
					switch ($module) {
					case "PrintProfiles":
						$storeSettingData = ASSETS_PATH_W . 'settings/stores/' . $storeId;
						rrmdir($storeSettingData);
						$this->deletePrintProfileData($storeId, 'print_profiles', 'store_id');
						$printProfileFilePath = ASSETS_PATH_W . 'print_profile/' . $storeId . '_*.*';
						$printProfileThumbPath = ASSETS_PATH_W . 'print_profile/thumb_' . $storeId . '_*.*';
						$this->deleteFileFromFolder($printProfileFilePath);
						$this->deleteFileFromFolder($printProfileThumbPath);
						break;

					case "Backgrounds":
						$sql = "SELECT * FROM backgrounds WHERE store_id=" . $storeId;
						$getbackgroundsData = DB::select($sql);
						if (!empty($getbackgroundsData)) {
							foreach ($getbackgroundsData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('background_category_rel')->where('background_id', $xe_id)->delete();
								DB::table('background_tag_rel')->where('background_id', $xe_id)->delete();
							}
							$backgroundsFilePath = ASSETS_PATH_W . 'backgrounds/' . $storeId . '_*.*';
							$backgroundsThumbPath = ASSETS_PATH_W . 'backgrounds/thumb_' . $storeId . '_*.*';
							$this->deleteFileFromFolder($backgroundsFilePath);
							$this->deleteFileFromFolder($backgroundsThumbPath);
							DB::table('backgrounds')->where('store_id', $storeId)->delete();
							DB::table('categories')->where('store_id', $storeId)->where('asset_type_id', 1)->delete();
						}
						break;

					case "Cliparts":
						$sql = "SELECT * FROM cliparts WHERE store_id=" . $storeId;
						$getClipartsData = DB::select($sql);
						if (!empty($getClipartsData)) {
							foreach ($getClipartsData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('clipart_category_rel')->where('clipart_id', $xe_id)->delete();
								DB::table('clipart_tag_rel')->where('clipart_id', $xe_id)->delete();
							}
							$clipartsFilePath = ASSETS_PATH_W . 'vectors/' . $storeId . '_*.*';
							$clipartsThumbPath = ASSETS_PATH_W . 'vectors/thumb_' . $storeId . '_*.*';
							$this->deleteFileFromFolder($clipartsFilePath);
							$this->deleteFileFromFolder($clipartsThumbPath);
							DB::table('cliparts')->where('store_id', $storeId)->delete();
							DB::table('categories')->where('store_id', $storeId)->where('asset_type_id', 2)->delete();
						}
						break;

					case "ColorPalettes":
						$sql = "SELECT * FROM color_palettes WHERE store_id=" . $storeId;
						$getColorPalettesData = DB::select($sql);
						if (!empty($getColorPalettesData)) {
							$colorPalettesFilePath = ASSETS_PATH_W . 'color_palettes/' . $storeId . '_*.*';
							$colorPaletteThumbPath = ASSETS_PATH_W . 'color_palettes/thumb_' . $storeId . '_*.*';
							$this->deleteFileFromFolder($colorPalettesFilePath);
							$this->deleteFileFromFolder($colorPaletteThumbPath);
							DB::table('color_palettes')->where('store_id', $storeId)->delete();
							DB::table('categories')->where('store_id', $storeId)->where('asset_type_id', 3)->delete();
						}
						break;

					case "DesignStates":
						$sql = "SELECT * FROM design_states WHERE store_id=" . $storeId;
						$getDesignStatesData = DB::select($sql);
						if (!empty($getDesignStatesData)) {
							DB::table('design_states')->where('store_id', $storeId)->delete();
						}
						break;
					case "Fonts":
						$sql = "SELECT * FROM fonts WHERE store_id=" . $storeId;
						$getFontsData = DB::select($sql);
						if (!empty($getFontsData)) {
							foreach ($getFontsData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('font_category_rel')->where('font_id', $xe_id)->delete();
								DB::table('font_tag_rel')->where('font_id', $xe_id)->delete();
							}
							$fontsFilePath = ASSETS_PATH_W . 'fonts/' . $storeId . '_*.*';
							$this->deleteFileFromFolder($fontsFilePath);
							DB::table('fonts')->where('store_id', $storeId)->delete();
							DB::table('categories')->where('store_id', $storeId)->where('asset_type_id', 6)->delete();
						}
						break;

					case "Masks":
						$sql = "SELECT * FROM masks WHERE store_id=" . $storeId;
						$masksData = DB::select($sql);
						if (!empty($masksData)) {
							foreach ($masksData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('mask_tag_rel')->where('mask_id', $xe_id)->delete();
							}
							$masksFilePath = ASSETS_PATH_W . 'masks/' . $storeId . '_*.*';
							$masksThumbPath = ASSETS_PATH_W . 'masks/thumb_' . $storeId . '_*.*';
							$this->deleteFileFromFolder($masksFilePath);
							$this->deleteFileFromFolder($masksThumbPath);
							DB::table('masks')->where('store_id', $storeId)->delete();
							DB::table('categories')->where('store_id', $storeId)->where('asset_type_id', 8)->delete();
						}
						break;
					case "PrintAreas":
						$sql = "SELECT * FROM print_area_types WHERE store_id=" . $storeId;
						$printAreaTypesData = DB::select($sql);
						if (!empty($printAreaTypesData)) {
							DB::table('print_area_types')->where('store_id', $storeId)->delete();
						}
						$sql = "SELECT * FROM print_areas WHERE store_id=" . $storeId;
						$printAreasData = DB::select($sql);
						if (!empty($printAreasData)) {
							DB::table('print_areas')->where('store_id', $storeId)->delete();
						}
						$printAreaTypesFilePath = ASSETS_PATH_W . 'print_area_types/' . $storeId . '_*.*';
						$this->deleteFileFromFolder($printAreaTypesFilePath);
						break;
					case "Productions":
						$this->deleteDataFromParentTable('production_hub_settings', $storeId, '');
						$this->deleteDataFromParentTable('production_status', $storeId, '');
						$this->deleteDataFromParentTable('production_tags', $storeId, '');
						$this->deleteDataFromParentTable('production_email_templates', $storeId, '');
						$this->deleteDataFromParentTable('quote_payment_methods', $storeId, '');
						$this->deleteDataFromParentTable('purchase_order_status', $storeId, '');
						//delete data from production table
						$this->deleteDataFromParentTable('production_jobs', $storeId, '');
						//Delete data from orders table whose production job is created
						$sql = "SELECT * FROM orders WHERE store_id=" . $storeId . " AND production_status != '0'";
						$productionOrderData = DB::select($sql);
						if (!empty($productionOrderData)) {
							DB::table('orders')->where('store_id', $storeId)->where('production_status', '!=', '0')->delete();
						}
						break;
					case "Settings":
						$this->deleteDataFromParentTable('settings', $storeId, '');
						$this->deleteDataFromParentTable('app_units', $storeId, '');
						$this->deleteDataFromParentTable('quote_dynamic_form_values', $storeId, '');
						$isDir = ASSETS_PATH_W . 'settings/order_setting/' . $storeId;
						if (is_dir($isDir)) {
							array_map('unlink', array_filter((array) array_merge(glob(ASSETS_PATH_W . 'settings/order_setting/' . $storeId . "/*"))));
						}
						break;
					case "Shapes":
						$sql = "SELECT * FROM shapes WHERE store_id=" . $storeId;
						$shapesData = DB::select($sql);
						if (!empty($shapesData)) {
							foreach ($shapesData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('shape_category_rel')->where('shape_id', $xe_id)->delete();
							}
							$shapesFilePath = ASSETS_PATH_W . 'shapes/' . $storeId . '_*.*';
							$shapesThumbPath = ASSETS_PATH_W . 'shapes/thumb_' . $storeId . '_*.*';
							$this->deleteFileFromFolder($shapesFilePath);
							$this->deleteFileFromFolder($shapesThumbPath);
							DB::table('shapes')->where('store_id', $storeId)->delete();
							DB::table('categories')->where('store_id', $storeId)->where('asset_type_id', 9)->delete();
						}
						break;
					case "Templates":
						$sql = "SELECT * FROM templates WHERE store_id=" . $storeId;
						$templatesData = DB::select($sql);
						if (!empty($templatesData)) {
							foreach ($templatesData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('template_category_rel')->where('template_id', $xe_id)->delete();
							}
							DB::table('templates')->where('store_id', $storeId)->delete();
							DB::table('categories')->where('store_id', $storeId)->where('asset_type_id', 11)->delete();
						}
						break;
					case "Users":
						$sql = "SELECT * FROM user_privileges WHERE store_id=" . $storeId;
						$userPrivilegesData = DB::select($sql);
						if (!empty($userPrivilegesData)) {
							DB::table('user_privileges')->where('store_id', $storeId)->delete();
						}
						break;
					case "Languages":
						$languagesAdminPath = ASSETS_PATH_W . 'languages/admin/' . $storeId . '_*.*';
						$languagesToolPath = ASSETS_PATH_W . 'languages/tool/' . $storeId . '_*.*';
						$this->deleteFileFromFolder($languagesAdminPath);
						$this->deleteFileFromFolder($languagesToolPath);
						$this->deleteDataFromParentTable('languages', $storeId, '');

						break;
					case "Products":
						$sql = "SELECT * FROM product_images WHERE store_id=" . $storeId;
						$getProductData = DB::select($sql);
						if (!empty($getProductData)) {
							foreach ($getProductData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('product_image_sides')->where('product_image_id', $xe_id)->delete();
							}
							DB::table('product_images')->where('store_id', $storeId)->delete();
							$mainImagePath = ASSETS_PATH_W . 'products/' . $storeId . '_*.*';
							$thumbPath = ASSETS_PATH_W . 'products/thumb_' . $storeId . '_*.*';
							$this->deleteFileFromFolder($mainImagePath);
							$this->deleteFileFromFolder($thumbPath);
						}
						$sql = "SELECT * FROM product_settings WHERE store_id=" . $storeId;
						$getproductSettingsData = DB::select($sql);
						if (!empty($getproductSettingsData)) {
							foreach ($getproductSettingsData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('print_profile_product_setting_rel')->where('product_setting_id', $xe_id)->delete();
								DB::table('product_decoration_settings')->where('product_setting_id', $xe_id)->delete();
							}
							DB::table('product_settings')->where('store_id', $storeId)->delete();
						}
						break;
					case "GraphicFonts":
						$sql = "SELECT * FROM graphic_fonts WHERE store_id=" . $storeId;
						$getGraphicFontsData = DB::select($sql);
						if (!empty($getGraphicFontsData)) {
							foreach ($getGraphicFontsData as $key => $value) {
								$value = (array) $value;
								$xe_id = $value['xe_id'];
								DB::table('graphic_font_letters')->where('graphic_font_id', $xe_id)->delete();
							}
							DB::table('graphic_fonts')->where('store_id', $storeId)->delete();
							$graphicsFilePath = ASSETS_PATH_W . 'graphics/' . $storeId . '_*.*';
							$graphicsFileThumbPath = ASSETS_PATH_W . 'graphics/thumb_' . $storeId . '_*.*';
							$this->deleteFileFromFolder($graphicsFilePath);
							$this->deleteFileFromFolder($graphicsFileThumbPath);
						}
						break;
					}
				}
			}

			$jsonResponse = [
				'status' => 1,
				'message' => 'Store deleted successfully',
			];

		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Delete: deletePrintProfileData
	 *
	 * @param $storeId
	 * @param $tableName
	 * @param $columnName
	 *
	 * @author soumya@riaxe.com
	 * @date   12 Jan 2020
	 *
	 */
	public function deletePrintProfileData($storeId, $tableName, $columnName) {
		$sql = "SELECT * FROM " . $tableName . " WHERE " . $columnName . "=" . $storeId;
		$getData = DB::select($sql);
		if (!empty($getData)) {
			foreach ($getData as $key => $value) {
				$value = (array) $value;
				$xe_id = $value['xe_id'];
				DB::table('print_profile_feature_rel')->where('print_profile_id', $xe_id)->delete();
				DB::table('print_profile_assets_category_rel')->where('print_profile_id', $xe_id)->delete();
			}
			DB::table($tableName)->where($columnName, $storeId)->delete();
		}

	}
	/**
	 * Delete: deletePrintProfileData
	 *
	 * @param $tableName
	 * @param $storeId
	 * @param $type
	 *
	 * @author soumya@riaxe.com
	 * @date   12 Jan 2020
	 *
	 */
	public function deleteDataFromParentTable($tableName, $storeId, $type = null) {
		if ($table == 'categories') {

		} else {
			$sql = "SELECT * FROM " . $tableName . " WHERE  store_id=" . $storeId;
			$getData = DB::select($sql);
			if (!empty($getData)) {
				DB::table($tableName)->where("store_id", $storeId)->delete();
			}
		}

	}
	/**
	 * Delete: deleteFileFromFolder
	 *
	 * @param $path
	 * @author soumya@riaxe.com
	 * @date   12 Jan 2020
	 *
	 */
	public function deleteFileFromFolder($path) {
		$fileStatus = array_map('unlink', glob($path));
		return $fileStatus;
	}
	/**
	 * GET: Store status
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   09 October 2020
	 * @return json
	 */
	public function enableCustomizeButton($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Customize button', 'error'),
		];
		if (!empty($args) && isset($args)) {
			$storeId = $args['id'];
			$storesInit = new Stores();
			$getStores = $storesInit->select('is_active')->where('xe_id', '=', $storeId);
			if ($getStores->get()->count() > 0) {
				$getStoreData = $getStores->get()->toArray();
				$jsonResponse = [
					'status' => 1,
					'data' => $getStoreData[0]['is_active'],
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Copy: Production status
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   28 July 2021
	 * @return true/false
	 */
	public function copyProductionStatus($defaultStoreId , $currentStoreId , $moduleIds, $productionProfilesIdsArray ) {
		$quotationModuleId= 1; /*Quotation module id*/
		$productionModuleId= 4; /*Production module id*/
		$productionStatus = false;
		$productionStatusTable = 'production_status';
		$productionStatusPrintProfileTable = 'production_status_print_profile_rel';
		$productionStatusFeaturesTable = 'production_status_features';
		$productionStatusAssigneeRelTable = 'production_status_assignee_rel';
		/*For quotation*/
		if (in_array($quotationModuleId, $moduleIds)) { 
			$quotationStatusList = DB::table($productionStatusTable)->where('store_id', '=', $defaultStoreId)->where('module_id', '=', $quotationModuleId);
			if ($quotationStatusList->count() > 0){
				$quotationStatusArray = $quotationStatusList->get()->toArray();
				foreach ($quotationStatusArray as $key => $value){
					$value = (array) $value;
					DB::insert('INSERT INTO ' . $productionStatusTable . ' (`store_id`, `status_name`,`color_code`,`module_id`,`is_default`,`sort_order`,`status`,`slug`) VALUES (?,?,?,?,?,?,?,?)', [$currentStoreId, $value['status_name'], $value['color_code'],$value['module_id'] , $value['is_default'], $value['sort_order'], $value['status'], $value['slug'] ]);
					DB::getPdo()->lastInsertId();
					$productionStatus = true;
				}
			}
		}
		/*For Production*/
		if (in_array($productionModuleId, $moduleIds)) { 
			$productionStatusList = DB::table($productionStatusTable)->where('store_id', '=', $defaultStoreId)->where('module_id', '=', $productionModuleId);
			if ($productionStatusList->count() > 0) {
				$productionStatusArray = $productionStatusList->get()->toArray();
				foreach ($productionStatusArray as $key => $value) {
					$value = (array) $value;
					$parentProductionStatusId = $value['xe_id'];
					DB::insert('INSERT INTO ' . $productionStatusTable . ' (`store_id`, `status_name`,`color_code`,`module_id`,`is_default`,`sort_order`,`status`,`slug`) VALUES (?,?,?,?,?,?,?,?)', [$currentStoreId, $value['status_name'], $value['color_code'],$value['module_id'] , $value['is_default'], $value['sort_order'], $value['status'], $value['slug'] ]);
					$newProductionStatusId = DB::getPdo()->lastInsertId();
					/*production_status_print_profile_rel*/
					$productionStatusPrintProfileList = DB::table($productionStatusPrintProfileTable)->where('status_id', '=', $parentProductionStatusId);
					if ($productionStatusPrintProfileList->count() > 0) {
						$printProfileArray = $productionStatusPrintProfileList->get()->toArray();
						foreach ($printProfileArray as $printKey => $printValue){
							$printValue = (array) $printValue;
							DB::insert('INSERT INTO ' . $productionStatusPrintProfileTable . ' (`status_id`, `print_profile_id` ) VALUES (?, ? )', [$newProductionStatusId, $productionProfilesIdsArray[$printValue['print_profile_id']] ]);
						}
					}
					/*production status features*/
					$productionStatusFeaturesList = DB::table($productionStatusFeaturesTable)->where('status_id', '=', $parentProductionStatusId);
					if ($productionStatusFeaturesList->count() > 0) {
						$featuresListArray = $productionStatusFeaturesList->get()->toArray();
						foreach ($featuresListArray as $featuresKey => $featuresValue) {
							$featuresValue = (array) $featuresValue;
							DB::insert('INSERT INTO ' . $productionStatusFeaturesTable . ' (`status_id`, `duration`,`is_global`,`is_group`) VALUES (?,?,?,?)', [$newProductionStatusId, $featuresValue['duration'], $featuresValue['is_global'],$featuresValue['is_group'] ]);
						}
					}
					/*production_status_assignee_rel*/
					$productionStatusAssigneeList = DB::table($productionStatusAssigneeRelTable)->where('status_id', '=', $parentProductionStatusId);
					if ($productionStatusAssigneeList->count() > 0) {
						$productionStatusAssigneeListArray = $productionStatusAssigneeList->get()->toArray();
						foreach ($productionStatusAssigneeListArray as $assigneeKey => $assigneeValue){
							$assigneeValue = (array) $assigneeValue;
							DB::insert('INSERT INTO ' . $productionStatusAssigneeRelTable . ' (`status_id`, `assignee_id`) VALUES (?,?)', [$newProductionStatusId, $assigneeValue['assignee_id']]);
						}
					}
					$productionStatus = true;
				}
			}
		}

		return $productionStatus;
	}
}