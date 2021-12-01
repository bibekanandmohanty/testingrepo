<?php
/**
 * Manage Print Profile
 *
 * PHP version 5.6
 *
 * @category  Print_Profile
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\PrintProfiles\Controllers;

use App\Components\Models\Category as CommonCategory;
use App\Modules\Fonts\Models\Font;
use App\Modules\PrintProfiles\Controllers\PricingController as Pricing;
use App\Modules\PrintProfiles\Models as PrintProfileModels;
use App\Modules\PrintProfiles\Models\Pricing as PricingModel;
use App\Modules\PrintProfiles\Models\PrintProfileAttributeRel;
use App\Modules\Settings\Models\Language;
use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Capsule\Manager as DB;
use ProductStoreSpace\Controllers\StoreProductsController;

/**
 * Print Profile Controller
 *
 * @category                Print_Profile
 * @package                 Print_Profile
 * @author                  Tanmaya Patra <tanmayap@riaxe.com>
 * @license                 http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link                    http://inkxe-v10.inkxe.io/xetool/admin
 * @SuppressWarnings(PHPMD)
 */
class PrintProfilesController extends StoreProductsController {
	/**
	 * Assets Slug list
	 *
	 * @var array
	 */
	private $assetsSlugList = [
		'cliparts',
		'backgrounds',
		'fonts',
		'shapes',
		'templates',
		'color-palettes',
	];
	/**
	 * GET: Get list of Print Profiles
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return All Print Profile List
	 */
	public function getAllPrintProfiles($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Print Profile', 'not_found'),
		];
		$getStoreDetails = get_store_details($request);
		$printProfileGtInit = new PrintProfileModels\PrintProfile();
		$getPrintProfile = $printProfileGtInit->whereNotNull('name');
		// Conditional Draft on is_setup key
		if (!empty($request->getQueryParam('is_setup'))) {
			$getPrintProfile->where('is_draft', 1);
		}

		if ($getPrintProfile->count() > 0) {
			// Adding Filters
			$categoryId = $request->getQueryParam('category');
			$assetTypeId = $request->getQueryParam('asset_type');
			// Dashboard incomplete print profile count
			$isSetupIncomplete = $request->getQueryParam('is_setup_incomplete');
			$isPriceIncomplete = $request->getQueryParam('is_price_incomplete');
			$isAssetsIncomplete = $request->getQueryParam('is_assets_incomplete');

			if (isset($isSetupIncomplete) && $isSetupIncomplete == 1) {
				$getPrintProfile->where(
					function ($query) {
						return $query->orWhere(
							[
								'is_price_setting' => 0,
								'is_product_setting' => 0,
								'is_assets_setting' => 0,
							]
						);
					}
				);
			}
			if (isset($isPriceIncomplete) && $isPriceIncomplete == 1) {
				$getPrintProfile->where(['is_price_setting' => 0]);
			}
			if (isset($isAssetsIncomplete) && $isAssetsIncomplete == 1) {
				$getPrintProfile->select('xe_id')->whereNotIn(
					'xe_id', function ($query) {
						$query->select('print_profile_id')->from('print_profile_assets_category_rel')->distinct();
					}
				);
			}

			// Filter by Category ID and It's Subcategory ID
			if (isset($assetTypeId) && $assetTypeId != "") {
				$seacrhAssetTypes = $this->assetsTypeId($assetTypeId);
				if (!empty($seacrhAssetTypes['asset_type_id'])
					&& $seacrhAssetTypes['asset_type_id'] > 0
				) {
					$getPrintProfile->whereHas(
						'assets', function ($q) use ($seacrhAssetTypes) {
							return $q->where(
								'asset_type_id', $seacrhAssetTypes['asset_type_id']
							);
						}
					);
				}
			}
			// Filter by Assets type ID
			if (isset($categoryId) && $categoryId != "") {
				$getPrintProfile->whereHas(
					'assets', function ($q) use ($categoryId) {
						return $q->where('category_id', $categoryId);
					}
				);
			}
			// Store conditions
			$getPrintProfile->where($getStoreDetails);

			$printProfileList = $getPrintProfile->select(
				'xe_id as id', 'name',
				'is_disabled', 'file_name', 'sticker_settings'
			)->orderBy('xe_id', 'desc')->get();

			foreach ($printProfileList as $key => $value) {
				$stickerSettings = json_clean_decode($value['sticker_settings'])['is_sticker_enabled'];
				$requireDetails[] = [
					'id' => $value['id'],
					'name' => $value['name'],
					'is_disabled' => $value['is_disabled'],
					'file_name' => $value['file_name'],
					'file_name_url' => $value['file_name_url'],
					'thumbnail' => $value['thumbnail'],
					'is_decoration_exists' => $value['is_decoration_exists'],
					'is_sticker_settings' => isset($stickerSettings) ? $stickerSettings : 0,
				];
			}

			$jsonResponse = [
				'status' => 1,
				'data' => $requireDetails,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Clone a Print Profile along with it's secondary Table's Data
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Clone Status
	 */
	public function clonePrintProfile($request, $response) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Print Profile Clone', 'error'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$getPostData = $request->getParsedBody();
		$cloneId = $getPostData['print_profile_id'];
		$cloneNewName = (isset($getPostData['name']) && $getPostData['name'] != "")
		? $getPostData['name'] : null;
		$printProfileGet = new PrintProfileModels\PrintProfile();
		$profiles = $printProfileGet->with('features', 'assets', 'engraves')
			->find($cloneId);
		$cloneProfile = $profiles->replicate();
		if ($cloneProfile->save()) {
			$printProfileInsertId = $cloneProfile->xe_id;
			$features = $cloneProfile->features;
			$assets = $cloneProfile->assets;
			$engraves = $cloneProfile->engraves;
			$getStoreDetails = get_store_details($request);
			$storeId = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
			// Clone Name and Description File
			$this->cloneLanguageDetails($cloneId, $printProfileInsertId, $getPostData['language_data'], $storeId);
			if (isset($features) && count($features->toArray()) > 0) {
				foreach ($features as $key => $feature) {
					$featureReplicate = new PrintProfileModels\PrintProfileFeatureRel();
					$featureReplicate->print_profile_id = $printProfileInsertId;
					$featureReplicate->feature_id = $feature->feature_id;
					$featureReplicate->save();
				}
			}
			if (isset($assets) && count($assets->toArray()) > 0) {
				foreach ($assets as $key => $asset) {
					$assetReplicate = new PrintProfileModels\PrintProfileAssetsCategoryRel();
					$assetReplicate->print_profile_id = $printProfileInsertId;
					$assetReplicate->asset_type_id = $asset->asset_type_id;
					$assetReplicate->category_id = $asset->category_id;
					$assetReplicate->save();
				}
			}
			if (isset($engraves->print_profile_id)
				&& $engraves->print_profile_id > 0
			) {
				$engraves->print_profile_id = $printProfileInsertId;
				$engraveReplicate = new PrintProfileModels\PrintProfileEngraveSetting($engraves->toArray());
				$engraveReplicate->save();
			}
			$basicData = [];
			$uploadedFileName = do_upload(
				'upload',
				path('abs', 'print_profile'),
				[150],
				'string'
			);
			if (!empty($uploadedFileName)) {
				$basicData += [
					'file_name' => $uploadedFileName,
				];
			}
			if (!empty($cloneNewName) && $cloneNewName != "") {
				$basicData += [
					'name' => $cloneNewName,
				];
			}
			if (isset($basicData) && count($basicData) > 0) {
				$printProfileUpdate = new PrintProfileModels\PrintProfile();
				$printProfileUpdate->where('xe_id', $printProfileInsertId)
					->update($basicData);
			}
			// Clone pricing Data
			$pricing = new Pricing();
			$pricing->clonePricing(
				$request, $response, $cloneId, $printProfileInsertId
			);

			$printProfileGet = new PrintProfileModels\PrintProfile();
			$clonedRecord = $printProfileGet->where('xe_id', $printProfileInsertId)
				->select('xe_id as id', 'name', 'is_disabled', 'file_name')
				->first();
			$jsonResponse = [
				'status' => 1,
				'message' => message('Print Profile Clone', 'clone'),
				'data' => $clonedRecord,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Clone Print Profile Name and Desc
	 *
	 * @param $cloneId              Previous Printprofile Id
	 * @param $printProfileInsertId New Printprofile Id
	 * @param $languageData         Language Details
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 mar 2020
	 * @return boolean
	 */
	private function cloneLanguageDetails($cloneId, $printProfileInsertId, $languageData, $storeId = 1) {
		if (!empty($cloneId) && !empty($printProfileInsertId) && !empty($languageData)) {
			$languageData = json_clean_decode($languageData, true);
			foreach ($languageData as $langKey => $langValue) {
				if ($storeId == 1) {
					$languagePath = 'tool/lang_' . strtolower($langValue['lang_name']) . '.json';
				} else {
					$languagePath = 'tool/' . $storeId . '_lang_' . strtolower($langValue['lang_name']) . '.json';
				}
				$languageLocation = path('read', 'language') . $languagePath;
				if (file_exists(path('abs', 'language') . $languagePath)) {
					$fileContents = file_get_contents($languageLocation);
					$fileData = json_clean_decode($fileContents);
					if (!empty($fileData['print_profiles'][$cloneId])) {
						$data = [
							'title' => $langValue['lang_data']['title'],
							'description' => $fileData['print_profiles'][$cloneId]['description'],
						];
						$fileData['print_profiles'][$printProfileInsertId] = $data;
						file_put_contents(path('abs', 'language') . $languagePath, json_clean_encode($fileData));
					}
				}
			}
		}
	}

	/**
	 * POST: Save Print Profile records
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Save status
	 */
	public function savePrintProfile($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Print Profile', 'not_found'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$getStoreDetails = get_store_details($request);
		$storeId = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
		if (isset($allPostPutVars['data']) && $allPostPutVars['data'] != "") {
			$getAllFormData = json_clean_decode($allPostPutVars['data'], true);
			DB::beginTransaction();
			// Step1: Process Profile Data
			$getResponseProfileData = $this->processProfileData(
				$request, $response, $args,
				$getAllFormData['profile'], 'save'
			);
			// Last Insert ID
			$lastInsertedId = $getResponseProfileData['last_inserted_id'];
			if (!empty($lastInsertedId) && $lastInsertedId > 0) {
				// Step2: Process Feature Data
				$this->processFeatureData(
					$getAllFormData['features_data'],
					$lastInsertedId,
					'save'
				);

				// Step4: Process VDP Data
				$this->processVdpData(
					$getAllFormData['vdp_data'],
					$lastInsertedId
				);
				// Step5: Process Laser Engrave data
				$this->processEngraveData(
					$getAllFormData['laser_engrave_data'],
					$lastInsertedId
				);
				// Step6: Process Image Data
				$this->processImageData(
					$getAllFormData['image_data'],
					$lastInsertedId
				);
				// Step7: Process Color Data
				$this->processColorData(
					$getAllFormData['color_data'],
					$lastInsertedId
				);
				// Step8: Process Order Data
				$this->processOrderData(
					$getAllFormData['order_data'],
					$lastInsertedId
				);
				// Step9: Process Text Settings
				$this->processTextData($getAllFormData['text_data'], $lastInsertedId);

				// Step10: Process BG Settings
				$this->processMiscData($getAllFormData['misc_data'], $lastInsertedId, $storeId);

				// Step11: Process Name and Number Settings
				if (!empty($getAllFormData['name_number_data'])) {
					$this->processNameNumberData(
						$getAllFormData['name_number_data'], $lastInsertedId
					);
				}

				// Step12: Process AR Settings
				if (!empty($getAllFormData['ar_data'])) {
					$this->processArData(
						$getAllFormData['ar_data'], $lastInsertedId
					);
				}

				// Step13: Process Language Settings
				if (!empty($getAllFormData['language_data'])) {
					$this->processLanguageData(
						$getAllFormData['language_data'], $lastInsertedId, $storeId
					);
				}
				// Step14: Process Embroidery Settings
				if (!empty($getAllFormData['embroidery'])) {
					$this->processEmbroideryData(
						$getAllFormData['embroidery'], $lastInsertedId
					);
				}

				// Step15: Process Sticker Settings
				if (!empty($getAllFormData['sticker'])) {
					$this->processStickerData(
						$getAllFormData['sticker'], $lastInsertedId
					);
				}

				// Set Publish Status
				$printProfilePublish = [
					'is_draft' => $getAllFormData['is_draft'],
				];
				try {
					$draftUpdateProfile = new PrintProfileModels\PrintProfile();
					$draftUpdateProfile->where('xe_id', $lastInsertedId)
						->update($printProfilePublish);
					// If the json file was created, then commit the last action
					if ($this->createJsonFile(
						$request, $response, $lastInsertedId
					)
					) {
						DB::commit();
						$jsonResponse = [
							'status' => 1,
							'print_profile_insert_id' => $lastInsertedId,
							'message' => message('Print Profile', 'saved'),
						];
					} else {
						// If file not created for tool, then revert the action
						DB::rollback();
						$this->deletePrintJsonFile(
							$lastInsertedId, $getStoreDetails['store_id']
						);
						$jsonResponse['message'] = message('Print Profile', 'file_not_created');
					}
				} catch (\Exception $e) {
					$jsonResponse = [
						'status' => 0,
						'message' => message('Print Profile', 'error'),
						'exception' => show_exception() === true
						? $e->getMessage() : '',
					];
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Update text Settings
	 *
	 * @param $textData       Text Data array
	 * @param $printProfileId Print Profile Inserted ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   17 Feb 2020
	 * @return none
	 */
	private function processTextData($textData, $printProfileId) {
		if (!empty($textData) && !empty($printProfileId)) {
			$profileObj = new PrintProfileModels\PrintProfile();
			$checkProfile = $profileObj->where('xe_id', $printProfileId);
			if ($checkProfile->count() > 0) {
				// Do Update
				$checkProfile->update(
					[
						'text_settings' => json_clean_encode($textData),
					]
				);
			}
		}
	}
	/**
	 * Update BG Settings
	 *
	 * @param $bgData         Text Data array
	 * @param $printProfileId Print Profile Inserted ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   17 Feb 2020
	 * @return none
	 */
	private function processMiscData($bgData, $printProfileId, $storeId) {
		if (!empty($bgData) && !empty($printProfileId)) {
			$profileObj = new PrintProfileModels\PrintProfile();
			$checkProfile = $profileObj->where('xe_id', $printProfileId);
			if ($checkProfile->count() > 0) {
				// Do Update
				$checkProfile->update(
					[
						'misc_settings' => json_clean_encode($bgData),
					]
				);
				//Check for quote request enable in global settings if not then enabled 
				$forceQuotationOption = $bgData['force_quotation_option'];
				if ($forceQuotationOption == 1) {
					$settingInit = new Setting();
					$setting = $settingInit
						->select('xe_id', 'setting_value')
						->where([
							'setting_key' => 'enable_email_quote',
							'type' => 5,
							'store_id' => $storeId
						]);
					if ($setting->count() > 0) {
						$settingsData = $setting->first();
						$settingsData = json_clean_decode($settingsData, true);
						if ($settingsData['setting_value'] == 0) {
							$settingInit->where('xe_id', $settingsData['xe_id'])
								->update(['setting_value' => 1]);
						}
					}
				}
			}
		}
	}
	/**
	 * Update AR Settings
	 *
	 * @param $featureData    Name and Number Data array
	 * @param $printProfileId Print Profile Inserted ID
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 mar 2020
	 * @return none
	 */
	private function processArData($featureData, $printProfileId) {
		if (!empty($featureData) && !empty($printProfileId)) {
			$profileObj = new PrintProfileModels\PrintProfile();
			$checkProfile = $profileObj->where('xe_id', $printProfileId);
			if ($checkProfile->count() > 0) {
				// Do Update
				$checkProfile->update(
					[
						'ar_settings' => json_clean_encode($featureData),
					]
				);
			}
		}
	}

	/**
	 * Update Process LanguageData Settings
	 *
	 * @param $languageData    Name and Number Data array
	 * @param $printProfileId Print Profile Inserted ID
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 mar 2020
	 * @return none
	 */
	private function processLanguageData($languageData, $printProfileId, $storeId = 1) {
		if (!empty($languageData) && !empty($printProfileId)) {
			foreach ($languageData as $langKey => $langValue) {
				$idExist = 0;

				if ($storeId == 1) {
					$languagePath = 'tool/lang_' . strtolower($langValue['lang_name']) . '.json';
				} else {
					$languagePath = 'tool/' . $storeId . '_lang_' . strtolower($langValue['lang_name']) . '.json';
				}

				$languageLocation = path('read', 'language') . $languagePath;
				if (file_exists(path('abs', 'language') . $languagePath)) {
					$fileContents = file_get_contents($languageLocation);
					$fileData = json_clean_decode($fileContents);

					if (!empty($fileData['print_profiles'])) {
						foreach ($fileData['print_profiles'] as $profileKey => $profileValue) {
							if ($printProfileId == $profileKey) {
								$data = [
									'title' => $langValue['lang_data']['title'],
									'description' => $langValue['lang_data']['description'],
								];
								$fileData['print_profiles'][$profileKey] = $data;
								file_put_contents(path('abs', 'language') . $languagePath, json_clean_encode($fileData));
								$idExist = 1;
							}
						}
						if ($idExist === 0) {
							$data = [
								'title' => $langValue['lang_data']['title'],
								'description' => $langValue['lang_data']['description'],
							];
							$fileData['print_profiles'][$printProfileId] = $data;
							file_put_contents(path('abs', 'language') . $languagePath, json_clean_encode($fileData));
						}
					}
				}
			}
		}
	}

	/**
	 * Update process Embroidery Data Settings
	 *
	 * @param $embroideryData    Name and Number Data array
	 * @param $printProfileId Print Profile Inserted ID
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 mar 2020
	 * @return none
	 */
	private function processEmbroideryData($embroideryData, $printProfileId) {
		if (!empty($embroideryData) && !empty($printProfileId)) {
			$profileObj = new PrintProfileModels\PrintProfile();
			$checkProfile = $profileObj->where('xe_id', $printProfileId);
			if ($checkProfile->count() > 0) {
				// Do Update
				$checkProfile->update(
					[
						'embroidery_settings' => json_clean_encode($embroideryData),
					]
				);
			}
		}
	}

	/**
	 * Update process Sticker Data Settings
	 *
	 * @param $stickerData    Name and Number Data array
	 * @param $printProfileId Print Profile Inserted ID
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 mar 2020
	 * @return none
	 */
	private function processStickerData($stickerData, $printProfileId) {
		if (!empty($stickerData) && !empty($printProfileId)) {
			$profileObj = new PrintProfileModels\PrintProfile();
			$checkProfile = $profileObj->where('xe_id', $printProfileId);
			if ($checkProfile->count() > 0) {
				// Do Update
				$checkProfile->update(
					[
						'sticker_settings' => json_clean_encode($stickerData),
					]
				);
			}
		}
	}

	/**
	 * Update Name and Number Settings
	 *
	 * @param $featureData    Name and Number Data array
	 * @param $printProfileId Print Profile Inserted ID
	 *
	 * @author satyabratap@riaxe.com
	 * @date   24 Feb 2020
	 * @return none
	 */
	private function processNameNumberData($featureData, $printProfileId) {
		if (!empty($featureData) && !empty($printProfileId)) {
			$profileObj = new PrintProfileModels\PrintProfile();
			$checkProfile = $profileObj->where('xe_id', $printProfileId);
			if ($checkProfile->count() > 0) {
				// Do Update
				$checkProfile->update(
					[
						'name_number_settings' => json_clean_encode($featureData),
					]
				);
			}
		}
	}

	/**
	 * Save/Update Profile section of Print Profile
	 *
	 * @param $request        Slim's Request object
	 * @param $response       Slim's Response object
	 * @param $args           Slim's Argument parameters
	 * @param $getProfileData Profile Data Array
	 * @param $type           Flag for save or update
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return All Print Profile List
	 */
	private function processProfileData($request, $response, $args, $getProfileData = [], $type = 'save') {
		$getPostPutData = [];
		$profileResponse = [];
		$getStoreDetails = get_store_details($request);

		$fileUploadKey = $getProfileData['image_path'];
		$getUploadedFileName = do_upload(
			$fileUploadKey,
			path('abs', 'print_profile'),
			[150],
			'string'
		);
		if (!empty($getUploadedFileName)) {
			$getPostPutData += [
				'file_name' => $getUploadedFileName,
			];
		}
		// Append Store ID
		$getPostPutData['store_id'] = $getStoreDetails['store_id'];
		// Append name and description
		$getPostPutData += [
			'name' => $getProfileData['name'],
			'description' => $getProfileData['description'],
		];
		if (!empty($type) && $type == 'save') {
			// Save rcords
			$saveProfile = new PrintProfileModels\PrintProfile($getPostPutData);
			if ($saveProfile->save()) {
				$printProfileInsertId = $saveProfile->xe_id;
				$profileResponse = [
					'status' => 1,
					'last_inserted_id' => $saveProfile->xe_id,
				];
			}
		} else {
			// Update records
			$printProfileUpdateId = isset($args['id']) ? $args['id'] : '';
			$printProfileGet = new PrintProfileModels\PrintProfile();
			$printProfileUpdateInit = $printProfileGet->where(
				'xe_id', $printProfileUpdateId
			);
			if ($printProfileUpdateInit->count() > 0) {
				try {
					$printProfileUpdateInit->update($getPostPutData);
					$printProfileInsertId = $printProfileUpdateId;
					$profileResponse = [
						'status' => 1,
						'last_inserted_id' => $printProfileUpdateId,
					];
				} catch (\Exception $e) {
					$profileResponse = [
						'status' => 0,
						'message' => message('Profile', 'exception'),
						'exception' => show_exception() === true
						? $e->getMessage() : '',
					];
				}
			} else {
				$profileResponse = [
					'status' => 0,
					'last_inserted_id' => 0,
					'message' => message('Print Profile', 'not_found'),
				];
			}
		}
		return $profileResponse;
	}
	/**
	 * Save/Update Fetaure-and-PrintProfile relational records of Print Profile
	 *
	 * @param $request         Slim's Request object
	 * @param $response        Slim's Response object
	 * @param $getFeaturesData Feature Data Array
	 * @param $printProfileId  Print Profile Last id
	 * @param $type            Flag for save or update
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return All Print Profile List
	 */
	private function processFeatureData(
		$getFeaturesData,
		$printProfileId,
		$type = 'save'
	) {
		$savePrintProfFeature = $featureResponse = [];
		if (!empty($getFeaturesData['features'])
			&& count($getFeaturesData['features']) > 0
		) {
			$getSelectedFeatures = $getFeaturesData['features'];

			foreach ($getSelectedFeatures as $key => $feature) {
				$savePrintProfFeature[$key] = [
					'print_profile_id' => $printProfileId,
					'feature_id' => $feature,
				];
			}
			// Clean records before process while updating
			if (!empty($type) && $type == 'update') {
				$profileFeatureRelDel = new PrintProfileModels\PrintProfileFeatureRel();
				$profileFeatureRelDel->where(['print_profile_id' => $printProfileId])
					->delete();
			}
			$profileFeatureRelIns = new PrintProfileModels\PrintProfileFeatureRel();
			if ($profileFeatureRelIns->insert($savePrintProfFeature)) {
				$featureResponse = [
					'status' => 1,
				];
			}
		}
		return $featureResponse;
	}

	/**
	 * If categories are selected against a Product, then the corresp. products
	 * under the selected categories will be attached automatically to print profiles
	 * in Product Settings Table
	 *
	 * @param $productList    Product List Array
	 * @param $printProfileId Print profile ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Boolean
	 */
	private function updtProdPrintProfRel($productList, $printProfileId, $storeId) {
		if (isset($printProfileId) && $printProfileId > 0
			&& isset($productList) && count($productList) > 0
		) {
			foreach ($productList as $prodKey => $productId) {
				// Set a default array with available parameters
				$productSettings = [
					'product_id' => $productId,
					'is_variable_decoration' => 0,
					'is_ruler' => 0,
					'is_crop_mark' => 0,
					'is_safe_zone' => 0,
					'crop_value' => 0.00,
					'safe_value' => 0.00,
					'is_3d_preview' => 0,
					'3d_object_file' => null,
					'3d_object' => null,
					'scale_unit_id' => 1,
					'store_id' => $storeId,
				];
				$getProductSettinit = new \App\Modules\Products\Models\ProductSetting();
				$getProdSettGt = $getProductSettinit->where(
					['product_id' => $productId]
				);

				if ($getProdSettGt->count() == 0) {
					$updateProductSett = new \App\Modules\Products\Models\ProductSetting($productSettings);
					if ($updateProductSett->save()) {
						$productSettingsId = $updateProductSett->xe_id;
					}
				} else {
					$getProductSettData = $getProdSettGt->select('xe_id')->first();
					$productSettingsId = $getProductSettData->xe_id;
				}

				$profileProdSettRel[$prodKey] = [
					'print_profile_id' => $printProfileId,
					'product_setting_id' => $productSettingsId,
				];
			}
			// Sync Print Profile Product Relation table
			try {
				$printProfileFind = new \App\Modules\PrintProfiles\Models\PrintProfile();
				$printProfileSync = $printProfileFind->find($printProfileId);
				$printProfileSync->products_relation()
					->sync($profileProdSettRel);
				return true;
			} catch (\Exception $e) {
				return false;
			}
		}
		return false;
	}
	/**
	 * Save/Update VDP-and-PrintProfile relational records of Print Profile
	 *
	 * @param $request        Slim's Request object
	 * @param $response       Slim's Response object
	 * @param $getVdpData     VDP data
	 * @param $printProfileId Print Profile ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return All Print Profile List
	 */
	private function processVdpData(
		$getVdpData,
		$printProfileId
	) {
		$vdpResponse['status'] = 0;
		$saveVdpRecords = [
			'is_vdp_enabled' => $getVdpData['is_enabled'],
			'vdp_data' => $getVdpData['vdp'],
		];
		try {
			$printProfile = new PrintProfileModels\PrintProfile();
			$printProfile->where('xe_id', $printProfileId)
				->update($saveVdpRecords);
			$vdpResponse['status'] = 1;
		} catch (\Exception $e) {
			$vdpResponse['status'] = 0;
		}

		return $vdpResponse;
	}
	/**
	 * Save/Update Engrave-and-PrintProfile relational records of Print Profile
	 *
	 * @param $request        Slim's Request object
	 * @param $response       Slim's Response object
	 * @param $getEngraveData Engrave Data
	 * @param $printProfileId Print Profile ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return All Print Profile List
	 */
	private function processEngraveData(
		$getEngraveData,
		$printProfileId
	) {
		$engraveSaveData = [];
		$engraveImageStatus = 0;
		$engraveType = 'image';
		$engraveSurfInsId = 0;
		// Engrave Preview Image Code engrave_preview_image_path
		if ($getEngraveData['is_laser_engrave_enabled'] === 1) {
			$engrPrvImgStatus = 0;
			$engravePreviewType = 'image';
			$engravePrevTypeVal = "";
			if (!empty($getEngraveData['engrave_preview_image_path'])
				&& $getEngraveData['engrave_preview_image_path'] != ""
			) {
				$getEngravePreviewFileName = do_upload(
					$getEngraveData['engrave_preview_image_path'],
					path('abs', 'print_profile'),
					[150],
					'string'
				);
				if (!empty($getEngravePreviewFileName)) {
					$engrPrvImgStatus = 1;
					$engravePreviewType = 'image';
					$engravePrevTypeVal = $getEngravePreviewFileName;
				}
			} else {
				$engrPrvImgStatus = 0;
				$engravePreviewType = 'color';
				$engravePrevTypeVal = $getEngraveData['engrave_preview_color_code'] != ""
				? $getEngraveData['engrave_preview_color_code'] : null;
			}

			// Engrave Image Code
			if (!empty($getEngraveData['engrave_details']['engrave_image_path'])
				&& $getEngraveData['engrave_details']['engrave_image_path'] != ""
			) {
				$getUploadedFileName = do_upload(
					$getEngraveData['engrave_details']['engrave_image_path'],
					path('abs', 'print_profile'),
					[150],
					'string'
				);
				if (!empty($getUploadedFileName)) {
					$engraveImageStatus = 1;
					$engraveType = 'image';
					$engraveTypeValue = $getUploadedFileName;
				}
			} else {
				$engraveImageStatus = 0;
				$engraveType = 'color';
				$engraveTypeValue
				= $getEngraveData['engrave_details']['engrave_color_code'];
			}

			/**
			 * Check if the given engrave_surface_id is user defined or not. If it's
			 * system defined then get it's pk and update the pk else if it's user
			 * defined then create or update record and get it's pk
			 */
			$isEngrSfcUsrDef = 1;
			$engrSfcUserDefKey = 0;
			$engravedSurface = new PrintProfileModels\EngravedSurface();
			$chkEngraveSurfGet = $engravedSurface->where(
				'xe_id',
				$getEngraveData['engrave_details']['engrave_surface_id']
			);
			if ($chkEngraveSurfGet->count() > 0) {
				$checkEngraveSurfaceUserType = $chkEngraveSurfGet->select(
					'xe_id', 'is_user_defined'
				)
					->first();
				if (isset($checkEngraveSurfaceUserType->is_user_defined)
					&& $checkEngraveSurfaceUserType->is_user_defined == 0
				) {
					$isEngrSfcUsrDef = 0;
					$engrSfcUserDefKey = $checkEngraveSurfaceUserType->xe_id;
				}
			}
			// Update Engrave Settings
			$engraveShadowDetails = $getEngraveData['engrave_details']['engrave_shadow'];
			$updateEngraveMaster = [
				'surface_name' => 'Custom',
				'engraved_type' => $engraveType,
				'shadow_direction' => isset($engraveShadowDetails['direction'])
				? $engraveShadowDetails['direction'] : null,
				'shadow_size' => isset($engraveShadowDetails['size'])
				? $engraveShadowDetails['size'] : null,
				'shadow_opacity' => isset($engraveShadowDetails['opacity'])
				? $engraveShadowDetails['opacity'] : null,
				'shadow_strength' => isset($engraveShadowDetails['strength'])
				? $engraveShadowDetails['strength'] : null,
				'shadow_blur' => isset($engraveShadowDetails['blur'])
				? $engraveShadowDetails['blur'] : null,
				'is_user_defined' => $isEngrSfcUsrDef,
			];
			// If image exists and Image saved and image file name returned then process
			if (!empty($engraveTypeValue) && $engraveTypeValue != null) {
				$updateEngraveMaster['engrave_type_value'] = $engraveTypeValue;
			}
			if (!empty($engravePrevTypeVal) && $engravePrevTypeVal != null) {
				$updateEngraveMaster['engrave_preview_type_value'] = $engravePrevTypeVal;
			}
			if (!empty($engravePreviewType) && $engravePreviewType != null) {
				$updateEngraveMaster['engrave_preview_type'] = $engravePreviewType;
			}

			$selAutoConvId = 'Grayscale';
			if ($getEngraveData['auto_convert_color']['auto_convert_id'] == '1') {
				$selAutoConvId = 'BW';
			}
			$ppEngraveSettRecord = [
				'print_profile_id' => $printProfileId,
				//'engraved_surface_id' => $engraveSurfInsId,
				'is_engraved_surface' => $getEngraveData['engrave_details']['is_enabled'],
				'is_BWGray_enabled' => $getEngraveData['is_BWGray_enabled'],
				'is_black_white' => $getEngraveData['is_black_white'],
				'is_gary_scale' => $getEngraveData['is_gary_scale'],
				'is_auto_convert' => $getEngraveData['auto_convert_color']['is_enabled'],
				'is_hide_color_options' => $getEngraveData['is_hide_color_options'],
				'auto_convert_type' => $selAutoConvId,
				'is_engrave_image' => $engraveImageStatus,
				'is_engrave_preview_image' => isset($engrPrvImgStatus)
				? $engrPrvImgStatus : null,
			];
			$ppEngraveSettGtInit = new PrintProfileModels\PrintProfileEngraveSetting();
			$ppEngraveSettings = $ppEngraveSettGtInit->where(
				['print_profile_id' => $printProfileId]
			);
			if ($ppEngraveSettings->count() > 0) {
				// Update Existing Engrave Record
				if (isset($isEngrSfcUsrDef) && $isEngrSfcUsrDef === 1) {
					$printProfileEngraveSettings = $ppEngraveSettings->first();
					$engravedSurfaceId = $printProfileEngraveSettings->engraved_surface_id;
					// Changes By Satya For fixes
					$engraveSurfaceDetInit = new PrintProfileModels\EngravedSurface();
					$isUserDef = $engraveSurfaceDetInit->select('is_user_defined')
						->where('xe_id', $engravedSurfaceId)
						->first();
					if ($engravedSurfaceId > 0 && $isUserDef->is_user_defined === 1) {
						// Update Engrave Settings
						$engSfcUpdate = new PrintProfileModels\EngravedSurface();
						$engSfcUpdate->where(
							'xe_id', $engravedSurfaceId
						)
							->update($updateEngraveMaster);
					} else {
						$saveEngraveRecord = new PrintProfileModels\EngravedSurface(
							$updateEngraveMaster
						);
						if ($saveEngraveRecord->save()) {
							$engravedSurfaceId = $saveEngraveRecord->xe_id;
						}
					}
				} else {
					if ($ppEngraveSettings->count() > 0) {
						$deleteSurfaceId = $ppEngraveSettings->first()->engraved_surface_id;
					}
					$engravedSurfaceId = $engrSfcUserDefKey;
				}
				$ppEngraveSettRecord += [
					'engraved_surface_id' => $engravedSurfaceId,
				];
				$profileEngSettUpdt = new PrintProfileModels\PrintProfileEngraveSetting();
				$profileEngSettUpdt->where(['print_profile_id' => $printProfileId])
					->update($ppEngraveSettRecord);
			} else {
				// Add New Engrave Settings Record
				if (isset($isEngrSfcUsrDef) && $isEngrSfcUsrDef == 1) {
					$saveEngraveRecord = new PrintProfileModels\EngravedSurface(
						$updateEngraveMaster
					);
					if ($saveEngraveRecord->save()) {
						$engraveSurfInsId = $saveEngraveRecord->xe_id;
					}
				} else {
					$engraveSurfInsId = $engrSfcUserDefKey;
				}

				$ppEngraveSettRecord += [
					'engraved_surface_id' => $engraveSurfInsId,
				];
				$newEngraveSurfaceSettings
				= new PrintProfileModels\PrintProfileEngraveSetting(
					$ppEngraveSettRecord
				);
				$newEngraveSurfaceSettings->save();
			}
		}

		$engraveSaveData = [
			'print_profile' => [
				'is_laser_engrave_enabled' => $getEngraveData[
					'is_laser_engrave_enabled'
				],
			],
		];

		// update engrave status
		try {
			$printProfileUpdate = new PrintProfileModels\PrintProfile();
			$printProfileUpdate->where('xe_id', $printProfileId)
				->update($engraveSaveData['print_profile']);
			return true;
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * Save/Update Image Settings of Print Profile
	 *
	 * @param $request        Slim's Request object
	 * @param $response       Slim's Response object
	 * @param $getImageData   Engrave Data
	 * @param $printProfileId Print Profile ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array
	 */
	private function processImageData(
		$getImageData,
		$printProfileId
	) {
		$imageResponse['status'] = 0;
		$saveImageData = [
			'image_settings' => json_encode($getImageData),
		];
		try {
			$printProfile = new PrintProfileModels\PrintProfile();
			$printProfile->where('xe_id', $printProfileId)
				->update($saveImageData);
			$imageResponse['status'] = 1;
		} catch (\Exception $e) {
			$imageResponse['status'] = 0;
		}

		return $imageResponse;
	}

	/**
	 * Save/Update Color Settings of Print Profile
	 *
	 * @param $request        Slim's Request object
	 * @param $response       Slim's Response object
	 * @param $getColorData   Engrave Data
	 * @param $printProfileId Print Profile ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array
	 */
	private function processColorData(
		$getColorData,
		$printProfileId
	) {
		$colorResponse = [];
		$saveColorData = [
			'color_settings' => json_encode($getColorData),
		];

		$printProfileUpdate = new PrintProfileModels\PrintProfile();
		try {
			$printProfileUpdate->where('xe_id', $printProfileId)
				->update($saveColorData);
			$colorResponse['status'] = 1;
		} catch (\Exception $e) {
			$colorResponse['status'] = 0;
		}

		return $colorResponse;
	}

	/**
	 * Save/Update Order Data
	 *
	 * @param $request        Slim's Request object
	 * @param $response       Slim's Response object
	 * @param $getOrderData   Engrave Data
	 * @param $printProfileId Print Profile ID
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array
	 */
	private function processOrderData(
		$getOrderData,
		$printProfileId
	) {
		$orderResponse = [];
		$saveOrderData = [
			'order_settings' => json_encode($getOrderData),
		];

		$printProfileUpdate = new PrintProfileModels\PrintProfile();
		try {
			$printProfileUpdate->where('xe_id', $printProfileId)
				->update($saveOrderData);
			$orderResponse['status'] = 1;
		} catch (\Exception $e) {
			$orderResponse['status'] = 0;
		}

		return $orderResponse;
	}

	/**
	 * PUT: Update Print Profile records
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   8 Nov 2019
	 * @return A JSON Response
	 */
	public function updatePrintProfile($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Print Profile', 'error'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$getStoreDetails = get_store_details($request);
		$storeId = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
		if (!empty($allPostPutVars['data'])) {
			$getAllFormData = json_clean_decode($allPostPutVars['data'], true);

			// Step1: Process Profile Data
			$respOfProfileData = $this->processProfileData(
				$request, $response, $args, $getAllFormData['profile'], 'update'
			);

			if (!empty($respOfProfileData['last_inserted_id'])
				&& is_valid_var($respOfProfileData['last_inserted_id'], 'int', 'bool')
			) {
				// Last Insert ID
				$lastInsertedId = $respOfProfileData['last_inserted_id'];

				// Step2: Process Feature Data
				$this->processFeatureData(
					$getAllFormData['features_data'],
					$lastInsertedId,
					'update'
				);
				// Step4: Process VDP Data
				$this->processVdpData(
					$getAllFormData['vdp_data'],
					$lastInsertedId
				);
				// Step5: Process Laser Engrave data
				$this->processEngraveData(
					$getAllFormData['laser_engrave_data'],
					$lastInsertedId
				);
				// Step6: Process Image Data
				$this->processImageData(
					$getAllFormData['image_data'],
					$lastInsertedId
				);
				// Step7: Process Color Data
				$this->processColorData(
					$getAllFormData['color_data'],
					$lastInsertedId
				);
				// Step8: Process Order Data
				$this->processOrderData(
					$getAllFormData['order_data'],
					$lastInsertedId
				);
				// Step9: Process Text Settings
				$this->processTextData($getAllFormData['text_data'], $lastInsertedId);

				// Step10: Process BG Settings
				$this->processMiscData($getAllFormData['misc_data'], $lastInsertedId, $storeId);

				// Step11: Process Name and Number Settings
				if (!empty($getAllFormData['name_number_data'])) {
					$this->processNameNumberData(
						$getAllFormData['name_number_data'], $lastInsertedId
					);
				}

				// Step12: Process AR Settings
				if (!empty($getAllFormData['ar_data'])) {
					$this->processArData(
						$getAllFormData['ar_data'], $lastInsertedId
					);
				}

				// Step13: Process Language Settings
				if (!empty($getAllFormData['language_data'])) {
					$this->processLanguageData(
						$getAllFormData['language_data'], $lastInsertedId, $storeId
					);
				}
				// Step14: Process Embroidery Settings
				if (!empty($getAllFormData['embroidery'])) {
					$this->processEmbroideryData(
						$getAllFormData['embroidery'], $lastInsertedId
					);
				}

				// Step15: Process Sticker Settings
				if (!empty($getAllFormData['sticker'])) {
					$this->processStickerData(
						$getAllFormData['sticker'], $lastInsertedId
					);
				}

				// Set Publish Status
				$printProfilePublish = [
					'is_draft' => $getAllFormData['is_draft'],
				];
				try {
					$printProfileInit = new PrintProfileModels\PrintProfile();
					$printProfileInit->where('xe_id', $lastInsertedId)
						->update($printProfilePublish);
					if ($this->createJsonFile($request, $response, $lastInsertedId)) {
						$jsonResponse = [
							'status' => 1,
							'print_profile_insert_id' => $lastInsertedId,
							'message' => message('Print Profile', 'updated'),
						];
					}
				} catch (\Exception $e) {
					$jsonResponse['exception'] = show_exception() === true
					? $e->getMessage() : '';
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Get Single Print Profile Record
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's arg object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   8 Nov 2019
	 * @return A JSON Response
	 */
	public function getSinglePrintProfile($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [];
		$printProfileRespRecord = [];
		$printProfileId = to_int($args['id']);
		$getStoreDetails = get_store_details($request);

		$printProfileInit = new PrintProfileModels\PrintProfile();
		$getPrintProfileInfo = $printProfileInit->where(
			[
				'xe_id' => $printProfileId,
				'store_id' => $getStoreDetails['store_id'],
			]
		)
			->with('features', 'engraves')
			->first();

		// Getting Profile Records feature_data
		$printProfileRespRecord['profile_data'] = [];
		$printProfileRespRecord['image_data'] = [];
		$printProfileRespRecord['color_data'] = [];
		$printProfileRespRecord['order_data'] = [];
		$printProfileRespRecord['text_data'] = [];
		$printProfileRespRecord['misc_data'] = [];
		$printProfileRespRecord['name_number_data'] = [];
		$printProfileRespRecord['sticker'] = [];
		$printProfileRespRecord['ar_data'] = [];
		$printProfileRespRecord['embroidery'] = [];
		$printProfileRespRecord['vdp_data'] = [
			'is_enabled' => 0,
			'vdp' => '{}',
		];

		// Check if print profile exist in this ID
		if (!empty($getPrintProfileInfo->xe_id)) {
			$printProfileRespRecord['profile_data'] = [
				'name' => $getPrintProfileInfo->name,
				'description' => $getPrintProfileInfo->description,
				'image_path' => $getPrintProfileInfo->file_name_url,
				'thumbnail' => $getPrintProfileInfo->thumbnail,
			];

			$printProfileRespRecord['vdp_data'] = [
				'is_enabled' => $getPrintProfileInfo->is_vdp_enabled,
				'vdp' => isset($getPrintProfileInfo->is_vdp_enabled)
				? $getPrintProfileInfo->is_vdp_enabled : '{}',
			];

			$printProfileRespRecord['image_data'] = json_clean_decode(
				$getPrintProfileInfo->image_settings, true
			);
			$printProfileRespRecord['color_data'] = json_clean_decode(
				$getPrintProfileInfo->color_settings, true
			);
			$printProfileRespRecord['order_data'] = json_clean_decode(
				$getPrintProfileInfo->order_settings, true
			);
			if (!empty($getPrintProfileInfo->text_settings)) {
				$printProfileRespRecord['text_data'] = json_clean_decode(
					$getPrintProfileInfo->text_settings, true
				);
			}
			if (!empty($getPrintProfileInfo->misc_settings)) {
				$printProfileRespRecord['misc_data'] = json_clean_decode(
					$getPrintProfileInfo->misc_settings, true
				);
			}
			if (!empty($getPrintProfileInfo->name_number_settings)) {
				$printProfileRespRecord['name_number_data'] = json_clean_decode(
					$getPrintProfileInfo->name_number_settings, true
				);
			}
			if (!empty($getPrintProfileInfo->ar_settings)) {
				$printProfileRespRecord['ar_data'] = json_clean_decode(
					$getPrintProfileInfo->ar_settings, true
				);
			}
			if (!empty($getPrintProfileInfo->sticker_settings)) {
                $stickerArray = json_clean_decode(
                    $getPrintProfileInfo->sticker_settings, true
                );
                $sticker = [];
                if (!empty($stickerArray)) {
                    $resourceDirR = ASSETS_PATH_R .'stickers';
                    if (!isset($stickerArray['is_sticker_exit'])) {
                        $sticker['is_sticker_enabled'] = $stickerArray['is_sticker_enabled'];
                        if (!empty($stickerArray['shapes'])) {
                            $stickerArray['shapes']['price'] = isset($stickerArray['shapes']['price'])?$stickerArray['shapes']['price']:0;
                            foreach ($stickerArray['shapes']['option_list'] as $k => $v) {
                                if ($v['name'] =='Basic shapes') {
                                    $stickerArray['shapes']['option_list'][$k]['options'][0]['file_name'] =$resourceDirR.'/'.'circle.svg';
                                    $stickerArray['shapes']['option_list'][$k]['options'][1]['file_name'] = $resourceDirR.'/'.'rect.svg';
                                    $stickerArray['shapes']['option_list'][$k]['options'][2]['file_name'] = $resourceDirR.'/'.'heart.svg';
                                    $stickerArray['shapes']['option_list'][$k]['options'][3]['file_name'] = $resourceDirR.'/'.'star.svg';
                                    $stickerArray['shapes']['option_list'][$k]['options'][4]['file_name'] = $resourceDirR.'/'.'round_corner.svg';
                                }
                            }
                        }
                        $sticker['shapes'] = isset($stickerArray['shapes']) ? 
                        $stickerArray['shapes']:[];
                        $sticker['material'] = isset($stickerArray['material']) ? 
                        $stickerArray['material']:[];
                        $sticker['sheet'] = isset($stickerArray['sheet']) ? 
                        $stickerArray['sheet']:[];
                        $sticker['roll'] = isset($stickerArray['roll']) ? 
                        $stickerArray['roll']:[];
                    } else {
                        $sticker = $stickerArray;   
                    }
                }
                $printProfileRespRecord['sticker'] = $sticker;
            }
			if (!empty($getPrintProfileInfo->embroidery_settings)) {
				$printProfileRespRecord['embroidery'] = json_clean_decode(
					$getPrintProfileInfo->embroidery_settings, true
				);
			}
		}

		// Section : Order Data Allowed Format List will be appended to
		// Gather Selected Order's Allowed format ids allowed_image_format
		$selectedOrderFormats = [];
		if (!empty($printProfileRespRecord['order_data']['allowed_order_format'])
			&& is_valid_array($printProfileRespRecord['order_data']['allowed_order_format'])
		) {
			$selectedOrderFormats = $printProfileRespRecord['order_data']['allowed_order_format'];
		}
		$getOrderFormatList = $this->getAllowedFormats($selectedOrderFormats, 'order');

		$printProfileRespRecord['order_data']['allowed_order_formats'] = $getOrderFormatList;
		// Remove the key as this key is utilized
		unset($printProfileRespRecord['order_data']['allowed_order_format']);

		// Section : Image Data
		// Get Allowed Formats and set which formats are selected
		$selectedAllwdFormats = [];
		// Gather Selected Order's Allowed format ids
		if (!empty($printProfileRespRecord['image_data']['allowed_image_format'])
			&& is_valid_array($printProfileRespRecord['image_data']['allowed_image_format'])
		) {
			$selectedAllwdFormats = $printProfileRespRecord['image_data']['allowed_image_format'];
		}
		$allowedFormatList = $this->getAllowedFormats($selectedAllwdFormats, 'image');

		// Replace selected ID array with Readable formatted array
		$printProfileRespRecord['image_data']['allowed_image_format'] = $allowedFormatList;

		// Collect all feature ids for checking for selected property
		$featureRelations = [];
		if (!empty($getPrintProfileInfo->features)
			&& is_valid_array($getPrintProfileInfo->features->toArray())
		) {
			$featureRelations = $getPrintProfileInfo->features->toArray();
		}

		// Looping thorugh Feature Types
		$printProfileRespRecord['feature_data'] = $this->getPrintProfileFeatures(
			$featureRelations
		);

		// Section : Laser Engrave Data
		$isLsrEngrvEnabled = 0;
		if (!empty($getPrintProfileInfo->is_laser_engrave_enabled)
			&& is_valid_var(
				$getPrintProfileInfo->is_laser_engrave_enabled, 'int', 'bool'
			)
		) {
			$isLsrEngrvEnabled = $getPrintProfileInfo->is_laser_engrave_enabled;
		}
		$engraveDetails = [];
		if (!empty($getPrintProfileInfo->engraves)) {
			$engraveDetails = $getPrintProfileInfo->engraves->toArray();
		}

		$printProfileRespRecord['engrave'] = $this->getPrintProfileEngraves(
			$engraveDetails, $isLsrEngrvEnabled
		);

		// Get a Default Font Details from Category Relations
		$getAssetTypeDetails = $this->assetsTypeId('fonts');
		$fontAssetTypeId = $getAssetTypeDetails['asset_type_id'];
		$assetTypeRelObj = new PrintProfileModels\PrintProfileAssetsCategoryRel();
		$getFontCatDetails = $assetTypeRelObj->where(
			[
				'asset_type_id' => $fontAssetTypeId,
				'print_profile_id' => $printProfileId,
			]
		)
			->with('font_category_rel')
			->with('font_category_rel.font')
			->get();
		$selectedFont = [];
		if (!empty($getFontCatDetails)) {
			$fontsByCategories = $getFontCatDetails->toArray();
			$fontDetails = [];
			foreach ($fontsByCategories as $key => $eachFont) {
				if (!empty($eachFont['font_category_rel']['font'])) {
					$fontDetails[] = $eachFont['font_category_rel']['font'];
				}
			}
			if (!empty($fontDetails[0])) {
				$printProfileRespRecord['font_data'] = [
					'id' => $fontDetails[0]['xe_id'],
					'name' => strval($fontDetails[0]['name']),
					'price' => $fontDetails[0]['price'],
					'font_family' => $fontDetails[0]['font_family'],
					'file_name' => $fontDetails[0]['file_name'],
				];
			}
		}

		$printProfileRespRecord['is_draft'] = !empty($getPrintProfileInfo->is_draft)
		? is_valid_var($getPrintProfileInfo->is_draft, 'int', 'int') : 0;
		$printProfileRespRecord['is_disabled'] = !empty($getPrintProfileInfo->is_disabled)
		? is_valid_var($getPrintProfileInfo->is_disabled, 'int', 'int') : 0;
		$printProfileRespRecord['is_image_magick_enabled'] = to_int(is_installed('imagick'));
		$printProfileRespRecord['is_inkscape_enabled'] = to_int(is_installed('inkscape'));

		$jsonResponse = $printProfileRespRecord;
		if (isset($args['return_type']) && $args['return_type'] == 'array') {
			return $printProfileRespRecord;
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Get Products according to the Category, so that these selected products
	 * will be updated against Print Profiles
	 *
	 * @param $request     Slim's Request object
	 * @param $response    Slim's Response object
	 * @param $categoryIds Slim's Request object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array
	 */
	private function getProductsByCategory($request, $response, $categoryIds = null) {
		$allProductIds = [];
		if (isset($categoryIds) && count($categoryIds) > 0) {
			$getCategoryList = implode(',', $categoryIds);
			$getStoreProducts = $this->getProducts(
				$request, $response, ['categories' => $getCategoryList]
			);

			// In case of simple Products
			if (!empty($getStoreProducts['products'])) {
				$allProducts = $getStoreProducts['products'];
				if (!empty($allProducts)) {
					foreach ($allProducts as $key => $product) {
						$allProductIds[] = $product['id'];
					}
				}
			}
			// In case of Predecorators
			$getStorePredecos = $this->getProducts(
				$request, $response,
				[
					'categories' => $getCategoryList, 'is_customize' => 1,
				]
			);
			if (!empty($getStoreProducts['products'])) {
				$allProducts = $getStoreProducts['products'];
				if (!empty($allProducts)) {
					foreach ($allProducts as $key => $product) {
						$allProductIds[] = $product['id'];
					}
				}
			}
			$allProductIds = array_unique($allProductIds);
		}

		return $allProductIds;
	}

	/**
	 * Enable or Disable particular print profile by ID
	 *
	 * @param $selectedAssets Array of Selected Assets
	 * @param $assetsId       Asset's Id
	 * @param $categoryId     Category's Id
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Boolean
	 */
	private function checkIfCategorySelected(
		$selectedAssets,
		$assetsId,
		$categoryId
	) {
		if (!empty($assetsId) && $assetsId > 0
			&& !empty($categoryId) && $categoryId > 0
		) {
			if (!empty($selectedAssets) && is_valid_array($selectedAssets)) {
				foreach ($selectedAssets as $selectedAsset) {
					if ($selectedAsset['asset_type_id'] == $assetsId
						&& $selectedAsset['category_id'] == $categoryId
					) {
						return true;
					}
				}
			}
		}

		return false;
	}
	/**
	 * Enable or Disable particular print profile by ID
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Json
	 */
	public function disablePrintProfile($request, $response, $args) {
		$jsonResponse = [
			'status' => 1,
			'message' => message('Print Profile', 'error'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$profileDisabledIds = $args['id'];

		if (!empty($profileDisabledIds) && $profileDisabledIds > 0) {
			$printProfileInit = new PrintProfileModels\PrintProfile();
			$getProntProfileData = $printProfileInit->where(
				['xe_id' => $profileDisabledIds]
			);
			if ($getProntProfileData->count() > 0) {
				$printProfileInit = new PrintProfileModels\PrintProfile();
				$printAreaInit = $printProfileInit->find($profileDisabledIds);
				$printAreaInit->is_disabled = !$printAreaInit->is_disabled;
				if ($printAreaInit->save()) {
					$jsonResponse = [
						'status' => 1,
						'message' => message('Print Profile', 'done'),
					];
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Delete particular print profile by ID
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Json
	 */
	public function deletePrintProfile($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Print Profile', 'error'),
		];

		$serverStatusCode = OPERATION_OKAY;
		$success = 0;
		$printProfileDelId = $args['id'];

		if (!empty($printProfileDelId) && $printProfileDelId > 0) {
			$productSettRelation = new \App\Modules\Products\Models\PrintProfileProductSettingRel();
			$productSettData = $productSettRelation->where(
				'print_profile_id', $printProfileDelId
			);

			if ($productSettData->count() > 0) {
				$jsonResponse = [
					'status' => 0,
					'message' => message('Print Profile', 'associate'),
				];
			} else {
				$printProfileGtInit = new PrintProfileModels\PrintProfile();
				$printProfileGt = $printProfileGtInit->where(
					['xe_id' => $printProfileDelId]
				);

				if ($printProfileGt->count() > 0) {
					// Delete Print Profile
					$printProfileGt->delete();
					// Delete Feature Relations
					$featureRelation = new PrintProfileModels\PrintProfileFeatureRel();
					$featureRelDelete = $featureRelation->where(
						'print_profile_id', $printProfileDelId
					)->delete();
					// Delete Print Profile Asset Category Relation
					$assetsRelation
					= new PrintProfileModels\PrintProfileAssetsCategoryRel();
					$assetsRelDelete = $assetsRelation->where(
						'print_profile_id', $printProfileDelId
					)->delete();
					// Delete Engrave Settings
					$engraveSettings
					= new PrintProfileModels\PrintProfileEngraveSetting();
					$engraveSettDel = $engraveSettings->where(
						'print_profile_id', $printProfileDelId
					)->delete();
					// Delete Template Print Profile Relation
					$templateRelation
					= new \App\Modules\Templates\Models\TemplatePrintProfileRel();
					$templateRelDel = $templateRelation->where(
						'print_profile_id', $printProfileDelId
					)->delete();
					// Delete Engrave Surface
					$ppEngraveSettGtInit = new PrintProfileModels\PrintProfileEngraveSetting();
					$ppEngraveSettGt = $ppEngraveSettGtInit->where(
						'print_profile_id', $printProfileDelId
					)->select('engraved_surface_id');
					if ($ppEngraveSettGt->count() > 0) {
						$engraveSurfaceId = $ppEngraveSettGt->first()->engraved_surface_id;
						$engraveSfcInit = new PrintProfileModels\EngravedSurface();
						$engraveSfcGt = $engraveSfcInit->where(
							['xe_id' => $engraveSurfaceId, 'is_user_defined' => 1]
						);
						if (!empty($engraveSfcGt)) {
							$engraveSfcGt->delete();
						}
					}
					// Delete Print Profile Pricing
					$pricingTrashResponse = $this->deleteProfilePricing($printProfileDelId);

					// Delete print profile key from language file
					$profileLangRelation = $this->dltPrintProfileLang($printProfileDelId);
					$this->deleteOldFile(
						'print_profiles',
						'file_name',
						['xe_id' => $printProfileDelId],
						path('abs', 'print_profile')
					);
					$jsonResponse = [
						'status' => 1,
						'message' => message('Print Profile', 'deleted'),
					];
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Delete language key value from language file
	 *
	 * @param $printProfileId Print Profile id
	 *
	 * @author satyabrata@riaxe.com
	 * @date   18 May 2020
	 * @return Array
	 */
	public function dltPrintProfileLang($printProfileId) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Lanuage Relation', 'error'),
		];
		$unset = 0;
		if (!empty($printProfileId) && $printProfileId > 0) {
			$languageInit = new Language();
			$getLanguage = $languageInit->select('name')->where(['type' => 'tool'])->get();
			foreach ($getLanguage->toArray() as $langKey => $langValue) {
				$languagePath = 'tool/lang_' . strtolower($langValue['name']) . '.json';
				$languageLocation = path('read', 'language') . $languagePath;
				if (file_exists(path('abs', 'language') . $languagePath)) {
					$fileContents = file_get_contents($languageLocation);
					$fileData = json_clean_decode($fileContents);
					if (!empty($fileData['print_profiles'][$printProfileId])) {
						unset($fileData['print_profiles'][$printProfileId]);
						file_put_contents(path('abs', 'language') . $languagePath, json_clean_encode($fileData));
						$unset++;
					}
				}
			}
		}
		if ($unset > 0) {
			$jsonResponse = [
				'status' => 1,
				'message' => message('Lannguage Relation', 'deleted'),
			];
		}

		return $jsonResponse;
	}

	/**
	 * Delete Print Profile and associated records by Print Profile ID
	 *
	 * @param $printProfileId Print Profile id
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array
	 */
	public function deleteProfilePricing($printProfileId) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Print Profile Pricing', 'error'),
		];
		$success = 0;
		DB::beginTransaction();
		if (!empty($printProfileId) && $printProfileId > 0) {
			$printProfDeleteInit = new PrintProfileModels\Pricing\PrintProfilePricing();
			$getPricingDetails = $printProfDeleteInit->where(
				'print_profile_id', $printProfileId
			);
			if ($getPricingDetails->count() > 0) {
				$getPricingDetails = $getPricingDetails->select('xe_id')->first();
				$printProfilePricingid = $getPricingDetails->xe_id;
				$priceModuleSettInit
				= new PrintProfileModels\Pricing\PriceModuleSetting();
				$priceModuleSettGet = $priceModuleSettInit->where(
					'print_profile_pricing_id', $printProfilePricingid
				);
				if ($priceModuleSettGet->count() > 0) {
					$moduleSettingList = $priceModuleSettGet->get()
						->pluck('xe_id')
						->toArray();
					// Advance Price Setting
					$priceModuleSettInit
					= new PrintProfileModels\Pricing\PriceModuleSetting();
					$modSetAdvPrcSettList = $priceModuleSettInit->where(
						'print_profile_pricing_id', $printProfilePricingid
					)->select('advance_price_settings_id')->first();
					$advPriceSettId
					= $modSetAdvPrcSettList->advance_price_settings_id;
					if ($advPriceSettId > 0) {
						$advPrcSettInit
						= new PrintProfileModels\Pricing\AdvancePriceSetting();
						$advPriceSettGtId = $advPrcSettInit->find($advPriceSettId);
						if ($advPriceSettGtId->delete()) {
							$success++;
						}
					}

					// Price Tier value  & Tier White Base
					$tierPrcInit = new PrintProfileModels\Pricing\TierPrice();
					$tierPriceGt = $tierPrcInit->where(
						'price_module_setting_id', $moduleSettingList
					)
						->get()
						->pluck('xe_id');
					if (!empty($tierPriceGt) && count($tierPriceGt) > 0) {
						$tierWhiteBaseInit
						= new PrintProfileModels\Pricing\TierWhitebase();
						$tierWhiteBaseDel = $tierWhiteBaseInit->whereIn(
							'price_tier_value_id', $tierPriceGt
						);
						if ($tierWhiteBaseDel->delete()) {
							$success++;
						}
						$tierPrcDelInit = new PrintProfileModels\Pricing\TierPrice();
						$tierPrcDelInit->where(
							'price_module_setting_id', $moduleSettingList
						);
						if ($tierPrcDelInit->delete()) {
							$success++;
						}
					}

					// Price Tier Quantity Range
					$prcTierQtyRangeInit
					= new PrintProfileModels\Pricing\PriceTierQuantityRange();
					$prcTierQtyRangeInit->whereIn(
						'price_module_setting_id', $moduleSettingList
					);
					if ($prcTierQtyRangeInit->count() > 0) {
						if ($prcTierQtyRangeInit->delete()) {
							$success++;
						}
					}

					// Delete Price Default Setting
					$priceDefSettInit
					= new PrintProfileModels\Pricing\PriceDefaultSetting();
					$priceDefSettGt = $priceDefSettInit->whereIn(
						'price_module_setting_id', $moduleSettingList
					);
					if ($priceDefSettGt->count() > 0) {
						if ($priceDefSettGt->delete()) {
							$success++;
						}
					}

					// Delete Price Module Settings
					$prcModuleSettDelInit
					= new PrintProfileModels\Pricing\PriceModuleSetting();
					$prcModuleSettDel = $prcModuleSettDelInit->where(
						'print_profile_pricing_id', $printProfilePricingid
					);
					if ($prcModuleSettDel->count() > 0) {
						if ($prcModuleSettDel->delete()) {
							$success++;
						}
					}
				}
				// Delete Print Profile
				if ($success > 0) {
					if ($getPricingDetails->delete()) {
						DB::commit();
						$jsonResponse = [
							'status' => 1,
							'message' => message(
								'Print Profile Pricing', 'deleted'
							),
						];
					} else {
						DB::rollback();
						$jsonResponse += [
							'message' => message(
								'Print Profile Pricing', 'reverted'
							),
						];
					}
				}
			}
		}

		return $jsonResponse;
	}
	/**
	 * Post : Assign Print Profiles to Category
	 * Used in all Asset's Manage Category Section
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return All Print Profile List
	 */
	public function assignCategoryToPrintProfile($request, $response) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Print Profile Assign', 'error'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();

		$getAssetTypeId = $this->assetsTypeId($allPostPutVars['asset_slug']);
		if ((!empty($getAssetTypeId['asset_type_id'])
			&& $getAssetTypeId['asset_type_id'] > 0)
			&& (!empty($allPostPutVars['category_id'])
				&& $allPostPutVars['category_id'] > 0)
		) {
			$printProfileIds = json_clean_decode(
				$allPostPutVars['print_profile_id'], true
			);

			$categoryIdList = [];
			$selectedCatId = to_int($allPostPutVars['category_id']);
			$assetTypeKey = to_int($getAssetTypeId['asset_type_id']);
			// Fetch and Append subcategories if exists
			$categoryObj = new \App\Components\Models\Category();
			$categoryGet = $categoryObj->where(
				[
					'parent_id' => $selectedCatId,
					'asset_type_id' => $assetTypeKey,
				]
			);
			if ($categoryGet->count() > 0) {
				$categoryDbList = $categoryGet->get()->pluck('xe_id')->toArray();
				$categoryIdList = $categoryDbList;
			}
			$categoryIdList[] = $selectedCatId;

			$saveRelation = [];
			foreach ($printProfileIds as $printProfilekey => $printProfileId) {
				foreach ($categoryIdList as $catKey => $category) {
					$saveRelation[] = [
						'print_profile_id' => $printProfileId,
						'asset_type_id' => $getAssetTypeId['asset_type_id'],
						'category_id' => $category,
					];
				}
			}
			// First Delete the Old relationship if any with this combinations
			$ppAssetCatRelGtInit
			= new PrintProfileModels\PrintProfileAssetsCategoryRel();
			$ppAssetCatRelGtInit->where('asset_type_id', $assetTypeKey)
				->whereIn('category_id', $categoryIdList)
				->delete();
			if (!empty($allPostPutVars['category_id'])) {
				$catInit = new CommonCategory();
				$getparent = $catInit->select('parent_id')->where('xe_id', $allPostPutVars['category_id'])->first()->parent_id;
				if ($getparent > 0) {
					$getSubcats = $catInit->select('xe_id')->where('parent_id', $getparent)->get()->toArray();
					if (!empty($getSubcats)) {
						foreach ($getSubcats as $subcatKey => $subcat) {
							$subcatArray[] = $subcat['xe_id'];
						}
					}
					// $ppAssetCatRelGtInit = new PrintProfileModels\PrintProfileAssetsCategoryRel();
					// $relValue = $ppAssetCatRelGtInit->whereIn('category_id', $subcatArray);
					// if ($relValue->count() === 0) {
					//     $ppAssetCatRelGtInit->where('asset_type_id', $assetTypeKey)
					//         ->where('category_id', $getparent)
					//         ->delete();
					// }
				}
			}
			// Save all Relation combinations
			$ppAssetCatRelSvInit
			= new PrintProfileModels\PrintProfileAssetsCategoryRel();
			if ($ppAssetCatRelSvInit->insert($saveRelation)) {
				$jsonResponse = [
					'status' => 1,
					'message' => message('Print Profile Assign', 'saved'),
				];
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Post: Assign Products with Print Profiles according to choosen Categories in
	 * Print Profile Module
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   30 Jan 2020
	 * @return Json
	 */
	public function saveProductsRelation($request, $response) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Product Category Assign', 'error'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $request->getParsedBody();
		$printProfileId = to_int($allPostPutVars['print_profile_id']);
		$printProfile = new PrintProfileModels\PrintProfile();
		$getPrintProfile = $printProfile->where('xe_id', $printProfileId);
		if ($printProfileId > 0 && $getPrintProfile->count() > 0) {
			$fullColorStatus = 0;
			if (!empty($allPostPutVars['is_full_color'])) {
				$fullColorStatus = to_int($allPostPutVars['is_full_color']);
			}
			$ppFullColor = new PrintProfileModels\PrintProfile();
			$ppFullColor->where('xe_id', $printProfileId)->update(['allow_full_color' => $fullColorStatus]);
			if (!empty($allPostPutVars['category_ids'])) {
				$categories = $allPostPutVars['category_ids'];
				$categoriesToArray = json_clean_decode($categories, true);
				// Find the product's slug key
				$getAssetType = new PrintProfileModels\AssetType();
				$assetTypeId = $getAssetType->where('slug', 'LIKE', 'product' . '%')
					->select('xe_id')
					->first()
					->xe_id;
				// Collect print profile - Assets relation array
				$printProfileAssetsData = [];
				foreach ($categoriesToArray as $category) {
					$printProfileAssetsData[] = [
						'print_profile_id' => $printProfileId,
						'asset_type_id' => $assetTypeId,
						'category_id' => $category,
					];
				}
				try {
					$printProfileFind = new PrintProfileModels\PrintProfile();
					$printProfileSync = $printProfileFind->find($printProfileId);
					// Update Print Profile Assets Relation
					// Check if assets are already assigned to print profile
					$profAssetCatRelDel = new PrintProfileModels\PrintProfileAssetsCategoryRel();
					$getPrintProfileRel = $profAssetCatRelDel->where(['print_profile_id' => $printProfileId]);
					// Find the product's slug key
					$getAssetType = new PrintProfileModels\AssetType();
					$productTypeId = $getAssetType->where('slug', 'LIKE', 'product' . '%')
						->select('xe_id')
						->first()
						->xe_id;
					$getPrintProfileRel->where('asset_type_id', $productTypeId)->delete();
					$profAssetCatRelIns = new PrintProfileModels\PrintProfileAssetsCategoryRel();

					if ($profAssetCatRelIns->insert($printProfileAssetsData)) {
						$productIdList = $this->getProductsByCategory(
							$request, $response, $categoriesToArray
						);
						// Update Which Print Profile assigned to Products
						if ($this->updtProdPrintProfRel(
							$productIdList, $printProfileId, $getStoreDetails['store_id']
						)
						) {
							$jsonResponse = [
								'status' => 1,
								'message' => message(
									'Product Category Assign', 'done'
								),
							];
						}
					}
				} catch (\Exception $e) {
					$jsonResponse['exception'] = show_exception() === true
					? $e->getMessage() : '';
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Get: Get Products category List with is_selected option
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author tanmayap@riaxe.com
	 * @date   30 Jan 2020
	 * @return Json
	 */
	public function getProductsRelation($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Product Assigned Category', 'not_found'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$printProfileId = $args['id'];
		$fullColorStatus = 0;
		$getCategories = [];

		if ($printProfileId > 0) {
			// Get full color status
			$printProfile = new PrintProfileModels\PrintProfile();
			$getFullColor = $printProfile->where('xe_id', $printProfileId)
				->select('allow_full_color')
				->first();
			$fullColorStatus = to_int($getFullColor->allow_full_color);
			// Find the product's slug key
			$getAssetType = new PrintProfileModels\AssetType();
			$assetTypeId = $getAssetType->where('slug', 'LIKE', 'product' . '%')
				->select('xe_id')
				->first()
				->xe_id;
			// Get selected category id list
			$profileAssetCatObj
			= new PrintProfileModels\PrintProfileAssetsCategoryRel();
			$selectedCategories = $profileAssetCatObj->where(
				[
					'asset_type_id' => $assetTypeId,
					'print_profile_id' => $printProfileId,
				]
			)
				->get()
				->pluck('category_id')
				->toArray();
			$selectedCategories = array_unique($selectedCategories);

			// Making the final list
			$finalCategoryList = [];
			$apiEndpoint = '';
			$getProdCategories = call_curl(
				[], 'products/categories', 'GET'
			);
			if (!empty($getProdCategories['status'])
				&& $getProdCategories['status'] == 1
			) {
				$getCategories = $getProdCategories['data'];
			}
			if (count($getCategories) > 0) {
				foreach ($getCategories as $catKey => $category) {
					$finalCategoryList[$catKey] = $category;
					$finalCategoryList[$catKey]['is_selected'] = 0;
					if (in_array($category['id'], $selectedCategories)) {
						$finalCategoryList[$catKey]['is_selected'] = 1;
					}
				}
			}
			$jsonResponse = [
				'status' => 1,
				'allow_full_color' => $fullColorStatus,
				'data' => $finalCategoryList,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Write the Pront Profile details to the Json File and save it inside
	 * Settings folder according to Store ID
	 *
	 * @param $request        Slim's Request object
	 * @param $response       Slim's Response object
	 * @param $printProfileId Print profile ID
	 *
	 * @author debashrib@riaxe.com
	 * @date   20 Jan 2020
	 * @return 1 or 0
	 */
	public function createJsonFile($request, $response, $printProfileId) {
		$responseStatus = 0;
		$getStoreDetails = get_store_details($request);
		$jsonFileLocation = path('abs', 'setting');
		$jsonFileLocation .= 'stores/' . $getStoreDetails['store_id'];
		$jsonFileLocation .= '/print_profile';
		// Create directory if not exists
		create_directory($jsonFileLocation);
		$jsonFilePath = $jsonFileLocation . '/' . $printProfileId . '.json';

		// Get print Profile Data as per the Print Profile ID
		$printProfileData = $this->getSinglePrintProfile(
			$request, $response, [
				'id' => $printProfileId,
				'return_type' => 'array',
			]
		);
		if (!empty($printProfileData)) {
			// Unset extra keys from an array
			unset(
				$printProfileData['is_draft'],
				$printProfileData['is_disabled'],
				$printProfileData['is_image_magick_enabled']
			);
			// Get Pricing Records as per the Print Profile ID
			$printProfilePriceInit = new PricingController();
			$pricingData = $printProfilePriceInit->getPricingDetails(
				$request, $response, [
					'id' => $printProfileId,
					'return_type' => 'array',
				]
			);
			$printProfileData['print_profile_pricing'] = $pricingData;
			$jsonData = json_encode($printProfileData, JSON_PRETTY_PRINT);
			$responseStatus = write_file($jsonFilePath, $jsonData);
			if ($responseStatus) {
				$this->updateRevisionNo($getStoreDetails['store_id']);
			}
		}

		return $responseStatus;
	}
	/**
	 * Delete Json file which was generated during file save
	 *
	 * @param $profileId Print Profile Primary Key
	 * @param $storeId   Associated Store's id
	 *
	 * @author tanmayap@riaxe.com
	 * @author debashrib@riaxe.com
	 * @date   22 Jan 2020
	 * @return array
	 */
	protected function deletePrintJsonFile($profileId, $storeId) {
		if (!empty($profileId) && $profileId > 0
			&& !empty($storeId) && $storeId > 0
		) {
			$jsonFileName = path('abs', 'setting');
			$jsonFileName .= 'stores/' . $storeId;
			$jsonFileName .= '/print_profile' . '/' . $profileId . '.json';
			if (file_exists($jsonFileName)) {
				return delete_file($jsonFileName);
			}
		}
		return false;
	}

	/**
	 * Get the order/images allowed formats for Print Profile
	 *
	 * @param $selectedFormats Array of selected format
	 * @param $type            order/image
	 *
	 * @author tanmayap@riaxe.com
	 * @author debashrib@riaxe.com
	 * @date   22 Jan 2020
	 * @return array
	 */
	private function getAllowedFormats($selectedFormats, $type) {
		$allowedFormats = [];
		$selectedMasterList = [];
		$profileAllowedFmts = new PrintProfileModels\PrintProfileAllowedFormat();
		$profileAlwdFmtsInit = $profileAllowedFmts->where(
			['type' => $type]
		);

		if ($profileAlwdFmtsInit->count() > 0) {
			$allowedFormats = $profileAlwdFmtsInit->select(
				'xe_id as id', 'name', 'is_disabled'
			)
				->get()
				->toArray();
		}
		if (!empty($allowedFormats)) {
			foreach ($allowedFormats as $allFmtKey => $allowedFormat) {
				$selectedMasterList[$allFmtKey] = [
					'id' => $allowedFormat['id'],
					'name' => $allowedFormat['name'],
					// 'type' => $allowedFormat['type'],
					'is_disabled' => $allowedFormat['is_disabled'],
					'is_selected' => count($selectedFormats) > 0 ?
					to_int(
						in_array($allowedFormat['id'], $selectedFormats)
					)
					: 0,
				];
			}
		}

		return $selectedMasterList;
	}
	/**
	 * Get the features for Print Profile
	 *
	 * @param $selectedFeatures array of selected features
	 *
	 * @author tanmayap@riaxe.com
	 * @author debashrib@riaxe.com
	 * @date   22 Jan 2020
	 * @return array
	 */
	private function getPrintProfileFeatures($selectedFeatures) {
		$getFeatureList = [];
		$getSelectedFeatures = [];
		// Collect all feature ids for checking for selected property
		// debug($selectedFeatures->toArray(), true);
		if (!empty($selectedFeatures) && is_valid_array($selectedFeatures)) {
			$getSelectedFeatures = array_column($selectedFeatures, 'feature_id');
		}

		$feature = new PrintProfileModels\Feature();
		if ($feature->count() > 0) {
			$getAllFeatures = $feature->get();
		}

		foreach ($getAllFeatures as $featureKey => $feature) {
			$getFeatureList[$featureKey] = [
				'id' => $feature->xe_id,
				'name' => $feature->name,
				'slug' => $feature->slug,
				'asset_type_id' => $feature->asset_type_id,
				'is_selected' => in_array($feature->xe_id, $getSelectedFeatures)
				? 1 : 0,
			];
		}

		return $getFeatureList;
	}

	/**
	 * Get the Engraves for Print Profile
	 *
	 * @param $engraves         Array of engraves
	 * @param $isEngraveEnabled Laser Engrave status
	 *
	 * @author tanmayap@riaxe.com
	 * @author debashrib@riaxe.com
	 * @date   22 Jan 2020
	 * @return array
	 */
	private function getPrintProfileEngraves($engraves, $isEngraveEnabled) {
		$printProfileRespRecord = [];
		// Section : Laser Engrave Data
		$engraveSfcInit = new PrintProfileModels\EngravedSurface();
		$engraveSurfaceMasterInit = $engraveSfcInit->where('is_user_defined', 0);
		// If Engrave Data Exists
		if (is_valid_array($engraves)) {
			// get Engrave surface list
			$profileEngraveSettInit = new PrintProfileModels\PrintProfileEngraveSetting();
			$getUserDefEngId = $profileEngraveSettInit->where(
				'print_profile_id', $engraves['print_profile_id']
			)
				->select('engraved_surface_id')
				->first();
			$userDefEngSettId = $getUserDefEngId->engraved_surface_id;
			$engaveSurfaces = $engraveSurfaceMasterInit->orWhere(
				'xe_id', $userDefEngSettId
			)
				->get();
			foreach ($engaveSurfaces as $engraveSurfaceKey => $engraveSurface) {
				$printProfileRespRecord['engrave_surface_list'][$engraveSurfaceKey]
				= [
					'id' => $engraveSurface->xe_id,
					'is_selected' =>
					$engraveSurface->xe_id === $engraves['engraved_surface_id']
					? 1 : 0,
					'surface_name' => $engraveSurface->surface_name,
					// Shadow parameters
					'shadow_direction' => $engraveSurface->shadow_direction,
					'shadow_size' => $engraveSurface->shadow_size,
					'shadow_opacity' => $engraveSurface->shadow_opacity,
					'shadow_strength' => $engraveSurface->shadow_strength,
					'shadow_blur' => $engraveSurface->shadow_blur,
					'is_user_defined' => $engraveSurface->is_user_defined,
				];
				$isEngravedTypeImage = 0;
				$engravedImagePath = null;
				$engravedImagePathThumb = null;
				$engraveColorCode = null;
				$isEngravedPreviewTypeImage = 0;
				$engravedPreviewImagePath = null;
				$engravedPreviewImagePathThumb = null;
				$engravePreviewColorCode = null;
				// Skip System Defined Data. Get into Custom Records
				if (isset($engraveSurface['is_user_defined'])
					&& $engraveSurface['is_user_defined'] == 1
				) {
					// If User Defined Data then, Fetch - Engrave Image,
					if (isset($engraveSurface->engraved_type)
						&& $engraveSurface->engraved_type == 'image'
					) {
						$isEngravedTypeImage = 1;
						if (!empty($engraveSurface->engrave_type_value)
							&& $engraveSurface->engrave_type_value != ''
						) {
							$engravedImagePath = path('read', 'print_profile')
							. $engraveSurface->engrave_type_value;
							$engravedImagePathThumb = path('read', 'print_profile')
							. 'thumb_' . $engraveSurface->engrave_type_value;
						}
					} else {
						$engraveColorCode = $engraveSurface->engrave_type_value;
					}
					// Get Engrave Preview Image Details
					if (isset($engraveSurface->engrave_preview_type)
						&& $engraveSurface->engrave_preview_type == 'image'
						&& !empty($engraveSurface->engrave_preview_type_value)
					) {
						$isEngravedPreviewTypeImage = 1;
						$engravedPreviewImagePath = path('read', 'print_profile')
						. $engraveSurface->engrave_preview_type_value;
						$engravedPreviewImagePathThumb = path('read', 'print_profile')
						. 'thumb_' . $engraveSurface->engrave_preview_type_value;
					} else {
						$engravePreviewColorCode
						= $engraveSurface->engrave_preview_type_value;
					}
				}
			}
			// Get the list for Auto Convert List
			$printProfileRespRecord['auto_convert_list'] = [
				[
					'id' => 1,
					'name' => 'BW',
					'is_selected' => $engraves['auto_convert_type'] == 'BW' ? 1 : 0,
				], [
					'id' => 2,
					'name' => 'Grayscale',
					'is_selected' => $engraves['auto_convert_type'] == 'Grayscale'
					? 1 : 0,
				],
			];
			// Make Engrave Array
			$printProfileRespRecord += [
				'is_laser_engrave_enabled' => $isEngraveEnabled,
				'is_auto_convert_enabled' => $engraves['is_auto_convert'],
				'is_hide_color_options' => $engraves['is_hide_color_options'],
				'is_engraved_surface' => $engraves['is_engraved_surface'],
				'is_BWGray_enabled'=> $engraves['is_BWGray_enabled'],
		        'is_black_white'=> $engraves['is_black_white'],
		        'is_gary_scale'=> $engraves['is_gary_scale'],
				// Set keys for engrave images
				'is_engrave_image' => is_valid_var(
					$isEngravedTypeImage, 'int', 'int'
				),
				'engrave_image_path' => is_valid_var(
					$engravedImagePath
				) ? $engravedImagePath : null,
				'engrave_image_path_thumbnail' => is_valid_var(
					$engravedImagePathThumb
				) ? $engravedImagePathThumb : null,
				'engrave_color_code' => is_valid_var(
					$engraveColorCode
				) ? $engraveColorCode : null,
				// Set keys for engrave preview images
				'is_engrave_preview_image' => $isEngravedPreviewTypeImage != ""
				? $isEngravedPreviewTypeImage : 0,
				'engrave_preview_image_path' => is_valid_var(
					$engravedPreviewImagePath
				) ? $engravedPreviewImagePath : null,
				'engrave_preview_image_path_thumbnail' => is_valid_var(
					$engravedPreviewImagePathThumb
				) ? $engravedPreviewImagePathThumb : null,
				'engrave_preview_color_code' => is_valid_var(
					$engravePreviewColorCode
				) ? $engravePreviewColorCode : null,
			];
		} else {
			// If Engrave Data not exists then set a Default DataSet. Fetch only
			// System Defined Engrave Surfaces
			$engaveSurfaces = $engraveSurfaceMasterInit->get();
			foreach ($engaveSurfaces as $engraveSurfaceKey => $engraveSurface) {
				$printProfileRespRecord['engrave_surface_list'][$engraveSurfaceKey]
				= [
					'id' => $engraveSurface->xe_id,
					'surface_name' => $engraveSurface->surface_name,
					'shadow_direction' => $engraveSurface->shadow_direction,
					'shadow_size' => $engraveSurface->shadow_size,
					'shadow_opacity' => $engraveSurface->shadow_opacity,
					'shadow_strength' => $engraveSurface->shadow_strength,
					'shadow_blur' => $engraveSurface->shadow_blur,
					'is_user_defined' => $engraveSurface->is_user_defined,
					'is_selected' => 0,
				];
			}
			// Send Auto Convert List if No engrave data exists
			$printProfileRespRecord['auto_convert_list'] = [
				[
					'id' => 1,
					'name' => 'BW',
					'is_selected' => 0,
				], [
					'id' => 2,
					'name' => 'Grayscale',
					'is_selected' => 0,
				],
			];
			// Send Engrave other key values as null or 0 if no Engrave data exist
			$printProfileRespRecord += [
				'is_laser_engrave_enabled' => 0,
				'is_auto_convert_enabled' => 0,
				'is_hide_color_options' => 0,
				'is_engraved_surface' => 0,
				'is_BWGray_enabled'=> 0,
		        'is_black_white'=> 0,
		        'is_gary_scale'=> 0,
				'is_engrave_image' => 0,
				'engrave_image_path' => "",
				'engrave_image_path_thumbnail' => "",
				'engrave_color_code' => "",
				'is_engrave_preview_image' => 0,
				'engrave_preview_image_path' => "",
				'engrave_preview_image_path_thumbnail' => "",
				'engrave_preview_color_code' => "",
			];
		}
		return $printProfileRespRecord;
	}

	/**
	 * Get: Get Assets w.r.t Print Profile
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author debashrib@riaxe.com
	 * @date   13 Feb 2020
	 * @return Json
	 */
	public function getAssetsRelation($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Assets Assigned', 'not_found'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$printProfileId = $args['id'];
		$getStoreDetails = get_store_details($request);

		$printProfileInit = new PrintProfileModels\PrintProfile();
		$getPrintProfileInfo = $printProfileInit->where(
			[
				'xe_id' => $printProfileId,
				'store_id' => $getStoreDetails['store_id'],
			]
		)
			->with('assets')
			->first();

		// Section : Assets Data
		// Gather Category lists of specified Asset types
		$printProfileAssetsData = [];
		$assetTypeInit = new PrintProfileModels\AssetType();
		$getAssetsList = $assetTypeInit->whereIn(
			'slug', $this->assetsSlugList
		);

		if ($getAssetsList->count() > 0) {
			$getAssets = $getAssetsList->get();
			foreach ($getAssets as $assetKey => $asset) {
				$printProfileAssetsData[$assetKey] = [
					'id' => $asset->xe_id,
					'name' => $asset->name,
					'slug' => $asset->slug,
				];

				$getCategoryList = $this->getAssetCategories(
					$request, $response, $asset->slug, $getStoreDetails
				);

				foreach ($getCategoryList as $categoryKey => $category) {
					if (!empty($getPrintProfileInfo->assets)) {
						$relationalAssetsList = $getPrintProfileInfo->assets->toArray();
						$getSelectedStatus = $this->checkIfCategorySelected(
							$relationalAssetsList,
							$asset->xe_id,
							$category['id']
						);
						$category['is_selected'] = to_int($getSelectedStatus);
					}
					$printProfileAssetsData[$assetKey]['categories'][$categoryKey] = $category;

					// Unset the product key from the main array
					if ($asset->slug == 'products') {
						unset($printProfileAssetsData[$assetKey]);
						unset($printProfileAssetsData[$assetKey]['categories'][$categoryKey]);
					}
				}
			}
			$jsonResponse = [
				'status' => 1,
				'data' => $printProfileAssetsData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Post: Assign Assets with Print Profiles
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   13 Feb 2020
	 * @return Json
	 */
	public function saveAssetsRelation($request, $response) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Assets Assign', 'error'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$printProfileId = to_int($allPostPutVars['print_profile_id']);
		$getAssetsData = json_clean_decode($allPostPutVars['data'], true);

		$printProfile = new PrintProfileModels\PrintProfile();
		$getPrintProfile = $printProfile->where('xe_id', $printProfileId);
		if ($getPrintProfile->count() > 0) {
			$printProfileAssetsData = [];

			if (!empty($getAssetsData) && count($getAssetsData) > 0) {
				foreach ($getAssetsData as $record) {
					foreach ($record['category_id'] as $category) {
						$printProfileAssetsData[] = [
							'print_profile_id' => $printProfileId,
							'asset_type_id' => $record['asset_type_id'],
							'category_id' => $category,
						];
					}
				}
				// Check if assets are already assigned to print profile
				$profAssetCatRelDel = new PrintProfileModels\PrintProfileAssetsCategoryRel();
				$getPrintProfileRel = $profAssetCatRelDel->where(['print_profile_id' => $printProfileId]);

				// Clean records before process while updating
				if ($getPrintProfileRel->count() > 0) {
					// Find the product's slug key
					$getAssetType = new PrintProfileModels\AssetType();
					$productTypeId = $getAssetType->where('slug', 'LIKE', 'product' . '%')
						->select('xe_id')
						->first()
						->xe_id;
					$getPrintProfileRel->where('asset_type_id', '<>', $productTypeId)->delete();
				}
				if (isset($printProfileAssetsData) && count($printProfileAssetsData) > 0) {
					$profAssetCatRelIns = new PrintProfileModels\PrintProfileAssetsCategoryRel();
					if ($profAssetCatRelIns->insert($printProfileAssetsData)) {
						$jsonResponse = [
							'status' => 1,
							'message' => message(
								'Assets Assign', 'done'
							),
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
	 * Get: Print Profile Setup Incomplete
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   28th Feb 2019
	 * @return A JSON Response
	 */

	public function getDataForDashboard($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$result = [];
		$getStoreDetails = get_store_details($request);
		$printProfileGtInit = new PrintProfileModels\PrintProfile();
		// Get total Print Profile
		$getTotalPrintProfile = $printProfileGtInit->whereNotNull('name')
			->where($getStoreDetails);
		$totalPrintProfile = $getTotalPrintProfile->count();
		// Get all imcomplete Print Profile
		$getIncPrintProfile = $printProfileGtInit->whereNotNull('name')
			->where($getStoreDetails)
			->where(
				function ($query) {
					return $query->orWhere(
						['is_price_setting' => 0, 'is_product_setting' => 0, 'is_assets_setting' => 0]
					);
				}
			);
		$incPrintProfile = $getIncPrintProfile->count();
		// Get all Print Profile's price setting imcomplete
		$getIncPriceSetting = $printProfileGtInit->whereNotNull('name')
			->where(['is_price_setting' => 0])
			->where($getStoreDetails);
		$incPriceSetting = $getIncPriceSetting->count();

		// Get clipart categories which not assign to any Print Profile
		$clipartAssetArr = $this->assetsTypeId('cliparts');
		$clipartAssetId = $clipartAssetArr['asset_type_id'];
		$clipartCatCount = $this->getCategoryCount($clipartAssetId);

		// Get Printable colors category which not assign to any Print Profile
		$colorsAssetArr = $this->assetsTypeId('color-palettes');
		$colorsAssetId = $colorsAssetArr['asset_type_id'];
		$colorsCatCount = $this->getCategoryCount($colorsAssetId);

		// Get Fonts category which not assign to any Print Profile
		$fontsAssetArr = $this->assetsTypeId('color-palettes');
		$fontsAssetId = $fontsAssetArr['asset_type_id'];
		$fontsCatCount = $this->getCategoryCount($fontsAssetId);

		// Get Shapes category which not assign to any Print Profile
		$shapesAssetArr = $this->assetsTypeId('shapes');
		$shapesAssetId = $shapesAssetArr['asset_type_id'];
		$shapesCatCount = $this->getCategoryCount($shapesAssetId);

		// Get Templates category which not assign to any Print Profile
		$templatesAssetArr = $this->assetsTypeId('templates');
		$templatesAssetId = $templatesAssetArr['asset_type_id'];
		$templatesCatCount = $this->getCategoryCount($templatesAssetId);

		$result = [
			'print_profile' => ($totalPrintProfile > 0)
			? $totalPrintProfile : 0,
			'incomplete_print_profile' => ($incPrintProfile > 0)
			? $incPrintProfile : 0,
			'incomplete_price_setting' => ($incPriceSetting > 0)
			? $incPriceSetting : 0,
			'clipart_category' => ($clipartCatCount > 0)
			? $clipartCatCount : 0,
			'printable_color_cagetory' => ($colorsCatCount > 0)
			? $colorsCatCount : 0,
			'fonts_category' => ($fontsCatCount > 0)
			? $fontsCatCount : 0,
			'shapes_category' => ($shapesCatCount > 0)
			? $shapesCatCount : 0,
			'templates_category' => ($templatesCatCount > 0)
			? $templatesCatCount : 0,
		];
		return response(
			$response,
			[
				'data' => $result, 'status' => $serverStatusCode,
			]
		);

	}

	/**
	 * Get: Get category count which not used in Print Profile
	 *
	 * @param $assetTypeId Asset type id
	 *
	 * @author debashrib@riaxe.com
	 * @date   28 Feb 2020
	 * @return count
	 */
	private function getCategoryCount($assetTypeId) {
		$count = 0;
		$getCategoryCount = DB::table('categories')->select('xe_id')
			->where(['parent_id' => 0, 'asset_type_id' => $assetTypeId])
			->whereNotIn(
				'xe_id', function ($query) use ($assetTypeId) {
					$query->select('xe_id')
						->from('categories')
						->where('parent_id', 0)
						->whereIn(
							'xe_id', function ($query1) use ($assetTypeId) {
								$query1->select('category_id')
									->distinct()
									->from('print_profile_assets_category_rel')
									->where('asset_type_id', $assetTypeId);
							}
						);
				}
			);
		$count = $getCategoryCount->count();
		return $count;
	}

	/**
	 * Save Print Profile Attribute Relation Data
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   3 Mar 2019
	 * @return boolean
	 */
	public function saveAttributeRelationDetails($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $request->getParsedBody();
		$jsonResponse = [
			'status' => 0,
			'message' => message('Attribute Relation', 'error'),
		];

		if (isset($allPostPutVars['data'])
			&& !empty($allPostPutVars['data'])
		) {
			$dataJson = $allPostPutVars['data'];
			$dataArray = json_clean_decode($dataJson, true);

			$settingsValue = [
				'is_enable_order_quantity' => $dataArray['is_enable_order_quantity'],
				'is_enable_product_attributes' => $dataArray['is_enable_product_attributes'],
				'attribute_id' => $dataArray['attribute_id'],
			];
			$settingInit = new Setting();
			$settingInit->where('setting_key', 'print_profile_attribute_rel')
				->delete();
			$settingsData = [
				'setting_key' => 'print_profile_attribute_rel',
				'setting_value' => json_encode($settingsValue),
				'store_id' => $getStoreDetails['store_id'],
			];

			$saveSettingsData = new Setting($settingsData);
			$saveSettingsData->save();
			$deleteInit = new PrintProfileAttributeRel();
			$deleteInit->truncate();
			if (!empty($dataArray['relation_data']) && $dataArray['relation_data'] != "") {
				$success = 0;
				foreach ($dataArray['relation_data'] as $relKey => $relValue) {
					$termId = $relValue['attribute_term_id'];
					if (!empty($relValue['range']) && $relValue['range'] != "") {
						foreach ($relValue['range'] as $rangeKey => $rangeValue) {
							$tierRangeId = $this->getTierRangeId(
								$rangeValue['from_qty'], $rangeValue['to_qty']
							);
							if (!empty($rangeValue['print_profiles']) && $rangeValue['print_profiles'] != "") {
								foreach ($rangeValue['print_profiles'] as $key => $value) {
									$relationData = [
										'attribute_term_id' => $termId,
										'tier_range_id' => $tierRangeId,
										'print_profile_id' => $value['id'],
									];
									$saveRelationalData = new PrintProfileAttributeRel(
										$relationData
									);
									if ($saveRelationalData->save()) {
										$success++;
									}
								}
							} else {
								$relationData = [
									'attribute_term_id' => $termId,
									'tier_range_id' => $tierRangeId,
									'print_profile_id' => 0,
								];
								$saveRelationalData = new PrintProfileAttributeRel(
									$relationData
								);
								if ($saveRelationalData->save()) {
									$success++;
								}
							}
						}
					}
				}
				if (!empty($success) && $success > 0) {
					$jsonResponse = [
						'status' => 1,
						'message' => $success . ' Relations saved successfully',
					];
				}
			}
		}
		return response(
			$response,
			['data' => $jsonResponse, 'status' => $serverStatusCode]
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
	 * @date   3 Mar 2019
	 * @return Json
	 */
	public function getAttributeRelationDetails($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Relation Data', 'not_found'),
		];
		$settingInit = new Setting();
		$getSettings = json_clean_decode($settingInit->where('setting_key', 'print_profile_attribute_rel')->first()['setting_value']);
		if (isset($getSettings['attribute_id']) && $getSettings['attribute_id'] != "") {
			$getProdAttributes = call_api(
				'store/attributes/' . $getSettings['attribute_id'], 'GET', []
			);
		} else {
			$getProdAttributes['data'][0] = [
				'id' => 0,
				'name' => '',
			];
		}

		if (!empty($getProdAttributes['data']) && $getProdAttributes['data'] != "") {
			foreach ($getProdAttributes['data'] as $termKey => $termValue) {
				$range = [];
				$relationInit = new PrintProfileAttributeRel();
				$getRelationData = $relationInit->where('attribute_term_id', $termValue['id'])
					->select('tier_range_id', 'print_profile_id')->get();

				if ($getRelationData->count() > 0) {
					foreach ($getRelationData as $key => $value) {
						$getQtyRange = $this->getTierRange(
							$value['tier_range_id']
						);
						$printProfileInit = new PrintProfileModels\PrintProfile();
						$printProfileName = $printProfileInit->where('xe_id', $value['print_profile_id'])
							->select('name')->first();
						$range[$value['tier_range_id']]['from_qty'] = $getQtyRange['from_range'];
						$range[$value['tier_range_id']]['to_qty'] = $getQtyRange['to_range'];
						if ($value['print_profile_id'] > 0) {
							$range[$value['tier_range_id']]['print_profiles'][] = [
								'id' => $value['print_profile_id'],
								'name' => $printProfileName['name'],
							];
						} else {
							$range[$value['tier_range_id']]['print_profiles'] = [];
						}
					}
				}
				$relationData[$termKey] = [
					'attribute_term_id' => $termValue['id'],
					'attribute_term_name' => $termValue['name'],
					'range' => array_values($range),
				];
			}
		}

		if (is_array($relationData[0]['range']) && count($relationData[0]['range']) > 0) {
			$getSettings += [
				'relation_data' => $relationData,
			];
			$jsonResponse = [
				'status' => 1,
				'data' => $getSettings,
			];
		}

		return response(
			$response,
			['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Save Tier Quantity Range/ Get the Range Id
	 *
	 * @param $fromPrice from quantity for tier range
	 * @param $toPrice   to quantity for tier range
	 *
	 * @author satyabratap@riaxe.com
	 * @date   3 Mar 2019
	 * @return json response wheather data is saved or any error occured
	 */
	private function getTierRangeId($fromPrice = 0, $toPrice = 0) {
		$priceTierQtyRngId = 0;
		if (isset($fromPrice) && isset($toPrice) && $fromPrice > 0 && $toPrice > 0) {
			$priceTierQtyRng = new PricingModel\PriceTierQuantityRange();
			$initPriceRange = $priceTierQtyRng->where(
				[
					'quantity_from' => $fromPrice,
					'quantity_to' => $toPrice,
				]
			);
			if ($initPriceRange->count() > 0) {
				// If Price range already exist
				$priceTierQtyRngId = $initPriceRange->first()->xe_id;
			} else {
				// If new range then return ID
				$tierRangeData = [
					'price_module_setting_id' => $this->priceModSettingsId,
					'quantity_from' => (float) $fromPrice,
					'quantity_to' => (float) $toPrice,
				];
				$saveTierRange = new PricingModel\PriceTierQuantityRange(
					$tierRangeData
				);
				if ($saveTierRange->save()) {
					$priceTierQtyRngId = $saveTierRange->xe_id;
				}
			}
		}

		return $priceTierQtyRngId;
	}

	/**
	 * GET: Get Tier Range
	 *
	 * @param $tierRangeId Tier Range Id
	 *
	 * @author satyabratap@riaxe.com
	 * @date   3 Mar 2019
	 * @return range in array format
	 */
	private function getTierRange($tierRangeId) {
		$tierRangeResponse = [];
		$priceTierQtyRng = new PricingModel\PriceTierQuantityRange();
		$getRangeValueInit = $priceTierQtyRng->where('xe_id', $tierRangeId);
		if ($getRangeValueInit->count() > 0) {
			$getRangeValue = $getRangeValueInit->first();
			$tierRangeResponse = [
				'from_range' => $getRangeValue->quantity_from,
				'to_range' => $getRangeValue->quantity_to,
			];
		}

		return $tierRangeResponse;
	}
	/**
	 * Get Category and Subcategory List of Assets Modules
	 * Used for Print Profile Module
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $slug     Slug value of the individual helper file
	 *
	 * @author tanmayap@riaxe.com
	 * @date   3rd Dec 2019
	 * @return Array of categories
	 */
	public function getAssetCategories($request, $response, $slug, $getStoreDetails) {
		$categories = [];
		if (!empty($slug) && $slug != 'products') {
			$assetType = DB::table('asset_types')
				->where('slug', $slug)
				->first();
			if (!empty($assetType)) {
				$assetTypeId = $assetType->xe_id;
				$getCategoryInit = new CommonCategory();
				$getCategories = $getCategoryInit->where(
					'asset_type_id', $assetTypeId
				);
				if ($getCategories->count() > 0) {
					$categories = $getCategories->select(
						'xe_id as id', 'parent_id', 'name', 'is_disable'
					)->where('store_id', '=', $getStoreDetails['store_id'])->get()->toArray();
				}
			}
		}
		return $categories;
	}

	/**
	 * Get Assets/Features name used in the print profiles
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $slug     Slug value of the individual helper file
	 *
	 * @author satyabratap@riaxe.com
	 * @date   16th Sept 2020
	 * @return Array of assets/features
	 */
	public function getAssets($request, $response, $slug) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Print Profile Assets', 'not_found'),
		];

		$featureInit = new PrintProfileModels\Feature();
		if ($featureInit->count() > 0) {
			$getAllAssets = $featureInit->get();
			if ($getAllAssets->count() > 0) {
				$jsonResponse = [
					'status' => 1,
					'data' => $getAllAssets,
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);

	}

	/**
	 * Get: Get Font Associated Categories
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author satyabratap@riaxe.com
	 * @date   25 Sept 2020
	 * @return Json
	 */
	public function getFontCategories($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Assets Assigned', 'not_found'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$printProfileId = $args['id'];
		$getStoreDetails = get_store_details($request);

		$assetTypeInit = new PrintProfileModels\AssetType();
		$getAssets = $assetTypeInit->where('slug', 'fonts');

		if ($getAssets->count() > 0) {
			$getAssetDetails = $getAssets->first()->toArray();
			$assetTypeId = $getAssetDetails['xe_id'];
			$assetsRelInit = new PrintProfileModels\PrintProfileAssetsCategoryRel();
			$getRelCategories = $assetsRelInit->where(['print_profile_id' => $printProfileId, 'asset_type_id' => $assetTypeId]);
			if ($getRelCategories->count() > 0) {
				$getCategoryInit = new CommonCategory();
				$getCategories = $getCategoryInit->where(
					[
						'asset_type_id' => $assetTypeId,
						'parent_id' => 0,
						'is_disable' => 0,
					]
				)->get()->toArray();
				$relationDetails = $getRelCategories->select('category_id')->get()->toArray();
				foreach ($getCategories as $catKey => $category) {
					foreach ($relationDetails as $relKey => $relcategory) {
						if ($category['xe_id'] == $relcategory['category_id']) {
							$finalCategories[] = [
								'id' => $category['xe_id'],
								'name' => $category['name'],
							];
						}
					}
				}
				$jsonResponse = [
					'status' => 1,
					'data' => $finalCategories,
				];
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get: Get Font Associated Categories
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author satyabratap@riaxe.com
	 * @date   25 Sept 2020
	 * @return Json
	 */
	public function getFontsByCategory($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Assets Assigned', 'not_found'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$categoryId = $args['id'];
		$printProfileId = $args['print_profile_id'];
		$getStoreDetails = get_store_details($request);
		$assetTypeInit = new PrintProfileModels\AssetType();
		$getAssets = $assetTypeInit->where('slug', 'fonts');
		if ($getAssets->count() > 0) {
			$getAssetDetails = $getAssets->first()->toArray();
			$assetTypeId = $getAssetDetails['xe_id'];
			$assetsRelInit = new PrintProfileModels\PrintProfileAssetsCategoryRel();
			$getRelCategories = $assetsRelInit->where(['print_profile_id' => $printProfileId, 'asset_type_id' => $assetTypeId]);
			if ($getRelCategories->count() > 0) {
				$getCategoryInit = new CommonCategory();
				$getSubcategories = $getCategoryInit->where(
					[
						'asset_type_id' => $assetTypeId,
						'parent_id' => $categoryId,
						'is_disable' => 0,
					]
				)->select('xe_id')->get()->toArray();
				if (empty($getSubcategories)) {
					$getSubcategories[] = [
						'xe_id' => $categoryId,
					];
				}
				$relationDetails = $getRelCategories->select('category_id')->get()->toArray();
				foreach ($getSubcategories as $subcatKey => $subCategory) {
					foreach ($relationDetails as $relKey => $relcategory) {
						if ($subCategory['xe_id'] == $relcategory['category_id']) {
							$subCategories[] = $subCategory['xe_id'];
						}
					}
				}
				$fontsInit = new Font();
				$getFonts = $fontsInit->select(
					'xe_id', 'name', 'price', 'font_family', 'file_name'
				);
				// Filter by Category ID
				if (!empty($subCategories)) {
					$getFonts->whereHas(
						'fontCategory', function ($q) use ($subCategories) {
							return $q->whereIn('category_id', $subCategories);
						}
					);
					if ($getFonts->count() > 0) {
						$fontDetails = $getFonts->get();
						$jsonResponse = [
							'status' => 1,
							'data' => $fontDetails,
						];
					}
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
}