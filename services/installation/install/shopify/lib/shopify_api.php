<?php
/**
 * Product and cart api for shopify store
 *
 * @category    Shopify
 * @package     Shopify-Api
 * @author      Inkxe Shopify Team
 */
class Shopify_api
{
    public $shop;
    public $api_key;
    public $token;
    public $secret;
    public $shopify;
    public $thumb_size = "small";
    public $full_size = "";

    /**
     * Shopify API login
     *
     * @category    Shopify
     * @package     Shopify-Api
     * @author      Inkxe Shopify Team
     */
    public function login()
    {
        global $shopify_apps;
        //pull random number based on number of apps
        $this->api_key = APIUSER;
        $this->token = APIPASS;
        $this->secret = SECRETKEY;
        $this->shop = SHOPIFY_SHOP;
        $this->shop = $this->shop . '.myshopify.com';
        $this->shopify = new ShopifyClient($this->shop, $this->token, $this->api_key, $this->secret);
        return time();
    }

    /**
     * @param  string $key
     * @param  string $method|Shopify api link
     * @param  array $call_params|Required parameters
     */
    public function call($key, $method, $call_params = array())
    {
        switch ($method) {
            case "cedapi_product.getAllProducts":
                return $this->products_all($call_params);
            case "cedapi_product.addCustomProduct":
                return $this->addCustomProduct($call_params);
            case "cedapi_product.checkCustomProduct":
                return $this->checkCustomProduct($call_params);
            case "cedapi_product.editCustomProduct":
                return $this->editCustomProduct($call_params);
            case "cedapi_product.clearCustomProducts":
                return $this->clearCustomProducts($call_params);
            case "cedapi_product.getSimpleProduct":
                return $this->products_get($call_params);
            case "cedapi_product.getVariants":
                return $this->products_get_variants($call_params);
            case "cedapi_product.getVariantList":
                return $this->products_get_variants_list($call_params);
            case "cedapi_product.getCategories":
                return json_encode(array('categories' => $this->products_types(true)));
            case "cedapi_product.getProductCount":
                return $this->products_count($call_params);
            case "cedapi_product.getSizeAndQuantity":
                return $this->products_get_variant_inventory($call_params);
            case "cedapi_cart.getOrdersGraph":
                return $this->orders_graph($call_params);
            case "cedapi_cart.getOrders":
                return $this->orders_all($call_params);
            case "cedapi_cart.getOrderDetails":
                return $this->orders_order($call_params);
            case "cedapi_product.getCategoriesByProduct":
                return $this->products_get_category($call_params);
            case "cedapi_cart.orderIdFromStore":
                return $this->getOrderIdFromStore($call_params);
            case "cedapi_cart.createOrderDetails":
                return $this->createOrderDetails($call_params);
            case "cedapi_product.getColorArr":
                return $this->variant_colors($call_params);
            case "cedapi_product.getSizeArr":
                return $this->getSizeArr($call_params);
            case "cedapi_product.addTemplateProducts":
                return $this->addTemplateProducts($call_params);
            case "cedapi_product.getProductInfo":
                return $this->getProductInfo($call_params);
            case "cedapi_product.getSizeVariants":
                return $this->getSizeVariants($call_params);
            case "cedapi_product.getPendingOrders":
                return $this->getPendingOrders($call_params);
            case "cedapi_cart.orderDetailsFromId":
                return $this->orderDetailsFromId($call_params);
            case "cedapi_cart.getOriginalVarID":
                return $this->getOriginalVarID($call_params);
            case "cedapi_cart.manageInventory":
                return $this->manageInventory($call_params);
            case "cedapi_order.getAllCustomers":
                return $this->getCustomers($call_params);
            case "cedapi_order.getCustomerData":
                return $this->getUserDetails($call_params);
            case "cedapi_product.getAllCountries":
                return $this->getCountries();
            case "cedapi_product.getStates":
                return $this->getStates($call_params);
            case "cedapi_order.addCustomer":
                return $this->addCustomer($call_params);
            case "cedapi_order.updateCustomer":
                return $this->updateCustomer($call_params);
            case "cedapi_order.deleteCustomers":
                return $this->deleteCustomers($call_params);
            case "cedapi_order.getCustomerAddress":
                return $this->getCustomerAddress($call_params);
            case "cedapi_order.addNewAddress":
                return $this->addCustomerAddress($call_params);
            case "cedapi_product.getProductSKU":
                return $this->getProductSKU($call_params);

        }
        return null;
    }

    /**
     * Created to delete all customized product created during addToCart without checkout
     *It is not implemented but may be required in feature.
     *
     * @param array|object $call_params
     * @return json
     */
    public function clearCustomProducts($call_params)
    {
        $custColID = 0;
        $collections = $this->shopify->call('GET', '/admin/smart_collections.json');
        foreach ($collections as $col) {
            if ($col['handle'] == "customized") {
                $custColID = $col['id'];
            }
        }
        if ($custColID) {
            $products = $this->shopify->call('GET', '/admin/products.json?collection_id=' . $custColID);
        }
        foreach ($products as $prod) {
            $createDate = date_format(new DateTime($prod['created_at']), 'd/m/Y');
            $now = date("d/m/Y");
            $thisInterval = $now - $createDate;
            if ($thisInterval >= $call_params) {
                $deleteProd = $this->shopify->call('DELETE', '/admin/products/' . $prod['id'] . '.json');
            }
        }
        if ($deleteProd) {
            return json_encode(array('result' => 'Customized products has been cleared !!!'));
        } else {
            return json_encode(array('result' => 'No customized products has been created beyond ' . $call_params . ' Days'));
        }
    }

    /**
     * while loading product.liquid
     *This will check wheather a product is customized one or not.
     *
     * @param array|object $call_params
     * @return json
     */
    public function checkCustomProduct($call_params)
    {
        $isDelete = false;
        $checkProd = $this->shopify->call('GET', '/admin/smart_collections.json?product_id=' . $call_params);
        if (!empty($checkProd) || isset($checkProd['Tags'])) {
            foreach ($checkProd as $prod) {
                if ($prod['handle'] == "customized") {
                    $isDelete = true;
                }
            }
        }
        return $isDelete;
    }

    /**
     * Hide customized product linked with an order after checkout.
     * Delete customized product through api.
     *
     * @param array|object $call_params
     * @return json
     */
    public function editCustomProduct($call_params)
    {
        $isCustom = false;
        $checkProd = $this->shopify->call('GET', '/admin/smart_collections.json?product_id=' . $call_params['product_id']);
        if (!empty($checkProd) || isset($checkProd['Tags'])) {
            foreach ($checkProd as $prod) {
                if ($prod['handle'] == "customized") {
                    $isCustom = true;
                }
            }
        }
        if ($call_params['isDelete'] == 0 && $isCustom) {
            $product_array = array(
                "product" => array(
                    "id" => $call_params['product_id'],
                    "published" => false,
                ));
            $hideProd = $this->shopify->call('PUT', '/admin/products/' . $call_params['product_id'] . '.json', $product_array);
            return json_encode($hideProd);
        } elseif ($call_params['isDelete'] == 1 && $isCustom) {
            $deleteProd = $this->shopify->call('DELETE', '/admin/products/' . $call_params['product_id'] . '.json');
            return json_encode($deleteProd);
        }
    }

    /**
     * Create a custom product while adding to cart with custom price.
     *
     * @param array|object $call_params
     * @return json
     */
    public function addCustomProduct($call_params)
    {

        $pid = $call_params['product_id'];
        $variantID = $call_params['simpleproduct_id'];
        $productOpt = $call_params['options'];
        $quantity = $call_params['qty'];
        $tierPriceData = array();
        $isTier = false;
        $product = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
        $variant = $this->shopify->call('GET', '/admin/products/' . $pid . '/variants/' . $variantID . '.json');
        // get store location id. Currently set for main store location.
        $variantPrice = $variant['price'];
        $inventoryQty = $variant['inventory_quantity'];
        // get option array for new product
        $optionArr = array();
        foreach ($product['options'] as $key => $opt) {
            array_push($optionArr, array("name" => $opt['name'], "position" => $opt['position']));
            // push the third attribute to the variant details. if it has extra one
            if (strtolower($opt['name']) !== 'title') {
                $newAttribute['option' . $opt['position']] = $variant['option' . $opt['position']];
            } else {
                $newAttribute['option1'] = '';
            }
             // change price in case of tier pricing
            if (strtolower($opt['name']) == "quantity") {
                $tierPriceData = $this->getTierPrice($product);
                $isTier = true;
            }
        }
        if ($isTier) {
            foreach ($tierPriceData as $tp) {
                if ($quantity >= $tp['tierQty'] && $quantity <= $tp['maxQty']) {
                    $variantPrice = $tp['tierPrice'];
                }
            }
        }
        $newPrice = $variantPrice + $call_params['custom_price'];
        $ref_id = $call_params['ref_id'];
        // fetch the png image url from server //
        $imgPngArr = $this->getCustomPreviewImages($ref_id);
        //print_r($imgPngArr);
        if (count($imgPngArr) > 0) {
            $kount = 1;
            foreach (json_decode($imgPngArr, true) as $pngUrl) {
                $pngArr[] = array("src" => $pngUrl, "position" => $kount);
                $kount++;
            }
        }
        // get variant details
        $variantArr = array(
            "sku" => $variant['id'] . "_" . $variant['sku'],
            "price" => $newPrice,
            "taxable" => $variant['taxable'],
            "weight" => $variant['weight'],
            "weight_unit" => $variant['weight_unit'],
            "inventory_management" => $variant['inventory_management'],
            // "inventory_quantity" => $variant['inventory_quantity'],
            "inventory_policy" => $variant['inventory_policy'],
        );
        $variantArr = array_merge($variantArr, $newAttribute);
        $product_array = array(
            "product" => array(
                "title" => addslashes($product['title']),
                "body_html" => ($product['body_html'] !== null ? addslashes($product['body_html']) : ""),
                "vendor" => "inkXE",
                "tags" => "customized",
                "published" => true,
                "options" => $optionArr,
                "variants" => array($variantArr),
                "images" => $pngArr,
                "image" => $pngArr['0'],
            ),
        );
        $addProduct = $this->shopify->call('POST', '/admin/products.json', $product_array);
        if ($addProduct['variants'][0]['inventory_management'] == 'shopify') {
            $newProductVariants = $addProduct['variants'];
            $inventoryItems = array_column($newProductVariants, 'inventory_item_id');
            foreach ($inventoryItems as $inventory) {
                $invLevels = $this->shopify->call('GET', '/admin/api/2019-04/inventory_levels.json?inventory_item_ids='.$inventory);
                foreach ($invLevels as $level) {
                    $locationData = $this->shopify->call('GET', '/admin/api/2019-04/locations/'.$level['location_id'].'.json');
                    if ($locationData['legacy'] == false) {
                        $newInventory = array("location_id" => $level['location_id'], "inventory_item_id" =>$inventory, "available" =>$inventoryQty);
                        $updateInventory = $this->shopify->call('POST', '/admin/inventory_levels/set.json', $newInventory);
                    }
                }
            }
        }
        $newProd = array();
        $thisVar = $addProduct['variants'][0];
        return array('pid' => $addProduct['id'], 'simpleprodID' => $thisVar['id']);
    }

    /**
     * Retrieve a single product's variants
     *
     * @param array|object $call_params
     * @param String confId|prod_id
     * @param String start
     * @param String limit
     * @param int store
     * @param String offset
     * @return json
     */
    public function products_get_variants($call_params)
    {
        $pid = $call_params['confId'];
        $offset = (isset($call_params['offset']) && trim($call_params['offset']) != '') ? trim($call_params['offset']) : '';
        $limit = (isset($call_params['limit']) && trim($call_params['limit']) != '') ? trim($call_params['limit']) : '';
        $shop_prod = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
        $prod_options = $this->shopify->call('GET', '/admin/products/' . $pid . '.json?fields=options');
        $variants = array();
        $allColors = "";
        $variant_count = "";
        $colorPos = "";
        $swatches = array();
        //get option positions
        foreach ($shop_prod['options'] as $option) {
            if (strtolower($option['name']) == 'color') {
                $colorPos = $option['position'];
            }
        }
        foreach ($prod_options['options'] as $opt) {
            if ($opt['position'] == $colorPos) {
                $allColors = $opt['values'];
                $allColors = array_map('strtolower', $allColors);
                $color_count = count(array_unique($allColors));
            }
        }
        if ($offset == 1) {
            $allowVar = ($offset * $limit);
            $allColors = array_slice($allColors, 0, $allowVar);
        } elseif ($offset > 1) {
            $allowVar = ($offset * $limit) - 1;
            $varStart = ($offset - 1) * $limit;
            $allColors = array_slice($allColors, $varStart, $allowVar);
        }
        //pull images out
        foreach ($shop_prod['images'] as $pi) {
            $images[$pi['id']] = $pi;
        }

        //loop through variants
        foreach ($shop_prod['variants'] as $pv) {
            if (in_array(strtolower($pv['option' . $colorPos]), $allColors)) {
                $allColors = array_diff($allColors, array(strtolower($pv['option' . $colorPos])));
                $this_var['id'] = $pv['id'];
                $this_var['name'] = self::shopify_size($shop_prod['title']) . ' / ' . $pv['title'];
                $this_var['description'] = self::shopify_body($shop_prod['body_html']);
                if (isset($images) && $pv['image_id'] && array_key_exists($pv['image_id'], $images)) {
                    $this_var['thumbnail'] = self::shopify_image($images[$pv['image_id']]['src'], $this->thumb_size);
                } else {
                    $this_var['thumbnail'] = self::shopify_image($shop_prod['image']['src'], $this->thumb_size);
                }

                $this_var['price'] = $pv['price'];
                $this_var['tax'] = "0.00";
                $this_var['xeColor'] = ($pv['option' . $colorPos] !== null ? $pv['option' . $colorPos] : "");
                $this_var['xe_color_id'] = ($pv['option' . $colorPos] !== null ? str_replace(' ', '_', $pv['option' . $colorPos]): "");
                $this_var1[] = $this_var['xeColor'];
                $this_var['colorUrl'] = "";
                $this_var['ConfcatIds'] = [];
                $color_look = "_" . str_replace(" ", "_", strtolower($this_var['xeColor'])) . "_";
                $color_lookup = ($this_prod['pidtype'] == 'simple' ? '_' : $color_look);
                $variantImageID = ($pv['image_id'] ? $pv['image_id'] : $shop_prod['image']['id']);
                if( strpos( $shop_prod['tags'], "Pre-Deco" ) !== false) {
                    $variantImageID = ($pv['image_id'] ? $pv['image_id'] : $shop_prod['images'][1]['id']);
                }
                //Get variant wise side images for multi color add to cart
                if (isset($images) && $variantImageID && array_key_exists($variantImageID, $images)) {
                    $this_var['thumbsides'] = array(self::shopify_image($images[$variantImageID]['src'], $this->thumb_size));
                    $this_var['sides'] = array(self::shopify_image($images[$variantImageID]['src'], $this->full_size));
                    $this_var['labels'][] = (strpos(strtolower($images[$variantImageID]['src']), "front") !== false) ? "Front" : "";
                    foreach ($images as $pimg) {
                        if (!in_array(self::shopify_image($pimg['src'], $this->full_size), $this_var['sides'])) {
                            if (strpos(strtolower($pimg['src']), $color_lookup . "left") !== false) {
                                $this_var['labels'][] = "Left";
                                $this_var['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "right") !== false) {
                                $this_var['labels'][] = "Right";
                                $this_var['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "back") !== false) {
                                $this_var['labels'][] = "Back";
                                $this_var['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "top") !== false) {
                                $this_var['labels'][] = "Top";
                                $this_var['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "bottom") !== false) {
                                $this_var['labels'][] = "Bottom";
                                $this_var['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            }
                        }
                        if (strpos(strtolower($pimg['src']), $color_lookup . "left") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_var['thumbsides'])) {
                            $this_var['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "right") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_var['thumbsides'])) {
                            $this_var['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "back") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_var['thumbsides'])) {
                            $this_var['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "top") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_var['thumbsides'])) {
                            $this_var['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "bottom") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_var['thumbsides'])) {
                            $this_var['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                    }
                } else {
                    $this_var['thumbsides'] = array();
                    $this_var['sides'] = array();
                    $this_var['labels'][] = "";
                }

                //Get variant wise side images for multi color add to cart
                $variants[] = $this_var;
            }
        }
        // if(sizeof($this_var1)>0)
        return json_encode(array('variants' => $variants, 'count' => $color_count));
    }

    /**
     * Retrieve all variants and their colors(used for color swatches)
     *
     * @param array|object $call_params
     * @param array|object $call_params
     * @return json
     */
    public function variant_colors($call_params)
    {
        $colorPos = "";
        $ProdID = $call_params['productId'];
        $Prod_colors = array();
        if ($ProdID && $ProdID > 0) {
            $thisProd = $this->shopify->call('GET', '/admin/products/' . $ProdID . '.json');
            $thisProdColors = array();
            $Prod_colors = array();

            foreach ($thisProd['options'] as $option) {
                if (strtolower($option['name']) == 'color') {
                    $colorPos = $option['position'];
                }
            }
            foreach ($thisProd['variants'] as $pv) {
                if (!in_array($pv['option' . $colorPos], $Prod_colors)) {
                    $thisProdColors[] = array('value' => $pv['option' . $colorPos], 'label' => $pv['option' . $colorPos], 'swatchImage' => "");
                    $Prod_colors[] = $pv['option' . $colorPos];
                }
            }
            return json_encode($thisProdColors);exit();

        } else {
            $loadCount = $call_params['loadCount'];
            $lastLoaded = $call_params['lastLoaded'];
            $collections = $this->shopify->call('GET', '/admin/custom_collections.json?handle=show-in-designer');
            $prod_params['collection_id'] = $collections[0]['id'];
            $prod_params['limit'] = 250;
            $all_products = $this->shopify->call('GET', '/admin/products.json', $prod_params);
            $colors = array();
            $unique_colors = array();
            foreach ($all_products as $p) {
                $colorPos = '';
                foreach ($p['options'] as $option) {
                    if (strtolower($option['name']) == 'color') {
                        $colorPos = $option['position'];
                    }
                }
                if ($colorPos != '') {
                    foreach ($p['variants'] as $pv) {
                        if ($pv['option' . $colorPos] !== null && !in_array(str_replace(' ', '_', strtolower($pv['option' . $colorPos])), $unique_colors)) {
                            $colors[$pv['id']] = array('value' => str_replace(' ', '_', strtolower($pv['option' . $colorPos])), 'label' => str_replace(' ', '_', strtolower($pv['option' . $colorPos])));
                            $unique_colors[] = str_replace(' ', '_', strtolower($pv['option' . $colorPos]));
                        }
                    }
                }
            }
        }
        if ($lastLoaded > 0) {
            return array_slice($colors, $lastLoaded, $loadCount);
        } else {
            return array_slice($colors, 0, $loadCount);
        }

    }
    /**
     * Retrieve a single product's size atribute values
     *
     * @param array|object $call_params
     * @return json
     */
    public function getSizeArr($call_params)
    {
        $prod_params = array();
        $prod_params['limit'] = 250;
        $prod_params['published_status'] = 'published';
        if (!$categoryid && $categoryid == '') {
            $collections = $this->shopify->call('GET', '/admin/custom_collections.json?handle=show-in-designer');
            $prod_params['collection_id'] = $collections[0]['id'];
        }
        $all_products = $this->shopify->call('GET', '/admin/products.json', $prod_params);
        $sizes = array();
        $unique_sizes = array();
        foreach ($all_products as $p) {
            $sizePos = "";
            foreach ($p['options'] as $option) {
                if (strtolower($option['name']) == 'size') {
                    $sizePos = $option['position'];
                }
            }
            if ($sizePos != "") {
                foreach ($p['variants'] as $pv) {
                    if (!in_array($pv['option' . $sizePos], $unique_sizes) && $pv['option' . $sizePos] !== "Default Title") {
                        $sizes[] = array('value' => $pv['option' . $sizePos], 'label' => $pv['option' . $sizePos]);
                        $unique_sizes[] = $pv['option' . $sizePos];
                    }
                }
            }
        }

        return json_encode($sizes);

    }
    /**
     * Get Id of show in designer collection Id
     *
     * @param array|object $call_params
     * @return json
     */
    private function getCustomColId()
    {
        $collections = $this->shopify->call('GET', '/admin/custom_collections.json?handle=show-in-designer');
        $isDesignerID = $collections[0]['id'];
        return $isDesignerID;
    }

    /**
     * Retrieve a single product's category(not used currently)
     *
     * @param array|object $call_params
     * @return json
     */
    public function addTemplateProducts($call_params)
    {
        $productType = $call_params['data']['product_type'];
        if ($productType == "simple") {
            $savedprodData = $this->saveSimplePDP($call_params);
            return $savedprodData;
        }else{
            $confProdID = $call_params['data']['conf_id'];
            $smplProdID = $call_params['data']['simpleproduct_id'];
            $prod_sku = $call_params['data']['sku'];
            $prod_price = $call_params['data']['price'];
            $prod_qty = $call_params['data']['qty'];
            $prod_color = str_replace(' ', '_', strtolower($call_params['varColor']));
            $isDesignerID = self::getCustomColId();
            $variant_arr = array();
            // get parent product details
            $shop_prod = $this->shopify->call('GET', '/admin/products/' . $smplProdID . '.json');
            if ($confProdID > 0) {
                $thisProdImgs = $this->shopify->call('GET', '/admin/products/' . $smplProdID . '.json?fields=images');
            }
            // get parent product images
            $imgArr = array();
            foreach ($call_params['configFile'] as $key => $img) {
                if (strpos($img, "/preDecoProduct/ci_")) {
                    $key = $key + 1;
                    array_push($imgArr, array("src" => $img, "position" => $key));
                }
            }
            $imgCount = ($confProdID == 0 ? (count($imgArr) + 1) : (count($thisProdImgs) + 1));
            $selImgs = array();
            foreach ($shop_prod['images'] as $prodImg) {
                if (strpos(strtolower($prodImg['src']), $prod_color) !== false) {
                    $selImgs[] = array("src" => $prodImg['src'], "position" => $imgCount);
                    $imgCount++;
                }
            }
            if ($confProdID == 0) {
                $imgArr = array_merge($imgArr, $selImgs);
            }
            // create variant array dynamicaly
            $kount = 1;
            foreach ($call_params['varSize'] as $size) {
                $variant_arr[] = array("option1" => $size, "option2" => $prod_color, "sku" => $prod_sku . "_0" . $kount, "price" => $prod_price, "inventory_management" => "shopify", "inventory_policy" => "deny");
                $kount++;
            }
            if ($confProdID == 0) {
                $product_array = array(
                    "product" => array(
                        "title" => self::shopify_body(addslashes($call_params['data']['product_name'])),
                        "body_html" => self::shopify_body(addslashes($call_params['data']['description'])),
                        "published" => true,
                        "tags" => "Pre-Deco",
                        "options" => array(
                            array(
                                "name" => "size",
                                "position" => 1,
                            ),
                            array(
                                "name" => "color",
                                "position" => 2,
                            ),
                        ),
                        "variants" => $variant_arr,
                        "images" => $imgArr,
                        "image" => array("src" => $call_params['configFile'][0], "position" => 1),
                    ),
                );
                $newProduct = $this->shopify->call('POST', '/admin/products.json', $product_array);
                if ($newProduct['variants'][0]['inventory_management'] == 'shopify') {
                    $newProductVariants = $newProduct['variants'];
                    $inventoryItems = array_column($newProductVariants, 'inventory_item_id');
                    foreach ($inventoryItems as $inventory) {
                        $invLevels = $this->shopify->call('GET', '/admin/api/2019-04/inventory_levels.json?inventory_item_ids='.$inventory);
                        foreach ($invLevels as $level) {
                            $locationData = $this->shopify->call('GET', '/admin/api/2019-04/locations/'.$level['location_id'].'.json');
                            if ($locationData['legacy'] == false) {
                                $newInventory = array("location_id" => $level['location_id'], "inventory_item_id" =>$inventory, "available" =>$prod_qty);
                                $updateInventory = $this->shopify->call('POST', '/admin/inventory_levels/set.json', $newInventory);
                            }
                        }
                    }
                }

                // assign product to "Show in designer" collection if is customized is checked.
                if ($call_params['data']['is_customized'] == 1) {
                    $customArr = array(
                        "collect" => array(
                            "product_id" => $newProduct['id'],
                            "collection_id" => $isDesignerID,
                        ),
                    );
                    $addCustomCol = $this->shopify->call('POST', '/admin/collects.json', $customArr);
                }
                // assign categories one by one (collections)
                $smartColIDs = $this->getSmartCollectionIds();
                if (!empty($call_params['data']['cat_id'])) {
                    foreach ($call_params['data']['cat_id'] as $catID) {
                        if (!in_array($catID, $smartColIDs)) {
                            $catArr = array(
                                "collect" => array(
                                    "product_id" => $newProduct['id'],
                                    "collection_id" => $catID,
                                ),
                            );
                            $addCategory = $this->shopify->call('POST', '/admin/collects.json', $catArr);
                        } else {
                            continue;
                        }
                    }
                }
            }
            if ($confProdID > 0) {
                // Get new product details
                $newProduct = $this->shopify->call('GET', '/admin/products/' . $confProdID . '.json');
                // Get list of existing variants and images
                foreach ($newProduct['variants'] as $vrnt) {
                    $oldVars[] = $vrnt;
                }
                foreach ($newProduct['images'] as $img) {
                    $oldImgs[] = $img;
                }
                $allVarArr = array_merge($oldVars, $variant_arr);
                $allImgsArr = array_merge($oldImgs, $selImgs);
                // assign variants to new product
                $prod_array = array(
                    "product" => array(
                        "id" => $confProdID,
                        "variants" => $allVarArr,
                        "images" => $allImgsArr,
                    ),
                );
                $editProduct = $this->shopify->call('PUT', '/admin/products/' . $confProdID . '.json', $prod_array);
                // update inventory
                if ($editProduct['variants'][0]['inventory_management'] == 'shopify') {
                    $newProductVariants = $editProduct['variants'];
                    $inventoryItems = array_column($newProductVariants, 'inventory_item_id');
                    foreach ($inventoryItems as $inventory) {
                        $invLevels = $this->shopify->call('GET', '/admin/api/2019-04/inventory_levels.json?inventory_item_ids='.$inventory);
                        foreach ($invLevels as $level) {
                            $locationData = $this->shopify->call('GET', '/admin/api/2019-04/locations/'.$level['location_id'].'.json');
                            if ($locationData['legacy'] == false) {
                                $newInventory = array("location_id" => $level['location_id'], "inventory_item_id" =>$inventory, "available" =>$prod_qty);
                                $updateInventory = $this->shopify->call('POST', '/admin/inventory_levels/set.json', $newInventory);
                            }
                        }
                    }
                }
                // Get final product details
                $newProduct = $this->shopify->call('GET', '/admin/products/' . $confProdID . '.json');
            }
            // get images of new products
            $newProdIMG = array();
            foreach ($newProduct['images'] as $img) {
                array_push($newProdIMG, array("id" => $img['id'], "src" => $img['src']));
            }
            //assign image to all variants of the product
            foreach ($newProduct['variants'] as $variant) {
                if (!$variant['image_id']) {
                    $imgID = 0;
                    foreach ($newProdIMG as $image) {
                        if (strpos(strtolower($image['src']), strtolower($prod_color)) !== false && strpos(strtolower($image['src']), '_front') !== false) {
                            $imgID = $image['id'];
                            break;
                        }
                    }
                    if ($imgID > 0) {
                        $variantArr = array(
                            "variant" => array(
                                "id" => $variant['id'],
                                "image_id" => $imgID,
                            ),
                        );
                        $addImg2var = $this->shopify->call('PUT', '/admin/variants/' . $variant['id'] . '.json', $variantArr);
                    }
                }
            }

            $response['conf_id'] = $newProduct['id'];
            $response['old_conf_id'] = $smplProdID;
            $response['variants'] = array(array("color_id" => $call_params['varColor'], "size_id" => $call_params['data']['sizes']));

            return json_encode($response);
        }
    }
    /**
     * Get product details of preDeco Product
     *
     * @param array|object $call_params
     * @return json
     */
    public function getProductInfo($call_params)
    {
        $sizePos = "";
        $colorPos = "";
        $pid = (isset($call_params['configId']) && trim($call_params['configId']) != '') ? trim($call_params['configId']) : '';
        $vid = (isset($call_params['smplProdID']) && trim($call_params['smplProdID']) != '' && trim($call_params['smplProdID']) > 0) ? trim($call_params['smplProdID']) : '';
        $qty = (isset($call_params['qty']) && trim($call_params['qty']) != '') ? trim($call_params['qty']) : '';
        $refid = (isset($call_params['refid']) && trim($call_params['refid']) != '') ? trim($call_params['refid']) : '';

        $product = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
        if (!($vid > 0) || $vid == '') {
            $vid = $product['variants'][0]['id'];
        }
        $varDet = $this->shopify->call('GET', '/admin/products/' . $pid . '/variants/' . $vid . '.json');
        foreach ($product['options'] as $option) {
            if (strtolower($option['name']) == 'color') {
                $colorPos = $option['position'];
            }
            if (strtolower($option['name']) == 'size') {
                $sizePos = $option['position'];
            }
        }
        $prodData = array();
        $prodData['is_predeco_product_template'] = true;
        $prodData['qty'] = $qty;
        $prodData['id'] = $pid;
        $prodData['refid'] = $refid;
        $prodData['addedprice'] = "0.00";
        $prodData['simple_product']['xe_color'] = $varDet['option' . $colorPos];
        $prodData['simple_product']['xe_size'] = $varDet['option' . $sizePos];
        $prodData['simple_product']['xe_size_id'] = $varDet['option' . $sizePos];
        $prodData['simple_product']['simpleProductId'] = $vid;
        return json_encode(array($prodData), JSON_NUMERIC_CHECK);

    }
    /**
     * Retrieve a single product's category(not used currently)
     *
     * @param array|object $call_params
     * @return json
     */
    public function products_get_category($call_params)
    {
        //get all types first
        $types = $this->products_types();
        $all_categories = array();
        $pid = $call_params['productid'];
        $collect = $this->shopify->call('GET', '/admin/collects.json?product_id=' . $pid);
        foreach ($collect as $category) {
            $all_categories[] = $category['collection_id'];
        }
        return json_encode($all_categories);
    }

    /**
     * Retrieve a single product's variants_list(may be used in future)
     *
     * @param array|object $call_params
     * @param int store
     * @param String confId|product ID
     * @return json
     */
    public function products_get_variants_list($call_params)
    {
        $pid = $call_params['confId'];
        $res['conf_id'] = $pid;
        $shop_prod = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
        $variants = array();
        foreach ($shop_prod['variants'] as $pv) {
            $this_var['color_id'] = $pv['option1'];
            $this_var['size_id'] = self::shopify_size($shop_prod['title']);
            $variants[] = $this_var;
        }
        $res['variants'] = $variants;
        return json_encode($res);
    }

    /**
     * Retrieve a single product's variant inventory level
     *
     * @param array|object $call_params
     * @param string productId
     * @param int store
     * @param string simpleProductId|variantID
     * @return json
     */
    public function products_get_variant_inventory($call_params)
    {
        $pid = $call_params['productId'];
        $vid = $call_params['simpleProductId'];
        $variant_info = array();
        $size_info = array();
        $shop_prod = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
        $shop_variant = $this->shopify->call('GET', '/admin/variants/' . $vid . '.json');
        $colorPos = "";
        $sizePos = "";
        $extraAtt = "";
        if (isset($pid) && $pid != '') {
            $tierDet = $this->getTierPrice($shop_prod);
        }
        foreach ($shop_prod['options'] as $option) {
            if (strtolower($option['name']) == 'color') {
                $colorPos = $option['position'];
            }
            if (strtolower($option['name']) == 'size') {
                $sizePos = $option['position'];
            }
            if (strtolower($option['name']) !== 'size' && strtolower($option['name']) !== 'color' && strtolower($option['name']) !== 'quantity' && strtolower($option['name']) !== 'title') {
                $extraAtt[$option['position']] = $option['name'];
            }
        }
        $prod_color = $shop_variant['option' . $colorPos];
        foreach ($shop_prod['variants'] as $shop_var) {
            if ($shop_var['option' . $colorPos] == $prod_color) {
                $size_info[] = $shop_var['option' . $sizePos];
            }
        }
        $size_info = array_unique($size_info);
        foreach ($shop_prod['variants'] as $shop_var) {
            if ($shop_var['option' . $colorPos] == $prod_color && in_array($shop_var['option' . $sizePos], $size_info)) {
                $size_info = array_diff($size_info, array($shop_var['option' . $sizePos]));
                if ($shop_var['inventory_policy'] == "continue" || $shop_var['inventory_quantity'] > 0 || $shop_var['inventory_management'] !== "shopify") {
                    $this_var['simpleProductId'] = $shop_var['id'];
                    $this_var['xe_color'] = ($shop_var['option' . $colorPos] !== null ? $shop_var['option' . $colorPos] : "");
                    $this_var['xe_size'] = ($shop_var['option' . $sizePos] !== null ? $shop_var['option' . $sizePos] : "");
                    $this_var['xe_color_id'] = ($shop_var['option' . $colorPos] !== null ? $shop_var['option' . $colorPos] : "");
                    $this_var['xe_size_id'] = ($shop_var['option' . $sizePos] !== null ? $shop_var['option' . $sizePos] : "");
                    if ($shop_var['inventory_policy'] == "continue" || !$shop_var['inventory_management'] !== "shopify") {
                        $this_var['quantity'] = 10000;
                    } else {
                        $this_var['quantity'] = $shop_var['inventory_quantity'];
                    }

                    $this_var['minQuantity'] = 1;
                    $this_var['price'] = $shop_var['price'];
                    $this_var['tierPrices'] = $tierDet;
                    $thisProdAttr = array("xe_size" => $this_var['xe_size'], "xe_size_id" => $this_var['xe_size'], "xe_color" => $this_var['xe_color'], "xe_color_id" => $this_var['xe_color']);
                    if (!empty($extraAtt)) {
                        foreach ($extraAtt as $key => $attribute) {
                            $extraAttArr[$attribute] = $pv['option' . $key];
                        }
                        $thisProdAttr = array_merge($thisProdAttr, $extraAttArr);
                    }
                    $this_var['attributes'] = $thisProdAttr;
                    $variant_info[] = $this_var;
                }
            }
        }
        return json_encode(array('quantities' => $variant_info));
    }

    /**
     * Retrieve a single product's info
     *
     * @param array|object $call_params
     * @param String productId| variantID
     * @param String configId| productID
     * @param int store
     * @return json
     */
    public function products_get($call_params)
    {
        $pid = $call_params['productId'];
        $configId = $call_params['configId'];
        $all_categories = array();
        try
        {
            $shop_prod = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
            $shop_var_id = 0;
            $collect = $this->shopify->call('GET', '/admin/custom_collections.json?product_id=' . $pid);
        } catch (Exception $e) {
            $shop_var = $this->shopify->call('GET', '/admin/variants/' . $pid . '.json');
            $shop_var_id = $shop_var['id'];
            $shop_prod = $this->shopify->call('GET', '/admin/products/' . $shop_var['product_id'] . '.json');
            $shop_prod['variants'] = array($shop_var);
            $collect = $this->shopify->call('GET', '/admin/custom_collections.json?product_id=' . $shop_var['product_id']);
        }
        if (isset($configId) && $configId != '') {
            $tierDet = $this->getTierPrice($shop_prod);
        }
        foreach ($collect as $category) {
            if ($category['handle'] != "show-in-designer") {
                $all_categories[] = strval($category['id']);
            }
        }
        foreach ($shop_prod['images'] as $pi) {
            $images[$pi['id']] = $pi;
        }

        $this_prod['pid'] = $shop_prod['id'];
        $this_prod['pname'] = self::shopify_body($shop_prod['title']);
        $this_prod['shortdescription'] = strip_tags(self::shopify_body($shop_prod['body_html']));
        $this_prod['category'] = $all_categories;
        $this_price = 10000000;
        $colorPos = "";
        $sizePos = "";
        $extraAtt = "";
        foreach ($shop_prod['options'] as $option) {
            if (strtolower($option['name']) == 'color') {
                $colorPos = $option['position'];
            }
            if (strtolower($option['name']) == 'size') {
                $sizePos = $option['position'];
            }
            if (strtolower($option['name']) != 'size' && strtolower($option['name']) != 'color' && strtolower($option['name']) != 'quantity' && strtolower($option['name']) !== 'title') {
                $extraAtt[$option['position']] = $option['name'];
            }
        }
        foreach ($shop_prod['variants'] as $pv) {
            if ($pv['price'] < $this_price || $pv['id'] == $shop_var_id) {
                $this_prod['pvid'] = $pv['id'];
                $this_prod['pvname'] = self::shopify_size($shop_prod['title']) . ' / ' . $pv['title'];
                $this_prod['xecolor'] = ($pv['option' . $colorPos] !== null ? $pv['option' . $colorPos] : "");
                $this_prod['xesize'] = ($pv['option' . $sizePos] !== null ? $pv['option' . $sizePos] : "");
                $this_prod['xe_color_id'] = str_replace(' ', '_', $this_prod['xecolor']);
                $this_prod['xe_size_id'] = $this_prod['xesize'];
                $this_prod['minQuantity'] = 1;
                $this_prod['quanntity'] = ($pv['inventory_policy'] =="continue" || $pv['inventory_management'] != "shopify" ? 100000 : $pv['inventory_quantity']);
                $this_prod['price'] = $pv['price'];
                $this_prod['tierPrices'] = $tierDet;
                $this_prod['taxrate'] = 0;
                $color_look = "_" . str_replace(" ", "_", strtolower($this_prod['xecolor'])) . "_";
                $this_prod['pidtype'] = (strpos(strtolower($pv['title']), 'default title') !== false ? "simple" : "configurable");
                $this_prod['pidtype'] = ($this_prod['xesize'] =="" && $this_prod['xecolor'] == "" ? "simple" : "configurable");
                $variantImageID = ($pv['image_id'] ? $pv['image_id'] : $shop_prod['image']['id']);
                if( strpos( $shop_prod['tags'], "Pre-Deco" ) !== false) {
                    $variantImageID = ($pv['image_id'] ? $pv['image_id'] : $shop_prod['images'][1]['id']);
                }
                $color_lookup = ($this_prod['pidtype'] == 'simple' ? '_' : $color_look);
                $this_prod['color_lookup'] = $color_lookup;
                if (isset($images) && $variantImageID && array_key_exists($variantImageID, $images)) {
                    $this_prod['thumbsides'] = array(self::shopify_image($images[$variantImageID]['src'], $this->thumb_size));
                    $this_prod['sides'] = array(self::shopify_image($images[$variantImageID]['src'], $this->full_size));
                    $this_prod['labels'][] = (strpos(strtolower($images[$variantImageID]['src']), "front") !== false) ? "Front" : "";
                    foreach ($images as $pimg) {
                        if (!in_array(self::shopify_image($pimg['src'], $this->full_size), $this_prod['sides'])) {
                            if (strpos(strtolower($pimg['src']), $color_lookup . "left") !== false) {
                                $this_prod['labels'][] = "Left";
                                $this_prod['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "right") !== false) {
                                $this_prod['labels'][] = "Right";
                                $this_prod['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "back") !== false) {
                                $this_prod['labels'][] = "Back";
                                $this_prod['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "top") !== false) {
                                $this_prod['labels'][] = "Top";
                                $this_prod['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            } else if (strpos(strtolower($pimg['src']), $color_lookup . "bottom") !== false) {
                                $this_prod['labels'][] = "Bottom";
                                $this_prod['sides'][] = self::shopify_image($pimg['src'], $this->full_size);
                            }
                        }
                        if (strpos(strtolower($pimg['src']), $color_lookup . "left") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_prod['thumbsides'])) {
                            $this_prod['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "right") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_prod['thumbsides'])) {
                            $this_prod['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "back") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_prod['thumbsides'])) {
                            $this_prod['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "top") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_prod['thumbsides'])) {
                            $this_prod['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                        if (strpos(strtolower($pimg['src']), $color_lookup . "bottom") !== false && !in_array(self::shopify_image($pimg['src'], $this->thumb_size), $this_prod['thumbsides'])) {
                            $this_prod['thumbsides'][] = self::shopify_image($pimg['src'], $this->thumb_size);
                        }

                    }
                } else {
                    if ($call_params['request'] == 'quote') {
                        $this_prod['thumbsides'] = array($shop_prod['image']['src']);
                        $this_prod['sides'] = array($shop_prod['image']['src']);
                        $this_prod['labels'][] = "Front";
                    }else{
                        $this_prod['thumbsides'] = array();
                        $this_prod['sides'] = array();
                        $this_prod['labels'][] = "";
                    }
                }
                $thisProdAttr = array("xe_size" => $this_prod['xesize'], "xe_size_id" => $this_prod['xesize'], "xe_color" => $this_prod['xecolor'], "xe_color_id" => $this_prod['xecolor']);
                if (!empty($extraAtt)) {
                    foreach ($extraAtt as $key => $attribute) {
                        $extraAttArr[$attribute] = $pv['option' . $key];
                    }
                    $thisProdAttr = array_merge($thisProdAttr, $extraAttArr);
                }
                $this_prod['attributes'] = $thisProdAttr;
                $this_price = $pv['price'];
                break;
            }
        }
        return json_encode($this_prod);
    }

    /**
     * Retrieve all configurable products from Shopify
     *
     * @param Bool|object $combine
     * @param array|object $call_params
     * @param string categoryid
     * @param string searchstring
     * @param int store
     * @param array range|int start\int range
     * @param bool loadVariants
     * @param string offset
     * @param int limit
     * @return json
     */
    public function products_all($call_params, $combine = true)
    {
        $offset = $call_params['offset'];
        $range = $call_params['range'];
        $filter['page'] = $offset;
        $filter['range'] = $range['range'];
        $categoryid = $call_params['categoryid'];
        $searchstring = $call_params['searchstring'];
        $preDecorated = $call_params['preDecorated'];
        $allowProd = '';
        $prodStart = '';
        $this_prod = array();
        $all_products = $this->shopify_all_products($filter, $categoryid, $searchstring);
        $custColl = $this->shopify->call('GET', '/admin/custom_collections.json?handle=show-in-designer');
        $designerCol = $custColl[0]['id'];
        $toolProds = array();
        if (!$preDecorated && !empty($all_products)) {
            foreach ($all_products as $p) {
                if (strpos($p['tags'], 'Pre-Deco') !== 0) {
                    $collect = $this->shopify->call('GET', '/admin/custom_collections.json?product_id=' . $p['id']);
                    foreach ($collect as $category) {
                        $all_categories[] = $category['id'];
                    }
                    if (in_array($designerCol, $all_categories)) {
                        $toolProds[] = $p;
                    }
                }
            }
        } elseif ($preDecorated && !empty($all_products)) {
            foreach ($all_products as $p) {
                $collect = $this->shopify->call('GET', '/admin/custom_collections.json?product_id=' . $p['id']);
                foreach ($collect as $category) {
                    $all_categories[] = $category['id'];
                }
                if (in_array($designerCol, $all_categories)) {
                    $toolProds[] = $p;
                }
            }
        } else {
            $prods = array();
        }

        // if ($offset == 1) {
        //     $allowProd = ($offset * $range['range']);
        //     $toolProds = array_slice($toolProds, 0, $allowProd);
        // } elseif ($offset > 1) {
        //     $allowProd = ($offset * $range['range']) - 1;
        //     $prodStart = ($offset - 1) * $range['range'];
        //     $toolProds = array_slice($toolProds, $prodStart, $range['range']);
        // }
        $color_count = count($toolProds);
        foreach ($toolProds as $p) {
            foreach ($p['images'] as $pi) {
                $images[$pi['id']] = $pi;
            }

            $this_prod['description'] = self::shopify_body($p['body_html']);
            $this_prod['price'] = 10000000;
            $this_prod['thumbnail'] = self::shopify_image($p['image']['src'], $this->thumb_size);
            $this_prod['category'] = $all_categories;
            $all_categories = array();
            $this_prod['id'] = $p['id'];
            $this_prod['name'] = self::shopify_body($p['title']);
            foreach ($p['variants'] as $pv) {
                if ($pv['price'] < $this_prod['price']) {
                    $this_prod['price'] = $pv['price'];
                    break;
                }
            }
            $this_prod['product_type'] = (strpos(strtolower($p['variants'][0]['title']), 'default title') !== false ? "simple" : "configurable");
            $prods[] = $this_prod;
        }
        if (empty($prods)) {
            $prods = array();
        }
        return json_encode(array('product' => $prods, 'count' => $color_count));
    }

    /**
     * Retrieve count of all configurable products from Shopify
     *
     * @param array|object $call_params
     * @param int store
     * @return json
     */
    public function products_count($call_params)
    {
        //get all types first
        $types = $this->products_types();
        $params['collection_id'] = SHOPIFY_COLLECTION_ID;
        $prod_count = $this->shopify->call('GET', '/admin/products/count.json', $params);
        return json_encode(array('size' => $prod_count));
    }

    /**
     * retrieve distinct product types from products
     *
     * @param Bool $assoc
     * @param int store
     * @return json
     */
    private function products_types($assoc = false)
    {
        $types = array();
        $all_types = array();
        $current_types = array();
        $type_counter = 1;
        $last_time = 0;
        //look to data file to preserve category ID's
        if (file_exists(DATA_DIR . 'product_types.json')) {
            $current_types = file_get_contents(DATA_DIR . 'product_types.json');
            $current_types = json_decode($current_types);
            $last_time = $current_types->last_time;
            $types = (array) $current_types->current_types;
            $type_counter = key(array_slice($types, -1, 1, true));
            $type_counter++;
        }
        //only fetch and store if cache is busted
        if ((time() - $last_time) >= DATA_CACHE_TIMEOUT) {
            $collection_custom = $this->shopify->call('GET', '/admin/custom_collections.json');
            $collection_smart = $this->shopify->call('GET', '/admin/smart_collections.json');
            foreach ($collection_custom as $p) {
                if ($p['handle'] != "show-in-designer") {
                    $id = $p['id'];
                    if (!in_array($p['title'], $types) && !empty($p['title'])) {
                        $types[$id] = $p['title'];
                    }
                    if (!in_array($p['title'], $all_types)) {
                        $all_types[] = $p['title'];
                    }

                }
            }
            foreach ($collection_smart as $p) {
                if ($p['handle'] != "customized" && $p['handle'] != "all") {
                    $id = $p['id'];
                    if (!in_array($p['title'], $types) && !empty($p['title'])) {
                        $types[$id] = $p['title'];
                    }
                    if (!in_array($p['title'], $all_types)) {
                        $all_types[] = $p['title'];
                    }

                }
            }
            //now account for any potentially deleted types
            $types_check = array();
            foreach ($types as $ti => $t) {
                if (in_array($t, $all_types)) {
                    $types_check[$ti] = $t;
                }

            }
            $types = $types_check;
            //write types to data file
            try
            {
                $types_data['last_time'] = time();
                $types_data['current_types'] = $types;
                file_put_contents(DATA_DIR . 'product_types.json', json_encode($types_data));
            } catch (Exception $e) {}
        }
        //alphabetcially sort types
        $types = array_map('strtolower', $types);
        asort($types);
        if ($assoc) {
            $types_assoc = array();
            foreach ($types as $ti => $t) {
                $types_assoc[] = array('id' => $ti, 'name' => $t);
            }

            return $types_assoc;
        } else {
            return $types;
        }

    }

    /**
     * retrieve images from store
     *
     * @param string $src
     * @param string $size
     * @return string
     */
    public static function shopify_image($src, $size)
    {
        if (empty($size)) {
            return $src;
        }

        $img_path = pathinfo($src);
        return str_replace("." . $img_path['extension'], "_" . $size . "." . $img_path['extension'], $src);
    }

    /**
     * retrieve product description from store
     *
     * @param string $body
     * @return array
     */
    public static function shopify_body($body)
    {
        return strip_tags(str_ireplace('"', "''", $body));
    }

    /**
     * retrieve product title from store
     *
     * @param string $title
     * @return array
     */
    public static function shopify_title($title)
    {
        $title_parts = explode('~', $title);
        return $title_parts[0] . ' Size ' . $title_parts[1];
    }

    /**
     * retrieve product inventory size from store
     *
     * @param string $title
     * @return array
     */
    public static function shopify_size($title)
    {
        $title_parts = explode('~', $title);
        return $title_parts[1];
    }

    /**
     * retrieve product tags from store
     *
     * @param string $prefix
     * @param string $prefix
     * @return array
     */
    public static function shopify_tag_value($prefix, $tags)
    {
        $tags = explode(',', trim(str_replace(", ", ",", $tags)));
        foreach ($tags as $t) {
            $tag_parts = explode('-', $t);
            if ($tag_parts[0] == $prefix) {
                array_shift($tag_parts);
                return implode('-', $tag_parts);
            }
        }
        return null;
    }

    /**
     * retrieve products from store
     *
     * @param string $filter
     * @param string $categoryid
     * @param string $searchstring
     * @return json
     */
    private function shopify_all_products($filter="", $categoryid="", $searchstring="")
    {
        $all_products = array();
        $prod_params['limit'] = $filter['range'];
        $prod_params['page'] = $filter['page'];
        $prod_params['published_status'] = 'published';
        if (!$categoryid && $categoryid == '') {
            $collections = $this->shopify->call('GET', '/admin/custom_collections.json?handle=show-in-designer');
            $prod_params['collection_id'] = $collections[0]['id'];
        }
        if ($categoryid && $categoryid != '') {
            $prod_params['collection_id'] = $categoryid;
        }
        if ($searchstring && $searchstring != '') {
            $prod_params['title'] = $searchstring;
        }
        $products = $this->shopify->call('GET', '/admin/products.json', $prod_params);
        return $products;
    }

    /**
     * retrieve products from store
     *
     * @param array $call_params
     * @param date from
     * @param date to
     * @param int store
     * @return json
     */
    public function orders_graph($call_params)
    {
        $last_week = date('Y-m-d', strtotime("-1 month"));
        $orders = $this->orders_get($last_week);
        $order_data = array();
        foreach ($orders as $oi => $order) {
            $get_prop = array();
            foreach ($order['line_items'] as $lt) {
                $get_prop = $lt['properties'];
            }

            if (count($get_prop) > 0) {
                foreach ($get_prop as $key => $value) {
                    if ($value['name'] == '_refid') {
                        $this_key = date('Y-m-d', strtotime($order['created_at']));
                        $order_data[$this_key] = array_key_exists($this_key, $order_data) ? $order_data[$this_key] + 1 : 1;
                    }
                }
            }
        }
        $res = array();
        foreach ($order_data as $odd => $od) {
            $res[] = array('date' => $odd, 'sales' => $od);
        }

        return json_encode($res);
    }

    /**
     * retrieve orders from store
     *
     * @param array $call_params
     * @param string lastOrderId
     * @param int store
     * @param int range
     * @param date fromDate
     * @param date toDate
     * @return json
     */
    public function orders_all($call_params)
    {
        $lastOdrKey = $call_params['range'];
        $listStart = $call_params['start'];
        $fromDate = $call_params['fromDate'];
        $toDate = $call_params['toDate'];
        // if ($call_params['start'] > 0) {
        // $orders = array_reverse($this->orders_get("", $call_params['lastOrderId'], "list"));
        // } else {
        $orders = $this->orders_get("", $call_params['lastOrderId'], $listStart, $fromDate, $toDate);
        // }
        $canceledOrders = $this->shopify->call('GET', '/admin/orders.json?status=cancelled');
        $archivedOrders = $this->shopify->call('GET', '/admin/orders.json?status=closed');
        $cancelIDs = array();
        $closedIDs = array();
        foreach ($canceledOrders as $co) {
            $cancelIDs[] = $co['id'];
        }
        foreach ($archivedOrders as $ao) {
            $closedIDs[] = $ao['id'];
        }
        $result = array();
        foreach ($orders as $oi => $order) {
            $res['order_id'] = $order['id'];
            $res['order_incremental_id'] = $order['order_number'];
            if (in_array($order['id'], $cancelIDs)) {
                $res['order_status'] = "Canceled";
            } elseif (in_array($order['id'], $closedIDs)) {
                $res['order_status'] = "Archived";
            } else {
                $res['order_status'] = self::shopify_order_status($order);
            }
            $res['order_date'] = date('Y-m-d H:i:s', strtotime($order['created_at']));
            $res['customer_name'] = $order['customer']['first_name'] . " " . $order['customer']['last_name'];
            $res['billing_address'] = array("first_name" => $order['billing_address']['first_name'], "last_name" => $order['billing_address']['last_name'], "fax" => "", "telephone" => $order['billing_address']['phone'], "email" => $order['email'], "city" => $order['billing_address']['city'], "address_1" => $order['billing_address']['address1'], "address_2" => $order['billing_address']['address2'], "state" => $order['billing_address']['province'], "postcode" => $order['billing_address']['zip'], "country" => $order['billing_address']['India'], "region" => "", "company" => $order['billing_address']['company'], );
            $res['shipping_address'] = array("first_name" => $order['shipping_address']['first_name'], "last_name" => $order['shipping_address']['last_name'], "fax" => "", "telephone" => $order['shipping_address']['phone'], "email" => $order['email'], "city" => $order['shipping_address']['city'], "address_1" => $order['shipping_address']['address1'], "address_2" => $order['shipping_address']['address2'], "state" => $order['shipping_address']['province'], "postcode" => $order['shipping_address']['zip'], "country" => $order['shipping_address']['India'], "region" => "", "company" => $order['shipping_address']['company'], );
            $result[] = $res;
        }
        // $result = array_slice($result, 0, $lastOdrKey);
        return json_encode(array('is_Fault' => 0, 'order_list' => $result));
    }

    /**
     * retrieve order detailss from store
     *
     * @param array $call_params
     * @param string orderIncrementId
     * @param int store
     * @return json
     */
    public function orders_order($call_params)
    {
        $order_id = $call_params['orderIncrementId'];
        $order = $this->shopify->call('GET', '/admin/orders/' . $order_id . '.json');
        $colorPos = "";
        $sizePos = "";
        //unpack order info
        $order_details['order_id'] = $order['id'];
        $order_details['order_incremental_id'] = $order['order_number'];
        $order_details['order_status'] = self::shopify_order_status($order);
        $order_details['order_date'] = date('Y-m-d', strtotime($order['created_at']));
        $order_details['customer_id'] = $order['customer']['id'];
        $order_details['customer_name'] = $order['shipping_address']['name'];
        $order_details['customer_email'] = $order['contact_email'];
        $order_details['shipping_method'] = $order['shipping_lines'][0]['title'];
        $order_details['shipping_address']['first_name'] = $order['shipping_address']['first_name'];
        $order_details['shipping_address']['last_name'] = $order['shipping_address']['last_name'];
        $order_details['shipping_address']['fax'] = "";
        $order_details['shipping_address']['region'] = $order['shipping_address']['province'];
        $order_details['shipping_address']['postcode'] = $order['shipping_address']['zip'];
        $order_details['shipping_address']['telephone'] = $order['shipping_address']['phone'];
        $order_details['shipping_address']['city'] = $order['shipping_address']['city'];
        $order_details['shipping_address']['address_1'] = $order['shipping_address']['address1'];
        $order_details['shipping_address']['address_2'] = $order['shipping_address']['address2'];
        $order_details['shipping_address']['state'] = $order['shipping_address']['province_code'];
        $order_details['shipping_address']['company'] = $order['shipping_address']['company'];
        $order_details['shipping_address']['email'] = $order['contact_email'];
        $order_details['shipping_address']['country'] = $order['shipping_address']['country_code'];
        $order_details['billing_address']['first_name'] = $order['billing_address']['first_name'];
        $order_details['billing_address']['last_name'] = $order['billing_address']['last_name'];
        $order_details['billing_address']['fax'] = "";
        $order_details['billing_address']['region'] = $order['billing_address']['province'];
        $order_details['billing_address']['postcode'] = $order['billing_address']['zip'];
        $order_details['billing_address']['telephone'] = $order['billing_address']['phone'];
        $order_details['billing_address']['city'] = $order['billing_address']['city'];
        $order_details['billing_address']['address_1'] = $order['billing_address']['address1'];
        $order_details['billing_address']['address_2'] = $order['billing_address']['address2'];
        $order_details['billing_address']['state'] = $order['billing_address']['province_code'];
        $order_details['billing_address']['company'] = $order['billing_address']['company'];
        $order_details['billing_address']['email'] = $order['contact_email'];
        $order_details['billing_address']['country'] = $order['billing_address']['country_code'];
        //retrieve all products from Shopify
        $prods = $this->shopify_all_products();
        //unpack line items
        $order_details['order_items'] = array();
        foreach ($order['line_items'] as $lii => $li) {
            $product = $this->shopify->call('GET', '/admin/products/' . $li['product_id'] . '.json');
            $variant = $this->shopify->call('GET', '/admin/variants/' . $li['variant_id'] . '.json');
            $this_color = "";
            $this_size = "";
            foreach ($product['options'] as $option) {
                if (strtolower($option['name']) == 'color') {
                    $colorPos = $option['position'];
                }
                if (strtolower($option['name']) == 'size') {
                    $sizePos = $option['position'];
                }
            }
            //unpack properties
            $props = array();
            foreach ($li['properties'] as $lip) {
                $props[$lip['name']] = $lip['value'];
            }

            $order_details['order_items'][$lii]['product_id'] = $li['variant_id'];
            $order_details['order_items'][$lii]['product_sku'] = $li['sku'];
            $order_details['order_items'][$lii]['product_name'] = $li['title'] . ' / ' . $li['variant_title'];
            $order_details['order_items'][$lii]['quantity'] = $li['quantity'];
            $order_details['order_items'][$lii]['itemStatus'] = "";
            $order_details['order_items'][$lii]['ref_id'] = $props['_refid'];
            $order_details['order_items'][$lii]['print_status'] = null;
            $order_details['order_items'][$lii]['product_price'] = $li['price'];
            $order_details['order_items'][$lii]['xe_size'] = $variant['option' . $sizePos];
            $order_details['order_items'][$lii]['xe_color'] = $variant['option' . $colorPos];
        }
        return json_encode(array('is_Fault' => 0, 'orderIncrementId' => $order_id, 'order_details' => $order_details));
    }

    /**
     * retrieve Inkxe orders IDs from store
     *
     * @param array $call_params
     * @param string lastOrderId
     * @param int store
     * @param int range
     * @return json
     */
    public function getOrderIdFromStore($call_params)
    {
        $range = $call_params['range'];
        $result = array();
        $get_prop = array();
        if ($call_params['lastOrderId'] > 0) {
            $orders = array_reverse($this->orders_get("", $call_params['lastOrderId'], ""));
        } else {
            $orders = $this->orders_get("", $call_params['lastOrderId'], "");
        }

        $res = array();
        if ($range > 0 && $orders) {
            $kounter = 0;
            foreach ($orders as $oi => $order) {
                $flag = 0;
                foreach ($order['line_items'] as $lt) {
                    if ($flag == 0) {
                        foreach ($lt['properties'] as $prop => $vaalue) {
                            if ($vaalue['name'] == "_refid") {
                                $flag = 1;
                                $res[$kounter]['order_id'] = $order['id'];
                                $res[$kounter]['order_incremental_id'] = $order['order_number'];
                                $kounter++;
                            }
                        }
                    }
                }

            }
        }
        $result = array_reverse($res);
        $orderArr = array_slice($result, 0, $range);
        //echo "<pre>"; print_r(array_reverse($orderArr)); exit;
        return json_encode(array('order_list' => array_reverse($orderArr)));
    }

    /**
     * create folders after placing order
     * currently moved to webhook.php(may be added during optimization)
     * @param array $call_params
     */
    public function createOrderDetails($call_params)
    {
        $orderId = $call_params['orderId'];
        $isOrder = "";
        $order = $this->shopify->call('GET', '/admin/orders/' . $orderId . '.json');
        foreach ($order['line_items'] as $item) {
            foreach ($item['properties'] as $prop => $vaalue) {
                if ($vaalue['name'] == "_refid") {
                    if (!$prop['value']) {
                        exit();
                    }
                }
            }
        }
        // create a folder for the order
        $orderDir = $call_params['folder'] . '/' . $orderId;
        if (!file_exists($orderDir)) {
            mkdir($orderDir, 0777);
        }
        //create order.json file
        $jsonFile = fopen($orderDir . '/' . 'order.json', 'w');
        fwrite($jsonFile, json_encode($order));
        fclose($jsonFile);
        // make folder for each item in the order
        foreach ($order['line_items'] as $item) {
            $item_dir = $orderDir . '/' . $item['id'] . '/';
            mkdir($item_dir);
            foreach ($item['properties'] as $prop) {
                if ($prop['name'] == "_refid") {
                    $svg_root = SVG_DIR . '/previewimg/' . $prop['value'] . '/svg/';
                    $svgs = glob($svg_root . "*.*");
                    foreach ($svgs as $svg) {
                        $svg_copy = str_replace($svg_root, $item_dir, $svg);
                        copy($svg, $svg_copy);
                    }
                }
            }
        }
    }

    /**
     * get orders from store
     *
     * @param string $since
     * @param string $last_id
     * @param string $limit
     */
    private function orders_get($since = "", $last_id = "", $limit = "", $fromDate="", $toDate="")
    {
        $orders = array();
        $lastInfo = $last_id;
        $params['status'] = "any";
        // $params['limit'] = 200;
        if (!empty($last_id)) {
            if ($limit == "") {
                $params['since_id'] = $last_id;
            } 
        }
        if (!empty($since)) {
            $params['created_at_min'] = date('Y-m-d 00:00:00', strtotime($since));
        }
        if (!empty($fromDate) &&  !empty($toDate)) {
            $params['created_at_min'] = date('Y-m-d 00:00:00', strtotime($fromDate));
            $params['created_at_max'] = date('Y-m-d 00:00:00', strtotime($toDate));
        }
        loadMoreOrders:
        if ($limit > 50) {
            $params['page'] = (($limit + (50 -($limit % 50))) / 50)+1;
            $orders_data = $this->shopify->call('GET', '/admin/orders.json', $params);
        } elseif($limit <= 50 && $limit > 0) {
            $params['page'] = 2;
            $orders_data = $this->shopify->call('GET', '/admin/orders.json', $params);
        }else{
            $orders_data = $this->shopify->call('GET', '/admin/orders.json', $params);
        }
        $dataCount = count($orders_data);
        if ($dataCount > 0) {
            foreach ($orders_data as $oi => $order) {
                // if (count($orders) <= 4) {
                // echo count($orders); echo "<br>";
                $get_prop = array();
                foreach ($order['line_items'] as $lt) {
                    foreach ($lt['properties'] as $prop => $vaalue) {
                        if ($vaalue['name'] == "_refid") {
                            $refID = $lt['properties'][0]['value'];
                        }elseif ($vaalue['value']['name'] == "_refid") {
                            $refID = $lt['properties'][0]['value']['value'];
                        }
                    }
                    if ($refID) {
                        $get_prop[] = $refID;
                    }
                    $refID = '';
                }
                if (!empty($get_prop)) {
                    if ($lastInfo == 0) {
                        $last_id = $order['id'];
                    } else {
                        if ($order['id'] < $last_id) {
                            $last_id = $order['id'];
                        }
                    }
                    $orders[] = $order;
                }
                // }
            }
            // while (count($orders) < 20) {
            //     $params['last_id'] = $last_id;
            //     goto loadMoreOrders;
            // }
        }
        return $orders;
    }

    /**
     * get order statuss from store
     *
     * @param array $order
     */
    public function shopify_order_status($order)
    {
        if ($order['financial_status'] == "refunded") {
            return "Refunded";
        }
        if ($order['fulfillment_status'] && $order['financial_status'] != "refunded") {
            if ($order['fulfillment_status'] == "partial") {
                return "Processing";
            } else {
                return "Complete";
            }

        }
        return "Pending";
    }

    public function getCustomPreviewImages($refids = 0, $return = 0)
    {
        try {
            if ($refids) {
                $regidArr = explode(',', $refids);
                $finalArray = array();
                $jsonData = '';
                $fileName = 'designState.json';
                $baseImagePath = PREVIEW_DIR;
                foreach ($regidArr as $keys => $values) {
                    $savePath = $baseImagePath . $values . '/';
                    $stateDesignPath = $savePath . 'svg/';
                    $stateDesignPath = $stateDesignPath . $fileName;
                    $jsonData = json_decode(file_get_contents($stateDesignPath), true);
                    if ($jsonData != '') {
                        $designStatus = 1;
                        $printid = $jsonData['printTypeId'];
                        for ($i = 0; $i < sizeof($jsonData['sides']); $i++) {
                            $productUrl = $jsonData['sides'][$i]['url'];
                            $customImageUrl = $jsonData['sides'][$i]['customizeImage'];
                            $svgData = $jsonData['sides'][$i]['svg'];
                            if ($svgData != '') {$designStatus = 1;} else { $designStatus = 0;}
                            $images[] = $customImageUrl;
                        }
                    } else {
                        $msg = array("status" => "nodata");
                        return json_encode($msg);
                    }
                }
                if ($return) {
                    return $images;
                } else {
                    return json_encode($images);
                }
            } else {
            }
        } catch (Exception $e) {
            $result = array('Caught exception:' => $e->getMessage());
            return json_encode($result);
        }
    }

    /**
     * get tier price details for a product
     *
     * @param string $configID
     * @return Array
     */
    private function getTierPrice($shop_prod)
    {
        $tierPrc = array();
        $atrVal = '';
        foreach ($shop_prod['options'] as $opt) {
            if (strtolower($opt['name']) == 'quantity') {
                $atrVal = $opt['values'];
            }
        }
        if (!empty($atrVal) && $atrVal != '') {
            foreach ($atrVal as $atr) {
                $fstQtys[] = substr($atr, 0, strpos($atr, '-'));
                $pricePos = array_search(min($fstQtys), $fstQtys);
            }
            $prodPrice = (float) substr($atrVal[$pricePos], (strpos($atrVal[$pricePos], '|') + 1));
            foreach ($atrVal as $atrr) {
                $thisTier['tierQty'] = (float) substr($atrr, 0, strpos($atrr, '-'));
                $thisTier['maxQty'] = (float) substr($atrr, (strpos($atrr, '-') + 1), strpos($atrr, '|'));
                $thisPrice = (float) substr($atrr, (strpos($atrr, '|') + 1));
                if ($thisPrice < $prodPrice) {
                    $thisTier['percentage'] = ceil(((($prodPrice - $thisPrice) / $prodPrice) * 100));
                } else {
                    $thisTier['percentage'] = 0.00;
                }
                $thisTier['tierPrice'] = substr($atrr, (strpos($atrr, '|') + 1));
                array_push($tierPrc, $thisTier);
            }
        }
        return $tierPrc;
    }

    public function getSizeVariants($call_params)
    {
        $pid = $call_params['productId'];
        $vid = $call_params['simpleProductId'];
        $variantInfo = array();
        $sizeInfo = array();
        $shopProd = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
        $shopVariant = $this->shopify->call('GET', '/admin/variants/' . $vid . '.json');
        $prodOptions = $this->shopify->call('GET', '/admin/products/' . $pid . '.json?fields=options');
        $colorPos = "";
        $sizePos = "";
        // get position of color and size variants
        foreach ($shopProd['options'] as $option) {
            if (strtolower($option['name']) == 'color') {
                $colorPos = $option['position'];
            }
            if (strtolower($option['name']) == 'size') {
                $sizePos = $option['position'];
            }
        }
        // get all sizes for a product
        foreach ($prodOptions['options'] as $opt) {
            if ($opt['position'] == $sizePos) {
                $allSizes = $opt['values'];
                $allSizes = array_map('strtolower', $allSizes);
                // $color_count = count(array_unique($allColors));
            }
        }
        foreach ($shopProd['variants'] as $shopVar) {
            if (in_array(strtolower($shopVar['option' . $sizePos]), $allSizes)) {
                $allSizes = array_diff($allSizes, array(strtolower($shopVar['option' . $sizePos])));
                if ($shopVar['inventory_policy'] == "continue" || $shopVar['inventory_quantity'] > 0 || $shopVar['inventory_management'] !== "shopify") {
                    $thisVar['simpleProductId'] = $shopVar['id'];
                    $thisVar['xe_color'] = ($shopVar['option' . $colorPos] !== null ? $shopVar['option' . $colorPos] : "");
                    $thisVar['xe_size'] = ($shopVar['option' . $sizePos] !== null ? $shopVar['option' . $sizePos] : "");
                    $thisVar['xe_color_id'] = ($shopVar['option' . $colorPos] !== null ? $shopVar['option' . $colorPos] : "");
                    $thisVar['xe_size_id'] = ($shopVar['option' . $sizePos] !== null ? $shopVar['option' . $sizePos] : "");
                    if ($shopVar['inventory_policy'] == "continue" || !$shopVar['inventory_management'] !== "shopify") {
                        $thisVar['quantity'] = 10000;
                    } else {
                        $thisVar['quantity'] = $shopVar['inventory_quantity'];
                    }
                    $thisVar['minQuantity'] = 1;
                    $thisVar['price'] = $shopVar['price'];
                    $thisVar['attributes'] = array();
                    $variantInfo[] = $thisVar;
                }
            }
        }
        return json_encode(array('quantities' => $variantInfo));
    }

    private function getSmartCollectionIds($call_params ="")
    {
        $smartColIDs = array();
        $smartCols = $this->shopify->call('GET', '/admin/smart_collections.json?fields=id');
        foreach ($smartCols as $smart) {
            $smartColIDs[] = $smart['id'];
        }
        return $smartColIDs;
    }

    public function getPendingOrders($call_params)
    {
        $sinceID = $call_params['sinceID'];
        $orderCount = 0;
        $newOrders = $this->shopify->call('GET', '/admin/orders.json?since_id=' . $sinceID);
        foreach ($newOrders as $order) {
            $get_prop = array();
            foreach ($order['line_items'] as $lt) {
                foreach ($lt['properties'] as $prop => $vaalue) {
                    if ($vaalue['name'] == "_refid") {
                        $refID = $lt['properties'][0]['value'];
                    }
                }

                if ($refID) {
                    $get_prop[] = $refID;
                }
                $refID = '';
            }
            if (!empty($get_prop)) {
                $orderCount++;
            }
        }
        return array('lastOrderID' => $sinceID, 'pendingOrderCount' => $orderCount);
    }

    //Get order details from order id
    public function orderDetailsFromId($call_params)
    {
        $order_id = $call_params['order_id'];
        return $response = $this->shopify->call('GET', '/admin/orders/' . $order_id . '.json');
    }

    public function getOriginalVarID($call_params)
    {
        $pvID = $call_params['pvID'];
        $isEdit = $call_params['isEdit'];
        $variantDetails = $this->shopify->call('GET', '/admin/variants/' . $pvID . '.json');
        $pid = $variantDetails['product_id'];
        $shopProd = $this->shopify->call('GET', '/admin/products/' . $pid . '.json');
        $sizePos = "";
        $parentVarID = "";
        // get position of color and size variants
        foreach ($shopProd['options'] as $option) {
            if (strtolower($option['name']) == 'size') {
                $sizePos = $option['position'];
            }
        }
        if ($isEdit > 0) {
            $parentVarID = substr($variantDetails['sku'], 0, strpos($variantDetails['sku'], '_') );
            return json_encode(array($variantDetails['option' . $sizePos], $parentVarID));
        } else {
            return $variantDetails['option' . $sizePos];
        }

    }

    public function manageInventory($call_params)
    {
        $orderInfo = $this->shopify->call('GET', '/admin/orders/' . $call_params['orderID'] . '.json');
        $orderItems = $orderInfo['line_items'];
        try {
            foreach ($orderInfo['line_items'] as $item) {
                $itemProp = implode(', ', array_column($item['properties'], 'name'));
                if (strpos($itemProp, '_refid') !== false) {
                    $variantSKU = $item['sku'];
                    $arr = explode("_", $variantSKU, 2);
                    $variantId = $arr[0];
                    $quantity = $item['quantity'];
                    $variant['variant'] = array('id' => $variantId, 'inventory_quantity_adjustment' => -$quantity);
                    $updateInventory = $this->shopify->call('PUT', '/admin/variants/' . $variantId . '.json', $variant);
                }
            }
            if (is_array($updateInventory)) {
                return true;
            } else {
                throw new Exception($e);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    private function saveSimplePDP($call_params){
        $imgArr = array();
        $optionArr = array();
        $isDesignerID = self::getCustomColId();
        $productID = $call_params['data']['simpleproduct_id'];
        $images = $call_params['data']['images'];
        $productInfo = $this->shopify->call('GET', '/admin/products/'.$productID.'.json?fields=variants,options,images');
        $thisProdImgs = $productInfo['images'];
        // get parent product images
        $imgArr = array();
        foreach ($call_params['configFile'] as $key => $img) {
            if (strpos($img, "/preDecoProduct/ci_")) {
                $key = $key + 1;
                array_push($imgArr, array("src" => $img, "position" => $key));
            }
        }
        $imgCount = ($confProdID == 0 ? (count($imgArr) + 1) : (count($thisProdImgs) + 1));
        $selImgs = array();
        foreach ($thisProdImgs as $prodImg) {
            $selImgs[] = array("src" => $prodImg['src'], "position" => $imgCount);
            $imgCount++;
        }
        $imgArr = array_merge($imgArr, $selImgs);
        $product_array = array(
            "product" => array(
                "title" => self::shopify_body(addslashes($call_params['data']['product_name'])),
                "body_html" => self::shopify_body(addslashes($call_params['data']['description'])),
                "published" => true,
                "tags" => "Pre-Deco",
                "images" => $imgArr,
                "image" => array("src" => $call_params['configFile'][0], "position" => 1),
            ),
        );
        $isExtraAttr = false;
        if ($productInfo['variants'][0]['title'] !== 'Default Title') {
            $optionArr = array();
            $variantArr = array();
            foreach ($productInfo['options'] as $key => $opt) {
                array_push($optionArr, array("name" => $opt['name'], "position" => $opt['position']));
            }
            foreach ($productInfo['variants'] as $key => $variant) {
                array_push($variantArr, array("option1" => $variant['option1'], "option2" => $variant['option2'], "option3" => $variant['option3'], "sku" => $call_params['data']['sku'], "price" => $call_params['data']['price'], "inventory_management" => "shopify", "inventory_policy" => "deny"));
            }
        }
        if (!empty($optionArr)) {
            $product_array['product']['options'] = $optionArr;
        }
        if (!empty($variantArr)) {
            $isExtraAttr = true;
            $product_array['product']['variants'] = $variantArr;
        }
        $newProduct = $this->shopify->call('POST', '/admin/products.json', $product_array);
        $newProductVariants = $newProduct['variants'];
        $variantArr = array("variant" => array("inventory_management" => "shopify", "inventory_policy" => "deny", "price" => $call_params['data']['price'], "image_id" => $newProduct['images'][1]['id']));
        if (!$isExtraAttr) {
            $updateVariant = $this->shopify->call('PUT', '/admin/variants/'.$newProductVariants[0]['id'].'.json', $variantArr);
        }
        if ($newProduct['variants'][0]['inventory_management'] == 'shopify') {
            $inventoryItems = array_column($newProductVariants, 'inventory_item_id');
            foreach ($inventoryItems as $inventory) {
                $invLevels = $this->shopify->call('GET', '/admin/api/2019-04/inventory_levels.json?inventory_item_ids='.$inventory);
                foreach ($invLevels as $level) {
                    $locationData = $this->shopify->call('GET', '/admin/api/2019-04/locations/'.$level['location_id'].'.json');
                    if ($locationData['legacy'] == false) {
                        $newInventory = array("location_id" => $level['location_id'], "inventory_item_id" =>$inventory, "available" =>$call_params['data']['qty']-1);
                        $updateInventory = $this->shopify->call('POST', '/admin/inventory_levels/set.json', $newInventory);
                    }
                }
            }
        }
        // assign product to "Show in designer" collection if is customized is checked.
        if ($call_params['data']['is_customized'] == 1) {
            $customArr = array(
                "collect" => array(
                    "product_id" => $newProduct['id'],
                    "collection_id" => $isDesignerID,
                ),
            );
            $addCustomCol = $this->shopify->call('POST', '/admin/collects.json', $customArr);
        }
        // assign categories one by one (collections)
        $smartColIDs = $this->getSmartCollectionIds();
        if (!empty($call_params['data']['cat_id'])) {
            foreach ($call_params['data']['cat_id'] as $catID) {
                if (!in_array($catID, $smartColIDs)) {
                    $catArr = array(
                        "collect" => array(
                            "product_id" => $newProduct['id'],
                            "collection_id" => $catID,
                        ),
                    );
                    $addCategory = $this->shopify->call('POST', '/admin/collects.json', $catArr);
                } else {
                    continue;
                }
            }
        }
        $response['conf_id'] = $newProduct['id'];
        $response['old_conf_id'] = $productID;
        $response['variants'] = array();

        return json_encode($response);
    }

    public function getCustomers($call_params)
    {
        $users = array();
        $params['limit'] = $call_params['limit'];
        $params['page'] = $call_params['page'];
        $customersCount = $this->shopify->call('GET', '/admin/customers/count.json');
        if ($call_params['searchString'] != '') {
            $customers = $this->shopify->call('GET', '/admin/customers/search.json?query='.$call_params['searchString']);
        }else{
            $customers = $this->shopify->call('GET', '/admin/customers.json', $params);
        }
        foreach ($customers as $cust) {
            $user = array();
            $user['store_customer_id'] = $cust['id'];
            $user['first_name'] = $cust['first_name'];
            $user['last_name'] = $cust['last_name'];
            $user['email'] = $cust['email'];
            $user['contact_no'] = $cust['phone'];
            $user['user_registered'] = date('d F Y',strtotime($cust['created_at']));
            $user['company'] = $cust['default_address']['company'];
            $user['total_spent'] = $cust['total_spent'] . "(" . $cust['orders_count'] . ")";
            $users['customer_list'][] = $user;
        }
        $users['user_count'] = $customersCount;
        return $users;
    }

    public function getUserDetails($call_params)
    {
        $customerID = $call_params['customerID'];
        $customerDetails = array();
        $customer = $this->shopify->call('GET', '/admin/customers/' . $customerID . '.json');
        $customerDetails['store_customer_id'] = $customer['id'];
        $customerDetails['first_name'] = $customer['first_name'];
        $customerDetails['last_name'] = $customer['last_name'];
        $customerDetails['email'] = $customer['email'];
        $customerDetails['contact_no'] = $customer['phone'];
        $customerDetails['company_name'] = $customer['default_address']['company'];
        $customerDetails['company_url'] = '';
        $customerDetails['billing_address_1'] = $customer['default_address']['address1'];
        $customerDetails['billing_address_2'] = $customer['default_address']['address2'];
        $customerDetails['billing_city'] = $customer['default_address']['city'];
        $customerDetails['billing_state'] = $customer['default_address']['province'];
        $customerDetails['billing_zip'] = $customer['default_address']['zip'];
        $customerDetails['billing_country'] = $customer['default_address']['country'];
        $customerDetails['billing_country_code'] = $customer['default_address']['country_code'];
        $customerDetails['shipping_address_1'] = $customer['default_address']['address1'];
        $customerDetails['shipping_address_2'] = $customer['default_address']['address2'];
        $customerDetails['shipping_city'] = $customer['default_address']['city'];
        $customerDetails['shipping_country'] = $customer['default_address']['country'];
        $customerDetails['shipping_state'] = $customer['default_address']['province'];
        $customerDetails['shipping_zip'] = $customer['default_address']['zip'];
        $customerDetails['shipping_country_code'] = $customer['default_address']['country_code'];
        return $customerDetails;
    }

    public function getCountries()
    {
        $allCountries = array();
        $countries = $this->shopify->call('GET', '/admin/countries.json?fields=code,name');
        foreach ($countries as $country) {
            $thisCountry = array();
            $thisCountry['countries_code'] = $country['code'];
            $thisCountry['countries_name'] = $country['name'];
            $allCountries[] = $thisCountry;
        }
        return $allCountries;
    }

    public function getStates($call_params)
    {
        $countryCode = $call_params['code'];
        $allStates = array();
        $countries = $this->shopify->call('GET', '/admin/countries.json?fields=code,name,id');
        $countryID = '';
        foreach ($countries as $country) {
            if ($country['code'] == $countryCode) {
                $countryID = $country['id'];
            }
        }
        if ($countryID != '') {
            $states = $this->shopify->call('GET', '/admin/countries/'.$countryID.'/provinces.json?fields=code,name');
            foreach ($states as $state) {
                $thisState = array();
                $thisState['state_code'] = $state['code'];
                $thisState['state_name'] = $state['name'];
                $allStates[] = $thisState;
            }
        }
        return $allStates;
    }

    public function addCustomer($call_params)
    {
        $thisUserID = '';
        $username = "";
        $checkCustomer = $this->shopify->call('GET', '/admin/customers/search.json?query=' . $call_params['user_email']);
        $usersFound = $checkCustomer[0];
        if (!empty($usersFound)) {
            $thisUserID = $usersFound['id'];
            $result = array('customerID' => $thisUserID, 'status' => 'EMAIL_EXISTS');
        } else {
            $thisUserPWD = $call_params['user_password'];
            $address = array(
                'address1' =>  $call_params['shipping_address_1'],
                'address2' =>  $call_params['shipping_address_2'],
                'city' =>  $call_params['shipping_city'],
                'province' =>  $call_params['shipping_state'],
                'phone' =>  $call_params['billing_phone'],
                'zip' =>  $call_params['shipping_postcode'],
                'last_name' =>  $call_params['last_name'],
                'first_name' =>  $call_params['first_name'],
                'country' =>  $call_params['shipping_country'],
                'company' =>  $call_params['company_name'],
                'default' => true
            );
            $newUser = array('customer' => array('first_name' => $call_params['first_name'],
                'last_name' => $call_params['last_name'],
                'email' => $call_params['user_email'],
                'verified_email' => true,
                'password' => $thisUserPWD,
                'password_confirmation' => $thisUserPWD,
                'send_email_welcome' => false,
                'addresses' => array($address),
                'phone' => $call_params['billing_phone'],
                'tags' => 'quoted',
            ));
            $newCustomer = $this->shopify->call('POST', '/admin/customers.json', $newUser);
            $thisUserID = $newCustomer['id'];
            $result = array('customerID' => $thisUserID, 'status' => 'NEW_USER_CREATED');
        }
        return $result;
    }

    public function updateCustomer($call_params)
    {
        $updateStatus = false;
        $thisUserID = $call_params['store_customer_id'];
        $custDataget = $this->shopify->call('GET', '/admin/customers/'.$thisUserID.'.json');
        $defaultAddID = $custDataget['default_address']['id'];
        $usersFound = $checkCustomer[0];
        
        $address = array(
            'address1' =>  $call_params['shipping_address_1'],
            'address2' =>  $call_params['shipping_address_2'],
            'city' =>  $call_params['shipping_city'],
            'province' =>  $call_params['shipping_state'],
            'phone' =>  $call_params['contact_no'],
            'zip' =>  $call_params['shipping_postcode'],
            'last_name' =>  $call_params['last_name'],
            'first_name' =>  $call_params['first_name'],
            'country' =>  $call_params['shipping_country'],
            'company' =>  $call_params['company_name'],
            'default' => true
        );
        $updatedAddress = $this->shopify->call('PUT', '/admin/customers/'.$thisUserID.'/addresses/'.$defaultAddID.'.json', array('address' => $address));
        $userData = array('customer' => array('first_name' => $call_params['first_name'],
            'id' => $thisUserID,
            'last_name' => $call_params['last_name'],
            'phone' => $call_params['contact_no']
        ));
        $updatedData = $this->shopify->call('PUT', '/admin/customers/'.$thisUserID.'.json', $userData);
        if ($updatedData['id'] > 0) {
            $updateStatus = true;
        }
        return $updateStatus;
    }

    public function deleteCustomers($call_params)
    {
        try {
            if ($call_params['customerID'] != '') {
                $newCustomer = $this->shopify->call('DELETE', '/admin/customers/'.$call_params['customerID'].'.json');
                return array('status' => 'success', 'message' => 'Deleted customer');
            }
        } catch (Exception $e) {
            return array('status' => 'failed', 'message' => 'Failed to delete customer');
        }
    }

    public function getCustomerAddress($call_params)
    {
        if ($call_params['customerID'] != '') {
            $alladdress = array();
            $addresses = $this->shopify->call('GET', '/admin/customers/'.$call_params['customerID'].'/addresses.json');
            foreach ($addresses as $add) {
                $thisAddress = array();
                $thisAddress['id'] = $add['id'];
                $thisAddress['address_line_one'] = $add['address1'];
                $thisAddress['address_line_two'] = $add['address2'];
                $thisAddress['city'] = $add['city'];
                $thisAddress['state'] = $add['province'];
                $thisAddress['postcode'] = $add['zip'];
                $thisAddress['country'] = $add['country_name'];
                $alladdress[] = $thisAddress;
            }
        }
        return $alladdress;
    }

    public function addCustomerAddress($call_params)
    {
        $thisUserID = $call_params['user_id'];
        $address = array(
            'address1' =>  $call_params['address_line_one'],
            'address2' =>  $call_params['address_line_two'],
            'city' =>  $call_params['city'],
            'province' =>  $call_params['state'],
            'zip' =>  $call_params['postcode'],
            'country' =>  $call_params['country']
        );
        $updatedAddress = $this->shopify->call('POST', '/admin/customers/'.$thisUserID.'/addresses.json', array('address' => $address));
        if ($updatedAddress['id'] > 0) {
            return array('status' => 'success', 'user_id' => $updatedAddress['id']);
        }
        
    }
    public function getProductSKU($call_params){
        $thisProduct = $this->shopify->call('GET', '/admin/products/' . $call_params['productID'] . '.json');
        $skuData = array();
        $skuData['sku'] = $thisProduct['variants'][0]['sku'];
        $skuData['product_image_url'] = $thisProduct['image']['src'];
        return $skuData;
    }
}
