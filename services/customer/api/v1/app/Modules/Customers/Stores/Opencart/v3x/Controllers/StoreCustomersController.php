<?php
/**
 * Manage Customer
 *
 * PHP version 5.6
 *
 * @category  Customers
 * @package   Eloquent
 * @author    Mukesh <mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace CustomerStoreSpace\Controllers;

use OrderStoreSpace\Controllers\StoreOrdersController;

/**
 * Customer Controller
 *
 * @category Class
 * @package  Customer
 * @author   Mukesh <mukeshp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreCustomersController extends StoreOrdersController
{
    /**
     * Instantiate Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET: Get Customer
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author mukeshp@riaxe.com
     * @date   18 Aug 2020
     * @return json response wheather data is saved or any error occured
     */
    public function getCustomers($request, $response, $args)
    {
        $totalSpent = 0;
        $storeResponse = [];
        $customerOrderDetails = [];
        if (isset($args['id']) && $args['id'] > 0) {
            $isLineItems = to_int((!empty($request->getQueryParam('orders'))
                    && $request->getQueryParam('orders') != "")
                ? $request->getQueryParam('orders') : 0);
            // Fetch Single customers
            try {
                $avtarURL = "https://secure.gravatar.com/avatar/f2df026b8e4c5a6e194c8497e4d9d7ef?s=96&d=mm&r=g";
                $getCustomerData = $this->getStoreCustomer($args['id']);
                if (!empty($getCustomerData)) {
                    if ($getCustomerData[0]['avatar_url']) {
                        $avtarURL = $getCustomerData[0]['avatar_url'];
                    }
                    $orderDetails = $this->_getOrderData($getCustomerData[0], 'customer', $isLineItems);
                    $totalSpent = $orderDetails['total_spent'];
                    $prepareOrder = $orderDetails['prepare_order'];
                    $totalOrderCount = $orderDetails['orders_count'];
                    $lastOrderDetail = (!empty($prepareOrder[0])
                        && $prepareOrder[0] > 0) ? $prepareOrder[0] : null;
                    $lastOrderDetailID = $lastOrderDetail['id'];
                    $lastOrder = time_elapsed($lastOrderDetail['created_date']);
                    $i = 0;
                    $country_name = '';
                    $state_name = '';
                    $addressData = $this->getCustomerShippingAddress($args['id']);
                    $addressData['billing']['email'] = $getCustomerData[0]['email'];
                    if(!empty($addressData['shipping'])) {
                        foreach ($addressData['shipping'] as $key => $value) {
                            $shippingAddress[$i]['first_name'] =  $getCustomerData[0]['firstname'];
                            $shippingAddress[$i]['last_name'] =  $getCustomerData[0]['lastname'];
                            $shippingAddress[$i]['company'] =  $getCustomerData[0]['company'];
                            $shippingAddress[$i]['address_1'] =  $value['address_1'];
                            $shippingAddress[$i]['address_2'] = $value['address_2'];
                            $shippingAddress[$i]['city'] =   $value['city'];
                            $shippingAddress[$i]['postcode'] =  $value['postcode'];
                            $shippingAddress[$i]['country'] =  $value['country_code'];
                            $shippingAddress[$i]['state'] =  $value['state_code'];
                            $shippingAddress[$i]['mobile_no'] =  $value['phone'];
                            $shippingAddress[$i]['id'] =  $value['id'];
                            $shippingAddress[$i]['country_name'] = $value['country'];
                            $shippingAddress[$i]['state_name'] =  $value['state'];
                            if($shippingAddress[$i]['id'] == $getCustomerData[0]['address_id'])
                                $shippingAddress[$i]['is_default'] =  1;
                            else 
                                $shippingAddress[$i]['is_default'] =  0;
                            $i++;
                        }
                    } else {
                        $shippingAddress[0]['first_name'] =  "";
                        $shippingAddress[0]['last_name'] =  "";
                        $shippingAddress[0]['company'] =  "";
                        $shippingAddress[0]['address_1'] =  "";
                        $shippingAddress[0]['address_2'] = "";
                        $shippingAddress[0]['city'] =   "";
                        $shippingAddress[0]['postcode'] =  "";
                        $shippingAddress[0]['country'] =  "";
                        $shippingAddress[0]['state'] =  "";
                        $shippingAddress[0]['mobile_no'] =  "";
                        $shippingAddress[0]['id'] =  0;
                        $shippingAddress[0]['country_name'] = "";
                        $shippingAddress[0]['state_name'] =  "";
                        $shippingAddress[0]['is_default'] =  1;
                    }
                    $customerOrderDetails = [
                        'id' => $getCustomerData[0]['customer_id'],
                        'first_name' => $getCustomerData[0]['firstname'],
                        'last_name' => $getCustomerData[0]['lastname'],
                        'email' => $getCustomerData[0]['email'],
                        'profile_pic' => $avtarURL,
                        'total_orders' => $totalOrderCount,
                        'total_order_amount' => $totalSpent,
                        'average_order_amount' => (
                            !empty($prepareOrder) && $prepareOrder > 0
                        ) ? $totalSpent / count($prepareOrder) : 0,
                        'last_order' => $lastOrder,
                        'last_order_id' => $lastOrderDetailID,
                        'date_created' => date(
                            'd/M/Y H:i:s', strtotime($getCustomerData[0]['date_added'])
                        ),
                        'billing_address' => $addressData['billing'],
                        'shipping_address' => $shippingAddress,
                        'orders' => (
                            !empty($prepareOrder) && count($prepareOrder) > 0
                        ) ? $prepareOrder : [],
                    ];
                }
                $storeResponse = $customerOrderDetails;
            } catch (\Exception $e) {
                // Blank the array
                // Store exception in logs
                create_log(
                    'store', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Customer'
                        ]
                    ]
                );
            }
        } else {
            // Get all requested Query params
            $fetch = (!empty($request->getQueryParam('fetch')))
            ? $request->getQueryParam('fetch') : '';
            $notification = (!empty($request->getQueryParam('notification')))
            ? $request->getQueryParam('notification') : '';
            $type = (!empty($request->getQueryParam('type'))) 
                    ? $request->getQueryParam('type') : '';
            $filters = [
                'search' => $request->getQueryParam('name'),
                'order' => (!empty($request->getQueryParam('order'))
                    && $request->getQueryParam('order') != "")
                    ? $request->getQueryParam('order') : 'asc',
                'orderby' => (!empty($request->getQueryParam('sortby'))
                    && $request->getQueryParam('sortby') != "")
                    ? $request->getQueryParam('sortby') : 'id',
                'per_page' => $request->getQueryParam('perpage'),
                'page' => $request->getQueryParam('page'),
                'pagination' => ($fetch != '' && $fetch == 'all')
                    ? 0 : 1,
                'customer_no_order' => $request->getQueryParam('customer_no_order'),
                'from_date' => $request->getQueryParam('from_date'),
                'to_date' => $request->getQueryParam('to_date'),
                'fetch' => $fetch,
                'notification' => $notification,
            ];
            $options = [];
            foreach ($filters as $filterKey => $filterValue) {
                $options[$filterKey] = $filterValue;
            }
            // Fetch all customers
            $storeResponse = $this->getStoreCustomers($options);
        }
        return $storeResponse;
    }

    /**
     * GET: Get Order Data Of Single Customer
     *
     * @param $customers Customer Details
     * @param $custFlag  Customer & Order Flag
     *
     * @author mukeshp@riaxe.com
     * @date   18th Aug 2020
     * @return json
     */
    private function _getOrderData($customers, $custFlag, $isLineItems=0)
    {
        $totalSpent = 0;
        $orderOptions = [];
        $options .= '&customer_id=' . $customers['customer_id'];
        $prepareOrder = [];
        $url = $this->getExtensionURL() . 'getOrders' . $options;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($output, true);
        $getOrders = $result['order_list'];
        if (isset($getOrders) && count($getOrders) > 0) {
            $i = 0;
            foreach ($getOrders as $orderValue) {
                if ($custFlag === 'customer') {
                    $url = $this->getExtensionURL() . 'getOrderDetails&order_id='. $orderValue['id'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $output = curl_exec($ch);
                    curl_close($ch);
                    $singleOrderDetailsRes = json_decode($output, true);
                    $singleOrderDetails = $singleOrderDetailsRes['order_details'];
                    if (!empty($singleOrderDetails['order_items'])) {
                        $quantity = 0;
                        foreach ($singleOrderDetails['order_items'] as $key => $items) {
                            $quantity += $items['quantity'];
                        }
                    }
                    $prepareOrder[] = [
                        'id' => $orderValue['id'],
                        'currency' => (isset($orderValue['currency'])
                            && $orderValue['currency'] != "")
                        ? $orderValue['currency'] : null,
                        'created_date' => (isset($orderValue['created_date'])
                            && $orderValue['created_date'] != "")
                        ? date(
                            'Y-m-d h:i:s', strtotime($orderValue['created_date'])
                        ) : null,
                        'total_amount' => (isset($orderValue['total_amount'])
                            && $orderValue['total_amount'] > 0)
                        ? $orderValue['total_amount'] : 0.00, // tax will be incl.
                        'quantity' => $quantity,
                    ];
                    // Added order line items
                    if ($isLineItems) {
                        $lineOrders = [];
                        if (!empty($singleOrderDetails['order_items'])) {
                            $lineItem = $singleOrderDetails['order_items'];
                            $lineOrders = $this->_getlineItemDetails($lineItem);
                        }
                        $prepareOrder[$i]['lineItems'] = $lineOrders;
                    }
                    $totalSpent = $totalSpent + $orderValue['total_amount'];
                } else {
                    $prepareOrder[] = [
                        'id' => $orderValue['id'],
                        'order_number' => $orderValue['id'],
                        'customer_first_name' => $customers['firstname'],
                        'customer_last_name' => $customers['lastname'],
                        'created_date' => (isset($orderValue['created_date'])
                            && $orderValue['date_created'] != "")
                        ? date(
                            'Y-m-d h:i:s', strtotime($orderValue['created_date'])
                        ) : null,
                        'total_amount' => (isset($orderValue['total_amount'])
                            && $orderValue['total_amount'] > 0)
                        ? $orderValue['total_amount'] : 0.00,
                        'currency' => (isset($orderValue['currency'])
                            && $orderValue['currency'] != "")
                        ? $orderValue['currency'] : null,
                        'status' => $orderValue['status'],

                    ];
                }
                $i++;
            }
        }
        $orderDetails = [
            'prepare_order' => $prepareOrder,
            'total_spent' => $totalSpent,
            'orders_count' => count($getOrders),
        ];
        return $orderDetails;
    }
    /**
     * POST: Get all countries
     *
     *
     * @author mukeshp@riaxe.com
     * @date   18 Aug 2020
     * @return Array
     */
    public function getAllCountries($request, $response) {
        $result  = $this->getStoreCountries();
        return $result;
    }
    /**
     * GET:Get all states by country code
     *
     * @param $country_code 
     *
     * @author mukeshp@riaxe.com
     * @date   18 Aug 2020
     * @return Array
     */
    public function getAllStates($request, $response , $args) {
        $result  = $this->getStatesByCountry($args['country_code']);
        return $result;
        
    }

    /**
     * GET: Total customer count
     *
     *
     * @author mukeshp@riaxe.com
     * @date   27 July 2020
     * @return Integer
     */
    public function userCount() {
        $totalUser  = $this->totalCustomer();
        return $totalUser;
    } 

    /**
     * POST: Create  a customer
     *
     * @param $user_email 
     * @param $user_password 
     * @param $first_name 
     * @param $last_name 
     * @param $company_name 
     * @param $company_url 
     * @param $billing_phone 
     * @param $billing_address_1 
     * @param $billing_address_2 
     * @param $billing_city 
     * @param $billing_state_code 
     * @param $billing_postcode 
     * @param $billing_country_code 
     * @param $shipping_address_1 
     * @param $shipping_address_2 
     * @param $shipping_city 
     * @param $shipping_state_code 
     * @param $shipping_postcode 
     * @param $shipping_country_code 
     *
     * @author mukeshp@riaxe.com
     * @date   03 Sept 2020
     * @return Array
     */
    public function createCustomer($request, $response, $args) {
        $allPostPutVars = $request->getParsedBody();
        $response  = $this->createStoreCustomer($allPostPutVars);
        return $response;
    }

    /**
     * POST: Delete a customer
     *
     * @param $user_id 
     *
     * @author mukeshp@riaxe.com
     * @date   03 Sept 2020
     * @return Array
     */
    public function deleteCustomer($request, $response, $args) {
        $user_id = json_clean_decode($args['id'], true);
        $result  = $this->deleteStoreCustomer($user_id);
        if (!empty($result)) {
            $response = array('status' => 1, 'message' => "Customer Deleted.");
        }else $response = array();
        return $response;
    }

    /**
     * POST: Add shipping address of a customer
     *
     * @param $id
     * @param $first_name
     * @param $last_name
     * @param $company
     * @param $address_1
     * @param $address_2
     * @param $city
     * @param $post_code
     * @param $country
     * @param $state
     *
     * @author mukeshp@riaxe.com
     * @date   04 Sept 2020
     * @return String
     */
    public function createShippingAddress($request, $response, $args) {
        $allPostPutVars = $request->getParsedBody();
        $result  = $this->addShippingAddress($allPostPutVars);
        if (!empty($result)) {
            $response = array('status' => 1, 'message' => "Address added");
        }else $response = array();
        return $response;
    }

    /**
     * POST: Update shipping address of a customer
     *
     * @param $id
     * @param $first_name
     * @param $last_name
     * @param $company
     * @param $address_1
     * @param $address_2
     * @param $city
     * @param $post_code
     * @param $country
     * @param $state
     *
     * @author mukeshp@riaxe.com
     * @date   04 Sept 2020
     * @return String
     */
    public function updateShippingAddress($request, $response, $args)
    {
        $allPostPutVars = $request->getParsedBody();
        $id = $args['id'];
        $result = $this->updateStoreShippingAddress($id, $allPostPutVars);
        if (!empty($result)) {
            $response = array('status' => 1, 'message' => "Address added");
        }else $response = array();
        return $response;

        return $result;
    }

    /**
     * GET:Customer details
     *
     ** @param $customerId
     * @param $storeId
     * @param $shipId
     * @author mukeshp@riaxe.com
     * @date   31 December 2020
     * @return Array
     */
    public function getQuoteCustomerDetails($customerId, $storeId, $shipId, $isAddress = false) {
        $customerDetails = [];
        $getCustomerData = $this->getStoreCustomer($customerId);
        if (!empty($getCustomerData)) {
            $customerDetails['customer']['id'] = $getCustomerData[0]['customer_id'];
            $customerDetails['customer']['email'] = $getCustomerData[0]['email'];
            $first_name = $getCustomerData[0]['firstname'];
            $last_name = $getCustomerData[0]['lastname'];
            $customerDetails['customer']['name'] = $first_name . ' ' . $last_name;
            $customerDetails['customer']['phone'] = $getCustomerData[0]['telephone'];

            if ($isAddress == true) {
                $addressData = $this->getCustomerShippingAddress($customerId);

                $customerDetails['customer']['billing_address']['first_name'] = $getCustomerData[0]['firstname'];
                $customerDetails['customer']['billing_address']['last_name'] = $getCustomerData[0]['lastname'];
                $customerDetails['customer']['billing_address']['address_1'] = $addressData['billing']['address_1'];
                $customerDetails['customer']['billing_address']['address_2'] = $addressData['billing']['address_2'];
                $customerDetails['customer']['billing_address']['city'] = $addressData['billing']['city'];
                $customerDetails['customer']['billing_address']['state'] = $addressData['billing']['state_code'];
                $customerDetails['customer']['billing_address']['postcode'] = $addressData['billing']['postcode'];
                $customerDetails['customer']['billing_address']['country'] = $addressData['billing']['country_code'];
                $customerDetails['customer']['billing_address']['email'] = $addressData['billing']['email'];
                $customerDetails['customer']['billing_address']['phone'] = $addressData['billing']['phone'];
                $customerDetails['customer']['billing_address']['company'] = $getCustomerData[0]['company'];
                $i = 0;
                if(!empty($addressData['shipping'])) {
                    foreach ($addressData['shipping'] as $key => $value) {
                        $customerDetails['customer']['shipping_address'][$i]['first_name'] =  $getCustomerData[0]['firstname'];
                        $customerDetails['customer']['shipping_address'][$i]['last_name'] =  $getCustomerData[0]['lastname'];
                        $customerDetails['customer']['shipping_address'][$i]['company'] =  $getCustomerData[0]['company'];
                        $customerDetails['customer']['shipping_address'][$i]['address_1'] =  $value['address_1'];
                        $customerDetails['customer']['shipping_address'][$i]['address_2'] = $value['address_2'];
                        $customerDetails['customer']['shipping_address'][$i]['city'] =   $value['city'];
                        $customerDetails['customer']['shipping_address'][$i]['postcode'] =  $value['postcode'];
                        $customerDetails['customer']['shipping_address'][$i]['country'] =  $value['country'];
                        $customerDetails['customer']['shipping_address'][$i]['state'] =  $value['state_code'];
                        $customerDetails['customer']['shipping_address'][$i]['mobile_no'] =  $value['mobile_no'];
                        $customerDetails['customer']['shipping_address'][$i]['id'] =  $value['id'];
                        $customerDetails['customer']['shipping_address'][$i]['country_name'] = $value['country'];
                        $customerDetails['customer']['shipping_address'][$i]['state_name'] =  $value['state'];
                        if($i == 0)
                            $customerDetails['customer']['shipping_address'][$i]['is_default'] =  1;
                        else 
                            $customerDetails['customer']['shipping_address'][$i]['is_default'] =  0;
                        $i++;
                    }
                }
            }
        }
        return $customerDetails;
    }

    /**
     * GET:User total count
     *
     * @param $storeId
     * @author mukeshp@riaxe.com
     * @date   13 Jan 2021
     * @return int
     */
    public function getTotalStoreCustomer($storeId) {
        $total_customer = 0;
        $total_customer = (int) $this->totalCustomer();
        return $total_customer;
    }

    /**
     * GET:Customer ids
     *
     * @param Slim default params
     * @author mukeshp@riaxe.com
     * @date   13 Jan 2021
     * @return Array
     */
    public function getStoreCustomerId($request, $response, $args) {
        global $wpdb;
        $customerIds = [];
        $storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
        $per_page = $request->getQueryParam('perpage');
        $page = $request->getQueryParam('page');
        $filter['page'] = $page;
        $filter['per_page'] = $per_page;
        $customerIds = $this->getStoreCustomerIds($filter);
        return $customerIds;
    }
}