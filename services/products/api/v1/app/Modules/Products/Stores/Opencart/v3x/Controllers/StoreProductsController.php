<?php
/**
 * Manage Opencart Store Products
 *
 * PHP version 5.6
 *
 * @category  Store_Product
 * @package   Store
 * @author    Mukesh Pradhan <mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace ProductStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store product Controller
 *
 * @category                Store_Product
 * @package                 Store
 * @author                  Mukesh Pradhan <tanmayap@riaxe.com>
 * @license                 http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link                    http://inkxe-v10.inkxe.io/xetool/admin
 * @SuppressWarnings(PHPMD)
 */
class StoreProductsController extends StoreComponent
{
    /**
     * Instantiate Constructer
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function storeProductVariant($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Store Product Variant', 'not_found'),
        ];
        $productId = $request->getQueryParam('product_id');
        $optionId = $request->getQueryParam('option_id');
        $param = [
            "product_id" => $productId, 'product_option_value_id' => $optionId,
        ];
        $variantResponse = $this->getStoreProductVariant($param);
        return $variantResponse;
    }
    /**
     * Get: Get the list of product or a Single product from the Opencart API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Array of list/one product(s)
     */
    public function getProducts($request, $response, $args)
    {
        $storeResponse = [];
        $singleProductDetails = [];
        $storeResponse = [
            'total_records' => 0,
            'products' => [],
        ];
        if (isset($args['id']) && $args['id'] > 0) {
            $type = 0;
            $productId = $args['id'];
            if (isset($args['product_id']) && $args['product_id'] != $args['id']) {
                $type = 1;
                $productId = $args['product_id'];
                $variantId = $args['id'];
            }

            try {
                $singleProductDetails = $this->getProductById($productId);
                $variantId = $singleProductDetails['pvid'];
                // Collecting Images into the Product Array
                if ($variantId != $productId) {
                    $productImages = $this->getProductStoreImages($variantId);
                    if (empty($productImages)) {
                        $productImages = $this->getProductStoreImages($productId);
                    }
                } else {
                    $productImages = $this->getProductStoreImages($productId);
                }
                $productType = $singleProductDetails['type'];
                $attributes = $this->getProductAttributes($productId, 1);
                $price = 0;
                if (!empty($singleProductDetails['sale_price'])) {
                    $price = $singleProductDetails['sale_price'];
                } elseif (!empty($singleProductDetails['price'])) {
                    $price = $singleProductDetails['price'];
                }
                $sanitizedProduct = [
                    'id' => $singleProductDetails['id'],
                    'name' => $singleProductDetails['name'],
                    'sku' => $singleProductDetails['sku'],
                    'type' => $singleProductDetails['type'],
                    'variant_id' => $variantId,
                    'description' => preg_replace(
                        "/\r|\n/",
                        "",
                        substr(
                            strip_tags($singleProductDetails['description']),
                            0,
                            110
                        )
                    ),
                    'price' => $price,
                    'stock_quantity' => $singleProductDetails['stock_quantity'],
                    'images' => $productImages,
                    'categories' => $singleProductDetails['categories'],
                    'attributes' => $attributes,
                ];
                $storeResponse = [
                    'total_records' => 1,
                    'products' => $sanitizedProduct,
                ];
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
            // Get all requested Query params
            $filters = [
                'search' => $request->getQueryParam('name'),
                'category' => $request->getQueryParam('category'),
                'range' => $request->getQueryParam('per_page'),
                'page' => $request->getQueryParam('page'),
                'order' => !empty($request->getQueryParam('order'))
                ? $request->getQueryParam('order') : 'desc',
                'order_by' => !empty($request->getQueryParam('orderby'))
                ? $request->getQueryParam('orderby') : 'post_date',
                'is_customize' => (!empty($request->getQueryParam('is_customize'))
                    && $request->getQueryParam('is_customize') > 0) ? 1 : 0,
                'is_catalog' => (!empty($request->getQueryParam('is_catalog'))
                    && $request->getQueryParam('is_catalog') > 0) ? 1 : 0,
                'fetch' => $request->getQueryParam('fetch') ? $request->getQueryParam('fetch') : '',
                'store_id' => $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1,
            ];
            // If any outer methods sends category(ies) then this code will run
            if (!empty($args['categories']) && $args['categories'] != "") {
                $filters['category'] = trim($args['categories']);
            }
            if (!empty($args['is_customize']) && $args['is_customize'] != "") {
                $filters['is_customize'] = trim($args['is_customize']);
            }

            /**
             * Fetch All Products
             */
            // Calling to Custom API for getting Product List
            try {
                $getProducts = $this->storeProducts($filters);
                if (isset($getProducts) && count($getProducts['data']) > 0) {
                    $storeResponse = [
                        'total_records' => $getProducts['records'],
                        'products' => $getProducts['data'],
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
     * Get: Get minimal product details from Opencart store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Array of one product details
     */
    public function getProductShortDetails($request, $response, $args)
    {
        $storeResponse = [];
        $productId = to_int($args['product_id']);
        $variantId = to_int($args['variant_id']);
        $optionId = $args['option_id'];
        $responseType = to_int($args['details']);

        if ($productId > 0 && $variantId > 0) {
            $productInfo = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'option_id' => $optionId,
                'details' => $responseType,
            ];
            try {
                $getLimitedDetails = $this->storeProductShortDetails($productInfo);
                if (!empty($getLimitedDetails)) {
                    $storeResponse = $getLimitedDetails;
                }
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
     * GET: Get list of category/subcategory or a Single category/subcategory
     * from the WooCommerce API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   23 Apr 2020
     * @return json of categorie(s)
     */
    public function getCategories($request, $response, $args)
    {
        $categories = [];
        $storeResponse = [];
        $productId = 0;
        // Get all requested Query params
        $name = $request->getQueryParam('name');
        if (!empty($args['id'])) {
            $productId = $args['id'];
        }
        // Set default option parameters
        $filters = [
            'page' => 1,
            'per_page' => 100,
            'order' => 'desc',
            'orderby' => 'id',
        ];
        /**
         * Filter process starts
         */
        if (!empty($productId)) {
            $endPoint .= '/' . $productId;
        }
        if (!empty($name)) {
            $filters += ['search' => $name];
        }
        // End of the filter
        try {
            $getCategories = $this->getStoreCategories($filters);
            /**
             * For single category listing, we get a 1D category array. But the
             * for-loop works for Multi-Dimentional Array. So to push the single
             * category array into the for-loop I converted the 1D array to Multi
             * dimentional array, so that foreach loop will be intact
             */
            if (empty($getCategories[0])) {
                $getCategories = [$getCategories];
            }
            if (!empty($getCategories[0])) {
                foreach ($getCategories as $key => $category) {
                    $categories[$key] = [
                        'id' => $category['id'],
                        'name' => htmlspecialchars_decode($category['name'], ENT_NOQUOTES),
                        'parent_id' => $category['parent_id'],
                    ];
                }
            }
            if (is_array($categories) && !empty($categories)) {
                $storeResponse = $categories;
            }
        } catch (\Exception $e) {
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get categories details',
                    ],
                ]
            );
        }

        return $storeResponse;
    }

    /**
     * Get: Product Attribute Pricing Details by Product Id from Opencart store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Array of All Product attributes price
     */
    public function storeProductAttrPrc($request, $response, $args)
    {
        $storeResponse = [];
        $productId = to_int($args['id']);
        $filters = [
            'product_id' => $productId,
        ];
        $options = [];
        foreach ($filters as $filterKey => $filterValue) {
            if (isset($filterValue) && $filterValue != "") {
                $options += [$filterKey => $filterValue];
            }
        }
        try {
            $singleProductAttrDetails = $this->getProductAttributes($productId,1);
            $storeResponse = $singleProductAttrDetails;
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
     * Post: Validate SKU or Name at Opencart store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Int product id if exist
     */
    public function validateStoreSkuName($request, $response)
    {
        $storeResponse = 0;
        $rootEndpoint = 'products';
        $allPostPutVars = (isset($saveType) && $saveType == 'update')
        ? $this->parsePut() : $request->getParsedBody();
        $filters = [];
        if (!empty($allPostPutVars['name'])) {
            $filters += [
                'search' => $allPostPutVars['name'],
            ];
        }
        if (!empty($allPostPutVars['sku'])) {
            $filters += [
                'sku' => $allPostPutVars['sku'],
            ];
        }
        try {
            $getProducts = $this->checkDuplicateNameAndSku($filters);
            if (!empty($getProducts[0]['id'])) {
                $storeResponse = $getProducts[0]['id'];
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
     * Get: Get all Attributes List from Store-end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Array of All store attributes
     */
    public function storeAttributeList($request, $response)
    {
        $storeResponse = [];
        $productId = $request->getQueryParam('product_id');
        if (!empty($productId)) {
            $attributeList = [];
            $attributeList = $this->getProductAttributes($productId);
            $storeResponse = $attributeList;
        } else {
            try {
                $getAllAttributes = $this->getProductAttributes(0);
                if (!empty($getAllAttributes)) {
                    $storeResponse = $getAllAttributes;
                }
            } catch (\Exception $e) {
                $storeResponse = [];
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Create Variations',
                        ],
                    ]
                );
            }
        }

        return $storeResponse;
    }

    /**
     * Get list of Color Variants from the WooCommerce API as per the product
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Json
     */
    public function colorsByProduct($request, $response, $args)
    {
        $storeResponse = [];
        $filters = [
            'product_id' => $args['product_id'],
            'attribute' => $args['slug'],
        ];
        try {
            $singleProductDetails = $this->getColorVariantsByProduct($filters);
            if (!empty($singleProductDetails)) {
                $storeResponse = $singleProductDetails;
            }
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
     * Get: Get all Attributes List from Store-end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Array list of Attributes
     */
    public function getOnlyAttribute($request, $response)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $filters = array(
            'type' => 'select',
        );
        try {
            $getAllAttributes = $this->getAttributes($filters);
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
     * Get: Get the list of product category wise from Magento store API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Array of products list
     */
    public function getToolProducts($request, $response)
    {
        $storeResponse = [];
        try {
            $getProducts = $this->getProductsByCategory();
            $storeResponse = $getProducts;
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get single attribute details',
                    ],
                ]
            );
        }

        return $storeResponse;
    }
    /**
     * Get: Total product count from Opencart store API
     *
     * @author mukeshp@riaxe.com
     * @date   25 April 2020
     * @return Array product total count
     */
    public function totalProductCount()
    {
        $totalCount = 0;
        try {
            $getCountDetails = $this->storeTotalProductCount();
            $totalCountDetails = $getCountDetails;
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get product count',
                    ],
                ]
            );
        }

        return $totalCountDetails;
    }

    /**
     * Post: Save predecorated products into the store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date   1 Jul 2020
     * @return Array records and server status
     */
    public function saveProduct($request, $response)
    {
        $storeResponse = [];
        $createVariation = true;
        $getPostData = (isset($saveType) && $saveType == 'update')
        ? $this->parsePut() : $request->getParsedBody();

        if (!empty($getPostData['data'])) {
            $predecorData = json_clean_decode($getPostData['data'], true);
            // print_r($predecorData);exit;
            $productSaveEndPoint = 'products';
            $mode = 'saved';
            if (isset($predecorData['product_id'])
                && $predecorData['product_id'] > 0
            ) {
                $productSaveEndPoint = 'products/' . $predecorData['product_id'];
                $mode = 'updated';
            }

            $productType = 'simple';
            if (!empty($predecorData['type'])) {
                $productType = $predecorData['type'];
            }

            // Setup a array of Basic Product attributes
            $productSaveData = [
                'product_name' => $predecorData['name'],
                'sku' => $predecorData['sku'],
                'product_type' => strtolower($productType),
                'description' => !empty($predecorData['description'])
                ? $predecorData['description'] : "",
                'short_description' => !empty($predecorData['short_description'])
                ? $predecorData['short_description'] : "",
                'qty' => $predecorData['quantity'],
                'catalog_visibility' => "visible",
                'ref_id' => $predecorData['ref_id'],
                'price' => $predecorData['price'],
                'product_id' => $predecorData['product_id'],
                'parent_product_id' => $predecorData['parent_product_id'],
                'is_customized' => $predecorData['is_redesign'],
                'attributes' => $predecorData['attributes'],
                'categories' => $predecorData['categories'],
            ];
            // Append Image Urls
            $productImages = [];
            $convertImageToSize = 500; // w*h pixel
            // If Images url are sent via json array Check for is_array()
            // because if they send images_url as string then it will not
            // satisfy. So if I check is_array() then if they send array or
            // string, both cond. will be stisfied
            if (is_array($predecorData['product_image_url'])
                && !empty($predecorData['product_image_url'])
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
                    $productImages['images'][] = [
                        'src' => $fileFetchPath . 'resize_' . $filenameToProcess,
                    ];
                }
            } else {
                // If Images are sent from front-end
                $uploadedFileNameList = do_upload(
                    'product_image_files', path('abs', 'product'), [], 'array'
                );
                foreach ($uploadedFileNameList as $uploadedImage) {
                    $productImages['images'][] = [
                        'src' => path('read', 'product') . $uploadedImage,
                    ];
                }
            }
            $productSaveData += $productImages;
            // Append Attributes by Looping through each Attribute
            $productAttributes = $getAttributeCombinations = [];
            // Process the Data to the Product's Post API
            try {
                $getProducts = $this->createPredecoratedProduct($productSaveData);
                // Call Another API for Create Variations
                if (!empty($getProducts['id'])) {
                    $storeResponse = [
                        'product_id' => $getProducts['id'],
                    ];
                    // Return variation id.
                    if ($productType == 'variable') {
                        if (!empty($variationResponse)) {
                            $storeResponse += [
                                'variation_id' => $getProducts['vid'],
                            ];
                        }
                    }
                }
                // End of Variation creation
            } catch (\Exception $e) {
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
     * @author mukeshp@riaxe.com
     * @date   21st July 2020
     * @return Array records and server status
     */
    public function storeMultiAttributeVariantDetails($request, $response, $args)
    {
        $storeResponse = [];
        $filteredAttributes = [];
        $productId = to_int($args['pid']);
        $variationId = to_int($args['vid']);
        $attribute = $args['attribute'];
        $details = $args['price'];
        try {
            $getProductVariantDetails = $this->getProductById($variationId);
            $getVariantOptionDetails = $this->getProductOptions($variationId);
            $finalArray = [];
            $attrKey = array_search($attribute,array_column($getVariantOptionDetails, 'option_title'));
            $length = count($getVariantOptionDetails) - 1;
            // print_r($getVariantOptionDetails);exit;
            foreach ($getVariantOptionDetails as $key => $value) {
                if ($key != $attrKey) {
                    $name  = $value['option_title'];
                    foreach ($value['option_values'] as $optKey => $optValue) {
                        $attributeList[$name.'_id'] = $value['option_id'];
                        $attributeList[$name] = $optValue['title'];
                    }
                }
            }
            foreach ($getVariantOptionDetails[$attrKey]['option_values'] as $optionKey => $optionValue) {
                $name  = $getVariantOptionDetails[$attrKey]['option_title'];
                $attributeList[$name.'_id'] = $getVariantOptionDetails[$attrKey]['option_id'];
                $attributeList[$name] = $optionValue['title'];
                $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['id'] = $getVariantOptionDetails[$attrKey]['option_id'];
                $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['name'] = $optionValue['title'];
                $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['variant_id'] = $variationId;
                if ($length == $attrKey || $details == 1) {
                    $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['price'] = $optionValue['price'];
                    $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['tier_prices'] = $this->getTierPrice($variationId,$optionValue['price']);
                    $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['inventory']['stock'] = $getProductVariantDetails['stock_quantity'];
                    $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['inventory']['min_quantity'] = 1;
                    $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['inventory']['max_quantity'] = $getProductVariantDetails['stock_quantity'];
                    $finalArray[$getVariantOptionDetails[$attrKey]['option_title']][$optionKey]['inventory']['quantity_increments'] = 1;
                    $finalArray[$attribute][$optionKey]['attributes'] = $attributeList;
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

    public function getParentProductID($variantId)
    {
        $parentProductId = $this->getProductIdByVariantID($variantId);
        return $parentProductId;
    }

    /**
     * POST: Product add to store
     *
     * @param $productData Product data
     * @param $catalog Catalog details
     *
     * @author mukeshp@riaxe.com
     * @date  07 Aug 2020
     * @return array json
     */
    public function addProductToStore($productData, $catalog)
    {
        ini_set('memory_limit', '1024M');

        $pro_id = 0;
        $productArr = [];
        if (!empty($productData)) {
            foreach ($productData as $k => $v) {
                $price = $v['catalog_price'];
                $catalog_price = $v['price'] ? $v['price'] : $price;
                $params = array('style_id' => $v['style_id'], "catalog_code" => $catalog);
                $returnData = api_call_by_curl($params, 'product');
                $predecorData = $returnData['data'];
                $sideName = $predecorData['variations'][0]['side_name'];
                $data = array('product' => json_encode($predecorData), 'price' => $catalog_price);
                if (!empty($predecorData)) {
                    $pro_id = $this->addCatalogProductToStore($data);
                    if ($pro_id) {
                        $productArr[$k]['product_id'] = $pro_id;
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
     * Get: get variants of a product
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mike@imprintnext.com
     * @date   29 July 2021
     * @return Array records
     */
    public function productVariants($request, $response, $args) {
        $variants = [];
        if (!empty($args['productID'])) {
            $storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
            $getProductVariantDetails = $this->getProductById($args['productID']);
            $getVariantOptionDetails = $this->getProductOptions($args['productID']);
            
            $variationCombination = array(array());
            foreach ($getVariantOptionDetails as $property => $propertyValues) {
                $tmp = array();
                foreach ($variationCombination as $resultItem) {
                    foreach ($propertyValues['option_values'] as $property_value) {
                        $tmp[] = array_merge($resultItem, array($property => $property_value['title']));
                    }
                }
                $variationCombination = $tmp;
            }

            foreach ($variationCombination as $key => $value) {
                $variationName = "";
                foreach ($value as $variationKey => $variationValue) {
                    if ($variationKey > 0) {
                        $variationName .= " / " . $variationValue;
                    } else {
                        $variationName = $variationValue;
                    }
                }
                $variants[$key]['id'] = $getProductVariantDetails['id'];
                $variants[$key]['title'] = $variationName;
                $variants[$key]['price'] = $getProductVariantDetails['price'];
            }
        }
        return $variants;
    }
}