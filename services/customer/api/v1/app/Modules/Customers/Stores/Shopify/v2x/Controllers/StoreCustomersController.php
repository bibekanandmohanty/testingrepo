<?php

/**
 *
 * This Controller used to fetch  Shopify Customer
 *
 * @category   Products
 * @package    Shopify API
 * @author     Original Author <debashrib@riaxe.com>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @1.0
 */

namespace CustomerStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

class StoreCustomersController extends StoreComponent {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get list of Customer or a Single customer
     *
     * @author     debashrib@riaxe.com
     * @date       17 dec 2019
     * @parameter  Slim default params
     * @response   Array of list/one customer(s)
     */
    public function getCustomers($request, $response, $args) {
        $storeResponse = [];
        $customers = [];
        $customerOrderDetails = [];

        // Get all requested Query params 
        $filters = [
            'searchString' => $request->getQueryParam('name'),
            'page' => $request->getQueryParam('page'),
            'from_date' => $request->getQueryParam('from_date'),
            'to_date' => $request->getQueryParam('to_date'),
            'customer_no_order' => $request->getQueryParam('customer_no_order'),
            'limit' => $request->getQueryParam('perpage'),
            'order' => (!empty($request->getQueryParam('order')) && $request->getQueryParam('order') != "") ? $request->getQueryParam('order') : 'asc',
            'orderby' => (!empty($request->getQueryParam('orderby')) && $request->getQueryParam('orderby') != "") ? $request->getQueryParam('orderby') : 'id',
        ];

        // For fetching Single Product
        if (isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
            $getOrders = 1;
            $customers = $this->getCustomerDetails($args['id'], $getOrders);
            $storeResponse = $customers;
        } else {
            $customers = $this->getAllCustomers($filters);
            if (!empty($customers)) {
                $storeResponse['customer_list'] = $customers;
            }
        }
        return $storeResponse;
    }

    /**
     * GET: Total customer count
     *
     *
     * @author debashisd@riaxe.com
     * @date   9 july 2020
     * @return Integer
     */
    public function userCount() {
        $totalUser  = $this->storeCustomerCount();
        return $totalUser;
    }

    /**
     * POST: Get all countries
     *
     *
     * @author debashisd@riaxe.com
     * @date   9 August 2020
     * @return Array
     */
    public function getAllCountries($request, $response) {
        $result  = $this->getShopCountries();
        return $result;
    }

    /**
     * GET:Get all states by country code
     *
     * @param $country_code 
     *
     * @author debashisd@riaxe.com
     * @date   9 August 2020
     * @return Array
     */
    public function getAllStates($request, $response , $args) {
        $countryCode = $args['country_code'];
        $result  = $this->getProvibce($countryCode);
        return $result;
        
    }

    /**
     * POST: Delete a customer
     *
     * @param $user_id 
     *
     * @author debashisd@riaxe.com
     * @date   9 August 2020
     * @return Array
     */
    public function deleteCustomer($request, $response, $args) {
        $customerID = json_clean_decode($args['id'], true);
        $response  = $this->deleteShopCustomer($customerID);
        if (!empty($response)) {
            return $response;
        }
    }

    /**
     * POST: Create  a customer
     *
     * @author debashisd@riaxe.com
     * @date   9 August 2020
     * @return Array
     */
    public function createCustomer($request, $response, $args) {
        $allPostPutVars = $request->getParsedBody();
        $result  = $this->newShopCustomer($allPostPutVars);
        if (!empty($result) && !empty($result['id'])) {
            $response = array('status' => 1, 'message' => "customer added");
        }else $response = array('status' => 0, 'message' => "Customer Email or number already exists");
        return $response;
    }

    /**
     * POST: Add shipping address of a customer
     *
     * @author debashisd@riaxe.com
     * @date   9 August 2020
     * @return String
     */
    public function createShippingAddress($request, $response, $args) {
        $allPostPutVars = $request->getParsedBody();
        $result  = $this->changeDefaultAddress($allPostPutVars);
        if (!empty($result)) {
            $response = array('status' => 1, 'message' => "Address added");
        }else $response = array();
        return $response;
    }

    /**
     * GET:User total count
     *
     ** @param $storeId
     * @author soumyas@riaxe.com
     * @date   16 December 2020
     * @return int
     */
    public function getTotalStoreCustomer($storeId) {
        //switch_to_blog($storeId);
        $total_user = 0;
        $totalCustomerCount = $this->storeCustomerCount();
        return $totalCustomerCount;
    }

    public function getQuoteCustomerDetails($customerId, $storeId, $shipId, $isAddress = false) {
        $customerData = $this->customerShortData($customerId, $isAddress);
        return $customerData;
    }

    public function updateShippingAddress($request, $response, $args) {
        $allPostPutVars = $request->getParsedBody();
        $id = $args['id'];
        $result = $this->updateCustomerAddressInShop($allPostPutVars, $id);
        return $result;
    }

}
