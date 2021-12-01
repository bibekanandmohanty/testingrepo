<?php
/**
 * Manage Stiore carts
 *
 * PHP version 5.6
 *
 * @category  Carts
 * @package   Store
 * @author    Mukesh Pradhan <mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace CartStoreSpace\Controllers;

use CommonStoreSpace\Controllers\StoreController;
use App\Modules\Settings\Models\Setting;

/**
 * Store Carts Controller
 *
 * @category Carts
 * @package  Store
 * @author   Mukesh Pradhan <mukeshp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class StoreCartsController extends StoreController
{
    /**
     * POST: Save Cart data
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date   09 Jan 2020
     * @return json response wheather data is saved or any error occured
     */
    public function addToStoreCart($request, $response, $customDesignId)
    {
        // Include files from Wordpress core
        require_once($this->storePath['abspath'] . "wp-blog-header.php");
        
        global $woocommerce;
        global $wpdb;
        $isPricingRoundUp = 0;
        $pricingRoundUpType = "";
        $tableAttrTaxonomy = $wpdb->prefix . "woocommerce_attribute_taxonomies";
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $settingsDetails = call_api('settings', 'GET', []);
        $isPricingRoundUp = $settingsDetails['general_settings']['currency']['is_price_round_up'];
        $pricingRoundUpType = $settingsDetails['general_settings']['currency']['price_round_up_type'];
        $action = $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] 
            ? $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] : 'add';
        $cartItemId = (isset($allPostPutVars['cart_item_id'])
            && $allPostPutVars['cart_item_id'] != '')
        ? $allPostPutVars['cart_item_id'] : 0;
        $cartInfo = [];
        $customerData = [];
        $customerId = 0;
        if (isset($allPostPutVars['customer_id'])) {
            $customerId = intval($allPostPutVars['customer_id']);
            if ($customerId == 0 || $customerId <= 0) {
                $customerId = 0;
            }
        }
        $cartData = json_decode(stripslashes($allPostPutVars['product_data']), true);
        if (isset($allPostPutVars['customer_data']))
            $customerData = json_decode(stripslashes($allPostPutVars['customer_data']), true);

        $store = $getStoreDetails['store_id'];
        try {

            // For Tier Price
            if (!empty($cartData)) {
                $productId = $cartData[0]['product_id'];
                $metaDataContent = get_post_meta($productId, 'imprintnext_tier_content');
                $tierPriceData = array();
                $commonTierPrice = array();
                $variantTierPrice = array();
                $sameforAllVariants = $isTier = false;
                if (!empty($metaDataContent)) {
                    $tierPriceData = $metaDataContent[0];
                    $isTier = true;
                    if ($tierPriceData['pricing_per_variants'] == 'true') {
                        $sameforAllVariants = true;
                        foreach ($tierPriceData['price_rules'][0]['discounts'] as $discount) {
                            $commonTierPrice[] = array("upper_limit" => $discount['upper_limit'],
                                    "lower_limit" => $discount['lower_limit'],
                                    "discount" => $discount['discount'],
                                    "discountType" => $tierPriceData['discount_type']
                            );
                        }
                    }else{
                        foreach ($tierPriceData['price_rules'] as $variant) {
                            foreach ($variant['discounts'] as $discount) {
                                $variantTierPrice[$variant['id']][] = array("upper_limit" => $discount['upper_limit'],"lower_limit" => $discount['lower_limit'],
                                        "discount" => $discount['discount'],
                                        "discountType" => $tierPriceData['discount_type']
                                );
                            }
                        }
                    }
                }
            }
            // End

            $addCart = "";
            foreach ($cartData as $cart) {
                $cart = (array) $cart;
                if ($cart['qty'] > 0) {
                    $price = 0;
                    $success = 0;
                    $id = (isset($cart['variant_id']) && $cart['variant_id'] != $cart['product_id']) ? $cart['variant_id'] : $cart['product_id'];
                    $product_id = $cart['product_id'];
                    if ($id != $product_id) {
                        $product = wc_get_product($id);
                    } else {
                        $product = wc_get_product($product_id);
                    }

                    if ($product->price) {
                        $price = $product->price;
                    }
                    $variation = array();
                    foreach ($cart['options'] as $key => $value) {
                        if (strpos($key, "_id") == false) {
                            if ($value != "") {
                                $attrSlug = $wpdb->get_var("SELECT attribute_name FROM $tableAttrTaxonomy WHERE attribute_label = '$key'");
                                if ($attrSlug != "") {
                                    $variation['attribute_pa_' . $attrSlug] = $value;
                                } else {
                                    $variation['attribute_' . $key] = $value;
                                }

                            }
                        }
                    }
                    $cartMeta = array();
                    // For Tier Pricing
                    $variantPrice = 0;
                    if ($isTier) {
                        $variantPrice = ($sameforAllVariants === true ? $this->getPriceAfterTierDiscount($commonTierPrice, $price,$cart['qty']) : $this->getPriceAfterTierDiscount($variantTierPrice[$cart['variant_id']], $price, $cart['qty']));
                        $tierPrice = $variantPrice;
                        $price = $variantPrice;
                    }
                    // End
                    if($cart['is_variable_decoration']){
                        $finalUnitPrice = $cart['added_price'];
                    }else{
                        $finalUnitPrice = $price + $cart['added_price'];
                    }
                    // Calculate Round Up pricing 
                    if ($isPricingRoundUp) {
                        if ($pricingRoundUpType == "upper") {
                            $finalUnitPrice = ceil($finalUnitPrice);
                        } else {
                            $finalUnitPrice = floor($finalUnitPrice);
                        }
                    }
                    $cartMeta['_other_options']['product-price'] = $finalUnitPrice;
                    $cartMeta['custom_design_id'] = $customDesignId;
                    $variation = (array) $variation;
                    if (isset($cartItemId) && $cartItemId !== 0 && $action == 'update') {
                        $removeCart = $woocommerce->cart->remove_cart_item($cartItemId);
                    }
                    // Only for simple products
                    if ($product_id == $id) {
                        $id = 0;
                    }
                    $addCart = $woocommerce->cart->add_to_cart($product_id, $cart['qty'], $id, $variation, $cartMeta);
                    if (json_encode($addCart) != false) {
                        $storeResponse = [
                            'status' => 1,
                            'message' => message('Cart', 'saved'),
                            'url' => $woocommerce->cart->get_cart_url(),
                            'customDesignId' => $customDesignId,
                        ];
                    } else {
                        $storeResponse = [
                            'status' => 0,
                            'message' => array('is_Fault' => 1),
                        ];
                    }
                }
            }
            if($storeResponse['status'] && $allPostPutVars['is_kiosk'] > 0) {
                // Get Production Time
                $settingInit = new Setting();
                $getSettings = $settingInit->where('type', '=', 7);
                $prodTime = 0;
                if ($getSettings->count() > 0) {
                    $data = $getSettings->get();
                    foreach ($data as $value) {
                        if ($value['setting_key'] == "production_time")
                            $prodTime = $value['setting_value'];
                    }
                }
                $now = new \DateTime(); //current date/time
                $now->add(new \DateInterval("PT{$prodTime}H"));
                $delivaryDate = $now->format('jS F, Y');
                // End
                // Create Order
                $order_data = array(
                     'status' => apply_filters('woocommerce_default_order_status', 'processing'),
                     'customer_id' => 0
                );
                $new_order = wc_create_order($order_data);
                add_post_meta($new_order->get_order_number(), 'kiosk_order', 1);
                foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {
                    $item_id = $new_order->add_product(
                            $values['data'], $values['quantity'], array(
                        'variation' => $values['variation'],
                        'totals' => array(
                            'subtotal' => $values['line_subtotal'],
                            'subtotal_tax' => $values['line_subtotal_tax'],
                            'total' => $values['line_total'],
                            'tax' => $values['line_tax'],
                            'tax_data' => $values['line_tax_data'] // Since 2.2
                        )
                            )
                    );
                    wc_add_order_item_meta($item_id,'custom_design_id',$values['custom_design_id']);
                    if ( $cart_item_key ) 
                        $woocommerce->cart->remove_cart_item( $cart_item_key );
                }
                if (!empty($customerData)) {
                    $address = array(
                       'first_name' => $customerData['name'],
                       'email'      => $customerData['email'],
                       'phone'      => $customerData['phone'],
                       'address_1'  => $customerData['address']
                       );
                    $new_order->set_address($address, 'billing');
                    $new_order->set_address($address, 'shipping');
                }
                $new_order->calculate_totals();
                // End

                // create order file
                $this->create_order_files($new_order->get_order_number());

                $storeResponse = [
                    'status' => $storeResponse['status'],
                    'message' => $storeResponse['message'],
                    'url' => $storeResponse['url'],
                    'customDesignId' => $storeResponse['customDesignId'],
                    'orderId' => $new_order->get_order_number(),
                    'deliveryDate' => $delivaryDate,
                ];
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Product add to cart',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Generate Order file
     * @param $order_id
     *
     * @author mukeshp@riaxe.com
     * @date   05 June 2020
     */
    public function create_order_files( $order_id ) {
        $xepath = get_site_url();
        $inkXEDir = get_option('inkxe_dir');
        if (!$inkXEDir) {
            $inkXEDir = "designer";
        }
        $xepath = $xepath . "/" . $inkXEDir . "/";

        $url = $xepath."api/v1/orders/create-order-files/".$order_id;
        $ch = curl_init();           
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);            
        $output=curl_exec($ch);  
        curl_close($ch);
    }

    private function getPriceAfterTierDiscount($tierPriceRule, $price, $quantity){
        $returnPrice = $price;
        foreach ($tierPriceRule as $tier) {
            if ($quantity >= $tier['lower_limit'] && $quantity <= $tier['upper_limit']) {
                $returnPrice = ($tier['discountType'] == "flat"? ($price - $tier['discount']): ($price - (($tier['discount'] / 100) * $price)) );
                break;
            } elseif ($quantity > $tier['upper_limit']) {
                $returnPrice = ($tier['discountType'] == "flat"? ($price - $tier['discount']): ($price - (($tier['discount'] / 100) * $price)) );
            }
        }
        return $returnPrice;
    }
}
