<?php
/**
 * Manage Print Profile Pricing
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Pricing
 * @package   Print_Profile
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\PrintProfiles\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\PrintProfiles\Controllers\PrintProfilesController as PrintProfile;
use App\Modules\PrintProfiles\Models\Pricing as PricingModel;

/**
 * Print Profile Pricing Controller
 *
 * @category Class
 * @package  Print_Profile_Pricing
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PricingController extends ParentController
{
    /**
     * Set default Server status
     */
    protected $serverStatusCode = OPERATION_OKAY;

    /**
     * JSON Response Array
     */
    protected $jsonMainResponse = [];

    /**
     * Set Print Profile Id to null. After any operation this variable will be
     * assigned with a real id
     */
    protected $printProfileId = null;

    /**
     * Print Profile's Pricind ID is set to null so that later we can store id
     * and can access from any method
     */
    protected $printProfPricingId = null;

    /**
     * Advanced Price Setting Id set to null
     */
    protected $advPriceSettingId = null;

    /**
     * Price Module Setting Id Set to null
     */
    protected $priceModSettingsId = null;

    /**
     * POST: Save Print Profile Pricing
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return json response wheather data is saved or any error occured
     */
    public function savePricing($request, $response, $cloneData = "")
    {
        $jsonResponse = [];
        $serverStatusCode = OPERATION_OKAY;

        if (empty($cloneData)) {
            $allPostPutVars = $request->getParsedBody();
            $getPriceJsonData = json_clean_decode($allPostPutVars['data'], true);
        } else {
            $getPriceJsonData = json_clean_decode($cloneData, true);
        }

        $printProfileKey = $getPriceJsonData['print_profile_id'];
        // Save Basic Data of Print profile Pricing
        $this->_pricingBasicData($getPriceJsonData, $printProfileKey);
        $this->_savePriceSettings($getPriceJsonData['modules'], $printProfileKey);
        // Create Json file at Print Profile end
        $printProfile = new PrintProfile();
        $printProfile->createJsonFile($request, $response, $printProfileKey);

        if (empty($cloneData)) {
            return response(
                $response, [
                    'data' => $this->jsonMainResponse, 'status' => $serverStatusCode,
                ]
            );
        }
        return $this->jsonMainResponse;
    }

    /**
     * POST: Clone Print Profile Pricing Records
     * 
     * @param $request   Slim's Request object
     * @param $response  Slim's Response object
     * @param $profileId Print Profile Id before cloned
     * @param $newId     Print Profile Id after cloned
     *
     * @author tanmayap@riaxe.com
     * @date   20 mar 2020
     * @return integer
     */
    public function clonePricing($request, $response, $profileId, $newId)
    {
        $getPricingDataByApi = $this->getPricingDetails($request, $response, ['id' => $profileId, 'return_type' => 'array']);
        $pricingData['print_profile_id'] = $newId;
        if (!empty($getPricingDataByApi)) {
            $pricingData += $getPricingDataByApi;
            $pricingDataToJson = json_clean_encode($pricingData);
            $pricingSave = [
                'data' => $pricingDataToJson
            ];
            $savePriceResponse = $this->savePricing($request, $response, $pricingDataToJson);
            return $savePriceResponse['status'];
        }
        return false;
    }

    /**
     * POST: Save Print Profile Pricing
     *
     * @param $records Pricing Data
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return json response wheather data is saved or any error occured
     */
    private function _savePriceSettings($records = [])
    {
        foreach ($records as $settings) {
            if (isset($settings['price_settings']['advance_price_type'])
                && $settings['price_settings']['advance_price_type'] != ""
            ) {
                $saveAdvancedpriceData = [
                    'advanced_price_type' => $settings['price_settings']['advance_price_type'],
                    'no_of_colors_allowed' => $settings['price_settings']['no_of_colors_allowed'],
                    'is_full_color' => $settings['price_settings']['is_full_color'],
                    'area_calculation_type' => $settings['price_settings']['area_calculation_type'],
                    'min_price' => (float) $settings['price_settings']['min_price'],
                ];
                $saveAdvancedPrice = new PricingModel\AdvancePriceSetting(
                    $saveAdvancedpriceData
                );
                if ($saveAdvancedPrice->save()) {
                    $this->advPriceSettingId = $saveAdvancedPrice->xe_id;
                }
            }
            $priceModuleGet = new PricingModel\PriceModule();
            $getPriceModuleDetails = $priceModuleGet->where(
                'slug', trim($settings['slug'])
            )
                ->select('xe_id')
                ->first();
            $modulePriceSetting = [
                'print_profile_pricing_id' => $this->printProfPricingId,
                'price_module_id' => $getPriceModuleDetails->xe_id,
                'module_status' => $settings['status'],
                'is_default_price' => (isset($settings['price_settings']['default_prices'])
                    && count($settings['price_settings']['default_prices']) > 0) ? 1 : 0,
                'is_quote_enabled' => isset($settings['price_settings']['is_quote_enable'])
                ? $settings['price_settings']['is_quote_enable'] : 0,
                'is_advance_price' => isset($settings['price_settings']['is_advance_price'])
                ? $settings['price_settings']['is_advance_price'] : 0,
                'advance_price_settings_id' => $this->advPriceSettingId,
                'is_quantity_tier' => isset($settings['price_settings']['is_quantity_tier'])
                ? (int) $settings['price_settings']['is_quantity_tier'] : 0,
                'quantity_tier_type' => $settings['price_settings']['quantity_tier_type'],
                'default_stitch_count_per_inch' => isset($settings['price_settings']['default_stitch_count_per_inch'])
                ? (int) $settings['price_settings']['default_stitch_count_per_inch'] : 2000,
            ];
            $modulePriceSettingSave = new PricingModel\PriceModuleSetting(
                $modulePriceSetting
            );
            if ($modulePriceSettingSave->save()) {
                $this->priceModSettingsId = $modulePriceSettingSave->xe_id;
            }
            // Process Default prices
            if (isset($settings['price_settings']['default_prices'])
                && count($settings['price_settings']['default_prices']) > 0
            ) {
                $defaultPriceList = [];
                if ($settings['slug'] === 'name-number') {
                    foreach ($settings['price_settings']['default_prices'] as $defaultPriceKey => $defaultPrice) {
                        $defaultPriceList[$defaultPriceKey] = [
                            'price_module_setting_id' => $this->priceModSettingsId,
                            'price_key' => $defaultPrice['price_key'],
                            'price_value' => $defaultPrice['price_value'],
                            'status' => $defaultPrice['status'],
                        ];
                        $initSavePriceDefaultSetting = new PricingModel\PriceDefaultSetting(
                            $defaultPriceList[$defaultPriceKey]
                        );
                        $initSavePriceDefaultSetting->save();
                        if (isset($defaultPrice['status'])) {
                            $defaultPriceList[$defaultPriceKey] = [
                                'price_module_setting_id' => $this->priceModSettingsId,
                                'price_key' => 'status',
                                'price_value' => $defaultPrice['status'],
                            ];
                            $initSavePriceDefaultSetting = new PricingModel\PriceDefaultSetting(
                                $defaultPriceList[$defaultPriceKey]
                            );
                            $initSavePriceDefaultSetting->save();
                        }

                    }
                } else {
                    foreach ($settings['price_settings']['default_prices'] as $defaultPriceKey => $defaultPrice) {
                        $defaultPriceList[$defaultPriceKey] = [
                            'price_module_setting_id' => $this->priceModSettingsId,
                            'price_key' => $defaultPrice['price_key'],
                            'price_value' => $defaultPrice['price_value'],
                        ];
                        $initSavePriceDefaultSetting = new PricingModel\PriceDefaultSetting(
                            $defaultPriceList[$defaultPriceKey]
                        );
                        $initSavePriceDefaultSetting->save();

                    }
                }
            }
            // Send one module data to print area function
            $this->_savePrintAreas($settings['price_settings']);
        }
    }

    /**
     * POST: Save Print Area Data
     *
     * @param $dataPerModule Price Data per Module
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return json response wheather data is saved or any error occured
     */
    private function _savePrintAreas($dataPerModule = [])
    {
        if (isset($dataPerModule) && count($dataPerModule) > 0) {
            foreach ($dataPerModule['print_areas'] as $printAreaIndex => $printArea) {
                // process Default data
                if (isset($printArea['default'])
                    && count($printArea['default']) > 0
                ) {
                    foreach ($printArea['default'] as $eachColor) {
                        $this->_saveCorePrice(
                            $eachColor, $printAreaIndex, 'default'
                        );
                    }
                }
                // process Color data
                if (isset($printArea['colors'])
                    && count($printArea['colors']) > 0
                ) {
                    foreach ($printArea['colors'] as $eachColor) {
                        $this->_saveCorePrice($eachColor, $printAreaIndex, 'colors');
                    }
                }
                if (isset($printArea['ranges'])
                    && count($printArea['ranges']) > 0
                ) {
                    foreach ($printArea['ranges'] as $eachRange) {
                        $this->_saveCorePrice($eachRange, $printAreaIndex, 'ranges');
                    }
                }
                if (isset($printArea['decoration_area'])
                    && count($printArea['decoration_area']) > 0
                ) {
                    foreach ($printArea['decoration_area'] as $eachDecorationArea) {
                        $this->_saveCorePrice(
                            $eachDecorationArea, $printAreaIndex, 'decoration_area'
                        );
                    }
                }
                if (isset($printArea['vdp']) && count($printArea['vdp']) > 0) {
                    foreach ($printArea['vdp'] as $eachVdp) {
                        $this->_saveCorePrice($eachVdp, $printAreaIndex, 'vdp');
                    }
                }
                if (isset($printArea['team']) && count($printArea['team']) > 0) {
                    foreach ($printArea['team'] as $eachTeam) {
                        $this->_saveCorePrice($eachTeam, $printAreaIndex, 'team');
                    }
                }
                if (isset($printArea['sleeve']) && count($printArea['sleeve']) > 0) {
                    foreach ($printArea['sleeve'] as $eachSleeve) {
                        $this->_saveCorePrice(
                            $eachSleeve, $printAreaIndex, 'sleeve'
                        );
                    }
                }
                if (isset($printArea['cliparts'])
                    && count($printArea['cliparts']) > 0
                ) {
                    foreach ($printArea['cliparts'] as $eachAsset) {
                        $this->_saveCorePrice(
                            $eachAsset, $printAreaIndex, 'cliparts'
                        );
                    }
                }
                if (isset($printArea['fonts']) && count($printArea['fonts']) > 0) {
                    foreach ($printArea['fonts'] as $eachAsset) {
                        $this->_saveCorePrice($eachAsset, $printAreaIndex, 'fonts');
                    }
                }
                if (isset($printArea['background'])
                    && count($printArea['background']) > 0
                ) {
                    foreach ($printArea['background'] as $eachAsset) {
                        $this->_saveCorePrice(
                            $eachAsset, $printAreaIndex, 'background'
                        );
                    }
                }
                if (isset($printArea['color']) && count($printArea['color']) > 0) {
                    foreach ($printArea['color'] as $eachAsset) {
                        $this->_saveCorePrice($eachAsset, $printAreaIndex, 'color');
                    }
                }
                if (isset($printArea['image_pricing'])
                    && count($printArea['image_pricing']) > 0
                ) {
                    foreach ($printArea['image_pricing'] as $eachImagePricing) {
                        $this->_saveCorePrice(
                            $eachImagePricing, $printAreaIndex, 'image_pricing'
                        );
                    }
                }
                if (isset($printArea['price_per_letter'])
                    && count($printArea['price_per_letter']) > 0
                ) {
                    foreach ($printArea['price_per_letter'] as $eachpricePerLetter) {
                        $this->_saveCorePrice(
                            $eachpricePerLetter, $printAreaIndex, 'price_per_letter'
                        );
                    }
                }
                if (isset($printArea['price_per_stitch'])
                    && count($printArea['price_per_stitch']) > 0
                ) {
                    foreach ($printArea['price_per_stitch'] as $eachpricePerLetter) {
                        $this->_saveCorePrice(
                            $eachpricePerLetter, $printAreaIndex, 'price_per_stitch'
                        );
                    }
                }
            }
            $this->jsonMainResponse = [
                'status' => 1,
                'message' => message('Pricing Profiles', 'saved'),
            ];
        }
    }

    /**
     * POST: Save Tier Price and Tier Whitebase Data
     *
     * @param $priceList      Price List
     * @param $printAreaIndex Index of the Print Area
     * @param $attributeType  Attribute Type
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return json response wheather data is saved or any error occured
     */
    private function _saveCorePrice($priceList = [], $printAreaIndex = 0, $attributeType = '')
    {
        if (isset($priceList['pricing']) && count($priceList['pricing']) > 0) {
            $allTierPrices = [];
            $printAreaId = null;
            if (isset($priceList['print_area_id'])
                && $priceList['print_area_id'] > 0
            ) {
                $printAreaId = $priceList['print_area_id'];
            }
            $designAreaFromLimit = $designAreaToLimit = null;
            if (isset($priceList['range_from']) && isset($priceList['range_to'])) {
                $designAreaFromLimit = (int) $priceList['range_from'];
                $designAreaToLimit = (int) $priceList['range_to'];
            }
            // if Color data comes then fillup color index
            $colorIndex = $getKeyName = null;
            if (isset($priceList['key_name']) && $priceList['key_name'] != "") {
                if (isset($attributeType) && $attributeType == 'colors') {
                    $colorIndex = $priceList['key_name'];
                } else {
                    $getKeyName = $priceList['key_name'];
                }
            }
            $screenCost = 0 ;
            if (isset($priceList['screen_cost']) && $priceList['screen_cost'] != "") {
                $screenCost = $priceList['screen_cost']; 
            }
            // Select Key_Name value
            $tierPriceList = [
                'attribute_type' => $attributeType,
                'price_module_setting_id' => $this->priceModSettingsId,
                'print_area_index' => (int) $printAreaIndex,
                'color_index' => $colorIndex,
                'print_area_id' => $printAreaId,
                'range_from' => $designAreaFromLimit,
                'range_to' => $designAreaToLimit,
                'key_name' => $getKeyName,
                'screen_cost' => $screenCost,
            ];

            // Main Price Loop Strats
            $tierPriceSave = new PricingModel\TierPrice($tierPriceList);
            $tierPriceSave->save();
            $tierPriceSaveKey = $tierPriceSave->xe_id;
            foreach ($priceList['pricing'] as $priceData) {
                $whiteBaseRecords = [];
                // (Case*) If Any D/L/W available then only proceed with D/L/W
                if (isset($priceData['d_price'])) {
                    $whiteBaseRecords[] = [
                        'price_tier_value_id' => $tierPriceSaveKey,
                        'tier_range_id' => $this->_getTierQuantityRangeId(
                            $priceData['from_qty'], $priceData['to_qty']
                        ),
                        'price' => $priceData['d_price'],
                        'white_base_type' => 'd',
                    ];
                }
                if (isset($priceData['l_price'])) {
                    $whiteBaseRecords[] = [
                        'price_tier_value_id' => $tierPriceSaveKey,
                        'tier_range_id' => $this->_getTierQuantityRangeId(
                            $priceData['from_qty'], $priceData['to_qty']
                        ),
                        'price' => $priceData['l_price'],
                        'white_base_type' => 'l',
                    ];
                }
                if (isset($priceData['w_price'])) {
                    $whiteBaseRecords[] = [
                        'price_tier_value_id' => $tierPriceSaveKey,
                        'tier_range_id' => $this->_getTierQuantityRangeId(
                            $priceData['from_qty'], $priceData['to_qty']
                        ),
                        'price' => $priceData['w_price'],
                        'white_base_type' => 'w',
                    ];
                }
                if (isset($priceData['p_price'])) {
                    $whiteBaseRecords[] = [
                        'price_tier_value_id' => $tierPriceSaveKey,
                        'tier_range_id' => $this->_getTierQuantityRangeId(
                            $priceData['from_qty'], $priceData['to_qty']
                        ),
                        'price' => ($priceData['p_price']),
                        'white_base_type' => 'p',
                    ];
                }
                $tierWhiteBaseIns = new PricingModel\TierWhitebase();
                $tierWhiteBaseIns->insert($whiteBaseRecords);
            }
        }
    }

    /**
     * POST: Save Tier Quantity Range/ Get the Range Id
     *
     * @param $fromPrice from quantity for tier range
     * @param $toPrice   to quantity for tier range
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return json response wheather data is saved or any error occured
     */
    private function _getTierQuantityRangeId($fromPrice = 0, $toPrice = 0)
    {
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
     * POST: Save Print Profile Pricing Data
     *
     * @param $record          Print Profile Pricing Data
     * @param $printProfileKey Print Profile Id
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return json response wheather data is saved or any error occured
     */
    private function _pricingBasicData($record, $printProfileKey)
    {
        $this->printProfileId = $printProfileKey;
        $basicData = [
            'print_profile_id' => $printProfileKey,
            'is_white_base' => $record['is_white_base'],
            'white_base_type' => $record['white_base_type'],
            'is_setup_price' => $record['is_setup_price'],
            'setup_price' => $record['setup_price'],
            'setup_type_product' => $record['setup_type_product'],
            'setup_type_order' => $record['setup_type_order'],
        ];

        $savePrintProfilePricing = new PricingModel\PrintProfilePricing($basicData);
        if ($savePrintProfilePricing->save()) {
            $this->printProfPricingId = $savePrintProfilePricing->xe_id;
        }
    }

    /**
     * GET: Get Pricing Details by Print Profile ID
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return Price Details of a print Profile
     */
    public function getPricingDetails($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $printProfileId = to_int($args['id']);
        $rangeSlugs = array('simple-deco', 'stitch-count', 'letter');
        $rangeTypes = array('ranges', 'price_per_stitch', 'price_per_letter');
        // Building replationships
        $profilePricingGet = new PricingModel\PrintProfilePricing();
        $getPriceDetailsInit = $profilePricingGet->where(
            ['print_profile_id' => $printProfileId]
        )
            ->with(
                'price_module_settings',
                'price_module_settings.price_module',
                'price_module_settings.price_default_settings',
                'price_module_settings.price_tier_quantity_range',
                'price_module_settings.tier_prices',
                'price_module_settings.tier_prices.whitebase',
                'price_module_settings.price_advance_price_settings'
            );

        // If there is no pricing data then return a not found response
        if ($getPriceDetailsInit->count() == 0) {
            if (isset($args['return_type']) && $args['return_type'] == 'array') {
                return $printProfilePricing;
            }
            return response(
                $response, ['data' => [
                    'status' => 0,
                    'message' => message('Print profile pricing', 'not_found'),
                ],
                    'status' => $serverStatusCode]
            );
        }

        // Get all price details related to print profile
        $getPriceDetails = $getPriceDetailsInit->first();

        $modules = [];
        $printProfilePricing = [
            'is_white_base' => $getPriceDetails['is_white_base'],
            'white_base_type' => $getPriceDetails['white_base_type'],
            'is_setup_price' => $getPriceDetails['is_setup_price'],
            'setup_price' => $getPriceDetails['setup_price'],
            'setup_type_order' => $getPriceDetails['setup_type_order'],
            'setup_type_product' => $getPriceDetails['setup_type_product'],
        ];

        if (isset($getPriceDetails['price_module_settings'])
            && count($getPriceDetails['price_module_settings']) > 0
        ) {
            foreach ($getPriceDetails['price_module_settings'] as $priceKey => $priceValue) {
                // Get Adv Prc sett Details from price_module_settings table
                if (isset($priceValue['advance_price_settings_id'])
                    && $priceValue['advance_price_settings_id'] > 0
                ) {
                    $getAdvPrcSett = new PricingModel\AdvancePriceSetting();
                    $advancePriceType = $getAdvPrcSett->where(
                        ['xe_id' => $priceValue['advance_price_settings_id']]
                    )
                        ->first();
                }

                // Fetching Default price settings array
                $defaultPrices = [];

                if (isset($priceValue['price_default_settings'])
                    && count($priceValue['price_default_settings']) > 0
                ) {
                    if ($priceValue['price_module']['slug'] == 'name-number') {
                        $statusCount = 0;
                        foreach ($priceValue['price_default_settings'] as $defaultKey => $defaultValue) {
                            if ($priceValue['price_default_settings'][$defaultKey]['price_key'] != 'status') {
                                $defaultPrices[$statusCount] = [
                                    'key_id' => $defaultValue['xe_id'],
                                    'price_key' => $defaultValue['price_key'],
                                    'price_value' => $defaultValue['price_value'],
                                    'status' => $priceValue['price_default_settings'][$defaultKey + 1]['price_value'],
                                ];
                                $statusCount++;
                            }
                        }
                    } else {
                        foreach ($priceValue['price_default_settings'] as $defaultKey => $defaultValue) {
                            $defaultPrices[$defaultKey] = [
                                'key_id' => $defaultValue['xe_id'],
                                'price_key' => $defaultValue['price_key'],
                                'price_value' => $defaultValue['price_value'],
                            ];
                        }
                    }
                }

                // Creating Tier Priced Multi-Dim Print Area Array
                $printAreaList = [];
                foreach ($priceValue['tier_prices'] as $eachTier) {
                    // Set blank the Dump Container and Bucket to generate a price
                    $myTierRangeIdBucket = [];
                    /**
                     * Arrange Whitebase Records as per the discussed array format
                     */
                    $getWhiteBaseData = $eachTier->whitebase;
                    $myDumpContainer = [];
                    foreach ($getWhiteBaseData as $eachWhiteBase) {
                        // Get quantity Range from tier_range_id, in array format
                        $getQtyRange = $this->_getTierRange(
                            $eachWhiteBase->tier_range_id
                        );
                        if (in_array($eachWhiteBase->tier_range_id, $myTierRangeIdBucket)) {
                            // if found in dump_container
                            $myDumpContainer[$eachWhiteBase->tier_range_id] += [
                                "from_qty" => $getQtyRange['from_range'],
                                "to_qty" => $getQtyRange['to_range'],
                                // make d_price or l_price or w_price dynamically
                                $eachWhiteBase->white_base_type . '_price' => $eachWhiteBase->price,
                            ];
                        } else {
                            // if not found in dump_container
                            $myTierRangeIdBucket[] = $eachWhiteBase->tier_range_id;
                            // !important : If array's key not exist in
                            // dump_container, then just push the key, DONT
                            // APPEND (+)
                            $myDumpContainer[$eachWhiteBase->tier_range_id] = [
                                "from_qty" => $getQtyRange['from_range'],
                                "to_qty" => $getQtyRange['to_range'],
                                $eachWhiteBase->white_base_type . '_price' => $eachWhiteBase->price,
                            ];
                        }
                    }

                    $formattedKeyName = '';
                    // If Key Name exist then set Key name
                    if (isset($eachTier['key_name'])
                        && $eachTier['key_name'] != ""
                    ) {
                        $formattedKeyName = $eachTier['key_name'];
                    }
                    // If color_index exists, set key_name as color_index
                    if (isset($eachTier['color_index'])
                        && $eachTier['color_index'] != ""
                    ) {
                        $formattedKeyName = $eachTier['color_index'];
                    }

                    // If screen_cost exists, set key_name as screen_cost
                    $screenCost = 0;
                    if (isset($eachTier['screen_cost'])
                        && $eachTier['screen_cost'] != ""
                    ) {
                        $screenCost = $eachTier['screen_cost'];
                    }

                    // print_r($priceValue['price_module']['slug']); exit;
                    if ($priceValue['price_module']['slug'] == 'simple-deco'
                        && $eachTier['attribute_type'] == 'decoration_area'
                    ) {
                        $printAreaList[$eachTier['print_area_index']][$eachTier['attribute_type']][] = [
                            'key_name' => $formattedKeyName,
                            'print_area_id' => $eachTier['print_area_id'],
                            'pricing' => array_values($myDumpContainer),
                        ];
                    } elseif (in_array($priceValue['price_module']['slug'], $rangeSlugs, true) && in_array($eachTier['attribute_type'], $rangeTypes, true)) {
                        $printAreaList[$eachTier['print_area_index']][$eachTier['attribute_type']][] = [
                            'range_from' => $eachTier['range_from'],
                            'range_to' => $eachTier['range_to'],
                            'pricing' => array_values($myDumpContainer),
                        ];
                    } else {
                        if ($formattedKeyName =='default') {
                            $printAreaList[$eachTier['print_area_index']][$eachTier['attribute_type']][] = [
                                'key_name' => $formattedKeyName,
                                'pricing' => array_values($myDumpContainer),
                            ];
                        } else {
                            $printAreaList[$eachTier['print_area_index']][$eachTier['attribute_type']][] = [
                                'key_name' => $formattedKeyName,
                                'screen_cost' => $screenCost,
                                'pricing' => array_values($myDumpContainer),
                            ];
                        }
                    }
                }

                // Generate Price Settings array header key/values
                if ($getPriceDetails['price_module_settings'][$priceKey]['price_module']['slug'] == 'simple-deco') {
                    $priceSettings = [
                        'is_quote_enable' => $priceValue['is_quote_enabled'],
                        'is_quantity_tier' => $priceValue['is_quantity_tier'],
                        'quantity_tier_type' => $priceValue['quantity_tier_type'],
                        'is_advance_price' => $priceValue['is_advance_price'],
                        // Gathering Advanced Price Settings Data
                        'advance_price_type' => $advancePriceType->advanced_price_type,
                        'no_of_colors_allowed' => (!empty($advancePriceType->no_of_colors_allowed)
                            && $advancePriceType->no_of_colors_allowed > 0
                            ? $advancePriceType->no_of_colors_allowed : 0),
                        'is_full_color' => (!empty($advancePriceType->is_full_color)
                            && $advancePriceType->is_full_color > 0
                            ? $advancePriceType->is_full_color : 0),
                        'area_calculation_type' => (!empty($advancePriceType->area_calculation_type)
                            && $advancePriceType->area_calculation_type != ""
                            ? $advancePriceType->area_calculation_type : ""),
                        'min_price' => (!empty($advancePriceType->min_price)
                            && $advancePriceType->min_price > 0
                            ? $advancePriceType->min_price : 0),
                        'default_prices' => $defaultPrices,
                        'print_areas' => $printAreaList,
                    ];
                } else {
                    $priceSettings = [
                        'is_quote_enable' => $priceValue['is_quote_enabled'],
                        'is_quantity_tier' => $priceValue['is_quantity_tier'],
                        'quantity_tier_type' => $priceValue['quantity_tier_type'],
                        'default_prices' => $defaultPrices,
                        'print_areas' => $printAreaList,
                    ];
                }
                if ($getPriceDetails['price_module_settings'][$priceKey]['price_module']['slug'] == 'stitch-count') {
                    $priceSettings['default_stitch_count_per_inch'] = $priceValue['default_stitch_count_per_inch'];
                }
                $advancePriceType = [];
                $modules = [
                    'id' => $priceValue['price_module']['xe_id'],
                    'slug' => $priceValue['price_module']['slug'],
                    'status' => $priceValue['module_status'],
                    // Merging price setting into the Module array
                    'price_settings' => $priceSettings,
                ];
                // Creating Multi array Moduels
                $printProfilePricing['modules'][$priceKey] = $modules;
            }
        }
        $jsonResponse = [
            'status' => 1,
            'data' => $printProfilePricing,
        ];

        if (isset($args['return_type']) && $args['return_type'] == 'array') {
            return $printProfilePricing;
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Get Tier Range
     *
     * @param $tierRangeId Tier Range Id
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return range in array format
     */
    private function _getTierRange($tierRangeId)
    {
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
     * Delete: Delete Print Profile Pricing Detail
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's arg object
     *
     * @author satyabratap@riaxe.com
     * @date   25 Dec 2019
     * @return range in array format
     */
    public function deletePricingDetails($request, $response, $args)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Print Profile Pricing', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        if (isset($args['id']) && $args['id'] != "") {
            $printProfileKey = $args['id'];
            $printProfile = new PrintProfile();
            $jsonResponse = $printProfile->deleteProfilePricing($printProfileKey);
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * PUT: Print Profile Pricing Detail
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is updated or not
     */
    public function updatePricing($request, $response, $args)
    {
        $jsonResponse = [];
        $serverStatusCode = $this->serverStatusCode;
        $allPostPutVars = $request->getParsedBody();
        
        if (!empty($args['id'])) {
            $printProfileKey = $args['id'];
            $printProfile = new PrintProfile();
            $pricingDelResp = $printProfile->deleteProfilePricing($printProfileKey);
            // Save Basic Data of Print profile Pricing
            $getPriceJsonData = json_clean_decode($allPostPutVars['data'], true);
            $this->_pricingBasicData($getPriceJsonData, $printProfileKey);
            $this->_savePriceSettings(
                $getPriceJsonData['modules'], $printProfileKey
            );

            // Create Json file at Print Profile end
            $printProfile = new PrintProfile();
            $printProfile->createJsonFile($request, $response, $printProfileKey);
        }
        return response(
            $response, [
                'data' => $this->jsonMainResponse, 'status' => $serverStatusCode,
            ]
        );
    }

}
