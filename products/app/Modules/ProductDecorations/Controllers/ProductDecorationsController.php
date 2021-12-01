<?php 
    /**
     *
     * Manage Products from Various Stores
     *
     * @category   Products
     * @package    Decoration Settings
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     tanmayap@riaxe.com
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @1.0
     */
    namespace App\Modules\ProductDecorations\Controllers;

    use StoreSpace\Controllers\StoreProductsController;
    use App\Modules\ProductDecorations\Models\ProductSetting;
    use App\Modules\ProductDecorations\Models\PrintProfileProductSetting;
    use App\Modules\ProductDecorations\Models\ProductSide;
    use App\Modules\ProductDecorations\Models\ProductDecorationSetting;
    use App\Modules\ProductDecorations\Models\ProductImageSettingsRel;
    use App\Modules\ProductDecorations\Models\PrintProfileDecorationSettingRel;
    use App\Modules\ProductDecorations\Models\PrintProfileProductSettingRel;
    use Illuminate\Database\Capsule\Manager as DB;
    use App\Modules\ProductImageSides\Models\ProductImage;
    use App\Modules\ProductImageSides\Models\ProductImageSides;
    // For Http Request and Response
    use GuzzleHttp\Client;

    class ProductDecorationsController extends StoreProductsController
    {

        /**
         * Save Product Decoration Settings
         *
         * @author     tanmayap@riaxe.com
         * @date       9 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function saveProductDecorations($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            $jsonResponse = [];
            $allPostPutVars = $request->getParsedBody();
            $getProductDecorationData = json_decode($allPostPutVars['decorations'], true);

            // Processing for Table: product_settings
            if(
                isset($getProductDecorationData['product_id']) && $getProductDecorationData['product_id'] > 0
            ) {
                //$productDecoration = $getProductDecorationData['product_settings'];
                $productDecorationDataSet = [
                    'product_id' => $getProductDecorationData['product_id'], 
                    'is_crop_mark' => $getProductDecorationData['is_crop_mark'], 
                    'is_safe_zone' => $getProductDecorationData['is_safe_zone'], 
                    'crop_value' => $getProductDecorationData['crop_value'], 
                    'safe_value' => $getProductDecorationData['safe_value'], 
                    'is_3d_preview' => $getProductDecorationData['is_3d_preview'], 
                    'scale_unit_id' => $getProductDecorationData['scale_unit_id']
                ];

                $productSetting = new ProductSetting($productDecorationDataSet);
                $productSetting->save();
                $productSettingInsertId = $productSetting->xe_id;
            }

            // Processing for Table: product_image_settings_rel
            if(
                isset($productSettingInsertId) && $productSettingInsertId > 0 && 
                isset($getProductDecorationData['product_image_id']) && $getProductDecorationData['product_image_id'] > 0 
            ) {
                $productImageSettings = new ProductImageSettingsRel([ //product_image_settings_rel PrintProfileProductSetting
                    'product_setting_id' => $productSettingInsertId,
                    'product_image_id' => $getProductDecorationData['product_image_id']
                ]);
                $productImageSettings->save();
                $productImageSettingsInsertId = $productImageSettings->xe_id;
            }

            // Processing for Table: product_sides, product_decoration_settings
            if(
                isset($productSettingInsertId) && $productSettingInsertId > 0 && 
                isset($getProductDecorationData['sides']) && count($getProductDecorationData['sides']) > 0
            ) {
                $imageSides = $getProductDecorationData['sides'];
                foreach ($imageSides as $psKey => $productSideData) {
                    $productSide = new ProductSide([
                        'product_setting_id' => $productSettingInsertId,
                        'side_name' => $productSideData['name'],
                        'product_image_dimension' => $productSideData['image_dimension'],
                        'is_visible' => $productSideData['is_visible'],
                        'product_image_side_id' => $productSideData['product_image_side_id']
                    ]);
                    $productSide->save();
                    $productSideInsertId = $productSide->xe_id;
                    $productDecorationSettingDataSet = [];
                    foreach ($productSideData['product_decoration'] as $pdsKey => $productDecoSetting) {
                        $productDecorationSettingDataSet[$pdsKey] = $productDecoSetting;
                        $productDecorationSettingDataSet[$pdsKey]['product_side_id'] = $productSideInsertId;
                        $productDecorationSettingDataSet[$pdsKey]['dimension'] = json_encode($productDecoSetting['dimension'], true);
                        $productDecorationSettingDataSet[$pdsKey]['product_setting_id'] = $productSettingInsertId;

                        $productDecorationSettingInit = new ProductDecorationSetting($productDecorationSettingDataSet[$pdsKey]);
                        $productDecorationSettingInit->save();
                        $productDecorationSettingInsertId = $productDecorationSettingInit->xe_id;

                        // Processing for Table: print_profile_decoration_setting_rel 
                        if(
                            isset($productDecorationSettingInsertId) && $productDecorationSettingInsertId > 0 && 
                            isset($productDecoSetting['print_profile_ids']) && count($productDecoSetting['print_profile_ids']) > 0
                        ) {
                            $printProfiles = $productDecoSetting['print_profile_ids'];
                            $printProfileDecorationSettingRelDataSet = [];
                            foreach ($printProfiles as $ppKey => $printProfile) {
                                $printProfileDecorationSettingRelDataSet[$ppKey]['print_profile_id'] = $printProfile;
                                $printProfileDecorationSettingRelDataSet[$ppKey]['decoration_setting_id'] = $productDecorationSettingInsertId;
                            }
                            PrintProfileDecorationSettingRel::insert($printProfileDecorationSettingRelDataSet);
                        }

                    }
                }
            }

            // Processing for Table: print_profile_product_setting_rel 
            if(
                isset($productSettingInsertId) && $productSettingInsertId > 0 && 
                isset($getProductDecorationData['print_profile_ids']) && count($getProductDecorationData['print_profile_ids']) > 0
            ) {
                $printProfiles = $getProductDecorationData['print_profile_ids'];
                $printProfileProductSettingRelDataSet = [];
                foreach ($printProfiles as $ppKey => $printProfile) {
                    $printProfileProductSettingRelDataSet[$ppKey]['print_profile_id'] = $printProfile;
                    $printProfileProductSettingRelDataSet[$ppKey]['product_setting_id'] = $productSettingInsertId;
                }
                PrintProfileProductSettingRel::insert($printProfileProductSettingRelDataSet);
            }

            if(isset($productSettingInsertId) && $productSettingInsertId > 0) {
                $jsonResponse = [
                    'status' => 1,
                    'product_settings_insert_id' => $productSettingInsertId,
                    'message' => message('Product Decoration Setting', 'saved'),
                ];
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Product Decoration Setting', 'error'),
                ];
            }
        
            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Update Product Decoration Settings
         *
         * @author     tanmayap@riaxe.com
         * @date       9 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function updateProductDecorations($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            $jsonResponse = [];
            $allPostPutVars = $this->parsePut();
            $productUpdateId = $args['product_id'];
            $getProductDecorationData = json_decode($allPostPutVars['decorations'], true);

            // Processing for Table: product_settings
            if(
                isset($productUpdateId) && $productUpdateId > 0
            ) {
                $productDecorationDataSet = [
                    'is_crop_mark' => $getProductDecorationData['is_crop_mark'], 
                    'is_safe_zone' => $getProductDecorationData['is_safe_zone'], 
                    'crop_value' => $getProductDecorationData['crop_value'], 
                    'safe_value' => $getProductDecorationData['safe_value'], 
                    'is_3d_preview' => $getProductDecorationData['is_3d_preview'], 
                    'scale_unit_id' => $getProductDecorationData['scale_unit_id']
                ];
                $productSettingsUpdateInit = ProductSetting::where(['product_id' => $productUpdateId]);
                $productSettingsUpdateInit->update($productDecorationDataSet);
                
                $productSettingsGet = $productSettingsUpdateInit->first();
                $productSettingInsertId = $productSettingsGet->xe_id;
            }

            // Processing for Table: product_image_settings_rel
            if(
                isset($productSettingInsertId) && $productSettingInsertId > 0 && 
                isset($getProductDecorationData['product_image_id']) && $getProductDecorationData['product_image_id'] > 0 
            ) {
                $productImageSettingInit = ProductImageSettingsRel::where(['product_setting_id' => $productSettingInsertId]);
                if($productImageSettingInit->count() > 0) {
                    // Update record
                    $productImageSettingInit->update(['product_image_id' => $getProductDecorationData['product_image_id']]);
                    $productImageSettingsInsertId = '';
                } else {
                    // Save record 
                    $productImageSettings = new ProductImageSettingsRel([ //product_image_settings_rel PrintProfileProductSetting
                        'product_setting_id' => $productSettingInsertId,
                        'product_image_id' => $getProductDecorationData['product_image_id']
                    ]);
                    $productImageSettings->save();
                    $productImageSettingsInsertId = $productImageSettings->xe_id;
                }
            }


            // Processing for Table: product_sides, product_decoration_settings
            if(
                isset($productSettingInsertId) && $productSettingInsertId > 0 && 
                isset($getProductDecorationData['sides']) && count($getProductDecorationData['sides']) > 0
            ) {
                // Clearing the old records
                $productSideDelete = ProductSide::where(['product_setting_id' => $productSettingInsertId])->delete();
                
                $imageSides = $getProductDecorationData['sides'];
                foreach ($imageSides as $psKey => $productSideData) {
                    $productSide = new ProductSide([
                        'product_setting_id' => $productSettingInsertId,
                        'side_name' => $productSideData['name'],
                        'product_image_dimension' => $productSideData['image_dimension'],
                        'is_visible' => $productSideData['is_visible']
                    ]);
                    $productSide->save();
                    $productSideInsertId = $productSide->xe_id;
                    $productDecorationSettingDataSet = [];
                    foreach ($productSideData['product_decoration'] as $pdsKey => $productDecoSetting) {
                        $productDecorationSettingDataSet[$pdsKey] = $productDecoSetting;
                        $productDecorationSettingDataSet[$pdsKey]['product_side_id'] = $productSideInsertId;
                        $productDecorationSettingDataSet[$pdsKey]['product_setting_id'] = $productSettingInsertId;

                        $productDecorationSettingInit = new ProductDecorationSetting($productDecorationSettingDataSet[$pdsKey]);
                        $productDecorationSettingInit->save();
                        $productDecorationSettingInsertId = $productDecorationSettingInit->xe_id;

                        // Processing for Table: print_profile_decoration_setting_rel 
                        if(
                            isset($productDecorationSettingInsertId) && $productDecorationSettingInsertId > 0 && 
                            isset($productDecoSetting['print_profile_ids']) && count($productDecoSetting['print_profile_ids']) > 0
                        ) {
                            $printProfiles = $productDecoSetting['print_profile_ids'];
                            $printProfileDecorationSettingRelDataSet = [];
                            foreach ($printProfiles as $ppKey => $printProfile) {
                                $printProfileDecorationSettingRelDataSet[$ppKey]['print_profile_id'] = $printProfile;
                                $printProfileDecorationSettingRelDataSet[$ppKey]['decoration_setting_id'] = $productDecorationSettingInsertId;
                            }
                            PrintProfileDecorationSettingRel::insert($printProfileDecorationSettingRelDataSet);
                        }

                    }
                }
            }

            // Processing for Table: print_profile_product_setting_rel 
            if(
                isset($productSettingInsertId) && $productSettingInsertId > 0 && 
                isset($getProductDecorationData['print_profile_ids']) && count($getProductDecorationData['print_profile_ids']) > 0
            ) {
                // Clearing the old records
                $printProfileProductSettingDelete = PrintProfileProductSettingRel::where(['product_setting_id' => $productSettingInsertId])->delete();

                $printProfiles = $getProductDecorationData['print_profile_ids'];
                $printProfileProductSettingRelDataSet = [];
                foreach ($printProfiles as $ppKey => $printProfile) {
                    $printProfileProductSettingRelDataSet[$ppKey]['print_profile_id'] = $printProfile;
                    $printProfileProductSettingRelDataSet[$ppKey]['product_setting_id'] = $productSettingInsertId;
                }
                PrintProfileProductSettingRel::insert($printProfileProductSettingRelDataSet);
            }

            if(isset($productSettingInsertId) && $productSettingInsertId > 0) {
                $jsonResponse = [
                    'status' => 1,
                    'product_settings_insert_id' => $productSettingInsertId,
                    'message' => message('Product Decoration Setting', 'updated'),
                ];
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Product Decoration Setting', 'error'),
                ];
            }
        
            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
           
        }

        /**
         * Get Product Decoration Settings
         *
         * @author     tanmayap@riaxe.com
         * @date       9 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function getProductDecorations($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            $jsonResponse = [];
            $allPostPutVars = $request->getParsedBody();
            $productDecorationSettingData = [];
            /**
             * Getting all images sides
             */
            
            $productSideImages = [];
            
            /**
             * Calling Relational Tables by their function names from Model
             */
            $getSettingsAssociatedRecords = ProductSetting::with(
                'sides', 'sides.product_decoration_setting',
                'sides.product_decoration_setting.print_profile_decoration_settings', 
                'sides.product_decoration_setting.print_profile_decoration_settings.print_profile',
                'print_profiles', 'print_profiles.profile' // Print Profile Product Setting Relations Alias name
            )->where('product_id', $args['product_id']);

            

            // Check if any record(s) exist
            if($getSettingsAssociatedRecords->count() > 0) {
                $getFinalArray = $getSettingsAssociatedRecords->orderBy('xe_id', 'desc')->first();
                /**
                 * (#001) Get Product Image Sides from Respective Stores
                 * If the store has no product images then, create a blank array with exception error
                 */
                $productApiUri = BASE_URL . 'products/' . $getFinalArray->product_id;
                $client = new Client();
                try {
                    $productAPIresponse = $client->request('GET', $productApiUri);
                    $clientResponseCode = $productAPIresponse->getStatusCode();
                    $responseBody = $productAPIresponse->getBody();
                    $getProductDetailsJson = $responseBody->getContents();
                    $getProductDetails = json_decode($getProductDetailsJson, true);
                    $productSideImages = $getProductDetails['data'][0]['images'];
                } catch (\Exception $e) {
                    // If no Image found or if there is no product with the given product ID
                    $productSideImages = [
                        'id' => 0,
                        'message' => 'Sorry! It seems that, Store has no relevant product images for this Product',
                        'exception' => $e->getMessage()
                    ];
                }
                // Ends 

                // Append Product Name to the final array from the Store API
                if(isset($getProductDetails['data'][0]['name']) && $getProductDetails['data'][0]['name'] != "") { 
                    $getFinalArray['product_name'] = $getProductDetails['data'][0]['name'];
                }
                
                // Build Product Settings Array
                $productDecorationSettingData = [
                    'id' => $getFinalArray['xe_id'],
                    'product_id' => $getFinalArray['product_id'],
                    'product_name' => $getFinalArray['product_name'],
                    'is_crop_mark' => $getFinalArray['is_crop_mark'],
                    'is_safe_zone' => $getFinalArray['is_safe_zone'],
                    'crop_value' => $getFinalArray['crop_value'],
                    'safe_value' => $getFinalArray['safe_value'],
                    'is_3d_preview' => $getFinalArray['is_3d_preview'],
                    'scale_unit_id' => $getFinalArray['scale_unit_id']
                ];

                $productDecorationSides = [];
                foreach ($getFinalArray['sides'] as $sideKey => $side) {
                    // Build Product Side Array
                    $productDecorationSides['id'] = $side['xe_id'];
                    $productDecorationSides['name'] = $side['side_name'];
                    $productDecorationSides['index'] = $side['side_index'];
                    $productDecorationSides['dimension'] = $side['dimension'];
                    $productDecorationSides['is_visible'] = $side['is_visible'];

                    // Get side image accordoing to the side index of the side array
                    $getProductImageSideInit = ProductImageSides::where('xe_id', $side['side_index']);
                    if($getProductImageSideInit->count() == 0 ) {
                        // Get Product Image Sides from Respective Stores
                        $productDecorationSides['is_decoration'] = 0;
                        /**
                         * In the section #001, we got all images from Store end. There may be multiple images. Each image belongs to one side
                         * Programatically, we get each side by the foreach loop key's index
                         */
                        $productDecorationSides['image'] = $productSideImages[$sideKey];
                    } else {
                        //  Get Product Image Sides from DB
                        $getProductImageSideData = $getProductImageSideInit->first();
                        $productDecorationSides['is_decoration'] = 1;
                        $productDecorationSides['image'] = [
                            'id' => $getProductImageSideData->xe_id,
                            'src' => $getProductImageSideData->file_name,
                            'thumbnail' => $getProductImageSideData->file_name
                        ];
                    }
                    // End 

                    // Loop through, Product Decoration Settings
                    $productDecorationSides['decoration_settings'] = [];
                    if(isset($side['product_decoration_setting']) && count($side['product_decoration_setting']) > 0) {
                        foreach ($side['product_decoration_setting'] as $decorationSettingLoop => $decorationSetting) {
                            $productDecorationSides['decoration_settings'][$decorationSettingLoop] = [
                                'name' => $decorationSetting['name'],
                                'dimension' => $decorationSetting['dimension'],
                                'sub_print_area_type' => $decorationSetting['sub_print_area_type'],
                                'min_height' => $decorationSetting['min_height'],
                                'max_height' => $decorationSetting['max_height'],
                                'min_width' => $decorationSetting['min_width'],
                                'max_width' => $decorationSetting['max_width'],
                                'bound_price' => $decorationSetting['bound_price'],
                                'is_border_enable' => $decorationSetting['is_border_enable'],
                                'is_sides_allow' => $decorationSetting['is_sides_allow'],
                                'no_of_sides' => $decorationSetting['no_of_sides'],
                                'is_dimension_enable' => $decorationSetting['is_dimension_enable']
                            ];
                        }
                    }

                    // Loop through, Print Profile Decoration Setting
                    if(isset($decorationSetting['print_profile_decoration_settings']) && count($decorationSetting['print_profile_decoration_settings']) > 0) {
                        foreach ($decorationSetting['print_profile_decoration_settings'] as $printProfileDecorationSettingKey => $printProfileDecorationSetting) {
                            $productDecorationSides['decoration_settings'][$decorationSettingLoop]['print_profiles'][] = [
                                'id' => $printProfileDecorationSetting['print_profile'][0]['xe_id'],
                                'name' => $printProfileDecorationSetting['print_profile'][0]['name']
                            ];
                        }
                    }

                    $productDecorationSettingData['sides'][$sideKey] = $productDecorationSides;
                }

                // Processing Print Profiles 
                $productDecorationSettingData['print_profiles'] = [];
                if(isset($getFinalArray['print_profiles']) && count($getFinalArray['print_profiles']) > 0) {
                    foreach ($getFinalArray['print_profiles'] as $printProfileKey => $printProfile) {
                        $productDecorationSettingData['print_profiles'][$printProfileKey] = [
                            'id' => $printProfile['profile']['xe_id'],
                            'name' => $printProfile['profile']['name']
                        ];
                    }
                }

                $jsonResponse = [
                    'status' => 1,
                    'data' => $productDecorationSettingData,
                ];
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Product Decoration Setting', 'not_found'),
                ];
            }
            
            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }
    }
    