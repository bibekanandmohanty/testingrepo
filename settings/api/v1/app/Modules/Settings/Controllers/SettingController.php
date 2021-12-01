<?php
/**
 * Manage Color Swatches
 *
 * PHP version 5.6
 *
 * @category  Settings
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Settings\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Settings\Models\Currency;
use App\Modules\Settings\Models\Language;
use App\Modules\Settings\Models\QuotationDynamicForm;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Models\Unit;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Setting Controller
 *
 * @category Class
 * @package  Setting
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class SettingController extends ParentController {
	/**
	 * The css file name which will be used to store Css from frontend
	 *
	 * @var string
	 */
	protected $cssFileName = 'style.css';

	/**
	 * GET: List of Currency
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Aug 2019
	 * @return All/Single Currency List
	 */
	public function getCurrencyValues($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Currency', 'not_found'),
		];
		$getStoreDetails = get_store_details($request);
		if (file_exists(path('abs', 'setting') . 'stores/' . $getStoreDetails['store_id'] . '/currencies.json')) {
			$currenyJsonLocation = path('abs', 'setting') . 'stores/' . $getStoreDetails['store_id'] . '/currencies.json';
			$currencyData = file_get_contents($currenyJsonLocation);
			if (!empty($currencyData)) {
				$jsonResponse = [
					'status' => 1,
					'data' => json_clean_decode($currencyData, true),
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: List of Unit
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Aug 2019
	 * @return All/Single Unit List
	 */
	public function getUnitValues($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Unit', 'not_found'),
		];
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$unitInit = new Unit();
		$getUnits = $unitInit->where('xe_id', '>', 0)->where('store_id', '=', $storeId)
			->orderBy('xe_id', 'DESC')
			->get();
		if ($unitInit->count() > 0) {
			$jsonResponse = [
				'status' => 1,
				'data' => $getUnits,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Save Setting
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Dec 2019
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveSettings($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$getStoreDetails = get_store_details($request);
		$settingLastInsertId = 0;
		$msg = '';
		$jsonResponse = [
			'status' => 0,
			'message' => message('Settings', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (isset($allPostPutVars['settings'])
			&& $allPostPutVars['settings'] != ""
		) {
			$settingsArray = json_clean_decode($allPostPutVars['settings'], true);
			$settingsType = $settingsArray['setting_type'];
			$settingInit = new Setting();
			$settingInit->where('type', $settingsType)->where('store_id', $getStoreDetails['store_id'])
				->delete();
			$rvnNumber = $this->updateRevisionNo($getStoreDetails['store_id']);
			if ($settingsType == 1) {
				if (!empty($settingsArray['general']['unit_details'])) {
					$unitDetails = $settingsArray['general']['unit_details'];
					foreach ($unitDetails as $unitKey => $unitValue) {
						$updateData = [
							'label' => $unitValue['label'],
							'is_default' => $unitValue['is_default'],
						];
						$unitInit = new Unit();
						$unitInit->where('xe_id', '=', $unitValue['xe_id'])->where('store_id', '=', $getStoreDetails['store_id'])
							->update($updateData);
					}
					unset($settingsArray['general']['unit_details']);
				}
				foreach ($settingsArray['general'] as $generalKey => $generalValue) {
					$settingsData = [
						'setting_key' => $generalKey,
						'setting_value' => !is_array($generalValue)
						? $generalValue : json_encode($generalValue),
						'type' => $settingsType,
						'store_id' => $getStoreDetails['store_id'],
					];
					$saveSettingsData = new Setting($settingsData);
					if ($saveSettingsData->save()) {
						$settingLastInsertId = $saveSettingsData->xe_id;
						$msg = 'General Settings';
					}
				}
			} elseif ($settingsType == 2) {
				$themeColor = "";
				$themeHoverColor = "";
				$themeBackgroundColor = "";
				foreach ($settingsArray['appearance'] as $appearanceKey => $appearanceValue) {
					if ($appearanceValue != ''
						&& $appearanceKey == 'custom_css'
					) {
						$customCssPath = rtrim(RELATIVE_PATH, WORKING_DIR) . '/' . 'custom.css';
						write_file($customCssPath, urldecode($appearanceValue));
						$cssSettingLocation = path(
							'abs', 'setting'
						) . $this->cssFileName;
						write_file($cssSettingLocation, $appearanceValue);
						$appearanceValue = $this->cssFileName;
					}
					$settingsData = [
						'setting_key' => $appearanceKey,
						'setting_value' => !is_array($appearanceValue)
						? $appearanceValue : json_encode($appearanceValue),
						'type' => $settingsType,
						'store_id' => $getStoreDetails['store_id'],
					];
					$saveSettingsData = new Setting($settingsData);
					if ($saveSettingsData->save()) {
						$settingLastInsertId = $saveSettingsData->xe_id;
						$msg = 'Appearance Settings';
					}
					if ($appearanceKey === "theme_color") {
						$themeColor = $appearanceValue;
					}
					if ($appearanceKey === "theme_hover_color") {
						$themeHoverColor = $appearanceValue;
					}
					if ($appearanceKey === "theme_background_color") {
						$themeBackgroundColor = $appearanceValue;
					}
				}
				// Save design value in chunk file.
				$this->replceInAdminFile($themeColor, $themeHoverColor, $themeBackgroundColor, $rvnNumber);
			} elseif ($settingsType == 3) {
				foreach ($settingsArray['image_setting'] as $imageKey => $imageValue) {
					$settingsData = [
						'setting_key' => $imageKey,
						'setting_value' => !is_array($imageValue)
						? $imageValue : json_encode($imageValue),
						'type' => $settingsType,
						'store_id' => $getStoreDetails['store_id'],
					];
					$saveSettingsData = new Setting($settingsData);
					if ($saveSettingsData->save()) {
						$settingLastInsertId = $saveSettingsData->xe_id;
						$msg = 'Image Settings';
					}
				}
			} elseif ($settingsType == 4) {
				if (!empty($settingsArray['store_setting'])) {
					foreach ($settingsArray['store_setting'] as $storeKey => $storeValue) {
						$settingsData = [
							'setting_key' => $storeKey,
							'setting_value' => !is_array($storeValue)
							? $storeValue : json_encode($storeValue),
							'type' => $settingsType,
							'store_id' => $getStoreDetails['store_id'],
						];
						$saveSettingsData = new Setting($settingsData);
						if ($saveSettingsData->save()) {
							$settingLastInsertId = $saveSettingsData->xe_id;
							$msg = 'Store Settings';
						}
					}
				}
				//store product categories selected by admin in the product_categories.json
				if (!empty($settingsArray['product_categories'])) {
					$categoryData = array('time_stamp' => time(), 'data' => array());
					foreach ($settingsArray['product_categories'] as $productCat) {
						$thisCategory['id'] = $productCat['id'];
						$thisCategory['name'] = $productCat['name'];
						$categoryData['data'][] = $thisCategory;
					}
					$settingLocation = path('abs', 'setting') . 'stores/' . $getStoreDetails['store_id'];
					// create directory if not exists
					create_directory($settingLocation);
					$jsonFilePath = $settingLocation . '/product_categories.json';
					if (!file_exists($jsonFilePath)) {
						$logCatFile = fopen($jsonFilePath, "w");
					}
					$writeStatus = write_file($jsonFilePath, json_encode($categoryData));
					$settingLastInsertId = 1;
					$msg = "product categories saved.";
				}
			} elseif ($settingsType == 5) {
				foreach ($settingsArray['cart'] as $cartKey => $cartValue) {
					$settingsData = [
						'setting_key' => $cartKey,
						'setting_value' => !is_array($cartValue)
						? $cartValue : json_encode($cartValue),
						'type' => $settingsType,
						'store_id' => $getStoreDetails['store_id'],
					];
					$saveSettingsData = new Setting($settingsData);
					if ($saveSettingsData->save()) {
						$dynamicFormInit = new QuotationDynamicForm();
						$dynamicFormInit->where('xe_id', '>', 0)->where('store_id', $getStoreDetails['store_id'])->delete();
						if (!empty($settingsArray['request_quote'])) {
							foreach ($settingsArray['request_quote'] as $quoteKey => $quoteValue) {
								$value = "";
								if (!empty($quoteValue['value'])) {
									$value = !is_array($quoteValue['value'])
									? $quoteValue['value']
									: json_encode($quoteValue['value']);
								}
								$formData = [
									'label' => $quoteValue['label'],
									'label_slug' => $quoteValue['label_slug'],
									'attribute_id' => $quoteValue['attribute_id'],
									'placeholder' => $quoteValue['placeholder'],
									'value' => $value,
									'is_required' => $quoteValue['is_required'],
									'sort_order' => $quoteValue['sort_order'],
									'store_id' => $getStoreDetails['store_id'],
									'is_default' => isset($quoteValue['is_default']) && $quoteValue['is_default'] != '' ? $quoteValue['is_default'] : 0,
								];
								$dynamicFormData = new QuotationDynamicForm($formData);
								$dynamicFormData->save();
							}
						}
						$settingLastInsertId = $saveSettingsData->xe_id;
						$msg = 'Cart Settings';
					}
				}
			} elseif ($settingsType == 6) {
				$uploadedFiles = $request->getUploadedFiles();
				$uploadingFilesNo = count($uploadedFiles['upload']);
				if ($uploadingFilesNo) {
					$orderSettingPath = path('abs', 'setting') . 'order_setting/' . $getStoreDetails['store_id'];
					if (is_dir($orderSettingPath)) {
						delete_directory($orderSettingPath);
					}
					create_directory($orderSettingPath);
					$allFileName = do_upload(
						'upload', $orderSettingPath, [100], 'array'
					);
				}
				foreach ($settingsArray['order_setting'] as $orderSettingKey => $orderSettingValue) {
					$settingsData = [
						'setting_key' => $orderSettingKey,
						'setting_value' => !is_array($orderSettingValue)
						? $orderSettingValue : json_encode($orderSettingValue),
						'type' => $settingsType,
						'store_id' => $getStoreDetails['store_id'],
					];
					$saveSettingsData = new Setting($settingsData);
					if ($saveSettingsData->save()) {
						$dynamicFormInit = new QuotationDynamicForm();
						$dynamicFormInit->where('xe_id', '>', 0)->delete();
						if (!empty($settingsArray['request_quote'])) {
							foreach ($settingsArray['request_quote'] as $quoteKey => $quoteValue) {
								$formData = [
									'label' => $quoteValue['label'],
									'label_slug' => $quoteValue['label_slug'],
									'attribute_id' => $quoteValue['attribute_id'],
									'placeholder' => $quoteValue['placeholder'],
									'value' => json_encode($quoteValue['value']),
									'is_required' => $quoteValue['is_required'],
									'sort_order' => $quoteValue['sort_order'],
									'is_default' => isset($quoteValue['is_default']) && $quoteValue['is_default'] != '' ? $quoteValue['is_default'] : 0,
								];
								$dynamicFormData = new QuotationDynamicForm($formData);
								$dynamicFormData->save();
							}
						}
						$settingLastInsertId = $saveSettingsData->xe_id;
						$msg = 'Order Settings';
					}
				}
			} elseif ($settingsType == 7){
                $uploadedLogo = "";
                $uploadedFiles = $request->getUploadedFiles();
                $uploadingFilesNo = count($uploadedFiles['logo']);
                if ($uploadingFilesNo) {
                    $kioskSettingPath = path('abs', 'setting') . 'kiosk_setting';
                    $kioskSplashPath = path('abs', 'setting') . 'kiosk_splash';
                    if (is_dir($kioskSettingPath)) {
                        delete_directory($kioskSettingPath);
                    }
                    create_directory($kioskSettingPath);
                    $uploadedLogo = do_upload(
                        'logo', $kioskSettingPath, '', 'string'
                    );
                    if (count($uploadedFiles['background'])) {
                        $uploadedBG = do_upload(
                            'background', $kioskSplashPath, '', 'string'
                        );
                    }
                }
                foreach ($settingsArray['kiosk_setting'] as $kioskKey => $kioskValue) {
                    $settingInit->where('setting_key', $kioskKey)->delete();
                    $settingsData = [
                        'setting_key' => $kioskKey,
                        'setting_value' => !is_array($kioskValue)
                            ? $kioskValue : json_encode($kioskValue),
                        'type' => $settingsType,
                        'store_id' => $getStoreDetails['store_id'],
                    ];
                    $saveSettingsData = new Setting($settingsData);
                    if ($saveSettingsData->save()) {
                        $settingLastInsertId = $saveSettingsData->xe_id;
                        $msg = 'Kiosk Settings';
                    }
                }
                if (!empty($settingsArray['kiosk_payment'])) {
                    foreach ($settingsArray['kiosk_payment'] as $paymentKey => $tokens) {
                        if (!empty($tokens) || $paymentKey == 'offline_payment') {
                            $settingInit->where('setting_key', $paymentKey)->delete();
                            $paymentData = [
                                'setting_key' => $paymentKey,
                                'setting_value' => $tokens,
                                'type' => $settingsType,
                                'store_id' => $getStoreDetails['store_id'],
                            ];
                            $saveSettingsData = new Setting($paymentData);
                            if ($saveSettingsData->save()) {
                                $settingLastInsertId = $saveSettingsData->xe_id;
                                $msg = 'Kiosk Settings';
                            }
                        }
                    }
                }
            }elseif ($settingsType == 8){
                $templateSettings = [
                        'setting_key' => 'template_products_rel',
                        'setting_value' => !is_array($settingsArray['template_product_relation'])
                        ? $settingsArray['template_product_relation'] : json_encode($settingsArray['template_product_relation']),
                        'type' => $settingsType,
                        'store_id' => $getStoreDetails['store_id'],
                    ];
                $saveSettingsData = new Setting($templateSettings);
                if ($saveSettingsData->save()) {
                    $settingLastInsertId = $saveSettingsData->xe_id;
                    $msg = 'Template Settings';
                }

            }
			// After save data, write data to json file
			if ($this->_writeOnJsonFile($getStoreDetails['store_id'])
				&& $settingLastInsertId > 0 && $msg != ''
			) {
				$jsonResponse = [
					'status' => 1,
					'message' => message($msg, 'saved'),
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Settings JSOn
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return JSON
	 */
	public function getSettings($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Settings', 'not_found'),
		];
		$generalSettings = [];
		$appearanceSettings = [];
		$imageSetting = [];
		$storeSetting = [];
		$cartSetting = [];
		$orderSetting = [];
		$kioskSetting = [];
		// Get Layout Details
		$getLayouts = DB::table('layouts')->get();
		foreach ($getLayouts as $layout) {
			$layoutDetails[] = [
				'layout_id' => $layout->xe_id,
				'image_url' => path('read', 'setting') . '' . $layout->file_name,
			];
		}
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$settingInit = new Setting();
		$getSettings = $settingInit->where('type', '>', 0)->where('store_id', '=', $storeId);
		if ($getSettings->count() > 0) {
			$data = $getSettings->get();
			$typeValue = [];
			foreach ($data as $value) {
				if ($value['type'] == 1) {
					$generalSettings[$value['setting_key']] = json_clean_decode(
						$value['setting_value'], true
					) ? json_clean_decode(
						$value['setting_value'], true
					) : $value['setting_value'];
				} elseif ($value['type'] == 2) {
					$pathInfo = pathinfo(
						$value['setting_value'], PATHINFO_EXTENSION
					);
					if ($pathInfo == 'css') {
						$cssData = "";
						$cssFilePath = path('read', 'setting')
						. str_replace('"', '', $value['setting_value']);
						$cssAbsPath = path('abs', 'setting')
						. str_replace('"', '', $value['setting_value']);
						if (file_exists($cssAbsPath)) {
							$myfile = fopen($cssAbsPath, "r");
							$cssData = fgets($myfile);
							fclose($myfile);
						}
						$appearanceSettings[$value['setting_key']] = $cssData;
					} else {
						$appearanceSettings[$value['setting_key']] = json_clean_decode(
							$value['setting_value'], true
						) ? json_clean_decode(
							$value['setting_value'], true
						) : $value['setting_value'];
					}
				} elseif ($value['type'] == 3) {
					$imageSetting[$value['setting_key']] = json_clean_decode(
						$value['setting_value'], true
					) ? json_clean_decode(
						$value['setting_value'], true
					) : $value['setting_value'];
				} elseif ($value['type'] == 4) {
					$storeSetting[$value['setting_key']] = json_clean_decode(
						$value['setting_value'], true
					) ? json_clean_decode(
						$value['setting_value'], true
					) : $value['setting_value'];
				} elseif ($value['type'] == 5) {
					$cartSetting[$value['setting_key']] = json_clean_decode(
						$value['setting_value'], true
					) ? json_clean_decode(
						$value['setting_value'], true
					) : $value['setting_value'];
				} elseif ($value['type'] == 6) {
					$packingSlipLogo = '';
					$packingSlipLogo = $this->getPackingSlipLogo($storeId);
					$orderSetting['packing_slip_logo'] = $packingSlipLogo;
					$orderSetting[$value['setting_key']] = json_clean_decode(
						$value['setting_value'], true
					) ? json_clean_decode(
						$value['setting_value'], true
					) : $value['setting_value'];
				} elseif ($value['type'] == 7) {
                    $kioskLogo = $kioskSplash = "";
                    $kioskLogo = $this->getKioskLogo();
                    $kioskSplash = $this->getKioskLogo('splash');
                    $macId = base64_encode($this->getMacId());
                    $kioskSetting['logo'] = $kioskLogo;
                    $kioskSetting['background'] = $kioskSplash;
                    $kioskSetting['s2sz051Rhj'] = $macId;
                    if ($value['setting_key'] != 'secret_key') {
                        $kioskSetting[$value['setting_key']] = json_clean_decode(
                            $value['setting_value'], true
                        ) ? json_clean_decode(
                            $value['setting_value'], true
                        ) : $value['setting_value'];
                    }else{
                        $secret = '';
                        if (!empty($value['setting_value'])) {
                           $secret = 'saved';
                        }
                        $kioskSetting[$value['setting_key']] = $secret;
                    }
                } elseif ($value['type'] == 8) {
                    $templateProductData = json_clean_decode($value['setting_value'], true);
                }
			}
			// Append Layout Master data to the key
			$appearanceSettings['layouts'] = $layoutDetails;
			$typeValue = [
				'general_settings' => $generalSettings,
				'appearance_settings' => $appearanceSettings,
				'image_setting' => $imageSetting,
				'store_setting' => $storeSetting,
				'cart_setting' => $cartSetting,
				'order_setting' => $orderSetting,
				'kiosk_setting' => $kioskSetting,
                'template_product_relation' => $templateProductData,
			];

			$jsonResponse = [
				'status' => 1,
			];
			$jsonResponse += $typeValue;
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Order seeting packing slip logo
	 *
	 *
	 * @author radhanatham@riaxe.com
	 * @date   28 May 2020
	 * @return String
	 */
	private function getPackingSlipLogo($storeId) {
		$packingSlipLogo = '';
		$orderSettingPath = path('abs', 'setting') . 'order_setting/' . $storeId;
		if (is_dir($orderSettingPath)) {
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
	 * POST: Save Language
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   12 Dec 2019
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveLanguage($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$jsonResponse = [
			'status' => 0,
			'message' => message('Language', 'error'),
		];
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		if (isset($allPostPutVars['name']) && $allPostPutVars['name'] != "") {
			$languageInit = new Language();
			$checkLanguage = $languageInit->where(
				[
					'name' => $allPostPutVars['name'], 'type' => $allPostPutVars['type'],
				]
			)
				->get();
			if ($checkLanguage->count() > 0) {
				$jsonResponse = [
					'status' => 0,
					'message' => 'This language is present. Please add another.',
				];
			} else {
				$languageInit = new Language();
				$getLanguage = $languageInit->where(
					[
						'is_default' => 1, 'type' => $allPostPutVars['type'],
					]
				);
				$defaultLanguage = $getLanguage->first();
				$languageFileName = 'lang_' . strtolower($allPostPutVars['name'])
					. '.json';
				$languagePath = $allPostPutVars['type'] . '/' . $languageFileName;
				$jsonlanguageLocation = path('abs', 'language') . $languagePath;
				write_file(
					$jsonlanguageLocation, file_get_contents(
						$defaultLanguage['file_name']
					)
				);
				$allPostPutVars += ['file_name' => $languageFileName];
				$allPostPutVars += ['store_id' => $getStoreDetails['store_id']];
				$getUploadedFlagName = do_upload(
					'flag', path('abs', 'language') . $allPostPutVars['type'] . '/', [], 'string'
				);
				if (!empty($getUploadedFlagName) && $getUploadedFlagName != null) {
					$allPostPutVars['flag'] = $getUploadedFlagName;
				}
				$languageInit = new Language($allPostPutVars);
				if ($languageInit->save()) {
					// After save data, write data to json file
					if ($this->_writeOnJsonFile($getStoreDetails['store_id'])) {
						$jsonResponse = [
							'status' => 1,
							'message' => message('Language', 'saved'),
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
	 * PUT: Update a language
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Aug 2019
	 * @return json response wheather data is updated or not
	 */
	public function updateLanguage($request, $response, $args) {
		$jsonResponse = [
			'status' => 1,
			'message' => message('Language', 'error'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$updateData = [];
		if (!empty($args) && $args['id'] != '' && $args['id'] > 0) {
			$languageId = $args['id'];
			if (isset($allPostPutVars['value']) && $allPostPutVars['value'] != "") {
				$languageFileName = 'lang_' . strtolower(
					$allPostPutVars['name']
				) . '.json';
				$languagePath = $allPostPutVars['type'] . '/' . $languageFileName;
				$jsonlanguageLocation = path('abs', 'language') . $languagePath;
				write_file($jsonlanguageLocation, $allPostPutVars['value']);
				$updateData += ['file_name' => $languageFileName];
			}
			// delete old file
			$this->deleteOldFile(
				'languages', 'flag', ['xe_id' => $languageId], path(
					'abs', 'language'
				) . $allPostPutVars['type'] . '/'
			);
			$getUploadedFlagName = do_upload(
				'flag', path('abs', 'language') . $allPostPutVars['type'] . '/', [], 'string'
			);
			if (!empty($getUploadedFlagName) && $getUploadedFlagName != null) {
				$updateData += ['flag' => $getUploadedFlagName];
			}
			$languageInit = new Language();
			$languageInit->where('xe_id', '=', $languageId)
				->update($updateData);
			$jsonResponse = [
				'status' => 1,
				'message' => message('Language', 'updated'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Settings JSOn
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return JSON
	 */
	public function getLanguage($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Language', 'not_found'),
		];
		$type = $request->getQueryParam('type');
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$languageInit = new Language();
		if (isset($type) && $type != "") {
			$getLanguages = $languageInit->where('type', '=', $type)->where('store_id', '=', !empty($request->getQueryParam('store_id')) ? $getStoreDetails['store_id'] : $getStoreDetails['store_id']);
		} else {
			$getLanguages = $languageInit->where('xe_id', '>', 0);
		}
		if (!empty($args) && $args['id'] != '' && $args['id'] > 0) {
			$finalData = [];
			$languageInit = new Language();
			$getLanguage = $getLanguages->where(
				[
					'xe_id' => $args['id'], 'store_id' => $getStoreDetails['store_id'],
				]
			)
				->first();
			$finalData['data'] = [$getLanguage];
		} else {
			$finalData['data'] = $getLanguages->where(
				['store_id' => $getStoreDetails['store_id']]
			)
				->orderBy('xe_id', 'asc')
				->get();
		}
		if ($languageInit->count() > 0) {
			$jsonResponse = [
				'status' => 1,
				'data' => $finalData['data'],
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * DELETE: Delete Language
	 *
	 * @param $request  Slim's Argument parameters
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return json response wheather data is deleted or not
	 */
	public function deleteLanguage($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'message' => message('Language', 'error'),
		];
		if (!empty($args) && $args['id'] != '' && $args['id'] > 0) {
			$languageUpdateId = $args['id'];
			$languageInit = new Language();
			if ($languageInit->where(['xe_id' => $languageUpdateId])->count() > 0) {
				$languageInit = $languageInit->find($languageUpdateId);
				$this->deleteOldFile(
					'languages', 'file', [
						'xe_id' => $languageUpdateId,
					], path('abs', 'language')
				);
				if ($languageInit->delete()) {
					$jsonResponse = [
						'status' => 1,
						'message' => message('Language', 'deleted'),
					];
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Default Language
	 *
	 * @param $request  Slim's Argument parameters
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return json message
	 */
	public function defaultLanguage($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$getStoreDetails = get_store_details($request);
		$jsonResponse = [
			'status' => 0,
			'message' => message('Language', 'error'),
		];
		if (!empty($args) && $args['id'] != '' && $args['id'] > 0) {
			$type = $request->getQueryParam('type');
			$languageInit = new Language();
			$languageInit->where(['type' => $type])
				->update(['is_default' => 0]);
			$languageInit = new Language();
			$languageInit->where(['xe_id' => $args['id'], 'type' => $type])
				->update(['is_default' => 1, 'is_enable' => 1]);
			$this->_writeOnJsonFile($getStoreDetails['store_id']);
			$jsonResponse = [
				'status' => 1,
				'message' => message('Language', 'done'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Enable/Disable Multi Language
	 *
	 * @param $request  Slim's Argument parameters
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return json message
	 */
	public function resetMultiLanguage($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [];
		$getStoreDetails = get_store_details($request);
		$type = $request->getQueryParam('type');
		$languageInit = new Language();
		try {
			$languageInit->where(['is_default' => 0, 'type' => $type])
				->update(['is_enable' => 0]);
			$this->_writeOnJsonFile($getStoreDetails['store_id']);
			$jsonResponse = [
				'status' => 1,
				'message' => message('Language', 'done'),
			];
		} catch (\Exception $e) {
			$jsonResponse = [
				'status' => 0,
				'message' => message('Language', 'error'),
				'exception' => show_exception() === true ? $e->getMessage() : '',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Enable Single Language
	 *
	 * @param $request  Slim's Argument parameters
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return json message
	 */
	public function enableLanguage($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$getStoreDetails = get_store_details($request);
		$jsonResponse = [
			'status' => 0,
			'message' => message('Language', 'error'),
		];

		if (!empty($args) && $args['id'] != '' && $args['id'] > 0) {
			$languageInit = new Language();
			$language = $languageInit->find($args['id']);
			$language->is_enable = !$language->is_enable;

			if ($language->save()) {
				$this->_writeOnJsonFile($getStoreDetails['store_id']);
				$jsonResponse = [
					'status' => 1,
					'message' => message('Language', 'done'),
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Change theme color in chunk file
	 *
	 * @param $themeColor      Theme Color from settingsthemeHoverColor
	 * @param $themeHoverColor Theme Hover Color from settings
	 *
	 * @author satyabratap@riaxe.com
	 * @date   04 May 2020
	 * @return json message
	 */
	public function replceInAdminFile($themeColor, $themeHoverColor, $themeBackgroundColor, $rvnNumber) {
		$staticFolderPath = rtrim(RELATIVE_PATH, WORKING_DIR) . '/static/css/';
		$fileNames = scandir($staticFolderPath);
		foreach ($fileNames as $fileName) {
			list($firstWord) = explode('.', $fileName);
			if ($firstWord == "main") {
				$filePath = $staticFolderPath . '/' . $fileName;
				if ($themeColor != "") {
					$fileContents = file_get_contents($filePath);
					$colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.imageWrap-box:hover .nf{color:')), ';}'));
					$colorCode = $colorData[1];
					$fileContents = str_replace($colorCode, $themeColor, $fileContents);
					file_put_contents($filePath, $fileContents);
				}
				if ($themeHoverColor != "") {
					$fileContents = file_get_contents($filePath);
					$colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.dropdown-item.active{background-color:')), ';}'));
					$colorCode = $colorData[1];
					$fileContents = str_replace($colorCode, $themeHoverColor, $fileContents);
					file_put_contents($filePath, $fileContents);
				}
				if ($themeBackgroundColor != "") {
					$fileContents = file_get_contents($filePath);
					$colorData = explode('color:', strtok(substr($fileContents, strpos($fileContents, '.btn-success:focus,.btn-success:hover{background-color:')), ';}'));
					$colorCode = $colorData[1];
					$fileContents = str_replace($colorCode, $themeBackgroundColor, $fileContents);
					file_put_contents($filePath, $fileContents);
				}
				// Adding RVN Number in index.html page for fixing cache issue
				$indexFile = rtrim(RELATIVE_PATH, WORKING_DIR) . '/index.html';
				$getIndexContents = htmlspecialchars(file_get_contents($indexFile));
				$stringAfterFile = substr($getIndexContents, strpos($getIndexContents, $fileName));
				$fileWithRvn = substr($stringAfterFile, 0, strpos($stringAfterFile, ' '));
				$getIndexContents = str_replace($fileWithRvn, $fileName . '?rvn=' . $rvnNumber . '"', $getIndexContents);
				file_put_contents($indexFile, htmlspecialchars_decode($getIndexContents));
			}
		}
	}

	/**
	 * GET: Specific Language Key Value
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return JSON
	 */
	public function getDefaultLangKey($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Language', 'not_found'),
		];
		$getStoreDetails = get_store_details($request);
		$languageData = [];
		$languageInit = new Language();
		$getLanguages = $languageInit->where(['type' => 'tool', 'is_enable' => 1])->where('store_id', '=', !empty($request->getQueryParam('store_id')) ? $getStoreDetails['store_id'] : $getStoreDetails['store_id'])->get();
		if (!empty($getLanguages)) {
			foreach ($getLanguages->toArray() as $langKey => $langValue) {
				$languagePath = 'tool/lang_' . strtolower($langValue['name']) . '.json';
				$languageLocation = path('abs', 'language') . $languagePath;
				if (file_exists(path('abs', 'language') . $languagePath)) {
					$fileContents = file_get_contents($languageLocation);
					$fileData = json_clean_decode($fileContents);
					$keyData = [
						'low_resolution_message' => $fileData['image']['lowResolutionMessage'],
						'image_upload_tip' => $fileData['image']['imageUploadTip'],
						'agree_image_terms' => $fileData['image']['agreeImageTerms'],
						'cart_terms_condition' => $fileData['cart']['cartTermsCondition'],
						'order_notes' => $fileData['cart']['orderNotes'],
					];
				}
				$languageData[$langKey] = [
					'lang_name' => $langValue['name'],
					'lang_id' => $langValue['xe_id'],
					'lang_data' => $keyData,
				];
			}
			if (count($languageData) > 0) {
				$jsonResponse = [
					'status' => 1,
					'data' => $languageData,
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Save Language Key Value
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Dec 2019
	 * @return JSON
	 */
	public function saveDefaultLangKey($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Language', 'not_found'),
		];
		$successCount = 0;
		$allPostPutVars = $request->getParsedBody();
		if (isset($allPostPutVars['data']) && !empty($allPostPutVars['data'])) {
			$langData = json_clean_decode($allPostPutVars['data']);
			foreach ($langData as $langKey => $langValue) {
				$languagePath = 'tool/lang_' . strtolower($langValue['lang_name']) . '.json';
				$languageLocation = path('abs', 'language') . $languagePath;
				if (file_exists(path('abs', 'language') . $languagePath)) {
					$fileContents = file_get_contents($languageLocation);
					$fileData = json_clean_decode($fileContents);
					foreach ($langValue['lang_data'] as $changeKey => $changeValue) {
						if ($changeKey === 'low_resolution_message') {
							$fileData['image']['lowResolutionMessage'] = $changeValue;
						}
						if ($changeKey === 'image_upload_tip') {
							$fileData['image']['imageUploadTip'] = $changeValue;
						}
						if ($changeKey === 'agree_image_terms') {
							$fileData['image']['agreeImageTerms'] = $changeValue;
						}
						if ($changeKey === 'cart_terms_condition') {
							$fileData['cart']['cartTermsCondition'] = $changeValue;
						}
						if ($changeKey === 'order_notes') {
							$fileData['cart']['orderNotes'] = $changeValue;
						}
					}
					file_put_contents(path('abs', 'language') . $languagePath, json_clean_encode($fileData));
					$successCount++;
				}
			}
		}
		if ($successCount > 0) {
			$jsonResponse = [
				'status' => 1,
				'message' => message('Language', 'done'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Write onto setting json file
	 *
	 * @param $storeId Font ID
	 *
	 * @author debashrib@riaxe.com
	 * @date   14 Jan 2020
	 * @return boolean
	 */
	private function _writeOnJsonFile($storeId) {
		$settingLocation = path('abs', 'setting') . 'stores/' . $storeId;
		// create directory if not exists
		create_directory($settingLocation);
		$jsonFilePath = $settingLocation . '/settings.json';
		$jsonData = $settingsDataArr = [];
		$response = 0;

		$settingInit = new Setting();
		$getSettings = $settingInit->where('type', '>', 0)
			->where('store_id', '=', $storeId)
			->select('xe_id', 'setting_key', 'setting_value', 'type');
		if ($getSettings->count() > 0) {
			$settingsData = $getSettings->get();
			if (!empty($settingsData)) {
				foreach ($settingsData as $data) {
					$settingsDataArr[$data['setting_key']] = json_clean_decode(
						$data['setting_value'], true
					) ? json_clean_decode(
						$data['setting_value'], true
					) : $data['setting_value'];
				}
				//Get Unit Name from the database
				if (isset($settingsDataArr['measurement_unit']['unit']) && $settingsDataArr['measurement_unit']['unit'] != "") {
					$unitId = $settingsDataArr['measurement_unit']['unit'];
					$appUnitObj = new \App\Modules\Products\Models\AppUnit();
					$appUnitName = $appUnitObj->where('xe_id', $unitId)->first()->name;
				}
				if (isset($settingsDataArr['custom_css']) && $settingsDataArr['custom_css'] != "") {
					$cssData = "";
					$cssFilePath = path('read', 'setting')
					. str_replace('"', '', $settingsDataArr['custom_css']);
					$cssAbsPath = path('abs', 'setting')
					. str_replace('"', '', $settingsDataArr['custom_css']);
					if (file_exists($cssAbsPath)) {
						$myfile = fopen($cssFilePath, "r");
						$cssData = fgets($myfile);
						fclose($myfile);
					}
				}
				//Get currency unicode character
                $currencyId = isset($settingsDataArr['currency']['currencyId']) ? $settingsDataArr['currency']['currencyId'] : 1;
                $currencyInit = new Currency();
                $currencyData = $currencyInit->where('xe_id', $currencyId)->first();
                $currencyDataArr = json_clean_decode($currencyData, true);
				$jsonData = [
					'unit' => isset($settingsDataArr['measurement_unit'])
					? $settingsDataArr['measurement_unit']['display_lebel'] : '',
					'unit_name' => isset($appUnitName) ? $appUnitName : '',
					'currency' => [
						'value' => isset($settingsDataArr['currency'])
						? $settingsDataArr['currency']['currency'] : '',
						'separator' => isset($settingsDataArr['currency'])
						? $settingsDataArr['currency']['separator'] : '',
						'post_fix' => isset($settingsDataArr['currency'])
						? $settingsDataArr['currency']['post_fix'] : '',
						'is_separator_disabled' => isset($settingsDataArr['currency']['is_separator_disabled'])
						? $settingsDataArr['currency']['is_separator_disabled'] : '',
						'is_price_round_up' => isset($settingsDataArr['currency']['is_price_round_up'])
						? $settingsDataArr['currency']['is_price_round_up'] : '',
						'price_round_up_type' => isset($settingsDataArr['currency']['price_round_up_type'])
						? $settingsDataArr['currency']['price_round_up_type'] : '',
						'is_postfix_symbol' => isset($settingsDataArr['currency']['is_postfix_symbol'])
						? $settingsDataArr['currency']['is_postfix_symbol'] : '',
						'unicode_character' => $currencyDataArr['unicode_character'],
					],
					'email' => isset($settingsDataArr['email'])
					? $settingsDataArr['email'] : '',
					'default_tab' => isset($settingsDataArr['default_tab'])
					? $settingsDataArr['default_tab'] : 0,
					'default_tab_slug' => isset($settingsDataArr['default_tab_slug'])
					? $settingsDataArr['default_tab_slug'] : '',
					'advance_settings' => isset($settingsDataArr['advance_settings'])
					? $settingsDataArr['advance_settings'] : '',
					'appearance' => [
						'theme_color' => isset($settingsDataArr['theme_color'])
						? $settingsDataArr['theme_color'] : '',
						'custom_css' => isset($cssData)
						? $cssData : '',
						'theme_layouts' => isset($settingsDataArr['theme_layouts'])
						? $settingsDataArr['theme_layouts'] : '',
					],
					'image_setting' => [
						'facebook_import' => [
							'app_id' => isset($settingsDataArr['facebook_import'])
							? $settingsDataArr['facebook_import']['app_id'] : '',
							'domain_name' => isset($settingsDataArr['facebook_import'])
							? $settingsDataArr['facebook_import']['domain_name'] : '',
							'url' => isset($settingsDataArr['facebook_import'])
							? $settingsDataArr['facebook_import']['url'] : '',
							'is_enabled' => isset($settingsDataArr['facebook_import'])
							? $settingsDataArr['facebook_import']['is_enabled'] : '',
						],
						'dropbox_import' => isset($settingsDataArr['dropbox_import'])
						? $settingsDataArr['dropbox_import'] : '',
						'google_drive_import' => isset($settingsDataArr['google_drive_import'])
						? $settingsDataArr['google_drive_import'] : '',
						'file_uploaded' => isset($settingsDataArr['file_uploaded'])
						? $settingsDataArr['file_uploaded'] : '',
						'terms_condition' => isset($settingsDataArr['terms_condition'])
						? $settingsDataArr['terms_condition'] : '',
					],
					'store' => [
						'color' => isset($settingsDataArr['color'])
						? $settingsDataArr['color'] : '',
						'size' => isset($settingsDataArr['size'])
						? $settingsDataArr['size'] : '',
						'predeco_items' => isset($settingsDataArr['predeco_items'])
						? $settingsDataArr['predeco_items'] : '',
					],
					'cart' => [
						'direct_check_out' => isset($settingsDataArr['direct_check_out'])
						? $settingsDataArr['direct_check_out'] : '',
						'cart_terms_condition' => isset($settingsDataArr['cart_terms_condition'])
						? $settingsDataArr['cart_terms_condition'] : '',
						'order_notes' => isset($settingsDataArr['order_notes']) ? $settingsDataArr['order_notes'] : '',
						'cart_edit' => isset($settingsDataArr['cart_edit'])
						? $settingsDataArr['cart_edit'] : '',
						'stock' => isset($settingsDataArr['stock']) ? $settingsDataArr['stock'] : '',
						'tier_price' => isset($settingsDataArr['tier_price']) ? $settingsDataArr['tier_price'] : '',
						'enable_email_quote' => (isset($settingsDataArr['enable_email_quote']) && $settingsDataArr['enable_email_quote'] == 1) ? $settingsDataArr['enable_email_quote'] : 0,
					],
                    'smpt_email_details' => [
                        'email_address_details' => isset($settingsDataArr['email_address_details'])
                        ? $settingsDataArr['email_address_details'] : '',
                        'smtp_details' => isset($settingsDataArr['smtp_details'])
                        ? $settingsDataArr['smtp_details'] : '',
                    ],
                    'template_product_rel' => $settingsDataArr['template_products_rel']
				];
				// get language data
				$languageInit = new Language();
				$languageCount = $languageInit->where(
					['type' => 'tool', 'store_id' => $storeId, 'is_enable' => 1]
				)
					->count();
				if ($languageCount > 0) {
					$languageInit = new Language();
					$languageData = $languageInit->where(
						['type' => 'tool', 'store_id' => $storeId, 'is_enable' => 1]
					)
						->select('name', 'type', 'flag', 'is_default')
						->get();
					if (!empty($languageData)) {
						foreach ($languageData as $key => $data) {
							if ($data['is_default'] == 1) {
								$jsonData['lanuage']['default'] = [
									'name' => $data['name'],
									'flag' => $data['flag'],
								];
							}
							unset($data['is_default']);
							$jsonData['lanuage']['lang_list'][$key] = $data;
						}
					}
					$jsonData['lanuage']['is_multi_lang'] = ($languageCount > 1)
					? 1 : 0;
				}
				$kioskLogo = $this->getKioskLogo();
                $kioskSplash = $this->getKioskLogo('splash');
                $macId = base64_encode($this->getMacId());
                $jsonData['kiosk_setting'] = [
                        'company_name' => isset($settingsDataArr['company_name'])
                        ? $settingsDataArr['company_name'] : '',
                        'punch_line' => isset($settingsDataArr['punch_line'])
                        ? $settingsDataArr['punch_line'] : '',
                        'production_time' => isset($settingsDataArr['production_time'])
                        ? $settingsDataArr['production_time'] : '',
                        'thank_you_msg' => isset($settingsDataArr['thank_you_msg'])
                        ? $settingsDataArr['thank_you_msg'] : '',
                        'logo' => isset($kioskLogo)
                        ? $kioskLogo : '',
                        'background' => isset($kioskSplash)
                        ? $kioskSplash : '',
                        's2sz051Rhj' => isset($macId)
                        ? $macId : '',
                        'offline_payment' => $settingsDataArr['offline_payment'] > 0
                        ? true : false,
                        'publish_key' => isset($settingsDataArr['publish_key'])
                        ? $settingsDataArr['publish_key'] : '',
                    ];
				$jsonData = json_encode($jsonData, JSON_PRETTY_PRINT);
				$response = write_file($jsonFilePath, $jsonData);
			}
		}
		return $response;
	}
	/**
	 * get cart edit settings
	 *
	 *
	 * @author debashisd@riaxe.com
	 * @date   14 Jan 2020
	 * @return json
	 */
	public function getCartEditSetting($request, $response) {
		$getStoreDetails = get_store_details($request);
		$settingLocation = path('abs', 'setting') . 'stores/' . $getStoreDetails['store_id'];
		$jsonFilePath = $settingLocation . '/settings.json';
		$settingJson = file_get_contents($jsonFilePath);
		$settngs = json_decode($settingJson, true);
		$cartSetting = $settngs['cart'];
		$cartEditSettings = array("is_enabled" => false);
		if (!empty($cartSetting['cart_edit'])) {
			$cartEditSettings = $cartSetting['cart_edit'];
		}
		return json_encode($cartEditSettings);
	}

	    /**
     * GET: Settings JSOn
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   13 Dec 2019
     * @return JSON
     */
    public function getDynamicFormValues($request, $response) {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Dynamic Form', 'not_found'),
        ];
        $formValues = [];
        $getStoreDetails = get_store_details($request);
        $getFormValues = DB::table('quote_dynamic_form_values')
            ->leftJoin('quote_dynamic_form_attribute', 'quote_dynamic_form_attribute.xe_id', '=', 'quote_dynamic_form_values.attribute_id')
            ->where('quote_dynamic_form_values.xe_id', '>', 0)
            ->where('quote_dynamic_form_values.store_id', $getStoreDetails['store_id']);
        // $formValueInit = new QuotationDynamicForm();
        // $getFormValues = $formValueInit
        //     ->leftjoin('clipart_category_rel', 'cliparts.xe_id', '=', 'clipart_category_rel.clipart_id')
        //     ->where('xe_id', '>', 0);
        if ($getFormValues->count() > 0) {
            $formData = $getFormValues->select(
                'quote_dynamic_form_values.*',
                'quote_dynamic_form_attribute.input_type'
            )->get();
            
            if (!empty($formData)) {
                foreach ($formData->toArray() as $value) {
                    $formValues[] = [
                        'xe_id' => $value->xe_id,
                        'label' => $value->label,
                        'label_slug' => $value->label_slug,
                        'attribute_id' => $value->attribute_id,
                        'placeholder' => $value->placeholder,
                        'value' => json_clean_decode(
                            $value->value, true
                        ) ? json_clean_decode(
                            $value->value, true
                        ) : $value->value,
                        'is_required' => $value->is_required,
                        'sort_order' => $value->sort_order,
                        'input_type' => $value->input_type,
                        'is_default' => $value->is_default,
                    ];
                }
    
                $jsonResponse = [
                    'status' => 1,
                    'data' => $formValues
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    // Will be called on product detail page to know if this is a template based product
    public function getTemplateSettingOfProducts($request, $response){
        $getStoreDetails = get_store_details($request);
        $categoryString = $request->getQueryParam('prodcatID');
        $settingLocation = path('abs', 'setting') . 'stores/' . $getStoreDetails['store_id'];
        $jsonFilePath = $settingLocation . '/settings.json';
        $settingJson = file_get_contents($jsonFilePath);
        $settngs = json_decode($settingJson, true);
        $templateData = array("is_enabled" => false);
        $templateSetting = $settngs['template_product_rel'];
        if (!empty($templateSetting) && $templateSetting['is_template_prod'] === true) {
            $thisProdCats = explode(',', $categoryString);
            foreach ($templateSetting['categories'] as $row => $relation) {
               if (count(array_intersect($thisProdCats, $relation['prodCatId'])) > 0) {
                    $templateData['is_enabled'] = true;
                    break;
                } 
            }
        }
        return json_encode($templateData);
    }

    /**
     * GET: Kiosk logo
     *
     *
     * @author debashisd@riaxe.com
     * @date   06 June 2020
     * @return String
     */
    private function getKioskLogo($imageType='logo')
    {
        $kioskLogo = '';
        if ($imageType == 'splash') {
            $dirName = 'kiosk_splash';
        }else{
            $dirName = 'kiosk_setting';
        }
        $kioskSettingPath = path('abs', 'setting') . $dirName;
        if (is_dir($kioskSettingPath)) {
            $scanDir = scandir($kioskSettingPath);
            if (is_array($scanDir)) {
                foreach ($scanDir as $dir) {
                    if ($dir != '.' && $dir != '..' && (strpos($dir, "thumb_") === false)) {
                        $kioskLogo = path('read', 'setting') . $dirName.DIRECTORY_SEPARATOR . $dir;
                    }
                }
            }
        }
        return $kioskLogo;
    }

    public function saveS3Credentials($request, $response){
    	$serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => 'aws settings not saved',
        ];
    	require getcwd() . '/app/Dependencies/aws/aws-autoloader.php';
    	$isBucketAvailable = false;
    	$allPostPutVars = $request->getParsedBody();
    	$awsKey = $allPostPutVars['aws_key'];
    	$S3Secret = $allPostPutVars['s3_secret'];
    	$S3region = $allPostPutVars['region'];
		try {
	    	$S3enabled = $allPostPutVars['is_enabled'];
		    	$client = new \Aws\S3\S3Client([
				    'region' => $S3region,
				    'version' => 'latest',
				    'credentials' => array(
		                'key' => $awsKey,
		                'secret' => $S3Secret
		            ),
				]);
	    	 $buckets = $client->listBuckets();
	    	 $bucketNames = array_column($buckets['Buckets'], 'Name');
	    	 if (in_array('imprintnext', $bucketNames)) {
	    	 	$isBucketAvailable = true;
	    	 }else {
	    	 	$bucketResponse = $this->createBucket($client, 'imprintnext');
	    	 	if(strpos($bucketResponse, 'Error: ') !== false){
				    $jsonResponse['message'] = $bucketResponse;
				} else{
				    $isBucketAvailable = true;
				}
	    	 }
	    	 if ($isBucketAvailable) {
	    	 	$getStoreDetails = get_store_details($request);
        		$settingLocation = path('abs', 'setting') . 'stores/' . $getStoreDetails['store_id']. '/S3Data.xml';
	    	 	$dom = new \DomDocument();
	    	 	$domKey = $dom->createElement("aws_key", $awsKey);
	    	 	$dom->appendChild($domKey);
	    	 	$domSecret = $dom->appendChild(
				  $dom->createElement('s3_secret')
				);
				$domSecret->appendChild(
				  $dom->createCDATASection($S3Secret)
				);
	    	 	$domS3region = $dom->createElement("region", $S3region);
	    	 	$dom->appendChild($domS3region);
	    	 	$domBucket = $dom->createElement("bucket", 'imprintnext');
	    	 	$dom->appendChild($domBucket);
	    	 	$domStatus = $dom->createElement("is_enabled", $S3enabled);
	    	 	$dom->appendChild($domStatus);
	    	 	$dom->save($settingLocation);
	    	 	$xmlContent = (string) file_get_contents($settingLocation);
	    	 	$xmlContent1 = str_replace('<?xml version="1.0"?>', '<?xml version="1.0"?><s3>', $xmlContent);
	    	 	file_put_contents($settingLocation, $xmlContent1.'</s3>');
	    	 	$jsonResponse = [
		            'status' => 1,
		            'message' => 'aws settings saved',
		        ];
	    	 }
		}catch (\Exception $e){
			$jsonResponse['message'] = "wrong S3 credentials";
		}
		return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    private  function createBucket($s3Client, $bucketName){
	    try {
	        $result = $s3Client->createBucket([
	            'Bucket' => $bucketName,
	        ]);
	        return 'bucket created';
	    } catch (AwsException $e) {
	        return 'Error: ' . $e->getAwsErrorMessage();
	    }
	}

	public function getS3Credentials($request, $response){
		$serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => 'aws settings not saved',
        ];
        $getStoreDetails = get_store_details($request);
		$settingLocation = path('abs', 'setting') . 'stores/' . $getStoreDetails['store_id']. '/S3Data.xml';
		if (file_exists($settingLocation)) {
			$dom = new \DomDocument();
			$dom->load($settingLocation);
			$elements = $dom->getElementsByTagName('s3');
			foreach($elements as $node){
			   foreach($node->childNodes as $child) {
			   	if ($child->nodeName != "#text") {
			      $data[$child->nodeName] = $child->nodeValue;
			   	}
			   }
			}
			$jsonResponse = [
	            'status' => 1,
	            'data' => $data,
	        ];
		}
		return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );

	}
}
