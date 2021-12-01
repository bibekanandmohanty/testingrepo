<?php
/**
 * Manage Product Decorations - Single and Multiple Variation
 *
 * PHP version 7.2
 *
 * @category  Product_Decorations
 * @package   Decoration_Settings
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Controllers;

use App\Modules\DecorationAreas\Models\PrintArea;
use App\Modules\PrintProfiles\Models as PrintProfileModels;
use App\Modules\Products\Models\AppUnit;
use App\Modules\Products\Models\AttributePriceRule;
use App\Modules\Products\Models\DecorationObjects;
use App\Modules\Products\Models\PrintProfileDecorationSettingRel;
use App\Modules\Products\Models\PrintProfileProductSettingRel;
use App\Modules\Products\Models\ProductCategorySettingsRel;
use App\Modules\Products\Models\ProductDecorationSetting;
use App\Modules\Products\Models\ProductImageSettingsRel;
use App\Modules\Products\Models\ProductImageSides;
use App\Modules\Products\Models\ProductSection;
use App\Modules\Products\Models\ProductSectionImage;
use App\Modules\Products\Models\ProductSetting;
use App\Modules\Products\Models\ProductSettingsRel;
use App\Modules\Products\Models\ProductSide;
use App\Modules\Products\Models\ProductSizeVariantDecorationSetting;
use Illuminate\Database\Capsule\Manager as DB;
use ProductStoreSpace\Controllers\StoreProductsController;
use App\Modules\Products\Controllers\ProductConfiguratorController;

/**
 * Product Decoration Controller
 *
 * @category                Product_Decoration
 * @package                 Product
 * @author                  Tanmaya Patra <tanmayap@riaxe.com>
 * @license                 http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link                    http://inkxe-v10.inkxe.io/xetool/admin
 * @SuppressWarnings(PHPMD)
 */
class ProductDecorationsController extends StoreProductsController {
    /**
     * Delete: Delete Decoration Settings
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return All Print Profile List
     */
    public function deleteDecoration($request, $response, $args) {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [];
        $productKey = to_int($args['product_id']);

        $productSettingObj = new ProductSetting();
        $productSettingGet = $productSettingObj->where(
            'product_id', $productKey
        )->first();

        if (!empty($productSettingGet->xe_id) && $productSettingGet->xe_id > 0) {
            $productSettingDeleteId = $productSettingGet->xe_id;

            $prodImgSettRelObj = new ProductImageSettingsRel();
            $prodImgSettRelIds = $prodImgSettRelObj->where(
                'product_setting_id', $productSettingDeleteId
            )->delete();

            $productSideObj = new ProductSide();
            $productSideObj->where(
                'product_setting_id', $productSettingDeleteId
            )->delete();

            // Delete Print Profile Product Setting Relation Table Records
            $profProdSettRelObj = new PrintProfileProductSettingRel();
            $profProdSettRelObj->where(
                'product_setting_id', $productSettingDeleteId
            )->delete();

            // Delete Product Decoration Setting Table Records
            $productDecoSettObj = new ProductDecorationSetting();
            $productDecoSettIds = $productDecoSettObj->where(
                [
                    'product_setting_id' => $productSettingDeleteId,
                ]
            )
                ->get()
                ->pluck('xe_id')
                ->toArray();

            // Delete Product Setting Table Records
            $productSettDelObj = new ProductSetting();
            $productSettDelObj->where(
                'xe_id', $productSettingDeleteId
            )->delete();

            // Delete Print Profile Decoration Setting Relation Table Records
            if (!empty($productDecoSettIds) && count($productDecoSettIds) > 0) {
                $proflDecoSettRelObj = new PrintProfileDecorationSettingRel();
                $proflDecoSettRelObj->whereIn(
                    'decoration_setting_id', $productDecoSettIds
                )->delete();

                // Delete Print Profile Decoration Setting Relation Table Records
                $prodSzVarDecoSettObj = new ProductSizeVariantDecorationSetting();
                $prodSzVarDecoSettObj->whereIn(
                    'decoration_setting_id', $productDecoSettIds
                )->delete();

                // Delete Product Decoration Settings Table Records
                $prodDecoSettDelObj = new ProductDecorationSetting();
                $prodDecoSettDelObj->whereIn(
                    'xe_id', $productDecoSettIds
                )->delete();
            }

            $jsonResponse = [
                'status' => 1,
                'message' => message('Decoration Setting', 'deleted'),
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Post: Save Product Decoration Settings
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    public function saveProductDecorations($request, $response) {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Decoration Setting', 'error'),
        ];
        $isProductImage = $is3dPreviewAll = 0;
        $allPostPutVars = $request->getParsedBody();
        $getStoreDetails = get_store_details($request);

        $getProdDecoInfo = !empty($allPostPutVars['decorations'])
        ? json_clean_decode($allPostPutVars['decorations'], true) : null;
        $replaceExisting = !empty($allPostPutVars['replace'])
        ? $allPostPutVars['replace'] : 0;
        $storeId = $allPostPutVars['store_id'] ? $allPostPutVars['store_id'] : 1;

        $isVariableDecoration = !empty($getProdDecoInfo['is_variable_decoration'])
        ? $getProdDecoInfo['is_variable_decoration'] : 0;
        if (!empty($isVariableDecoration) && $isVariableDecoration === 1) {
            // Process for variable decoration area
            $variableDecoResp = $this->saveVariableProductDecoration(
                $request, $response, $getProdDecoInfo, $replaceExisting, false
            );
            return response(
                $response, [
                    'data' => $variableDecoResp['response'],
                    'status' => $variableDecoResp['server_status_code'],
                ]
            );
        }
        $prodSettInit = new ProductSetting();
        // Clear old records if replace is set to 1
        $checkRecord = $prodSettInit->where(
            ['product_id' => $getProdDecoInfo['product_id'], 'store_id' => $storeId]
        );
        if ($checkRecord->count() > 0) {
            if (!empty($replaceExisting) && (int) $replaceExisting === 1) {
                $checkRecord->delete();
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Variable Decoration', 'exist'),
                ];
                return response(
                    $response, ['data' => $jsonResponse, 'status' => OPERATION_OKAY]
                );
            }
        }
        // Save Product Decorations
        $prodSettInsId = $this->saveProductSetting(
            $request,
            $response,
            $getProdDecoInfo,
            $storeId
        );
        /**
         * Processing for Table: print_profile_product_setting_rel
         * - For saving the outer Print profile Ids
         */
        if (!empty($prodSettInsId) && !empty($getProdDecoInfo['print_profile_ids'])) {
            $printProfiles = $getProdDecoInfo['print_profile_ids'];
            $ppProdSettRelData = [];
            foreach ($printProfiles as $ppKey => $printProfile) {
                $ppProdSettRelData[$ppKey]['print_profile_id'] = $printProfile;
                $ppProdSettRelData[$ppKey]['product_setting_id'] = $prodSettInsId;
            }
            $ppProdSettRelSvInit = new PrintProfileProductSettingRel();
            $ppProdSettRelSvInit->insert($ppProdSettRelData);
        }
        if (isset($getProdDecoInfo['is_product_image_all']) && $getProdDecoInfo['is_product_image_all']) {
            $isProductImage = 1;
        }
        if (isset($getProdDecoInfo['is_3d_preview_all']) && $getProdDecoInfo['is_3d_preview_all']) {
            $is3dPreviewAll = 1;
        }
        if (!empty($prodSettInsId)) {
            if (isset($getProdDecoInfo['product_setting']) && !empty($getProdDecoInfo['product_setting'])) {
                $this->svaeProductSettingsRel($getProdDecoInfo['product_setting'], $prodSettInsId, $isProductImage, $is3dPreviewAll, $getProdDecoInfo['product_id']);
            }
            $jsonResponse = [
                'status' => 1,
                'product_settings_insert_id' => $prodSettInsId,
                'message' => message('Product Decoration Setting', 'saved'),
            ];
        }
        if (strtolower(STORE_NAME) == "shopify") {
            $this->saveProductAPIasCache($getProdDecoInfo['product_id']);
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Post: save product setting
     *
     * @param $request         Slim's Request object
     * @param $response        Slim's Response object
     * @param $getProdDecoInfo Decoration data from f/end
     * @param $storeId         Store id
     *
     * @author tanmayap@riaxe.com
     * @date   19 mar 2020
     * @return integer
     */
    protected function saveProductSetting($request, $response, $getProdDecoInfo, $storeId) {
        $productSettingId = 0;
        // If any file exist then upload
        $objectFileName = do_upload('3d_object_file', path('abs', '3d_object'), [150], 'string');
        // Processing for Table: product_settings
        if (!empty($getProdDecoInfo['product_id'])) {
            $productSettData = [
                'store_id' => $storeId,
                'product_id' => $getProdDecoInfo['product_id'],
                'is_crop_mark' => $getProdDecoInfo['is_crop_mark'],
                'is_ruler' => !empty($getProdDecoInfo['is_ruler']) ? $getProdDecoInfo['is_ruler'] : 0,
                'is_safe_zone' => $getProdDecoInfo['is_safe_zone'],
                'crop_value' => $getProdDecoInfo['crop_value'],
                'safe_value' => $getProdDecoInfo['safe_value'],
                'is_3d_preview' => $getProdDecoInfo['is_3d_preview'],
                '3d_object_file' => !empty($objectFileName) ? $objectFileName : null,
                '3d_object' => !empty($getProdDecoInfo['3d_object'])
                ? $getProdDecoInfo['3d_object'] : null,
                'scale_unit_id' => $getProdDecoInfo['scale_unit_id'],
            ];
            if (isset($getProdDecoInfo['is_configurator'])) {
                $productSettData['is_configurator'] = $getProdDecoInfo['is_configurator'];
            }
            $productSetting = new ProductSetting($productSettData);
            $productSetting->save();
            $productSettingId = $productSetting->xe_id;
        }

        // Processing for Table: product_image_settings_rel
        if (!empty($productSettingId)
            && !empty($getProdDecoInfo['product_image_id'])
        ) {
            $productImageSettings = new ProductImageSettingsRel(
                [
                    'product_setting_id' => $productSettingId,
                    'product_image_id' => $getProdDecoInfo['product_image_id'],
                ]
            );
            $productImageSettings->save();
        }

        // Save Decoration Sides
        $this->saveDecorationSides($getProdDecoInfo, $productSettingId);
        return $productSettingId;
    }
    /**
     * Post: save decoration sides
     *
     * @param $decoration Decoration data from f/end
     * @param $settingsId Settings save id
     *
     * @author tanmayap@riaxe.com
     * @date   19 mar 2020
     * @return boolean
     */
    public function saveDecorationSides($decoration, $settingsId) {
        // Processing for Table: product_sides, product_decoration_settings
        $imageSides = $decoration['sides'];
        if (!empty($settingsId) && !empty($imageSides)) {
            foreach ($imageSides as $sideKey => $productSideData) {
                $overlayImgUpload = do_upload('overlay_'.$sideKey, path('abs', 'overlay'), [], 'string');
                $productSide = new ProductSide(
                    [
                        'product_setting_id' => $settingsId,
                        'side_name' => $productSideData['name'],
                        'product_image_dimension' => $productSideData['image_dimension'],
                        'is_visible' => $productSideData['is_visible'],
                        'product_image_side_id' => $productSideData['product_image_side_id'],
                        'image_overlay' => $productSideData['is_image_overlay']?$productSideData['is_image_overlay']:0,
                        'multiply_overlay' => $productSideData['multiply_overlay']?$productSideData['multiply_overlay']:0,
                        'overlay_file_name' => $overlayImgUpload,
                    ]
                );
                $productSide->save();
                // Product Side Insert Id
                $prodSideInsId = $productSide->xe_id;
                $prodDecoSettRecord = [];
                foreach ($productSideData['product_decoration'] as $pdsKey => $productDecoSetting) {
                    $prodDecoSettRecord[$pdsKey] = $productDecoSetting;
                    $prodDecoSettRecord[$pdsKey]['product_side_id'] = $prodSideInsId;
                    $prodDecoSettRecord[$pdsKey]['dimension']
                    = isset($productDecoSetting['dimension'])
                    ? json_encode($productDecoSetting['dimension'], true)
                    : "{}";
                    $prodDecoSettRecord[$pdsKey]['locations']
                    = isset($productDecoSetting['locations'])
                    ? json_encode($productDecoSetting['locations'], true)
                    : "{}";
                    $prodDecoSettRecord[$pdsKey]['product_setting_id'] = $settingsId;

                    $prodDecoSettInit = new ProductDecorationSetting(
                        $prodDecoSettRecord[$pdsKey]
                    );
                    $prodDecoSettInit->save();
                    $prodDecoSettInsId = $prodDecoSettInit->xe_id;

                    if (!empty($prodDecoSettInsId)) {
                        // Processing for Table: print_profile_decoration_setting_rel
                        if (!empty($productDecoSetting['print_profile_ids'])) {
                            $printProfiles = $productDecoSetting['print_profile_ids'];
                            $ppDecoSettRelData = [];
                            foreach ($printProfiles as $ppKey => $printProfile) {
                                $ppDecoSettRelData[$ppKey]['print_profile_id'] = $printProfile;
                                $ppDecoSettRelData[$ppKey]['decoration_setting_id'] = $prodDecoSettInsId;
                            }
                            $ppDecoSettInit = new PrintProfileDecorationSettingRel();
                            $ppDecoSettInit->insert($ppDecoSettRelData);
                        }

                        /**
                         * Processing for the table :
                         * product_size_variant_decoration_settings
                         * - Saving the data of Size Variants
                         */
                        if (!empty($productDecoSetting['size_variants'])) {
                            $sizeVariants = $productDecoSetting['size_variants'];
                            $sizeVariantDecoSett = [];
                            foreach ($sizeVariants as $svKey => $sizeVariant) {
                                $sizeVariantDecoSett[$svKey]['size_variant_id'] = $sizeVariant['size_variant_id'];
                                $sizeVariantDecoSett[$svKey]['print_area_id'] = $sizeVariant['print_area_id'];
                                $sizeVariantDecoSett[$svKey]['decoration_setting_id'] = $prodDecoSettInsId;
                            }
                            $sizeVariantDecoSettInit = new ProductSizeVariantDecorationSetting();
                            $sizeVariantDecoSettInit->insert($sizeVariantDecoSett);
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Put: Update Product Decoration Settings
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    public function updateProductDecorations($request, $response, $args) {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Decoration Setting', 'error'),
        ];
        $isProductImage = $is3dPreviewAll = 0;
        $allPostPutVars = $request->getParsedBody();
        $productUpdateId = $args['product_id'];
        $getProductDecorationData = json_clean_decode(
            $allPostPutVars['decorations'], true
        );
        $productDecorationDataSet = [];
        $prodSettGtInit = new ProductSetting();
        $productSettingsUpdateInit = $prodSettGtInit->where(
            ['product_id' => $productUpdateId]
        );
        $productSettingsGet = $productSettingsUpdateInit->orderBy(
            'xe_id', 'desc'
        )
            ->first();
        $productSettingInsertId = $productSettingsGet->xe_id;
        if (!$productSettingInsertId) {
            return $this->saveProductDecorations($request, $response);
        } else {
            $isVariableDecoration = $getProductDecorationData['is_variable_decoration'];
            if (!empty($isVariableDecoration) && $isVariableDecoration === 1) {
                // Process for variable decoration area
                $getVariableProductDecoration = $this->saveVariableProductDecoration(
                    $request, $response, $getProductDecorationData, 0, true
                );
                return response(
                    $response, [
                        'data' => $getVariableProductDecoration['response'],
                        'status' => $getVariableProductDecoration['server_status_code'],
                    ]
                );
            }
            $updateObjectFile = do_upload('3d_object_file', path('abs', '3d_object'), [], 'string');
            if (!empty($updateObjectFile)) {
                $productDecorationDataSet += [
                    '3d_object_file' => $updateObjectFile,
                ];
            }

            // Processing for Table: product_settings
            if (!empty($productUpdateId)) {
                $productDecorationDataSet += [
                    'is_variable_decoration' => $isVariableDecoration,
                    'is_ruler' => $getProductDecorationData['is_ruler'],
                    'is_crop_mark' => $getProductDecorationData['is_crop_mark'],
                    'is_safe_zone' => $getProductDecorationData['is_safe_zone'],
                    'crop_value' => $getProductDecorationData['crop_value'],
                    'safe_value' => $getProductDecorationData['safe_value'],
                    'is_3d_preview' => $getProductDecorationData['is_3d_preview'],
                    '3d_object' => !empty($getProductDecorationData['3d_object'])
                    ? $getProductDecorationData['3d_object'] : "{}",
                    'scale_unit_id' => $getProductDecorationData['scale_unit_id'],
                ];
                if ($getProductDecorationData['is_configurator']) {
                    $productDecorationDataSet['is_configurator'] = $getProductDecorationData['is_configurator'];
                }
                $prodSettGtInit = new ProductSetting();
                $productSettingsUpdateInit = $prodSettGtInit->where(
                    ['product_id' => $productUpdateId]
                );
                $productSettingsUpdateInit->update($productDecorationDataSet);

                $productSettingsGet = $productSettingsUpdateInit->orderBy(
                    'xe_id', 'desc'
                )
                    ->first();
                $productSettingInsertId = $productSettingsGet->xe_id;

            }

            // Processing for Table: product_image_settings_rel
            if (isset($getProductDecorationData['product_image_id']) && $getProductDecorationData['product_image_id'] > 0) {

                $prodImgSettRelGtInit = new ProductImageSettingsRel();
                $productImageSettingInit = $prodImgSettRelGtInit->where(
                    ['product_setting_id' => $productSettingInsertId]
                );
                if ($productImageSettingInit->count() > 0) {
                    // Update record
                    $productImageSettingInit->update(
                        [
                            'product_image_id' => $getProductDecorationData['product_image_id'],
                        ]
                    );
                    $productImageSettingsInsertId = '';
                } else {
                    // Save record
                    $productImageSettings = new ProductImageSettingsRel(
                        [
                            'product_setting_id' => $productSettingInsertId,
                            'product_image_id' => $getProductDecorationData['product_image_id'],
                        ]
                    );
                    $productImageSettings->save();
                    $productImageSettingsInsertId = $productImageSettings->xe_id;
                }
            } elseif (isset($getProductDecorationData['product_image_id'])
                && $getProductDecorationData['product_image_id'] == 0
            ) {
                $prodImgSettRelGtInit = new ProductImageSettingsRel();
                $productImageSettingInit = $prodImgSettRelGtInit->where(
                    ['product_setting_id' => $productSettingInsertId]
                );
                $productImageSettingInit->delete();
            }

            // Processing for Table: product_sides, product_decoration_settings
            if (!empty($productSettingInsertId) && !empty($getProductDecorationData['sides'])) {
                // Clearing the old records
                $productSideObj = new ProductSide();
                $productSideObj->where(
                    ['product_setting_id' => $productSettingInsertId]
                )
                    ->delete();

                $imageSides = $getProductDecorationData['sides'];
                foreach ($imageSides as $sideKey => $productSideData) {
                    $uploadedFiles = $request->getUploadedFiles();
                    if (array_key_exists('overlay_'.$sideKey, $uploadedFiles)) {
                        $overlayImgUpload = do_upload('overlay_'.$sideKey, path('abs', 'overlay'), [], 'string');
                    }else{
                        $overlayImgUpload = substr($productSideData['overlay_image'], strrpos($productSideData['overlay_image'], '/') + 1);
                    }
                    $productSide = new ProductSide(
                        [
                            'product_setting_id' => $productSettingInsertId,
                            'side_name' => $productSideData['name'],
                            'product_image_dimension' => $productSideData['image_dimension'],
                            'is_visible' => $productSideData['is_visible'],
                            'product_image_side_id' => $productSideData['product_image_side_id'],
                            'image_overlay' => $productSideData['is_image_overlay']?$productSideData['is_image_overlay']:0,
                            'multiply_overlay' => $productSideData['multiply_overlay']?$productSideData['multiply_overlay']:0,
                            'overlay_file_name' => $overlayImgUpload,
                        ]
                    );
                    $productSide->save();
                    $productSideInsertId = $productSide->xe_id;
                    $prodDecoSettData = [];
                    foreach ($productSideData['product_decoration'] as $pdsKey => $productDecoSetting) {
                        $prodDecoSettData[$pdsKey] = $productDecoSetting;
                        $prodDecoSettData[$pdsKey]['product_side_id'] = $productSideInsertId;
                        $prodDecoSettData[$pdsKey]['dimension'] = isset($productDecoSetting['dimension'])
                        ? json_encode($productDecoSetting['dimension'], true) : "{}";
                        $prodDecoSettData[$pdsKey]['locations'] = isset($productDecoSetting['locations'])
                        ? json_encode($productDecoSetting['locations'], true) : "{}";
                        $prodDecoSettData[$pdsKey]['bleed_mark_data'] = isset($productDecoSetting['bleed_mark_data'])
                        ? json_encode($productDecoSetting['bleed_mark_data'], true) : "{}";
                        $prodDecoSettData[$pdsKey]['shape_mark_data'] = isset($productDecoSetting['shape_mark_data'])
                        ? json_encode($productDecoSetting['shape_mark_data'], true) : "{}";
                        $prodDecoSettData[$pdsKey]['product_setting_id'] = $productSettingInsertId;

                        $productDecorationSettingInit = new ProductDecorationSetting($prodDecoSettData[$pdsKey]);
                        $productDecorationSettingInit->save();
                        $prodDecoSettInsId = $productDecorationSettingInit->xe_id;

                        // Processing for Table: print_profile_decoration_setting_rel
                        if (!empty($prodDecoSettInsId) && !empty($productDecoSetting['print_profile_ids'])) {
                            $printProfiles = $productDecoSetting['print_profile_ids'];
                            $ppDecoSettRelData = [];
                            foreach ($printProfiles as $ppKey => $printProfile) {
                                $ppDecoSettRelData[$ppKey]['print_profile_id'] = $printProfile;
                                $ppDecoSettRelData[$ppKey]['decoration_setting_id'] = $prodDecoSettInsId;
                            }
                            $ppDecoSettRelInit = new PrintProfileDecorationSettingRel();
                            $ppDecoSettRelInit->insert($ppDecoSettRelData);
                        }

                        /**
                         * NEW Processing for the table :
                         * product_size_variant_decoration_settings
                         * - Saving the data of Size Variants
                         */
                        if (!empty($productDecoSetting['size_variants'])) {
                            // Clean up the old records
                            $prodSvVartDecoSetObj = new ProductSizeVariantDecorationSetting();
                            $prodSvVartDecoSetObj->where(
                                'decoration_setting_id',
                                $productDecorationSettingInsertId
                            )
                                ->delete();

                            $sizeVariants = $productDecoSetting['size_variants'];
                            $prodSizeVarDecoSett = [];
                            foreach ($sizeVariants as $svKey => $sizeVariant) {
                                $prodSizeVarDecoSett[$svKey]['size_variant_id'] = $sizeVariant['size_variant_id'];
                                $prodSizeVarDecoSett[$svKey]['print_area_id'] = $sizeVariant['print_area_id'];
                                $prodSizeVarDecoSett[$svKey]['decoration_setting_id'] = $productDecorationSettingInsertId;
                            }
                            $prodSzVarDecoSettini = new ProductSizeVariantDecorationSetting();
                            $prodSzVarDecoSettini->insert($prodSizeVarDecoSett);
                        }
                    }
                }
            }
            // Processing for Table: print_profile_product_setting_rel
            if (!empty($productSettingInsertId) && !empty($getProductDecorationData['print_profile_ids'])) {
                // Clearing the old records
                $profProdSettRelObj = new PrintProfileProductSettingRel();
                $profProdSettRelObj->where(
                    ['product_setting_id' => $productSettingInsertId]
                )
                    ->delete();

                $printProfiles = $getProductDecorationData['print_profile_ids'];
                $ppProdSettRelData = [];
                foreach ($printProfiles as $ppKey => $printProfile) {
                    $ppProdSettRelData[$ppKey]['print_profile_id'] = $printProfile;
                    $ppProdSettRelData[$ppKey]['product_setting_id'] = $productSettingInsertId;
                }
                $ppProdSettRelDataInit = new PrintProfileProductSettingRel();
                $ppProdSettRelDataInit->insert($ppProdSettRelData);
            }

            if (!empty($productSettingInsertId)) {
                if (isset($getProductDecorationData['product_setting']) && !empty($getProductDecorationData['product_setting'])) {
                    if (isset($getProductDecorationData['is_product_image_all']) && $getProductDecorationData['is_product_image_all']) {
                        $isProductImage = 1;
                    }
                    if (isset($getProductDecorationData['is_3d_preview_all']) && $getProductDecorationData['is_3d_preview_all']) {
                        $is3dPreviewAll = 1;
                    }
                    $this->svaeProductSettingsRel($getProductDecorationData['product_setting'], $productSettingInsertId, $isProductImage, $is3dPreviewAll, $productUpdateId);
                }
                $jsonResponse = [
                    'status' => 1,
                    'product_settings_insert_id' => $productSettingInsertId,
                    'message' => message('Product Decoration Setting', 'updated'),
                ];
            }
            if (strtolower(STORE_NAME) == "shopify") {
                $this->saveProductAPIasCache($args['product_id']);
            }
            return response(
                $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
            );
        }
    }

    /**
     * GET: Update and Save Variable Product Decoration Module
     * (ProductSetting ProductSide ProductDecorationSetting)
     *
     * @param $request         Slim's Request parameters
     * @param $response        Slim's Response parameters
     * @param $prodVarDecoData Product Variable Decoration Data
     * @param $doReplace       Flag to whearher replace/not
     * @param $updateRecord    Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    private function saveVariableProductDecoration(
        $request,
        $response,
        $prodVarDecoData,
        $doReplace,
        $updateRecord
    ) {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $prodSettingData = [
            'store_id' => $getStoreDetails['store_id'],
            'product_id' => $prodVarDecoData['product_id'],
            'is_variable_decoration' => $prodVarDecoData['is_variable_decoration'],
            'is_custom_size' => $prodVarDecoData['is_custom_size'],
            'is_crop_mark' => $prodVarDecoData['is_crop_mark'],
            'is_ruler' => $prodVarDecoData['is_ruler'],
            'is_safe_zone' => $prodVarDecoData['is_safe_zone'],
            'crop_value' => $prodVarDecoData['crop_value'],
            'safe_value' => $prodVarDecoData['safe_value'],
            'is_3d_preview' => $prodVarDecoData['is_3d_preview'],
            '3d_object_file' => "",
            '3d_object' => $prodVarDecoData['3d_object'],
            'scale_unit_id' => $prodVarDecoData['scale_unit_id'],
            'decoration_type' => $prodVarDecoData['decoration_type'],
            'custom_size_unit_price' => $prodVarDecoData['custom_size_unit_price'],
        ];
        if (isset($prodVarDecoData['decoration_dimensions']) && $prodVarDecoData['decoration_dimensions'] != "") {
            $prodSettingData['decoration_dimensions'] = json_clean_encode($prodVarDecoData['decoration_dimensions'], true);
        }
        if (isset($prodVarDecoData['is_configurator']) && $prodVarDecoData['is_configurator'] != "") {
            $prodSettingData['is_configurator'] = $prodVarDecoData['is_configurator'];
        }

        // Clear old records if replace is set to 1
        $prodSettInit = new ProductSetting();
        $checkProductSettingRecord = $prodSettInit->where(
            [
                'product_id' => $prodVarDecoData['product_id'],
            ]
        );
        if ($checkProductSettingRecord->count() > 0 && $updateRecord == false) {
            if (!empty($doReplace) && (int) $doReplace === 1) {
                $checkProductSettingRecord->delete();
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Variable Decoration', 'exist'),
                ];
                // Stop and return the error message from here
                return [
                    'response' => $jsonResponse,
                    'server_status_code' => $serverStatusCode,
                ];
            }
        }
        // Processing Product Settings
        // If any file exist then upload
        $uploadedFiles = $request->getUploadedFiles();
        if (!empty($prodVarDecoData['3d_object_file_upload'])
            && (!empty($uploadedFiles[$prodVarDecoData['3d_object_file_upload']]->file)
                && $uploadedFiles[$prodVarDecoData['3d_object_file_upload']]->file != "")
        ) {
            $objectFileName = do_upload(
                $prodVarDecoData['3d_object_file_upload'],
                path('abs', '3d_object'),
                [150],
                'string'
            );
            $prodSettingData['3d_object_file'] = $objectFileName;
        }

        $productSettingInsertId = 0;
        if (!empty($updateRecord) && $updateRecord === true) {
            unset($prodSettingData['product_id']);
            $prodSettInit = new ProductSetting();
            $getCheckProductSettingRecord = $prodSettInit->where(
                ['product_id' => $prodVarDecoData['product_id']]
            )
                ->select('xe_id')
                ->first();

            $checkProductSettingRecord->update($prodSettingData);
            if (!empty($getCheckProductSettingRecord->xe_id)
                && $getCheckProductSettingRecord->xe_id > 0
            ) {
                $productSettingInsertId = $getCheckProductSettingRecord->xe_id;
                // Delete old records from Print_Profile_Product_Setting_Rel
                $profProdSettRelObj = new PrintProfileProductSettingRel();
                $profProdSettRelObj->where(
                    ['product_setting_id' => $productSettingInsertId]
                )
                    ->delete();
                // Delete old records from Product_Image_Settings_Rel
                ProductImageSettingsRel::where(
                    ['product_setting_id' => $productSettingInsertId]
                )
                    ->delete();
            }
        } else {
            $productSetting = new ProductSetting($prodSettingData);
            $productSetting->save();
            $productSettingInsertId = $productSetting->xe_id;
        }

        if (!empty($productSettingInsertId) && $productSettingInsertId > 0) {
            // For Variable Decoration Ares, delete Sides if Any sides are available
            $prodSideGtInit = new ProductSide();
            $initProductSides = $prodSideGtInit->where(
                ['product_setting_id' => $productSettingInsertId]
            );
            if ($initProductSides->count() > 0) {
                $initProductSides->delete();
            }

            if (isset($prodVarDecoData['print_profile_ids'])
                && count($prodVarDecoData['print_profile_ids']) > 0
            ) {
                $printProfiles = $prodVarDecoData['print_profile_ids'];
                $ppProdSettRelData = [];
                foreach ($printProfiles as $ppKey => $printProfile) {
                    $ppProdSettRelData[$ppKey]['print_profile_id'] = $printProfile;
                    $ppProdSettRelData[$ppKey]['product_setting_id'] = $productSettingInsertId;
                }
                $ppProdSettRelSvInit = new PrintProfileProductSettingRel();
                $ppProdSettRelSvInit->insert($ppProdSettRelData);
            }

            // Processing Product Image Settings Rel
            if (isset($prodVarDecoData['product_image_id'])
                && $prodVarDecoData['product_image_id'] > 0
            ) {
                $productImageSettings = new ProductImageSettingsRel(
                    [
                        'product_setting_id' => $productSettingInsertId,
                        'product_image_id' => $prodVarDecoData['product_image_id'],
                    ]
                );
                $productImageSettings->save();
            }

            // Processing Product Decoration Settings Record
            $productDecorationData = $prodVarDecoData['product_decoration'];
            // Initialize Nw/new Product Deco Sett Data Array
            $prodDecoSettDataNw = [];
            // Choose dimension according to the flag
            if (!empty($productDecorationData['is_pre_defined'])
                && $productDecorationData['is_pre_defined'] == 1
            ) {
                $prodDecoSettDataNw['pre_defined_dimensions']
                = json_encode($productDecorationData['pre_defined_dimensions']);
                $prodDecoSettDataNw['user_defined_dimensions'] = null;
            } else {
                $prodDecoSettDataNw['user_defined_dimensions']
                = json_encode($productDecorationData['user_defined_dimensions']);
                $prodDecoSettDataNw['pre_defined_dimensions'] = null;
            }
            // for overlay image section (Note: for static boundary this is saved side wise)
            $uploadedFiles = $request->getUploadedFiles();
            if (array_key_exists('overlay_0', $uploadedFiles)) {
                $overlayImgUpload = do_upload('overlay_0', path('abs', 'overlay'), [], 'string');
            }else{
                $overlayImgUpload = substr($productDecorationData['overlay_image'], strrpos($productDecorationData['overlay_image'], '/') + 1);
            }
            // Setup decoration settings array
            $prodDecoSettDataNw += [
                'product_setting_id' => $productSettingInsertId,
                'dimension' => isset($productDecorationData['dimension'])
                ? json_encode($productDecorationData['dimension'], true) : "{}",
                'locations' => isset($productDecorationData['locations'])
                ? json_encode($productDecorationData['locations'], true) : "{}",
                'is_border_enable' => $productDecorationData['is_border_enable'],
                'is_sides_allow' => $productDecorationData['is_sides_allow'],
                'no_of_sides' => $productDecorationData['no_of_sides'],
                'bleed_mark_data' => isset($productDecorationData['bleed_mark_data'])
                ? json_encode($productDecorationData['bleed_mark_data'], true) : "{}",
                'shape_mark_data' => isset($productDecorationData['shape_mark_data'])
                ? json_encode($productDecorationData['shape_mark_data'], true) : "{}",
                'image_overlay' => $productDecorationData['is_image_overlay']?$productDecorationData['is_image_overlay']:0,
                'multiply_overlay' => $productDecorationData['multiply_overlay']?$productDecorationData['multiply_overlay']:0,
                'overlay_file_name' => $overlayImgUpload,
            ];
            if (!empty($productDecorationData['print_area_id'])
                && $productDecorationData['print_area_id'] > 0
            ) {
                $prodDecoSettDataNw += [
                    'print_area_id' => $productDecorationData['print_area_id'],
                ];
            }

            if (!empty($updateRecord) && $updateRecord === true) {
                unset($prodDecoSettDataNw['product_setting_id']);
                $prodDecoSettInit = new ProductDecorationSetting();
                $prodDecoSettUpdate = $prodDecoSettInit->where(
                    ['product_setting_id' => $productSettingInsertId]
                );
                $prodDecoSettUpdate->update($prodDecoSettDataNw);
                $prodDecoSettUpdGet = $prodDecoSettUpdate->select('xe_id')
                    ->first();
                $prodDecoSettInsId = $prodDecoSettUpdGet->xe_id;

                // Delete old records from "print_profile_decoration_setting_rel
                $ppDecoSettRelGtInit = new PrintProfileDecorationSettingRel();
                $ppDecoSettRelGtInit->where(
                    ['decoration_setting_id' => $prodDecoSettInsId]
                )
                    ->delete();
            } else {
                $prodDecoSettSaveInit = new ProductDecorationSetting(
                    $prodDecoSettDataNw
                );
                $prodDecoSettSaveInit->save();
                $prodDecoSettInsId = $prodDecoSettSaveInit->xe_id;
            }

            // Processing Print Profile Decoration Setting Rel
            if (isset($prodDecoSettInsId) && $prodDecoSettInsId > 0) {
                // Processing for Table: print_profile_decoration_setting_rel
                if (isset($productDecorationData['print_profile_ids'])
                    && count($productDecorationData['print_profile_ids']) > 0
                ) {
                    $printProfiles = $productDecorationData['print_profile_ids'];
                    $ppDecoSettRelData = [];
                    foreach ($printProfiles as $ppKey => $printProfile) {
                        $ppDecoSettRelData[$ppKey]['print_profile_id'] = $printProfile;
                        $ppDecoSettRelData[$ppKey]['decoration_setting_id'] = $prodDecoSettInsId;
                    }
                    $ppDecoSettRelSvInit = new PrintProfileDecorationSettingRel();
                    $ppDecoSettRelSvInit->insert($ppDecoSettRelData);
                }
            }

            $jsonResponse = [
                'status' => 1,
                'product_settings_insert_id' => $productSettingInsertId,
                'message' => message(
                    'Variable Product Decoration Setting',
                    (!empty($updateRecord) && $updateRecord === true)
                    ? 'updated' : 'saved'
                ),
            ];
        } else {
            $jsonResponse = [
                'status' => 0,
                'data' => [],
                'message' => message('Variable Product Decoration Setting', 'error'),
            ];
        }

        return [
            'response' => $jsonResponse,
            'server_status_code' => $serverStatusCode,
        ];
    }

    /**
     * Get: Get product settings details along with other related records
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   03 Feb 2020
     * @return Array
     */
    public function productSettingDetails($request, $response, $args) {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $settingsDetail = [];
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Settings Details', 'error'),
        ];
        $prodSettingObj = new ProductSetting();
        $productId = $args['product_id'];
        $returnType = !empty($args['return_type']) ? $args['return_type'] : 'json';
        $storeId = $getStoreDetails['store_id'];
        $settingsAssocRecords = $prodSettingObj->with('sides');
        $settingsAssocRecords->with('sides.product_decoration_setting');
        $settingsAssocRecords->with('sides.product_decoration_setting.product_size_variant_decoration_settings');
        $settingsAssocRecords->with('sides.product_decoration_setting.print_profile_decoration_settings');
        $settingsAssocRecords->with('sides.product_decoration_setting.print_profile_decoration_settings.print_profile');
        $settingsAssocRecords->with('sides.product_decoration_setting.print_area');
        $settingsAssocRecords->with('print_profiles', 'print_profiles.profile');
        $settingsAssocRecords->where(['product_id' => $productId]);
        $settingsAssocRecords->where(['store_id' => $storeId]);

        if ($settingsAssocRecords->count() > 0) {
            $settingsDetail = $settingsAssocRecords->orderBy(
                'xe_id', 'desc'
            )
                ->first()->toArray();
            $jsonResponse = [
                'status' => 1,
                'data' => $settingsDetail,
            ];
        }
        if ($returnType == 'json') {
            return response(
                $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
            );
        }
        return $settingsDetail;
    }
    /**
     * Get: Return AppUnit's default record if no ID available
     * or else return the associated data with the supplied ID
     *
     * @param $id     App Unit Primary Key
     * @param $column Which column to retun
     *
     * @author tanmayap@riaxe.com
     * @date   03 Mar 2020
     * @return string
     */
    protected function getAppUnit($id = 0, $column = 'label') {
        $data = 1;
        $appUnitObj = new AppUnit();
        if (!empty($id)) {
            $appUnitDetail = $appUnitObj->where('xe_id', $id);
        } else {
            $appUnitDetail = $appUnitObj->where('is_default', 1);
        }

        if ($appUnitDetail->count() > 0) {
            $scaleUnit = $appUnitDetail->first()->toArray();
            $data = $scaleUnit[$column];
        }

        return $data;
    }
    /**
     * Get: Fetch all Product Decoration Settings
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     * @param $returnType     response return type
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    public function getProductDecorations($request, $response, $args, $returnType = 0) {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [];
        $productDecorationSettingData = [];
        $productSideImages = [];
        $getFinalArray = [];
        $getScaleUnit = 0;
        $productId = !empty($args['product_id']) ? $args['product_id'] : null;
        $getStoreDetails = get_store_details($request);
        $store_id = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
        /**
         * Relational Tables by their function names from Model size_variant_id
         */
        $getSettingsAssociatedRecords = $this->productSettingDetails(
            $request, $response,
            [
                'product_id' => $productId,
                'return_type' => 'array',
            ]
        );
        /**
         * (#001) Get Product Image Sides from Respective Stores If the
         * store has no product images then, create a blank array with
         * exception error
         */
        $getStoreProduct = $this->getProducts(
            $request, $response, ['id' => $productId]
        );
        $is3dPreviewAll = $isProductImage = $isDecoration = 0;
        if (empty($getSettingsAssociatedRecords)) {
            $settingData = $this->getSettingsIdByProductId($productId, $getStoreProduct['products']['categories']);
            if (!empty($settingData)) {
                $is3dPreviewAll = $settingData->is_3d_preview;
                $isProductImage = $settingData->is_product_image;
                $isDecoration = 1;
                $getSettingsAssociatedRecords = $this->productSettingDetails(
                    $request, $response,
                    [
                        'product_id' => $settingData->product_id,
                        'return_type' => 'array',
                    ]
                );
            }
        }

        $attributeName = $this->getAttributeName();
        if (!empty($getStoreProduct['products'])) {
            try {
                $getProductDetails = $getStoreProduct['products'];
                $productSizeInfo = [];
                if (!empty($getProductDetails['attributes'])) {
                    foreach ($getProductDetails['attributes'] as $key => $attribute) {
                        if (!empty($attributeName['size'])) {
                            if (clean($attribute['name']) === clean($attributeName['size'])) {
                                $productSizeInfo = $attribute['options'];
                            }
                        }
                    }
                }
                if (!empty($getProductDetails['images'])) {
                    $productSideImages = $getProductDetails['images'];
                }

                $productVariantId = $getProductDetails['variant_id'];
                $getSizeData = $productSizeInfo;
            } catch (\Exception $e) {
                // If no Image found or if there is no product with the given
                create_log(
                    'Product Decorations', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'get product decoration',
                        ],
                    ]
                );
            }
        }
        // Ends
        // Append Product Name to the final array from the Store API
        $getFinalArray['product_name'] = "";
        if (!empty($getProductDetails['name'])) {
            $getFinalArray['product_name'] = $getProductDetails['name'];
        }
        $productDecorationSettingData += [
            // 'id' => $getSettingsAssociatedRecords['xe_id'],
            'product_id' => $productId,
            'variant_id' => !empty($productVariantId) ? $productVariantId : 0,
            'product_name' => $getFinalArray['product_name'],
            'type' => $getProductDetails['type'],
            'sku' => $getProductDetails['sku'],
            'price' => $getProductDetails['price'],
        ];
        $productDecorationSettingData['is_variable_decoration'] = 0;
        $productDecorationSettingData['is_custom_size'] = 0;
        $productDecorationSettingData['decoration_type'] = "";
        $productDecorationSettingData['custom_size_unit_price'] = "";
        $productDecorationSettingData['decoration_dimensions'] = "";
        $productDecorationSettingData['is_ruler'] = 0;
        $productDecorationSettingData['is_crop_mark'] = 0;
        $productDecorationSettingData['is_safe_zone'] = 0;
        $productDecorationSettingData['crop_value'] = 0;
        $productDecorationSettingData['safe_value'] = 0;
        $productDecorationSettingData['is_3d_preview'] = 0;
        $productDecorationSettingData['3d_object_file'] = "";
        $productDecorationSettingData['3d_object'] = "";
        $productDecorationSettingData['scale_unit_id'] = $getScaleUnit;
        $productDecorationSettingData['is_configurator'] = 0;
        $productDecorationSettingData['is_product_image'] = 0;
        $productDecorationSettingData['product_image_id'] = 0;
        $productDecorationSettingData['is_decoration_exists'] = 0;
        $productDecorationSettingData['is_svg_configurator'] = 0;
        $productDecorationSettingData += ['store_images' => $productSideImages];
        if (!empty($getSizeData)) {
            $productDecorationSettingData += ['size' => $getSizeData];
        }
        $productDecorationSettingData['sides'] = [];
        $productDecorationSettingData['print_profiles'] = [];
        // Check if any record(s) exist
        if (!empty($getSettingsAssociatedRecords) && count($getSettingsAssociatedRecords) > 0) {
            $getFinalArray = $getSettingsAssociatedRecords;
            /**
             * If the DB has it's own image product ID, send product_image_id, or
             * send 0
             */
            if ($isDecoration && $isProductImage) {
                $prodImgSettRelObj = new ProductImageSettingsRel();
                $checkForProductImage = $prodImgSettRelObj->where(
                    'product_setting_id', $getFinalArray['xe_id']
                )
                    ->first();
                $hasProdImgId = to_int($checkForProductImage['product_image_id']);
            } elseif (!$isDecoration) {
                $prodImgSettRelObj = new ProductImageSettingsRel();
                $checkForProductImage = $prodImgSettRelObj->where(
                    'product_setting_id', $getFinalArray['xe_id']
                )
                    ->first();
                $hasProdImgId = to_int($checkForProductImage['product_image_id']);
            } else {
                $hasProdImgId = 0;
            }

            $productDecorationSettingData['is_variable_decoration'] = $getFinalArray['is_variable_decoration'];
            $productDecorationSettingData['is_custom_size'] = $getFinalArray['is_custom_size'];
            $productDecorationSettingData['decoration_type'] = $getFinalArray['decoration_type'];
            $productDecorationSettingData['custom_size_unit_price'] = $getFinalArray['custom_size_unit_price'];
            $productDecorationSettingData['is_ruler'] = $getFinalArray['is_ruler'];
            $productDecorationSettingData['is_crop_mark'] = $getFinalArray['is_crop_mark'];
            $productDecorationSettingData['is_safe_zone'] = $getFinalArray['is_safe_zone'];
            $productDecorationSettingData['crop_value'] = (float) $getFinalArray['crop_value'];
            $productDecorationSettingData['safe_value'] = (float) $getFinalArray['safe_value'];
            $productDecorationSettingData['is_3d_preview'] = $getFinalArray['is_3d_preview'];
            // $productDecorationSettingData['3d_object_file'] = $getFinalArray['3d_object_file'];
            $productDecorationSettingData['3d_object'] = $getFinalArray['3d_object'];
            $productDecorationSettingData['scale_unit_id'] = $getFinalArray['scale_unit_id'];
            $productDecorationSettingData['is_configurator'] = (isset($getFinalArray['is_configurator']))? $getFinalArray['is_configurator'] : 0;
            $productDecorationSettingData['is_product_image'] = $hasProdImgId > 0 ? 1 : 0;
            $productDecorationSettingData['product_image_id'] = $hasProdImgId;
            $productDecorationSettingData['is_decoration_exists'] = 1;
            $productDecorationSettingData['is_svg_configurator'] = $getFinalArray['is_svg_configurator'];
            if (!empty($getFinalArray['decoration_dimensions'])) {
                $productDecorationSettingData['decoration_dimensions'] = json_clean_decode($getFinalArray['decoration_dimensions'], true);
            }
            $configuratorImage = [];
            if ($getFinalArray['is_configurator'] == 1) {
                $configuratorImage = $this->getConfiguratorImages($productId);
            }
            $productDecorationSettingData['configurator_image'] = $configuratorImage;
            if ($isDecoration && $is3dPreviewAll) {
                $decorationObjInit = new DecorationObjects();
                $objFileDetails = $decorationObjInit->select('3d_object_file')
                    ->where('product_id', $getFinalArray['product_id'])
                    ->first();
                $objFile = $objFileDetails['3d_object_file'];
            } elseif (!$isDecoration) {
                $decorationObjInit = new DecorationObjects();
                $objFileDetails = $decorationObjInit->select('3d_object_file')
                    ->where('product_id', $getFinalArray['product_id'])
                    ->first();
                $objFile = $objFileDetails['3d_object_file'];
            } else {
                $objFile = [];
            }
            $productDecorationSettingData['3d_object_file'] = !empty($objFile) ? $objFile : "";
            $prodImageSideObj = new ProductImageSides();
            $getProductImageSideInit = $prodImageSideObj->where(
                'product_image_id',
                $checkForProductImage['product_image_id']
            )
                ->get();
            $productDecorationSides = [];
            // Check if requested array is for Decoration or for variable Decoration
            if (!empty($getFinalArray['sides']) && count($getFinalArray['sides']) > 0) {
                if (!$isProductImage && $hasProdImgId == 0) {
                    $productImage = $getStoreProduct['products']['images'];
                    $imageSide = $productStoreImageSide = count($productImage);
                } else {
                    $imageSide = count($getFinalArray['sides']);
                }
                // debug($getFinalArray['sides']->toArray(), true);
                $i = 1;
                foreach ($getFinalArray['sides'] as $sideKey => $side) {
                    // Build Product Side Array
                    if ($imageSide >= $i) {
                        $productDecorationSides['id'] = $side['xe_id'];
                        $productDecorationSides['name'] = $side['side_name'];
                        $productDecorationSides['index'] = $side['side_index'];
                        $productDecorationSides['dimension'] = $side['dimension'];
                        $productDecorationSides['locations'] = $side['locations'];
                        $productDecorationSides['is_visible'] = $side['is_visible'];
                        $productDecorationSides['is_image_overlay'] = $side['image_overlay'];
                        $productDecorationSides['multiply_overlay'] = $side['multiply_overlay'];
                        $productDecorationSides['overlay_image'] = !empty($side['overlay_file_name'])? path('read', 'overlay').$side['overlay_file_name']:"";
                        $productDecorationSides['crop_value'] = $side['crop_value'];
                        $productDecorationSides['safe_value'] = $side['safe_value'];

                        // Get side image accordoing to the side index of the side array
                        // Check if product_image_id exist in ProductImageSettingsRel
                        $prodImgSettRelGtInit = new ProductImageSettingsRel();
                        $doExistProdImgSetts = $prodImgSettRelGtInit->where(
                            'product_image_id',
                            $checkForProductImage['product_image_id']
                        );
                        if ($doExistProdImgSetts->count() === 0) {
                            // Get Product Image Sides from Respective Stores
                            /**
                             * In the section #001, we got all images from Store
                             * end. There may be multiple images. Each image belongs
                             * to one side Programatically, we get each side by the
                             * foreach loop key's index
                             */
                            if (!empty($productSideImages[$sideKey])) {
                                $productDecorationSides['image'] = $productSideImages[$sideKey];
                            }
                        } else {
                            // Get Product Image Sides from DB
                            if (!empty($getProductImageSideInit[$sideKey])) {
                                $getProductImageSideData = $getProductImageSideInit[$sideKey];
                                if (!empty($getProductImageSideData)) {
                                    $productDecorationSides['image'] = [
                                        'id' => $getProductImageSideData->xe_id,
                                        'src' => $getProductImageSideData->file_name,
                                        'thumbnail' => $getProductImageSideData->thumbnail,
                                    ];
                                }
                            }
                        }
                        // End

                        // Loop through, Product Decoration Settings
                        $productDecorationSides['decoration_settings'] = [];
                        if (!empty($side['product_decoration_setting'])) {
                            foreach ($side['product_decoration_setting'] as $decorationSettingLoop => $decorationSetting) {
                                $decorationSizeVariantList = [];
                                foreach ($decorationSetting['product_size_variant_decoration_settings'] as $sizevariantKey => $sizeVariant) {
                                    $decorationSizeVariantList[$sizevariantKey] = [
                                        'print_area_id' => $sizeVariant['print_area_id'],
                                        'size_variant_id' => to_int($sizeVariant['size_variant_id']),
                                    ];
                                }
                                $productDecorationSides['decoration_settings'][$decorationSettingLoop] = [
                                    'id' => $decorationSetting['xe_id'],
                                    'name' => $decorationSetting['name'],
                                    'dimension' => $decorationSetting['dimension'],
                                    'locations' => $decorationSetting['locations'],
                                    'bleed_mark_data' => $decorationSetting['bleed_mark_data'],
                                    'shape_mark_data' => $decorationSetting['shape_mark_data'],
                                    'sub_print_area_type' => $decorationSetting['sub_print_area_type'],
                                    'min_height' => $decorationSetting['min_height'],
                                    'max_height' => $decorationSetting['max_height'],
                                    'min_width' => $decorationSetting['min_width'],
                                    'max_width' => $decorationSetting['max_width'],
                                    'is_border_enable' => $decorationSetting['is_border_enable'],
                                    'is_sides_allow' => $decorationSetting['is_sides_allow'],
                                    'print_area_id' => $decorationSetting['print_area_id'],
                                    'decoration_size_variants' => !empty($decorationSizeVariantList)
                                    ? $decorationSizeVariantList : [],
                                ];
                                // Loop through, Print Profile Decoration Setting
                                if (!empty($decorationSetting['print_profile_decoration_settings'])) {
                                    foreach ($decorationSetting['print_profile_decoration_settings'] as $ppDecoSetting) {
                                        if (!empty($ppDecoSetting['print_profile'][0]['xe_id'])
                                            && !empty($ppDecoSetting['print_profile'][0]['name'])
                                        ) {
                                            $productDecorationSides['decoration_settings'][$decorationSettingLoop]['print_profiles'][] = [
                                                'id' => $ppDecoSetting['print_profile'][0]['xe_id'],
                                                'name' => $ppDecoSetting['print_profile'][0]['name'],
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        $productDecorationSettingData['sides'][$sideKey] = $productDecorationSides;
                        $i++;
                    }
                } // End of foreach print_area_id
                $imageSides = $productStoreImageSide - count($productDecorationSettingData['sides']);
                if ($imageSides > 0) {
                    for ($sides = 1; $sides <= $imageSides; $sides++) {
                        $j = 1;
                        foreach ($getFinalArray['sides'] as $sideKey => $side) {
                            // Build Product Side Array
                            if ($imageSides >= $j) {
                                $productDecorationSides['id'] = $side['xe_id'];
                                $productDecorationSides['name'] = $side['side_name'];
                                $productDecorationSides['index'] = $side['side_index'];
                                $productDecorationSides['dimension'] = $side['dimension'];
                                $productDecorationSides['is_visible'] = $side['is_visible'];
                                $productDecorationSides['crop_value'] = $side['crop_value'];
                                $productDecorationSides['safe_value'] = $side['safe_value'];

                                // Get side image accordoing to the side index of the side array
                                // Check if product_image_id exist in ProductImageSettingsRel
                                $prodImgSettRelGtInit = new ProductImageSettingsRel();
                                $doExistProdImgSetts = $prodImgSettRelGtInit->where(
                                    'product_image_id',
                                    $checkForProductImage['product_image_id']
                                );
                                if ($doExistProdImgSetts->count() === 0) {
                                    // Get Product Image Sides from Respective Stores
                                    /**
                                     * In the section #001, we got all images from Store
                                     * end. There may be multiple images. Each image belongs
                                     * to one side Programatically, we get each side by the
                                     * foreach loop key's index
                                     */
                                    if (!empty($productSideImages[$sideKey])) {
                                        $productDecorationSides['image'] = $productSideImages[$sideKey + $sides];
                                    }
                                } else {
                                    // Get Product Image Sides from DB
                                    if (!empty($getProductImageSideInit[$sideKey])) {
                                        $getProductImageSideData = $getProductImageSideInit[$sideKey];
                                        if (!empty($getProductImageSideData)) {
                                            $productDecorationSides['image'] = [
                                                'id' => $getProductImageSideData->xe_id,
                                                'src' => $getProductImageSideData->file_name,
                                                'thumbnail' => $getProductImageSideData->thumbnail,
                                            ];
                                        }
                                    }
                                }
                                // End

                                // Loop through, Product Decoration Settings
                                $productDecorationSides['decoration_settings'] = [];
                                if (!empty($side['product_decoration_setting'])) {
                                    foreach ($side['product_decoration_setting'] as $decorationSettingLoop => $decorationSetting) {
                                        $decorationSizeVariantList = [];
                                        foreach ($decorationSetting['product_size_variant_decoration_settings'] as $sizevariantKey => $sizeVariant) {
                                            $decorationSizeVariantList[$sizevariantKey] = [
                                                'print_area_id' => $sizeVariant['print_area_id'],
                                                'size_variant_id' => to_int($sizeVariant['size_variant_id']),
                                            ];
                                        }
                                        $productDecorationSides['decoration_settings'][$decorationSettingLoop] = [
                                            'name' => $decorationSetting['name'],
                                            'dimension' => $decorationSetting['dimension'],
                                            'locations' => $decorationSetting['locations'],
                                            'sub_print_area_type' => $decorationSetting['sub_print_area_type'],
                                            'min_height' => $decorationSetting['min_height'],
                                            'max_height' => $decorationSetting['max_height'],
                                            'min_width' => $decorationSetting['min_width'],
                                            'max_width' => $decorationSetting['max_width'],
                                            'is_border_enable' => $decorationSetting['is_border_enable'],
                                            'is_sides_allow' => $decorationSetting['is_sides_allow'],
                                            'print_area_id' => $decorationSetting['print_area_id'],
                                            'decoration_size_variants' => !empty($decorationSizeVariantList)
                                            ? $decorationSizeVariantList : [],
                                        ];
                                        // Loop through, Print Profile Decoration Setting
                                        if (!empty($decorationSetting['print_profile_decoration_settings'])) {
                                            foreach ($decorationSetting['print_profile_decoration_settings'] as $ppDecoSetting) {
                                                if (!empty($ppDecoSetting['print_profile'][0]['xe_id'])
                                                    && !empty($ppDecoSetting['print_profile'][0]['name'])
                                                ) {
                                                    $productDecorationSides['decoration_settings'][$decorationSettingLoop]['print_profiles'][] = [
                                                        'id' => $ppDecoSetting['print_profile'][0]['xe_id'],
                                                        'name' => $ppDecoSetting['print_profile'][0]['name'],
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                                $productDecorationSettingData['sides'][$sides] = $productDecorationSides;
                                $j++;
                            }
                        }
                    }
                }
            } else {
                $varProdDecoData = [];
                // Get variable decoration settings data
                $productSettingId = $getFinalArray['xe_id'];
                $getProductDecorationSetting = ProductDecorationSetting::where(
                    ['product_setting_id' => $productSettingId]
                )
                    ->first();

                $varProdDecoData['product_decoration']['dimension']
                = json_clean_decode(
                    $getProductDecorationSetting['dimension'],
                    true
                );
                $varProdDecoData['product_decoration']['locations']
                = json_clean_decode(
                    $getProductDecorationSetting['locations'],
                    true
                );
                $varProdDecoData['product_decoration']['bleed_mark_data']
                = json_clean_decode(
                    $getProductDecorationSetting['bleed_mark_data'],
                    true
                );
                $varProdDecoData['product_decoration']['shape_mark_data']
                = json_clean_decode(
                    $getProductDecorationSetting['shape_mark_data'],
                    true
                );
                $varProdDecoData['product_decoration']['bound_price'] = $getProductDecorationSetting['custom_bound_price'];
                $varProdDecoData['product_decoration']['is_border_enable'] = $getProductDecorationSetting['is_border_enable'];
                $varProdDecoData['product_decoration']['is_sides_allow'] = $getProductDecorationSetting['is_sides_allow'];
                $varProdDecoData['product_decoration']['no_of_sides'] = $getProductDecorationSetting['no_of_sides'];
                $varProdDecoData['product_decoration']['print_area_id'] = $getProductDecorationSetting['print_area_id'];
                $varProdDecoData['product_decoration']['is_image_overlay'] = $getProductDecorationSetting['image_overlay'];
                $varProdDecoData['product_decoration']['multiply_overlay'] = $getProductDecorationSetting['multiply_overlay'];
                $varProdDecoData['product_decoration']['overlay_image'] = !empty($getProductDecorationSetting['overlay_file_name'])? path('read', 'overlay').$getProductDecorationSetting['overlay_file_name']:"";

                if (!empty($getProductDecorationSetting['pre_defined_dimensions'])
                    && $getProductDecorationSetting['pre_defined_dimensions'] != ""
                ) {
                    $varProdDecoData['product_decoration']['is_pre_defined']
                    = !empty($getProductDecorationSetting['pre_defined_dimensions']) ? 1 : 0;
                    $varProdDecoData['product_decoration']['pre_defined_dimensions']
                    = json_clean_decode(
                        $getProductDecorationSetting['pre_defined_dimensions'],
                        true
                    );
                } elseif (!empty($getProductDecorationSetting['user_defined_dimensions'])
                    && $getProductDecorationSetting['user_defined_dimensions'] != ""
                ) {
                    $varProdDecoData['product_decoration']['is_pre_defined']
                    = !empty($getProductDecorationSetting['user_defined_dimensions']) ? 0 : 1;
                    $varProdDecoData['product_decoration']['user_defined_dimensions']
                    = json_clean_decode(
                        $getProductDecorationSetting['user_defined_dimensions'],
                        true
                    );
                }

                $productDecorationSettingData += $varProdDecoData;
            }

            // Processing Print Profiles
            if (!empty($getFinalArray['print_profiles'])
                && count($getFinalArray['print_profiles']) > 0
            ) {
                foreach ($getFinalArray['print_profiles'] as $printProfile) {
                    if (!empty($printProfile['profile']['xe_id'])
                        && $printProfile['profile']['xe_id'] > 0
                        && !empty($printProfile['profile']['name'])
                    ) {
                        $productDecorationSettingData['print_profiles'][] = [
                            'id' => $printProfile['profile']['xe_id'],
                            'name' => $printProfile['profile']['name'],
                        ];
                    }
                }
            }

            $jsonResponse = [
                'status' => 1,
                'data' => $productDecorationSettingData,
            ];
        } else {
            $jsonResponse = [
                'status' => 1,
                'data' => $productDecorationSettingData,
            ];
        }
        if ($returnType == 1) {
            return $jsonResponse;
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Get: Product Decoration details with minimal store data
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   27 feb 2020
     * @return Json
     */
    public function productDetailsWithDecoration($request, $response, $args) {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [];
        $getScaleUnit = $this->getAppUnit();
        $productDecorationSettingData = [];
        $productSideImages = [];
        $productId = $args['product_id'];
        $storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
        $type = $request->getQueryParam('type') ? $request->getQueryParam('type') : '';
        $source = $request->getQueryParam('source') ? $request->getQueryParam('source') : '';
        $currentStoreUrl = '';
        $defaultStoreUrl = '';
        if ($storeId > 1 && $type = 'tool') {
            $databaseStoreInfo = DB::table('stores')->where('xe_id', '=', $storeId);
            if ($databaseStoreInfo->count() > 0) {
                $storeData = $databaseStoreInfo->get()->toArray();
                $storeDataArray = (array) $storeData[0];
                $currentStoreUrl = $storeDataArray['store_url'];
            }
        }
        /**
         * Relational Tables by their function names from Model size_variant_id
         */
        $getSettingsAssociatedRecords = $this->productSettingDetails(
            $request, $response,
            [
                'product_id' => $args['product_id'],
                'return_type' => 'array',
            ]
        );

        $is3dPreviewAll = $isProductImage = $isDecoration = 0;
        if (empty($getSettingsAssociatedRecords)) {
            $settingData = $this->getSettingsIdByProductId($args['product_id'], $getStoreProduct['categories']);
            if (!empty($settingData)) {
                $is3dPreviewAll = $settingData->is_3d_preview;
                $isProductImage = $settingData->is_product_image;
                $isDecoration = 1;
                $getSettingsAssociatedRecords = $this->productSettingDetails(
                    $request, $response,
                    [
                        'product_id' => $settingData->product_id,
                        'return_type' => 'array',
                    ]
                );
            }
            unset($getStoreProduct['categories']);
        }
        // Check if any record(s) exist
        if (!empty($getSettingsAssociatedRecords)) {
            $getFinalArray = $getSettingsAssociatedRecords;

            /**
             * (#001) Get Product Image Sides from Respective Stores If the
             * store has no product images then, create a blank array with
             * exception error
             */
            $variantId = $request->getQueryParam('variant_id');
            // For Opencart store
            $optionId = $request->getQueryParam('option_id') ?
            $request->getQueryParam('option_id') : "";
            // Get product details from the store method
            $getStoreProduct = $this->getProductShortDetails(
                $request,
                $response,
                [
                    'product_id' => $args['product_id'],
                    'variant_id' => $variantId,
                    'option_id' => $optionId,
                    'details' => 1,
                ]
            );
            $productImages = [];
            if ($args['product_id'] == $variantId && $getStoreProduct['decoration_id'] > 0) {
                $designId = $getStoreProduct['decoration_id'];
                $type = 'predecorators';
                $urlPath = "";
                if (file_exists(path('abs', 'design_state') . $type . '/' . $designId . '.json')) {
                    $design = file_get_contents(path('abs', 'design_state') . $type . '/' . $designId . '.json');
                    $designData = json_clean_decode($design, true);
                    $i = 0;
                    foreach ($designData['sides'] as $sides) {
                        $productImages[$i]['src'] = $sides['url'];
                        $productImages[$i]['thumbnail'] = $sides['url'];
                        $i++;
                    }
                }
            }
            if (!empty($getStoreProduct)) {
                try {
                    $attributeName = $this->getAttributeName();
                    if (!empty($getStoreProduct['attributes'][$attributeName['color']])) {
                        $colorData = $getStoreProduct['attributes'][$attributeName['color']];
                        if (!empty($colorData)) {
                            $attr[0]['id'] = $colorData['id'];
                            $attr[0]['name'] = $colorData['name'];

                            $variantData = $this->getColorSwatchData($attr);
                            $getStoreProduct['attributes'][$attributeName['color']] = $variantData[0];
                        }
                    }

                    $getProductDetails = $getStoreProduct;
                    $productSideImages = !empty($productImages)
                    ? $productImages
                    : $getProductDetails['images'];
                    $finalAttributes = [];
                } catch (\Exception $e) {
                    // If no Image found or if there is no product with the
                    // given product ID
                    $productSideImages = [
                        'id' => 0,
                        'message' => 'Sorry! It seems that, Store has no relevant product images for this Product',
                        'exception' => show_exception() === true ? $e->getMessage() : '',
                    ];
                }
            }
            // Ends
            // Append Product Name to the final array from the Store API
            if (isset($getProductDetails['data']['name'])
                && $getProductDetails['data']['name'] != ""
            ) {
                $getFinalArray['product_name'] = $getProductDetails['data']['name'];
            }
            if ($storeId > 1 && $type = 'tool') {
                foreach ($getProductDetails['images'] as $key => $value) {
                    $hostname = parse_url($value['src'], PHP_URL_HOST); //hostname
                    $getProductDetails['images'][$key]['src'] = str_replace($hostname, $currentStoreUrl, $value['src']);
                    $getProductDetails['images'][$key]['thumbnail'] = str_replace($hostname, $currentStoreUrl, $value['thumbnail']);
                }
            }

            /**
             * If the DB has it's own image product ID, send product_image_id, or send 0
             */
            if ($isDecoration && $isProductImage) {
                $prodImgSettRelObj = new ProductImageSettingsRel();
                $checkForProductImage = $prodImgSettRelObj->where(
                    'product_setting_id', $getFinalArray['xe_id']
                )
                    ->first();
                $hasProdImgId = to_int($checkForProductImage['product_image_id']);
            } elseif (!$isDecoration) {
                $prodImgSettRelObj = new ProductImageSettingsRel();
                $checkForProductImage = $prodImgSettRelObj->where(
                    'product_setting_id', $getFinalArray['xe_id']
                )
                    ->first();
                $hasProdImgId = to_int($checkForProductImage['product_image_id']);
            } else {
                $hasProdImgId = 0;
            }

            // Get App unit name
            $getScaleUnit = $this->getAppUnit($getFinalArray['scale_unit_id'], 'label');
            $getScaleUnitName = $this->getAppUnit($getFinalArray['scale_unit_id'], 'name');
            // Build Product Settings Array product_image_id
            $productDecorationSettingData = [
                'id' => $getFinalArray['xe_id'],
                'product_id' => $getFinalArray['product_id'],
                'is_variable_decoration' => $getFinalArray['is_variable_decoration'],
                'is_custom_size' => $getFinalArray['is_custom_size'],
                'decoration_type' => $getFinalArray['decoration_type'],
                'custom_size_unit_price' => $getFinalArray['custom_size_unit_price'],
                'product_name' => $getProductDetails['name'],
                'price' => (float) $getProductDetails['price'],
                'tax' => $getProductDetails['tax'] ? $getProductDetails['tax'] : 0,
                'is_ruler' => $getFinalArray['is_ruler'],
                'is_crop_mark' => $getFinalArray['is_crop_mark'],
                'is_safe_zone' => $getFinalArray['is_safe_zone'],
                'crop_value' => (float) $getFinalArray['crop_value'],
                'safe_value' => (float) $getFinalArray['safe_value'],
                'is_3d_preview' => $getFinalArray['is_3d_preview'],
                '3d_object_file' => $getFinalArray['3d_object_file'],
                '3d_object' => $getFinalArray['3d_object'],
                'scale_unit_id' => $getScaleUnit,
                'print_unit' => $getScaleUnitName,
                'is_configurator' => (isset($getFinalArray['is_configurator']))? $getFinalArray['is_configurator'] : 0,
                'is_product_image' => $hasProdImgId > 0 ? 1 : 0,
                'product_image_id' => $hasProdImgId,
                'attributes' => $getProductDetails['attributes'],
                'tier_prices' => isset($getProductDetails['tier_prices']) ? $getProductDetails['tier_prices'] : [],
                'is_svg_configurator' => $getFinalArray['is_svg_configurator'],
            ];
            if (!empty($getFinalArray['decoration_dimensions'])) {
                $productDecorationSettingData['decoration_dimensions'] = json_clean_decode($getFinalArray['decoration_dimensions'], true);
            }
            $configuratorImage = [];
            if ($getFinalArray['is_configurator'] == 1) {
                // Get Product Configurator Image
                $configuratorImage = $this->getConfiguratorImages($productId);
            }
            if ($productDecorationSettingData['is_svg_configurator'] == 1) {
                $configuratorInit = new ProductConfiguratorController();
                $configuratorData = $configuratorInit->getSVGProductConfigurator($request, $response, ["product_id"=>$productId,"isReturn"=>true]);
                if (!empty($configuratorData)) {
                    if(!empty($configuratorData[0]['sideList'])) {
                        $configuratorImage = $configuratorData[0]['sideList'];
                    }
                }
            }
            $productDecorationSettingData['configurator_image'] = $configuratorImage;
            if ($isDecoration && $is3dPreviewAll) {
                $decorationObjInit = new DecorationObjects();
                $objFileDetails = $decorationObjInit->select('3d_object_file')
                    ->where('product_id', $getFinalArray['product_id'])
                    ->first();
                $objFile = $objFileDetails['3d_object_file'];
            } elseif (!$isDecoration) {
                $decorationObjInit = new DecorationObjects();
                $objFileDetails = $decorationObjInit->select('3d_object_file')
                    ->where('product_id', $getFinalArray['product_id'])
                    ->first();
                $objFile = $objFileDetails['3d_object_file'];
            } else {
                $objFile = [];
            }
            $productDecorationSettingData['3d_object_file'] = !empty($objFile) ? $objFile : "";
            if (!empty($finalAttributes)) {
                $productDecorationSettingData += $finalAttributes;
            }

            $prodImgSideObj = new ProductImageSides();
            $getProductImageSideInit = $prodImgSideObj->where(
                'product_image_id',
                $checkForProductImage['product_image_id']
            )
                ->get();
            $productDecorationSides = [];
            // Check if requested array is for Decoration or for variable Decoration
            if (isset($getFinalArray['sides'])
                && count($getFinalArray['sides']) > 0
            ) {
                // For SVG configurator section sides is coming from only databse.
                if (!empty($configuratorImage)) {
                    $imageSide = count($getFinalArray['sides']);
                    if (count($configuratorImage) < $imageSide) {
                        $imageSide = count($configuratorImage);
                    }
                } else {
                    if (!$isProductImage && $hasProdImgId == 0) {
                        $productImage = $getStoreProduct['images'];
                        $imageSide = $productStoreImageSide = count($productImage);
                    } else {
                        $imageSide = count($getFinalArray['sides']);
                    }
                }
                $i = 1;
                foreach ($getFinalArray['sides'] as $sideKey => $side) {
                    // Build Product Side Array
                    if ($imageSide >= $i) {
                        $productDecorationSides['name'] = $side['side_name'];
                        $productDecorationSides['is_visible'] = $side['is_visible'];
                        $productDecorationSides['is_image_overlay'] = $side['image_overlay'];
                        $productDecorationSides['multiply_overlay'] = $side['multiply_overlay'];
                        $productDecorationSides['overlay_image'] = !empty($side['overlay_file_name'])? path('read', 'overlay').$side['overlay_file_name']:"";

                        // Get side image accordoing to the side index of the side array
                        // Check if product_image_id exist in ProductImageSettingsRel
                        $prodImgSettRelGtInit = new ProductImageSettingsRel();
                        $doExistProdImgSetts = $prodImgSettRelGtInit->where(
                            'product_image_id', $checkForProductImage['product_image_id']
                        );

                        if ($doExistProdImgSetts->count() === 0 || ($args['product_id'] == $variantId && $getStoreProduct['decoration_id'] > 0)) {
                            // Get Product Image Sides from Respective Stores
                            /**
                             * In the section #001, we got all images from Store
                             * end. There may be multiple images. Each image belongs
                             * to one side Programatically, we get each side by the
                             * foreach loop key's index
                             */
                            if (!empty($productSideImages[$sideKey])) {
                                $productDecorationSides['image'] = $productSideImages[$sideKey];
                            } else {
                                $productDecorationSides['image'] = [];
                            }
                        } else {
                            // Get Product Image Sides from DB
                            if (!empty($getProductImageSideInit[$sideKey])) {
                                $getProductImageSideData = $getProductImageSideInit[$sideKey];
                                if (!empty($getProductImageSideData)) {
                                    $productDecorationSides['image'] = [
                                        'id' => $getProductImageSideData->xe_id,
                                        'src' => $getProductImageSideData->file_name,
                                        'thumbnail' => $getProductImageSideData->thumbnail,
                                    ];
                                }
                            }
                        }
                        // End

                        // Loop through, Product Decoration Settings
                        $productDecorationSides['decoration_settings'] = [];
                        if (isset($side['product_decoration_setting'])
                            && count($side['product_decoration_setting']) > 0
                        ) {
                            foreach ($side['product_decoration_setting'] as $decorationSettingLoop => $decorationSetting) {
                                $decorationSizeVariantList = [];
                                foreach ($decorationSetting['product_size_variant_decoration_settings'] as $sizevariantKey => $sizeVariant) {
                                    $decorationSizeVariantList[$sizevariantKey] = [
                                        'print_area_id' => $sizeVariant['print_area_id'],
                                        'size_variant_id' => (int) $sizeVariant['size_variant_id'],
                                    ];
                                }
                                // Get print area and print area type
                                $printAreaObj = new PrintArea();
                                $getPrintAreaDetails = $printAreaObj->where('xe_id', $decorationSetting['print_area_id'])
                                    ->with('print_area_type')
                                    ->get();

                                $printAreaDetails = $getPrintAreaDetails->toArray();
                                $printArea = [];
                                if (!empty($printAreaDetails)) {
                                    $path = "";
                                    $typeName = "";
                                    if (!empty($printAreaDetails[0]['print_area_type'])) {
                                        $typeName = $printAreaDetails[0]['print_area_type']['name'];
                                    }
                                    $path = $printAreaDetails[0]['file_name'];
                                    $printArea = array(
                                        'id' => $printAreaDetails[0]['xe_id'],
                                        'name' => $printAreaDetails[0]['name'],
                                        'type' => $typeName,
                                        'height' => $printAreaDetails[0]['height'],
                                        'width' => $printAreaDetails[0]['width'],
                                        'path' => $path,
                                    );
                                }
                                // End
                                $productDecorationSides['decoration_settings'][$decorationSettingLoop] = [
                                    'name' => $decorationSetting['name'],
                                    'dimension' => $decorationSetting['dimension'],
                                    'locations' => $decorationSetting['locations'],
                                    'bleed_mark_data' => $decorationSetting['bleed_mark_data'],
                                    'shape_mark_data' => $decorationSetting['shape_mark_data'],
                                    'sub_print_area_type' => $decorationSetting['sub_print_area_type'],
                                    'min_height' => $decorationSetting['min_height'],
                                    'max_height' => $decorationSetting['max_height'],
                                    'min_width' => $decorationSetting['min_width'],
                                    'max_width' => $decorationSetting['max_width'],
                                    'is_border_enable' => $decorationSetting['is_border_enable'],
                                    'is_sides_allow' => $decorationSetting['is_sides_allow'],
                                    'print_area' => $printArea,
                                    'decoration_size_variants' => (isset($decorationSizeVariantList) && count($decorationSizeVariantList) > 0)
                                    ? $decorationSizeVariantList : [],
                                ];
                                // Loop through, Print Profile Decoration Setting
                                if (isset($decorationSetting['print_profile_decoration_settings'])
                                    && count($decorationSetting['print_profile_decoration_settings']) > 0
                                ) {
                                    foreach ($decorationSetting['print_profile_decoration_settings'] as $ppDecoSetting) {
                                        if ((isset($ppDecoSetting['print_profile'][0]['xe_id'])
                                            && $ppDecoSetting['print_profile'][0]['xe_id'] > 0)
                                            && !empty($ppDecoSetting['print_profile'][0]['name'])
                                        ) {
                                            $productDecorationSides['decoration_settings'][$decorationSettingLoop]['print_profiles'][] = [
                                                'id' => $ppDecoSetting['print_profile'][0]['xe_id'],
                                                'name' => $ppDecoSetting['print_profile'][0]['name'],
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                        $productDecorationSettingData['sides'][$sideKey] = $productDecorationSides;
                        $i++;
                    }
                } // End of foreach print_area_id
                $imageSides = $productStoreImageSide - count($productDecorationSettingData['sides']);
                if ($imageSides > 0) {
                    for ($sides = 1; $sides <= $imageSides; $sides++) {
                        $j = 1;
                        foreach ($getFinalArray['sides'] as $sideKey => $side) {
                            // Build Product Side Array
                            if ($imageSide >= $j) {
                                $productDecorationSides['name'] = $side['side_name'];
                                $productDecorationSides['is_visible'] = $side['is_visible'];
                                $productDecorationSides['is_image_overlay'] = $side['image_overlay'];
                                $productDecorationSides['multiply_overlay'] = $side['multiply_overlay'];
                                $productDecorationSides['overlay_image'] = !empty($side['overlay_file_name'])? path('read', 'overlay').$side['overlay_file_name']:"";

                                // Get side image accordoing to the side index of the side array
                                // Check if product_image_id exist in ProductImageSettingsRel
                                $prodImgSettRelGtInit = new ProductImageSettingsRel();
                                $doExistProdImgSetts = $prodImgSettRelGtInit->where(
                                    'product_image_id', $checkForProductImage['product_image_id']
                                );

                                if ($doExistProdImgSetts->count() === 0 || ($args['product_id'] == $variantId && $getStoreProduct['decoration_id'] > 0)) {
                                    // Get Product Image Sides from Respective Stores
                                    /**
                                     * In the section #001, we got all images from Store
                                     * end. There may be multiple images. Each image belongs
                                     * to one side Programatically, we get each side by the
                                     * foreach loop key's index
                                     */
                                    if (!empty($productSideImages[$sideKey])) {
                                        $productDecorationSides['image'] = $productSideImages[$sideKey + $sides];
                                    } else {
                                        $productDecorationSides['image'] = [];
                                    }
                                } else {
                                    // Get Product Image Sides from DB
                                    if (!empty($getProductImageSideInit[$sideKey])) {
                                        $getProductImageSideData = $getProductImageSideInit[$sideKey];
                                        if (!empty($getProductImageSideData)) {
                                            $productDecorationSides['image'] = [
                                                'id' => $getProductImageSideData->xe_id,
                                                'src' => $getProductImageSideData->file_name,
                                                'thumbnail' => $getProductImageSideData->thumbnail,
                                            ];
                                        }
                                    }
                                }
                                // End

                                // Loop through, Product Decoration Settings
                                $productDecorationSides['decoration_settings'] = [];
                                if (isset($side['product_decoration_setting'])
                                    && count($side['product_decoration_setting']) > 0
                                ) {
                                    foreach ($side['product_decoration_setting'] as $decorationSettingLoop => $decorationSetting) {
                                        $decorationSizeVariantList = [];
                                        foreach ($decorationSetting['product_size_variant_decoration_settings'] as $sizevariantKey => $sizeVariant) {
                                            $decorationSizeVariantList[$sizevariantKey] = [
                                                'print_area_id' => $sizeVariant['print_area_id'],
                                                'size_variant_id' => (int) $sizeVariant['size_variant_id'],
                                            ];
                                        }
                                        // Get print area and print area type
                                        $printAreaObj = new PrintArea();
                                        $getPrintAreaDetails = $printAreaObj->where('xe_id', $decorationSetting['print_area_id'])
                                            ->with('print_area_type')
                                            ->get();

                                        $printAreaDetails = $getPrintAreaDetails->toArray();
                                        $printArea = [];
                                        if (!empty($printAreaDetails)) {
                                            $path = "";
                                            $typeName = "";
                                            if (!empty($printAreaDetails[0]['print_area_type'])) {
                                                $typeName = $printAreaDetails[0]['print_area_type']['name'];
                                            }
                                            $path = $printAreaDetails[0]['file_name'];
                                            $printArea = array(
                                                'id' => $printAreaDetails[0]['xe_id'],
                                                'name' => $printAreaDetails[0]['name'],
                                                'type' => $typeName,
                                                'height' => $printAreaDetails[0]['height'],
                                                'width' => $printAreaDetails[0]['width'],
                                                'path' => $path,
                                            );
                                        }
                                        // End
                                        $productDecorationSides['decoration_settings'][$decorationSettingLoop] = [
                                            'name' => $decorationSetting['name'],
                                            'dimension' => $decorationSetting['dimension'],
                                            'locations' => $decorationSetting['locations'],
                                            'bleed_mark_data' => $decorationSetting['bleed_mark_data'],
                                            'shape_mark_data' => $decorationSetting['shape_mark_data'],
                                            'sub_print_area_type' => $decorationSetting['sub_print_area_type'],
                                            'min_height' => $decorationSetting['min_height'],
                                            'max_height' => $decorationSetting['max_height'],
                                            'min_width' => $decorationSetting['min_width'],
                                            'max_width' => $decorationSetting['max_width'],
                                            'is_border_enable' => $decorationSetting['is_border_enable'],
                                            'is_sides_allow' => $decorationSetting['is_sides_allow'],
                                            'print_area' => $printArea,
                                            'decoration_size_variants' => (isset($decorationSizeVariantList) && count($decorationSizeVariantList) > 0)
                                            ? $decorationSizeVariantList : [],
                                        ];
                                        // Loop through, Print Profile Decoration Setting
                                        if (isset($decorationSetting['print_profile_decoration_settings'])
                                            && count($decorationSetting['print_profile_decoration_settings']) > 0
                                        ) {
                                            foreach ($decorationSetting['print_profile_decoration_settings'] as $ppDecoSetting) {
                                                if ((isset($ppDecoSetting['print_profile'][0]['xe_id'])
                                                    && $ppDecoSetting['print_profile'][0]['xe_id'] > 0)
                                                    && !empty($ppDecoSetting['print_profile'][0]['name'])
                                                ) {
                                                    $productDecorationSides['decoration_settings'][$decorationSettingLoop]['print_profiles'][] = [
                                                        'id' => $ppDecoSetting['print_profile'][0]['xe_id'],
                                                        'name' => $ppDecoSetting['print_profile'][0]['name'],
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                                $productDecorationSettingData['sides'][$sides] = $productDecorationSides;
                                $j++;
                            }
                        }
                    }
                }
            } else {
                $varProdDecoData = [];
                // Get variable decoration settings data
                $productSettingId = $getFinalArray['xe_id'];
                $prodDecoSettObj = new ProductDecorationSetting();
                $getProductDecorationSetting = $prodDecoSettObj->where(
                    ['product_setting_id' => $productSettingId]
                )
                    ->first();
                // Get print area and print area type
                $printAreaObj = new PrintArea();
                $getPrintAreaDetails = $printAreaObj->where(
                    'xe_id', $getProductDecorationSetting['print_area_id']
                )
                    ->with('print_area_type')
                    ->get();

                $printAreaDetails = $getPrintAreaDetails->toArray();
                $printArea = [];
                if (!empty($printAreaDetails)) {
                    $path = "";
                    $typeName = "";
                    if (!empty($printAreaDetails[0]['print_area_type'])) {
                        $typeName = $printAreaDetails[0]['print_area_type']['name'];
                    }
                    $path = $printAreaDetails[0]['file_name'];
                    $printArea = array(
                        'id' => $printAreaDetails[0]['xe_id'],
                        'name' => $printAreaDetails[0]['name'],
                        'type' => $typeName,
                        'height' => $printAreaDetails[0]['height'],
                        'width' => $printAreaDetails[0]['width'],
                        'path' => $path,
                    );
                }
                // End

                $varProdDecoData['product_decoration']['dimension']
                = json_clean_decode($getProductDecorationSetting['dimension'], true);
                $varProdDecoData['product_decoration']['locations']
                = json_clean_decode($getProductDecorationSetting['locations'], true);
                $varProdDecoData['product_decoration']['bleed_mark_data']
                = json_clean_decode($getProductDecorationSetting['bleed_mark_data'], true);
                $varProdDecoData['product_decoration']['shape_mark_data']
                = json_clean_decode($getProductDecorationSetting['shape_mark_data'], true);
                $varProdDecoData['product_decoration']['bound_price'] = $getProductDecorationSetting['custom_bound_price'];
                $varProdDecoData['product_decoration']['is_border_enable'] = $getProductDecorationSetting['is_border_enable'];
                $varProdDecoData['product_decoration']['is_sides_allow'] = $getProductDecorationSetting['is_sides_allow'];
                $varProdDecoData['product_decoration']['no_of_sides'] = $getProductDecorationSetting['no_of_sides'];
                $varProdDecoData['product_decoration']['is_image_overlay'] = $getProductDecorationSetting['image_overlay'];
                $varProdDecoData['product_decoration']['multiply_overlay'] = $getProductDecorationSetting['multiply_overlay'];
                $varProdDecoData['product_decoration']['overlay_image'] = !empty($getProductDecorationSetting['overlay_file_name'])? path('read', 'overlay').$getProductDecorationSetting['overlay_file_name']:"";
                $varProdDecoData['product_decoration']['print_area'] = $printArea;

                if (!empty($getProductDecorationSetting['pre_defined_dimensions'])
                    && $getProductDecorationSetting['pre_defined_dimensions'] != ""
                ) {
                    $varProdDecoData['product_decoration']['is_pre_defined'] = !empty($getProductDecorationSetting['pre_defined_dimensions']) ? 1 : 0;
                    $varProdDecoData['product_decoration']['pre_defined_dimensions']
                    = json_clean_decode(
                        $getProductDecorationSetting['pre_defined_dimensions'],
                        true
                    );
                } elseif (!empty($getProductDecorationSetting['user_defined_dimensions'])
                    && $getProductDecorationSetting['user_defined_dimensions'] != ""
                ) {
                    $varProdDecoData['product_decoration']['is_pre_defined'] = !empty($getProductDecorationSetting['user_defined_dimensions']) ? 0 : 1;
                    $varProdDecoData['product_decoration']['user_defined_dimensions']
                    = json_clean_decode(
                        $getProductDecorationSetting['user_defined_dimensions'],
                        true
                    );
                }

                $productDecorationSettingData += $varProdDecoData;
            }

            // Process Price Data
            $priceInit = new AttributePriceRule();
            $getPriceData = $priceInit->where(['product_id' => $productId])->first();
            $productDecorationSettingData['is_price_rule'] = 0;
            if (!empty($getPriceData)) {
                $productDecorationSettingData['is_price_rule'] = 1;
            }
            if ($storeId > 1 && $type = 'tool' && $source != 'admin') {
                foreach ($productDecorationSettingData['sides'] as $key => $value) {
                    $hostname = parse_url($value['image']['src'], PHP_URL_HOST); //hostname
                    $productDecorationSettingData['sides'][$key]['image']['src'] = str_replace($hostname, $currentStoreUrl, $value['image']['src']);
                    $productDecorationSettingData['sides'][$key]['image']['thumbnail'] = str_replace($hostname, $currentStoreUrl, $value['image']['thumbnail']);
                }

            }
            // End

            // Processing Print Profiles
            $productDecorationSettingData['print_profiles'] = [];
            if (isset($getFinalArray['print_profiles'])
                && count($getFinalArray['print_profiles']) > 0
            ) {
                foreach ($getFinalArray['print_profiles'] as $printProfile) {
                    if (!empty($printProfile['profile']['xe_id'])
                        && $printProfile['profile']['xe_id'] > 0
                        && !empty($printProfile['profile']['name'])
                        && $printProfile['profile']['name'] != null
                    ) {
                        $productDecorationSettingData['print_profiles'][] = [
                            'id' => $printProfile['profile']['xe_id'],
                            'name' => $printProfile['profile']['name'],
                            'thumbnail' => $printProfile['profile']['thumbnail'],
                            'description' => !empty($printProfile['profile']['description']) ? $printProfile['profile']['description'] : "",
                        ];
                    }
                }
            }

            $jsonResponse = [
                'status' => 1,
                'data' => $productDecorationSettingData,
            ];
        } else {
            /**
             * (#001) Get Product Image Sides from Respective Stores If the
             * store has no product images then, create a blank array with
             * exception error
             */
            $variantId = $request->getQueryParam('variant_id');
            // For Opencart store
            $optionId = $request->getQueryParam('option_id') ?
            $request->getQueryParam('option_id') : "";
            $storeId = $request->getQueryParam('store_id') ?
            $request->getQueryParam('store_id') : 1;
            // Get product details from the store method
            $getStoreProduct = $this->getProductShortDetails(
                $request,
                $response,
                [
                    'product_id' => $args['product_id'],
                    'variant_id' => $variantId,
                    'option_id' => $optionId,
                    'details' => 1,
                ]
            );

            $productImages = [];
            if ($args['product_id'] == $variantId && $getStoreProduct['decoration_id'] > 0) {
                $designId = $getStoreProduct['decoration_id'];
                $type = 'predecorators';
                $urlPath = "";
                if (file_exists(path('abs', 'design_state') . $type . '/' . $designId . '.json')) {
                    $design = file_get_contents(path('abs', 'design_state') . $type . '/' . $designId . '.json');
                    $designData = json_clean_decode($design, true);
                    $i = 0;
                    foreach ($designData['sides'] as $sides) {
                        $productImages[$i]['src'] = $sides['url'];
                        $productImages[$i]['thumbnail'] = $sides['url'];
                        $i++;
                    }
                }
            }
            if (!empty($getStoreProduct)) {
                try {
                    $attributeName = $this->getAttributeName();
                    if (!empty($getStoreProduct['attributes'][$attributeName['color']])) {
                        $colorData = $getStoreProduct['attributes'][$attributeName['color']];
                        if (!empty($colorData)) {
                            $attr[0]['id'] = $colorData['id'];
                            $attr[0]['name'] = $colorData['name'];

                            $variantData = $this->getColorSwatchData($attr);
                            $getStoreProduct['attributes'][$attributeName['color']] = $variantData[0];
                        }
                    }

                    $getProductDetails = $getStoreProduct;
                    $productSideImages = !empty($productImages)
                    ? $productImages
                    : $getProductDetails['images'];
                    $finalAttributes = [];
                } catch (\Exception $e) {
                    // If no Image found or if there is no product with the
                    // given product ID
                    $productSideImages = [
                        'id' => 0,
                        'message' => 'Sorry! It seems that, Store has no relevant product images for this Product',
                        'exception' => show_exception() === true ? $e->getMessage() : '',
                    ];
                }
            }
            // Ends
            // Append Product Name to the final array from the Store API
            if (isset($getProductDetails['name'])
                && $getProductDetails['name'] != ""
            ) {
                $getFinalArray['product_name'] = $getProductDetails['name'];
            }
            $sides = [];
            if (!empty($getProductDetails['images'])) {
                $printProfile = [];
                $printProfileInit = new PrintProfileModels\PrintProfile();
                $getPrintProfileInfo = $printProfileInit->where(
                    [
                        'is_disabled' => 0,
                        'store_id' => $storeId,
                    ]
                )->first();
                // Check if print profile exist in this ID

                $productImage = $getProductDetails['images'];
                $imageSide = $productStoreImageSide = count($productImage);
                $dimension = '{"x":206,"y":212,"width":175.35,"height":124.05,"type":"rect","path":"","rotate":false,"cx":0,"cy":0,"cw":0,"ch":0,"sx":0,"sy":0,"sw":0,"sh":0}';
                $locations = '{"x_location":0,"y_location":0}';
                $print_area_id = 4;
                // Get print area and print area type
                $printAreaObj = new PrintArea();
                $getPrintAreaDetails = $printAreaObj->where('xe_id', $print_area_id)
                    ->with('print_area_type')
                    ->get();

                $printAreaDetails = $getPrintAreaDetails->toArray();
                $printArea = [];
                if (!empty($printAreaDetails)) {
                    $path = "";
                    $typeName = "";
                    if (!empty($printAreaDetails[0]['print_area_type'])) {
                        $typeName = $printAreaDetails[0]['print_area_type']['name'];
                    }
                    $path = $printAreaDetails[0]['file_name'];
                    $printArea = array(
                        'id' => $printAreaDetails[0]['xe_id'],
                        'name' => $printAreaDetails[0]['name'],
                        'type' => $typeName,
                        'height' => $printAreaDetails[0]['height'],
                        'width' => $printAreaDetails[0]['width'],
                        'path' => $path,
                    );
                }
                $decoration_settings = [];
                if ($imageSide > 0) {
                    $printProfile = [];
                    if (!empty($getPrintProfileInfo->xe_id)) {
                        $printProfile[] = [
                            'id' => $getPrintProfileInfo->xe_id,
                            'name' => $getPrintProfileInfo->name,
                        ];
                    }
                    for ($i = 1; $i <= $imageSide; $i++) {
                        $sides[$i]["name"] = "Side " . $i;
                        $sides[$i]["is_visible"] = 1;
                        $sides[$i]["is_image_overlay"] = 0;
                        $sides[$i]["multiply_overlay"] = 0;
                        $sides[$i]["overlay_image"] = "";
                        $sides[$i]["image"]["src"] = $productImage[$i - 1]['src'];
                        $sides[$i]["image"]["thumbnail"] = $productImage[$i - 1]['thumbnail'];
                        $sides[$i]["decoration_settings"][] = [
                            'name' => "Front",
                            "dimension" => $dimension,
                            "locations" => $locations,
                            "sub_print_area_type" => "normal_size",
                            "min_height" => null,
                            "max_height" => null,
                            "min_width" => null,
                            "max_width" => null,
                            "is_border_enable" => 0,
                            "is_sides_allow" => 0,
                            "print_area" => $printArea,
                            "decoration_size_variants" => [],
                            "print_profiles" => $printProfile,
                        ];

                    }
                }
            }
            $printProfiles = [];
            if (!empty($getPrintProfileInfo->xe_id)) {
                $printProfiles[] = [
                    'id' => $getPrintProfileInfo->xe_id,
                    'name' => $getPrintProfileInfo->name,
                    'thumbnail' => $getPrintProfileInfo->thumbnail,
                    'description' => !empty($getPrintProfileInfo->description) ? $getPrintProfileInfo->description : "",
                ];
            }
            // Get App unit name
            $getScaleUnit = $this->getAppUnit(0, 'label');
            $getScaleUnitName = $this->getAppUnit(0, 'name');
            // Build Product Settings Array product_image_id
            $productDecorationSettingData = [
                'id' => 1,
                'product_id' => $args['product_id'],
                'is_variable_decoration' => 0,
                'is_custom_size' => 0,
                'decoration_type' => null,
                'custom_size_unit_price' => 0,
                'product_name' => $getProductDetails['name'],
                'price' => to_decimal($getProductDetails['price']),
                'tax' => $getProductDetails['tax'] ? $getProductDetails['tax'] : 0,
                'is_ruler' => 0,
                'is_crop_mark' => 0,
                'is_safe_zone' => 0,
                'crop_value' => 0,
                'safe_value' => 0,
                'is_3d_preview' => 0,
                '3d_object_file' => '',
                '3d_object' => '{}',
                'scale_unit_id' => $getScaleUnit,
                'print_unit' => $getScaleUnitName,
                'is_configurator' => 0,
                'is_product_image' => 0,
                'product_image_id' => 0,
                'attributes' => $getProductDetails['attributes'],
                'tier_prices' => isset($getProductDetails['tier_prices']) ? $getProductDetails['tier_prices'] : [],
                'configurator_image' => [],
                'sides' => array_values($sides),
                'is_price_rule' => 0,
                'print_profiles' => $printProfiles,
                'is_svg_configurator' => 0,
            ];
            $jsonResponse = [
                'status' => 1,
                'data' => $productDecorationSettingData,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Post: Save & Get Decoration Object File
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   3 Feb 2019
     * @return A JSON Response
     */
    public function objDetailsOperation($request, $response) {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Object Details', 'error'),
        ];

        $allPostPutVars = $request->getParsedBody();
        if ($allPostPutVars['product_id'] != "") {
            $productId = $allPostPutVars['product_id'];
            $objInit = new DecorationObjects();
            $getDetails = $objInit->where('product_id', $productId)->first();
            if (!empty($getDetails)) {
                $getDetails = $getDetails->toArray();
                $uploadedFiles = $request->getUploadedFiles();
                $fileName = null;
                if (!empty($uploadedFiles['3d_object_file'])) {
                    // Delete from Directory
                    $countDetails = $objInit->where('product_id', $productId)->count();
                    if ($countDetails === 1) {
                        $this->deleteOldFile(
                            'decoration_objects', '3d_object_file', ['product_id' => $productId], path('abs', '3d_object')
                        );
                    }
                    $fileName = do_upload(
                        '3d_object_file', path('abs', '3d_object'), [], 'string'
                    );

                    $objData = [
                        '3d_object_file' => $fileName,
                    ];
                    $uvFileUpdate = $objInit->where(
                        ['product_id' => $productId]
                    );
                    if ($uvFileUpdate->update($objData)) {
                        $jsonResponse = [
                            'status' => 1,
                            '3d_object_file' => path('read', '3d_object') . $fileName,
                        ];
                    }
                }
            } else {
                $uploadedFiles = $request->getUploadedFiles();
                $fileName = null;
                if (!empty($uploadedFiles['3d_object_file'])) {
                    $fileName = do_upload(
                        '3d_object_file', path('abs', '3d_object'), [], 'string'
                    );

                    $objData = [
                        'product_id' => $productId,
                        '3d_object_file' => $fileName,
                    ];

                    $saveObjData = new DecorationObjects($objData);
                    if ($saveObjData->save()) {
                        $jsonResponse = [
                            'status' => 1,
                            '3d_object_file' => path('read', '3d_object') . $fileName,
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
     * Post: Save & Get UV File
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   11 Jun 2019
     * @return A JSON Response
     */
    public function uvFilesOperation($request, $response) {

        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator Image', 'error'),
        ];

        $allPostPutVars = $request->getParsedBody();
        if ($allPostPutVars['product_id'] != "") {
            $productId = $allPostPutVars['product_id'];
            $objInit = new DecorationObjects();
            $getDetails = $objInit->where('product_id', $productId)->first();
            if (!empty($getDetails)) {
                $getDetails = $getDetails->toArray();
                $uploadedFiles = $request->getUploadedFiles();
                $fileName = null;
                // Delete from Directory
                $this->deleteOldFile(
                    'decoration_objects', 'uv_file', ['product_id' => $productId], path('abs', '3d_object')
                );
                if (!empty($uploadedFiles['uv_file'])) {
                    $fileName = do_upload(
                        'uv_file', path('abs', '3d_object'), [], 'string'
                    );

                    $objData = [
                        'uv_file' => $fileName,
                    ];
                    $uvFileUpdate = $objInit->where(
                        ['product_id' => $productId]
                    );

                    if ($uvFileUpdate->update($objData)) {
                        $jsonResponse = [
                            'status' => 1,
                            'uv_file' => path('read', '3d_object') . $fileName,
                        ];
                    }
                }
            } else {
                $uploadedFiles = $request->getUploadedFiles();
                $fileName = null;
                if (!empty($uploadedFiles['uv_file'])) {
                    $fileName = do_upload(
                        'uv_file', path('abs', '3d_object'), [], 'string'
                    );

                    $objData = [
                        'product_id' => $productId,
                        'uv_file' => $fileName,
                    ];

                    $saveObjData = new DecorationObjects($objData);
                    if ($saveObjData->save()) {
                        $jsonResponse = [
                            'status' => 1,
                            'uv_file' => path('read', '3d_object') . $fileName,
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
     * Delete: Delete Decoration Settings From Relation Table
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   10 Sept 2019
     * @return Nothing
     */
    public function deleteProductDecorationRel($productKey) {
        $productSettingObj = new ProductSetting();
        $productSettingGet = $productSettingObj->where(
            'product_id', $productKey
        )->first();

        if (!empty($productSettingGet->xe_id) && $productSettingGet->xe_id > 0) {
            $this->removeProductSettingsRel($productKey);
            $this->removeProductCategorySettingsRel($productKey);
            $productSettingDeleteId = $productSettingGet->xe_id;

            $prodImgSettRelObj = new ProductImageSettingsRel();
            $prodImgSettRelIds = $prodImgSettRelObj->where(
                'product_setting_id', $productSettingDeleteId
            )->delete();

            $productSideObj = new ProductSide();
            $productSideObj->where(
                'product_setting_id', $productSettingDeleteId
            )->delete();

            // Delete Print Profile Product Setting Relation Table Records
            $profProdSettRelObj = new PrintProfileProductSettingRel();
            $profProdSettRelObj->where(
                'product_setting_id', $productSettingDeleteId
            )->delete();

            // Delete Product Decoration Setting Table Records
            $productDecoSettObj = new ProductDecorationSetting();
            $productDecoSettIds = $productDecoSettObj->where(
                [
                    'product_setting_id' => $productSettingDeleteId,
                ]
            )
                ->get()
                ->pluck('xe_id')
                ->toArray();

            // Delete Product Setting Table Records
            $productSettDelObj = new ProductSetting();
            $productSettDelObj->where(
                'xe_id', $productSettingDeleteId
            )->delete();

            // Delete Print Profile Decoration Setting Relation Table Records
            if (!empty($productDecoSettIds) && count($productDecoSettIds) > 0) {
                $proflDecoSettRelObj = new PrintProfileDecorationSettingRel();
                $proflDecoSettRelObj->whereIn(
                    'decoration_setting_id', $productDecoSettIds
                )->delete();

                // Delete Print Profile Decoration Setting Relation Table Records
                $prodSzVarDecoSettObj = new ProductSizeVariantDecorationSetting();
                $prodSzVarDecoSettObj->whereIn(
                    'decoration_setting_id', $productDecoSettIds
                )->delete();

                // Delete Product Decoration Settings Table Records
                $prodDecoSettDelObj = new ProductDecorationSetting();
                $prodDecoSettDelObj->whereIn(
                    'xe_id', $productDecoSettIds
                )->delete();
            }
        }
    }

    /**
     * POST: Save Product Setting Relation Table
     *
     * @param $productDetails  Product or product categoies id
     * @param $productSettingId Setting id
     * @param $isProductImage    Flag check product image enbaled or not
     * @param $is3dPreviewAll    Flaf check 3d enabled or not
     *
     * @author radhanatham@riaxe.com
     * @date   10 Sept 2019
     * @return Nothing
     */
    private function svaeProductSettingsRel($productDetails, $productSettingId, $isProductImage, $is3dPreviewAll, $parentProductId) {
        $ppProdSettRelData = [];
        foreach ($productDetails as $k => $productD) {
            if (!empty($productD['product_ids'])) {
                foreach ($productD['product_ids'] as $key => $productId) {
                    if (!in_array($productId, $productD['skip_product_ids'])) {
                        $this->removeProductSettingsRel($productId);
                        if ($parentProductId != $productId) {
                            $this->deleteProductDecorationRel($productId);
                            $ppProdSettRelData[$key]['product_setting_id'] = $productSettingId;
                            $ppProdSettRelData[$key]['product_id'] = $productId;
                            $ppProdSettRelData[$key]['is_3d_preview'] = $is3dPreviewAll;
                            $ppProdSettRelData[$key]['is_product_image'] = $isProductImage;
                        }
                    }
                }
                if (!empty($ppProdSettRelData)) {
                    $productSettingsRelInit = new ProductSettingsRel();
                    $productSettingsRelInit->insert($ppProdSettRelData);
                }
            }
            if (isset($productD['category_id']) && $productD['category_id']) {
                $this->removeProductCategorySettingsRel($productD['category_id']);
                $ppProdCatSettRelData = [
                    'product_setting_id' => $productSettingId,
                    'product_category_id' => $productD['category_id'],
                    'is_3d_preview' => $is3dPreviewAll,
                    'is_product_image' => $isProductImage,
                ];
                $ProductCategorySettingsRelInit = new ProductCategorySettingsRel();
                $ProductCategorySettingsRelInit->insert($ppProdCatSettRelData);
            }
        }
    }

    /**
     * Get: Delete Product Setting Relation Table
     *
     * @param $productId  Product Id
     *
     * @author radhanatham@riaxe.com
     * @date   10 Sept 2019
     * @return Nothing
     */
    private function removeProductSettingsRel($productId) {
        $productSettingsRelInit = new ProductSettingsRel();
        $getproductSettings = $productSettingsRelInit->where(
            ['product_id' => $productId]
        )->select('product_id');
        $totalCounts = $getproductSettings->count();
        if ($totalCounts > 0) {
            $prodSettRelInit = new ProductSettingsRel();
            $productSettingInit = $prodSettRelInit->where(
                ['product_id' => $productId]
            );
            $productSettingInit->delete();
        }
    }

    /**
     * Get: Delete Product Category Setting Relation Table
     *
     * @param $categoryId  Product category id
     *
     * @author radhanatham@riaxe.com
     * @date   10 Sept 2019
     * @return Nothing
     */
    private function removeProductCategorySettingsRel($categoryId) {
        $productCatSettingsRelInit = new ProductCategorySettingsRel();
        $getproductCatSettings = $productCatSettingsRelInit->where(
            ['product_category_id' => $categoryId]
        )->select('product_category_id');
        $totalCounts = $getproductCatSettings->count();
        if ($totalCounts > 0) {
            $prodCatSettRelInit = new ProductCategorySettingsRel();
            $productCatSettingInit = $prodCatSettRelInit->where(
                ['product_category_id' => $categoryId]
            );
            $productCatSettingInit->delete();
        }
    }

    /**
     * Get: Get Product Setting
     *
     * @param $productId  Product id
     * @param $productCategory  Product categories id
     *
     * @author radhanatham@riaxe.com
     * @date   10 Sept 2019
     * @return Setting Object
     */
    public function getSettingsIdByProductId($productId, $productCategory) {
        $settingData = [];
        $productSetting = DB::table('product_settings_rel')
            ->join('product_settings', 'product_settings.xe_id', '=', 'product_settings_rel.product_setting_id')
            ->where('product_settings_rel.product_id', '=', $productId)
            ->select('product_settings.product_id', 'product_settings_rel.product_setting_id', 'product_settings_rel.is_3d_preview', 'product_settings_rel.is_product_image')->first();
        if (!empty($productSetting)) {
            $settingData = $productSetting;
        } else {
            foreach ($productCategory as $k => $category) {
                $productSetting = DB::table('product_category_settings_rel')
                    ->join('product_settings', 'product_settings.xe_id', '=', 'product_category_settings_rel.product_setting_id')
                    ->where('product_category_settings_rel.product_category_id', '=', $category['id'])
                    ->select('product_settings.product_id', 'product_category_settings_rel.product_setting_id', 'product_category_settings_rel.is_3d_preview', 'product_category_settings_rel.is_product_image')->first();
                if (!empty($productSetting)) {
                    $settingData = $productSetting;
                    break;
                }
            }
        }
        return $settingData;
    }

    /**
     * Get: Get Product Configurator Images
     *
     * @param $productId  Product id
     *
     * @author satyabratap@riaxe.com
     * @date   05 Oct 2020
     * @return Setting Object
     */
    private function getConfiguratorImages($productId) {
        $configuratorImage = [];
        $sectionInit = new ProductSection();
        $sectionImageInit = new ProductSectionImage();
        $isSection = $sectionInit->where('product_id', $productId)->count();

        $sectionImages = [];
        if ($isSection > 0) {
            $sections = $sectionInit->select('xe_id')
                ->where('product_id', $productId)
                ->where('parent_id', 0)
                ->where('is_disable', 0)
                ->get()
                ->toArray();
            if (!empty($sections)) {
                foreach ($sections as $key => $section) {
                    $isSubsection = $sectionInit
                        ->where('parent_id', $section['xe_id'])
                        ->where('is_disable', 0)
                        ->count();
                    $parentImage = $sectionImageInit
                        ->where('section_id', $section['xe_id'])
                        ->where('is_disable', 0)
                        ->count();
                    if ($isSubsection === 0 && $parentImage > 0) {
                        $sectionImages = $sectionImageInit
                            ->where('section_id', $section['xe_id'])
                            ->where('is_disable', 0)
                            ->first();
                    } elseif ($isSubsection > 0 && $parentImage === 0) {
                        $subSectionId = $sectionInit
                            ->where('parent_id', $section['xe_id'])
                            ->where('is_disable', 0)
                            ->first()->xe_id;
                        $countSectionImages = $sectionImageInit
                            ->where('section_id', $subSectionId)
                            ->where('is_disable', 0)
                            ->count();
                        if ($countSectionImages > 0) {
                            $sectionImages = $sectionImageInit
                                ->where('section_id', $subSectionId)
                                ->where('is_disable', 0)
                                ->first();
                        }
                    }
                    if (!empty($sectionImages)) {
                        break;
                    }
                }
            }
        }
        if (!empty($sectionImages)) {
            $configuratorImage = [
                'src' => $sectionImages->file_name,
                'thumbnail' => $sectionImages->thumbnail,
                'thumb_value' => $sectionImages->thumb_value,
            ];
        }
        return $configuratorImage;
    }

    /**
     * Get: Fetch all Product Decoration Detail for Quotation details
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     * @param $returnType     response return type
     *
     * @author malay@riaxe.com
     * @date   29 Dec 2020
     * @return Json
     */
    public function getDecorationDetail($request, $response, $args, $returnType = 0) {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [];
        $productDecorationSettingData = [];
        $productSideImages = [];
        $getFinalArray = [];
        $getScaleUnit = 0;
        $productId = !empty($args['product_id']) ? $args['product_id'] : null;
        /**
         * Relational Tables by their function names from Model size_variant_id
         */
        $getSettingsAssociatedRecords = $this->productSettingDetails(
            $request, $response,
            [
                'product_id' => $productId,
                'return_type' => 'array',
            ]
        );
        /**
         * (#001) Get Product Image Sides from Respective Stores If the
         * store has no product images then, create a blank array with
         * exception error
         */
        $getStoreProduct = $this->getProducts(
            $request, $response, ['id' => $productId, 'store_id' => $getStoreDetails['store_id']]
        );
        $is3dPreviewAll = $isProductImage = $isDecoration = 0;
        if (empty($getSettingsAssociatedRecords)) {
            $settingData = $this->getSettingsIdByProductId($productId, $getStoreProduct['products']['categories']);
            if (!empty($settingData)) {
                $is3dPreviewAll = $settingData->is_3d_preview;
                $isProductImage = $settingData->is_product_image;
                $isDecoration = 1;
            }
        }

        $attributeName = $this->getAttributeName();
        if (!empty($getStoreProduct['products'])) {
            try {
                $getProductDetails = $getStoreProduct['products'];
                $productSizeInfo = [];
                if (!empty($getProductDetails['attributes'])) {
                    $attrKey = array_search(
                        $attributeName['size'],
                        array_column($getStoreProduct['attributes'], 'name')
                    );
                    $productSizeInfo = $getProductDetails['attributes'][$attrKey]['options'];
                }
                if (!empty($getProductDetails['images'])) {
                    $productSideImages = $getProductDetails['images'];
                }

                $productVariantId = $getProductDetails['variant_id'];
                $getSizeData = $productSizeInfo;
            } catch (\Exception $e) {
                // If no Image found or if there is no product with the given
                create_log(
                    'Product Decorations', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'get product decoration',
                        ],
                    ]
                );
            }
        }
        // Ends
        // Append Product Name to the final array from the Store API
        $getFinalArray['product_name'] = "";
        if (!empty($getProductDetails['name'])) {
            $getFinalArray['product_name'] = $getProductDetails['name'];
        }
        $productDecorationSettingData += [
            // 'id' => $getSettingsAssociatedRecords['xe_id'],
            'product_id' => $productId,
            'variant_id' => !empty($productVariantId) ? $productVariantId : 0,
            'product_name' => $getFinalArray['product_name'],
            'type' => $getProductDetails['type'],
            'sku' => $getProductDetails['sku'],
            'price' => $getProductDetails['price'],
        ];

        $productDecorationSettingData['is_variable_decoration'] = 0;
        $productDecorationSettingData['is_custom_size'] = 0;
        $productDecorationSettingData['decoration_type'] = "";
        $productDecorationSettingData['custom_size_unit_price'] = "";
        $productDecorationSettingData['decoration_dimensions'] = "";
        $productDecorationSettingData['is_ruler'] = 0;
        $productDecorationSettingData['is_crop_mark'] = 0;
        $productDecorationSettingData['is_safe_zone'] = 0;
        $productDecorationSettingData['crop_value'] = 0;
        $productDecorationSettingData['safe_value'] = 0;
        $productDecorationSettingData['is_3d_preview'] = 0;
        $productDecorationSettingData['3d_object_file'] = "";
        $productDecorationSettingData['3d_object'] = "";
        $productDecorationSettingData['scale_unit_id'] = $getScaleUnit;
        $productDecorationSettingData['is_configurator'] = 0;
        $productDecorationSettingData['is_product_image'] = 0;
        $productDecorationSettingData['product_image_id'] = 0;
        $productDecorationSettingData['is_decoration_exists'] = 0;
        $productDecorationSettingData += ['store_images' => $productSideImages];

        if (!empty($getSizeData)) {
            $productDecorationSettingData += ['size' => $getSizeData];
        }
        $productDecorationSettingData['sides'] = [];
        $productDecorationSettingData['print_profiles'] = [];
        // Check if any record(s) exist
        if (!empty($getSettingsAssociatedRecords) && count($getSettingsAssociatedRecords) > 0) {
            $getFinalArray = $getSettingsAssociatedRecords;
            /**
             * If the DB has it's own image product ID, send product_image_id, or
             * send 0
             */
            if ($isDecoration && $isProductImage) {
                $prodImgSettRelObj = new ProductImageSettingsRel();
                $checkForProductImage = $prodImgSettRelObj->where(
                    'product_setting_id', $getFinalArray['xe_id']
                )
                    ->first();
                $hasProdImgId = to_int($checkForProductImage['product_image_id']);
            } elseif (!$isDecoration) {
                $prodImgSettRelObj = new ProductImageSettingsRel();
                $checkForProductImage = $prodImgSettRelObj->where(
                    'product_setting_id', $getFinalArray['xe_id']
                )
                    ->first();
                $hasProdImgId = to_int($checkForProductImage['product_image_id']);
            } else {
                $hasProdImgId = 0;
            }

            $productDecorationSettingData['is_variable_decoration'] = $getFinalArray['is_variable_decoration'];
            $productDecorationSettingData['is_custom_size'] = $getFinalArray['is_custom_size'];
            $productDecorationSettingData['decoration_type'] = $getFinalArray['decoration_type'];
            $productDecorationSettingData['custom_size_unit_price'] = $getFinalArray['custom_size_unit_price'];
            $productDecorationSettingData['is_ruler'] = $getFinalArray['is_ruler'];
            $productDecorationSettingData['is_crop_mark'] = $getFinalArray['is_crop_mark'];
            $productDecorationSettingData['is_safe_zone'] = $getFinalArray['is_safe_zone'];
            $productDecorationSettingData['crop_value'] = (float) $getFinalArray['crop_value'];
            $productDecorationSettingData['safe_value'] = (float) $getFinalArray['safe_value'];
            $productDecorationSettingData['is_3d_preview'] = $getFinalArray['is_3d_preview'];
            // $productDecorationSettingData['3d_object_file'] = $getFinalArray['3d_object_file'];
            $productDecorationSettingData['3d_object'] = $getFinalArray['3d_object'];
            $productDecorationSettingData['scale_unit_id'] = $getFinalArray['scale_unit_id'];
            $productDecorationSettingData['is_configurator'] = (isset($getFinalArray['is_configurator']))? $getFinalArray['is_configurator'] : 0;
            $productDecorationSettingData['is_product_image'] = $hasProdImgId > 0 ? 1 : 0;
            $productDecorationSettingData['product_image_id'] = $hasProdImgId;
            $productDecorationSettingData['is_decoration_exists'] = 1;
            if (!empty($getFinalArray['decoration_dimensions'])) {
                $productDecorationSettingData['decoration_dimensions'] = json_clean_decode($getFinalArray['decoration_dimensions'], true);
            }
            $configuratorImage = [];
            if ($getFinalArray['is_configurator'] == 1) {
                $configuratorImage = $this->getConfiguratorImages($productId);
            }
            $productDecorationSettingData['configurator_image'] = $configuratorImage;
            if ($isDecoration && $is3dPreviewAll) {
                $decorationObjInit = new DecorationObjects();
                $objFileDetails = $decorationObjInit->select('3d_object_file')
                    ->where('product_id', $getFinalArray['product_id'])
                    ->first();
                $objFile = $objFileDetails['3d_object_file'];
            } elseif (!$isDecoration) {
                $decorationObjInit = new DecorationObjects();
                $objFileDetails = $decorationObjInit->select('3d_object_file')
                    ->where('product_id', $getFinalArray['product_id'])
                    ->first();
                $objFile = $objFileDetails['3d_object_file'];
            } else {
                $objFile = [];
            }
            $productDecorationSettingData['3d_object_file'] = !empty($objFile) ? $objFile : "";
            $prodImageSideObj = new ProductImageSides();
            $getProductImageSideInit = $prodImageSideObj->where(
                'product_image_id',
                $checkForProductImage['product_image_id']
            )
                ->get();
            $productDecorationSides = [];
            // Check if requested array is for Decoration or for variable Decoration
            if (!empty($getFinalArray['sides']) && count($getFinalArray['sides']) > 0) {
                if (!$isProductImage && $hasProdImgId == 0) {
                    $productImage = $getStoreProduct['products']['images'];
                    $imageSide = $productStoreImageSide = count($productImage);
                } else {
                    $imageSide = count($getFinalArray['sides']);
                }
                // debug($getFinalArray['sides']->toArray(), true);
                $productDecorationSettingData['sides'] = $getFinalArray['sides'];
                foreach ($getFinalArray['sides'] as $sideKey => $side) {
                    $prodImgSettRelGtInit = new ProductImageSettingsRel();
                    $doExistProdImgSetts = $prodImgSettRelGtInit->where(
                        'product_image_id',
                        $checkForProductImage['product_image_id']
                    );
                    if ($doExistProdImgSetts->count() === 0) {
                        // Get Product Image Sides from Respective Stores
                        /**
                         * In the section #001, we got all images from Store
                         * end. There may be multiple images. Each image belongs
                         * to one side Programatically, we get each side by the
                         * foreach loop key's index
                         */
                        if (!empty($productSideImages[$sideKey])) {
                            $productDecorationSettingData['sides'][$sideKey]['image'] = $productSideImages[$sideKey];
                        }
                    } else {
                        // Get Product Image Sides from DB
                        if (!empty($getProductImageSideInit[$sideKey])) {
                            $getProductImageSideData = $getProductImageSideInit[$sideKey];
                            if (!empty($getProductImageSideData)) {
                                $productDecorationSettingData['sides'][$sideKey]['image'] = [
                                    'id' => $getProductImageSideData->xe_id,
                                    'src' => $getProductImageSideData->file_name,
                                    'thumbnail' => $getProductImageSideData->thumbnail,
                                ];
                            }
                        }
                    }
                }
            } else {
                $varProdDecoData = [];
                // Get variable decoration settings data
                $productSettingId = $getFinalArray['xe_id'];
                $getProductDecorationSetting = ProductDecorationSetting::where(
                    ['product_setting_id' => $productSettingId]
                )
                    ->first();

                $varProdDecoData['product_decoration']['dimension']
                = json_clean_decode(
                    $getProductDecorationSetting['dimension'],
                    true
                );
                $varProdDecoData['product_decoration']['locations']
                = json_clean_decode(
                    $getProductDecorationSetting['locations'],
                    true
                );
                $varProdDecoData['product_decoration']['bleed_mark_data']
                = json_clean_decode(
                    $getProductDecorationSetting['bleed_mark_data'],
                    true
                );
                $varProdDecoData['product_decoration']['shape_mark_data']
                = json_clean_decode(
                    $getProductDecorationSetting['shape_mark_data'],
                    true
                );
                $varProdDecoData['product_decoration']['bound_price'] = $getProductDecorationSetting['custom_bound_price'];
                $varProdDecoData['product_decoration']['is_border_enable'] = $getProductDecorationSetting['is_border_enable'];
                $varProdDecoData['product_decoration']['is_sides_allow'] = $getProductDecorationSetting['is_sides_allow'];
                $varProdDecoData['product_decoration']['no_of_sides'] = $getProductDecorationSetting['no_of_sides'];
                $varProdDecoData['product_decoration']['print_area_id'] = $getProductDecorationSetting['print_area_id'];

                if (!empty($getProductDecorationSetting['pre_defined_dimensions'])
                    && $getProductDecorationSetting['pre_defined_dimensions'] != ""
                ) {
                    $varProdDecoData['product_decoration']['is_pre_defined']
                    = !empty($getProductDecorationSetting['pre_defined_dimensions']) ? 1 : 0;
                    $varProdDecoData['product_decoration']['pre_defined_dimensions']
                    = json_clean_decode(
                        $getProductDecorationSetting['pre_defined_dimensions'],
                        true
                    );
                } elseif (!empty($getProductDecorationSetting['user_defined_dimensions'])
                    && $getProductDecorationSetting['user_defined_dimensions'] != ""
                ) {
                    $varProdDecoData['product_decoration']['is_pre_defined']
                    = !empty($getProductDecorationSetting['user_defined_dimensions']) ? 0 : 1;
                    $varProdDecoData['product_decoration']['user_defined_dimensions']
                    = json_clean_decode(
                        $getProductDecorationSetting['user_defined_dimensions'],
                        true
                    );
                }

                $productDecorationSettingData += $varProdDecoData;
            }

            // Processing Print Profiles
            if (!empty($getFinalArray['print_profiles'])
                && count($getFinalArray['print_profiles']) > 0
            ) {
                foreach ($getFinalArray['print_profiles'] as $printProfile) {
                    if (!empty($printProfile['profile']['xe_id'])
                        && $printProfile['profile']['xe_id'] > 0
                        && !empty($printProfile['profile']['name'])
                    ) {
                        $productDecorationSettingData['print_profiles'][] = [
                            'id' => $printProfile['profile']['xe_id'],
                            'name' => $printProfile['profile']['name'],
                        ];
                    }
                }
            }

            $jsonResponse = [
                'status' => 1,
                'data' => $productDecorationSettingData,
            ];

        } else {
            $productDecorationSettingData['is_decoration_exists'] = 1;
            $productDecorationSettingData['scale_unit_id'] = 1;
            $sides = [];
            if (!empty($getStoreProduct['products']['images'])) {
                $printProfile = [];
                $printProfileInit = new PrintProfileModels\PrintProfile();
                $getPrintProfileInfo = $printProfileInit->where(
                    [
                        'is_disabled' => 0,
                        'store_id' => $getStoreDetails['store_id']
                    ]
                )->first();
                // Check if print profile exist in this ID

                $productImage = $getStoreProduct['products']['images'];
                $imageSide = $productStoreImageSide = count($productImage);
                $dimension = '{"x":206,"y":212,"width":175.35,"height":124.05,"type":"rect","path":"","rotate":false,"cx":0,"cy":0,"cw":0,"ch":0,"sx":0,"sy":0,"sw":0,"sh":0}';
                $locations = '{"x_location":0,"y_location":0}';
                //Get print area id
                $defaultPrintArea = DB::table('print_areas')->where([
                    'name' => 'A4',
                    'store_id' => $getStoreDetails['store_id']
                ]);
                $defaultPrintAreaArr = $defaultPrintArea->get(); 
                $defaultPrintAreaArr = json_clean_decode($defaultPrintAreaArr, true);
                $print_area_id = $defaultPrintAreaArr[0]['xe_id'];;
                $decoration_settings = [];
                if ($imageSide > 0) {
                    $printProfile = [];
                    for ($j = 1; $j <= $imageSide; $j++) {
                        if (!empty($getPrintProfileInfo->xe_id)) {
                            $printProfile[] = [
                                'xe_id' => $getPrintProfileInfo->xe_id,
                                'name' => $getPrintProfileInfo->name,
                            ];
                        }
                    }
                    for ($i = 1; $i <= $imageSide; $i++) {
                        $sides[$i]["xe_id"] = $i;
                        $sides[$i]["side_name"] = "Side " . $i;
                        $sides[$i]["side_index"] = null;
                        $sides[$i]["dimension"] = "";
                        $sides[$i]["locations"] = null;
                        $sides[$i]["is_visible"] = 1;
                        $sides[$i]["crop_value"] = null;
                        $sides[$i]["safe_value"] = null;
                        $sides[$i]["image"]["id"] = 1;
                        $sides[$i]["image"]["src"] = $productImage[$i - 1]['src'];
                        $sides[$i]["image"]["thumbnail"] = $productImage[$i - 1]['thumbnail'];

                        $sides[$i]["product_decoration_setting"][] = [
                            'xe_id' => 1,
                            'name' => "Front",
                            "dimension" => $dimension,
                            "locations" => $locations,
                            "sub_print_area_type" => "normal_size",
                            "min_height" => null,
                            "max_height" => null,
                            "min_width" => null,
                            "max_width" => null,
                            "is_border_enable" => 0,
                            "is_sides_allow" => 0,
                            "print_area_id" => $print_area_id,
                            "product_size_variant_decoration_settings" => [],
                            "print_profile_decoration_settings" => array(array("print_profile" => $printProfile)),
                        ];
                    }
                }
            }

            $printProfiles = [];
            if (!empty($getPrintProfileInfo->xe_id)) {
                $printProfiles[] = [
                    'id' => $getPrintProfileInfo->xe_id,
                    'name' => $getPrintProfileInfo->name,
                ];
            }
            $productDecorationSettingData['print_profiles'] = $printProfiles;
            $productDecorationSettingData['configurator_image'] = [];
            $productDecorationSettingData['sides'] = array_values($sides);
            $jsonResponse = [
                'status' => 1,
                'data' => $productDecorationSettingData,
            ];
        }

        if ($returnType == 1) {
            return $jsonResponse;
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}