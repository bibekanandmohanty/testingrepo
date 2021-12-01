<?php
/**
 * This Controller used to save, fetch or delete Standalone Customers
 *
 * PHP version 5.6
 *
 * @category  Customers_API
 * @package   Customers
 * @author    Radhanatha <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace CustomerStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Customers Controller Class
 *
 * @category Customers_API
 * @package  Customers
 * @author   Radhanatha <radhanatham@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreCustomersController extends StoreComponent
{
    /**
     * Get list of customer or a Single customer from the Magento API
     *
     * @param $request  Slim default params
     * @param $response Slim default params
     * @param $args     Slim default params
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array of list/one customer(s)
     **/
    public function getCustomers($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        try {
            if (isset($args['id']) && $args['id'] > 0) {
                // Fetching Single Customer details
                $filters = array(
                    'store' => $getStoreDetails['store_id'],
                    'customerId' => $args['id'],
                    'isOrder' => (!empty($request->getQueryParam('orders'))
                        && $request->getQueryParam('orders') != "")
                    ? $request->getQueryParam('orders') : 0,
                );
                $storeResponse = $this->getStoreCustomerDetails($filters);
            } else {
                // Fetching all customers by filters
                $page = (!empty($request->getQueryParam('page'))
                    && $request->getQueryParam('page') != "")
                ? $request->getQueryParam('page') : 1;
                $limit = (!empty($request->getQueryParam('perpage'))
                    && $request->getQueryParam('perpage') != "")
                ? $request->getQueryParam('perpage') : 40;
                $order = (!empty($request->getQueryParam('order'))
                    && $request->getQueryParam('order') != "")
                ? $request->getQueryParam('order') : 'DESC';
                $orderby = (!empty($request->getQueryParam('orderby'))
                    && $request->getQueryParam('orderby') != "")
                ? $request->getQueryParam('orderby') : 'id';
                $name = (!empty($request->getQueryParam('name'))
                    && $request->getQueryParam('name') != "")
                ? $request->getQueryParam('name') : '';
                $from = (!empty($request->getQueryParam('from_date'))
                    && $request->getQueryParam('from_date') != "")
                ? $request->getQueryParam('from_date') : '';
                $to = (!empty($request->getQueryParam('to_date'))
                    && $request->getQueryParam('to_date') != "")
                ? $request->getQueryParam('to_date') : '';
                $customerNoOrder = (!empty($request->getQueryParam('customer_no_order'))
                    && $request->getQueryParam('customer_no_order') != "")
                ? $request->getQueryParam('customer_no_order') : '';
                $type = (!empty($request->getQueryParam('type'))
                    && $request->getQueryParam('type') != "")
                ? $request->getQueryParam('type') : '';
                $fetch = (!empty($request->getQueryParam('fetch'))
                    && $request->getQueryParam('fetch') != "")
                ? $request->getQueryParam('fetch') : '';
                $filters = array(
                    'store' => $getStoreDetails['store_id'],
                    'searchstring' => $searchstring,
                    'page' => $page,
                    'limit' => $limit,
                    'order' => $order,
                    'orderby' => $orderby,
                    'name' => $name,
                    'customerId' => 0,
                    'from_date' => $from,
                    'to_date' => $to,
                    'customer_no_order' => $customerNoOrder,
                    'type' => $type,
                    'fetch' => $fetch,
                );
                $customerJson = $this->getStoreCustomerDetails($filters);
                $storeResponse = [
                    'total_user' => $customerJson['total_records'],
                    'customer_list' => $customerJson['data'],
                ];
            }
        } catch (\Exception $e) {
            // Blank the array
            $storeResponse = [];
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Customer',
                    ],
                ]
            );
        }

        return $storeResponse;

    }


    /**
     * Get list of customer or a Single customer from the PrestaShop API
     *
     * @param $filters Customer filter
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array of list/one customer(s)
     **/
    private function getStoreCustomerDetails($filters)
    {
        $storeId = $filters['store'];
        $storeResponse = [];

        if (!empty($filters) && $filters['customerId'] > 0) {
            $customerId = $filters['customerId'];
            $parameter = array(
                            'resource'      => 'customers',
                            'display'       => 'full',
                            'filter[id]'    => '%[' . $customerId . ']%', 'limit' => '1',
                            'id_shop'       => $storeId,
                            'output_format' => 'JSON',
                         );
            // Call
            $jsonData = $this->webService->get($parameter);
            //return json format
            $customerArr = json_decode($jsonData, true);
            if (!empty($customerArr)) {
                $getCustomers = $customerArr['customers'];
                $address = $this->webService->getAddressByCutsomerId(
                    $getCustomers[0]['id'],
                    $getCustomers[0]['email']
                );
                if (!empty($address)) {
                    $shipping = $address['shipping_address'];
                    $billing = $address['billing_address'];
                }

                $lastOrderId = $this->webService->getLastOrderIdByCustomerId(
                    $customerId, $storeId
                );
                $lastOrderDate = $this->webService->getLastOrderDateByCustomerId(
                    $customerId, $storeId
                );

                $totalOrder = $this->webService->getTotalOrderCountByCustomerId(
                    $customerId, $storeId
                );
                $isOrder = $filters['isOrder'];
                $orderDetails = $this->webService->getOrderDetailsByCustomerId(
                    $customerId, $isOrder, $storeId
                );
                $customer = [
                    'id' => $getCustomers[0]['id'],
                    'first_name' => $getCustomers[0]['firstname'],
                    'last_name' => $getCustomers[0]['lastname'],
                    'email' => $getCustomers[0]['email'],
                    'profile_pic' => '',
                    'total_order_amount' => $orderDetails['total_order_amount']
                    ? $orderDetails['total_order_amount'] : 0,
                    'average_order_amount' => $orderDetails['average_order_amount']
                    ? $orderDetails['average_order_amount'] : 0,
                    'last_order' => $lastOrderDate,
                    'date_created' => date(
                        'd/M/Y H:i:s', strtotime($getCustomers[0]['date_add'])
                    ),
                    'total_orders' => $totalOrder,
                    'last_order_id' => $lastOrderId > 0 ? $lastOrderId : null,
                    'billing_address' => $billing ? $billing : [],
                    'shipping_address' => $shipping ? $shipping : [],
                    'orders' => $orderDetails['order_item']
                    ? $orderDetails['order_item'] : [],
                ];
                $storeResponse = $customer;
            }
        } else {
            $customers = array();
            //call to prestashop webservice for get all customers
            $totalCustomers = $this->webService->getCutomers($filters);
            if (!empty($totalCustomers)) {
                $page = $filters['page'];
                $perpage = $filters['limit'];
                $getTotalcustomersCount = count($totalCustomers);
                if ($page == 1) {
                    $allowCustomer = ($page * $perpage);
                    $totalCustomers = array_slice($totalCustomers, 0, $allowCustomer);
                } elseif ($page > 1) {
                    $allowCustomer = ($page * $perpage) - 1;
                    $customerStart = ($page - 1) * $perpage;
                    $totalCustomers = array_slice(
                        $totalCustomers,
                        $customerStart, $perpage
                    );
                }
                $i = 0;
                $lastOrderId = 0;
                $totalOrder = 0;
                $beforeDate = date('Y-m-d', strtotime('-1 years'));
                $formDate = $filters['from_date'] ? $filters['from_date'] : $beforeDate;
                $toDate = $filters['to_date'] ? $filters['to_date'] : date('Y-m-d');
                $fromDate = date('Y-m-d', strtotime($formDate));
                $toDate = date('Y-m-d', strtotime($toDate));
                foreach ($totalCustomers as $k => $v) {
                    $date = $v['date_add'];
                    $date = date('Y-m-d H:i:s', strtotime($date));
                    $orderDate = date('Y-m-d', strtotime($date));
                    $lastOrderId = $this->webService->getLastOrderIdByCustomerId($v['id'], $storeId);
                    if (($orderDate >= $fromDate) && ($orderDate <= $toDate)) {
                        $customers[$i]['id'] = $v['id'];
                        $customers[$i]['first_name'] = $v['firstname'];
                        $customers[$i]['last_name'] = $v['lastname'];
                        $customers[$i]['email'] = $v['email'];
                        $customers[$i]['date_created'] = date(
                            'd/M/Y H:i:s', strtotime($v['date_add'])
                        );
                        $totalOrder = $this->webService->getTotalOrderCountByCustomerId($v['id'], $storeId);
                        $customers[$i]['last_order_id'] = $lastOrderId > 0 ? $lastOrderId : null;
                        $customers[$i]['total_orders'] = $totalOrder;
                        $i++;
                    }
                }
                $storeResponse = [
                    'total_records' => $getTotalcustomersCount,
                    'data' => $customers,
                ];
            }
        }
        return $storeResponse;
    }
    /**
     * GET: Get all countries
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return Array
     */
    public function getAllCountries($request, $response)
    {
        $result = $this->webService->getStoreCountries();
        return $result;
    }

    /**
     * GET: Get all countries
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return Array
     */
    public function getAllStates($request, $response, $args)
    {
        $countryCode = $args['country_code'];
        $result = $this->webService->getStoreStates($countryCode);
        return $result;

    }

    /**
     * GET: Total customer count
     *
     * @author radhanatham@riaxe.com
     * @date   10 April 2020
     * @return Integer
     */
    public function userCount($request, $response, $args)
    {
        $getTotalcustomersCount = 0;
        $order = (!empty($request->getQueryParam('order'))
            && $request->getQueryParam('order') != "")
        ? $request->getQueryParam('order') : 'DESC';
        $orderby = (!empty($request->getQueryParam('sortby'))
            && $request->getQueryParam('sortby') != "")
        ? $request->getQueryParam('sortby') : 'id';
        $name = (!empty($request->getQueryParam('name'))
            && $request->getQueryParam('name') != "")
        ? $request->getQueryParam('name') : '';
        $customerNoOrder = (!empty($request->getQueryParam('customer_no_order'))
            && $request->getQueryParam('customer_no_order') != "")
        ? $request->getQueryParam('customer_no_order') : '';
        $filters = array(
            'order' => $order,
            'orderby' => $orderby,
            'name' => $name,
            'customer_no_order' => $customerNoOrder,
        );
        $totalCustomers = $this->webService->getCutomers($filters);
        if (!empty($totalCustomers)) {
            $getTotalcustomersCount = count($totalCustomers);
        }
        return $getTotalcustomersCount;
    }

    /**
     * POST: Create single multiple shipping address of a customer
     *
     * @param $user_id
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
     * @author radhanatham@riaxe.com
     * @date   31 march 2020
     * @return String
     */
    public function createShippingAddress($request, $response, $args)
    {
        $allPostPutVars = $request->getParsedBody();
        $result = $this->webService->addCustomerNewShippingaddress($allPostPutVars);
        return $result;
    }

    /**
     * POST: Delete single multiple shipping address of a customer
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
     * @author radhanatham@riaxe.com
     * @date   31 march 2020
     * @return String
     */
    public function updateShippingAddress($request, $response, $args)
    {
        $allPostPutVars = $request->getParsedBody();
        $id = $args['id'];
        $result = $this->webService->updateShippingAddress($allPostPutVars, $id);

        return $result;
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
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return Array
     */
    public function createCustomer($request, $response, $args)
    {
        $allPostPutVars = $request->getParsedBody();
        $result = $this->webService->createCustomer($allPostPutVars);
        return $result;

    }
    /**
     * POST: Update a customer
     *
     * @param $user_id
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
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return Array
     */
    public function updateCustomer($request, $response, $args)
    {
        $allPostPutVars = $request->getParsedBody();
        $user_id = $args['id'];
        $result = $this->webService->updateCustomer($allPostPutVars, $user_id);
        return $result;

    }

    /**
     * POST: Delete a customer
     *
     * @param $request  Slim default params
     * @param $response Slim default params
     * @param $args     Slim default params
     *
     * @author radhanatham@riaxe.com
     * @date   10 Aug 2020
     * @return Array
     */
    public function deleteCustomer($request, $response, $args)
    {
        $status = 0;
        $userIdArr = json_clean_decode($args['id'], true);
        if (!empty($userIdArr)) {
            foreach ($userIdArr as $key => $userId) {
                $status = $this->webService->deleteCustomer($userId);
            }
        }
        if ($status) {
            $jsonResponse = [
                'status' => 1,
                'message' => 'Deleted Successfully',
            ];

        } else {
            $jsonResponse = [
                'status' => 0,
                'message' => 'Deleted Failed',
            ];
        }
        return $jsonResponse;
    }

    /**
     * GET:User total count
     *
     * * @param $storeId
     *
     * @author radhanatham@riaxe.com
     * @date   31 December 2020
     * @return int
     */
    public function getTotalStoreCustomer($storeId)
    {
        $total_user = 0;
        $resource = 'customers';
        $customersCount = $this->webService->countResource($resource);
        $total_user = sizeof($customersCount['customers']);
        return $total_user;
    }

    /**
     * GET:Customer details
     *
     * @param $customerId
     * @param $storeId
     * @param $shipId
     * @param $isAddress
     * 
     * @author soumyas@riaxe.com
     * @date   16 December 2020
     * @return Array
     */
    public function getQuoteCustomerDetails($customerId, $storeId, $shipId, $isAddress = false)
    {
        $customerDetails = [];
        $customerDetails = $this->webService->getCutsomerAddress($customerId, $storeId, $isAddress);
        return $customerDetails;
    }

    /**
     * GET:User ids
     *
     * * @param $storeId
     *
     * @author radhanatham@riaxe.com
     * @date   07 Jan 2021
     * @return int
     */
    public function getStoreCustomerId($request, $response, $args)
    {
        $customerIds = [];
        $storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
        $perpage = $request->getQueryParam('perpage');
        $page = $request->getQueryParam('page');
        $order = (!empty($request->getQueryParam('order'))
            && $request->getQueryParam('order') != "")
        ? $request->getQueryParam('order') : 'DESC';
        $orderby = (!empty($request->getQueryParam('sortby'))
            && $request->getQueryParam('sortby') != "")
        ? $request->getQueryParam('sortby') : 'id';
        $name = (!empty($request->getQueryParam('name'))
            && $request->getQueryParam('name') != "")
        ? $request->getQueryParam('name') : '';
        $customerNoOrder = (!empty($request->getQueryParam('customer_no_order'))
            && $request->getQueryParam('customer_no_order') != "")
        ? $request->getQueryParam('customer_no_order') : '';
        $filters = array(
            'order' => $order,
            'orderby' => $orderby,
            'orderby' => $orderby,
            'name' => $name,
            'customer_no_order' => $customerNoOrder,
        );
        $totalCustomers = $this->webService->getCutomers($filters);
        if (!empty($totalCustomers)) {
            if ($page == 1) {
                $allowCustomer = ($page * $perpage);
                $totalCustomers = array_slice($totalCustomers, 0, $allowCustomer);
            } elseif ($page > 1) {
                $allowCustomer = ($page * $perpage) - 1;
                $customerStart = ($page - 1) * $perpage;
                $totalCustomers = array_slice(
                    $totalCustomers,
                    $customerStart, $perpage
                );
            }
            $i = 0;
            foreach ($totalCustomers as $k => $v) {
                $customerIds[$i]['id'] = $v['id'];
                $i++;
            }
        }

        return $customerIds;
    }
}
