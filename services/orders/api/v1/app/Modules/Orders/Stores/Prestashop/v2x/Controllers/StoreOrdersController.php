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

        $shopId = $request->getQueryParam('store_id');
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
                $singleOrderDetails = $this->webService->getOrderByOrderId($args['id'], $shopId);
                $lineItem = $singleOrderDetails['line_item'];
                $orders = $singleOrderDetails['data'];
                $storeResponse = [
                    'total_records' => 1,
                    'order_details' => $orders,
                ];
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
                    'id_shop' => $request->getQueryParam('store_id'),
                ];
                // Calling to Custom API for getting Order List
                $orders = $this->getAllOrders($filters);
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
        $storeOrder = [];
        //All Filter columns from url
        $shopId = $filters['id_shop'] ? $filters['id_shop'] : 1;
        $customerId = $filters['customer_id'] ? $filters['customer_id'] : 0;
        $page = $filters['page'] ? $filters['page'] : 1;
        $perpage = $filters['per_page'] ? $filters['per_page'] : 20;
        $sortBy = $filters['order_by'] ? $filters['order_by'] : 'id';
        $order = $filters['order'] == 'ASC' ? 'ASC' : 'DESC';
        $search = $filters['search'] ? $filters['search'] : '';
        $isCustomize = $filters['is_customize'] ? $filters['is_customize'] : 0;
        $sort = $sortBy . '_' . $order;
        $limit = $perpage * $page;
        $orders = array();
        $totalOrdersCount = 0;
        if ($search) {
            $filter = array(
                'resource' => 'orders',
                'display' => 'full',
                'filter[id]' => '%[' . $search . ']%', 'limit' => '' . $limit . '',
                'id_shop' => $shopId,
                'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
            );
            //call to prestashop webservice for get all products id count
            $orderJson = $this->webService->get($filter);
            //return json format
            $ordersArr = json_decode($orderJson, true);
            $parameterCount = array(
                'resource' => 'orders',
                'display' => '[id]',
                'filter[id]' => '%[' . $search . ']%',
                'id_shop' => $shopId,
                'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
            );
        } else {
            // Here we set the option array for the Webservice :
            if (!$isCustomize) {
                if ($customerId) {
                    $parameter = array(
                        'resource' => 'orders',
                        'display' => 'full',
                        'limit' => '' . $limit . '',
                        'filter[id_customer]' => '[' . $customerId . ']',
                        'id_shop' => $shopId,
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                    $parameterCount = array(
                        'resource' => 'orders',
                        'display' => '[id]',
                        'filter[id_customer]' => '[' . $customerId . ']',
                        'id_shop' => $shopId,
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                } else {
                    $parameter = array(
                        'resource' => 'orders',
                        'display' => 'full',
                        'id_shop' => $shopId,
                        'limit' => '' . $limit . '',
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                    $parameterCount = array(
                        'resource' => 'orders',
                        'display' => '[id]',
                        'id_shop' => $shopId,
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                }

            } else {
                if ($customerId) {
                    $parameter = array(
                        'resource' => 'orders',
                        'display' => 'full',
                        'filter[ref_id]' => '![0]',
                        'filter[id_customer]' => '[' . $customerId . ']',
                        'id_shop' => $shopId,
                        'limit' => '' . $limit . '',
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                    $parameterCount = array(
                        'resource' => 'orders',
                        'display' => '[id]',
                        'filter[ref_id]' => '![0]',
                        'filter[id_customer]' => '[' . $customerId . ']',
                        'id_shop' => $shopId,
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                } else {
                    $parameterCount = array(
                        'resource' => 'orders',
                        'display' => '[id]',
                        'filter[ref_id]' => '![0]',
                        'id_shop' => $shopId,
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                    $parameter = array(
                        'resource' => 'orders',
                        'display' => 'full',
                        'filter[ref_id]' => '![0]',
                        'id_shop' => $shopId,
                        'limit' => '' . $limit . '',
                        'sort' => '[' . $sort . ']', 'output_format' => 'JSON',
                    );
                }

            }
            //call to prestashop webservice for get all orders
            $orderJson = $this->webService->get($parameter);
            //return json format
            $ordersArr = json_decode($orderJson, true);
        }
        if (!empty($ordersArr)) {
            $orderJsonCount = $this->webService->get($parameterCount);
            //return json format
            $ordersCountArr = json_decode($orderJsonCount, true);
            $totalOrdersCount = sizeof($ordersCountArr['orders']);
            $totalorders = $ordersArr['orders'];
            if ($page == 1) {
                $allowOrder = ($page * $perpage);
                $totalorders = array_slice($totalorders, 0, $allowOrder);
            } elseif ($page > 1) {
                $allowOrder = ($page * $perpage) - 1;
                $orderstart = ($page - 1) * $perpage;
                $totalorders = array_slice(
                    $totalorders,
                    $orderstart, $perpage
                );
            }
            $i = 0;
            $totalOrder = 0;
            $beforeDate = date('Y-m-d', strtotime('-1 years'));
            $formDate = $filters['from'] ? $filters['from'] : $beforeDate;
            $toDate = $filters['to'] ? $filters['to'] : date('Y-m-d');
            $fromDate = date('Y-m-d', strtotime($formDate));
            $toDate = date('Y-m-d', strtotime($toDate));
            foreach ($totalorders as $k => $v) {
                $date = $v['date_add'];
                $date = date('Y-m-d H:i:s', strtotime($date));
                $orderDate = date('Y-m-d', strtotime($date));
                if (($orderDate >= $fromDate) && ($orderDate <= $toDate)) {
                    $orders[$i]['id'] = $v['id'];
                    $orders[$i]['order_number'] = $v['id'];
                    $customer = $this->webService->getCustomerName(
                        $v['id_customer']
                    );
                    $orders[$i]['customer_first_name'] = $customer['first_name'];
                    $orders[$i]['customer_last_name'] = $customer['last_name'];
                    $orders[$i]['created_date'] = $v['date_add'];
                    $orders[$i]['currency'] = $this->webService->getCurrencyIsoCode(
                        $v['id_currency']
                    );
                    $orders[$i]['is_customize'] = $v['ref_id'] ? 1 : 0;
                    $orders[$i]['total_amount'] = $v['total_paid'];
                    $orders[$i]['production'] = '';
                    $orders[$i]['status'] = $this->webService->getOrderStatus(
                        $v['id']
                    );
                    $orders[$i]['order_total_quantity'] = $this->webService->getOrderTotalQuantity($v['id']);
                    $i++;
                }
            }
        }
        return $storeOrder = [
            'data' => $orders,
            'records' => $totalOrdersCount,
        ];
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
        $parameterl = array(
            'resource' => 'orders',
            'display' => 'full',
            'filter[id]' => '[' . $args['id'] . ']', 'output_format' => 'JSON',
        );
        $jsonData = $this->webService->get($parameterl);
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
                'message' => $this->webService->getOrderStatus($storeResp['id']),
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
        $isPurchaseOrder = (isset($args['is_purchase_order'])) ? $args['is_purchase_order'] : 0;
        //Get all order details by order id
        $jsonResponse = [];
        $parameterl = array(
            'resource' => 'orders',
            'display' => 'full',
            'filter[id]' => '[' . $args['id'] . ']', 'output_format' => 'JSON',
        );
        $jsonData = $this->webService->get($parameterl);
        $order = json_decode($jsonData, true);
        $singleOrderDetails = $order['orders'][0];
        $lineItem = array();
        $j = 0;
        foreach ($singleOrderDetails['associations']['order_rows'] as $v) {
            $lineItem[$j]['item_id'] = $v['id'];
            $lineItem[$j]['product_id'] = $v['product_id'];
            $lineItem[$j]['product_name'] = $v['product_name'];
            $lineItem[$j]['quantity'] = $v['product_quantity'];
            $lineItem[$j]['print_status'] = '';
            $lineItem[$j]['variant_id'] = $v['product_attribute_id'] == 0 ? $v['product_id'] : $v['product_attribute_id'];
            $lineItem[$j]['product_sku'] = $v['product_reference'];
            $lineItem[$j]['ref_id'] = $v['ref_id'];
            if($isPurchaseOrder){
                $option['product_id'] = $v['product_id'];
                $option['variation_id'] = $lineItem[$j]['variant_id'];
                $combination = $this->webService->getAttributeCombinationsById($option);
                foreach ($combination as $key => $value) {
                    $attrName = $value['group_name'];
                    $attrValId = $value['id_attribute_group'];
                    $attrValName = $value['attribute_name'];
                    $idAttribute = $value['id_attribute'];
                    $attribute[$attrName]['id'] = $attrValId;
                    $attribute[$attrName]['name'] = $attrValName;
                    $attribute[$attrName]['attribute_id'] = $idAttribute;
                    $hexCode = '';
                    if ($value['is_color_group']) {
                        $hexCode = $this->webService->getColorHexValue($idAttribute);
                    }
                    $attribute[$attrName]['hex-code'] = $hexCode;
                }
                $category = $this->webService->getCategoryByPid($v['product_id']);
                $lineItem[$j]['images'] = $this->webService->getProducImage($lineItem[$j]['variant_id'], $v['product_id']);
                $lineItem[$j]['attributes'] = $attribute;
                $lineItem[$j]['categories'] = $category;
            }
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
        $orderStatus = $this->webService->getOrderStates();
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
        $orderStatus = $this->webService->updateStoreOrderStatus($orderId, $orderData['statusKey']);
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
     * @author soumyas@riaxe.com
     * @date   16 May 2020
     * @return Array
     */
    public function storeOrder($queryArray)
    {
        $cartId = $orderId = 0;
        $customerId = $queryArray['customer_id'];
        $productData = $queryArray['product_data'];
        if (!empty($productData)) {
            $productTotalPrice = 0;
            foreach ($productData as $k => $item) {
                $cartParameter = array();
                $cartParameter['id'] = $item['product_id'];
                $cartParameter['custom_fields'] = "";
                $cartParameter['id_product_attribute'] = $item['variant_id'];
                $cartParameter['quantity'] = $item['quantity'];
                $cartParameter['ref_id'] = $item['custom_design_id'];
                $cartParameter['added_price'] = $item['design_cost'];
                $cartParameter['id_customer'] = $customerId;
                $productTotalPrice += $item['design_cost'] + $item['unit_price'];
                // Add to Cart store api call//
                if ($item['quantity'] > 0) {
                    $cartId = $this->webService->addToCartProduct($cartParameter);
                }
            }
            if ($cartId) {
                $orderId = $this->webService->createOrderByCustomerId($productData, $cartId, $customerId, $productTotalPrice);
            }
        }
        if ($orderId) {
            $orderData = ["id" => $orderId];
        } else {
            $orderData = ["id" => 0];
        }
        return $orderData;
    }

    /**
     * GET: Order details
     *
     * @param $order_id
     * @param $orderItemId
     * @param $is_customer
     * @param $store_id
     *
     * @author radhanatham@riaxe.com
     * @date   04 Jan 2021
     * @return Array
     */
    public function getStoreOrderLineItemDetails($order_id, $orderItemId, $is_customer, $store_id)
    {
        $jsonResponse = [];
        $jsonResponse = $this->webService->getStoreOrderLineItemDetails($order_id, $orderItemId, $is_customer, $store_id);
        return $jsonResponse;
    }
}
