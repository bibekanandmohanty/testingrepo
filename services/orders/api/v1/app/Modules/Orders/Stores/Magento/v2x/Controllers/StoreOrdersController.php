<?php
/**
 *
 * This Controller used fetch  Magento Orders on various endpoints
 *
 * @category   Orders
 * @package    Magento API
 * @author     Tapas Ranjan<tapasranjanp@riaxe.com>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @1.0
 */
namespace OrderStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

class StoreOrdersController extends StoreComponent
{

    /**
     * Get list of orders from the Magento API
     *
     * @author     radhanatham@riaxe.com
     * @date       18 Dec 2019
     * @parameter  Slim default params
     * @response   Array of list/one order(s)
     */
    public function getOrders($request, $response, $args)
    {
        $orders = [];
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        try {
            if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
                $filters = array(
                    'orderId' => $args['id'],
                    'minimalData' => 0,
                    'isPurchaseOrder' => (isset($args['is_purchase_order'])) ? $args['is_purchase_order'] : 0,
                    'store' => $getStoreDetails['store_id']
                );
                //Get order details by order id
                $orderDetailsObj = $this->apiCall('Order', 'getOrderDetails', $filters);
                $orders = json_clean_decode($orderDetailsObj->result, true);
                $storeResponse = [
                    'total_records' => 1,
                    'order_details' => $orders['order_list']
                ];
            } else {
                //Get all order list
                $filters = [
                    'store' => $getStoreDetails['store_id'],
                    'search' => $request->getQueryParam('name') ? $request->getQueryParam('name') : '',
                    'page' => $request->getQueryParam('page') ? $request->getQueryParam('page') : 1,
                    'per_page' => $request->getQueryParam('per_page') ? $request->getQueryParam('per_page') : 40,
                    'after' => $request->getQueryParam('from') ? $request->getQueryParam('from') : date('Y-m-d', strtotime('2015-01-01')),
                    'before' => $request->getQueryParam('to') ? $request->getQueryParam('to') : date('Y-m-d'),
                    'order' => (!empty($request->getQueryParam('order')) && $request->getQueryParam('order') != "") ? $request->getQueryParam('order') : 'DESC',
                    'orderby' => (!empty($request->getQueryParam('orderby')) && $request->getQueryParam('orderby') != "") ? 'created_date' : 'entity_id',
                    'customize' => $request->getQueryParam('is_customize') ? $request->getQueryParam('is_customize') : 0,
                    'customerId' => $request->getQueryParam('customer_id') ? $request->getQueryParam('customer_id') : 0,
                ];
                $orderObj = $this->apiCall('Order', 'getOrders', $filters);
                $orders = json_clean_decode($orderObj->result, true);
                $storeResponse = [
                    'total_records' => $orders['total_records'],
                    'order_details' => $orders['order_list']
                ];
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get orders',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * GET: Get Order items
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tapasranjanp@riaxe.com
     * @date   6th Mar 2020
     * @return Array of a single order details
     */
    public function orderItemDetails($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $storeResponse = [];
        $filters = array(
            'orderId' => $args['id'],
            'minimalData' => 1,
            'isPurchaseOrder' => (isset($args['is_purchase_order'])) ? $args['is_purchase_order'] : 0,
            'store' => $getStoreDetails['store_id']
        );
        try{
            //Get all order details by order id
            $orderDetailsObj = $this->apiCall('Order', 'getOrderDetails', $filters);
            $storeResponse = json_clean_decode($orderDetailsObj->result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get order item details',
                    ],
                ]
            );
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
     * @author tapasranjanp@riaxe.com
     * @date   6th Mar 2020
     * @return Array of order log
     */
    public function getStoreLogs($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $storeResponse = [];
        $filters = array(
            'orderId' => $args['id'],
            'store' => $getStoreDetails['store_id']
        );
        try{
            //Get order details by order id
            $orderDetailsObj = $this->apiCall('Order', 'getOrderlogByOrderId', $filters);
            $storeResponse = json_clean_decode($orderDetailsObj->result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get order log',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
    /**
     * GET : Default order statuses
     *
     * @author tapasranjanp@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function getDefaultOrderStatuses() {
        $storeResponse = [];
        $filters = array(
            'store' => 1//$getStoreDetails['store_id']
        );
        try{
            //Get order all status
            $orderStatusesObj = $this->apiCall('Order', 'getAllOrderStatuses', $filters);
            $orderStatus = json_clean_decode($orderStatusesObj->result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get order all status',
                    ],
                ]
            );
        }
        return $orderStatus;
    }
    /**
     * POST : Order placed
     *
     * @param orderId
     * @param orderData
     *
     * @author tapasranjanp@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function updateStoreOrderStatus($orderId, $orderData) {
        $storeResponse = [];
        $filters = array(
            'orderId' => $orderId,
            'orderStatus' => $orderData['statusKey']
        );
        try{
            //Update order status by order id
            $orderStatusesObj = $this->apiCall('Order', 'updateOrderStatusByOrderId', $filters);
            $orderStatus = json_clean_decode($orderStatusesObj->result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Update order all status',
                    ],
                ]
            );
        }
        return $orderStatus;
    }

    /**
     * POST : Order placed
     *
     * @param queryArray
     *
     * @author debashrib@riaxe.com
     * @date   09 Aug 2020
     * @return Array
     */
    public function storeOrder($queryArray) {
        $storeResponse = [];
        $filters = array(
            'orderData' => json_encode($queryArray)
        );
        try{
            $orderStatusesObj = $this->apiCall('Order', 'placeOrderFromQuotation', $filters);
            $storeResponse = json_clean_decode($orderStatusesObj->result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Order',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * GET: Order single item details
     *
     *
     * @author tapasranjan@riaxe.com
     * @date   11 December 2020
     * @return Array
     */
    public function getStoreOrderLineItemDetails($order_id, $orderItemId, $is_customer, $store_id)
    {
        $storeResponse = [];
        $filters = array(
            'orderId' => $order_id,
            'orderItemId' => $orderItemId,
            'isCustomer' => $is_customer,
            'store' => $store_id
        );
        try{
            //Get all order details by order id
            $orderlineItemDetails = $this->apiCall('Order', 'getOrderLineItemDetails', $filters);
            $storeResponse = json_clean_decode($orderlineItemDetails->result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get order item details',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
}
