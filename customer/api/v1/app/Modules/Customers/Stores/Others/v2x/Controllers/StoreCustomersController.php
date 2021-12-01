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
                $storeResponse = '{"id":3,"first_name":"Robert","last_name":"M","email":"roberttest@gmail.com","profile_pic":"","total_order_amount":137.8,"average_order_amount":45.93333333333334,"last_order":"38 minutes ago","date_created":"11\/Dec\/2020 06:43:05","total_orders":3,"last_order_id":8,"billing_address":{"address_1":"Patia, Bhubaneswar","address_2":"Patia-2, Bhubaneswar","city":"Bhubaneswar","state":"Odisha","postcode":"751 212","phone":"07064409344","email":"roberttest@gmail.com","country":"India"},"shipping_address":[{"address_1":"Patia, Bhubaneswar","address_2":"Patia-2, Bhubaneswar","city":"Bhubaneswar","state":"Odisha","postcode":"751 212","phone":"07064409344","email":"roberttest@gmail.com","country":"India"}],"orders":[{"id":8,"created_date":"2020-12-11 06:50:50","currency":"GBP","total_amount":"20.900000","quantity":1},{"id":7,"created_date":"2020-12-11 06:50:01","currency":"GBP","total_amount":"14.900000","quantity":1},{"id":6,"created_date":"2020-12-11 06:43:41","currency":"GBP","total_amount":"102.000000","quantity":1}]}';
                $storeResponse = json_decode($storeResponse, true);
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
                );
                if ($customerNoOrder =='true') {
                    $customerJson = '{"total_records":1,"data":[{"id":"1","first_name":"Anonymous","last_name":"Anonymous","email":"anonymous@psgdpr.com","date_created":"11\/Dec\/2020 06:14:13","last_order_id":null,"total_orders":0}]}';
                } else {
                    $customerJson = '{"total_records":3,"data":[{"id":"3","first_name":"Robert","last_name":"M","email":"roberttest@gmail.com","date_created":"11\/Dec\/2020 06:43:05","last_order_id":8,"total_orders":3},{"id":"2","first_name":"John","last_name":"DOE","email":"pub@prestashop.com","date_created":"11\/Dec\/2020 06:15:49","last_order_id":5,"total_orders":5},{"id":"1","first_name":"Anonymous","last_name":"Anonymous","email":"anonymous@psgdpr.com","date_created":"11\/Dec\/2020 06:14:13","last_order_id":null,"total_orders":0}]}';
                }
                $customerJson = json_decode($customerJson, true);
                $storeResponse = $customerJson['data'];
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
        $storeResponse = [];
        return $storeResponse;
    }
    /**
     * GET: Get all countries
     *
     *
     * @author radhanatham@riaxe.com
     * @date  10 Aug 2020
     * @return Array
     */
    public function getAllCountries($request, $response)
    {
        $result = '[{"countries_code":"IN","countries_name":"India"},{"countries_code":"GB","countries_name":"United Kingdom"}]';
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * GET: Get all countries
     *
     *
     * @author radhanatham@riaxe.com
     * @date  10 Aug 2020
     * @return Array
     */
    public function getAllStates($request, $response, $args)
    {
        $countryCode = $args['country_code'];
        $result = '[{"state_code":"OD","state_name":"Odisha"},{"state_code":"BH","state_name":"Bihar"},{"state_code":"AS","state_name":"Asam"},{"state_code":"CH","state_name":"Chhatisgarh"}]';
        $result = json_decode($result, true);
        return $result;

    }

    /**
     * GET: Total customer count
     *
     *
     * @author radhanatham@riaxe.com
     * @date   10 April 2020
     * @return Integer
     */
    public function userCount($request, $response, $args)
    {
        $resource = 'customers';
        $getTotalcustomersCount = 3;
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
        $address_id = 1;
        if ($address_id) {
            $status = 1;
            $message = 'Created Successfully';
        } else {
            $status = 0;
            $message = 'Created Failed';
        }
        $jsonResponse = [
            'status' => $status,
            'message' => $message,
        ];
        return $jsonResponse;
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
        if ($id) {
            $status = 1;
            $message = 'Updated Successfully';
        } else {
            $status = 0;
            $message = 'Updated Failed';
        }

        return $jsonResponse = [
            'status' => $status,
            'message' => $message,
        ];
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
     * @date  10 Aug 2020
     * @return Array
     */
    public function createCustomer($request, $response, $args)
    {
        $allPostPutVars = $request->getParsedBody();
        if (!empty($allPostPutVars)) {
            $message = "Customer created successfully";
            $status = 1;
        } else {
            $status = 0;
            $message = "Invalid customer details";
        }
        return $jsonResponse = [
            'status' => $status,
            'message' => $message,
        ];
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
     * @date  10 Aug 2020
     * @return Array
     */
    public function updateCustomer($request, $response, $args)
    {
        $allPostPutVars = $request->getParsedBody();
        $user_id = $args['id'];
        $result = true;
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
     * @date  10 Aug 2020
     * @return Array
     */
    public function deleteCustomer($request, $response, $args)
    {
        $status = 0;
        $userIdArr = json_clean_decode($args['id'], true);
        if (!empty($userIdArr)) {
            $status = true;
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
}
