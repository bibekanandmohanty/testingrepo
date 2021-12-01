<?php
/**
 * This Controller used to save, fetch or delete Prestashop Products on various
 * endpoints
 *
 * PHP version 5.6
 *
 * @category  Prestashop_API
 * @package   Store
 * @author    Radhanatha <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace ProductStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Product Controller
 *
 * @category Prestashop_API
 * @package  Store
 * @author   Radhanatha <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreProductsController extends StoreComponent
{
    public function __construct()
    {
        $this->demoDataJSON = str_replace(BASE_DIR."/api/v1", "", RELATIVE_PATH)."mockupData/JSON/mockData.json";
        $this->storeURL = str_replace("/".BASE_DIR."/api/v1/", "", BASE_URL);
    }
    /**
     * Get list of product or a Single product from the Prestashop API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   15 March 2020
     * @return Array of list/one product(s)
     */
    public function getProducts($request, $response, $args)
    {
        $attributes = $this->getAttributeName();
        $colorGroup = $attributes['color'];
        $sizeGroup = ucfirst($attributes['size']);
        $storeResponse = [];
        $products = $productCategories = $colorArray = $sizeArray = $extraAttributeArray = [];
        $productArray = $productColourVariations = $productSizeVariations = $productExtraVariations = [];
        $getStoreDetails = get_store_details($request);
        if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
            $sanitizedProduct = [];
            // For fetching Single Product
            $productId = $args['id'];
            $productData = [];
            try {
                // Data get from static JSON
                $data = file_get_contents($this->demoDataJSON);
                $productJson = json_decode($data,true);
                $productJson['getProductDetails']['images']['0']['src'] = $this->storeURL . $productJson['getProductDetails']['images']['0']['src'];
                $productJson['getProductDetails']['images']['0']['thumbnail'] = $this->storeURL . $productJson['getProductDetails']['images']['0']['thumbnail'];
                $sanitizedProduct = $productJson['getProductDetails'];
                $storeResponse = [
                    'total_records' => 1,
                    'products' => $sanitizedProduct,
                ];
                // End
            } catch (\Exception $e) {
                $storeResponse[] = [];
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Get product',
                        ],
                    ]
                );
            }
        } else {
            // For fetching All Product by filteration
            $getTotalProductsCount = 0;
            $filterArray = array('type' => array('eq' => 'configurable'));
            $searchstring = $request->getQueryParam('name')
            ? $request->getQueryParam('name') : '';
            $categoryid = $request->getQueryParam('category')
            ? $request->getQueryParam('category') : 0;
            $page = $request->getQueryParam('page')
            ? $request->getQueryParam('page') : 1;
            $perpage = $request->getQueryParam('perpage')
            ? $request->getQueryParam('perpage') : 40;
            $sku = $request->getQueryParam('sku')
            ? $request->getQueryParam('sku') : '';
            $order = $request->getQueryParam('order')
            ? $request->getQueryParam('order') : 'desc';
            $orderby = $request->getQueryParam('orderby')
            ? $request->getQueryParam('orderby') : 'id';
            $isCustomize = $request->getQueryParam('is_customize')
            ? $request->getQueryParam('is_customize') : 0;
            $isCatalog = $request->getQueryParam('is_catalog')
            ? $request->getQueryParam('is_catalog') : 0;
            $limit = $perpage * $page;
            $sort = $orderby . '_' . $order;
            try {
                $option = array(
                    'page_number' => $page,
                    'nb_products' => $perpage,
                    'order_by' => 'id_product',
                    'order_way' => $order,
                    'category_id' => $categoryid,
                );
                if ($isCustomize) {
                    $productsJson = '[]';
                } elseif ($isCatalog) {
                    $productsJson = '[]';
                } else {
                    // Data get from static JSON
                    $data = file_get_contents($this->demoDataJSON);
                    $products = json_decode($data,true);
                    $products['getProductList']['0']['image']['0'] = $this->storeURL . $products['getProductList']['0']['image']['0'];
                    $productsJson = json_encode($products['getProductList']);
                    // End
                }
                $productArray = json_decode($productsJson, true);
                if (isset($productArray) && count($productArray) > 0) {
                    $storeResponse = [
                        'total_records' => $getTotalProductsCount,
                        'products' => $productArray,
                    ];
                }
            } catch (\Exception $e) {
                $storeResponse = [];
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Get all products',
                        ],
                    ]
                );
            }
        }
        return $storeResponse;
    }

    /**
     * Get list of category/subcategory or a Single category/subcategory from the
     * PrestaShop API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   15 March 2020
     * @return Array of list/one category/subcategory(s)
     */
    public function getCategories($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
            $categoryId = $args['id'];
            // Data get from static JSON
            $data = file_get_contents($this->demoDataJSON);
            $dataJson = json_decode($data,true);
            $storeResponse = $dataJson['getCatagory'];
            // End
        } else {
            try {
                // Get all category/subcategory(s) with filterration
                $name = $request->getQueryParam('name')
                ? $request->getQueryParam('name') : '';
                $order = $request->getQueryParam('order')
                ? $request->getQueryParam('order') : 'desc';
                $orderby = $request->getQueryParam('orderby')
                ? $request->getQueryParam('orderby') : 'created_at';
                $filters = [
                    'resource' => 'categories',
                    'display' => 'full',
                    'order' => $order,
                    'orderby' => $orderby,
                    'name' => $name,
                    'store' => $getStoreDetails['store_id'],
                    'output_format' => 'JSON',
                    'language' => '' . $this->languageId . '',
                ];
                // Data get from static JSON
                $data = file_get_contents($this->demoDataJSON);
                $dataJson = json_decode($data,true);
                $storeResponse = $dataJson['getCatagories'];
                // End
            } catch (\Exception $e) {
                $storeResponse = [];
            }

        }
        return $storeResponse;
    }

    /**
     * Get list of Color Variants from the Prestashop API as per the product
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author radhanatham@riaxe.com
     * @date   15 March 2020
     * @return Json
     */
    public function colorsByProduct($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 1,
            'records' => 0,
            'message' => 'No Color available',
            'data' => [],
        ];
        $productId = $args['product_id'];
        try {
            $filters = array(
                'store' => $getStoreDetails['store_id'],
                'product_id' => $productId,
                'attribute' => 'xe_color',
            );
            // Data get from static JSON
            $data = file_get_contents($this->demoDataJSON);
            $dataJson = json_decode($data,true);
            $dataJson['getVariants']['0']['sides']['0']['image']['src'] = $this->storeURL . $dataJson['getVariants']['0']['sides']['0']['image']['src'];
            $dataJson['getVariants']['0']['sides']['0']['image']['thumbnail'] = $this->storeURL . $dataJson['getVariants']['0']['sides']['0']['image']['thumbnail'];
            $dataJson['getVariants']['1']['sides']['0']['image']['src'] = $this->storeURL . $dataJson['getVariants']['1']['sides']['0']['image']['src'];
            $dataJson['getVariants']['1']['sides']['0']['image']['thumbnail'] = $this->storeURL . $dataJson['getVariants']['1']['sides']['0']['image']['thumbnail'];
            $productDetails = $dataJson['getVariants'];
            if (!empty($productDetails)) {
                $storeResponse = $productDetails;
            }
            // End
        } catch (\Exception $e) {
            $storeResponse = [];
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Create Predeco',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * GET: Product Attribute Pricing  Details by Product Id
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author radhanatham@riaxe.com
     * @date   15 March 2020
     * @return All store attributes
     */
    public function storeProductAttrPrc($request, $response, $args)
    {
        $storeResponse = [];
        $productId = to_int($args['id']);
        $filters = [
            'product_id' => $productId,
        ];
        try {
            // Data get from static JSON
            $data = file_get_contents($this->demoDataJSON);
            $productVariant = json_decode($data,true);
            $storeResponse = $productVariant['getProductAttrPriceDetails'];
        } catch (\Exception $e) {
            $storeResponse = [];
        }

        return $storeResponse;
    }

    /**
     * Get: Get all Attributes List from Store-end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return All store attributes
     */
    public function storeAttributeList($request, $response)
    {
        $productId = $request->getQueryParam('product_id');
        $getStoreDetails = get_store_details($request);
        $filters = [
            'store' => $getStoreDetails['store_id'],
            'product_id' => $productId,
        ];
        $attributeList = [];
        if (!empty($productId)) {
            $attributes = $this->webService->getAttributeCombinations($filters);
            $attributeId = $attributeList = [];
            $attributValueName = [];
            foreach ($attributes as $key => $value) {
                $attribute = $attributeValues = [];
                if (!in_array($value['id_attribute_group'], $attributeId)) {
                    $attribute['id'] = $value['id_attribute_group'];
                    $attribute['name'] = $value['group_name'];
                    array_push($attributeList, $attribute);
                    array_push($attributeId, $value['id_attribute_group']);
                } else {
                    if (!in_array($value['attribute_name'], $attributValueName)) {
                        array_push($attributValueName, $value['attribute_name']);
                        $key = array_search($value['id_attribute_group'], $attributeId);
                        $attributeValues['id'] = $value['id_attribute'];
                        $attributeValues['name'] = $value['attribute_name'];
                        $attributeList[$key]['terms'][] = $attributeValues;
                    }
                }
            }
        } else {
            $attributeList = $this->webService->storeAttributeList($filters);
        }

        return $attributeList;
    }

    /**
     * Post: Validate SKU or Name at Store end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Validate response Array
     */
    public function validateStoreSkuName($request, $response)
    {
        $storeResponse = 0;
        $allPostPutVars = $request->getParsedBody();
        if (!empty($allPostPutVars)) {
            $filters = array(
                'name' => $allPostPutVars['name'] ? $allPostPutVars['name'] : '',
                'sku' => $allPostPutVars['sku'] ? $allPostPutVars['sku'] : '',
            );
        }
        try {
            $getProducts = $this->webService->checkDuplicateNameAndSku(
                $filters
            );
            if (!empty($getProducts) && $getProducts) {
                $storeResponse = $getProducts;
            }
        } catch (\Exception $e) {
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Fetch Variations',
                    ],
                ]
            );
        }

        return $storeResponse;
    }

    /**
     * Post: Save predecorated products into the store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Array records and server status
     */
    public function saveProduct($request, $response)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $createVariation = true;
        $getPostData = (isset($saveType) && $saveType == 'update')
        ? $this->parsePut() : $request->getParsedBody();
        if (isset($getPostData['data']) && $getPostData['data'] != "") {
            $predecorData = json_clean_decode($getPostData['data'], true);
            $mode = 'saved';
            if (isset($predecorData['product_id'])
                && $predecorData['product_id'] > 0
            ) {
                $mode = 'updated';
            }
            $productType = 'simple';
            if (isset($predecorData['type']) && $predecorData['type'] != "") {
                $productType = $predecorData['type'];
            }
            $isPredecorated = 1;
            if (isset($predecorData['ref_id']) && $predecorData['ref_id'] > 0) {
                $isPredecorated = 0;
            }
            // Setup a array of Basic Product attributes
            $productSaveData = [
                'name' => $predecorData['name'],
                'sku' => $predecorData['sku'],
                'type' => strtolower($productType),
                'regularPrice' => strval($predecorData['price']),
                'stockQuantity' => $predecorData['quantity'],
                'description' => !empty($predecorData['description'])
                ? $predecorData['description'] : null,
                'shortDescription' => !empty($predecorData['short_description'])
                ? $predecorData['short_description'] : null,
                'isRedesign' => $predecorData['is_redesign'],
                'isPredecorated' => $isPredecorated,
                'parentProductId' => $predecorData['parent_product_id'],
                'productId' => $predecorData['product_id'],
                'designId' => $predecorData['ref_id'],
            ];
            $categories = [];
            $categories['categories'] = [];
            if (isset($predecorData['categories'])
                && count($predecorData['categories']) > 0
            ) {
                foreach ($predecorData['categories'] as $category) {
                    array_push($categories['categories'], $category['category_id']);
                }
                $productSaveData += $categories;
            }
            // Append Image Urls
            $productImages = [];
            $productImages['images'] = [];
            $convertImageToSize = 500;
            if (isset($predecorData['product_image_url'])
                && is_array($predecorData['product_image_url'])
                && count($predecorData['product_image_url']) > 0
            ) {
                $fileSavePath = path('abs', 'temp');
                $fileFetchPath = path('read', 'temp');
                foreach ($predecorData['product_image_url'] as $imageUrl) {
                    $randomName = getRandom();
                    $tempFileName = 'products_' . $randomName;
                    $fileExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
                    $filenameToProcess = $tempFileName . '.' . $fileExtension;
                    // Downlaod the image so that we can change the dimension of
                    // the received image file
                    download_file($imageUrl, $fileSavePath, $filenameToProcess);
                    $fileUrlToProcess = $fileFetchPath . $filenameToProcess;
                    $imageManager = new \Intervention\Image\ImageManagerStatic();
                    $img = $imageManager->make($fileUrlToProcess);
                    $img->resize(
                        $convertImageToSize, null, function ($constraint) {
                            $constraint->aspectRatio();
                        }
                    );
                    $img->save($fileSavePath . 'resize_' . $filenameToProcess);
                    array_push($productImages['images'], $fileFetchPath . 'resize_' . $filenameToProcess);
                }
            } else {
                // If Images are sent from front-end
                $uploadedFileNameList = do_upload(
                    'product_image_files', path('abs', 'predecorator'), [150], 'array'
                );
                foreach ($uploadedFileNameList as $uploadedImage) {
                    array_push($productImages['images'], path('read', 'predecorator') . $uploadedImage);
                }
            }
            $productSaveData += $productImages;
            // End
            if (!empty($predecorData['attributes'])) {
                // Append Attributes by Looping through each Attribute
                $productAttributes = [];
                foreach ($predecorData['attributes'] as $prodAttributekey => $prodAttribute) {
                    $productAttributes['attributes'][] = [
                        'attributeId' => $prodAttribute['attribute_id'],
                        'attributeCode' => $prodAttribute['attribute_code'],
                        'attributeOption' => $prodAttribute['attribute_options'][0],
                    ];
                }
                $productSaveData += $productAttributes;
            }

            // Process the Data to the Product's Post API
            try {
                $params = array(
                    'store' => $getStoreDetails['store_id'],
                    'data' => $productSaveData,
                );
                $resultData = $this->webService->createPredecoProduct($params);
                if (!empty($resultData) && $resultData['id'] > 0) {
                    $storeResponse = [
                        'product_id' => $resultData['id'],
                    ];
                }
            } catch (\Exception $e) {
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Create predecorated product',
                        ],
                    ]
                );
            }
        }
        return $storeResponse;
    }

    /**
     * Get: Get all Attributes List from Store-end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return Array list of Attributes
     */
    public function getOnlyAttribute($request, $response)
    {
        $storeResponse = [];
        $filters = [
            'store' => $getStoreDetails['store_id'],
        ];
        try {
            // Data get from static JSON
            $data = file_get_contents($this->demoDataJSON);
            $jsonData = json_decode($data,true);
            $getAllAttributes = $jsonData['getAttributes'];
            if (!empty($getAllAttributes)) {
                $storeResponse = $getAllAttributes;
            }
            // End
        } catch (\Exception $e) {
            $storeResponse = [];
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get specific attribute list',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Get: Get minimal product details from the store end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   15 March 2020
     * @return Array of list/one product(s)
     */
    public function getProductShortDetails($request, $response, $args)
    {
        $attributes = $this->getAttributeName();
        $sizeGroup = $attributes['size'];
        $colorGroup = $attributes['color'];
        $storeResponse = [];
        $productId = to_int($args['product_id']);
        $variantId = to_int($args['variant_id']);
        $responseType = to_int($args['details']);
        if ($productId > 0 && $variantId > 0) {
            try {
                // Data get from static JSON
                $data = file_get_contents($this->demoDataJSON);
                $productJson = json_decode($data,true);
                $productJson['getProductShortDetails']['images']['0']['src'] = $this->storeURL . $productJson['getProductShortDetails']['images']['0']['src'];
                $productJson['getProductShortDetails']['images']['0']['thumbnail'] = $this->storeURL . $productJson['getProductShortDetails']['images']['0']['thumbnail'];
                $storeResponse = $productJson['getProductShortDetails'];
                // End
            } catch (\Exception $e) {
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Get product limited details',
                        ],
                    ]
                );
            }
        }
        return $storeResponse;
    }

    /**
     * Get: Get the list of product or a Single product from the Prestashop API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatha@riaxe.com
     * @date   13 March 2019
     * @return Array of products list
     */
    public function getToolProducts($request, $response)
    {
        return array();
    }

    /**
     * Get: Get variation's attribute details by variant ID
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array records and server status
     */
    public function storeVariantAttributeDetails($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $storeResponse = $sizeVariations = [];
        $filter = array(
            'product_id' => $args['pid'],
            'store' => $getStoreDetails['store_id'],
            'variation_id' => $args['vid'],
        );
        $groupName = $args['color_name'];
        try {
            //call to prestashop webservice for get all product combination
            $productVariations = $this->webService->getAttributeCombinations($filter);
            $productPrice = $this->webService->getProductPriceByPid($args['pid']);
            $variantId = [];
            if (!empty($productVariations)) {
                if ($groupName == '') {
                    $groupName = 'Color';
                } else {
                    $groupName = ucfirst($groupName);
                }
                $colorId = $colorName = '';
                $colorGroupName = $ziseGroupName = '';
                foreach ($productVariations as $attribute) {
                    if ($attribute['group_name'] == $groupName
                        && $args['vid'] == $attribute['id_product_attribute']
                    ) {
                        $colorGroupName = $attribute['group_name'];
                        $colorGroupNameId = $colorGroupName . '_id';
                        $colorId = $attribute['id_attribute'];
                        $colorName = $attribute['attribute_name'];
                    }
                }
                foreach ($productVariations as $attributes) {
                    if (($attributes['group_name'] == $groupName)
                        && ($colorName == $attributes['attribute_name'])
                    ) {
                        array_push($variantId, $attributes['id_product_attribute']);
                    }
                }
                $i = 0;
                foreach ($productVariations as $v) {
                    if ($v['is_color_group'] == 0
                        && (in_array($v['id_product_attribute'], $variantId))
                    ) {
                        $ziseGroupName = $v['group_name'];
                        $ziseGroupNameId = $ziseGroupName . '_id';
                        $sizeVariations[$i]['variant_id'] = $v['id_product_attribute'];
                        $sizeVariations[$i]['inventory']['stock'] = $v['quantity'];
                        $sizeVariations[$i]['inventory']['min_quantity'] = $v['minimal_quantity'];
                        $sizeVariations[$i]['inventory']['max_quantity'] = $v['quantity'];
                        $sizeVariations[$i]['inventory']['quantity_increments'] = '1';
                        $sizeVariations[$i]['price'] = $this->webService->convertToDecimal($productPrice, 2);
                        $sizeVariations[$i]['tier_prices'] = [];
                        $sizeVariations[$i]['attributes'][$colorGroupNameId] = $colorId;
                        $sizeVariations[$i]['attributes'][$colorGroupName] = $colorName;
                        $sizeVariations[$i]['attributes'][$ziseGroupNameId] = $v['id_attribute'];
                        $sizeVariations[$i]['attributes'][$ziseGroupName] = $v['attribute_name'];
                        $i++;
                    }
                }
            }
            if (!empty($sizeVariations)) {
                $storeResponse = array_values($sizeVariations);
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Store variant attribute details with quantity',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Get total product count from the Prestashop API
     *
     * @author radhanatham@riaxe.com
     * @date   11 march 2020
     * @return count
     */
    public function totalProductCount()
    {
        $parameter = array(
            'resource' => 'products',
            'display' => '[id]',
            'output_format' => 'JSON',
        );
        $getProductCount['total'] = 20;
        $getProductCount['vc'] = '';
        return $getProductCount;
    }

    /**
     * Get: Get Attribute List for Variants with Multiple attribute
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument
     *
     * @author radhanatham@riaxe.com
     * @date   30th june 2020
     * @return Array records and server status
     */
    public function storeMultiAttributeVariantDetails($request, $response, $args)
    {
        $storeResponse = [];
        $attributes = $this->getAttributeName();
        $sizeGroup = $attributes['size'];
        $colorGroup = $attributes['color'];
        $productId = to_int($args['pid']);
        $variationId = to_int($args['vid']);
        $attribute = ucfirst($args['attribute']);
        $isAttribute = $args['isAttribute'];
        $extraAttributeIdArr = [];
        if ($isAttribute) {
            $extraAttributeIdArr = $this->getMultipleAttributeVariant($request, $response, $args);
        }
        $details = $args['price'];
        $extraAttributeGroup = '';
        if ($args['attribute'] != $sizeGroup) {
            $extraAttributeGroup = $args['attribute'];
        } else {
            $extraAttributeGroup = $sizeGroup;
        }
        $option['per_page'] = 100;
        try {
            $filter = array(
                'product_id' => $productId,
                'variation_id' => $variationId,
            );
            // Data get from static JSON
            $data = file_get_contents($this->demoDataJSON);
            $jsonData = json_decode($data,true);
            $finalArray = $jsonData['getMultipleAttributes'];
            if (!empty($finalArray)) {
                $storeResponse = $finalArray;
            }
            // End
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Store variant attribute details with quantity',
                    ],
                ]
            );
        }

        return $storeResponse;
    }

    /**
     * Get: Get Attribute List for Variants ids
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument
     *
     * @author radhanatham@riaxe.com
     * @date   13th july 2020
     * @return Array records
     */
    public function getMultipleAttributeVariant($request, $response, $args)
    {
        $productId = to_int($args['pid']);
        $variationId = to_int($args['vid']);
        $attribute = ucfirst($args['attribute']);
        try {
            $filter = array(
                'product_id' => $productId,
                'variation_id' => $variationId,
            );
            $productVariations = $this->webService->getAttributeCombinations($filter);

            $arributeName = '';
            if (!empty($productVariations)) {
                foreach ($productVariations as $k => $v) {
                    if ($v['id_product_attribute'] == $variationId && $v['is_color_group'] == 1) {
                        $arributeName = $v['attribute_name'];
                    }
                }
            }
            if (!empty($productVariations)) {
                foreach ($productVariations as $k => $v) {
                    if ($v['attribute_name'] == $arributeName) {
                        $attributesId[] = $v['id_product_attribute'];
                    }
                }
            }
            $arribute = [];
            if (!empty($productVariations)) {
                $attributesNameArray = array();
                foreach ($productVariations as $k => $v) {
                    if (($v['is_color_group'] == 0
                        && (in_array($v['id_product_attribute'], $attributesId) && ($v['group_name'] == $attribute))) && (!in_array($v['attribute_name'], $attributesNameArray))
                    ) {
                        array_push($attributesNameArray, $v['attribute_name']);
                        $arribute[] = $v['id_product_attribute'];
                    }
                }
            }

        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Store variant attribute details with quantity',
                    ],
                ]
            );
        }

        return $arribute;
    }

    /**
     * POST: Product add to store
     *
     * @param $productData Product data
     * @param $catalog     Catalog details
     *
     * @author radhanatham@riaxe.com
     * @date   05 June 2020
     * @return array json
     */
    public function addProductToStore($productData, $catalog)
    {
        $productArr = [];
        if (!empty($productData)) {
            foreach ($productData as $k => $v) {
                $price = $v['price'];
                $catalog_price = $v['catalog_price'] ? $v['catalog_price'] : $price;
                $params = array('style_id' => $v['style_id'], "catalog_code" => $catalog);
                $returnData = api_call_by_curl($params, 'product');
                $predecorData = $returnData['data'];
                $sideName = $predecorData['variations'][0]['side_name'];
                if (!empty($predecorData)) {
                    $productId = $this->webService->addCatalogProductToStore($predecorData, $price, $catalog_price);
                    if ($productId) {
                        $productArr[$k]['product_id'] = $productId;
                        $productArr[$k]['product_side'] = $sideName;
                        $productArr[$k]['style_id'] = $v['style_id'];
                        $productArr[$k]['decorData'] = $predecorData;
                    }
                }
            }
        }
        return $productArr;
    }

    /**
     * Get product categories
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   07 Sept 2020
     * @return array
     */
    public function productCategories($productId)
    {
        $storeResponse = [];
        try {
            $getProductCategory = $this->webService->productCategories($productId);
            $i = 0;
            foreach ($getProductCategory as $v) {
                if ($v['parent_id'] >= 2 && $v['name'] != 'ROOT') {
                    $responceCategories[$i]['id'] = $v['id'];
                    $responceCategories[$i]['name'] = $v['name'];
                    if ($v['parent_id'] == 2 || $v['parent_id'] == 1) {
                        $idParent = 0;
                    } else {
                        $idParent = $v['parent_id'];
                    }
                    $responceCategories[$i]['parent_id'] = $idParent;
                    $i++;
                }
            }
            if (!empty($responceCategories) && count($responceCategories) > 0) {
                $storeResponse = $responceCategories;

            }
        } catch (\Exception $e) {
            $storeResponse = [];
        }
        return $storeResponse;
    }

}
