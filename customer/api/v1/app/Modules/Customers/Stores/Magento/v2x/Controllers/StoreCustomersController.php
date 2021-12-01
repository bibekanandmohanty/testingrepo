<?php 
/**
 *
 * This Controller used to save, fetch or delete Magento Customers
 *
 * @category   Customers
 * @package    Magento API
 * @author     Tapas Ranjan<tapasranjanp@riaxe.com>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @1.0
 */
namespace CustomerStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

class StoreCustomersController extends StoreComponent
{
    /**
     * Get list of customer or a Single customer from the Magento API
     *
     * @author     tapasranjanp@riaxe.com
     * @date       18 Dec 2019
     * @parameter  Slim default params
     * @response   Array of list/one customer(s)
     */
    public function getCustomers($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        try {
            if(isset($args['id']) && $args['id'] > 0) {
                // Fetching Single Customer details           
                $filters = array(
                    'store' => $getStoreDetails['store_id'],
                    'customerId' => $args['id']
                );
                $result = $this->apiCall('Customer', 'getStoreCustomerDetails', $filters);
                $result = $result->result;
                $storeResponse = json_clean_decode($result, true);
            }else{
                // Fetching all customer by filters
                $searchstring = (!empty($request->getQueryParam('name'))
                        && $request->getQueryParam('name') != "")
                        ? $request->getQueryParam('name') : '';
                $page = (!empty($request->getQueryParam('page'))
                        && $request->getQueryParam('page') != "")
                        ? $request->getQueryParam('page') : 0;
                $limit = (!empty($request->getQueryParam('perpage'))
                        && $request->getQueryParam('perpage') != "")
                        ? $request->getQueryParam('perpage') : 20;
                $order = (!empty($request->getQueryParam('order'))
                        && $request->getQueryParam('order') != "")
                        ? $request->getQueryParam('order') : 'asc';
                $orderby = (!empty($request->getQueryParam('orderby'))
                        && $request->getQueryParam('orderby') != "")
                        ? $request->getQueryParam('orderby') : 'id';
                $type = (!empty($request->getQueryParam('type'))
                        && $request->getQueryParam('type') != "")
                        ? $request->getQueryParam('type') : '';
                $fetch = (!empty($request->getQueryParam('fetch'))) ? $request->getQueryParam('fetch') : '';
                $notification = (!empty($request->getQueryParam('notification'))) ? $request->getQueryParam('notification') : '';
                if ($type != '' && $type == 'quote') {
                    $limit = 100;
                }
                $filters = array(
                    'store' => $getStoreDetails['store_id'],
                    'searchstring' => $searchstring,
                    'page' => $page,
                    'limit' => $limit,
                    'order' => $order,
                    'orderby' => $orderby,
                    'customerNoOrder' => $request->getQueryParam('customer_no_order'),
                    'fromDate' => $request->getQueryParam('from_date'),
                    'toDate' => $request->getQueryParam('to_date'),
                    'fetch' => $fetch,
                );
                $result = $this->apiCall('Customer', 'getStoreCustomers', $filters);
                $result = $result->result;
                $storeResponse = json_clean_decode($result, true);
            }
        } catch (\Exception $e) {
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
        return $storeResponse;
    }

    /**
     * GET: Total customer count
     *
     *
     * @author debashrib@riaxe.com
     * @date   04 Aug 2020
     * @return Integer
     */
    public function userCount() {
        $filters = array();
        $result = $this->apiCall('Customer', 'getTotalCustomerCount', $filters);
        $result = $result->result;
        $resultData = json_clean_decode($result, true);
        $totalUser = $resultData['user_count'];
        return $totalUser;
    }

    /**
     * POST: Get all countries
     *
     *
     * @author debashrib@riaxe.com
     * @date   04 Aug 2020
     * @return Array
     */
    public function getAllCountries($request, $response) {
        $filters = array();
        $result = $this->apiCall('Customer', 'getAllCountries', $filters);
        $result = $result->result;
        $resultData = json_clean_decode($result, true);
        $countries = $resultData['countries'];
        return $countries;
    }

    /**
     * GET:Get all states by country code
     *
     * @param $country_code 
     *
     * @author debashrib@riaxe.com
     * @date   05 Aug 2020
     * @return Array
     */
    public function getAllStates($request, $response , $args) 
    {
        $filters = array(
            'countryCode' => $args['country_code']
        );
        $result = $this->apiCall('Customer', 'getAllStatesByCode', $filters);
        $result = $result->result;
        $resultData = json_clean_decode($result, true);
        $states = $resultData['states'];
        return $states;
    }

    /**
     * Create Store Customer
     *
     * @author     debashrib@riaxe.com
     * @date       06 Aug 2020
     * @parameter  Slim default params
     * @response   Array
     */
    public function createCustomer($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        try {
            if (!empty($allPostPutVars)) {
                $filters = array(
                    'store' => $getStoreDetails['store_id'],
                    'data' => json_encode($allPostPutVars),
                );
                $result = $this->apiCall('Customer', 'createCustomer', $filters);
                $result = $result->result;
                $resultData = json_clean_decode($result, true);
                if (!empty($resultData)) {
                    $storeResponse = [
                        'status' => $resultData['status'],
                        'message' => $resultData['message']
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
                        'module' => 'Customer'
                    ]
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Create Customer Shipping Address
     *
     * @author     debashrib@riaxe.com
     * @date       06 Aug 2020
     * @parameter  Slim default params
     * @response   Array
     */
    public function createShippingAddress($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        try {
            if (!empty($allPostPutVars)) {
                $filters = array(
                    'store' => $getStoreDetails['store_id'],
                    'data' => json_encode($allPostPutVars),
                );
                $result = $this->apiCall('Customer', 'createShippingAddress', $filters);
                $result = $result->result;
                $resultData = json_clean_decode($result, true);
                if (!empty($resultData)) {
                    $storeResponse = [
                        'status' => $resultData['status'],
                        'message' => $resultData['message']
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
                        'module' => 'Customer'
                    ]
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Update Customer Shipping Address
     *
     * @author     debashrib@riaxe.com
     * @date       08 Aug 2020
     * @parameter  Slim default params
     * @response   Array
     */
    public function updateShippingAddress($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        try {
            if (!empty($allPostPutVars)) {
                $allPostPutVars['shipping_id'] = $args['id'];
                $filters = array(
                    'store' => $getStoreDetails['store_id'],
                    'data' => json_encode($allPostPutVars)
                );
                $result = $this->apiCall('Customer', 'updateShippingAddress', $filters);
                $result = $result->result;
                $resultData = json_clean_decode($result, true);
                if (!empty($resultData)) {
                    $storeResponse = [
                        'status' => $resultData['status'],
                        'message' => $resultData['message']
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
                        'module' => 'Customer'
                    ]
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Delete Customer
     *
     * @author     debashrib@riaxe.com
     * @date       09 Aug 2020
     * @parameter  Slim default params
     * @response   Array
     */
    public function deleteCustomer($request, $response, $args)
    {
        $storeResponse = [];
        $getStoreDetails = get_store_details($request);
        if(isset($args['id']) && $args['id'] != '') {
            $customerIdArr = json_decode($args['id'], true);
            $customerIds = implode(',', $customerIdArr);
            $filters = array(
                'store' => $getStoreDetails['store_id'],
                'customerIds' =>  $customerIds
            );
            $result = $this->apiCall('Customer', 'deleteCustomer', $filters);
            $result = $result->result;
            $storeResponse = json_clean_decode($result, true);
        }
        return $storeResponse;
    }

    /**
     * GET:Minimal Customer details
     *
     * @author tapasranjanp@riaxe.com
     * @date   25 December 2020
     * @parameter  Slim default params
     * @response   Array
     */
    public function getQuoteCustomerDetails($customerId, $storeId, $shipId, $isAddress = false)
    {
        $storeResponse = [];
        $filters = array(
            'store' => $storeId,
            'customerId' =>  $customerId,
            'shipId' =>  $shipId,
            'isAddress' =>  $isAddress
        );
        $result = $this->apiCall('Customer', 'getStoreCustomerDetailsWithShipId', $filters);
        $result = $result->result;
        $result = json_clean_decode($result, true);
        if (!empty($result) && isset($result['id'])) {
            $storeResponse['customer'] = $result;
        }
        return $storeResponse;
    }

    /**
     * GET:User total count
     *
     ** @param $storeId
     * @author tapasranjanp@riaxe.com
     * @date   16 December 2020
     * @return int
     */
    public function getTotalStoreCustomer($storeId)
    {
        $storeResponse = [];
        try {
            // Fetching all Customer count           
            $filters = array(
                'store' => (!empty($storeId)) ? $storeId : 1
            );
            $result = $this->apiCall('Customer', 'getStoreCustomerCount', $filters);
            $result = $result->result;
            $storeResponse = json_clean_decode($result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Customer count'
                    ]
                ]
            );
        }
        return $storeResponse;
    }

    /**
     * Get All Customer's Id
     *
     * @author     tapasranjanp@riaxe.com
     * @date       13 Jan 2021
     * @parameter  Slim default params
     * @response   Array
     */
    public function getStoreCustomerId($request, $response, $args)
    {
        $storeResponse = [];
        try {
            $storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
            $page = $request->getQueryParam('page');
            $perPage = $request->getQueryParam('perpage');
            $filters = array(
                'store' => $storeId,
                'page' => $page,
                'perPage' =>  $perPage
            );
            $result = $this->apiCall('Customer', 'getStoreCustomersId', $filters);
            $result = $result->result;
            $storeResponse = json_clean_decode($result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get Customers Ids'
                    ]
                ]
            );
        }
        return $storeResponse;
    }
}
