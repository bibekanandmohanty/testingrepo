<?php
/**
 * Manage Products from catalog Store
 *
 * PHP version 5.6
 *
 * @category  Products
 * @package   Store
 * @author    Radhanatha <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Controllers;

// use Illuminate\Database\Capsule\Manager as DB;
use App\Modules\Products\Models\CatalogProductRel;
use App\Modules\Products\Models\PrintProfileDecorationSettingRel;
use App\Modules\Products\Models\PrintProfileProductSettingRel;
use App\Modules\Products\Models\ProductDecorationSetting;
use App\Modules\Products\Models\ProductImageSettingsRel;
use App\Modules\Products\Models\ProductSetting;
use App\Modules\Products\Models\ProductSide;
use App\Modules\Products\Models\ProductImage;
use App\Modules\Products\Models\ProductImageSides;
use ProductStoreSpace\Controllers\StoreProductsController;
use App\Modules\DecorationAreas\Models\PrintArea;

/**
 * Products catalog Controller
 *
 * @category Class
 * @package  Product
 * @author   Radhanatha <radhanatham@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductsCatalogController extends StoreProductsController
{

    /**
     * GET: Get all catalog products from catalog
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function getProducts($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => "No products found",
        ];
        $page = $request->getQueryParam('page');
        $limit = $request->getQueryParam('limit');
        $sortBy = (!empty($request->getQueryParam('sortby'))
            && $request->getQueryParam('sortby') != null
        ) ? $request->getQueryParam('sortby') : 'id';
        $order = (!empty($request->getQueryParam('order'))
            && $request->getQueryParam('order') != null
        ) ? $request->getQueryParam('order') : 'desc';
        $name = $request->getQueryParam('name');
        $brand_name = $request->getQueryParam('brand_name');
        $sku_code = $request->getQueryParam('sku_code');
        $perpage = $request->getQueryParam('perpage');
        $categoryId = $request->getQueryParam('category');
        $brandId = $request->getQueryParam('brand');
        $catalog_code = $request->getQueryParam('catalog_code');
        if ($request->getQueryParam('catalog_code')) {
            $params = array('name' => $name, "catalog_code" => $catalog_code, "category" => $categoryId, "brand" => $brandId, "sortby" => $sortBy, "order" => $order, "page" => $page, "perpage" => $perpage);
            $productList = api_call_by_curl($params, 'products');
            $totalRecords = $productList['total_records'];
            $records = $productList['records'];
            $productArr = array();
            $totalExitProductArr = [];
            if (!empty($productList['data'])) {
                $data = $productList['data'];
                $i = 0;
                foreach ($data as $k => $product) {
                    $productArr[$i]['id'] = $product['id'];
                    $productArr[$i]['style_id'] = $product['style_id'];
                    $productArr[$i]['part_number'] = $product['part_number'];
                    $productArr[$i]['description'] = $product['description'];
                    $productArr[$i]['title'] = $product['title'];
                    $productArr[$i]['brand_name'] = $product['brand_name'];
                    $productArr[$i]['base_category'] = $product['base_category'];
                    $productArr[$i]['categories'] = $product['categories'];
                    $productArr[$i]['style_image'] = $product['style_image'];
                    $productArr[$i]['brand_image'] = $product['brand_image'];
                    $productArr[$i]['price'] = $product['price'];
                    $existingProductID = $this->checkProductExist($product['style_id']);
                    $productArr[$i]['existingProductID'] = $existingProductID;
                    $productArr[$i]['is_imported'] = $existingProductID > 0 ? true : false;
                    $i++;
                }
                if (!empty($productArr)) {
                    $jsonResponse = [
                        'status' => 1,
                        'records' => $records,
                        'total_records' => $totalRecords,
                        'data' => array_values($productArr),
                    ];
                }
            }
        }
        return response($response, ['data' => $jsonResponse, 'status' => $serverStatusCode]);
    }

    /**
     * GET: Check catalog product already exit or not
     *
     * @param $styleId  Product stylid
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return boolean
     */
    private function checkProductExist($styleId)
    {
        $existingProductID = 0;
        $catlogProductRelSvInit = new CatalogProductRel();
        $catalogProduct = $catlogProductRelSvInit->select('product_id');
        $catalogProduct->where('catalog_product_id', trim($styleId));
        if ($catalogProduct->count() > 0) {
            $existingProductID = $catalogProduct->first()->product_id;
        }
        return $existingProductID;
    }

    /**
     * POST: Product add to store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function importProucts($request, $response, $args)
    {
        ignore_user_abort(true);
        ini_set('memory_limit', '1024M');
        $jsonResponse = [
            'status' => 0,
            'records' => 0,
            'data' => [],
            'message' => message('Products', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        $predecoDetails = $request->getParsedBody();
        $productData = json_clean_decode($predecoDetails['product_data']);
        $serverStatusCode = OPERATION_OKAY;
        foreach ($productData as $key => $product) {
            if ($product['old_product_id'] == 0) {
               $productData[$key]['old_product_id'] = $this->checkProductExist($product['style_id']);
            }
        }

        // Call Internal Store
        $predecoSaveResp = $this->addProductToStore($productData, $predecoDetails['catalog_code'], $predecoDetails , $getStoreDetails['store_id']);
        $cloneStatus['status'] = 0;
        if (!empty($predecoSaveResp)) {
            $productCount = count($predecoSaveResp);
            $getProdDecoInfo = [];
            foreach ($predecoSaveResp as $k => $v) {
                if ($predecoDetails['catalog_code'] == 'in') {
                    $getProdDecoInfo = $this->productPrintAreaFromIN($request, $v);
                }elseif (empty($v['old_product_id'])){
                    $getProdDecoInfo['product_id'] = $v['product_id'];
                    $getProdDecoInfo['is_crop_mark'] = 0;
                    $getProdDecoInfo['is_safe_zone'] = 0;
                    $getProdDecoInfo['is_ruler'] = 0;
                    $getProdDecoInfo['crop_value'] = 0;
                    $getProdDecoInfo['safe_value'] = 0;
                    $getProdDecoInfo['is_3d_preview'] = 0;
                    $getProdDecoInfo['3d_object'] = '';
                    $getProdDecoInfo['3d_object_file_upload'] = '';
                    $getProdDecoInfo['scale_unit_id'] = 1;
                    $getProdDecoInfo['product_image_id'] = 0;
                    $getProdDecoInfo['print_profile_ids'] = array("1", "2");
                    $getProdDecoInfo['is_variable_decoration'] = 0;
                    $sideArr = [];
                    foreach ($v['product_side'] as $key => $side) {
                        $sideArr[$key]['name'] = $side;
                        $sideArr[$key]['is_visible'] = 1;
                        $sideArr[$key]['image_dimension'] = '';
                        $sideArr[$key]['product_image_side_id'] = 0;
                        $sideArr[$key]['product_decoration'] = array(
                            array("name" => "A4", "print_area_id" => 4,
                                "dimension" => array(
                                    "x" => 213, "y" => 221, "width" => 175.35, "height" => 124.05, "type" => "rect", "path" => "", "rotate" => false, "cx" => 0, "cy" => 0, "cw" => 0, "ch" => 0, "sx" => 0, "sy" => 0, "sw" => 0, "sh" => 0,
                                ),
                                "sub_printarea_type" => "normal_size", "size_variants" => array(), "print_profile_ids" => array("1", "2"),
                            ),
                        );
                    }
                    $getProdDecoInfo['sides'] = $sideArr;
                }

                // Save Product Decorations
                $prodSettInsId = $this->saveCatalogProductSetting(
                    $getProdDecoInfo,
                    $getStoreDetails['store_id']
                );

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

                if (!empty($v['product_id'])) {
                    $catalogProduct['product_id'] = $v['product_id'];
                    $catalogProduct['catalog_product_id'] = $v['style_id'];
                    $catlogProductRelSvInit = new CatalogProductRel();
                    $catlogProductRelSvInit->where(['catalog_product_id' => $v['style_id']])->delete();
                    $catlogProductRelSvInit->insert($catalogProduct);
                }

            }

            $jsonResponse = [
                'status' => 1,
                'message' => message('Catalog Product', 'saved'),
            ];
            $msg = $productCount . ' nos of products imported successfully';
        } else {
            $msg = 'Imported failed. Please try again';
        }
        $this->mailSendToAdmin($msg);
        return response(
            $response,
            [
                'data' => $jsonResponse, 'status' => $serverStatusCode,
            ]
        );
    }

    protected function productPrintAreaFromIN($request, $newProduct){
        $thisDecorData = $newProduct['decorData'];
        if (!empty($thisDecorData['product_image_id']) && $thisDecorData['product_image_id'] > 0) {
            $prodIMGTemplate = api_call_by_curl('call To IM server', IMPRINT_CATALOG_API_URL.'image-sides/'.$thisDecorData['product_image_id']);
            $newProdImgID = $this->saveImprintProductImage($request, $prodIMGTemplate['data'][0]);
        }
        $getProdDecoInfo['product_id'] = $newProduct['product_id'];
        $getProdDecoInfo['is_crop_mark'] = $thisDecorData['crop_value'] > 0 ? 1:0;
        $getProdDecoInfo['is_safe_zone'] = $thisDecorData['is_safe_zone'];
        $getProdDecoInfo['is_ruler'] = $thisDecorData['is_ruler'];
        $getProdDecoInfo['crop_value'] = $thisDecorData['crop_value'];
        $getProdDecoInfo['safe_value'] = $thisDecorData['safe_value'];
        $getProdDecoInfo['is_3d_preview'] = $thisDecorData['is_3d_preview'];
        $getProdDecoInfo['3d_object'] = $thisDecorData['3d_object'];
        $getProdDecoInfo['3d_object_file_upload'] = $thisDecorData['3d_object_file_upload'];
        $getProdDecoInfo['scale_unit_id'] = !empty($thisDecorData['scale_unit_id'])? $thisDecorData['scale_unit_id']: 0;
        $getProdDecoInfo['product_image_id'] = $newProdImgID; 
        $getProdDecoInfo['print_profile_ids'] = array_column($thisDecorData['print_profiles'], 'id');
        $getProdDecoInfo['is_variable_decoration'] = $thisDecorData['is_variable_decoration'];
        $sideArr = [];
        foreach ($thisDecorData['sides'] as $key => $side) {
            $sideArr[$key]['name'] = $side['name'];
            $sideArr[$key]['is_visible'] = $side['is_visible'];
            $sideArr[$key]['image_dimension'] = '';
            $sideArr[$key]['product_image_side_id'] =$side['image']['id'] > 0 ? $side['image']['id']:0;
            foreach ($side['decoration_settings'] as $boundKey => $boundary) {
                $thisDimension = json_decode( $boundary['dimension'], true);
                $boundaryInfo = api_call_by_curl('call To IM server', IMPRINT_CATALOG_API_URL.'print-areas?id='.$boundary['print_area_id']);
                $newBoundaryID = $this->saveImprintProductBoundary($request, $boundaryInfo['data'][0]);
                $sideArr[$key]['product_decoration'][] = array("name" => $boundary['name'], "print_area_id" => $newBoundaryID,//work is there
                    "dimension" => array(
                        "x" => $thisDimension['x'], "y" => $thisDimension['y'], "width" => $thisDimension['width'], "height" => $thisDimension['height'], "type" => $thisDimension['type'], "path" => $thisDimension['path'], "rotate" => $thisDimension['rotate'], "cx" => $thisDimension['cx'], "cy" => $thisDimension['cy'], "cw" => $thisDimension['cw'], "ch" => $thisDimension['ch'], "sx" => $thisDimension['sx'], "sy" => $thisDimension['sy'], "sw" => $thisDimension['sw'], "sh" => $thisDimension['sh'],
                    ),
                    "sub_printarea_type" => "normal_size", "size_variants" => array(), "print_profile_ids" => $getProdDecoInfo['print_profile_ids'],
                );
            }
        }
        $getProdDecoInfo['sides'] = $sideArr;
        return $getProdDecoInfo;

    }

    private function saveImprintProductImage($request, $imgData){
        $store = get_store_details($request);
        if (!empty($imgData)) {
           $productImageData = [
                'name' => $imgData['name'],
            ];
            $productImageData['store_id'] = $store['store_id'];
            $saveProductImage = new ProductImage($productImageData);
            $saveProductImage->save();
            $productImageInsertId = $saveProductImage->xe_id;

            foreach ($imgData['sides'] as $sideData) {
                $fileNameArr = explode(".", $sideData['raw_file_name'], 2);
                $imageUploadIndex = $fileNameArr[0];
                $thisImageContent = file_get_contents($sideData['file_name']);
                $createNewImg = file_put_contents(path('abs', 'product').$sideData['raw_file_name'], $thisImageContent);
                $createNewImg = file_put_contents(path('abs', 'product')."thumb_".$sideData['raw_file_name'], $thisImageContent);
                $productImageSides = [
                    'product_image_id' => $productImageInsertId,
                    'side_name' => !empty($sideData['side_name'])
                    ? $sideData['side_name'] : null,
                    'sort_order' => $sideData['sort_order'],
                ];
                if (!empty($sideData['raw_file_name'])) {
                    $productImageSides['file_name'] = $sideData['raw_file_name'];
                }
                $saveProductImageSide = new ProductImageSides(
                    $productImageSides
                );
                $saveProductImageSide->save();
            }
            return $productImageInsertId;
        }
    }

    private function saveImprintProductBoundary($request, $boundaryInfo){
        $getStoreId = get_store_details($request);
        if ($boundaryInfo['is_user_defined'] === 0) {
            return $boundaryInfo['xe_id'];
        }else{
            if (!empty($boundaryInfo)) {
                $random = rand();
                $targetFile = path('abs', 'print_area').$random.".svg";
                if (!empty($boundaryInfo['file_name'])) {
                    $thissvgContent = file_get_contents($boundaryInfo['file_name']);
                    $createNewsvg = file_put_contents($targetFile, $thissvgContent);
                }
                $newBoundary = array(
                    'store_id' => $getStoreId['store_id'],
                    'name' => $boundaryInfo['name'],
                    'print_area_type_id' => $boundaryInfo['print_area_type_id'],
                    'width' => $boundaryInfo['width'],
                    'height' => $boundaryInfo['height'],
                    'file_name' => $random.".svg",
                    'is_user_defined' => 1,
                );
                $printAreaInit = new PrintArea($newBoundary);
                if ($printAreaInit->save()) {
                    return $printAreaInit->xe_id;
                }
            }
        }
    }

    /**
     * Post: save product setting
     *
     * @param $getProdDecoInfo Decoration data
     * @param $storeId         Store id
     *
     * @author radhanatham@riaxe.com
     * @date   05 June 2020
     * @return integer
     */
    protected function saveCatalogProductSetting($getProdDecoInfo, $storeId)
    {

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
        $this->saveCatalogDecorationSides($getProdDecoInfo, $productSettingId);
        return $productSettingId;
    }

    /**
     * Post: save decoration sides
     *
     * @param $decoration Decoration data
     * @param $settingsId Settings save id
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return boolean
     */
    public function saveCatalogDecorationSides($decoration, $settingsId)
    {
        // Processing for Table: product_sides, product_decoration_settings
        $imageSides = $decoration['sides'];
        if (!empty($settingsId) && !empty($imageSides)) {
            foreach ($imageSides as $productSideData) {
                $productSide = new ProductSide(
                    [
                        'product_setting_id' => $settingsId,
                        'side_name' => $productSideData['name'],
                        'product_image_dimension' => $productSideData['image_dimension'],
                        'is_visible' => $productSideData['is_visible'],
                        'product_image_side_id' => $productSideData['product_image_side_id'],
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
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * GET: mail send to admin
     *
     * @param $msg Total product
     *
     * @author radhanatham@riaxe.com
     * @date  25 Aug 2020
     * @return boolean
     */
    private function mailSendToAdmin($msg)
    {
        $setting = call_curl(
            [], 'settings', 'GET'
        );
        $printShopEmail = $setting['general_settings']['email_address_details']['to_email'];
        $subject = 'Catalog Product Import';
        $fromEmail = $setting['general_settings']['email_address_details']['from_email'];
        $smtpData = $setting['general_settings']['smtp_details'];
        $emailFormat = [
            'from' => [
                'email' => $fromEmail,
                'name' => $fromEmail,
            ],
            'recipients' => [
                'to' => [
                    'email' => $printShopEmail,
                    'name' => $printShopEmail,
                ],
                'reply_to' => [
                    'email' => '',
                    'name' => '',
                ],
                'cc' => [
                    'email' => '',
                    'name' => '',
                ],
                'bcc' => [
                    'email' => '',
                    'name' => '',
                ],
            ],
            'attachments' => ['', ''],
            'subject' => $subject,
            'body' => '<html>
                    <body>
                    <table width="400" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td>
                    <div align="center">' . $msg . '.</div>
                    </td>
                    </tr>
                    </table>
                    </body>
                    </html>',
            'smptData' => $smtpData,
        ];
        if ($smtpData['smtp_host'] != ''
            && $smtpData['smtp_user'] != ''
            && $smtpData['smtp_pass'] != ''
        ) {
            $mailResponse = email($emailFormat);
        }
    }

    /**
     * Create name and number CSV file
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return json
     */
    public function createProductCsvSample($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Product Import CSV Sample', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $predecoDetails = $request->getParsedBody();
        $productData = json_clean_decode($predecoDetails['product_data']);
        if (!empty($productData)) {
            $csvFilename = $this->createProductImportCSV($request, $response, $args);
            $jsonResponse = [
                'status' => 1,
                'data' => $csvFilename,
                'message' => message('Catalog CSV Sample', 'saved'),
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Download name and number csv sample file
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author radhanatham@riaxe.com
     * @date   05 June 2020
     * @return json
     */
    public function downloadProductCsvSample($request, $response, $args)
    {
        $fileName = $request->getQueryParam('filename') ? $request->getQueryParam('filename') : '';
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Catalog Sample', 'error'),
        ];

        $assetsPath = path('abs', 'assets');
        $catalogAssetsPath = $assetsPath . 'catalog';
        if ($fileName != '') {
            $downloadFilepath = $catalogAssetsPath . '/' . $fileName;
            if (file_exists($downloadFilepath)) {
                file_download($downloadFilepath);
                $status = 1;
                $message = 'Catalog Sample File Downloaded Successful';
            } else {
                $status = 0;
                $message = 'Error In Catalog Sample File Download';
            }
            $jsonResponse = [
                'status' => $status,
                'message' => $message,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Get all catalogs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function getAllCatalog($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => "No catalog found",
        ];
        $params = array();
        $catalogList = api_call_by_curl($params, '');
        return response($response, ['data' => $catalogList, 'status' => $serverStatusCode]);
    }
    /**
     * GET: Get all catalogs category
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function getCatalogCategory($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => "No catalog category found",
        ];
        $catalog_code = $request->getQueryParam('catalog_code');
        $params = array("catalog_code" => $catalog_code);
        $catalogCategoryList = api_call_by_curl($params, 'categories');
        return response($response, ['data' => $catalogCategoryList, 'status' => $serverStatusCode]);
    }
    /**
     * GET: Get all catalogs brands
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function getCatalogBrand($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => "No catalog brand found",
        ];
        $catalog_code = $request->getQueryParam('catalog_code');
        $params = array("catalog_code" => $catalog_code);
        $catalogBrandList = api_call_by_curl($params, 'brand');
        return response($response, ['data' => $catalogBrandList, 'status' => $serverStatusCode]);
    }

    /**
     * GET: Get product details
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function getProductDetails($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => "No catalog product details found",
        ];
        $catalog_code = $request->getQueryParam('catalog_code');
        $style_id = $request->getQueryParam('style_id');
        $params = array("catalog_code" => $catalog_code, 'style_id' => $style_id);
        $productDetails = api_call_by_curl($params, 'product');
        if (empty($productDetails['data']['color_data']) || empty($productDetails['data']['size_data'])) {
            foreach ($productDetails['data']['attributes'] as $attribute) {
                if (strtolower($attribute['name']) == "color" || strtolower($attribute['name']) == "colour") {
                    $productDetails['data']['color_data'] = $attribute['options'];
                }
                if (strtolower($attribute['name']) == "size") {
                    $productDetails['data']['size_data'] = $attribute['options'];
                }
            }
        }
        return response($response, ['data' => $productDetails, 'status' => $serverStatusCode]);
    }

    /**
     * GET: Get status of the import product
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date  09 Oct 2020
     * @return array json
     */
    public function getImportProductStatus($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $catProductId = "";
        $jsonResponse = [
            'status' => 1,
            'message' => "Processing",
        ];
        if (isset($args['id'])) {
            $catProductId = $args['id'];
            $status = $this->checkProductExist($catProductId);
            if ($status == 0) {
                $importStatus = "Processing";
            } else {
                $importStatus = "Completed";
            }
            $jsonResponse = [
                'status' => 1,
                'message' => $importStatus,
            ];
        }
        return response(
            $response, [
                'data' => $jsonResponse, 'status' => $serverStatusCode,
            ]
        );
    }
}