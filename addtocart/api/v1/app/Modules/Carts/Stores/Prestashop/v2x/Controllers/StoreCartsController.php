<?php
/**
 * Manage Store carts
 *
 * PHP version 5.6
 *
 * @category  Cart_API
 * @package   Store
 * @author    Radhanatha <radhanatham@riaxe.com>
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
 * @author   Radhanatha <radhanatham@riaxe.com>
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
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return json response wheather data is saved or any error occured
     */
    public function addToStoreCart($request, $response, $args)
    {
        $storeResponse = [];
        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $action = (isset($allPostPutVars['action'])
            && $allPostPutVars['action'] != '')
        ? $allPostPutVars['action'] : 'add';
        $cartItemId = (isset($allPostPutVars['cart_item_id'])
            && $allPostPutVars['cart_item_id'] != '')
        ? $allPostPutVars['cart_item_id'] : 0;

        $customerId = 0;
        if (isset($allPostPutVars['customer_id'])) {
            $customerId = intval($allPostPutVars['customer_id']);
            if ($customerId == 0 || $customerId <= 0) {
                $customerId = 0;
            }
        }
        $settingsDetails = call_curl([], 'settings', 'GET');
        $action = $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting']
        ? $settingsDetails['cart_setting']['cart_edit']['cart_item_edit_setting'] : 'add';
        $cartInfo = array();
        $cartItemArr = $jsonContent = json_clean_decode(
            $allPostPutVars['product_data'], true
        );
        try {
            //remove cart item
            if ($action == 'update' && $cartItemId) {
                foreach ($cartItemArr as $item) {
                    // Initialization of variables //
                    $cartParameter = array();
                    $cartParameter['id'] = $item['product_id'];
                    $cartParameter['custom_fields'] = "";
                    $cartParameter['id_product_attribute'] = $item['variant_id'];
                    $cartParameter['quantity'] = $item['qty'];
                    $cartParameter['ref_id'] = $args;
                    $cartParameter['added_price'] = $item['added_price'];
                    $cartParameter['cart_item_id'] = $cartItemId;
                    // Add to Cart store api call//
                    if ($item['qty'] > 0) {
                        $cartInfo = $this->webService->removeCartItem($cartParameter);
                    }
                }
            }
            //add cart item
            foreach ($cartItemArr as $item) {
                // Initialization of variables //
                $cartParameter = array();
                $cartParameter['id'] = $item['product_id'];
                $cartParameter['custom_fields'] = "";
                $cartParameter['id_product_attribute'] = $item['variant_id'];
                $cartParameter['quantity'] = $item['qty'];
                $cartParameter['ref_id'] = $args;
                $cartParameter['added_price'] = $item['added_price'];
                $cartParameter['is_variable_decoration'] = $item['is_variable_decoration'];
                // Add to Cart store api call//
                if ($item['qty'] > 0) {
                    $cartInfo = $this->webService->addToCart($cartParameter);
                }
            }
            if (!empty($cartInfo)) {
                if ($cartInfo['status']) {
                    $url = $cartInfo['url'];
                    $storeResponse = [
                        'status' => 1,
                        'message' => message('Cart', 'saved'),
                        'url' => $url,
                    ];
                } else {
                    $storeResponse = [
                        'status' => 0,
                        'message' => "Add to cart failed",
                    ];
                }
            } else {
                $storeResponse = [
                    'status' => 0,
                    'message' => "Add to cart failed",
                ];
            }
        } catch (Exception $e) {
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
     * POST: Add Template Product To Cart
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   15 July 2020
     * @return json response wheather data is saved or any error occured
     */
    public function addTemplateProductToCart($request, $response, $args)
    {
        $storeResponse = [];
        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();

        try {
            // Initialization of variables //
            $cartParameter = array();
            $cartParameter['id'] = $allPostPutVars['product_id'];
            $cartParameter['custom_fields'] = "";
            $cartParameter['id_product_attribute'] = $allPostPutVars['variant_id'];
            $cartParameter['quantity'] = $allPostPutVars['order_qty'];
            $cartParameter['ref_id'] = $args;
            $cartParameter['added_price'] = 0;
            // Add to Cart store api call//
            if ($allPostPutVars['order_qty'] > 0) {
                $cartInfo = $this->webService->addToCart($cartParameter);
            }
            if (!empty($cartInfo)) {
                if ($cartInfo['status']) {
                    $url = $cartInfo['url'];
                    $storeResponse = [
                        'status' => 1,
                        'message' => message('Cart', 'saved'),
                        'url' => $url,
                    ];
                } else {
                    $storeResponse = [
                        'status' => 0,
                        'message' => "Add to cart failed",
                    ];
                }
            } else {
                $storeResponse = [
                    'status' => 0,
                    'message' => "Add to cart failed",
                ];
            }
        } catch (Exception $e) {
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
