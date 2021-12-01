<?php
/**
 * Manage Order at Prestashop store end as well as at Admin end
 *
 * PHP version 5.6
 *
 * @category  Store_Order
 * @package   Order
 * @author    Radhanatha <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace OrderStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Order Controller
 *
 * @category Store_Order
 * @package  Order
 * @author   Radhanatha <radhanatham@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreOrdersController extends StoreComponent
{
    /**
     * Initialize Construct
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     */
    public function __construct()
    {
        parent::__construct();
        $this->demoDataJSON = str_replace(BASE_DIR."/api/v1", "", RELATIVE_PATH)."mockupData/JSON/mockData.json";
        $this->storeURL = str_replace("/".BASE_DIR."/api/v1/", "", BASE_URL);
    }

    /**
     * Get list of orders or a Single orders from the WooCommerce API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Order List in Json format
     */
    public function getOrders($request, $response, $args)
    {
        $orders = $lineItem = $storeResponse = [];
        $jsonResponse = [
            'status' => 1,
            'records' => 0,
            'total_records' => 0,
            'message' => message('Orders', 'not_found'),
            'data' => [],
        ];
        try {
            if (isset($args['id']) && $args['id'] > 0) {
                // Fetch Single Order Details
                // Get static Data from JSON
                $data = file_get_contents($this->demoDataJSON);
                $jsonData = json_decode($data,true);
                $singleOrderDetails = $jsonData['getOrderDetails'];
                $singleOrderDetails['data']['orders'][0]['images'][0]['src'] = $this->storeURL . $singleOrderDetails['data']['orders'][0]['images'][0]['src'];
                $singleOrderDetails['data']['orders'][0]['images'][0]['thumbnail'] = $this->storeURL . $singleOrderDetails['data']['orders'][0]['images'][0]['thumbnail'];
                $orders = $singleOrderDetails['data'];
                $storeResponse = [
                    'total_records' => 1,
                    'order_details' => $orders,
                ];
                // End
            } else {
                // Get all requested Query params
                $filters = [
                    'search' => $request->getQueryParam('name'),
                    'page' => $request->getQueryParam('page'),
                    'sku' => $request->getQueryParam('sku'),
                    'print_type' => $request->getQueryParam('print_type'),
                    'is_customize' => $request->getQueryParam('is_customize'),
                    'order_by' => $request->getQueryParam('orderby'),
                    'order' => $request->getQueryParam('order'),
                    'to' => $request->getQueryParam('to'),
                    'from' => $request->getQueryParam('from'),
                    'per_page' => $request->getQueryParam('per_page'),
                    'customer_id' => $request->getQueryParam('customer_id'),
                ];
                if ($request->getQueryParam('is_customize')) {
                    // Get static Data from JSON
                    $data = file_get_contents($this->demoDataJSON);
                    $jsonData = json_decode($data,true);
                    $orders = $jsonData['getOrderList'];
                    // End
                }else{
                    // Get static Data from JSON
                    $data = file_get_contents($this->demoDataJSON);
                    $jsonData = json_decode($data,true);
                    $orders = $jsonData['getOrderList'];
                    // End
                }
                if (!empty($orders['data'])) {
                    $storeResponse = $orders;
                    $storeResponse = [
                        'total_records' => $orders['records'],
                        'order_details' => $orders['data'],
                    ];

                }
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
     * Get list of orders from the Prestashop API
     *
     * @param $filters All order parameters
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Order List in Json format
     */
    private function getAllOrders($filters)
    {
        return array();
    }

    /**
     * Get list of Order Logs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Responce object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Order List in Json format
     */
    public function getStoreLogs($request, $response, $args)
    {
        $jsonResponse = [];
        $jsonData = '{"orders":[{"id":6,"id_address_delivery":"6","id_address_invoice":"6","id_cart":"6","id_currency":"1","id_lang":"2","id_customer":"3","id_carrier":"3","current_state":"1","module":"ps_checkpayment","invoice_number":"0","invoice_date":"0000-00-00 00:00:00","delivery_number":"0","delivery_date":"0000-00-00 00:00:00","valid":"0","date_add":"2020-12-11 06:43:41","date_upd":"2020-12-11 06:43:42","shipping_number":"","id_shop_group":"1","id_shop":"1","secure_key":"2f2d5d50cacc6b63fcee81bcbcabee78","payment":"Payments by check","recyclable":"0","gift":"0","gift_message":"","mobile_theme":"0","total_discounts":"0.000000","total_discounts_tax_incl":"0.000000","total_discounts_tax_excl":"0.000000","total_paid":"102.000000","total_paid_tax_incl":"102.000000","total_paid_tax_excl":"102.000000","total_paid_real":"0.000000","total_products":"100.000000","total_products_wt":"100.000000","total_shipping":"2.000000","total_shipping_tax_incl":"2.000000","total_shipping_tax_excl":"2.000000","carrier_tax_rate":"0.000","total_wrapping":"0.000000","total_wrapping_tax_incl":"0.000000","total_wrapping_tax_excl":"0.000000","round_mode":"2","round_type":"2","conversion_rate":"1.000000","reference":"ONAQWRKWL","ref_id":"1","associations":{"order_rows":[{"id":"8","product_id":"20","product_attribute_id":"44","product_quantity":"1","product_name":"Men Tshirt - Size : XXL","product_reference":"Men Tshirt","product_ean13":"","product_isbn":"","product_upc":"","product_price":"100.000000","id_customization":"0","unit_price_tax_incl":"100.000000","unit_price_tax_excl":"100.000000","ref_id":"1"}]}}]}';
        //return json format
        $ordersArr = json_decode($jsonData, true);
        $storeResp = $ordersArr['orders'][0];
        $storeOrderLog = [];
        if (!empty($storeResp['id']) && $storeResp['id'] > 0) {
            $storeOrderLog[] = [
                'id' => $storeResp['id'],
                'order_id' => $storeResp['id'],
                'agent_type' => 'admin',
                'agent_id' => null,
                'store_id' => 1,
                'message' => 'Awaiting check payment',
                'log_type' => 'order_status',
                'status' => 'new',
                'created_at' => date(
                    'Y-m-d H:i:s', strtotime($storeResp['date_add'])
                ),
                'updated_at' => date(
                    'Y-m-d H:i:s', strtotime($storeResp['date_upd'])
                ),
            ];
            if (!empty($storeResp['invoice_date']) && $storeResp['invoice_date'] != "0000-00-00 00:00:00") {
                $storeOrderLog[] = [
                    'id' => $storeResp['id'],
                    'order_id' => $storeResp['id'],
                    'agent_type' => 'admin',
                    'agent_id' => null,
                    'store_id' => 1,
                    'message' => (!empty($storeResp['invoice_date'])
                        && $storeResp['invoice_date'] != "") ? 'Paid' : 'Not-paid',
                    'date_paid' => (
                        !empty($storeResp['invoice_date'])
                        && $storeResp['invoice_date'] != ""
                    ) ? $storeResp['invoice_date'] : null,
                    'payment_method' => (!empty($storeResp['payment'])
                        && $storeResp['payment'] != "")
                    ? $storeResp['payment'] : null,
                    'payment_method_title' => null,
                    'log_type' => 'payment_status',
                    'status' => 'new',
                    'created_at' => date(
                        'Y-m-d H:i:s', strtotime($storeResp['date_add'])
                    ),
                    'updated_at' => date(
                        'Y-m-d H:i:s', strtotime($storeResp['date_upd'])
                    ),
                ];
            }
        }

        return $storeOrderLog;
    }

    /**
     * GET: Get Order items
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return json
     */
    public function orderItemDetails($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $orderArray = [];
        //Get all order details by order id
        $jsonResponse = [];
        $parameters = array();
        $jsonData ='{"orders":[{"id":6,"id_address_delivery":"6","id_address_invoice":"6","id_cart":"6","id_currency":"1","id_lang":"2","id_customer":"3","id_carrier":"3","current_state":"1","module":"ps_checkpayment","invoice_number":"0","invoice_date":"0000-00-00 00:00:00","delivery_number":"0","delivery_date":"0000-00-00 00:00:00","valid":"0","date_add":"2020-12-11 06:43:41","date_upd":"2020-12-11 06:43:42","shipping_number":"","id_shop_group":"1","id_shop":"1","secure_key":"2f2d5d50cacc6b63fcee81bcbcabee78","payment":"Payments by check","recyclable":"0","gift":"0","gift_message":"","mobile_theme":"0","total_discounts":"0.000000","total_discounts_tax_incl":"0.000000","total_discounts_tax_excl":"0.000000","total_paid":"102.000000","total_paid_tax_incl":"102.000000","total_paid_tax_excl":"102.000000","total_paid_real":"0.000000","total_products":"100.000000","total_products_wt":"100.000000","total_shipping":"2.000000","total_shipping_tax_incl":"2.000000","total_shipping_tax_excl":"2.000000","carrier_tax_rate":"0.000","total_wrapping":"0.000000","total_wrapping_tax_incl":"0.000000","total_wrapping_tax_excl":"0.000000","round_mode":"2","round_type":"2","conversion_rate":"1.000000","reference":"ONAQWRKWL","ref_id":"1","associations":{"order_rows":[{"id":"8","product_id":"20","product_attribute_id":"44","product_quantity":"1","product_name":"Men Tshirt - Size : XXL","product_reference":"Men Tshirt","product_ean13":"","product_isbn":"","product_upc":"","product_price":"100.000000","id_customization":"0","unit_price_tax_incl":"100.000000","unit_price_tax_excl":"100.000000","ref_id":"1"}]}}]}';
        $order = json_decode($jsonData, true);
        $singleOrderDetails = $order['orders'][0];
        $lineItem = array();
        $j = 0;
        foreach ($singleOrderDetails['associations']['order_rows'] as $v) {
            $lineItem[$j]['item_id'] = $v['id'];
            $lineItem[$j]['product_id'] = $v['product_id'];
            $lineItem[$j]['name'] = $v['product_name'];
            $lineItem[$j]['quantity'] = $v['product_quantity'];
            $lineItem[$j]['print_status'] = '';
            $lineItem[$j]['variant_id'] = $v['product_attribute_id'] == 0 ? $v['product_id'] : $v['product_attribute_id'];
            $lineItem[$j]['product_sku'] = $v['product_reference'];
            $lineItem[$j]['ref_id'] = $v['ref_id'];
            $j++;
        }
        $orderArray['order_details']['order_id'] = $args['id'];
        $orderArray['order_details']['order_incremental_id'] = $args['id'];
        $orderArray['order_details']['store_id'] = $singleOrderDetails['id_shop'];
        $orderArray['order_details']['customer_id'] = $singleOrderDetails['id_customer'];
        $orderArray['order_details']['order_items'] = $lineItem;
        return $orderArray;
    }

    /**
     * GET : Default order statuses
     *
     * @author radhanatham@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function getDefaultOrderStatuses()
    {
        $orderStatusJosn = '[{"value":"Awaiting bank wire payment","key":"Awaiting bank wire payment"},{"value":"Awaiting Cash On Delivery validation","key":"Awaiting Cash On Delivery validation"},{"value":"Awaiting check payment","key":"Awaiting check payment"},{"value":"Canceled","key":"Canceled"},{"value":"Delivered","key":"Delivered"},{"value":"On backorder (not paid)","key":"On backorder (not paid)"},{"value":"On backorder (paid)","key":"On backorder (paid)"},{"value":"Payment accepted","key":"Payment accepted"},{"value":"Payment error","key":"Payment error"},{"value":"Processing in progress","key":"Processing in progress"},{"value":"Refunded","key":"Refunded"},{"value":"Remote payment accepted","key":"Remote payment accepted"},{"value":"Shipped","key":"Shipped"}]';
        $orderStatus = json_decode($orderStatusJosn, true);
        return $orderStatus;
    }

    /**
     * POST : Order Status changed
     *
     * @param orderId
     * @param orderData
     *
     * @author radhanatham@riaxe.com
     * @date   25 June 2020
     * @return Array
     */
    public function updateStoreOrderStatus($orderId, $orderData)
    {
        $orderStatus = true;
        if ($orderStatus) {
            $status = 'success';
        } else {
            $status = 'failed';
        }
        return $status;
    }

    /**
     * POST : Order placed
     *
     * @param queryArray
     *
     * @author radhanatham@riaxe.com
     * @date   16 May 2020
     * @return Array
     */
    public function storeOrder($queryArray)
    {
        $cartId = $orderId = 0;
        $customerId = $queryArray['customer_id'];
        $productData = $queryArray['product_data'];
        if (!empty($productData)) {
            $orderId = 0;
        }
        if ($orderId) {
            $orderData = ["id" => $orderId];
        } else {
            $orderData = ["id" => 0];
        }
        return $orderData;
    }
}
