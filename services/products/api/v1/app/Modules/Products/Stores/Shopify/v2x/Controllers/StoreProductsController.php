<?php
/**
 *
 * This Controller used to save, fetch or delete WooCommerce Products on various endpoints
 *
 * @category   Products
 * @package    WooCommerce API
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     tanmayap@riaxe.com
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @1.0
 */
namespace ProductStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

class StoreProductsController extends StoreComponent
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Get list of product from the Shopify API
     *
     * @author     tanmayap@riaxe.com
     * @date       24 sep 2019
     * @parameter  Slim default params
     * @response   Array of list/one product(s)
     */
    public function getProducts($request, $response, $args)
    {
        $productId = trim($args['id']);
        if (!empty($productId)) {
            $jsonResponse = $this->getProductById($request, $response, $args);
            return $jsonResponse;
        }
        $storeResponse = [];
        $products = [];
        $endPoint = 'products';

        $searchstring = $request->getQueryParam('name');
        $categoryid = $request->getQueryParam('category');
        $limit = $request->getQueryParam('per_page');
        $page = $request->getQueryParam('page');
        $orderby = $request->getQueryParam('orderby');
        $order = $request->getQueryParam('order');
        $listCatalogue = (!empty($request->getQueryParam('is_catalog'))? $request->getQueryParam('is_catalog') : 0);
        $fetchFilter = (!empty($request->getQueryParam('fetch'))? $request->getQueryParam('fetch') : 'limited');
        $isCustomize = $request->getQueryParam('is_customize'); //For Predeco Product

        // Get all requested Query params
        $filters = [
            'limit' => $limit,
            'page' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'published_status' => 'published',
            'isCatalogue' => $listCatalogue,
        ];
        if ($categoryid && $categoryid != '') {
            $filters['collection_id'] = $categoryid;
        } else {
            $filters['collection_id'] = '';
        }
        if ($searchstring && $searchstring != '') {
            $filters['title'] = $searchstring;
        }

        try {
            $getProducts = $this->getAllProducts($filters, $isCustomize);
            $getTotalProductsCount = $this->getTotalProductsCount($filters, $isCustomize);
            foreach ($getProducts as $key => $product) {
                $productImages = [];
                if (isset($product['images']) && count($product['images']) > 0) {
                    foreach ($product['images'] as $prodImgKey => $prodImg) {
                        $productImages[$prodImgKey] = $prodImg['src'];
                    }
                }

                //Choosing variant price as product price
                $productPrice = 10000000;
                $isSoldOut = true;
                foreach ($product['variants'] as $pv) {
                    if (!empty($productId)) {
                        //For product details
                        if ($pv['price'] < $productPrice) {
                            $productPrice = array(
                                'current' => $pv['price'],
                                'regular_price' => $pv['price'],
                                'sale_price' => $pv['price'],
                            );
                            break;
                        }
                    } else {
                        if ($pv['price'] < $productPrice) {
                            $productPrice = $pv['price'];
                            break;
                        }
                        if ($pv['inventory_policy'] =="continue" || $pv['inventory_quantity'] > 0 || !empty($pv['inventory_quantity'])) {
                            $isSoldOut = false;
                            break;
                        }
                    }
                }

                $productType = (strpos(strtolower($product['variants'][0]['title']), 'default title') !== false ? "simple" : "variable");
                $stock = 0;
                foreach ($product['variants'] as $var) {
                    if ($var['inventory_policy'] =="continue" || $var['inventory_quantity'] > 0) {
                        $stock = 1;
                        break;
                    }
                }
                $products[$key] = [
                    'id' => $product['id'],
                    'variation_id' => $product['variants'][0]['id'],
                    'name' => $product['title'],
                    'stock' => $stock,
                    'type' => $productType,
                    'is_sold_out' => ($stock > 0?false:true),
                    'sku' => (count($product['variants']) > 0) ? $product['variants'][0]['sku'] : '',
                    'is_decoration_exists' => $isDecorationExists,
                    'print_profile' => $printProfiles,
                    'price' => $productPrice,
                    'image' => $productImages,
                ];
                if ($fetchFilter == 'all') {
                    $preDecoMeta = $this->GetProductMeta($product['id']);
                    if (!empty($preDecoMeta) && is_array($preDecoMeta)) {
                       $products[$key]['custom_design_id'] = $preDecoMeta['custom_design_id'];
                       $products[$key]['is_redesign'] = $preDecoMeta['is_redesign'];
                       $products[$key]['is_decorated_product'] = ($preDecoMeta['custom_design_id']> 0?1:0);
                    }
                }
            }

            if (isset($products) && is_array($products) && count($products) > 0) {
                $storeResponse = [
                    'status' => 1,
                    'total_records' => $getTotalProductsCount,
                    'products' => $products,
                ];
            } else {
                $storeResponse = [
                    'status' => 0,
                    'total_records' => $getTotalProductsCount,
                    'message' => 'No products available',
                    'products' => [],
                ];
            }

        } catch (\Exception $e) {
            $storeResponse = [
                'status' => 0,
                'message' => 'Invalid request',
                'exception' => $e->getMessage(),
            ];
        }
        // Reset Total product Count
        $_SESSION['productsCount'] = 0;
        return $storeResponse;
    }

    /**
     * Get product details from the Shopify API
     *
     * @author     tanmayap@riaxe.com
     * @date       23 Dec 2019
     * @parameter  Slim default params
     * @response   Array of list/one product(s)
     */
    public function getProductById($request, $response, $args)
    {
        $products = [];
        try {
            $product = $this->getProductDetails($args['id']);
        } catch (\Exception $e) {
            $storeResponse = [
                'status' => 0,
                'message' => 'Invalid request',
                'exception' => $e->getMessage(),
            ];
        }

        // Reset Total product Count
        $_SESSION['productsCount'] = 0;
        return $product;
    }

    /**
     * Get list of category/subcategory or a Single category/subcategory from the Shopify store
     *
     * @author     ramasankarm@riaxe.com
     * @date       14 dec 2019
     * @parameter  Slim default params
     * @response   Array of list/one categories/category
     */
    public function getCategories($request, $response, $args)
    {
        $categories = [];
        try {
            $categories = $this->getCollections();
            if (!empty($categories)) {
                $storeResponse = $categories;
            }
        } catch (\Exception $e) {
            $serverStatusCode = EXCEPTION_OCCURED;
            $storeResponse = [
                'status' => 0,
                'message' => 'Invalid request',
                'exception' => $e->getMessage(),
            ];
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
     * @author tapasranjanp@riaxe.com
     * @date   28 feb 2020
     * @return Array of list/one product(s)
     */
    public function getProductShortDetails($request, $response, $args)
    {
        $storeResponse = [
            'status' => 0,
            'data' => [],
        ];

        $productId = to_int($args['product_id']);
        $variantId = to_int($args['variant_id']);
        $responseType = to_int($args['details']);

        if ($productId > 0 && $variantId > 0) {
            try {
                $productInfo = array(
                    'variantID' => $variantId,
                    'productID' => $productId,
                );
                $productData = [];
                $storeResponse = $this->getVariantShortInfo($productInfo);
            } catch (\Exception $e) {
                $storeResponse['exception'] = show_exception() === true
                ? $e->getMessage() : '';
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
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    public function colorsByProduct($request, $response, $args)
    {
        $storeResponse = [
            'status' => 1,
            'records' => 0,
            'message' => 'No Color available',
            'data' => [],
        ];
        $filters = [
            'product_id' => $args['product_id'],
            'attribute' => $args['slug'],
        ];
        $options = [];
        $variantData = [];
        foreach ($filters as $filterKey => $filterValue) {
            if (isset($filterValue) && $filterValue != "") {
                $options += [$filterKey => $filterValue];
            }
        }
        try {
            $storeResponse = $this->getColorOptions($options);
        } catch (\Exception $e) {
            $jsonResponse = [
                'status' => 0,
                'message' => message('Products', 'error'),
                'exception' => show_exception() === true ? $e->getMessage() : '',
            ];
        }

        return $storeResponse;
    }

    /**
     * Get: Get variation's attribute details by variant ID
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument
     *
     * @author tanmayap@riaxe.com
     * @date   13 Feb 2020
     * @return Array records and server status
     */
    public function storeVariantAttributeDetails($request, $response, $args)
    {
        $storeResponse = [
            'status' => 0,
            'message' => message('Quantity', 'error'),
        ];
        $filteredAttributes = $params = [];
        $params['productId'] = $args['pid'];
        $params['variantId'] = $args['vid'];
        $option['per_page'] = 100;
        $filteredAttributes = $this->getVariantInventory($params);
        if (!empty($filteredAttributes)) {
            $storeResponse = $filteredAttributes;
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
     * @date   04 March 2020
     * @return Array list of Attributes
     */
    public function getOnlyAttribute($request, $response)
    {
        $serverStatusCode = 0;
        $getStoreDetails = get_store_details($request);
        $storeResponse = [
            'status' => 0,
            'data' => message('Store Attributes', 'not_found'),
        ];
        $attributeList = [];
        $filters = array(
            'type' => 'select',
            'store' => $getStoreDetails['store_id'],
        );
        $getAllAttributes = array(
            0 => array(
                'id' => 'color',
                'name' => 'color',
            ),
            1 => array(
                'id' => 'size',
                'name' => 'size',
            ));
        if (isset($getAllAttributes) && count($getAllAttributes) > 0) {
            $storeResponse = $getAllAttributes;
        }
        return $storeResponse;
    }

    public function getOriginalVarID($variantID)
    {
        $thisVariantId = $this->getParentVariantID($variantID);
        return $thisVariantId;
    }

    /**
     * Get: Product Attribute Pricing Details by Product Id from Shopify store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author debashisd@riaxe.com
     * @date   18 March 2020
     * @return All store attributes
     */
    public function storeProductAttrPrc($request, $response, $args)
    {
        $storeResponse = [];
        try {
            $productId = trim($args['id']);
            $result = $this->getAttributes($productId);
            if (!empty($result)) {
                $storeResponse = $result;
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
     * Get: Get all Attributes List from Store-end
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashisd@riaxe.com
     * @date   19 Mar 2019
     * @return Array list of Attributes
     */
    public function storeAttributeList($request, $response)
    {
        $storeResponse = [];
        $productId = $request->getQueryParam('product_id');
        if (!empty($productId)) {
            $attributeList = [];
            $getProductDetail = $this->getAttributes($productId);
            if (!empty($getProductDetail)) {
                foreach ($getProductDetail as $attribute) {
                    $attributeList[] = [
                        'id' => $attribute['id'],
                        'name' => $attribute['name'],
                        'terms' => $attribute['options'],
                    ];
                }

                $storeResponse = $attributeList;
            }
        } else {
            try {
                $getAllAttributes = $this->getAttributes();
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
     * Get: Get all Attributes List from Store-end
     *
     * @author debashisd@riaxe.com
     * @return Shopify main product ID
     */
    public function getParentProductData($variantID)
    {
        $parentVarID = $this->getParentVariantID($variantID);
        $productID = $this->shopifyParentProductID($parentVarID);
        print_r($productID);exit();
    }


    /**
     * Get: get variants of a product
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashisd@riaxe.com
     * @date   23 July 2020
     * @return Array records
     */
    public function productVariants($request, $response, $args)
    {
        $variants = [];
        if (!empty($args['productID'])) {
            $variants = $this->getStoreVariants($args['productID']);
        }
        return $variants;
    }

    /**
     * Get: get tier Details of a product
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashisd@riaxe.com
     * @date   23 July 2020
     * @return Array records
     */
    public function productTierDiscounts($request, $response, $args)
    {
        $tierContent = [];
        if (!empty($args['productID'])) {
            $tierContent = $this->getTierDiscounts($args['productID']);
        }
        return $tierContent;
    }

    /**
     * Post: Save tier pricing
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashisd@riaxe.com
     * @date   23 July 2020
     * @return Boolean status
     */
    public function saveTierDiscount($request, $response, $args){
        $tierData = $request->getParsedBody();
        $tierData['productID'] = $args['productID'];
        $tierData['price_rules'] = json_decode($tierData['price_rules'], true);
        $tierStatus = $this->saveProductTierPrice($tierData);
        return $tierStatus;
    }

    /**
     * Get: Get Attribute List for Variants with Multiple attribute
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument
     *
     * @author debashisd@riaxe.com
     * @date   10th June 2020
     * @return Array records and server status
     */
    public function storeMultiAttributeVariantDetails($request, $response, $args)
    {
        $storeResponse = [];
        $filteredAttributes = [];
        $productId = to_int($args['pid']);
        $variationId = to_int($args['vid']);
        $attribute = $args['attribute'];
        $option['per_page'] = 100;
        try {
            $storeData = array("productID" => $productId, "variantID" => $variationId, "option" => $attribute);
            $storeData['is_price'] = !empty($request->getQueryParam('price')) ? $request->getQueryParam('price') : 0;
            $finalArray = $this->getProductAttrOptions($storeData);
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

    public function getProductByType($request, $response, $args)
    {
        $storeResponse = [
            'status' => 0,
            'message' => message('Product', 'error'),
        ];
        $foundProduct = $this->storeProductByType($args);
        if (!empty($foundProduct)) {
            $storeResponse = $foundProduct;
        }
        return $storeResponse;
    }

    /**
     * Post: Save predecorated products into the store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashisd@riaxe.com
     * @date   1 May 2019
     * @return Array records and server status
     */
    public function saveProduct($request, $response)
    {
        $storeResponse = $productImages = [];
        $createVariation = true;
        $getPostData = (isset($saveType) && $saveType == 'update')
        ? $this->parsePut() : $request->getParsedBody();

        if (!empty($getPostData['data'])) {
            $predecorData = json_clean_decode($getPostData['data'], true);
            if (is_array($predecorData['product_image_url'])
                    && empty($predecorData['product_image_url'])
                ) {
                $uploadedFileNameList = do_upload(
                    'product_image_files', path('abs', 'product'), [], 'array'
                );
                foreach ($uploadedFileNameList as $uploadedImage) {
                    $productImages[] = path('read', 'product') . $uploadedImage;
                }
                $predecorData['product_image_url'] = $productImages;
            }
            foreach ($predecorData['attributes'] as $attrKey => $attributes) {
                foreach ($attributes['attribute_options'] as $optKey => $option) {
                    $predecorData['attributes'][$attrKey]['attribute_options'][$optKey] = addslashes($option);
                }
            }
            $storeResponse = $this->createDecoratedProduct($predecorData);
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
    public function addProductToStore($productData, $catalog, $priceData , $storeID)
    {
        $productArr = [];
        if (!empty($productData)) {
            foreach ($productData as $k => $v) {
                $price = $v['price'];
                $storeCategories = $v['categories'];
                $catalog_price = $v['catalog_price'] ? $v['catalog_price'] : $price;
                $params = array('style_id' => $v['style_id'], "catalog_code" => $catalog);
                $returnData = api_call_by_curl($params, 'product');
                $predecorData = $returnData['data'];
                $predecorData['old_product_id'] = $v['old_product_id'];
                $sideName = $predecorData['variations'][0]['side_name'];
                if (!empty($predecorData)) {
                    $productId = $this->addCatalogProductToStore($priceData, $predecorData, $price, $catalog_price, $storeCategories);
                    if ($productId) {
                        $productArr[$k]['product_id'] = $productId;
                        $productArr[$k]['product_side'] = $sideName;
                        $productArr[$k]['style_id'] = $v['style_id'];
                        $productArr[$k]['decorData'] = $catalogProductData;

                    }
                }
            }
        }
        return $productArr;
    }

    public function createProductImportCSV($request, $response, $args){
        $getStoreDetails = get_store_details($request);
        $predecoDetails = $request->getParsedBody();
        $productData = json_clean_decode($predecoDetails['product_data']);
        $catalog = $predecoDetails['catalog_code'];
        $assetsPath = path('abs', 'assets');
        $cataloAssetsPath = $assetsPath . 'catalog';
        if (!is_dir($cataloAssetsPath)) {
            mkdir($cataloAssetsPath, 0755);
        }
        $headerData = [
                "Title", "Body (HTML)", "Vendor", "Type", "Tags", "Published", "Option1 Name", "Option1 Value", "Option2 Name", "Option2 Value", "Option3 Name", "Option3 Value", "Variant SKU", "Variant Grams", "Variant Inventory Tracker", "Variant Inventory Qty", "Variant Inventory Policy", "Variant Fulfillment Service", "Variant Price", "Variant Compare At Price", "Variant Requires Shipping", "Variant Taxable", "Variant Barcode", "Image Src", "Image Position", "Gift Card", "Variant Image", "Variant Weight Unit", "Cost per item"
            ];
            $rowData = $productData;
            $randNo = getRandom();
            $csvFilename = $randNo . '.csv';
            if (!empty($productData)) {
                $productArray = [];
                $productArray[0] = $headerData;
                $i = 1;
                $variants = [];
                foreach ($productData as $k => $v) {
                    $price = $v['price'];
                    $catalog_price = $v['catalog_price'];
                    $params = array("catalog_code" => $catalog, 'style_id' => $v['style_id']);
                    $returnData = api_call_by_curl($params, 'product');
                    $productData = $returnData['data'];
                    $category = $categories = '';
                    foreach ($productData['category'] as $key => $cat) {
                        $category .= $cat . '>';
                    }

                    $categories = rtrim($category, ">");
                    $arraySize = $productData['size_data'];
                    $arrayColor = $productData['color_data'];
                    $color = $colors = '';
                    if (!empty($arrayColor)) {
                        foreach ($arrayColor as $cl) {
                            $color .= $cl . ', ';
                        }
                        $colors = rtrim($color, ', ');
                    }

                    $size = $sizes = '';
                    if (!empty($arraySize)) {
                        foreach ($arraySize as $sz) {
                            $size .= $sz . ', ';
                        }
                        $sizes = rtrim($size, ', ');
                    }
                    $productImageUrl = $productData['images']['src'];
                    $stock = $predecorData['total_qty'];
                    $productArray[] = [
                      $productData['name'], $productData['description'], $productData['variations'][0]['brand_name'], $productData['variations'][0]['style_name'],  "catalogue", "true", $productData['attributes'][0]['name'], $productData['attributes'][0]['options'][0], $productData['attributes'][1]['name'], $productData['attributes'][1]['options'][0], $productData['attributes'][2]['name'], $productData['attributes'][2]['options'][0], $productData['sku'], $productData['variations'][0]['unit_weight'], "shopify", $stock, "deny", "manual", $price, "", "true", "true", "", $productImageUrl, 1, "", "", "gm", $productData['variations'][0]['piece_price']
                    ];
                    if (!empty($productData['variations'])) {
                        $j = 0;
                        if (count($productData['variations']) > 1) {
                            foreach ($productData['variations'] as $keys => $variations) {
                                if ($keys > 0) {
                                    $quantity = $variations['quantity'];
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
                                    $randNos = getRandom();
                                    $options = array_values($variations['attributes']); 
                                    $productArray[] = [
                                        $productData['name'], "", "", "",  "", "", "", $options[0], "", $options[1], "", $options[2], $productData['variations'][$keys]['sku'], $productData['variations'][$keys]['unit_weight'], "shopify", $quantity, "deny", "manual", $price, "", "true", "true", "", $productData['variations'][$keys]['image_path'][0], "", "", "", "gm", $productData['variations'][$keys]['piece_price']
                                    ];
                                    $j++;
                                }
                            }
                        }
                    }
                    $i++;
                    $newArr = $productArray;
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
            }

        return $csvFilename;
    }

    /**
     * Get total product count from the WooCommerce API
     *
     * @author debashisd@riaxe.com
     * @date   23 August 2020
     * @return count
     */
    public function totalProductCount()
    {
        $totalCount = 0;
        try {
            $getCountDetails = $this->getTotalProductsCount(array(), "");
            $totalCountDetails = array("total" =>$getCountDetails);
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
     * Post: Create product catagories/subcategories.
     *
     * @param $request       Slim's Request object
     * @param $response      Slim's Response object
     *
     * @author devon@imprintnext.com
     * @date   17 Mar 2021
     * @return Array records and server status
     */
    public function createProductCatagories($request, $response) {
        $storeResponse = [];
        $getPostData = $request->getParsedBody();
        $catName = $getPostData['name'];
        $catId = $getPostData['catId']; 
        $storeResponse = $this->addCollection($catName);
        return $storeResponse;
    }

    /**
     * Internal: Saves product API data in cache files 
     * for Shopify API call limitation.
     *
     * @param $productID       Product ID
     *
     * @author devon@imprintnext.com
     * @date   24 AUG 2021
     * @return Array records and server status
     */
    public function saveProductAPIasCache($productID){
        $shopifyProductID = $productID;
        if (!is_dir(SHOPIFY_CACHE_FOLDER)) {
          mkdir(SHOPIFY_CACHE_FOLDER);
        }
        $variantsDIR = SHOPIFY_CACHE_FOLDER . "variants/";
        if (!is_dir($variantsDIR)) {
          mkdir($variantsDIR);
        }
        $productData = $this->getShopifyProductInfo($productID);
        if (!empty($productData)) {
            $thisProdCacheFile = SHOPIFY_CACHE_FOLDER . $productID . ".json";
            file_put_contents($thisProdCacheFile, json_encode($productData));
            foreach ($productData['variants'] as $variant) {
                $thisVarCacheFile = $variantsDIR . $variant['id'] . ".json";
                file_put_contents($thisVarCacheFile, json_encode($variant));
            }
        }
    }
}
