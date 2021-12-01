<?php
/**
 * Manage Stiore carts
 *
 * PHP version 5.6
 *
 * @category  Carts
 * @package   Store
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace CartStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Carts Controller
 *
 * @category Carts
 * @package  Store
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class StoreCartsController extends StoreComponent
{
    /**
     * POST: Save Cart data
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   07 Jan 2020
     * @return json response wheather data is saved or any error occured
     */
    public function addToStoreCart($request, $response, $designID) 
    {
        $storeResponse = [];
        $cartParam = [];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $action = (isset($allPostPutVars['action']) 
            && $allPostPutVars['action'] !='') 
            ?$allPostPutVars['action']:'add';
        $settingsDetails = call_api('settings', 'GET', []);
        $action = $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] 
            ? $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] : 'add';
        $cartItemId = (isset($allPostPutVars['cart_item_id']) 
            && $allPostPutVars['cart_item_id'] !='') 
            ?$allPostPutVars['cart_item_id']:0;
        $cartInfo = [];
        
        try {
            $cartProduct = json_decode($allPostPutVars['product_data'], true);
            $cartParam = $this->createAddToCartLink($cartProduct, $designID);
            $url = "";
            if (!empty($cartParam)) {
                $url = 'https://' . SHOPIFY_SHOP . '.myshopify.com/cart?view=refitem&ref=' . implode('--', $cartParam);
            }
            if (isset($cartItemId) && $cartItemId != 0 && $action == 'update') {
                $url .= "&edit=".$cartItemId;
            }
            if ($url != "") {
                $storeResponse = [
                    'status' => 1,
                    'message' => message('Cart', 'saved'),
                    'url' => $url,
                ];
            } else {
                $storeResponse = [
                    'status' => 0,
                    'message' => message('Cart', 'failed'),
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

    private function createTempProduct($cartArr, $refid)
    {
        try {
            $productId = $cartArr['product_id'];
            $custom_price = $cartArr['added_price'];
            $is_variable_decoration = isset($cartArr['is_variable_decoration']) ? $cartArr['is_variable_decoration'] : 0;
            //$cutom_design_refId = $cartArr['refid'];
            $cutom_design_refId = $refid;
            $quantity = $cartArr['qty'];
            $variantId = $cartArr['variant_id'];
            //$color1 = $cartArr['simple_product']['color1'];
            $xeColor = $cartArr['options']['Color'];
            $xeSize = $cartArr['options']['Size'];
            $product_data = array(
                "product_id" => $productId,
                "variant_id" => $variantId,
                "options" => array('xe_color' => $xeColor, 'xe_size' => $xeSize),
                "custom_price" => $custom_price,
                "is_variable_decoration" => $is_variable_decoration,
                "ref_id" => $refid,
                "qty" => $quantity,
            );
            // if ($custom_price > 0) {
            $result = $this->addCustomProduct($product_data);
            $product = array(
                "product_id" => $result['new_product_id'],
                "qty" => $quantity,
                "variant_id" => $result['new_variant_id'],
            );
            // }else{
            //  $product = array(
            //                "product_id" => $productId,
            //                "qty" => $quantity,
            //                "simpleproduct_id" => $variantId,
            //                "options"=>array('xe_color'=>$xeColor, 'xe_size'=>$xeSize),
            //                "custom_price" => $custom_price,
            //                "custom_design" => $cutom_design_refId,
            //        );
            // }
            return $product;
        } catch (Exception $e) {
            $result = array('Caught exception:' => $e->getMessage());
            return $result;
        }
    }
}
