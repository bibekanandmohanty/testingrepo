<?php
/**
 * Manage Templates and Designs
 *
 * PHP version 5.6
 *
 * @category  Template
 * @package   Template_Design
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Templates\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Components\Models\DesignStates;
use App\Modules\PrintProfiles\Models\PrintProfile;
use App\Modules\Templates\Models as TemplateModel;
use App\Modules\Templates\Models\TemplateCategoryRel;
use App\Modules\Settings\Models\Setting;

/**
 * Template Controller
 *
 * @category Class
 * @package  Template_Design
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TemplateController extends ParentController
{
    /**
     * Post: Save Design State along with Templates
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Save status in json format
     */
    public function saveDesigns($request, $response)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $allPostPutVars = $request->getParsedBody();
        // Initilize json Response
        $jsonResponse = [
            'status' => 0,
            'message' => message('Design Data', 'error'),
        ];
        $productArray = json_clean_decode($allPostPutVars['product_info']);
        $designState = [
            'store_id' => $getStoreDetails['store_id'],
            'product_variant_id' => !empty($allPostPutVars['product_variant_id'])
            ? $allPostPutVars['product_variant_id'] : 0,
            'product_id' => !empty($productArray['product_id'])
            ? $productArray['product_id'] : null,
            'type' => !empty($allPostPutVars['template_type'])
            ? $allPostPutVars['template_type'] : "template",
            'custom_price' => !empty($allPostPutVars['custome_price'])
            ? to_decimal($allPostPutVars['custome_price']) : 0.00,
            'selected_category_id' => !empty($allPostPutVars['selected_category_id'])
            ? $allPostPutVars['selected_category_id'] : "",
        ];

        $designData = "";
        if (!empty($allPostPutVars['design_data'])) {
            $designData = $allPostPutVars['design_data'];
        }
        // Save design data and svg json format
        $reffId = $this->saveDesignData($designState, "", ['directory' => 'templates']);
        if ($reffId > 0) {
            // Save Template Data with its dedicated function
            $templateSaveResponse = $this->saveTemplates(
                $request, $response, $reffId, 'save'
            );

            $captureImage = $this->saveDesignImages(
                $request, $response, ['ref_id' => $reffId, 'svg_data' => $designData]
            );

            if (!empty($templateSaveResponse) && $templateSaveResponse > 0) {
                // Save Print Profile Relations
                $this->savePrintProfileRelations(
                    $request, $response, $reffId, $templateSaveResponse, 'save'
                );
                // Save Template Tag Relations
                $templateTagRels = [
                    'reff_id' => $reffId,
                    'template_id' => $templateSaveResponse,
                    'store_id' => $getStoreDetails['store_id'],
                ];
                $this->saveTemplateTags(
                    $getStoreDetails['store_id'],
                    $templateSaveResponse,
                    $allPostPutVars['tags']
                );
                // Save Template Categories
                $this->_SaveTemplateCategories(
                    $request, $response, $templateTagRels, 'save'
                );
            }

            $getAssocCaptures = $this->getCaptureImages($reffId);
            $jsonResponse = [
                'status' => 1,
                'ref_id' => $reffId,
                'template_id' => $templateSaveResponse,
                'capture_images' => $getAssocCaptures,
                'product_id' => $designState['product_id'],
                'message' => message('Design Template', 'saved'),
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Post: Save Design Images
     * It consists of images with and with-out product images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   19 Feb 2020
     * @return boolean
     */
    protected function saveDesignImages($request, $response, $args)
    {
        $reffId = $args['ref_id'];
        $svgData = $args['svg_data'];
        $allPostPutVars = $request->getParsedBody();
        $sideDetails = json_clean_decode($allPostPutVars['sides']);
        $primeLocationW = path('abs', 'design_preview') . 'templates';
        $primeLocationR = path('read', 'design_preview') . 'templates';
        $primeJsonLocW = path('abs', 'design_state') . 'templates';
        // Create direcotry if not exist
        if (!file_exists($primeJsonLocW)) {
            create_directory($primeJsonLocW);
        }
        $listOfFiles = [];

        $tempDirByRefIdSave = path('abs', 'template') . 'REF_ID_' . $reffId;
        $tempDirByRefIdGet = path('read', 'template') . 'REF_ID_' . $reffId . '/';
        $excludedFileNames = do_upload('design_without_product', $primeLocationW, [200], 'array');
        $includedFileNames = do_upload('design_urls', $primeLocationW, [200], 'array');

        if (!empty($svgData)) {
            $listOfFiles['design_data'] = $svgData;
        }
        if (!empty($excludedFileNames)) {
            foreach ($excludedFileNames as $exfileNameKey => $exfileName) {
                $listOfFiles['without_product_file'][] = [
                    'filename' => $exfileName,
                ];
            }
        }
        // if (!empty($includedFileNames)) {
        //     foreach ($includedFileNames as $incFileNameKey => $incFileName) {
        //         $listOfFiles['with_product_file'][] = [
        //             'filename' => $incFileName,
        //         ];
        //     }
        // }
        // if (isset($args['update_type']) && $args['update_type'] === 'update') {
        //     if (!empty($allPostPutVars['design_urls'])) {
        //         foreach ($allPostPutVars['design_urls'] as $urlKey => $url) {
        //             $listOfFiles['with_product_file'][] = [
        //                 'filename' => $url,
        //             ];
        //         }
        //     }
        // }
        $designUrl = [];
        $designIndex = 0;
        $retainIndex = 0;
        foreach ($sideDetails as $sideKey => $sideData) {
            if ($sideData['is_designed'] == 1 && $sideData['is_design_retain'] == 0) {
                $designUrl[] = $primeLocationR . '/' . $includedFileNames[$designIndex];
                $listOfFiles['with_product_file'][] = ['filename' => $includedFileNames[$designIndex]];
                $designIndex++;
            } elseif ($sideData['is_designed'] == 1 && $sideData['is_design_retain'] == 1) {
                $designUrl[] = $primeLocationR . '/' . $allPostPutVars['design_urls'][$retainIndex];
                $listOfFiles['with_product_file'][] = ['filename' => $allPostPutVars['design_urls'][$retainIndex]];
                $retainIndex++;
            } else {
                $designUrl[] = $sideData['url'];
            }
        }
        $designProductData = [
            [
                'variant_id' => [
                    $allPostPutVars['product_variant_id']
                ],
                'design_urls' => $designUrl
            ]
        ];
        $designData = [
            'notes' => "",
            'product_info' => json_clean_decode($allPostPutVars['product_info']),
            'design_product_data' => $designProductData,
            'sides' => json_clean_decode($allPostPutVars['sides']),
            'env_info' => json_clean_decode($allPostPutVars['env_info']),
            'face_data' => isset($allPostPutVars['face_data']) ? json_clean_decode($allPostPutVars['face_data']) : "",
            'layer_data' => isset($allPostPutVars['layer_data']) ?  json_clean_decode($allPostPutVars['layer_data']) : "", 
            'other_file_details' => $listOfFiles
        ];

        if (is_array($designData)) {
            $designData = json_clean_encode($designData);
            // Save the file's sequences into a json file
            return write_file($primeJsonLocW . '/' . $reffId . '.json', $designData);
        }

        return false;
    }
    /**
     * Put: Update Design State along with Templates
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Save status in json format
     */
    public function updateDesigns($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $designId = 0;
        $allPutVars = $request->getParsedBody();
        $jsonResponse = [
            'status' => 0,
            'message' => message('Design Template', 'error'),
        ];
        if (!empty($args['id']) && $args['id'] > 0) {
            $designId = to_int($args['id']);
            $designState = [
                'product_setting_id' => $allPutVars['product_settings_id'],
                'product_variant_id' => $allPutVars['product_variant_id'],
                'type' => $allPutVars['template_type'],
                'selected_category_id' => $allPutVars['selected_category_id'],
            ];
            $templateDesignInit = new DesignStates();
            $designInit = $templateDesignInit->where('xe_id', $designId);
            if ($designInit->count() > 0) {
                try {
                    $designInit->update($designState);
                    // Save Template Data with its dedicated function
                    $templateSaveResponse = $this->saveTemplates(
                        $request, $response, $designId, 'update'
                    );
                    if (!empty($templateSaveResponse) && $templateSaveResponse > 0) {
                        // Save Print Profile Relations
                        $this->savePrintProfileRelations(
                            $request, $response, $designId,
                            $templateSaveResponse, 'update'
                        );
                        // Save Template Tag Relations
                        $templateTagRels = [
                            'reff_id' => $designId,
                            'template_id' => $templateSaveResponse,
                            'store_id' => $getStoreDetails['store_id'],
                        ];
                        $this->saveTemplateTags(
                            $getStoreDetails['store_id'],
                            $templateSaveResponse,
                            $allPutVars['tags']
                        );
                        // Save Template Categories
                        $this->_SaveTemplateCategories(
                            $request, $response, $templateTagRels, 'update'
                        );
                    }

                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Design Template', 'updated'),
                    ];
                } catch (\Exception $e) {
                    $serverStatusCode = EXCEPTION_OCCURED;
                    create_log(
                        'template', 'error',
                        [
                            'message' => $e->getMessage(),
                            'extra' => [
                                'module' => 'Update Design State',
                            ],
                        ]
                    );
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Get single or all templates
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json Response
     */
    public function getTemplates($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 1,
            'total_records' => 0,
            'records' => 0,
            'data' => [],
            'message' => message('Template', 'not_found'),
        ];
        $offset = 0;
        $single = false;
        $templateInit = new TemplateModel\Template();
        $getTemplates = $templateInit->where('xe_id', '>', 0);
        // Make conditional Select Id
        $designId = to_int($args['id']);
        if (!empty($designId)) {
            $single = true;
            $getTemplates->whereHas(
                'getDesignState', function ($q) use ($designId) {
                    return $q->where('xe_id', $designId);
                }
            );
        } else {
            // Collect all Filter columns from url
            $page = $request->getQueryParam('page');
            $colorUsed = $request->getQueryParam('color_used'); // number of colors
            $perpage = $request->getQueryParam('perpage');
            $sortBy = $request->getQueryParam('sortby');
            $catagoryId = $request->getQueryParam('catagory');
            $tagId = $request->getQueryParam('tag');
            $printProfileId = $request->getQueryParam('print_profile');
            $order = $request->getQueryParam('order');
            $name = $request->getQueryParam('name');
            $printProfileKey = $request->getQueryParam('print_profile_id');
            $type = $request->getQueryParam('type');
            $productCategoryID = $request->getQueryParam('prod_cat_id');

            // For multiple Shape data
            $getTemplates->select(
                'xe_id as id', 'xe_id', 'name', 'ref_id',
                'store_id', 'no_of_colors', 'color_hash_codes', 'is_easy_edit'
            );
            // Filter Search as per type
            if (isset($type) && $type != "") {
                $getTemplates->where(
                    function ($query) use ($type) {
                        $query->where('is_easy_edit', $type);
                    }
                );
            }
        }

        $totalCounts = $getTemplates->count();
        if ($totalCounts > 0) {
            if (!empty($getStoreDetails)) {
                $getTemplates->where($getStoreDetails);
            }
            // Multiple Table search for name attribute
            if (!empty($name)) {
                $getTemplates->where('name', 'LIKE', '%' . $name . '%')
                    ->orWhereHas(
                        'templateTags.tag', function ($q) use ($name) {
                            return $q->where('name', 'LIKE', '%' . $name . '%');
                        }
                    )
                    ->orWhereHas(
                        'templateCategory.category', function ($q) use ($name) {
                            return $q->where('name', 'LIKE', '%' . $name . '%');
                        }
                    );
            }

            // Color used Filter
            if (!empty($colorUsed)) {
                $getTemplates->where('no_of_colors', $colorUsed);
            }
            // Filter By Print Profile Id
            if (!empty($printProfileKey)) {
                $profileCatRelObj = new \App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel();
                $assetTypeArr = $this->assetsTypeId('templates');
                $profileCatRelDetails = $profileCatRelObj->where(
                    [
                        'asset_type_id' => $assetTypeArr['asset_type_id'],
                    ]
                )
                    ->where('print_profile_id', $printProfileKey)
                    ->get();

                $relCatIds = [];
                foreach ($profileCatRelDetails->toArray() as $value) {
                    array_push($relCatIds, $value['category_id']);
                }
                $catagoryId = json_encode($relCatIds);
            }
            // this block will get the templates assigned for selected profuct categories.
            if (!empty($productCategoryID)) {
                $productCatArr = explode(',', $productCategoryID);
                $settingInit = new Setting();
                $getSettings = $settingInit->where('setting_key', '=', 'template_products_rel');
                $categoryRelations = $getSettings->get()->toArray();
                $relationSetting = json_clean_decode($categoryRelations[0]['setting_value']);
                if (!empty($relationSetting['categories'])) {
                    $relationRows = $relationSetting['categories'];
                    $templateCatArr = array();
                    foreach ($relationRows as $row => $relation) {
                       if (count(array_intersect($productCatArr, $relation['prodCatId'])) > 0) {
                           $templateCatArr = array_merge($templateCatArr, $relation['templateCats']);
                        } 
                    }
                }
                $templateCatsToFilter = array_unique($templateCatArr);
                $catagoryId = json_encode($templateCatsToFilter);
            }
            // Filter by Category IDs
            if (!empty($catagoryId)) {
                $searchCategories = json_clean_decode($catagoryId, true);
                if (!empty($searchCategories)) {
                    $getTemplates->whereHas(
                        'templateCategory', function ($q) use ($searchCategories) {
                            return $q->whereIn('category_id', $searchCategories);
                        }
                    );
                }
            }
            // Filter by Tag IDs
            if (!empty($tagId)) {
                $searchTags = json_clean_decode($tagId, true);
                if (!empty($searchTags)) {
                    $getTemplates->whereHas(
                        'templateTags', function ($q) use ($searchTags) {
                            return $q->whereIn('tag_id', $searchTags);
                        }
                    );
                }
            }
            // Filter by Print Profile
            if (!empty($printProfileId)) {
                $searchPrintProfiles = json_clean_decode($printProfileId, true);
                if (!empty($searchPrintProfiles)) {
                    $getTemplates->whereHas(
                        'templatePrintProfiles', function ($q) use ($searchPrintProfiles) {
                            return $q->whereIn(
                                'print_profile_id', $searchPrintProfiles
                            );
                        }
                    );
                }
            }

            // Total records including all filters
            $getTotalPerFilters = $getTemplates->count();

            // Get pagination data
            if (!empty($page)) {
                $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                $offset = $totalItem * ($page - 1);
                $getTemplates->skip($offset)->take($totalItem);
            }
            // Sorting by column name and sord order parameter
            if (!empty($sortBy) && !empty($order)) {
                $getTemplates->orderBy($sortBy, $order);
            }
            $templateData = $templateList = $getTemplates->orderBy('xe_id', 'DESC')
                ->with('getDesignState')
                ->get();
            foreach ($templateList as $templateKey => $template) {
                $templateId = $template->xe_id;
                $referenceId = $template->ref_id;
                $type = 'templates';
                $urlPath = "";
                if (file_exists(path('abs', 'design_state') . $type . '/' . $referenceId . '.json')) {
                    $urlPath = path('read', 'design_state') . $type . '/' . $referenceId . '.json';
                }
                // Create template_id from xe_id
                $templateData[$templateKey]['template_id'] = $templateId;
                unset($templateData[$templateKey]['xe_id']);
                if (!empty($templateId)) {
                    // Get Associated Tags and Categories
                    $getTagCategories = $this->getAssocCategoryTag($templateId);
                    $templateData[$templateKey]['categories'] = $getTagCategories['categories'];
                    $templateData[$templateKey]['tags'] = $getTagCategories['tags'];
                    // Get Associated Print profiles
                    $templateData[$templateKey]['print_profiles']
                    = $this->templateToPrintProfile($templateId);
                    // Get Associated product Details
                    $assocProductId = $this->getAssocProductId($referenceId, $single);
                    $templateData[$templateKey]['product_id'] = $assocProductId['conditional_products_id'];
                    if ($single) {
                        $templateData[$templateKey]['product_name'] = $assocProductId['product_name'];
                        $templateData[$templateKey]['variant_id'] = $assocProductId['product_variant_id'];
                    }
                    // Get template Images
                    $getAssocCaptures = $this->getCaptureImages($referenceId);
                    $templateData[$templateKey]['capture_images'] = $getAssocCaptures;
                    // Get svg design data
                    if ($single) {
                        $svgCode = $this->getSVGFile($referenceId, $type);
                        $templateData[$templateKey]['design_data'] = $svgCode;
                    }
                }
            }
            if ($getTotalPerFilters > 0) {
                $jsonResponse = [
                    'status' => 1,
                    'total_records' => $getTotalPerFilters,
                    'records' => count($templateData),
                    'data' => $templateData,
                    'design_url' => $urlPath,
                ];
            }
        } else {
            $templateDesignStInit = new DesignStates();
            $designStateData = $templateDesignStInit->where('xe_id', $designId)
                ->first();
            if (!empty($designStateData)) {
                $designStateData = $designStateData->toArray();
                $urlPath = '';
                $type = '';
                $designData = [];
                // Get the json type
                if ($designStateData['type'] == 'cart') {
                    $type = 'carts';
                } elseif ($designStateData['type'] == 'predecorator') {
                    $type = 'predecorators';
                } elseif ($designStateData['type'] == 'quote') {
                    $type = 'quotes';
                } elseif ($designStateData['type'] == 'share') {
                    $type = 'shares';
                } elseif ($designStateData['type'] == 'user_design') {
                    $type = 'user_designs';
                } elseif ($designStateData['type'] == 'artwork') {
                    $type = 'artworks';
                }
                // Url Path
                $urlPath = "";
                if (file_exists(path('abs', 'design_state') . $type . '/' . $designId . '.json')) {
                    $urlPath = path('read', 'design_state') . $type . '/' . $designId . '.json';
                }
                // Get Associated product Details
                $assocProductId = $this->getAssocProductId($designId, $single);
                $designData[0]['product_id'] = $assocProductId['conditional_products_id'];
                if ($single) {
                    $designData[0]['product_name'] = $assocProductId['product_name'];
                    $designData[0]['variant_id'] = $assocProductId['product_variant_id'];
                }
                if ($single) {
                    $svgCode = $this->getSVGFile($designId, $type);
                    $designData[0]['design_data'] = $svgCode;
                }
                $jsonResponse = [
                    'status' => 1,
                    'total_records' => 1,
                    'data' => $designData,
                    'design_url' => $urlPath,
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Get SVG Files json data associated to the Template's Ref ID
     *
     * @param $refId Custom Design Id
     * @param $type  Type of the design
     *
     * @author tanmayap@riaxe.com
     * @date   19 Feb 2020
     * @return Array
     */
    public function getSVGFile($refId, $type)
    {
        $svgDataArray = [];
        $designData = [];
        $svgJsonPath = path('abs', 'design_state') . $type;
        $svgJsonPath .= '/' . $refId . '.json';
        if (file_exists($svgJsonPath)) {
            $svgData = read_file($svgJsonPath);
            if (!empty($svgData)) {
                $svgDataArray = json_clean_decode($svgData, true);
                if (!empty($svgDataArray['sides'])) {
                    foreach ($svgDataArray['sides'] as $svgKey => $svg) {
                        $designData[$svgKey] = [
                            'svg' => $svg['svg'],
                        ];
                    }
                } elseif (!empty($svgDataArray['design_data'])) {
                    return json_clean_decode($svgDataArray['design_data']);
                }
                return $designData;
            }
        }
        return false;
    }

    /**
     * Get Associated Product Primary Key from the Template's RefId
     *
     * @param $refId        Design Ref Id
     * @param $storeProduct Flag
     *
     * @author tanmayap@riaxe.com
     * @date   14 aug 2019
     * @return integer
     */
    protected function getAssocProductId($refId, $storeProduct = false)
    {
        // Get Product Id : Conditional Case applied for Inkxe 8
        $productDetails = [];
        $prodSettsProdId = 0;
        $getDesignStateData = DesignStates::with(
            'productSetting'
        )
            ->find($refId);
        if (isset($getDesignStateData)
            && count($getDesignStateData->toArray()) > 0
        ) {
            $designState = $getDesignStateData->toArray();
            if (isset($designState['product_id'])
                && $designState['product_id'] > 0
            ) {
                $prodSettsProdId = $designState['product_id'];
            } elseif (isset($designState['product_setting']['product_id'])
                && $designState['product_setting']['product_id'] > 0
            ) {
                $prodSettsProdId = to_int(
                    $designState['product_setting']['product_id']
                );
            }
        }
        $productDetails['conditional_products_id'] = $prodSettsProdId;
        if ($storeProduct) {
            $endPoint = 'products/' . $prodSettsProdId;
            $singleProductApi = call_curl([], $endPoint, 'GET');
            if (!empty($singleProductApi) && $singleProductApi['status'] == 1) {
                $productDetails += [
                    'product_id' => $singleProductApi['data']['id'],
                    'product_name' => $singleProductApi['data']['name'],
                    'product_variant_id' => $singleProductApi['data']['variant_id'],
                ];
            }
        }
        return $productDetails;
    }
    /**
     * Get Associated print Profile Lists from the Template ID
     *
     * @param $templateId Slim's Request object
     *
     * @author tanmayap@riaxe.com
     * @date   14 aug 2019
     * @return json response
     */
    protected function templateToPrintProfile($templateId)
    {
        $tempProfileRelObj = new TemplateModel\TemplatePrintProfileRel();
        $getPrintProfiles = $tempProfileRelObj->where('template_id', $templateId)
            ->select('print_profile_id')
            ->get();
        $getPrintProfileInit = new PrintProfile();
        $getMasterPrintprofs = $getPrintProfileInit->get();
        $printProfileIdList = [];
        foreach ($getPrintProfiles as $printProfile) {
            $printProfileIdList[] = $printProfile['print_profile_id'];
        }
        // Selected Print profile. Set as Blank array if no data exist
        $printProfileSelected = [];
        // Loop through all print profile and add is_selected key
        // where key matches
        foreach ($getMasterPrintprofs as $printprofileMaster) {
            if (in_array($printprofileMaster->xe_id, $printProfileIdList)) {
                $printProfileSelected[] = [
                    'id' => $printprofileMaster->xe_id,
                    'name' => $printprofileMaster->name,
                    'is_selected' => 1,
                ];
            }
        }
        return $printProfileSelected;
    }
    /**
     * Get Associated Categories and tags of Template by ID
     *
     * @param $templateId Slim's Request object
     *
     * @author tanmayap@riaxe.com
     * @date   14 aug 2019
     * @return Array
     */
    protected function getAssocCategoryTag($templateId)
    {
        // Get Category Ids
        $tempCatRelObj = new TemplateModel\TemplateCategoryRel();
        $getCategory = $tempCatRelObj->where('template_id', $templateId)
            ->select('category_id')
            ->with('category')
            ->get();
        $categoryIdList = [];
        foreach ($getCategory as $category) {
            $categoryIdList[] = $category['category_id'];
        }
        // Get Tag names
        $tempTagRelObj = new TemplateModel\TemplateTagRel();
        $getTags = $tempTagRelObj->where('template_id', $templateId)
            ->select('tag_id')
            ->with('tag')
            ->get();
        $tagNameList = [];
        foreach ($getTags as $tag) {
            $tagNameList[] = $tag['tag']['name'];
        }

        return [
            'categories' => $categoryIdList,
            'tags' => $tagNameList,
        ];
    }
    /**
     * Get Captured Images associated to the Template's Ref ID
     *
     * @param $refId Design ID
     * @param $isRaw Flag
     *
     * @author tanmayap@riaxe.com
     * @date   14 aug 2019
     * @return Array
     */
    private function getCaptureImages($refId, $isRaw = false)
    {
        $getCaptures = [];
        $withOutProductFile = [];
        $withProductFile = [];
        $fileLocationDir = path('read', 'design_preview') . 'templates';
        $absDirectoryName = path('abs', 'design_state') . 'templates';
        $finalSortedFileList = [];
        if (file_exists($absDirectoryName . '/' . $refId . '.json')) {
            $getImageFileHistory = read_file(
                $absDirectoryName . '/' . $refId . '.json'
            );
            // Get Capture Records as json format
            $finalSortedFileList = json_clean_decode($getImageFileHistory, true);
            if (!empty($isRaw)) {
                return $finalSortedFileList;
            }
            if (!empty($finalSortedFileList['other_file_details']['without_product_file'])) {
                foreach ($finalSortedFileList['other_file_details']['without_product_file'] as $woFileKey => $woFile) {
                    $withOutProductFile[] = [
                        'src' => $fileLocationDir . '/' . $woFile['filename'],
                        'thumb' => $fileLocationDir . '/' . 'thumb_' . $woFile['filename'],
                    ];
                }
                $getCaptures['without_product_file'] = $withOutProductFile;
            } elseif (isset($finalSortedFileList['without_product_file']) && !empty($finalSortedFileList['without_product_file'])) {
                foreach ($finalSortedFileList['without_product_file'] as $woFileKey => $woFile) {
                    $withOutProductFile[] = [
                        'src' => $fileLocationDir . '/' . $woFile['filename'],
                        'thumb' => $fileLocationDir . '/' . 'thumb_' . $woFile['filename'],
                    ];
                }
                $getCaptures['without_product_file'] = $withOutProductFile;
            }
            if (!empty($finalSortedFileList['other_file_details']['with_product_file'])) {
                foreach ($finalSortedFileList['other_file_details']['with_product_file'] as $woFileKey => $woFile) {
                    $withProductFile[] = [
                        'src' => $fileLocationDir . '/' . $woFile['filename'],
                        'thumb' => $fileLocationDir . '/' . 'thumb_' . $woFile['filename'],
                    ];
                }
                $getCaptures['with_product_file'] = $withProductFile;
            } elseif (isset($finalSortedFileList['with_product_file']) && !empty($finalSortedFileList['with_product_file'])) {
                foreach ($finalSortedFileList['with_product_file'] as $woFileKey => $woFile) {
                    $withOutProductFile[] = [
                        'src' => $fileLocationDir . '/' . $woFile['filename'],
                        'thumb' => $fileLocationDir . '/' . 'thumb_' . $woFile['filename'],
                    ];
                }
                $getCaptures['with_product_file'] = $withOutProductFile;
            }
        }

        return $getCaptures;
    }
    /**
     * Delete: Delete a clipart along with all the tags and categories
     * - Input must be in a valid JSON format like [1,2,3,...] where 1,2,3.. are
     *   clipart IDs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   14 aug 2019
     * @return json response
     */
    public function deleteTemplate($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Template', 'error'),
        ];
        if (isset($args) && count($args) > 0 && $args['id'] != '') {
            $getTemplateId = json_clean_decode($args['id'], true);

            $templateInit = new TemplateModel\Template();
            $getTemplates = $templateInit->whereIn('xe_id', $getTemplateId);
            if ($getTemplates->count() > 0) {
                $templateAssocRefIds = $getTemplates->whereIn(
                    'xe_id', $getTemplateId
                )
                    ->select('ref_id')
                    ->get();
                $refIdList = [];
                foreach ($templateAssocRefIds as $refId) {
                    $refIdList[] = $refId->ref_id;
                }
            }
            if (!empty($refIdList) && count($refIdList) > 0) {
                // Delete Record
                $templateDesignStInit = new DesignStates();
                if ($templateDesignStInit->whereIn('xe_id', $refIdList)->delete()) {
                    // Delete Template Data
                    $templateInit = new TemplateModel\Template();
                    $templateInit->whereIn('xe_id', $getTemplateId)
                        ->delete();
                    // Delete Template_Tag_Rel Data
                    $templateTagInit = new TemplateModel\TemplateTagRel();
                    $templateTagInit->whereIn('template_id', $getTemplateId)
                        ->delete();
                    // Delete Template_PP_Rel Data
                    $templateProfileInit = new TemplateModel\TemplatePrintProfileRel();
                    $templateProfileInit->whereIn('template_id', $getTemplateId)
                        ->delete();
                    // Delete Template_Cat_Rel Data
                    $templateCatInit = new TemplateModel\TemplateCategoryRel();
                    $templateCatInit->whereIn('template_id', $getTemplateId)
                        ->delete();
                    // Delete Capture_Img Data
                    $templateImgInit = new TemplateModel\TemplateCaptureImages();
                    $templateImgInit->whereIn('ref_id', $refIdList)
                        ->delete();
                    // Delete Design Data Files
                    $this->deleteTemplateStuffs($refIdList);
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Template', 'deleted'),
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Delete Files used by Design Data and Templates by reading the json file
     *
     * @param $refIdList Array of Design Ids
     *
     * @author tanmayap@riaxe.com
     * @date   20 Feb 2020
     * @return boolean
     */
    public function deleteTemplateStuffs($refIdList)
    {
        $jsonLocW = path('abs', 'design_state') . 'templates';
        $primeLocationR = path('abs', 'design_preview') . 'templates';
        $deteleStatus = 0;
        if (!empty($refIdList)) {
            foreach ($refIdList as $key => $designId) {
                $getJsonFile = $this->getCaptureImages($designId, true);
                $jsonFileLoc = $jsonLocW . '/' . $designId . '.json';
                if (!empty($getJsonFile['without_product_file'])) {
                    foreach ($getJsonFile['without_product_file'] as $woFile) {
                        $fileFullLoc = $primeLocationR . '/' . $woFile['filename'];
                        $fileFullLocThumb = $primeLocationR . '/' . 'thumb_' . $woFile['filename'];
                        if (file_exists($fileFullLoc)) {
                            delete_directory($fileFullLoc);
                            $deteleStatus++;
                        }
                        if (file_exists($fileFullLocThumb)) {
                            delete_directory($fileFullLocThumb);
                            $deteleStatus++;
                        }
                    }
                }

                if (!empty($getJsonFile['with_product_file'])) {
                    foreach ($getJsonFile['with_product_file'] as $wFile) {
                        $fileFullLoc = $primeLocationR . '/' . $wFile['filename'];
                        $fileFullLocThumb = $primeLocationR . '/' . 'thumb_' . $wFile['filename'];
                        if (file_exists($fileFullLoc)) {
                            delete_directory($fileFullLoc);
                            $deteleStatus++;
                        }
                        if (file_exists($fileFullLocThumb)) {
                            delete_directory($fileFullLocThumb);
                            $deteleStatus++;
                        }
                    }
                }
                // After Deleting all Binary image files, Delete the json file
                if (file_exists($jsonFileLoc)) {
                    delete_directory($jsonFileLoc);
                }
            }
            // Manupulate the response
            if ($deteleStatus > 0) {
                return true;
            }
        }

        return false;
    }
    /**
     * Save Template Tags into Tags table and Template-tag Relational table
     *
     * @param $storeId    Store ID
     * @param $templateId Template ID
     * @param $tags       Tags in json array format
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Boolean
     */
    protected function saveTemplateTags($storeId, $templateId, $tags)
    {
        // Save Clipart and tags relation
        if (!empty($tags)) {
            $tagToArray = json_clean_decode($tags, true);
            $tagToString = implode(',', $tagToArray);
            $getTagIds = $this->saveTags($storeId, $tagToString);
            // SYNC Tags into Clipart Tag Relationship Table
            $templateInit = new TemplateModel\Template();
            $findTemplate = $templateInit->find($templateId);
            if ($findTemplate->tags()->sync($getTagIds)) {
                return true;
            }
        } else {
            // Clean relation in case no tags supplied
            $templateTagRelInit = new TemplateModel\TemplateTagRel();
            $templateTags = $templateTagRelInit->where('template_id', $templateId);
            if ($templateTags->delete()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Save Template Categories into Categories table and Template-category
     * Relational table
     *
     * @param $request    Slim's Request object
     * @param $response   Slim's Response object
     * @param $parameters Slim's Argument parameters
     * @param $saveType   Flag for Save or Update
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Boolean
     */
    private function _SaveTemplateCategories($request, $response, $parameters, $saveType = 'save')
    {
        //$getPostData = $request->getParsedBody();
        $getPostData = $request->getParsedBody();
        $templateId = $parameters['template_id'];
        $categories = $getPostData['categories'];

        $categoryListArray = json_clean_decode($categories, true);
        // Clean-up old records before updating the table
        if (!empty($saveType) && $saveType == 'update') {
            TemplateModel\TemplateCategoryRel::where('template_id', $templateId)
                ->delete();
        }
        // SYNC Categories to the Clipart_Category Relationship Table
        $templateInit = new TemplateModel\Template();
        $findTemplate = $templateInit->find($templateId);
        if ($findTemplate->categories()->sync($categoryListArray)) {
            return true;
        }
        return false;
    }

    /**
     * Save method for Template data
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $reffId   REF ID from Design State Table
     * @param $saveType Flag for Save or Update
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Boolean
     */
    private function saveTemplates($request, $response, $reffId, $saveType = 'save')
    {
        $getStoreDetails = get_store_details($request);
        $getPostData = $request->getParsedBody();

        $templateData = [
            'ref_id' => $reffId,
            'store_id' => $getStoreDetails['store_id'],
            'name' => $getPostData['name'],
            'description' => isset($getPostData['description'])
            ? $getPostData['description'] : null,
            'no_of_colors' => $getPostData['no_of_colors'],
            'color_hash_codes' => $getPostData['used_colors'],
            'template_index' => $getPostData['template_index'],
            'is_easy_edit' => !empty($getPostData['is_easy_edit']) ? $getPostData['is_easy_edit'] : 0,
        ];
        if ($saveType == 'update') {
            unset($templateData['ref_id']);
            // Create a new object instance for fetch
            $templateInit = new TemplateModel\Template();
            $updateTemplateInit = $templateInit->where('ref_id', $reffId);
            try {
                $updateTemplateInit->update($templateData);
                // Send template id on success updation so that at update method
                // we can check if update done or not
                return $updateTemplateInit->first()->xe_id;
            } catch (\Exception $e) {
                create_log(
                    'template', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'save template',
                        ],
                    ]
                );
            }
        } else {
            // Create a new object instance for save
            $saveTemplateInit = new TemplateModel\Template($templateData);
            $saveTemplateInit->save();
            if ($saveTemplateInit->xe_id) {
                return $saveTemplateInit->xe_id;
            }
            return false;
        }
    }

    /**
     * Save method for Print profile Relations
     *
     * @param $request    Slim's Request object
     * @param $response   Slim's Response object
     * @param $reffId     REF ID from Design State Table
     * @param $templateId Insert ID of Template Record
     * @param $saveType   Flag for Save or Update
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Boolean
     */
    private function savePrintProfileRelations($request, $response, $reffId, $templateId, $saveType = 'save')
    {
        $getPostData = $request->getParsedBody();
        $printProfileRels = [];

        $getPrintProfiles = json_clean_decode($getPostData['print_profiles'], true);

        if (!empty($getPrintProfiles) && count($getPrintProfiles) > 0) {
            foreach ($getPrintProfiles as $printKey => $printProfile) {
                $printProfileRels[$printKey] = [
                    'print_profile_id' => $printProfile,
                    'template_id' => $templateId,
                ];
            }
        }
        // Clean-up old records before updating the table
        if (!empty($saveType) && $saveType == 'update') {
            TemplateModel\TemplatePrintProfileRel::where('template_id', $templateId)
                ->delete();
        }
        $tempPrintProfRelInit = new TemplateModel\TemplatePrintProfileRel();
        $savePrintProfileData = $tempPrintProfRelInit->insert($printProfileRels);
        if ($savePrintProfileData) {
            return true;
        }
        return false;
    }

    /**
     * Delete a category from the table
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Jan 2020
     * @return Delete Json Status
     */
    public function deleteCategory($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Category', 'error'),
        ];
        if (!empty($args) && $args['id'] > 0) {
            $categoryId = $args['id'];
            $jsonResponse = $this->deleteCat(
                'templates', $categoryId, 'Templates', 'TemplateCategoryRel'
            );
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Get most used template
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   04 Mar 2020
     * @return json
     */
    public function mostUsedTemplate($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Fonts', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $page = $request->getQueryParam('page');
        $perpage = $request->getQueryParam('perpage');
        $item = $request->getQueryParam('items');
        $templateInit = new TemplateModel\Template();
        $getTemplates = $templateInit->where($getStoreDetails)
            ->select('xe_id', 'name', 'ref_id');
        $totalCounts = $getTemplates->count();
        if ($totalCounts > 0) {
            // Get pagination data
            $offset = 0;
            if (isset($page) && $page != "") {
                $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                $offset = $totalItem * ($page - 1);
                $getTemplates->skip($offset)->take($totalItem);
            }
            $templateData = $templateList = $getTemplates->orderBy('total_used', 'DESC')
                ->get();
            foreach ($templateList as $templateKey => $template) {
                $referenceId = $template->ref_id;
                // Get template Images
                $getAssocCaptures = $this->getCaptureImages($referenceId);
                $templateData[$templateKey]['capture_images']
                = $getAssocCaptures;
            }
            $jsonResponse = [
                'status' => 1,
                'total_records' => $totalCounts,
                'records' => count($templateData),
                'data' => $templateData,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * POST: Update Templates along with designs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   22 Sept 2020
     * @return Save status in json format
     */
    public function updateTemplates($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $designId = 0;
        $allPutVars = $request->getParsedBody();
        $jsonResponse = [
            'status' => 0,
            'message' => message('Design Template', 'error'),
        ];
        
        if (!empty($args['id']) && $args['id'] > 0) {
            $designId = to_int($args['id']);
            $designState = [
                'product_setting_id' => $allPutVars['product_settings_id'],
                'product_variant_id' => $allPutVars['product_variant_id'],
                'type' => $allPutVars['template_type'],
                'selected_category_id' => $allPutVars['selected_category_id'],
            ];
            // print_r($designState); exit;
            $templateDesignInit = new DesignStates();
            $designInit = $templateDesignInit->where('xe_id', $designId);

            // Save design data and svg json format
            $option = [
                'directory' => 'templates',
                'save_type' => 'update',
                'design_id' => $designId,
            ];
            $reffId = $this->saveDesignData($designState, "", $option);

            $designData = "";
            if (!empty($allPostPutVars['design_data'])) {
                $designData = $allPostPutVars['design_data'];
            }
            $captureImage = $this->saveDesignImages(
                $request, $response, ['ref_id' => $designId, 'svg_data' => $designData, 'update_type' => 'update']
            );

            if ($designInit->count() > 0) {
                try {
                    $designInit->update($designState);
                    // Save Template Data with its dedicated function
                    $templateSaveResponse = $this->saveTemplates(
                        $request, $response, $designId, 'update'
                    );
                    if (!empty($templateSaveResponse) && $templateSaveResponse > 0) {
                        // Save Print Profile Relations
                        $this->savePrintProfileRelations(
                            $request, $response, $designId,
                            $templateSaveResponse, 'update'
                        );
                        // Save Template Tag Relations
                        $templateTagRels = [
                            'reff_id' => $designId,
                            'template_id' => $templateSaveResponse,
                            'store_id' => $getStoreDetails['store_id'],
                        ];
                        $this->saveTemplateTags(
                            $getStoreDetails['store_id'],
                            $templateSaveResponse,
                            $allPutVars['tags']
                        );
                        // Save Template Categories
                        $this->_SaveTemplateCategories(
                            $request, $response, $templateTagRels, 'update'
                        );
                    }
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Design Template', 'updated'),
                    ];
                } catch (\Exception $e) {
                    $serverStatusCode = EXCEPTION_OCCURED;
                    create_log(
                        'template', 'error',
                        [
                            'message' => $e->getMessage(),
                            'extra' => [
                                'module' => 'Update Design State',
                            ],
                        ]
                    );
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
