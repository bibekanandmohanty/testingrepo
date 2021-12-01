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

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Carts Controller
 *
 * @category Carts
 * @package  Store
 * @author   Mukesh Pradhan <mukeshp@riaxe.com>
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
     * @author mukeshp@riaxe.com
     * @date   01 Jun 2020
     * @return json response wheather data is saved or any error occured
     */
    public function addToStoreCart($request, $response, $customDesignId)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $settingsDetails = call_api('settings', 'GET', []);
        $action = $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] 
            ? $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] : 'add';
        $cartItemId = (isset($allPostPutVars['cart_item_id'])
            && $allPostPutVars['cart_item_id'] != '')
        ? $allPostPutVars['cart_item_id'] : 0;
        $cartInfo = [];
        $customerId = 0;
        if (isset($allPostPutVars['customer_id'])) {
            $customerId = intval($allPostPutVars['customer_id']);
            if ($customerId == 0 || $customerId <= 0) {
                $customerId = 0;
            }
        }
        $cartData = json_decode(stripslashes($allPostPutVars['product_data']), true);
        $store = $getStoreDetails['store_id'];
        try {
            $addCart = "";
            $cookieObj = $_COOKIE;
            $session_id = $cookieObj['OCSESSID'];
            $productdata = array();
            $j = 0;
            foreach ($cartData as $cart) {
                $cart = (array) $cart;
                if ($cart['qty'] > 0) {
                    $id = (isset($cart['variant_id']) && $cart['variant_id'] != $cart['product_id']) ? $cart['variant_id'] : $cart['product_id'];
                    
                    $options = $this->getProductRelatedOptions($id);
                    $productOptions = array();
                    foreach ($options as $option) {
                        $optionName = $option['name'];
                        if ($optionName == 'refid') {
                            $productOptions[$option['id']] = $customDesignId;
                        } elseif ($optionName != 'xe_is_design') {
                            $productOptions[$option['id']] = $this->getProductOptionValue($cart['options'][$optionName], $option['id']);
                        }
                    }
                    $productdata[$j]['id'] = $id;
                    $productdata[$j]['qty'] = $cart['qty'];
                    $productdata[$j]['options'] = $productOptions;
                    $productdata[$j]['refid'] = $customDesignId;
                    $productdata[$j]['extra_price'] = $cart['added_price'];
                    $productdata[$j]['is_variable_decoration'] = $cart['is_variable_decoration'];
                    $productdata[$j]['session_id'] = $session_id;
                    $j++;
                }
                $i++;
            }
            // For update cart item
            if (isset($cartItemId) && $cartItemId !== 0 && $action == 'update') {
                $removeCart = $this->deleteCartItem($cartItemId);
            }
            // End
            $status = $this->storeAddToCart($productdata);
            if ($status) {
                $storeURL = str_replace(BASE_DIR.DIRECTORY_SEPARATOR, '', API_URL);
                $url = $storeURL . '?route=checkout/cart';
                $storeResponse = [
                    'status' => 1,
                    'message' => message('Cart', 'saved'),
                    'url' => $url,
                    'customDesignId' => $customDesignId,
                ];
            } else {
                $storeResponse = [
                    'status' => 0,
                    'message' => array('is_Fault' => 1),
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
}
