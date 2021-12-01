<?php
/**
 * Manage Stiore carts
 *
 * PHP version 5.6
 *
 * @category  Magento Cart API
 * @package   Store
 * @author    Tapas Ranjan<tapasranjanp@riaxe.com>
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
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   07 Jan 2020
     * @return Array response with status msg and cart URL
     */
    public function addToStoreCart($request, $response, $args) 
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $settingsDetails = call_api('settings', 'GET', []);
        $action = $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] 
            ? $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] : 'add';
        $cartItemId = (isset($allPostPutVars['cart_item_id']) 
            && $allPostPutVars['cart_item_id'] !='') 
            ?$allPostPutVars['cart_item_id']:0;
        $cartInfo = [];
        
        // Get quote Id from cookie
        $quoteId = 0;
        if (isset($allPostPutVars['quoteId'])) {
            $quoteId = intval($allPostPutVars['quoteId']);
        } else if (isset($_COOKIE['quoteId'])) {
            $quoteId = intval(base64_decode($_COOKIE['quoteId']));
        }
        
        $customerId = 0;
        if (isset($allPostPutVars['customer_id'])) {
            $customerId = intval($allPostPutVars['customer_id']);
            if ($customerId == 0 || $customerId <= 0) {
                $customerId = 0;
            }
        }

        $filters = array(
            'quoteId' => $quoteId,
            'store' => ($allPostPutVars['store_id']) ? $allPostPutVars['store_id'] : $getStoreDetails['store_id'],
            'customerId' => $customerId,
            'cartItemId' => $cartItemId,
            'customDesignId' => $args,
            'productsData' => $allPostPutVars['product_data'],
            'action' => $action
        );
        
        try {
            if (isset($cartItemId) && $cartItemId != 0 && $action == 'update') {
                $removeCartData = array(
                    'cartItemId' => $cartItemId
                );
                $cartRemoveresult = $this->apiCall('Cart', 'removeCartItem', $removeCartData);
                $cartRemoveresult = $cartRemoveresult->cartRemoveresult;
                $cartRemoveStatus = json_clean_decode($cartRemoveresult, true);
            }
            $result = $this->apiCall('Cart', 'addToCart', $filters);
            $result = $result->result;
            $cartInfo = json_clean_decode($result, true);
            $url = $cartInfo['checkoutURL'] . '?quoteId=' . $cartInfo['quoteId'];
            if ($cartInfo['is_Fault'] == 0) {
                // setting quote id cookie
                $expire = time() + 60 * 60 * 24 * 30; //30 days
                setcookie(
                    "quoteId", base64_encode($cartInfo['quoteId']), $expire, "/"
                );
                $storeResponse = [
                    'status' => 1,
                    'message' => message('Cart', 'saved'),
                    'url' => $url,
                ];
            } else if ($cartInfo['is_Fault'] == 1) {
                $storeResponse = [
                    'status' => 0,
                    'message' => $cartInfo,
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
