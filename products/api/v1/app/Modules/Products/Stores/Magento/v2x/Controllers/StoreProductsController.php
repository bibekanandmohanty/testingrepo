<?php
/**
 * This Controller used to save, fetch or delete Magento Products on various
 * endpoints
 *
 * PHP version 5.6
 *
 * @category  Magento_API
 * @package   Store
 * @author    Tapas Ranjan<tapasranjanp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace ProductStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Product Controller
 *
 * @category Magento_API
 * @package  Store
 * @author   Tapas Ranjan<tapasranjanp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreProductsController extends StoreComponent
{
    /**
     * Get: list of product or a Single product from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of list/one product(s)
     */
    public function getProducts($request, $response, $args)
    {
        $products = [];
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
            // For fetching Single Product
            try {
                $productId = $args['id'];
                $productInfo = array(
                    'productId' => $productId,
                    'configProductId' => $productId,
                    'minimalData' => 0,
                    'store' => $getStoreDetails['store_id'],
                );
                $result = $this->apiCall('Product', 'getProductById', $productInfo);
                $products = json_clean_decode($result->result, true);
                if (!empty($products) && count($products) > 0) {
                    $storeResponse = [
                        'total_records' => 1,
                        'products' => $products['data'],
                    ];
                } else {
                    $storeResponse = [
                        'total_records' => 0,
                        'products' => [],
                    ];
                }
            } catch (\Exception $e) {
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
        } else {
            // For fetching All Product by filteration
            $getTotalProductsCount = 0;
            $filterArray = array('type' => array('eq' => 'configurable'));
            $searchstring = $request->getQueryParam('name')
            ? $request->getQueryParam('name') : '';
            $categoryid = $request->getQueryParam('category')
            ? $request->getQueryParam('category') : 2;
            $page = $request->getQueryParam('page')
            ? $request->getQueryParam('page') : 0;
            $perpage = $request->getQueryParam('perpage')
            ? $request->getQueryParam('perpage') : 20;
            $sku = $request->getQueryParam('sku')
            ? $request->getQueryParam('sku') : '';
            $order = $request->getQueryParam('order')
            ? $request->getQueryParam('order') : 'desc';
            $orderby = $request->getQueryParam('orderby')
            ? $request->getQueryParam('orderby') : 'created_at';
            $isPredecorated = (!empty($request->getQueryParam('is_customize'))
                && $request->getQueryParam('is_customize') > 0) ? 1 : 0;
            $isCatalog = (!empty($request->getQueryParam('is_catalog'))
                && $request->getQueryParam('is_catalog') > 0) ? 1 : 0;
            $filters = array(
                'filters' => $filterArray,
                'categoryid' => $categoryid,
                'searchstring' => $searchstring,
                'store' => $getStoreDetails['store_id'],
                'range' => array('start' => $page, 'range' => $perpage),
                'offset' => $page,
                'limit' => $perpage,
                'isPredecorated' => $isPredecorated,
                'isCatalog' => $isCatalog,
                'sku' => $sku,
                'order' => $order,
                'orderby' => $orderby,
            );
            try {
                $result = $this->apiCall('Product', 'getProducts', $filters);
                $products = json_clean_decode($result->result, true);
                if (!empty($products) && count($products) > 0) {
                    $storeResponse = [
                        'total_records' => $products['records'],
                        'products' => $products['data'],
                    ];

                } else {
                    $storeResponse = [
                        'total_records' => 0,
                        'products' => [],

                    ];
                }
            } catch (\Exception $e) {
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
     * Get: Get minimal product details from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of one product details
     */
    public function getProductShortDetails($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $productId = to_int($args['product_id']);
        $variantId = to_int($args['variant_id']);

        if ($productId > 0 && $variantId > 0) {
            try {
                $productInfo = array(
                    'productId' => $variantId,
                    'configProductId' => $productId,
                    'minimalData' => 1,
                    'store' => $getStoreDetails['store_id'],
                );
                $result = $this->apiCall('Product', 'getProductById', $productInfo);
                $getProductDetails = json_clean_decode($result->result, true);
                $storeResponse = $getProductDetails['data'];
            } catch (\Exception $e) {
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Get product short details',
                        ],
                    ]
                );
            }
        }
        return $storeResponse;
    }
    /**
     * Get: list of category/subcategory or a Single category/subcategory from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of list/one category/subcategory(s)
     */
    public function getCategories($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        try {
            // Get all category/subcategory(s) with filterration
            $name = $request->getQueryParam('name')
            ? $request->getQueryParam('name') : '';
            $order = $request->getQueryParam('order')
            ? $request->getQueryParam('order') : 'desc';
            $orderby = $request->getQueryParam('orderby')
            ? $request->getQueryParam('orderby') : 'created_at';
            $filters = [
                'order' => $order,
                'orderby' => $orderby,
                'name' => $name,
                'store' => $getStoreDetails['store_id'],
            ];
            $result = $this->apiCall('Product', 'getCategories', $filters);
            $result = $result->result;
            $categoryDetails = json_clean_decode($result, true);
            if (!empty($categoryDetails) && count($categoryDetails) > 0) {
                $storeResponse = $categoryDetails;
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get all categories',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Get: list of Color Variants as per the product from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of color variants
     */
    public function colorsByProduct($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $filters = [
            'productId' => $args['product_id'],
            'store' => $getStoreDetails['store_id'],
            'attribute' => $args['slug'],
        ];
        try {
            $result = $this->apiCall(
                'Product', 'getColorVariantsByProduct', $filters
            );
            $result = $result->result;
            $singleProductDetails = json_clean_decode($result, true);
            if (!empty($singleProductDetails)) {
                $storeResponse = $singleProductDetails;
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get color by product',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Get: Product Attribute Pricing Details by Product Id from Magento store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of All store attributes price
     */
    public function storeProductAttrPrc($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);

        try {
            $productId = intval($args['id']);
            $getProducts = [];
            $filters = [
                'productId' => $productId,
                'store' => $getStoreDetails['store_id'],
            ];
            $result = $this->apiCall(
                'Product', 'getAllVariantsByProduct', $filters
            );
            $result = $result->result;
            $singleProductDetails = json_clean_decode($result, true);
            if (!empty($singleProductDetails)) {
                $storeResponse = $singleProductDetails;
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get products attribute price',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Get: Get all Attributes List from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of All store attributes
     */
    public function storeAttributeList($request, $response)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $productId = $request->getQueryParam('product_id');
        $filters = [
            'store' => $getStoreDetails['store_id'],
            'productId' => $productId,
            'type' => 'select and checkbox'
        ];
        try {
            $result = $this->apiCall(
                'Product', 'getAttributesByProductId', $filters
            );
            $result = $result->result;
            $attributeList = json_clean_decode($result, true);
            if (!empty($attributeList) && count($attributeList) > 0) {
                $storeResponse = $attributeList;
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Store attribute list',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Post: Validate SKU or Name at Magento store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Int product id if exist
     */
    public function validateStoreSkuName($request, $response)
    {
        $storeResponse = 0;
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = (isset($saveType) && $saveType == 'update')
        ? $this->parsePut() : $request->getParsedBody();
        $filters = array(
            'sku' => isset($allPostPutVars['sku']) ? $allPostPutVars['sku'] : null,
            'name' => isset($allPostPutVars['name']) ? $allPostPutVars['name'] : null,
            'store' => $getStoreDetails['store_id'],
        );
        try {
            $result = $this->apiCall(
                'Product', 'checkDuplicateNameAndSku', $filters
            );
            $result = $result->result;
            $getProducts = json_clean_decode($result, true);
            if (!empty($getProducts[0]) && $getProducts[0] > 0) {
                $storeResponse = $getProducts[0];
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Validate product with sku or name exist or not',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Post: Save predecorated products into the Magento store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 Feb 2020
     * @return Array saved records
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
            $isPredecorated = 0;
            $predecoratedId = 0;
            if (isset($predecorData['ref_id']) && $predecorData['ref_id'] > 0) {
                $isPredecorated = 1;
                $predecoratedId = $predecorData['ref_id'];
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
                'predecoratedId' => $predecoratedId,
                'parentProductId' => $predecorData['parent_product_id'],
                'productId' => $predecorData['product_id'],
                'attributeSet' => 'ImprintNext', //$predecorData['attr_set'],
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
                        'attributeCode' => $prodAttribute['attribute_name'],
                        'attributeOption' => $prodAttribute['attribute_options']
                    ];
                }
                $productSaveData += $productAttributes;
            }
            // Process the Data to the Product's Post API
            try {
                $params = array(
                    'store' => $getStoreDetails['store_id'],
                    'data' => json_encode($productSaveData),
                );
                if ($productType == 'simple') {
                    // Add pre-decorated Simple product
                    $result = $this->apiCall('Product', 'createPredecoSimpleProduct', $params);
                } else {
                    // Add pre-decorated Configurable product
                    $result = $this->apiCall('Product', 'createPredecoConfigProduct', $params);
                }
                $result = $result->result;
                $resultData = json_clean_decode($result, true);
                if (!empty($resultData['id']) && $resultData['id'] > 0) {
                    $storeResponse = [
                        'product_id' => $resultData['id'],
                    ];
                }
                if (!empty($resultData['vids'])) {
                    $storeResponse += [
                        'variation_id' => $resultData['vids'],
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
     * Get: Total product count from Magento store API
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array product total count
     */
    public function totalProductCount()
    {
        $storeResponse = [];
        $filters = array(
            'store' => 1, //$getStoreDetails['store_id']
        );
        try {
            $result = $this->apiCall(
                'Product', 'totalProductCount', $filters
            );
            $result = $result->result;
            $storeResponse = json_clean_decode($result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get total product count',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Get: Get all Attributes List from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array list of dropdown type Attributes
     */
    public function getOnlyAttribute($request, $response)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $filters = array(
            'type' => 'select',
            'store' => $getStoreDetails['store_id'],
        );
        try {
            $result = $this->apiCall(
                'Product', 'getAttributes', $filters
            );
            $result = $result->result;
            $getAllAttributes = json_clean_decode($result, true);
            if (!empty($getAllAttributes) && count($getAllAttributes) > 0) {
                $storeResponse = $getAllAttributes;
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get dropdown type attributes',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Get: Get variation's attribute details by variant ID from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of available attribute of a product
     */
    public function storeVariantAttributeDetails($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $attributeName = $this->getAttributeName();
        $productId = $args['pid'];
        $variationId = $args['vid'];
        $filters = array(
            'productId' => $productId,
            'store' => $getStoreDetails['store_id'],
            'simpleProductId' => $variationId,
            'color' => $attributeName['color'],
            'size' => $attributeName['size'],
        );
        try {
            $result = $this->apiCall(
                'Product', 'getSizeAndQuantity', $filters
            );
            $result = $result->result;
            $getSelVarDetails = json_clean_decode($result, true);
            if (!empty($getSelVarDetails)) {
                $storeResponse = $getSelVarDetails;
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
     * Get: Get the list of product category wise from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tapasranjanp@riaxe.com
     * @date   18 March 2020
     * @return Array of products list
     */
    public function getToolProducts($request, $response)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $filters = array(
            'store' => $getStoreDetails['store_id'],
        );
        try {
            $result = $this->apiCall(
                'Product', 'getAllProductByCategory', $filters
            );
            $result = $result->result;
            $getProducts = json_clean_decode($result, true);
            $storeResponse = $getProducts;
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get all product with category wise',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * Get: Get Attribute List for Variants with Multiple attribute
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument
     *
     * @author tapasranjanp@riaxe.com
     * @date   13th April 2020
     * @return Array attributes records
     */
    public function storeMultiAttributeVariantDetails($request, $response, $args)
    {
        $storeResponse = [];
        $filteredAttributes = [];
        $getStoreDetails = get_store_details($request);
        $productId = to_int($args['pid']);
        $variationId = to_int($args['vid']);
        $attribute = $args['attribute'];
        $details = $args['price'];
        try {
            $filters = array(
                'store' => $getStoreDetails['store_id'],
                'productId' => $productId,
                'variationId' => $variationId,
                'attribute' => $attribute,
            );
            $result = $this->apiCall(
                'Product', 'getMultiAttributeVariantDetails', $filters
            );
            $result = $result->result;
            $getSelVarDetails = json_clean_decode($result, true);

            $varKey = array_search(
                $variationId,
                array_column($getSelVarDetails, 'id')
            );

            $attrKey = array_search(
                $attribute,
                array_column($getSelVarDetails[$varKey]['attributes'], 'name')
            );
            $length = count($getSelVarDetails[$varKey]['attributes']) - 1;
            $attributes = [];
            $finalArray = [];
            $j = 0;
            foreach ($getSelVarDetails as $variations) {
                $count = 0;
                for ($i = 0; $i < $attrKey; $i++) {
                    if ($variations['attributes'][$i]['option'] == $getSelVarDetails[$varKey]['attributes'][$i]['option']) {
                        $count++;
                    }
                }
                if (($count == $attrKey) && (empty($attributes) || !in_array($variations['attributes'][$attrKey]['option'], $attributes))) {
                    $finalArray[$variations['attributes'][$attrKey]['name']][$j]['id'] = $variations['attributes'][$attrKey]['id'];
                    $finalArray[$variations['attributes'][$attrKey]['name']][$j]['name'] = $variations['attributes'][$attrKey]['option'];
                    $finalArray[$variations['attributes'][$attrKey]['name']][$j]['variant_id'] = $variations['id'];
                    if ($length == $attrKey || $details == 1) {
                        $finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['stock'] = $variations['stock_quantity'];
                        $finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['min_quantity'] = $variations['min_quantity'];
                        $finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['max_quantity'] = $variations['max_quantity'];
                        $finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['quantity_increments'] = $variations['quantity_increments'];
                        $finalArray[$variations['attributes'][$attrKey]['name']][$j]['price'] = $variations['price'];
                        $finalArray[$variations['attributes'][$attrKey]['name']][$j]['tier_prices'] = $variations['tier_prices'];
                        $finalArray[$variations['attributes'][$attrKey]['name']][$j]['attributes'] = $variations['options'];
                    }
                    array_push($attributes, $variations['attributes'][$attrKey]['option']);
                    $j++;
                }
            }
            if (!empty($finalArray)) {
                $storeResponse = $finalArray;
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
     * POST: Product add to store
     *
     * @param $productData Product data
     * @param $catalog Catalog details
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function addProductToStore($productData, $catalog)
    {
        $productArr = [];
        if (!empty($productData)) {
            foreach ($productData as $k => $v) {
                $price = $v['price'];
                $catalog_price = $v['catalog_price'] ? $v['catalog_price'] : $price;
                $oldProductId = $v['old_product_id'] ? $v['old_product_id'] : 0;
                $params = array('style_id' => $v['style_id'], "catalog_code" => $catalog);
                $returnData = api_call_by_curl($params, 'product');
                $predecorData = $returnData['data'];
                $sideName = $predecorData['variations'][0]['side_name'];
                if (!empty($predecorData)) {
                    $predecorData['categories'] = $v['categories'];
                    $filters = array(
                        'data' => json_encode($predecorData),
                        'price' => $price,
                        'productId' => $oldProductId
                    );
                    $result = $this->apiCall(
                        'Product', 'addCatalogProductToStore', $filters
                    );
                    $result = $result->result;
                    $productId = json_clean_decode($result, true);
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
    public function createProductImportCSV($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $predecoDetails = $request->getParsedBody();
        $productData = json_clean_decode($predecoDetails['product_data']);
        $catalog = $predecoDetails['catalog_code'];
        $assetsPath = path('abs', 'assets');
        $cataloAssetsPath = $assetsPath . 'catalog';
        if (!is_dir($cataloAssetsPath)) {
            mkdir($cataloAssetsPath, 0755);
        }
        if (!empty($productData)) {
            $headerData = [
                "sku", "store_view_code", "attribute_set_code", "product_type", "categories", "product_websites", "name", "description", "short_description", "weight", "product_online", "tax_class_name", "visibility", "price", "special_price", "special_price_from_date", "special_price_to_date", "url_key", "meta_title", "meta_keywords", "meta_description", "base_image", "base_image_label", "small_image", "small_image_label", "thumbnail_image", "thumbnail_image_label", "swatch_image", "swatch_image_label", "created_at", "updated_at", "new_from_date", "new_to_date", "display_product_options_in", "map_price", "msrp_price", "map_enabled", "gift_message_available", "custom_design", "custom_design_from", "custom_design_to", "custom_layout_update", "page_layout", "product_options_container", "msrp_display_actual_price_type", "country_of_manufacture", "additional_attributes", "qty", "out_of_stock_qty", "use_config_min_qty", "is_qty_decimal", "allow_backorders", "use_config_backorders", "min_cart_qty", "use_config_min_sale_qty", "max_cart_qty", "use_config_max_sale_qty", "is_in_stock", "notify_on_stock_below", "use_config_notify_stock_qty", "manage_stock", "use_config_manage_stock", "use_config_qty_increments", "qty_increments", "use_config_enable_qty_inc", "enable_qty_increments", "is_decimal_divided", "website_id", "related_skus", "related_position", "crosssell_skus", "crosssell_position", "upsell_skus", "upsell_position", "additional_images", "additional_image_labels", "hide_from_product_page", "custom_options", "bundle_price_type", "bundle_sku_type", "bundle_price_view", "bundle_weight_type", "bundle_values", "bundle_shipment_type", "associated_skus", "downloadable_links", "downloadable_samples", "configurable_variations", "configurable_variation_labels",
            ];
            $rowData = $productData;
            $dateTime = date('d-m-y h:i:s');
            $time = date("g:iA", strtotime($dateTime));
            $date = date('m/d/Y');
            $currentDateTime = $date . ' ' . $time;
            $randNo = getRandom();
            $csvFilename = $randNo . '.csv';
            if (!empty($productData)) {
                $productArray = [];
                $i = 0;
                $variants = [];
                $variants[0] = $headerData;
                foreach ($productData as $k => $v) {
                    $price = $v['price'];
                    $catalog_price = $v['catalog_price'];
                    $params = array("catalog_code" => $catalog, 'style_id' => $v['style_id']);
                    $returnData = api_call_by_curl($params, 'product');
                    $predecorData = $returnData['data'];
                    $category = $categories = '';
                    foreach ($predecorData['category'] as $key => $cat) {
                        $category .= 'Default Category/'. $cat . ',';
                    }

                    $categories = rtrim($category, ",");

                    $productImageUrl = $predecorData['images']['src'];
                    $combination = $combinations = '';
                    if (!empty($predecorData['variations'])) {
                        $j = 0;
                        foreach ($predecorData['variations'] as $keys => $variations) {
                            $stock = $variations['quantity'];
                            $varintPrice = 0;
                            if ($variations['piece_price'] > 0) {
                                $diffPrice = $price - $catalog_price;
                                $varintPrice = $variations['piece_price'] + $diffPrice;
                            } else {
                                $varintPrice = $maxprice;
                            }
                            $image_path = $variations['image_path'];
                            $image = $images = '';
                            if (!empty($image_path)) {
                                foreach ($image_path as $img) {
                                    if ($img != '') {
                                        $image .= $img . ', ';
                                    }
                                }
                                $images = rtrim($image, ', ');
                            }
                            $images = $image_path[0];
                            $combination .= 'sku=' . $predecorData['sku'] . '-' . $variations['attributes']['color'] . '-' . $variations['attributes']['size'] . ',color=' . $variations['attributes']['color'] . ',size=' . $variations['attributes']['size'] . '|';
                            $comb = 'color=' . $variations['attributes']['color'] . ',disable_addtocart=0,size=' . $variations['attributes']['size'] . ',xe_is_designer=0,xe_is_template=0,is_catalog=1';

                            $sku = $predecorData['sku'] . '-' . $variations['attributes']['color'] . '-' . $variations['attributes']['size'];
                            $variants[] = [
                                $sku, "", "ImprintNext", "simple", $categories, "base", $sku, $predecorData['description'], $predecorData['description'], 1, 1, 0, "Not Visible Individually", $varintPrice, "", "", "", strtolower($sku), $sku, $sku, $sku, $images, "", $images, "", $images, "", $images, "", $currentDateTime, $currentDateTime, "", "", "Block after Info Column", "", "", "", "No", "", "", "", "", "", "", "Use config", "", $comb, $stock, 0, 1, 0, 0, 1, 1, 1, 10000, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "",
                            ];
                            $j++;
                        }
                        $combinations = rtrim($combination, '|');
                        $productArray[$i] = [
                            $predecorData['sku'], "", "ImprintNext", "configurable", $categories, "base", $predecorData['name'], $predecorData['description'], $predecorData['description'], 1, 1, 0, "Catalog, Search", $varintPrice, "", "", "", strtolower($predecorData['sku']), $predecorData['sku'], $predecorData['sku'], $predecorData['sku'], $productImageUrl, "", $productImageUrl, "", $productImageUrl, "", $productImageUrl, "", $currentDateTime, $currentDateTime, "", "", "Block after Info Column", "", "", "", "Use config", "", "", "", "", "", "", "Use config", "", "disable_addtocart=0,xe_is_designer=1,xe_is_template=0,is_catalog=1", 0, 0, 1, 0, 0, 1, 1, 1, 10000, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", $combinations, "color=Color,size=Size",
                        ];
                    }
                    $i++;
                }
            }
            $newArr = array_merge($variants, $productArray);
            if (!empty($newArr)) {
                $cfilename = $cataloAssetsPath . '/' . $csvFilename;
                if (is_dir($cataloAssetsPath)) {
                    $fp = fopen($cfilename, 'w');
                    foreach ($newArr as $fields) {
                        fputcsv($fp, $fields);
                    }
                }
                fclose($fp);
            }
        }
        return $csvFilename;
    }

    /**
     * Post: Create product catagories/subcategories.
     *
     * @param $request Slim's Request object
     * @param $response Slim's Response object
     *
     * @author Tapas
     * @date   23 Mar 2021
     * @return Array saved id with message
     */
    public function createProductCatagories($request, $response) {
        $storeResponse = [];
        $getPostData = $request->getParsedBody();
        $catName = $getPostData['name'];
        $catId = $getPostData['catId'];
        $store_id = $getPostData['store_id'];
        try {
            $filters = array(
                'catName' => $catName,
                'catId' => $catId,
                'store' => $store_id
            );
            $result = $this->apiCall('Product', 'createStoreProductCatagories', $filters);
            $result = $result->result;
            $categoryId = json_clean_decode($result, true);
            if ($categoryId != 0 && $categoryId != null) {
                $storeResponse = [
                    'status' => 1,
                    'catatory_id' => $categoryId,
                    'message' => message('Catagories', 'saved'),
                ];
            } else {
                $storeResponse = [
                        'status' => 0,
                        'message' => 'Category already exist.',
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
                        'module' => 'Create Categories',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Remove: Delete catagories and subcatagories.
     *
     * @param $request Slim's Request object
     * @param $response Slim's Response object
     * @param $args Slim's Argument parameters
     *
     * @author Tapas
     * @date   23 Mar 2021
     * @return Array saved id with message
     */
    public function removeCategories($request, $response, $args) {
        $storeResponse = [];
        if (isset($args['id']) && $args['id'] > 0) {
            try {
                $filters = array(
                    'catId' =>(int)$args['id'],
                    'store' => $request->getQueryParam('store_id')
                );
                $result = $this->apiCall('Product', 'removeStoreProductCatagories', $filters);
                $result = $result->result;
                $status = json_clean_decode($result, true);
                if ($status) {
                    $storeResponse = [
                        'status' => 1,
                        'message' => message('Categories', 'deleted'),
                    ];
                } else {
                    $storeResponse = [
                        'status' => 0,
                        'message' => message('Categories', 'error'),
                    ];
                }
            } catch (\Exception $e) {
                $storeResponse = [
                    'status' => 0,
                    'message' => $e->getMessage(),
                ];
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Remove catagories',
                        ],
                    ]
                );
            }
        }
        return $storeResponse;
    }

    /**
     * Get: get variants of a product
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tapasranjanp@riaxe.com
     * @date   28 July 2020
     * @return Array records
     */
    public function productVariants($request, $response, $args) {
        $storeResponse = [];
        if (!empty($args['productID'])) {
            $storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
            try {
                $productId = intval($args['productID']);
                $filters = [
                    'productId' => $productId,
                    'store' => $storeId,
                ];
                $result = $this->apiCall(
                    'Product', 'getVariants', $filters
                );
                $result = $result->result;
                $storeResponse = json_clean_decode($result, true);
            } catch (\Exception $e) {
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Get products attribute price',
                        ],
                    ]
                );
            }
        }
        return $storeResponse;
    }
}
