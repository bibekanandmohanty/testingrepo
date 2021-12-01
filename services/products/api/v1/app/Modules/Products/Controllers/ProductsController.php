<?php
/**
 * Manage Products from Woocommerce Store
 *
 * PHP version 5.6
 *
 * @category  Products
 * @package   Store
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Controllers;

use App\Modules\PrintProfiles\Models as PrintProfileModels;
use App\Modules\Products\Models\AppUnit;
use App\Modules\Products\Models\AttributePriceRule;
use App\Modules\Products\Models\DecorationObjects;
use App\Modules\Products\Models\PrintProfileDecorationSettingRel;
use App\Modules\Products\Models\PrintProfileProductSettingRel;
use App\Modules\Products\Models\ProductDecorationSetting;
use App\Modules\Products\Models\ProductImageSettingsRel;
use App\Modules\Products\Models\ProductImageSides;
use App\Modules\Products\Models\ProductSetting;
use App\Modules\Products\Models\ProductSide;
use Illuminate\Database\Capsule\Manager as DB;
use ProductStoreSpace\Controllers\StoreProductsController;
use App\Modules\Products\Controllers\ProductConfiguratorController as Configurator;

/**
 * Products Controller
 *
 * @category Class
 * @package  Product
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductsController extends StoreProductsController {
	/**
	 * GET: Getting List of All product or Single product information
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return A JSON Response
	 */
	public function getProductList($request, $response, $args, $returnType = 0) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'records' => 0,
			'data' => [],
			'message' => message('Products', 'not_found'),
		];
		$productDetails = [];
		$isAdmin = $request->getQueryParam('is_admin');
		$fetch = $request->getQueryParam('fetch');
		$type = $request->getQueryParam('type');
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$currentStoreUrl = '';
		$getProductResponse = $this->getProducts($request, $response, $args);
		if (!empty($args['id'])) {
			if ($storeId > 1 && $type == "tool") {
				$currentStoreUrl = '';
				$databaseStoreInfo = DB::table('stores')->where('xe_id', '=', $storeId);
				if ($databaseStoreInfo->count() > 0) {
					$storeData = $databaseStoreInfo->get()->toArray();
					$storeDataArray = (array) $storeData[0];
					$currentStoreUrl = $storeDataArray['store_url'];
				}
				foreach ($getProductResponse['products']['images'] as $key => $value) {
					$file_name = $value['src'];
					$thumbnail = $value['thumbnail'];
					$hostname = parse_url($file_name, PHP_URL_HOST); //hostname
					$getProductResponse['products']['images'][0]['src'] = str_replace($hostname, $currentStoreUrl, $file_name);
					$getProductResponse['products']['images'][0]['thumbnail'] = str_replace($hostname, $currentStoreUrl, $thumbnail);
				}
			}

			$productDetails = $getProductResponse['products'];
			$getAssociatedPrintProfileData = $this->getAssocPrintProfiles(
				$productDetails['id'], $isAdmin, $storeId
			);

			$productDetails['is_decoration_exists'] = 1;
			$productDetails['print_profile'] = $getAssociatedPrintProfileData['print_profiles'];
			$productDetails['attributes'] = $this->getPriceDetails(
				$productDetails['attributes'],
				$args['id']
			);
			$jsonResponse = [
				'status' => 1,
				'records' => 1,
				'data' => $productDetails,
			];
			if ($returnType == 1) {
				return $jsonResponse;
			}
		} else {
			if (!empty($getProductResponse['products'])) {
				foreach ($getProductResponse['products'] as $eachProductKey => $eachProduct) {
					$getAssocProdImages = [];
					$isAssProTemplate = true;
					$getAssocProdImages = $this->getAssocProductImages($eachProduct['id']);
					$getAssociatedPrintProfileData = $this->getAssocPrintProfiles($eachProduct['id'], $isAdmin, $storeId);
					if (empty($getAssocProdImages)) {
						$getAssocProdImages = isset($eachProduct['image']) ? $eachProduct['image'] : [];
						$isAssProTemplate = false;
					}
					$productDetails[$eachProductKey] = [
						'id' => $eachProduct['id'],
						'variant_id' => $eachProduct['variation_id'],
						'name' => $eachProduct['name'],
						'type' => $eachProduct['type'],
						'sku' => $eachProduct['sku'],
						'price' => $eachProduct['price'],
						'stock' => $eachProduct['stock'],
						'is_template' => $isAssProTemplate,
						'is_sold_out' => $eachProduct['is_sold_out'],
						'image' => $getAssocProdImages,
						'is_decoration_exists' => $getAssociatedPrintProfileData['is_decoration_exists'],
						'print_profile' => ($fetch != '') ? [] : $getAssociatedPrintProfileData['print_profiles'],
					];
					if($fetch == 'all') {
						$productDetails[$eachProductKey] += ['custom_design_id' => $eachProduct['custom_design_id']];
						$productDetails[$eachProductKey] += ['is_decorated_product' => $eachProduct['is_decorated_product']];
						$productDetails[$eachProductKey] += ['is_redesign' => $eachProduct['is_redesign']];
						$productDetails[$eachProductKey] += ['decoration_type' =>$this->getProductDecorationType($eachProduct['id'] , $storeId)];
					}
				}
			}
			$jsonResponse = [
				'status' => 1,
				'records' => count($productDetails),
				'total_records' => $getProductResponse['total_records'],
				'data' => $productDetails,
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * GET: Getting List of All Category/Subcategory or Single
	 * Category/Subcategory information
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return A JSON Response
	 */
	public function totalCategories($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = [
			'status' => 0,
			'data' => [],
		];
		$apiSource = $request->getQueryParam('src');
		// get selected categories only
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$settingLocation = path('abs', 'setting') . 'stores/' . $storeId;
		$jsonFilePath = $settingLocation . '/product_categories.json';
		if (file_exists($jsonFilePath)) {
			$logContent = json_decode(file_get_contents($jsonFilePath), true);
			$selectedCatagories = array_column($logContent['data'], 'id');
		}
		$storeResponse = $this->getCategories($request, $response, $args);
		// bellow three line to sort store responded category list alphabetically.
		$keys = array_column($storeResponse, 'name');
		$array_lowercase = array_map('strtolower', $keys);
        array_multisort($array_lowercase, SORT_ASC, SORT_STRING, $storeResponse);
		$categoriesForTool = array();
		foreach ($storeResponse as $key => $category) {
			$storeResponse[$key]['show_in_tool'] = false;
			if (in_array($category['id'], $selectedCatagories)) {
				$storeResponse[$key]['show_in_tool'] = true;
				$categoriesForTool[] = $category;
			}
		}
		if ($apiSource == 'tool' && !empty($selectedCatagories)) {
			$storeResponse = $categoriesForTool;
		}
		if (!empty($storeResponse)) {
			$storeResponse = [
				'status' => 1,
				'data' => $storeResponse,
			];
		} else {
			$storeResponse = [
				'status' => 0,
				'data' => [],
			];
		}

		return response(
			$response,
			[
				'data' => $storeResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * GET: Get list of Measurement Units
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return A JSON Response
	 */
	public function getMeasurementUnits($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$appUnitInit = new AppUnit();
		$initAppUnit = $appUnitInit->whereNotNull('name');
		$jsonResponse = [
			'status' => 0,
			'message' => message('Measurement Unit', 'error'),
		];
		if ($initAppUnit->count() > 0) {
			$jsonResponse = [
				'status' => 1,
				'data' => $initAppUnit->orderBy('xe_id', 'desc')->get(),
			];
		}

		return response(
			$response,
			['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Clone the Product Decoration
	 *
	 * @param $parentId   Parent Product Id
	 * @param $predecoId  Pre Decorated Product Id
	 *
	 * @author satyabratap@riaxe.com
	 * @date   21 Apr 2020
	 * @return A JSON Response
	 */
	public function cloneProduct($parentId, $predecoId) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Products', 'error'),
		];

		$productSettingId = 0;
		if ($parentId != "") {
			$getSettingsInit = new ProductSetting;
			$settingDetails = $getSettingsInit->where(
				['product_id' => $parentId]
			)->first();
			if (!empty($settingDetails)) {
				$settingDetails = $settingDetails->toArray();
				$productSettData = [
					'product_id' => $predecoId,
					'store_id' => $settingDetails['store_id'],
					'is_variable_decoration' => $settingDetails['is_variable_decoration'],
					'is_ruler' => $settingDetails['is_ruler'],
					'is_crop_mark' => $settingDetails['is_crop_mark'],
					'is_safe_zone' => $settingDetails['is_safe_zone'],
					'crop_value' => $settingDetails['crop_value'],
					'safe_value' => $settingDetails['safe_value'],
					'is_3d_preview' => $settingDetails['is_3d_preview'],
					'3d_object_file' => $settingDetails['3d_object_file'],
					'3d_object' => $settingDetails['3d_object'],
					'is_configurator' => $settingDetails['is_configurator'],
					'scale_unit_id' => $settingDetails['scale_unit_id'],
					'is_configurator' => $settingDetails['is_configurator'],
                    'is_svg_configurator' => $settingDetails['is_svg_configurator'],
				];

				$productSetting = new ProductSetting($productSettData);
				$productSetting->save();
				$clonedSettingId = $productSetting->xe_id;
				$productSettingId = $settingDetails['xe_id'];
				// FOR SVG CONFIGURATOR
				if($settingDetails['is_svg_configurator']) {
                    $configuratorInit = new Configurator();
                    $configuratorInit->cloneSVGProductConfigurator($parentId,$predecoId);
                }
			}

			if ($productSettingId > 0) {
				$getImgRelInit = new ProductImageSettingsRel;
				$imgRelData = $getImgRelInit->where(
					['product_setting_id' => $productSettingId]
				)->first();
				if (!empty($imgRelData)) {
					$imgRelData = $imgRelData->toArray();
					$saveRelDetails = [
						'product_setting_id' => $clonedSettingId,
						'product_image_id' => $imgRelData['product_image_id'],
					];

					$productImageSettings = new ProductImageSettingsRel($saveRelDetails);
					$productImageSettings->save();
				}

				$profileDecoRelInit = new PrintProfileProductSettingRel();
				$getProfSetInit = $profileDecoRelInit->where(
					['product_setting_id' => $productSettingId]
				)->get();
				if (!empty($getProfSetInit)) {
					$getProfSetInit = $getProfSetInit->toArray();
					foreach ($getProfSetInit as $settProfile) {
						$saveSettProfile = new PrintProfileProductSettingRel(
							[
								'print_profile_id' => $settProfile['print_profile_id'],
								'product_setting_id' => $clonedSettingId,
							]
						);
						$saveSettProfile->save();
					}
				}
				if ($settingDetails['is_variable_decoration'] == 1) {
					$getDecorationInit = new ProductDecorationSetting();
					$getDecorationData = $getDecorationInit->where(
						[
							'product_setting_id' => $productSettingId,

						]
					)->first();
					if (!empty($getDecorationData)) {
						$decoration = $getDecorationData->toArray();
						$saveDecoration = new ProductDecorationSetting(
							[
								'product_setting_id' => $clonedSettingId,
								'dimension' => $decoration['dimension'],
								'print_area_id' => $decoration['print_area_id'],
								'sub_print_area_type' => $decoration['sub_print_area_type'],
								'pre_defined_dimensions' => $decoration['pre_defined_dimensions'],
								'user_defined_dimensions' => $decoration['user_defined_dimensions'],
								'custom_min_height' => $decoration['custom_min_height'],
								'custom_max_height' => $decoration['custom_max_height'],
								'custom_min_width' => $decoration['custom_min_width'],
								'custom_max_width' => $decoration['custom_max_width'],
								'custom_bound_price' => $decoration['custom_bound_price'],
								'is_border_enable' => $decoration['is_border_enable'],
								'is_sides_allow' => $decoration['is_sides_allow'],
								'no_of_sides' => $decoration['no_of_sides'],
								'is_dimension_enable' => $decoration['is_dimension_enable'],
							]
						);
						$saveDecoration->save();
					}
				} else {
					$get3DObjInit = new DecorationObjects();
					$objDataExist = $get3DObjInit->where(
						['product_id' => $parentId]
					)->count();
					if ($objDataExist > 0) {
						$objData = $get3DObjInit->where(
							['product_id' => $parentId]
						)->first()->toArray();
						if (!empty($objData['3d_object_file'])) {
							$objFile = str_replace(path('read', '3d_object'), '', $objData['3d_object_file']);
						}
						if (!empty($objData['uv_file'])) {
							$uvFile = str_replace(path('read', '3d_object'), '', $objData['uv_file']);
						}
						$objDetails = [
							'product_id' => $predecoId,
							'3d_object_file' => $objFile,
							'uv_file' => $uvFile,
						];
						$saveObjData = new DecorationObjects($objDetails);
						$saveObjData->save();
					}
					$getProductSideInit = new ProductSide();
					$sideData = $getProductSideInit->where(
						['product_setting_id' => $productSettingId]
					)->get();
					if (!empty($sideData)) {
						$sideData = $sideData->toArray();
						foreach ($sideData as $productSideData) {
							$productSide = new ProductSide(
								[
									'product_setting_id' => $clonedSettingId,
									'side_name' => $productSideData['side_name'],
									'product_image_dimension' => $productSideData['product_image_dimension'],
									'is_visible' => $productSideData['is_visible'],
									'product_image_side_id' => $productSideData['product_image_side_id'],
								]
							);
							$productSide->save();
							$clonedSideId = $productSide->xe_id;

							$getDecorationInit = new ProductDecorationSetting();
							$getDecorationData = $getDecorationInit->where(
								[
									'product_setting_id' => $productSettingId,
									'product_side_id' => $productSideData['xe_id'],

								]
							)->get();
							if (!empty($getDecorationData)) {
								$getDecorationData = $getDecorationData->toArray();
								foreach ($getDecorationData as $decoration) {
									$saveDecoration = new ProductDecorationSetting(
										[
											'product_setting_id' => $clonedSettingId,
											'product_side_id' => $clonedSideId,
											'name' => $decoration['name'],
											'dimension' => $decoration['dimension'],
											'print_area_id' => $decoration['print_area_id'],
											'sub_print_area_type' => $decoration['sub_print_area_type'],
											'pre_defined_dimensions' => $decoration['pre_defined_dimensions'],
											'user_defined_dimensions' => $decoration['user_defined_dimensions'],
											'custom_min_height' => $decoration['custom_min_height'],
											'custom_max_height' => $decoration['custom_max_height'],
											'custom_min_width' => $decoration['custom_min_width'],
											'custom_max_width' => $decoration['custom_max_width'],
											'custom_bound_price' => $decoration['custom_bound_price'],
											'is_border_enable' => $decoration['is_border_enable'],
											'is_sides_allow' => $decoration['is_sides_allow'],
											'no_of_sides' => $decoration['no_of_sides'],
											'is_dimension_enable' => $decoration['is_dimension_enable'],
										]
									);
									$saveDecoration->save();
									$clonedDecoId = $saveDecoration->xe_id;

									$profileDecoRelInit = new PrintProfileDecorationSettingRel();
									$getProfileDecoRel = $profileDecoRelInit->where(
										[
											'decoration_setting_id' => $decoration['xe_id'],

										]
									)->get();
									if (!empty($getProfileDecoRel)) {
										$getProfileDecoRel = $getProfileDecoRel->toArray();
										foreach ($getProfileDecoRel as $decoProfile) {
											$saveProfile = new PrintProfileDecorationSettingRel(
												[
													'print_profile_id' => $decoProfile['print_profile_id'],
													'decoration_setting_id' => $clonedDecoId,
												]
											);
											$saveProfile->save();
										}
									}
								}
							}
						}
					}
				}
			}
			$jsonResponse = [
				'status' => 1,
				'message' => message('Products', 'done'),
			];
		}
		return $jsonResponse;
	}

	/**
	 * Post: Save Predecorator Data at Store end
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Save response Array
	 */
	public function savePredecorator($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Predecorator', 'error'),
		];
		// Call Internal Store
		$predecoSaveResp = $this->saveProduct($request, $response);
		//Copy the Decoration of the Parent Product
		$predecoDetails = $request->getParsedBody();
		$productData = json_clean_decode($predecoDetails['data']);
		$parentProductId = $productData['parent_product_id'];
		$cloneStatus['status'] = 0;
		if (!empty($predecoSaveResp)) {
			if ($parentProductId != "" and $productData['ref_id'] > 0) {
				$cloneStatus = $this->cloneProduct($parentProductId, $predecoSaveResp['product_id']);
			}
			$decoJsonStatus = 0;
			if ($productData['ref_id'] != "") {
				$templateJsonPath = path('abs', 'design_state') . 'templates/' . $productData['ref_id'] . '.json';
				if (file_exists($templateJsonPath)) {
					$preDecoFolder = path('abs', 'design_state') . 'predecorators';
					if (!file_exists($preDecoFolder)) {
						mkdir($preDecoFolder, 0777, true);
					}
					$preDecoJsonFile = path('abs', 'design_state') . 'predecorators/' . $productData['ref_id'] . '.json';
					write_file($preDecoJsonFile, file_get_contents($templateJsonPath));
					$decoJsonStatus = 1;
				}
			}
			$jsonResponse = [
				'status' => 1,
				'product_id' => $predecoSaveResp['product_id'],
				'decoration_status' => $cloneStatus['status'],
				'deco_json_status' => $decoJsonStatus,
				'message' => message('Predecorator', 'saved'),
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * Post: Create Product Variations
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Save response Array
	 */
	public function createVariations($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Variation', 'error'),
		];
		$variationResponse = $this->createProductVariations($request, $response);
		if (!empty($variationResponse)) {
			$jsonResponse = [
				'status' => 1,
				'product_id' => $predecoSaveResp['product_id'],
				'variation_id' => $predecoSaveResp['variation_id'],
				'message' => message('Variation', 'saved'),
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * Post: Validate Sku and Name
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Validate response Array
	 */
	public function validateParams($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => 'Sorry, you can not proceed with this details',
		];
		if (strtolower(STORE_NAME) == "shopify") {
			$validationResponse = 0;
		} else {
			$validationResponse = $this->validateStoreSkuName($request, $response);
		}

		if ($validationResponse == 0) {
			$jsonResponse = [
				'status' => 1,
				'message' => 'You can use this combination for new product',
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * Get: Get Attribute List
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return A JSON Response
	 */
	public function getStoreAttributes($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Store Attributes', 'not_found'),
		];
		$attributeResponse = $this->storeAttributeList($request, $response);
		if (!empty($attributeResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $attributeResponse,
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * GET: Get Product Attribute Pricing by Product Id
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Json
	 */
	public function getProductAttrPrc($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
		];
		$productData = $this->storeProductAttrPrc($request, $response, $args);
		$printProfiles = [];
		if (!empty($productData)) {
			$productId = to_int($args['id']);
			$printProfileDetails = $this->getAssocPrintProfiles($productId);
			if (!empty($printProfileDetails['print_profiles'])) {
				$printProfiles = $printProfileDetails['print_profiles'];
			}
			$productAttributes = $this->getPriceDetails(
				$productData, $productId
			);
			$jsonResponse = [
				'status' => 1,
				'data' => [
					'print_profile' => $printProfiles,
					'attributes' => $productAttributes,
				],
			];
		}

		return response(
			$response,
			['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Save Product attribute prices
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return boolean
	 */
	public function saveProdAttrPrc($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$jsonResponse = [
			'status' => 0,
			'message' => message('Attribute Pricing', 'error'),
		];

		if (isset($allPostPutVars['price_data'])
			&& !empty($allPostPutVars['price_data'])
		) {
			$attributePriceJson = $allPostPutVars['price_data'];
			$attributePriceArray = json_clean_decode($attributePriceJson, true);
			if (isset($attributePriceArray['product_id'])
				&& $attributePriceArray['product_id'] > 0
			) {
				$productId = $attributePriceArray['product_id'];
				AttributePriceRule::where('product_id', $productId)->delete();
				if (isset($attributePriceArray['attributes'])
					&& count($attributePriceArray['attributes']) == 0
				) {
					$jsonResponse = [
						'status' => 1,
						'message' => message('Attribute Pricing', 'updated'),
					];
				} else {
					$success = 0;
					foreach ($attributePriceArray['attributes'] as $attributeData) {
						$attributeId = $attributeData['attribute_id'];
						if (isset($attributeData['attribute_term'])
							&& $attributeData['attribute_term'] != ""
						) {
							foreach ($attributeData['attribute_term'] as $attributeTermData) {
								$attributeTermId = $attributeTermData['attribute_term_id'];
								if (isset($attributeTermData['price_data'])
									&& $attributeTermData['price_data'] != ""
								) {
									foreach ($attributeTermData['price_data'] as $priceData) {
										$printProfileId = $priceData['print_profile_id'];
										$price = $priceData['price'];
										$attributePrices = [
											'product_id' => $productId,
											'attribute_id' => $attributeId,
											'attribute_term_id' => $attributeTermId,
											'print_profile_id' => $printProfileId,
											'price' => $price,
										];
										$saveAttributePrice = new AttributePriceRule(
											$attributePrices
										);
										if ($saveAttributePrice->save()) {
											$success++;
										}
									}
								}
							}
						}
					}
					if (!empty($success) && $success > 0) {
						$jsonResponse = [
							'status' => 1,
							'message' => $success . ' Prices saved successfully',
						];
					}
				}
			}
		}

		return response(
			$response,
			['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Getting List of All color of a single product
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   19 Sept 2019
	 * @return Array list of colors
	 */
	public function colorsByProductId($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'records' => 0,
			'data' => [],
			'message' => message('Colors', 'not_found'),
		];
		$attributeName = $this->getAttributeName();
		$attributeSlug = $attributeName['color'];
		$productId = to_int($args['id']);
		$isAdmin = to_int((!empty($request->getQueryParam('isadmin'))
			&& $request->getQueryParam('isadmin') != "")
			? $request->getQueryParam('isadmin') : 0);
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$type = $request->getQueryParam('type');
		$getColorResponse = $this->colorsByProduct(
			$request, $response, [
				'product_id' => $productId, 'slug' => $attributeSlug,
			]
		);
		$currentStoreUrl = '';
		if ($storeId > 1 && $type == "tool") {
			$databaseStoreInfo = DB::table('stores')->where('xe_id', '=', $storeId);
			if ($databaseStoreInfo->count() > 0) {
				$storeData = $databaseStoreInfo->get()->toArray();
				$storeDataArray = (array) $storeData[0];
				$currentStoreUrl = $storeDataArray['store_url'];
			}
			foreach ($getColorResponse as $key => $value) {
				$hostname = parse_url($value['sides'][0]['image']['src'], PHP_URL_HOST); //hostname
				$getColorResponse[$key]['sides'][0]['image']['src'] = str_replace($hostname, $currentStoreUrl, $value['sides'][0]['image']['src']);
				$getColorResponse[$key]['sides'][0]['image']['thumbnail'] = str_replace($hostname, $currentStoreUrl, $value['sides'][0]['image']['thumbnail']);
			}
		}
		$combineData = array("combine" => 1, "is_admin" => $isAdmin);
		if (!empty($getColorResponse)) {
			$variantData = $this->getColorSwatchData($getColorResponse, $combineData);
			if(isset($args['return']) && $args['return'] == 1){
                return array_values($variantData);
			}
			$jsonResponse = [
				'status' => 1,
				'records' => count($variantData),
				'data' => array_values($variantData),
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * Getting List of All Attributes
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   31 Jan 2019
	 * @return Array list of colors
	 */
	public function getAttributeList($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Attributes', 'not_found'),
		];
		$attributeResponse = $this->getOnlyAttribute($request, $response);
		if (!empty($attributeResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $attributeResponse,
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * Getting Single Attribute Details by Attribute Id
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   3 Mar 2019
	 * @return Array list of colors
	 */
	public function getAttributeDetails($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Attributes', 'not_found'),
		];
		$attributeResponse = $this->getAttributeTerms($request, $response, $args);
		if (!empty($attributeResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $attributeResponse,
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * Getting List of All Products for tool
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 Feb 2019
	 * @return Array list of Products as per the categories
	 */
	public function getToolProductList($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Products', 'not_found'),
		];
		$productResponse = $this->getToolProducts($request, $response);
		if (!empty($productResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $productResponse,
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * Getting total product count from store
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   6 Feb 2019
	 * @return Number of products
	 */
	public function getTotalProductCount($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$count = $this->totalProductCount($storeId);

		return response(
			$response,
			[
				'data' => $count, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * Getting total product count from store
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   6 Feb 2019
	 * @return json
	 */
	public function variantAttributeDetails($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Attribute quantity details', 'not_found'),
		];
		$attributeName = $this->getAttributeName();
		$colorName = $attributeName['color'];
		$productId = to_int($args['pid']);
		$variationId = to_int($args['vid']);
		$getQuantityDetails = $this->storeVariantAttributeDetails(
			$request, $response, [
				'pid' => to_int($args['pid']), 'vid' => to_int($args['vid']), 'color_name' => $colorName,
			]
		);
		if (!empty($getQuantityDetails)) {
			$jsonResponse = [
				'status' => 1,
				'data' => [
					'quantities' => $getQuantityDetails,
				],
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * Get Print profile, decoration status and product images by product ID
	 *
	 * @param $productId product id
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return array
	 */
	public function getAssocPrintProfiles($productId, $isAdmin = 0, $storeId = 1) {
		$printProfiles = $getSettings = [];
		$isDecorationExists = 0;
		if (!empty($productId)) {
			$productSettings = new \App\Modules\Products\Models\ProductSetting();
			$getSettings = $productSettings->where('product_id', $productId)->where('store_id', $storeId);

			if (!$getSettings->count() > 0) {
				$productSettingRel = new \App\Modules\Products\Controllers\ProductDecorationsController();
				$getProductSettings = $productSettingRel->getSettingsIdByProductId($productId, []);
				if (!empty($getProductSettings)) {
					$productSettings = new \App\Modules\Products\Models\ProductSetting();
					$getSettings = $productSettings->where('xe_id', $getProductSettings->product_setting_id)->where('store_id', $storeId);
				}
			}
			if ($getSettings->count() > 0) {
				$isDecorationExists = 1;
				$productSetting = $getSettings->with(
					'print_profiles', 'print_profiles.profile'
				)->where('store_id', $storeId)
					->first();
				if (!empty($productSetting['print_profiles'])) {
					foreach ($productSetting['print_profiles'] as $profile) {
						if (!empty($profile->profile['xe_id'])
							&& $profile->profile['xe_id']
						) {
							$printProfiles[] = [
								'id' => $profile->profile->xe_id,
								'name' => $profile->profile->name,
							];
						}
					}
				}
			} else {
				if (!$isAdmin) {
					/* Default print profile */
					$printProfiles = $this->getSingleEnbaledPrintProfile($storeId);
				}
			}
		}

		return [
			'print_profiles' => $printProfiles,
			'is_decoration_exists' => $isDecorationExists,
		];
	}

	/**
	 * Get Asssociated images of a product from Database
	 *
	 * @param $productId product id
	 *
	 * @author tanmayap@riaxe.com
	 * @date   26 Mar 2020
	 * @return array
	 */
	public function getAssocProductImages($productId) {
		$assocProdImages = $getSettings = [];
		$isDecorationExists = 0;
		if (!empty($productId)) {
			$productSettings = new \App\Modules\Products\Models\ProductSetting();
			$getSettings = $productSettings->where('product_id', $productId);
			if (!$getSettings->count() > 0) {
				$productSettingRel = new \App\Modules\Products\Controllers\ProductDecorationsController();
				$getProductSettings = $productSettingRel->getSettingsIdByProductId($productId, []);
				if (!empty($getProductSettings)) {
					$productSettings = new \App\Modules\Products\Models\ProductSetting();
					$getSettings = $productSettings->where('xe_id', $getProductSettings->product_setting_id);
				}
			}
			if ($getSettings->count() > 0) {
				$productSetting = $getSettings->with(
					'print_profiles', 'print_profiles.profile'
				)
					->first();
				$prodImgSettRelObj = new \App\Modules\Products\Models\ProductImageSettingsRel();
				$prodImgSettRelInfo = $prodImgSettRelObj->where(
					'product_setting_id', $productSetting['xe_id']
				)
					->first();
				$productImageId = $prodImgSettRelInfo['product_image_id'];
				if ($productImageId > 0) {
					$prodImageSideObj = new \App\Modules\Products\Models\ProductImageSides();
					$getProductImageSideInit = $prodImageSideObj->where(
						'product_image_id',
						$productImageId
					)
						->get();
					$getProductImageSideInit = $prodImageSideObj->where(
						'product_image_id',
						$productImageId
					)
						->get();
					if (!empty($getProductImageSideInit)) {
						foreach ($getProductImageSideInit as $key => $productImage) {
							$assocProdImages[$key] = $productImage->thumbnail;
						}
					}
				}
			}
		}

		return $assocProdImages;
	}

	/**
	 * GET: Get Attribute Pricing Details
	 *
	 * @param $productDetails Product Details
	 * @param $productId      Product Id
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Feb 2020
	 * @return Array of pricing
	 */
	public function getPriceDetails($productDetails, $productId) {
		$productAttributes = [];
		if (!empty($productDetails) && !empty($productId)) {
			$attributeName = $this->getAttributeName();
			foreach ($productDetails as $attrKey => $attributes) {
				$attrDetails = $attrTerms = [];
				if (!empty($attributes)) {
					foreach ($attributes['options'] as $termKey => $termValue) {
						$priceInit = new \App\Modules\Products\Models\AttributePriceRule();
						$getPriceData = $priceInit->where(['product_id' => $productId, 'attribute_id' => $attributes['id'], 'attribute_term_id' => $termValue['id']])
							->select('print_profile_id', 'price');
						$priceData = [];
						if (isset($getPriceData) && $getPriceData->count() > 0) {
							foreach ($getPriceData->get() as $priceKey => $priceValue) {
								$priceData[$priceKey] = [
									'print_profile_id' => $priceValue->print_profile_id,
									'price' => $priceValue->price,
								];
							}
						}
						$attrDetails = [
							'id' => $termValue['id'],
							'name' => $termValue['name'],
						];
						if (!empty($attributeName['color'])) {
							if (!empty($attributes['name'])
								&& $attributes['name'] === $attributeName['color']
							) {
								if (STORE_NAME == 'Prestashop') {
									$attrDetails['hex_code'] = $termValue['hex_code'];
									$attrDetails['file_name'] = $termValue['file_name'];
								} else {
									$colorSwatchInit = new \App\Modules\Settings\Models\ColorSwatch();
									$getTermLocalData = $colorSwatchInit->where('attribute_id', $termValue['id'])
										->first();
									$attrDetails['hex_code'] = $attrDetails['file_name'] = "";
									if (!empty($getTermLocalData['hex_code'])
										&& $getTermLocalData['hex_code'] != ""
									) {
										$attrDetails['hex_code'] = $getTermLocalData['hex_code'];
									}
									if (!empty($getTermLocalData['file_name'])
										&& $getTermLocalData['file_name'] != ""
									) {
										$attrDetails['file_name'] = $getTermLocalData['file_name'];
									}
								}
							}
						}
						$attrDetails['price_data'] = $priceData;
						$attrTerms[$termKey] = $attrDetails;
					}
				}
				$productAttributes[$attrKey] = [
					'id' => $attributes['id'],
					'name' => $attributes['name'],
					'attribute_term' => $attrTerms,
				];
			}
		}

		return $productAttributes;
	}

	/**
	 * GET: Get Multiple Attributes Variant Details
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument object
	 * @param $attribute     Attribute Name
	 *
	 * @author malay@riaxe.com
	 * @date   10th April 2020
	 * @return json
	 */
	public function multiAttributeVariantDetails($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Attribute quantity details', 'not_found'),
		];
		$attributeName = $_REQUEST['attribute'];
		$productId = to_int($args['pid']);
		$variationId = to_int($args['vid']);
		$price = isset($_REQUEST['price']) ? $_REQUEST['price'] : 0;
		$isAttribute = $request->getQueryParam('is_attribute') ? $request->getQueryParam('is_attribute') : 0;
		$getAttributes = $this->getAttributeName();
		$colorAttributeSlug = $getAttributes['color'];
		$getQuantityDetails = $this->storeMultiAttributeVariantDetails(
			$request, $response, [
				'pid' => to_int($args['pid']), 'vid' => to_int($args['vid']), 'attribute' => $attributeName, 'price' => $price, 'isAttribute' => $isAttribute,
			]
		);
		if (!empty($getQuantityDetails)) {
			if($colorAttributeSlug == $attributeName) {
                $getColorResponse = $this->colorsByProductId($request, $response, ["id" => $productId, "return" => 1]);
                foreach($getQuantityDetails[$attributeName] as $key => $value){
                    $attrKey = array_search(
                        $value['variant_id'],
                        array_column($getColorResponse, 'variant_id')
                    );
                    if(!is_bool($attrKey)) {
                        $getQuantityDetails[$attributeName][$key]['attribute_id'] = $getColorResponse[$attrKey]['attribute_id'];
                        $getQuantityDetails[$attributeName][$key]['hex_code'] = $getColorResponse[$attrKey]['hex_code'];
                        $getQuantityDetails[$attributeName][$key]['file_name'] = $getColorResponse[$attrKey]['file_name'];
                        $getQuantityDetails[$attributeName][$key]['color_type'] = $getColorResponse[$attrKey]['color_type'];
                    }
                }       
            }

			$jsonResponse = [
				'status' => 1,
				'quantities' => $getQuantityDetails,
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}

	public function getShopifyParentProduct($request, $response, $args) {
		$thisVarID = $args['vid'];
		return $this->getParentProductData($thisVarID);
	}

	/**
	 * Get: Get Product Variant
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author radha@riaxe.com
	 * @date   23 Jul 2020
	 * @return A JSON Response
	 */
	public function getProductVariant($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Store Product Variant', 'not_found'),
		];
		$variantResponse = $this->storeProductVariant($request, $response);
		if (!empty($variantResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $variantResponse['variant_id'],
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * GET: Get All product variants of a product
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument object
	 *
	 * @author debashisd@riaxe.com
	 * @date   23rd July 2020
	 * @return json
	 */
	public function getProductVariants($request, $response, $args, $returnType = 0) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('variants of the products', 'not_found'),
		];
		$productId = to_int($args['pid']);
		$productVariants = $this->productVariants(
			$request, $response, [
				'productID' => $productId,
			]
		);
		if (!empty($productVariants)) {
			$jsonResponse = [
				'status' => 1,
				'variants' => $productVariants,
			];
		}
		if ($returnType == 1) {
			return $productVariants;
		}

		return response(
			$response,
			[
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * GET: Get All product variants of a product
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument object
	 *
	 * @author debashisd@riaxe.com
	 * @date   23rd July 2020
	 * @return json
	 */
	public function getTierPricing($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('tier Pricing rules', 'not_found'),
		];
		$productId = to_int($args['pid']);
		$tierDiscounts = $this->productTierDiscounts(
			$request, $response, [
				'productID' => $productId,
			]
		);
		if (!empty($tierDiscounts)) {
			$jsonResponse = $tierDiscounts;
		}

		return response(
			$response,
			[
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * POST: Save product tier Discount Rules
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument object
	 *
	 * @author debashisd@riaxe.com
	 * @date   23rd July 2020
	 * @return json
	 */
	public function saveTierPricing($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('tier Pricing rules', 'not_found'),
		];
		$productId = to_int($args['pid']);
		$saveTierDiscount = $this->saveTierDiscount(
			$request, $response, [
				'productID' => $productId,
			]
		);
		if ($saveTierDiscount) {
			$jsonResponse = [
				'status' => 1,
				'message' => "Tier price Discounts are saved for this product",
			];
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * GET: Get Single Print Profile Record
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's arg object
	 *
	 * @author radhanatham@riaxe.com
	 * @date   8 Dec 2020
	 * @return A JSON Response
	 */
	private function getSingleEnbaledPrintProfile($storeId) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [];
		$printProfileInit = new PrintProfileModels\PrintProfile();
		$getPrintProfileInfo = $printProfileInit->where(
			[
				'is_disabled' => 0,
				'store_id' => $storeId,
			]
		)->first();

		// Check if print profile exist in this ID
		if (!empty($getPrintProfileInfo->xe_id)) {
			$jsonResponse[] = [
				'id' => $getPrintProfileInfo->xe_id,
				'name' => $getPrintProfileInfo->name,
			];
		}
		return $jsonResponse;

	}

	/**
	 * GET: Getting List of All Category/Subcategory or Single
	 * Category/Subcategory information
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author mukeshp@riaxe.com
	 * @date   1 March 2021
	 * @return A JSON Response
	 */
	public function CategoriesSubcatagories($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = [
			'status' => 0,
			'data' => [],
		];
		$apiSource = $request->getQueryParam('src');
		// get selected categories only
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$settingLocation = path('abs', 'setting') . 'stores/' . $storeId;
		$jsonFilePath = $settingLocation . '/product_categories.json';
		if (file_exists($jsonFilePath)) {
			$logContent = json_decode(file_get_contents($jsonFilePath), true);
			$selectedCatagories = array_column($logContent['data'], 'id');
		}
		$storeResponse = $this->getCategoriesSubcategories($request, $response, $args);
		$categoriesForTool = array();
		foreach ($storeResponse as $key => $category) {
			$storeResponse[$key]['show_in_tool'] = false;
			if (in_array($category['id'], $selectedCatagories)) {
				$storeResponse[$key]['show_in_tool'] = true;
				$categoriesForTool[] = $category;
			}
		}
		if ($apiSource == 'tool' && !empty($selectedCatagories)) {
			$storeResponse = $categoriesForTool;
		}
		if (!empty($storeResponse)) {
			$storeResponse = [
				'status' => 1,
				'data' => $storeResponse,
			];
		} else {
			$storeResponse = [
				'status' => 0,
				'data' => [],
			];
		}

		return response(
			$response,
			[
				'data' => $storeResponse, 'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * Post: Create Product Catagory
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author mukeshp@riaxe.com
	 * @date   2 March 2021
	 * @return Save response Array
	 */
	public function saveCategories($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Catagories', 'error'),
		];
		$categoryResponse = $this->createProductCatagories($request, $response);
		if (!empty($categoryResponse)) {
			$jsonResponse = $categoryResponse;
		}

		return response(
			$response,
			[
				'data' => $jsonResponse,
				'status' => $serverStatusCode,
			]
		);
	}

	/**
     * Delete: Delete Product Catagories(s)
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   3 Mar 2021
     * @return json
     */
    public function deleteCategories($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $storeResponse = [
            'status' => 0,
            'message' => message('Catagories Delete', 'error'),
        ];
        if (!empty($args['id'])) {
        	$storeResponse = $this->removeCategories($request, $response, $args);
        }

        return response(
            $response, ['data' => $storeResponse, 'status' => $serverStatusCode]
        );
	}
	/**
     * GET: Product decoration type
     *
     * @param $productId
     * @param $storeId
     *
     * @author soumyas@riaxe.com
     * @date   24 May 2021
     * @return string
     */
    public function getProductDecorationType($productId , $storeId) {
    	$decorationType = '';
    	$getSettingsInit = new ProductSetting;
		$settingDetails = $getSettingsInit->select('decoration_type')->where(['product_id' => $productId,'store_id' => $storeId]);
		if ($settingDetails->count() > 0){
			$settingDetailsData = $settingDetails->get()->toArray();
			$decorationType = $settingDetailsData[0]['decoration_type'] ? $settingDetailsData[0]['decoration_type']:'';
		}
		return $decorationType;
    }
}
