<?php

/**
 *
 * This Controller used to save, fetch or delete Shopify orders
 *
 * @category   Products
 * @package    WooCommerce API
 * @author     Original Author <debashrib@riaxe.com>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @1.0
 */

namespace OrderStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;
use App\Modules\Products\Controllers\ProductsController;
use App\Modules\Orders\Models\Orders;

class StoreOrdersController extends StoreComponent {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get list of Order or a Single Order Details
     *
     * @author     debashrib@riaxe.com
     * @date       18 dec 2019
     * @parameter  Slim default params
     * @response   Array of list/one order(s)
     */
    public function getOrders($request, $response, $args) {
        $serverStatusCode = OPERATION_OKAY;
        $storeResponse = [];
        $orders = [];

        // Get all requested Query params 
        $filters = [
            'is_customize' => $request->getQueryParam('is_customize'),
            'sku' => $request->getQueryParam('sku'),
            'search' => $request->getQueryParam('name'),
            'page' => $request->getQueryParam('page'),
            'order_id' => $request->getQueryParam('orderid'),
            'per_page' => $request->getQueryParam('per_page'),
            'fromDate' => $request->getQueryParam('from'),
            'customer_id' => $request->getQueryParam('customer_id'),
            'toDate' => $request->getQueryParam('to'),
            'order_status' => $request->getQueryParam('order_status'),
            'order' => (!empty($request->getQueryParam('order')) && $request->getQueryParam('order') != "") ? $request->getQueryParam('order') : 'asc',
            'orderby' => (!empty($request->getQueryParam('orderby')) && $request->getQueryParam('order_by') != "") ? $request->getQueryParam('orderby') : 'id',
        ];

        if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
            // fetching Single Order
            $orders = $this->orderDetails($args['id']);
        } else {
            // fetching all Orders
            $orders = $this->ordersAll($filters);
        }
        if (isset($orders) && is_array($orders) && count($orders) > 0) {
            $storeResponse = [
                    'status' => 1,
                    'records' => (isset($args['id']) && $args['id'] != "" && $args['id'] > 0)?1:count($orders),
                    'order_details' => $orders
                ];
        } else {
            $serverStatusCode = NO_DATA_FOUND;
            $storeResponse = [
                'status' => 0,
                'message' => 'No order available',
                'order_details' => []
            ];
        }
        return $storeResponse;
    }

    /**
     * Get list of Order Logs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Responce object
     * @param $args     Slim's Argument parameters
     *
     * @author debashisd@riaxe.com
     * @date   17th Mar 2020
     * @return Order information in Json format
     */
    public function orderItemDetails($request, $response, $args)
    {
        $orderArray = [];
        //Get all order details by order id
        $orderDetailsObj = $this->orderInfo($args['id']);
        $orderArray = json_clean_decode($orderDetailsObj, true);
        return $orderArray;
    }

    /**
     * Get list of Order Logs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Responce object
     * @param $args     Slim's Argument parameters
     *
     * @author debashisd@riaxe.com
     * @date   17th Mar 2020
     * @return Order List in Json format
     */
    public function getStoreLogs($request, $response, $args)
    {
        $orderArray = [];
        //Get order details by order id
        $orderDetailsObj = $this->getOrderLog($args['id']);
        $orderArray = json_clean_decode($orderDetailsObj, true);
        return $orderArray;
    }
    /**
     * hide or delete duplicate products in Shopify
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Responce object
     * @param $args     Slim's Argument parameters
     *
     * @author debashisd@riaxe.com
     * @date   21st April 2020
     * @return Order List in Json format
     */
    public function editCustomProduct($request, $response, $args)
    {
        //hide or delete duplicate products in Shopify
        $params['product_id'] = $request->getQueryParam('pid');
        $params['isDelete'] = $request->getQueryParam('delete');
        $orderItemStatus = $this->editCustomCartProduct($params);
        return $orderItemStatus;
    }

    /**
     * GET : Default order statuses
     *
     * @author debashisd@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function getDefaultOrderStatuses() {
        $orderStatus = '[{"value":"Order Received","key":"received"},{"value":"Pending payment","key":"pending"},{"value":"Processing","key":"processing"},{"value":"On hold","key":"on-hold"},{"value":"Closed","key":"closed"},{"value":"Cancelled","key":"cancelled"},{"value":"Refunded","key":"refunded"},{"value":"Reopened","key":"reopened"}]';
        return json_decode($orderStatus, true);
    }

    /**
     * POST : Order placed
     *
     * @param orderId
     * @param orderData
     *
     * @author soumyas@riaxe.com
     * @date   03 June 2020
     * @return Array
     */
    public function updateStoreOrderStatus($orderId, $orderData) {
        $orderStatus = ['order_status' => $orderData['statusKey']];
        $ordersInit = new Orders();
        $ordersInit->where('order_id', $orderId)
                    ->update($orderStatus);
        return $this->updateOrderStatuses($orderId, $orderData);
    }

    /**
     * GET : Default order statuses
     *
     * @author debashisd@riaxe.com
     * @date   9th August 2020
     * @return Array
     */
    public function storeOrder($decodeData){
        $orderData = $this->createShopOrder($decodeData);
        return $orderData;
    }

    public function archiveOrderById($request, $response, $args)
    {
        $storeResponse = [];
        $orderIds = $request->getParsedBody();
        try {
            // Calling to Custom API for getting Archive status
            $storeResponse = $this->archiveShopOrders($orderIds);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Archive Status',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * GET: Order details
     *
     * @param $order_id
     * @param $store_id
     *
     * @author soumyas@riaxe.com
     * @date   11 December 2020
     * @return Array
     */
    public function getStoreOrderLineItemDetails($order_id, $orderItemId, $is_customer, $store_id) {
        $orderItemData = $this->getOrderLineItemData($order_id, $orderItemId, $is_customer);
        return $orderItemData;
    }

}
