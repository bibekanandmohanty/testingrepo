<?php
/**
 * Manage Order at Woo-Commerce store end as well as at Admin end
 *
 * PHP version 5.6
 *
 * @category  Store_Order
 * @package   Order
 * @author    Satyabrata <satyabratap@riaxe.com>
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
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreOrdersController extends StoreComponent
{
    /**
     * Set Date Format
     *
     * @var string
     */
    protected $dateFormat = 'd/M/Y H:i:s';

    /**
     * Initialize Construct
     *
     * @author satyabratap@riaxe.com
     * @date   5 Oct 2019
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Get list of product or a Single product from the WooCommerce API
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   5 Jul 2020
     * @return Order List in Json format
     */
    public function getOrders($request, $response, $args)
    {
        $orders = [];
        $orderOptions = [];
        $orderCount = 0;
        $storeResponse = [];
        $orderId = to_int($args['id']);
        try {
            if (!empty($orderId)) {
                // Fetch Single Order Details
                $url = $this->getExtensionURL() . 'getOrderDetails&order_id='. $orderId;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec($ch);
                curl_close($ch);
                $singleOrderDetailsRes = json_decode($output, true);
                $singleOrderDetails = $singleOrderDetailsRes['order_details'];
                if (!empty($singleOrderDetails)) {
                    if ($singleOrderDetails['customer_id'] > 0) {
                        $orderCount = $this->getTotalOrders($singleOrderDetails['customer_id']);
                    }
                    if (isset($singleOrderDetails['order_items'])
                        && count($singleOrderDetails['order_items']) > 0
                    ) {
                        $lineItem = $singleOrderDetails['order_items'];
                        $lineOrders = $this->_getlineItemDetails($lineItem);
                    }
                    $shippingCost = 0;
                    $totalAmount = 0;
                    $discount = 0;
                    $tax = 0;
                    if ($singleOrderDetails['total_tax']) {
                        $tax = $singleOrderDetails['total_tax'];
                    }
                    if (!empty($singleOrderDetails['amount_details'])) {
                        if (array_key_exists("shipping",$singleOrderDetails['amount_details'])) {
                            $shippingCost = $singleOrderDetails['amount_details']['shipping'];
                        } 
                        if (array_key_exists("total",$singleOrderDetails['amount_details'])) {
                            $totalAmount = $singleOrderDetails['amount_details']['sub_total'];
                        }
                        // For Quotation
                        if (array_key_exists("tax",$singleOrderDetails['amount_details'])) {
                            $tax = $singleOrderDetails['amount_details']['tax'];
                        }
                        if (array_key_exists("discount",$singleOrderDetails['amount_details'])) {
                            $discount = $singleOrderDetails['amount_details']['discount'];
                        }
                    }
                    if ($totalAmount == 0) {
                        $totalAmount = $singleOrderDetails['total'];
                    }
                    $orders = [
                        'id' => $singleOrderDetails['id'],
                        'order_number' => $singleOrderDetails['id'],
                        'customer_first_name' => $singleOrderDetails['billing']['first_name'],
                        'customer_last_name' => $singleOrderDetails['billing']['last_name'],
                        'customer_email' => $singleOrderDetails['billing']['email'],
                        'customer_id' => $singleOrderDetails['customer_id'],
                        'created_date' => $singleOrderDetails['date_created'],
                        'total_amount' => $totalAmount,
                        'total_tax' => $tax,
                        'total_discounts' => $discount,
                        'total_shipping' => $shippingCost,
                        'currency' => $singleOrderDetails['currency'],
                        'note' => $singleOrderDetails['customer_note'],
                        'status' => $singleOrderDetails['status'],
                        'total_orders' => $orderCount,
                        'billing' => $singleOrderDetails['billing'],
                        'shipping' => $singleOrderDetails['shipping'],
                        'payment' => $singleOrderDetails['payment_method_title'],
                        'store_url' => str_replace(BASE_DIR.'/', '', API_URL),
                        'orders' => isset($lineOrders) ? $lineOrders : [],
                    ];
                    $storeResponse = [
                        'total_records' => 1,
                        'order_details' => $orders
                    ];
                }
            } else {
                $to = "";
                $from = "";
                if ($request->getQueryParam('to')) {
                    $dt = new \DateTime($request->getQueryParam('to'));
                    $to = $dt->format('Y-m-d');
                }
                if ($request->getQueryParam('from')) {
                    $dtFrom = new \DateTime($request->getQueryParam('from'));
                    $from = $dtFrom->format('Y-m-d');
                }
                // Get all requested Query params
                $filters = [
                    'search' => $request->getQueryParam('name'),
                    'page' => $request->getQueryParam('page'),
                    'sku' => $request->getQueryParam('sku'),
                    'print_type' => $request->getQueryParam('print_type'),
                    'is_customize' => $request->getQueryParam('is_customize'),
                    'order_by' => $request->getQueryParam('orderby'),
                    'order' => $request->getQueryParam('order'),
                    'to' => $to,
                    'from' => $from,
                    'per_page' => $request->getQueryParam('per_page'),
                    'customer_id' => $request->getQueryParam('customer_id'),
                ];
                $options = "";
                foreach ($filters as $filterKey => $filterValue) {
                    if (isset($filterValue) && $filterValue != "") {
                        $options .= '&' . $filterKey .'='. $filterValue;
                    }
                }
                // Fetch All Orders
                // Calling to Custom API for getting Order List
                $url = $this->getExtensionURL() . 'getOrders' . $options;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec($ch);
                curl_close($ch);
                $result = json_decode($output, true);
                $orders = $result['order_list'];
                // $orders = object_to_array($this->plugin->get('orders', $options));
                if (!empty($orders)) {
                    $storeResponse = $orders;
                    $storeResponse = [
                        'total_records' => count($orders),
                        'order_details' => $orders
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
     * Get list of Order Logs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Responce object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   5 Jul 2020
     * @return Order List in Json format
     */
    public function getStoreLogs($request, $response, $args)
    {
        $orderId = $args['id'];
        $storeResponse = [];
        try {
            $url = $this->getExtensionURL() . 'getOrderDetails&order_id='. $orderId;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $singleOrderDetailsRes = json_decode($output, true);
            $storeResp = $singleOrderDetailsRes['order_details'];
            if (!empty($storeResp['id']) && $storeResp['id'] > 0) {
                $storeResponse[] = [
                    'order_id' => $storeResp['id'],
                    'message' => $storeResp['status'],
                    'log_type' => 'order_status',
                    'status' => 'new',
                    'created_at' => date(
                        $this->dateFormat, strtotime($storeResp['date_created'])
                    ),
                    'updated_at' => date(
                        $this->dateFormat, strtotime($storeResp['date_modified'])
                    ),
                ];
                /**
                 * Woocommerce has no payment history logic. So we need to break one
                 * record to multiple histories.  If customer paid for the order then,
                 * paid details will be pushed to the histiry
                 */
                if (!empty($storeResp['date_paid'])) {
                    $storeResponse[] = [
                        'order_id' => $storeResp['id'],
                        'message' => !empty($storeResp['date_paid']) ? 'Paid' : 'Not-paid',
                        'date_paid' => !empty($storeResp['date_paid'])
                            ? $storeResp['date_paid'] : null,
                        'payment_method' => !empty($storeResp['payment_method'])
                            ? $storeResp['payment_method'] : null,
                        'payment_method_title' => !empty($storeResp['payment_method_title']) 
                            ? $storeResp['payment_method_title'] : null,
                        'log_type' => 'payment_status',
                        'status' => 'new',
                        'created_at' => date(
                            $this->dateFormat, strtotime($storeResp['date_created'])
                        ),
                        'updated_at' => date(
                            $this->dateFormat, strtotime($storeResp['date_modified'])
                        ),
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
                        'module' => 'Get orders log',
                    ],
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Generate thumb images from store product images by using store end image urls
     *
     * @param $imagePath  Product image path
     * @param $resolution Required Size
     *
     * @author tanmayap@riaxe.com
     * @date   24 sep 2019
     * @return Image path
     */
    public function _getVariableImageSizes($imagePath, $resolution)
    {
        // Only available 100, 150, 300, 450 and 768 resolution image sizes
        $imageResolution = 300;
        if (isset($resolution) && ($resolution == 100
            || $resolution == 150 || $resolution == 300
            || $resolution == 450 || $resolution == 768)
        ) {
            $imageResolution = $resolution;
        }
        $explodeImage = explode('/', $imagePath);
        $getImageFromUrl = end($explodeImage);
        $fileExtension = pathinfo($getImageFromUrl, PATHINFO_EXTENSION);
        $fileName = pathinfo($getImageFromUrl, PATHINFO_FILENAME);
        $updatedImageName = $fileName . '-' . $imageResolution . 'x'
            . $imageResolution . '.' . $fileExtension;
        $updatedImagePath = str_replace(
            $getImageFromUrl, $updatedImageName, $imagePath
        );
        return $updatedImagePath;
    }

    /**
     * GET: Get Line Item Decorations of Orders
     *
     * @param $lineItems Line Item Details
     *
     * @author satyabratap@riaxe.com
     * @date   7 jan 2019
     * @return json
     */
    public function _getlineItemDetails($lineItems)
    {
        $lineOrders = [];
        foreach ($lineItems as $orderDetailsKey => $orderDetails) {
            $productImages = [];
            try {
                $productId = isset($orderDetails['variation_id']) 
                            && $orderDetails['variation_id'] > 0 
                            ? $orderDetails['variation_id'] : $orderDetails['product_id'];
                $getProductImages = $this->getProductStoreImages($productId);
            } catch (\Exception $e) {
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Get product details inside line-item',
                        ],
                    ]
                );
            }
            if (!empty($getProductImages)) {
                foreach ($getProductImages as $prodImg) {
                    $productImages[] = [
                        'src' => $prodImg['src'],
                        'thumbnail' => $prodImg['thumbnail'],
                    ];
                }
                $customDesignId = 0;
                if (array_key_exists("ref_id", $orderDetails)) {
                    $customDesignId = $orderDetails['ref_id'];
                }
                
            }
            $lineOrders[$orderDetailsKey] = [
                'id' => $orderDetails['id'],
                'product_id' => $this->getProductIdByVariantID($orderDetails['product_id']),
                'variant_id' => isset($orderDetails['variation_id']) 
                    && $orderDetails['variation_id'] > 0 
                    ? $orderDetails['variation_id'] 
                    : $orderDetails['product_id'],
                'name' => $orderDetails['name'],
                'price' => $orderDetails['price'],
                'quantity' => $orderDetails['quantity'],
                'total' => $orderDetails['total'],
                'sku' => $orderDetails['sku'],
                'images' => $productImages,
                'custom_design_id' => $customDesignId,
            ];
        }
        return $lineOrders;
    }

    /**
     * GET: Get Order items
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   22th Jul 2020
     * @return json
     */
    public function orderItemDetails($request, $response, $args)
    {
        $storeResponse = [];
        $order = [];
        $orderId = $args['id'];
        $isPurchaseOrder = 0;
        if ($args['is_purchase_order'] == true && isset($args['is_purchase_order'])) {
            $isPurchaseOrder = 1;
        }
        try {
            $url = $this->getExtensionURL() . 'getOrderDetails&order_id='. $orderId . '&is_purchase_order=' . $isPurchaseOrder;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $singleOrderDetailsRes = json_decode($output, true);
            $singleOrderDetails = $singleOrderDetailsRes['order_details'];
            $order['order_id'] = $orderId;
            $order['order_incremental_id'] = $orderId;
            $order['customer_id'] = $singleOrderDetails['customer_id'];
            $storeDetails = get_store_details($request);
            $order['store_id'] = $storeDetails['store_id'];
            if (!empty($singleOrderDetails['order_items'])) {
                foreach ($singleOrderDetails['order_items'] as $key => $items) {
                    $attributes = [];
                    $order['order_items'][$key]['item_id'] = $items['id'];
                    $order['order_items'][$key]['print_status'] = null;
                    $order['order_items'][$key]['product_id'] = $this->getProductIdByVariantID($items['product_id']);
                    $order['order_items'][$key]['variant_id'] = (
                        isset(
                            $items['variation_id']
                        ) && $items['variation_id'] != 0
                    ) ? $items['variation_id'] : $items['product_id'];
                    $order['order_items'][$key]['product_sku'] = $items['sku'];
                    $order['order_items'][$key]['product_name'] = $items['name'];
                    $order['order_items'][$key]['quantity'] = $items['quantity'];
                    $order['order_items'][$key]['ref_id'] = $items['ref_id'];
                    if ($isPurchaseOrder) {
                        $order['order_items'][$key]['images'] = $items['images'];
                        $order['order_items'][$key]['categories'] = $items['categories'];
                        $attributes = $items['attributes'];
                        $attribute = [];
                        if (!empty($attributes)) {
                            foreach ($attributes as $keyAttr => $value) {
                                $attributeId = 0;
                                $valId = 0;
                                $attrValDetails = $this->getStoreProductOptionValDetails($value['product_option_value_id']);
                                if (!empty($attrValDetails)) {
                                    $attributeId = $attrValDetails['option_id'];
                                    $valId = $attrValDetails['option_value_id'];
                                }
                                $attrName = $value['name'];
                                $attribute[$attrName]['id'] = $valId;
                                $attribute[$attrName]['name'] = $value['value'];
                                $attribute[$attrName]['attribute_id'] = $attributeId;
                                $attribute[$attrName]['hex-code'] = '';
                            }
                        }
                        $order['order_items'][$key]['attributes'] = $attribute;
                    }
                }
            }
            $storeResponse['order_details'] = $order;
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
     * GET : Default order statuses
     *
     * @author mukeshp@riaxe.com
     * @date   25 July 2020
     * @return Array
     */
    public function getDefaultOrderStatuses() {
        $orderStatus = $this->getStoreOrderStatus();
        return $orderStatus;
    }

    /**
     * POST : Order Status changed
     *
     * @param orderId
     * @param orderData
     *
     * @author mukeshp@riaxe.com
     * @date   02 Aug 2020
     * @return String
     */
    public function updateStoreOrderStatus($orderId, $orderData) {
        $order_status = '';
        $statusResponse = $this->modifyStoreOrderStatus($orderId,$orderData['statusKey']);
        if ($statusResponse > 0) {
            $order_status = "success";
        }
        return $order_status;
    }

    /**
     * POST : Order placed from Quotation
     *
     * @param queryArray
     *
     * @author mukeshp@riaxe.com
     * @date   08 Sept 2020
     * @return Array
     */  

    public function storeOrder( $queryArray ){
        $orderId = $this->placeOrderFromQuotation($queryArray);
        // Returns the order ID
        return array('id' => $orderId);
    }

    public function getStoreOrderLineItemDetails($orderId, $orderItemId, $isCustomer, $storeId) {
        $orderResponse = [];
        $i = 0;
        if (!empty($storeId) && !empty($orderId) && !empty($orderItemId)) {
            $url = $this->getExtensionURL() . 'getOrderItemDetails&order_id='. $orderId . '&order_item_id=' . $orderItemId;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $singleOrderItemDetailsRes = json_decode($output, true);
            $orderResponse['order_id'] = $singleOrderItemDetailsRes['order_item_details']['order_id'];
            $orderResponse['order_number'] = $singleOrderItemDetailsRes['order_item_details']['order_number'];
            $orderResponse['item_id'] = $singleOrderItemDetailsRes['order_item_details']['item_id'];
            $orderResponse['product_id'] = $this->getProductIdByVariantID($singleOrderItemDetailsRes['order_item_details']['product_id']);
            $orderResponse['variant_id'] = $singleOrderItemDetailsRes['order_item_details']['product_id'];
            $orderResponse['name'] = $singleOrderItemDetailsRes['order_item_details']['name'];
            $orderResponse['quantity'] = $singleOrderItemDetailsRes['order_item_details']['quantity'];
            $orderResponse['sku'] = $singleOrderItemDetailsRes['order_item_details']['sku'];
            if ($isCustomer == true) {
                $orderResponse['price'] = $singleOrderItemDetailsRes['order_item_details']['price'];
                $orderResponse['total'] = $singleOrderItemDetailsRes['order_item_details']['total'];
            }
            $orderResponse['images'] = $singleOrderItemDetailsRes['order_item_details']['images'];
            $attributes = $singleOrderItemDetailsRes['order_item_details']['attributes'];
            $orderResponse['categories'] = $singleOrderItemDetailsRes['order_item_details']['categories'];
            $attribute = [];
            if (!empty($attributes)) {
                foreach ($attributes as $key => $value) {
                    $attributeId = 0;
                    $valId = 0;
                    $attrValDetails = $this->getStoreProductOptionValDetails($value['product_option_value_id']);
                    if (!empty($attrValDetails)) {
                        $attributeId = $attrValDetails['option_id'];
                        $valId = $attrValDetails['option_value_id'];
                    }
                    $attrName = $value['name'];
                    $attribute[$attrName]['id'] = $valId;
                    $attribute[$attrName]['name'] = $value['value'];
                    $attribute[$attrName]['attribute_id'] = $attributeId;
                    $attribute[$attrName]['hex-code'] = '';
                }
            }
            $orderResponse['attributes'] = $attribute;
            if ($isCustomer == true) {
                $orderResponse['custom_design_id'] = $singleOrderItemDetailsRes['order_item_details']['custom_design_id'];
                $orderResponse['customer_id'] = $singleOrderItemDetailsRes['order_item_details']['customer_id'];
                $orderResponse['customer_email'] = $singleOrderItemDetailsRes['order_item_details']['customer_email'];
                $orderResponse['customer_first_name'] = $singleOrderItemDetailsRes['order_item_details']['customer_first_name'];
                $orderResponse['customer_last_name'] = $singleOrderItemDetailsRes['order_item_details']['customer_last_name'];
                // BILLING INFORMATION
                $orderResponse['billing']['first_name'] = $singleOrderItemDetailsRes['order_item_details']['billing']['first_name'];
                $orderResponse['billing']['last_name'] = $singleOrderItemDetailsRes['order_item_details']['billing']['last_name'];
                $orderResponse['billing']['company'] = $singleOrderItemDetailsRes['order_item_details']['billing']['company'];
                $orderResponse['billing']['address_1'] = $singleOrderItemDetailsRes['order_item_details']['billing']['address_1'];
                $orderResponse['billing']['address_2'] = $singleOrderItemDetailsRes['order_item_details']['billing']['address_2'];
                $orderResponse['billing']['city'] = $singleOrderItemDetailsRes['order_item_details']['billing']['city'];
                $orderResponse['billing']['state'] = $singleOrderItemDetailsRes['order_item_details']['billing']['state'];
                $orderResponse['billing']['country'] = $singleOrderItemDetailsRes['order_item_details']['billing']['country'];
                $orderResponse['billing']['postcode'] = $singleOrderItemDetailsRes['order_item_details']['billing']['postcode'];
                // SHIPPING INFORMATION
                $orderResponse['shipping']['first_name'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['first_name'];
                $orderResponse['shipping']['last_name'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['last_name'];
                $orderResponse['shipping']['address_1'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['address_1'];
                $orderResponse['shipping']['address_2'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['address_2'];
                $orderResponse['shipping']['city'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['city'];
                $orderResponse['shipping']['state'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['state'];
                $orderResponse['shipping']['country'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['country'];
                $orderResponse['shipping']['postcode'] = $singleOrderItemDetailsRes['order_item_details']['shipping']['postcode'];
            }

        }
        return $orderResponse;
    }
}
