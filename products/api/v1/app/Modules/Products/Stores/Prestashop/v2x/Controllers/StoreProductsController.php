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
        $shopId = $request->getQueryParam('store_id')
        ? $request->getQueryParam('store_id') : 1;

        if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
            $sanitizedProduct = [];
            // For fetching Single Product
            $productId = $args['id'];
            $productData = [];
            $parameters = array(
                'resource' => 'products', 'display' => 'full',
                'filter[id]' => '[' . $productId . ']',
                'id_shop' => $shopId,
                'output_format' => 'JSON',
                'language' => '' . $this->languageId . '',
            );

            try {

                $result = $this->webService->get($parameters);
                $productObj = json_decode($result, true);
                $product = $productObj['products'][0];
                $productParameter = array(
                    'product_id' => $productId,
                );
                $productStock = 0;
                $productCombinationStock = $product['associations']['combinations'];
                if (!empty($productCombinationStock)) {
                    foreach ($productCombinationStock as $key => $combination) {
                        $productStock += $this->webService->getProductStock(0,
                            $combination['id']
                        );
                    }
                } else {
                    $productStock += $this->webService->getProductStock($productId, 0);
                }
                if (!empty($product['associations']['categories'])) {
                    foreach ($product['associations']['categories'] as $k => $v) {
                        $category = $this->getCategories(
                            $request, $response, [
                                'id' => $v['id'],
                            ]
                        );
                        $productCategories[$k]['id'] = $category[0]['id'];
                        $productCategories[$k]['name'] = $category[0]['name'];
                        $productCategories[$k]['slug'] = $category[0]['slug'];
                        if ($category[0]['parent_id'] == 2 || $category[0]['parent_id'] == 1) {
                            $parentId = 0;
                        } else {
                            $parentId = $category[0]['parent_id'];
                        }
                        $productCategories[$k]['parent_id'] = $parentId;
                    }
                }
                if (is_array($product) && count($product) > 0) {
                    $variantId = $product['id_default_combination'];
                    $sanitizedProduct['id'] = $product['id'];
                    $sanitizedProduct['name'] = $product['name'];
                    $sanitizedProduct['variant_id'] = $variantId == 0
                    ? $productId : $variantId;
                    $sanitizedProduct['type'] = $variantId == 0 ? 'simple' : 'variable';
                    if ($product['price'] == 0 && $variantId) {
                        $price = $this->webService->getCombinationPrice($variantId);
                    } else {
                        $price = $product['price'];
                    }
                    $sanitizedProduct['price'] = $price;
                    $sanitizedProduct['tax'] = $this->webService->getTaxRate($productId);
                    $sanitizedProduct['sku'] = $product['reference'];
                    $sanitizedProduct['stock_quantity'] = $productStock;
                    $sanitizedProduct['description'] = $product['description'];
                    $sanitizedProduct['categories'] = $productCategories;
                    //call to prestashop webservice for get product images
                    $imageArr = $this->webService->getProducImage(
                        $variantId, $productId
                    );
                    $images = array();
                    if (!empty($imageArr)) {
                        $i = 0;
                        foreach ($imageArr as $image) {
                            $images[$i]['src'] = $image['src'];
                            $images[$i]['thumbnail'] = $image['thumbnail'];
                            $i++;
                        }
                        $sanitizedProduct['images'] = $images;
                    }
                    if (empty($images)) {
                        $imageIdArr = $this->webService->getProductImageByPid(
                            $productId, $shopId
                        );
                        if (sizeof($imageIdArr) > 0) {
                            foreach ($imageIdArr as $k => $imageId) {
                                // get image full URL
                                $thumbnail = $this->webService->getProductThumbnail(
                                    $imageId['id_image']
                                );
                                $productImage = $this->webService->getProductImage(
                                    $imageId['id_image']
                                );
                                $sanitizedProduct['images'][$k]['src'] = $productImage;
                                $sanitizedProduct['images'][$k]['thumbnail'] = $thumbnail;
                            }
                        }
                    }
                }

                $combinations = $this->webService->getAttributeCombinations(
                    $productParameter
                );
                if (!empty($combinations)) {
                    foreach ($combinations as $key => $comb) {
                        $obj = array();
                        if (($comb['is_color_group'] == '1')
                            && (!in_array($comb['attribute_name'], $colorArray))
                        ) {
                            $colorId = $comb['id_attribute_group'];
                            $colorName = $comb['group_name'];
                            array_push($colorArray, $comb['attribute_name']);
                            $obj['id'] = $comb['id_attribute'];
                            $obj['name'] = $comb['attribute_name'];
                            array_push($productColourVariations, $obj);
                        } else if (($comb['is_color_group'] == '0')
                            && (!in_array($comb['attribute_name'], $sizeArray))
                        ) {
                            $sizeId = $comb['id_attribute_group'];
                            $sizeName = $comb['group_name'];
                            array_push($sizeArray, $comb['attribute_name']);
                            $obj['id'] = $comb['id_attribute'];
                            $obj['name'] = $comb['attribute_name'];
                            array_push($productSizeVariations, $obj);
                        } else {
                            if (!in_array($comb['attribute_name'], $extraAttributeArray) && ($comb['is_color_group'] == '0') && ($comb['group_name'] != $sizeGroup)) {
                                $extraAttributeId = $comb['id_attribute_group'];
                                $extraAttributeName = $comb['group_name'];
                                array_push($extraAttributeArray, $comb['attribute_name']);
                                $obj['id'] = $comb['id_attribute'];
                                $obj['name'] = $comb['attribute_name'];
                                array_push($productExtraVariations, $obj);

                            }
                        }
                    }
                    if (!empty($productColourVariations)) {
                        $sanitizedProduct['attributes'][0]['id'] = $colorId;
                        $sanitizedProduct['attributes'][0]['name'] = $colorGroup;
                        $sanitizedProduct['attributes'][0]['options'] = array_values(
                            $productColourVariations
                        );
                    }
                    if (!empty($productSizeVariations)) {
                        $sanitizedProduct['attributes'][1]['id'] = $sizeId;
                        $sanitizedProduct['attributes'][1]['name'] = strtolower($sizeGroup);
                        $sanitizedProduct['attributes'][1]['options'] = array_values(
                            $productSizeVariations
                        );
                    }
                    if (!empty($productExtraVariations)) {
                        $sanitizedProduct['attributes'][2]['id'] = $extraAttributeId;
                        $sanitizedProduct['attributes'][2]['name'] = strtolower($extraAttributeName);
                        $sanitizedProduct['attributes'][2]['options'] = array_values(
                            $productExtraVariations
                        );
                    }
                    $sanitizedProduct['attributes'] = array_values(
                        $sanitizedProduct['attributes']
                    );
                } else {
                    $sanitizedProduct['attributes'] = [];
                }
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
            // For fetching All Product by filteration
            $getTotalProductsCount = 0;
            $isDecoratedProduct = 0;
            $filterArray = array('type' => array('eq' => 'configurable'));
            $searchstring = $request->getQueryParam('name')
            ? $request->getQueryParam('name') : '';
            $shopId = $request->getQueryParam('store_id')
            ? $request->getQueryParam('store_id') : 1;
            $categoryid = $request->getQueryParam('category')
            ? $request->getQueryParam('category') : 0;
            $page = $request->getQueryParam('page')
            ? $request->getQueryParam('page') : 1;
            $perpage = $request->getQueryParam('per_page')
            ? $request->getQueryParam('per_page') : 40;
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
            $fetchAll = $request->getQueryParam('fetch')
            ? $request->getQueryParam('fetch') : '';
            $offset = ($page - 1) * $perpage;
            $sort = $orderby . '_' . $order;
            try {
                $parameter = array(
                    'resource' => 'products',
                    'display' => '[id,id_default_combination,name,reference,price,xe_is_temp,is_catalog,customize]',
                    'filter[name]' => '%[' . $searchstring . ']%',
                    'sort' => '[' . $sort . ']',
                    'limit' => $offset . ',' . $perpage . '',
                    'output_format' => 'JSON',
                    'language' => '' . $this->languageId . '',
                    'id_shop' => $shopId,
                );
                if ($isCustomize == 0 && $fetchAll == '') {
                    $parameter['filter[xe_is_temp]'] = $isCustomize;
                }
                $option = array(
                    'page_number' => $page,
                    'nb_products' => $perpage,
                    'order_by' => 'id_product',
                    'order_way' => $order,
                    'category_id' => $categoryid,
                );
                if ($categoryid) {
                    $parameter['filter[id_category_default]'] = $categoryid;
                }
                $productCountParam = 'all';
                if ($orderby == 'top') {
                    $productArray = $this->webService->getPopularProducts($option);
                } else {
                    $productJson = $this->webService->get($parameter);
                    $products = json_decode($productJson, true);
                    if (is_array($products['products'])
                        && count($products['products']) > 0
                    ) {
                        $i = 0;
                        if ($isCustomize) {
                            $productCountParam = 'predeco';
                            foreach ($products['products'] as $v) {
                                if ($v['xe_is_temp'] > 0 || $v['xe_is_temp'] == -2) {
                                    $isDecoratedProduct = 1;
                                    $productId = $v['id'];
                                    $imageIdArr = $this->webService->getProductImageByPid(
                                        $productId, $shopId
                                    );
                                    // get Image by id
                                    if (sizeof($imageIdArr) > 0) {
                                        foreach ($imageIdArr as $imageId) {
                                            $thumbnail = $this->webService->getProductThumbnail(
                                                $imageId['id_image']
                                            );
                                            $productArray[$i]['image'][] = $thumbnail;
                                        }
                                    }
                                    $productArray[$i]['id'] = $productId;
                                    $variationId = ($v['id_default_combination'] == 0
                                        ? $productId : $v['id_default_combination']);
                                    $productArray[$i]['variation_id'] = $variationId;
                                    $productArray[$i]['name'] = $v['name'];
                                    $productArray[$i]['type'] = $v['id_default_combination'] == 0
                                    ? 'simple' : 'variable';
                                    $productArray[$i]['sku'] = $v['reference'];
                                    $productArray[$i]['price'] = $v['price'];
                                    $productArray[$i]['custom_design_id'] = $v['xe_is_temp'];
                                    $productArray[$i]['is_decorated_product'] = $isDecoratedProduct;
                                    $productArray[$i]['is_redesign'] = $v['customize'];
                                    $i++;
                                }
                            }
                        } elseif ($isCatalog) {
                            $productCountParam = 'catalog';
                            foreach ($products['products'] as $v) {
                                if ($v['is_catalog'] > 0) {
                                    $productId = $v['id'];
                                    $imageIdArr = $this->webService->getProductImageByPid(
                                        $productId, $shopId
                                    );
                                    // get Image by id
                                    if (sizeof($imageIdArr) > 0) {
                                        foreach ($imageIdArr as $imageId) {
                                            $thumbnail = $this->webService->getProductThumbnail(
                                                $imageId['id_image']
                                            );
                                            $productArray[$i]['image'][] = $thumbnail;
                                        }
                                    }
                                    if ($v['xe_is_temp'] > 0 || $v['xe_is_temp'] == -2) {
                                        $isDecoratedProduct = 1;
                                    }
                                    $productArray[$i]['id'] = $productId;
                                    $variationId = ($v['id_default_combination'] == 0
                                        ? $productId : $v['id_default_combination']);
                                    $productArray[$i]['variation_id'] = $variationId;
                                    $productArray[$i]['name'] = $v['name'];
                                    $productArray[$i]['type'] = $v['id_default_combination'] == 0
                                    ? 'simple' : 'variable';
                                    $productArray[$i]['sku'] = $v['reference'];
                                    $productArray[$i]['price'] = $v['price'];
                                    $productArray[$i]['custom_design_id'] = $v['xe_is_temp'];
                                    $productArray[$i]['is_decorated_product'] = $isDecoratedProduct;
                                    $productArray[$i]['is_redesign'] = $v['customize'];
                                    $i++;
                                }
                            }
                        } else {

                            foreach ($products['products'] as $v) {
                                $productId = $v['id'];
                                $imageIdArr = $this->webService->getProductImageByPid(
                                    $productId, $shopId
                                );
                                // get Image by id
                                if (sizeof($imageIdArr) > 0) {
                                    foreach ($imageIdArr as $imageId) {
                                        $thumbnail = $this->webService->getProductThumbnail(
                                            $imageId['id_image']
                                        );
                                        $productArray[$i]['image'][] = $thumbnail;
                                    }
                                }
                                //checking the product stock
                                $productStock = $this->webService->getProductStock($productId,0);
                                if($productStock > 0){
                                    $productArray[$i]['is_sold_out'] = false;
                                }else{
                                    $productArray[$i]['is_sold_out'] = true;
                                }
                                if ($v['xe_is_temp'] > 0 || $v['xe_is_temp'] == -2) {
                                    $isDecoratedProduct = 1;
                                }

                                $productArray[$i]['id'] = $productId;
                                $variationId = ($v['id_default_combination'] == 0
                                    ? $productId : $v['id_default_combination']);
                                $productArray[$i]['variation_id'] = $variationId;
                                $productArray[$i]['name'] = $v['name'];
                                $productArray[$i]['type'] = $v['id_default_combination'] == 0
                                ? 'simple' : 'variable';
                                $productArray[$i]['sku'] = $v['reference'];
                                $productArray[$i]['price'] = $v['price'];
                                $productArray[$i]['stock'] = $productStock;
                                $productArray[$i]['custom_design_id'] = $v['xe_is_temp'];
                                $productArray[$i]['is_decorated_product'] = $isDecoratedProduct;
                                $productArray[$i]['is_redesign'] = $v['customize'];
                                $i++;
                            }
                        }
                        $productArray = array_values($productArray);
                    }
                }
                if (isset($productArray) && count($productArray) > 0) {
                    $getTotalProductsCount = $this->webService->countProducts($productCountParam, $searchstring);
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
        $responceCategories = [];
        if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
            $categoryId = $args['id'];
            $productData = [];
            $filters = [
                'resource' => 'categories',
                'display' => 'full',
                'filter[id]' => '[' . $categoryId . ']',
                'output_format' => 'JSON',
                'language' => '' . $this->languageId . '',
            ];

            $result = $this->storeApiCall($filters);
            $categoryDetails = json_decode($result, true);
            $i = 0;
            foreach ($categoryDetails['categories'] as $v) {
                $responceCategories[$i]['id'] = $v['id'];
                $responceCategories[$i]['name'] = $v['name'];
                $responceCategories[$i]['slug'] = $v['name'];
                $responceCategories[$i]['parent_id'] = $v['id_parent'];
                $i++;
            }
            if (!empty($responceCategories) && count($responceCategories) > 0) {
                $storeResponse = $responceCategories;
            }
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

                $result = $this->storeApiCall($filters);
                $categoryDetails = json_decode($result, true);
                $i = 0;
                foreach ($categoryDetails['categories'] as $v) {
                    if ($v['id_parent'] >= 2 && $v['name'] != 'ROOT') {
                        $responceCategories[$i]['id'] = $v['id'];
                        $responceCategories[$i]['name'] = $v['name'];
                        $responceCategories[$i]['slug'] = $v['name'];
                        if ($v['id_parent'] == 2 || $v['id_parent'] == 1) {
                            $idParent = 0;
                        } else {
                            $idParent = $v['id_parent'];
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
            //call to prestashop webservice for get all product price
            $productPrice = $this->webService->getProductPriceByPid($productId);
            //call to prestashop webservice for get all product combination
            $combinations = $this->webService->getAttributeCombinations($filters);
            $colorArray = $productDetails = array();
            foreach ($combinations as $key => $comb) {
                $variantData = [];
                if (($comb['is_color_group'] == '1')
                    && (!in_array($comb['attribute_name'], $colorArray))
                ) {
                    array_push($colorArray, $comb['attribute_name']);
                    $combinationId = $comb['id_product_attribute'];
                    //call to prestashop webservice for get product images
                    $imageArr = $this->webService->getProducImage(
                        $combinationId, $productId
                    );
                    $images = array();
                    if (!empty($imageArr)) {
                        $i = 0;
                        foreach ($imageArr as $image) {
                            $images[$i]['image']['src'] = $image['src'];
                            $images[$i]['image']['thumbnail'] = $image['thumbnail'];
                            $i++;
                        }
                    }
                    if (!empty($images)) {
                        $variantData['sides'] = $images;
                    } else {
                        $variantData['sides'] = $images;
                    }
                    $variantData['id'] = $comb['id_attribute'];
                    $variantData['attribute_id'] = $comb['id_attribute'];
                    $variantData['name'] = $comb['attribute_name'];
                    //call to prestashop webservice for get color hexa value
                    $variantData['hex_code'] = $this->webService->getColorHex(
                        $comb['id_attribute']
                    );
                    if ($variantData['hex_code'] == '') {
                        $variantData['file_name'] = $this->webService->getColorHexValue(
                            $comb['id_attribute']
                        );
                    } else {
                        $variantData['file_name'] = '';
                    }
                    $variantData['color_type'] = $comb['is_color_group'];
                    $variantData['variant_id'] = $comb['id_product_attribute'];
                    if ($productPrice <= 0) {
                        $price = $this->webService->getCombinationPrice($combinationId);
                    } else {
                        $price = $productPrice;
                    }
                    $discountPrice = $this->webService->getDiscountPrice($productId, $price);
                    if (!empty($discountPrice)) {
                        $variantData['tier_prices'] = $discountPrice;
                    } else {
                        $variantData['tier_prices'] = [];
                    }
                    $variantData['price'] = $this->webService->convertToDecimal($price, 2);
                    $variantData['inventory']['stock'] = $comb['quantity'];
                    $variantData['inventory']['min_quantity'] = $comb['minimal_quantity'];
                    $variantData['inventory']['max_quantity'] = $comb['quantity'];
                    $variantData['inventory']['quantity_increments'] = $comb['minimal_quantity'];
                    array_push($productDetails, $variantData);
                }
            }
            if (empty($productDetails)) {
                foreach ($combinations as $key => $comb) {
                    $variantData = [];
                    if (($comb['is_color_group'] == '0')
                        && (!in_array($comb['attribute_name'], $colorArray))
                    ) {
                        array_push($colorArray, $comb['attribute_name']);
                        $combinationId = $comb['id_product_attribute'];
                        //call to prestashop webservice for get product images
                        $imageArr = $this->webService->getProducImage(
                            $combinationId, $productId
                        );
                        $images = array();
                        if (!empty($imageArr)) {
                            $i = 0;
                            foreach ($imageArr as $image) {
                                $images[$i]['image']['src'] = $image['src'];
                                $images[$i]['image']['thumbnail'] = $image['thumbnail'];
                                $i++;
                            }
                        }
                        if (!empty($images)) {
                            $variantData['sides'] = $images;
                        } else {
                            $variantData['sides'] = $images;
                        }
                        $variantData['id'] = $comb['id_attribute'];
                        $variantData['attribute_id'] = $comb['id_attribute'];
                        $variantData['name'] = $comb['attribute_name'];
                        //call to prestashop webservice for get color hexa value
                        $variantData['hex_code'] = $this->webService->getColorHex(
                            $comb['id_attribute']
                        );
                        if ($variantData['hex_code'] == '') {
                            $variantData['file_name'] = $this->webService->getColorHexValue(
                                $comb['id_attribute']
                            );
                        } else {
                            $variantData['file_name'] = '';
                        }

                        $variantData['color_type'] = $comb['is_color_group'];
                        $variantData['variant_id'] = $comb['id_product_attribute'];
                        if ($productPrice <= 0) {
                            $price = $this->webService->getCombinationPrice($combinationId);
                        } else {
                            $price = $productPrice;
                        }
                        $discountPrice = $this->webService->getDiscountPrice($productId, $price);
                        if (!empty($discountPrice)) {
                            $variantData['tier_prices'] = $discountPrice;
                        } else {
                            $variantData['tier_prices'] = [];
                        }
                        $variantData['price'] = $this->webService->convertToDecimal($price, 2);
                        $variantData['tax'] = $this->webService->getTaxRate($productId);
                        $variantData['inventory']['stock'] = $comb['quantity'];
                        $variantData['inventory']['min_quantity'] = $comb['minimal_quantity'];
                        $variantData['inventory']['max_quantity'] = $comb['quantity'];
                        $variantData['inventory']['quantity_increments'] = $comb['minimal_quantity'];
                        array_push($productDetails, $variantData);
                    }
                }
            }
            if (!empty($productDetails)) {
                $storeResponse = $productDetails;
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
    public function sizeByProduct($request, $response, $args)
    {

        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);

        $variantData = [];
        $productId = 1;
        $lang_id = 1;
        $jsonResponse = [
            'status' => 1,
            'records' => 0,
            'message' => 'No Color available',
            'data' => [],
        ];
        $productId = $args['product_id'];
        $filters = [
            'product_id' => $args['id'],
            'store' => $getStoreDetails['store_id'],
            'attribute' => 'xe_color',
        ];
        $custom_ssl_var = 0;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        }
        if ((bool) \Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $baseUrl = _PS_BASE_URL_SSL_;
        } else {
            $baseUrl = _PS_BASE_URL_;
        }
        try {
            $param = array('resource' => 'getAttributeCombinations', 'product_id' => $productId);
            $combinations = $this->webService->getAttributeCombinations($param);
            $size_array = array();
            $size_variations = array();
            foreach ($combinations as $key => $comb) {
                $obj = '';
                if (($comb['is_color_group'] == '0') && (!in_array($comb['attribute_name'], $size_variations))) {
                    // print_r($comb);

                    array_push($size_variations, $comb['attribute_name']);
                    $idImage = $this->getProductCoverImageId($comb['id_attribute']);
                    // get Image by id
                    if (sizeof($idImage) > 0) {
                        foreach ($idImage as $k => $v) {
                            $image = new \Image($v['id_image']);
                            // get image full URL
                            $thumbnail = $baseUrl . _THEME_PROD_DIR_ . $image->getExistingImgPath() . "-small_default.jpg";
                            $src = $baseUrl . _THEME_PROD_DIR_ . $image->getExistingImgPath() . ".jpg";
                            $comb['image']['src'] = $src;
                            $comb['image']['thumbnail'] = $thumbnail;
                        }
                    }
                    // $variantData = $this->getColorSwatchData($comb);
                    // print_r($variantData);

                    $obj->id = $comb['id_attribute'];
                    $obj->attribute_id = $comb['id_attribute'];
                    $obj->name = $comb['attribute_name'];
                    $obj->hex_code = '';
                    $obj->file_name = '';
                    $obj->color_type = $comb['is_color_group'];
                    $obj->variant_id = $comb['id_product_attribute'];
                    $obj->price = $comb['price'];
                    $obj->sides['image'] = $comb['image'];
                    $obj->inventory->stock = $comb['quantity'];
                    $obj->inventory->min_quantity = $comb['minimal_quantity'];
                    $obj->inventory->max_quantity = $comb['quantity'];
                    $obj->inventory->quantity_increments = $comb['minimal_quantity'];
                    array_push($size_variations, $obj);
                }

            }
            if (!empty($size_variations)) {
                // $variantData = $this->getColorData($product_details);
                $jsonResponse = [
                    'status' => 1,
                    'records' => count($size_variations),
                    'data' => $size_variations,
                ];
            }
        } catch (\Exception $e) {
            $serverStatusCode = EXCEPTION_OCCURED;
            $jsonResponse = [
                'status' => 0,
                'message' => message('Distress', 'error'),
                'exception' => show_exception() === true ? $e->getMessage() : '',
            ];
        }
        return [
            'data' => $jsonResponse,
            'httpStatusCode' => $serverStatusCode,
        ];
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
            $productVariant = $this->getAllVariantsByProduct($filters);
            $storeResponse = $productVariant;
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
            $getProducts = $this->webService->checkDuplicateNameAndSku($filters
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
                $j = 0;
                foreach ($predecorData['product_image_url'] as $imageUrl) {
                    if ($j = 0) {
                        $j++;
                        continue;
                    }
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
                $productAttributes =  [];
                foreach ($predecorData['attributes'] as $prodAttributekey => $prodAttribute) {
                    $productAttributes[] = $prodAttribute['attribute_options']; 
                }
                $productSaveData['attributes'] = $this->array_cartesian($productAttributes);
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
     * GET: Product Attribute Pricing  Details by Product Id
     *
     * @param $args Slim's Arguments
     *
     * @author radhanatham@riaxe.com
     * @date   10 March 2020
     * @return All store attributes
     */
    private function getAllVariantsByProduct($args)
    {
        $attributes = $this->getAttributeName();
        $sizeGroup = $attributes['size'];
        $colorGroup = $attributes['color'];

        $variantArray = $colorArray = [];
        $sizeArray = $productColourVariations = $productSizeVariations = [];
        $param = array(
            'product_id' => $args['product_id'],
        );
        $combinations = $this->webService->getAttributeCombinations($param);
        if (!empty($combinations)) {
            foreach ($combinations as $key => $comb) {
                $obj = array();
                if ((ucfirst($comb['group_name']) == ucfirst($colorGroup)) && (!in_array($comb['attribute_name'], $colorArray))) {
                    $variantArray[0]['id'] = $comb['id_attribute_group'];
                    $variantArray[0]['name'] = $colorGroup;
                    array_push($colorArray, $comb['attribute_name']);
                    $obj['id'] = $comb['id_attribute'];
                    $obj['name'] = $comb['attribute_name'];
                    $obj['hex_code'] = $this->webService->getColorHex(
                        $comb['id_attribute']
                    );
                    if ($obj['hex_code'] == '') {
                        $obj['file_name'] = $this->webService->getColorHexValue(
                            $comb['id_attribute']
                        );
                    } else {
                        $obj['file_name'] = '';
                    }
                    array_push($productColourVariations, $obj);
                } else if ((ucfirst($comb['group_name']) == ucfirst($sizeGroup)) && (!in_array($comb['attribute_name'], $sizeArray))) {
                    $variantArray[1]['id'] = $comb['id_attribute_group'];
                    $variantArray[1]['name'] = $sizeGroup;
                    array_push($sizeArray, $comb['attribute_name']);
                    $obj['id'] = $comb['id_attribute'];
                    $obj['name'] = $comb['attribute_name'];
                    array_push($productSizeVariations, $obj);
                }
            }
            if (!empty($productColourVariations)) {
                $variantArray[0]['options'] = $productColourVariations;
            }
            if (!empty($productSizeVariations)) {
                $variantArray[1]['options'] = $productSizeVariations;
            }
        }
        return array_values($variantArray);
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
            $getAllAttributes = $this->webService->getAttributeGroups();
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
                $filter = array('resource' => 'products',
                    'display' => 'full',
                    'filter[id]' => '[' . $productId . ']',
                    'output_format' => 'JSON',
                    'language' => '' . $this->languageId . '',
                );
                //call to prestashop webservice for get product details
                $result = $this->webService->get($filter);
                $products = json_decode($result, true);
                $getProductDetails['price'] = $products['products'][0]['price'];
                $getProductDetails['name'] = $products['products'][0]['name'];
                $getProductDetails['categories'] = $this->productCategories($productId);
                $parameter = array(
                    'variation_id' => $variantId,
                    'product_id' => $productId,
                );
                //call to prestashop webservice for get product combination
                $combinations = $this->webService->getAttributeCombinationsById(
                    $parameter
                );
                if ($products['products'][0]['price'] == 0 && $variantId) {
                    $price = $this->webService->getCombinationPrice($variantId);
                } else {
                    $price = $products['products'][0]['price'];
                }
                $getProductDetails['price'] = $price;
                $getProductDetails['tax'] = $this->webService->getTaxRate($productId);
                $discountPrice = $this->webService->getDiscountPrice($productId, $price);
                $getProductDetails['tier_prices'] = $discountPrice;
                $sizeArr = $colorArr = $extraAttributeArr = array();
                $colorGroupName = $sizeGroupName = $extraGroupName = '';
                foreach ($combinations as $key => $comb) {
                    if ($comb['id_product_attribute'] == $variantId) {
                        if ($comb['is_color_group'] == 1) {
                            $colorGroupName = $comb['group_name'];
                            $colorArr['id'] = $comb['id_attribute_group'];
                            $colorArr['name'] = $comb['attribute_name'];
                            $colorArr['attribute_id'] = $comb['id_attribute'];
                        } elseif ($comb['group_name'] == ucfirst($sizeGroup)) {
                            $sizeGroupName = $comb['group_name'];
                            $sizeArr['id'] = $comb['id_attribute_group'];
                            $sizeArr['name'] = $comb['attribute_name'];
                            $sizeArr['attribute_id'] = $comb['id_attribute'];
                        } else {
                            $extraGroupName = $comb['group_name'];
                            $extraAttributeArr['id'] = $comb['id_attribute_group'];
                            $extraAttributeArr['name'] = $comb['attribute_name'];
                            $extraAttributeArr['attribute_id'] = $comb['id_attribute'];
                        }
                    }
                }
                if (!empty($colorArr)) {
                    $getProductDetails['attributes'][$colorGroup] = $colorArr;
                }

                if (!empty($sizeArr)) {
                    $getProductDetails['attributes'][$sizeGroup] = $sizeArr;
                }
                if (!empty($extraAttributeArr)) {
                    $getProductDetails['attributes'][strtolower($extraGroupName)] = $extraAttributeArr;
                }
                if ($variantId == $productId) {
                    $getProductDetails['attributes'] = [];
                }
                //call to prestashop webservice for get product images
                $imageArr = $this->webService->getProducImage(
                    $variantId, $productId
                );
                $images = array();
                if (!empty($imageArr)) {
                    $i = 0;
                    foreach ($imageArr as $image) {
                        $images[$i]['src'] = $image['src'];
                        $images[$i]['thumbnail'] = $image['thumbnail'];
                        $i++;
                    }
                }
                if ($variantId == $productId && empty($images)) {
                    $imageIdArr = $this->webService->getProductImageByPid(
                        $productId
                    );
                    if (sizeof($imageIdArr) > 0) {
                        foreach ($imageIdArr as $k => $imageId) {
                            // get image full URL
                            $thumbnail = $this->webService->getProductThumbnail(
                                $imageId['id_image']
                            );
                            $productImage = $this->webService->getProductImage(
                                $imageId['id_image']
                            );
                            $images[$k]['src'] = $productImage;
                            $images[$k]['thumbnail'] = $thumbnail;
                        }
                    }
                    $getProductDetails['images'] = $images;
                } else {
                    $getProductDetails['images'] = $images;
                }
                $getProductDetails['images'] = $images;
                $storeResponse = $getProductDetails;
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
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'data' => [],
        ];
        $categorywiseProductList = [];
        $filter = array('resource' => 'categories',
            'display' => '[id,name]',
            'output_format' => 'JSON',
            'language' => '' . $this->languageId . '',
        );
        //call to prestashop webservice for get all categories
        $categoryJson = $this->storeApiCall($filter);
        $categorylist = json_decode($categoryJson, true);
        foreach ($categorylist['categories'] as $k => $category) {
            $categorywiseProductList[$k]['id'] = $category['id'];
            $categorywiseProductList[$k]['name'] = $category['name'];
            $parameters = array('resource' => 'products',
                'display' => '[id,name,type,price,reference]',
                'filter[id_category_default]' => '[' . $category['id'] . ']',
                'output_format' => 'JSON',
                'language' => '' . $this->languageId . '',
            );
            $productJson = $this->storeApiCall($parameters);
            $products = json_decode($productJson, true);
            if (!empty($products['products'])) {
                foreach ($products['products'] as $key => $product) {
                    $productId = $product['id'];
                    $products['products'][$key]['name'] = $product['name'];
                    // get Image by id
                    $imageIdArr = $this->webService->getProductImageByPid(
                        $productId
                    );
                    if (sizeof($imageIdArr) > 0) {
                        foreach ($imageIdArr as $imageId) {
                            //call to prestashop webservice for get product images
                            $thumbnail = $this->webService->getProductThumbnail(
                                $imageId['id_image']
                            );
                            $products['products'][$key]['image'][] = $thumbnail;
                        }
                    }
                }
                $categorywiseProductList[$k]['products'] = $products['products'];
            } else {
                $categorywiseProductList[$k]['products'] = [];
            }
        }
        $categories = [];
        $categories['categories'] = array_values($categorywiseProductList);
        $jsonResponse = [
            'status' => 1,
            'data' => $categories,
        ];
        return [
            'data' => $jsonResponse,
            'server_status' => $serverStatusCode,
        ];
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
        //call to prestashop webservice for get all products id count
        $result = $this->webService->get($parameter);
        $products = json_decode($result, true);
        $getProductCount['total'] = sizeof($products['products']);
        $getProductCount['vc'] = $this->webService->getPrestaShopVersion();
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
            $productVariations = $this->webService->getAttributeCombinations($filter);

            $arributeName = '';
            if (!empty($productVariations)) {
                foreach ($productVariations as $k => $v) {
                    if ($v['id_product_attribute'] == $variationId && $v['is_color_group'] == 1) {
                        $arributeName = $v['attribute_name'];
                        $colorGroupNameId = $colorGroup . '_id';
                        $colorId = $v['id_attribute'];
                    }
                }
            }
            $attributesId = [];
            if (!empty($productVariations)) {
                foreach ($productVariations as $k => $v) {
                    if ($v['attribute_name'] == $arributeName) {
                        $attributesId[] = $v['id_product_attribute'];
                    }
                }
            }

            $arribute = [];
            $finalArray = [];
            if (!empty($productVariations)) {
                $i = 0;
                $attributesNameArray = array();
                if (!empty($attributesId)) {
                    foreach ($productVariations as $k => $v) {
                        if (($v['is_color_group'] == 0
                            && (in_array($v['id_product_attribute'], $attributesId) && ($v['group_name'] == $attribute))) && (!in_array($v['attribute_name'], $attributesNameArray)) && (!in_array($v['id_product_attribute'], $extraAttributeIdArr))
                        ) {
                            array_push($attributesNameArray, $v['attribute_name']);
                            $arribute[$i]['id'] = $v['id_attribute_group'];
                            $arribute[$i]['name'] = $v['attribute_name'];
                            $arribute[$i]['variant_id'] = $v['id_product_attribute'];
                            if ($details) {
                                $ziseGroupNameId = $extraAttributeGroup . '_id';
                                $productPrice = $this->webService->getCombinationPrice($v['id_product_attribute']);
                                if ($productPrice > 0) {
                                    $price = $productPrice;
                                } else {
                                    $price = $this->webService->getProductPriceByPid($productId);
                                }
                                $discountPrice = $this->webService->getDiscountPrice($productId, $price);
                                $arribute[$i]['tier_prices'] = $discountPrice;
                                $arribute[$i]['price'] = $price;
                                $arribute[$i]['tax'] = $this->webService->getTaxRate($productId);
                                $arribute[$i]['inventory']['stock'] = $v['quantity'];
                                $arribute[$i]['inventory']['max_quantity'] = $v['quantity'];
                                $arribute[$i]['inventory']['min_quantity'] = $v['minimal_quantity'];
                                if ($ziseGroupNameId) {
                                    $arribute[$i]['attributes'][$extraAttributeGroup] = $v['attribute_name'];
                                    $arribute[$i]['attributes'][$ziseGroupNameId] = $v['id_attribute'];
                                }
                                if ($colorId) {
                                    $arribute[$i]['attributes'][$colorGroupNameId] = $colorId;
                                    $arribute[$i]['attributes'][$colorGroup] = $arributeName;

                                }
                                if (!$colorId && !$ziseGroupNameId) {
                                    $arribute[$i]['attributes'] = [];
                                }
                            }
                            $i++;
                        }
                    }
                } else {
                    foreach ($productVariations as $k => $v) {
                        if (($v['is_color_group'] == 0
                            && (($v['group_name'] == $attribute))) && (!in_array($v['attribute_name'], $attributesNameArray)) && (!in_array($v['id_product_attribute'], $extraAttributeIdArr))
                        ) {
                            array_push($attributesNameArray, $v['attribute_name']);
                            $arribute[$i]['id'] = $v['id_attribute_group'];
                            $arribute[$i]['name'] = $v['attribute_name'];
                            $arribute[$i]['variant_id'] = $v['id_product_attribute'];
                            $ziseGroupNameId = $extraAttributeGroup . '_id';
                            $productPrice = $this->webService->getCombinationPrice($v['id_product_attribute']);
                            if ($productPrice > 0) {
                                $price = $productPrice;
                            } else {
                                $price = $this->webService->getProductPriceByPid($productId);
                            }
                            $discountPrice = $this->webService->getDiscountPrice($productId, $price);
                            $arribute[$i]['tier_prices'] = $discountPrice;
                            $arribute[$i]['price'] = $price;
                            $arribute[$i]['tax'] = $this->webService->getTaxRate($productId);
                            $arribute[$i]['inventory']['stock'] = $v['quantity'];
                            $arribute[$i]['inventory']['max_quantity'] = $v['quantity'];
                            $arribute[$i]['inventory']['min_quantity'] = $v['minimal_quantity'];
                            $arribute[$i]['inventory']['quantity_increments'] = '1';
                            if ($ziseGroupNameId) {
                                $arribute[$i]['attributes'][$extraAttributeGroup] = $v['attribute_name'];
                                $arribute[$i]['attributes'][$ziseGroupNameId] = $v['id_attribute'];
                            }
                            if ($colorId) {
                                $arribute[$i]['attributes'][$colorGroupNameId] = $colorId;
                                $arribute[$i]['attributes'][$colorGroup] = $arributeName;

                            }
                            if (!$colorId && !$ziseGroupNameId) {
                                $arribute[$i]['attributes'] = [];
                            }
                            $i++;
                        }
                    }
                }
                $finalArray[$extraAttributeGroup] = $arribute;
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
     * @param $catalog Catalog details
     *
     * @author radhanatham@riaxe.com
     * @date  05 June 2020
     * @return array json
     */
    public function addProductToStore($productData, $catalog, $predecoDetails, $storeId)
    {
        $productArr = [];
        if (!empty($productData)) {
            foreach ($productData as $k => $v) {
                $price = $v['price'];
                $categories = $v['categories'];
                $catalog_price = $v['catalog_price'] ? $v['catalog_price'] : $price;
                $params = array('style_id' => $v['style_id'], "catalog_code" => $catalog);
                $oldProductId = $v['old_product_id'] ? $v['old_product_id'] : 0;
                $returnData = api_call_by_curl($params, 'product');
                $predecorData = $returnData['data'];
                $sideName = $predecorData['variations'][0]['side_name'];
                if (!empty($predecorData)) {
                    $productId = $this->webService->addCatalogProductToStore($predecorData, $price, $catalog_price, $categories, $storeId, $oldProductId);
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

    /**
     * GET: Create product attribute combination
     *
     * @param $arrays Attribute array
     *
     * @author radhanatham@riaxe.com
     * @date   22 Dec 2020
     * @return All combination attribute
     */
    private function array_cartesian($arrays)
    {
        $result = array();
        $arrays = array_values($arrays);
        $sizeIn = sizeof($arrays);
        $size = $sizeIn > 0 ? 1 : 0;
        foreach ($arrays as $array) {
            $size = $size * sizeof($array);
        }
        for ($i = 0; $i < $size; $i ++) {
            $result[$i] = array();
            for ($j = 0; $j < $sizeIn; $j ++) {
                array_push($result[$i], current($arrays[$j]));
            }
            for ($j = ($sizeIn -1); $j >= 0; $j --) {
                if (next($arrays[$j])) {
                    break;
                } elseif (isset($arrays[$j])) {
                    reset($arrays[$j]);
                }
            }
        }
        return $result;
    }

    /**
     * Create product categories
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     *
     * @author rakeshd@imprintnext.com
     * @date   25 March 2021
     * @return array category id
     */

    public function createProductCatagories($request, $response)
    {
        $storeResponse = [];
        $getPostData = $request->getParsedBody();
        $catName = $getPostData['name'];
        $catId = $getPostData['catId'];
        $store_id = $getPostData['store_id'];
        try {
            $filters = array(
                'catName' => $catName,
                'catId' => $catId,
                'store' => $store_id,
            );
            $categoryId = $this->webService->addCategory($filters);
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
     * Remove Product category
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args Slim's Argument parameters
     *
     * @author rakeshd@imprintnext.com
     * @date   25 March 2021
     * @return array status_code
     */
    public function removeCategories($request, $response, $args)
    {
        $storeResponse = [];
        $catId = $args['id'];
        try {
            $categoryId = $this->webService->deleteCategory($catId);
            if ($categoryId != 0 && $categoryId != null) {
                $storeResponse = [
                    'status' => 1,
                    'message' => message('Catagories', 'deleted'),
                ];
            } else {
                $storeResponse = [
                    'status' => 0,
                    'message' => message('Catagories', 'error'),
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
                        'module' => 'Remove Categories',
                    ],
                ]
            );
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
    public function productVariants($request, $response, $args)
    {
        $storeResponse = [];
        if (!empty($args['productID'])) {
            $productId = to_int($args['productID']);
            $storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
            $filters = [
                'product_id' => $productId,
            ];
            try {
                $productPrice = $this->webService->getProductPriceByPid($productId);
                $combinations = $this->webService->getAttributeCombinations($filters);
                if (!empty($combinations)) {
                    $temp = [];
                    $i = 0;
                    foreach ($combinations as $key => $comb) {
                        if (!in_array($comb['id_product_attribute'], $temp)){
                            if ($productPrice <= 0) {
                                $price = $this->webService->getCombinationPrice($comb['id_product_attribute']);
                            } else {
                                $price = $productPrice;
                            }
                            $storeResponse[$i]['id'] = $comb['id_product_attribute'];
                            $storeResponse[$i]['title'] = $comb['attribute_name'];
                            $storeResponse[$i]['price'] = $price;
                            $temp[] = $comb['id_product_attribute'];
                            $i++;
                        }else{
                            $storeResponse[$i-1]['title'] = $storeResponse[$i-1]['title'].'-'.$comb['attribute_name'];
                        }
                    }
                }
            } catch (\Exception $e) {
                $storeResponse = [];
            }
        }
        return $storeResponse;
    }

}
