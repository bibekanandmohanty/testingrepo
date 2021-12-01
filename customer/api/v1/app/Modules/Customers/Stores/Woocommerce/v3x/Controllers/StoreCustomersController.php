<?php
/**
 * Manage Customer
 *
 * PHP version 5.6
 *
 * @category  Customers
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
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
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreCustomersController extends StoreOrdersController {
	/**
	 * Instantiate Constructor
	 */
	public function __construct() {
		parent::__construct();
		include_once $this->storePath['abspath'] . "wp-blog-header.php";
	}

	/**
	 * GET: Get Customer
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author satyabratap@riaxe.com
	 * @date   7 jan 2019
	 * @return json response wheather data is saved or any error occured
	 */
	public function getCustomers($request, $response, $args) {
		$totalSpent = 0;
		$endPoint = 'customers';
		$storeResponse = [];
		$customerOrderDetails = [];
		require_once $this->storePath['abspath'] . "wp-blog-header.php";
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		if (is_multisite()) {
			switch_to_blog($storeId);
		}
		if (isset($args['id']) && $args['id'] > 0) {
			$endPoint .= '/' . $args['id'];
			$isLineItems = to_int((!empty($request->getQueryParam('orders'))
				&& $request->getQueryParam('orders') != "")
				? $request->getQueryParam('orders') : 0);
			// Fetch Single customers
			try {
				$getCustomerData = get_userdata($args['id']);
				$getCustomerData = (array) $getCustomerData->data;
				$customer_id = $getCustomerData['ID'];
				$first_name = get_user_meta($customer_id, "first_name", true) ? get_user_meta($customer_id, "first_name", true) : '';
				$last_name = get_user_meta($customer_id, "last_name", true) ? get_user_meta($customer_id, "last_name", true) : '';
				$getCustomerData['store_id'] = $storeId;
				$orderDetails = $this->_getOrderData(array('id' => $getCustomerData['ID']), 'customer', $isLineItems);
				$totalSpent = $orderDetails['total_spent'];
				$prepareOrder = $orderDetails['prepare_order'];
				$totalOrderCount = $orderDetails['orders_count'];
				$lastOrderDetail = (!empty($prepareOrder) && $prepareOrder > 0) ? $prepareOrder : null;
				$lastOrderDetailID = '';
				$lastOrder = wc_get_customer_last_order($customer_id);
				$lastOrderTime = "";
				if (!empty($lastOrder)) {
					$lastOrderDetailID = $lastOrder->get_id();
					$lastOrderTime = $lastOrderDetail[0]['created_date'];
				}
				$i = 0;
				$country_name = '';
				$state_name = '';

				/*GET BILLING DETAILS*/
				$getCustomerData['billing']['first_name'] = get_user_meta($customer_id, "billing_first_name", true);
				$getCustomerData['billing']['last_name'] = get_user_meta($customer_id, "billing_last_name", true);
				$getCustomerData['billing']['address_1'] = get_user_meta($customer_id, "billing_address_1", true);
				$getCustomerData['billing']['address_2'] = get_user_meta($customer_id, "billing_address_2", true);
				$getCustomerData['billing']['city'] = get_user_meta($customer_id, "billing_city", true);
				$getCustomerData['billing']['state'] = get_user_meta($customer_id, "billing_state", true);
				$getCustomerData['billing']['postcode'] = get_user_meta($customer_id, "billing_postcode", true);
				$getCustomerData['billing']['country'] = get_user_meta($customer_id, "billing_country", true);
				$getCustomerData['billing']['email'] = get_user_meta($customer_id, "billing_email", true);
				$getCustomerData['billing']['phone'] = get_user_meta($customer_id, "billing_phone", true);

				/**GET SHIPPING DETAILS*/
				$shippingAddress[$i]['address_1'] = get_user_meta($customer_id, "shipping_address_1", true);
				$shippingAddress[$i]['address_2'] = get_user_meta($customer_id, "shipping_address_2", true);
				$shippingAddress[$i]['city'] = get_user_meta($customer_id, "shipping_city", true);
				$shippingAddress[$i]['state'] = get_user_meta($customer_id, "shipping_state", true);
				$shippingAddress[$i]['postcode'] = get_user_meta($customer_id, "shipping_postcode", true);
				$shippingAddress[$i]['country'] = get_user_meta($customer_id, "shipping_country", true);
				$shippingAddress[$i]['mobile_no'] = get_user_meta($customer_id, "shipping_phone", true);
				$shippingAddress[$i]["id"] = "0";
				$shippingAddress[$i]['is_default'] = 1;

				if (get_user_meta($customer_id, "shipping_state", true) && get_user_meta($customer_id, "shipping_country", true)) {
					$countryCode = get_user_meta($customer_id, "shipping_country", true);
					$stateCode = get_user_meta($customer_id, "shipping_state", true);
					$country_name = $this->getCountryNameByCode($countryCode);
					$state_name = $this->getStateNameByStateAndCountryCode($countryCode, $stateCode);
				}
				$shippingAddress[$i]["country_name"] = $country_name;
				$shippingAddress[$i]["state_name"] = $state_name;

				$addressData = $this->getShippingAddressByCuctomerId($args['id']);
				if (!empty($addressData)) {
					foreach ($addressData as $key => $value) {
						$i++;
						$shippingAddress[$i]['first_name'] = $value['first_name'];
						$shippingAddress[$i]['last_name'] = $value['last_name'];
						$shippingAddress[$i]['company'] = '';
						$shippingAddress[$i]['address_1'] = $value['address_line_one'];
						$shippingAddress[$i]['address_2'] = $value['address_line_two'];
						$shippingAddress[$i]['city'] = $value['city'];
						$shippingAddress[$i]['postcode'] = $value['postcode'];
						$shippingAddress[$i]['country'] = $value['country'];
						$shippingAddress[$i]['state'] = $value['state'];
						$shippingAddress[$i]['mobile_no'] = $value['mobile_no'];
						$shippingAddress[$i]['id'] = $value['id'];
						$shippingAddress[$i]['country_name'] = $this->getCountryNameByCode($value['country']);
						$shippingAddress[$i]['state_name'] = $this->getStateNameByStateAndCountryCode($value['country'], $value['state']);
						$shippingAddress[$i]['is_default'] = $value['is_default'];

					}
				}
				$customerOrderDetails = [
					'id' => $customer_id,
					'first_name' => $first_name,
					'last_name' => $last_name,
					'email' => $getCustomerData['user_email'],
					'profile_pic' => get_avatar_url($args['id']),
					'total_orders' => $totalOrderCount,
					'total_order_amount' => $totalSpent,
					'average_order_amount' => (
						!empty($prepareOrder) && $prepareOrder > 0
					) ? $totalSpent / count($prepareOrder) : 0,
					'last_order' => $lastOrderTime,
					'last_order_id' => $lastOrderDetailID,
					'date_created' => date(
						'd/M/Y H:i:s', strtotime($getCustomerData['user_registered'])
					),
					'billing_address' => $getCustomerData['billing'],
					'shipping_address' => $shippingAddress,
					'orders' => (
						!empty($prepareOrder) && count($prepareOrder) > 0
					) ? $prepareOrder : [],
				];
				$storeResponse = $customerOrderDetails;
			} catch (\Exception $e) {
				// Blank the array
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
		} else {
			// Get all requested Query params
			$fetch = (!empty($request->getQueryParam('fetch')))
			? $request->getQueryParam('fetch') : '';
			$type = (!empty($request->getQueryParam('type')))
			? $request->getQueryParam('type') : '';
			$notification = (!empty($request->getQueryParam('notification')))
			? $request->getQueryParam('notification') : '';

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
				'store_id' => $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1,
				'quote' => $type,
				'fetch' => $fetch,
				'notification' => $notification,

			];
			$options = [];
			foreach ($filters as $filterKey => $filterValue) {
				if (isset($filterValue) && $filterValue != "") {
					$options[$filterKey] = $filterValue;
				}
			}
			// Fetch all customers
			$storeResponse = $this->plugin->get($endPoint, $options);
		}

		return $storeResponse;
	}

	/**
	 * GET: Get Order Data Of Single Customer
	 *
	 * @param $customers Customer Details
	 * @param $custFlag  Customer & Order Flag
	 *
	 * @author satyabratap@riaxe.com
	 * @date   7 jan 2019
	 * @return json
	 */
	private function _getOrderData($customers, $custFlag, $isLineItems = 0) {
		$totalSpent = 0;
		$orderOptions = [];
		$lineOrders = [];
		$orderOptions['customer'] = $customers['id'];
		$prepareOrder = [];
		$getOrders = get_posts(array(
			'numberposts' => -1,
			'meta_key' => '_customer_user',
			'meta_value' => $customers['id'],
			'post_type' => wc_get_order_types(),
			'post_status' => array_keys(wc_get_order_statuses()),
		));
		if (isset($getOrders) && count($getOrders) > 0) {
			$i = 0;
			foreach ($getOrders as $orderValue) {
				$orderId = $orderValue->ID;
				$order = wc_get_order($orderId);
				$currency = $order->get_currency() ? $order->get_currency() : null;
				$total = $order->get_total() ? $order->get_total() : 0.00;
				$createdDate = $order->get_date_created()->format('j/M/Y H:i:s');
				$orderStatus = $order->get_status();
				$first_name = $order->get_billing_first_name() ? $order->get_billing_first_name() : '';
				$last_name = $order->get_billing_last_name() ? $order->get_billing_last_name() : '';
				if ($custFlag === 'customer') {
					if (!empty($order->get_items())) {
						$quantity = 0;
						foreach ($order->get_items() as $item_id => $item_values) {
							$quantity += $item_values['quantity'];

						}
					}
					$prepareOrder[] = [
						'id' => $orderId,
						'currency' => $currency,
						'created_date' => $createdDate,
						'total_amount' => $total, // tax will be incl.
						'quantity' => $quantity,
					];
					// Added order line items
					if ($isLineItems) {
						if (!empty($order->get_items())) {
							/*
								$lineItem = $order->get_items();
								$lineOrders = $this->_getlineItemDetails($lineItem);
							*/
							$line = 0;
							$lineOrders = [];
							foreach ($order->get_items() as $item_id => $item) {
								$product = $item->get_product();
								$product_sku = null;
								if (is_object($product)) {
									$product_sku = $product->get_sku();
								}
								$product_id = $item->get_product_id();
								$variation_id = $item->get_variation_id();
								$variationId = isset($variation_id) && $variation_id > 0 ? $variation_id : $product_id;
								$lineOrders[$line]['id'] = $item->get_id();
								$lineOrders[$line]['product_id'] = $product_id;
								$lineOrders[$line]['variant_id'] = $variationId;
								$lineOrders[$line]['name'] = $item->get_name();
								$lineOrders[$line]['sku'] = $product_sku;
								$lineOrders[$line]['quantity'] = $item->get_quantity();
								$lineOrders[$line]['price'] = $item->get_subtotal();
								$lineOrders[$line]['total'] = $order->get_item_meta($item_id, '_line_total', true);
								$meta_data = $item->get_meta_data();
								$formatted_meta = [];
								$productImageArray = [];
								$j = 0;
								$k = 0;
								foreach ($meta_data as $meta) {
									$name = str_replace("pa_", "", $meta->key);
									if ($name == 'custom_design_id') {
										$customDesignId = $meta->value;
										$formatted_meta[$j] = $customDesignId;
										$j++;
									}
								}
								if ($product->image_id != 0) {
									$imageSrc = wp_get_attachment_image_src($product->image_id, 'full');
									$imageSrcThumb = wp_get_attachment_image_src($product->image_id, 'thumbnail');
									$productImageArray[$k]['src'] = $imageSrc[0];
									$productImageArray[$k]['thumbnail'] = $imageSrcThumb[0];
									$k++;
								}
								if ($product_id != $variant_id) {
									$attachments = get_post_meta($variant_id, 'variation_image_gallery', true);
									$attachmentsExp = array_filter(explode(',', $attachments));
									foreach ($attachmentsExp as $id) {
										$imageSrc = wp_get_attachment_image_src($id, 'full');
										$imageSrcThumb = wp_get_attachment_image_src($id, 'thumbnail');
										$productImageArray[$k]['src'] = $imageSrc[0];
										$productImageArray[$k]['thumbnail'] = $imageSrcThumb[0];
										$k++;
									}
								} else {
									foreach ($product->gallery_image_ids as $id) {
										$imageSrc = wp_get_attachment_image_src($id, 'full');
										$imageSrcThumb = wp_get_attachment_image_src($id, 'thumbnail');
										$productImageArray[$k]['src'] = $imageSrc[0];
										$productImageArray[$k]['thumbnail'] = $imageSrcThumb[0];
										$k++;
									}
								}
								$lineOrders[$line]['custom_design_id'] = $formatted_meta[0];
								$lineOrders[$line]['images'] = $productImageArray;
								$line++;
							}
						}
						$prepareOrder[$i]['lineItems'] = $lineOrders;
					}
					$totalSpent = $totalSpent + $total;
				} else {

					$prepareOrder[] = [
						'id' => $orderId,
						'order_number' => $orderId,
						'customer_first_name' => $first_name,
						'customer_last_name' => $clast_name,
						'created_date' => $createdDate,
						'total_amount' => $total,
						'currency' => $currency,
						'status' => $orderStatus,

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
	 * GET: Get Multiple Shipping Address  Of Single Customer
	 *
	 * @param $userId
	 *
	 * @author soumyas@riaxe.com
	 * @date   28 march 2020
	 * @return Array
	 */
	public function getShippingAddressByCuctomerId($userId) {
		$result = $this->plugin->get('customer/multiple_shipping_address', ['userId' => $userId]);
		return $result;
	}
	/**
	 * GET: Get country name by code
	 *
	 * @param $country_code
	 *
	 * @author soumyas@riaxe.com
	 * @date   28 march 2020
	 * @return String
	 */
	public function getCountryNameByCode($country_code) {
		$result = $this->plugin->get('customer/get_country_name', ['country_code' => $country_code]);
		return $result;
	}
	/**
	 * GET: Get state name by country & state code
	 *
	 * @param $country_code
	 * @param $state_code
	 *
	 * @author soumyas@riaxe.com
	 * @date   28 march 2020
	 * @return String
	 */
	public function getStateNameByStateAndCountryCode($country_code, $state_code) {
		$result = $this->plugin->get('customer/get_state_name', ['country_code' => $country_code, 'state_code' => $state_code]);
		return $result;
	}
	/**
	 * GET: Delete single  Multiple Shipping Address  Of a Customer
	 *
	 * @param $id
	 *
	 * @author soumyas@riaxe.com
	 * @date   28 march 2020
	 * @return String
	 */
	public function deleteShippingAddress($request, $response, $args) {
		$id = $args['id'];
		$result = $this->plugin->get('customer/delete_shipping_address', ['id' => $id]);
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
	 * @author soumyas@riaxe.com
	 * @date   31 march 2020
	 * @return String
	 */
	public function updateShippingAddress($request, $response, $args) {
		$allPostPutVars = $request->getParsedBody();
		$id = $args['id'];
		$result = $this->plugin->post('customer/update_shipping_address', ['request' => $allPostPutVars, 'id' => $id]);

		return $result;
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
	 * @author soumyas@riaxe.com
	 * @date   31 march 2020
	 * @return String
	 */
	public function createShippingAddress($request, $response, $args) {
		$allPostPutVars = $request->getParsedBody();
		$result = $this->plugin->post('customer/create_shipping_address', ['request' => $allPostPutVars]);
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
	 * @author soumyas@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function createCustomer($request, $response, $args) {
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$allPostPutVars = $request->getParsedBody();
		$allPostPutVars['store_id'] = $storeId;
		$result = $this->plugin->post('customer/create_customer', ['request' => $allPostPutVars]);
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
	 * @author soumyas@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function updateCustomer($request, $response, $args) {
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$allPostPutVars = $request->getParsedBody();
		$allPostPutVars['store_id'] = $storeId;
		$user_id = $args['id'];
		$result = $this->plugin->post('customer/update_customer', ['request' => $allPostPutVars, 'user_id' => $user_id]);
		return $result;

	}
	/**
	 * POST: Delete a customer
	 *
	 * @param $user_id
	 *
	 * @author soumyas@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function deleteCustomer($request, $response, $args) {
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id'):1;
		$user_id = json_clean_decode($args['id'], true);
		$result = $this->plugin->post('customer/delete_customer', ['user_id' => $user_id, 'store_id' => $storeId]);
		return $result;
	}

	/**
	 * POST: Get all countries
	 *
	 *
	 * @author soumyas@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function getAllCountries($request, $response) {
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$result = $this->plugin->get('get_countries', ['store_id' => $storeId]);
		return $result;
	}
	/**
	 * GET:Get all states by country code
	 *
	 * @param $country_code
	 *
	 * @author soumyas@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function getAllStates($request, $response, $args) {
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$country_code = $args['country_code'];
		$result = $this->plugin->get('get_states', ['country_code' => $country_code, 'store_id' => $storeId]);
		return $result;

	}

	/**
	 * GET: Total customer count
	 *
	 *
	 * @author soumyas@riaxe.com
	 * @date   10 April 2020
	 * @return Integer
	 */
	public function userCount($request, $response, $args) {
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$filters = [
			'customer_no_order' => $request->getQueryParam('customer_no_order'),
			'from_date' => $request->getQueryParam('from_date'),
			'to_date' => $request->getQueryParam('to_date'),
			'search' => $request->getQueryParam('name'),
			'store_id' => $storeId,
			'quote' => $request->getQueryParam('type') ? $request->getQueryParam('type') : '',
			'notification' => $request->getQueryParam('notification') ? $request->getQueryParam('notification') : '',
		];
		$filterArray = [];
		foreach ($filters as $filterKey => $filterValue) {
			if (isset($filterValue) && $filterValue != "") {
				$filterArray[$filterKey] = $filterValue;
			}
		}
		$totalUser = $this->plugin->get('customer_count', $filterArray);
		return $totalUser;
	}
	/**
	 * GET: Customer Details
	 *
	 ** @param $customerId
	 * @author soumyas@riaxe.com
	 * @date   21 September 2020
	 * @return Array
	 */
	public function getCustomerDetails($request, $response, $args) {
		$customer_id = $args['id'];
		$result = $this->plugin->get('customer_details', ['customer_id' => $customer_id]);
		return $result;
	}
	public function getStoreCountryState($request, $response, $args) {
		$countryState = ['countryCode' => $args['code'], 'stateCode' => $args['state_code']];
		$result = $this->plugin->get('country_state_name', ['countryState' => $countryState]);
		return $result;
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
		$total_user = 0;
		$count_args = array(
			'role' => 'Customer',
		);
		$wp_user_query = new \WP_User_Query($count_args);
		$total_user = (int) $wp_user_query->get_total();
		return $total_user;
	}
	/**
	 * GET:User ids
	 *
	 ** @param $storeId
	 * @author soumyas@riaxe.com
	 * @date   16 December 2020
	 * @return int
	 */
	public function getStoreCustomerId($request, $response, $args) {
		global $wpdb;
		$customerIds = [];
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$per_page = $request->getQueryParam('perpage');
		$page = $request->getQueryParam('page');
		if (is_multisite()) {
			switch_to_blog($storeId);
		}
		$args = array(
			'role' => 'Customer',
			'offset' => $page ? ($page - 1) * $per_page : 0,
			'number' => $per_page,

		);
		$users = get_users($args);
		if (!empty($users)) {
			$i = 0;
			foreach ($users as $user) {
				$customerIds[$i]['id'] = $user->ID;
				$i++;
			}
		}
		return $customerIds;
	}
	/**
	 * GET:Customer details
	 *
	 ** @param $customerId
	 * @param $storeId
	 * @param $shipId
	 * @author soumyas@riaxe.com
	 * @date   16 December 2020
	 * @return Array
	 */
	public function getQuoteCustomerDetails($customerId, $storeId, $shipId, $isAddress = false) {
		$customerDetails = [];
		global $wpdb;
		$storeResponse = get_userdata($customerId);
		if (!empty($storeResponse)) {
			$storeResponse = (array) $storeResponse->data;
			$customerDetails['customer']['id'] = $storeResponse['ID'];
			$customerDetails['customer']['email'] = $storeResponse['user_email'];
			$first_name = get_user_meta($customerId, "first_name", true) ? get_user_meta($customerId, "first_name", true) : '';
			$last_name = get_user_meta($customerId, "last_name", true) ? get_user_meta($customerId, "last_name", true) : '';
			$customerDetails['customer']['name'] = $first_name . ' ' . $last_name;
			$customerDetails['customer']['phone'] = get_user_meta($customerId, "billing_phone", true);

			if ($isAddress == true) {
				$customerDetails['customer']['billing_address']['first_name'] = !empty(get_user_meta($customerId, "billing_first_name", true)) ? get_user_meta($customerId, "billing_first_name", true) : $first_name;
				$customerDetails['customer']['billing_address']['last_name'] = !empty(get_user_meta($customerId, "billing_last_name", true)) ? get_user_meta($customerId, "billing_last_name", true) : $last_name;
				$customerDetails['customer']['billing_address']['address_1'] = get_user_meta($customerId, "billing_address_1", true);
				$customerDetails['customer']['billing_address']['address_2'] = get_user_meta($customerId, "billing_address_2", true);
				$customerDetails['customer']['billing_address']['city'] = get_user_meta($customerId, "billing_city", true);
				$customerDetails['customer']['billing_address']['state'] = get_user_meta($customerId, "billing_state", true);
				$customerDetails['customer']['billing_address']['postcode'] = get_user_meta($customerId, "billing_postcode", true);
				$customerDetails['customer']['billing_address']['country'] = get_user_meta($customerId, "billing_country", true);
				$customerDetails['customer']['billing_address']['email'] = get_user_meta($customerId, "billing_email", true);
				$customerDetails['customer']['billing_address']['phone'] = get_user_meta($customerId, "billing_phone", true);
				$customerDetails['customer']['billing_address']['company'] = '';

				if ($shipId == 0) {
					$customerDetails['customer']['shipping_address'][0]['id'] = 0;
					$customerDetails['customer']['shipping_address'][0]['first_name'] = get_user_meta($customerId, "shipping_first_name", true);
					$customerDetails['customer']['shipping_address'][0]['last_name'] = get_user_meta($customerId, "shipping_last_name", true);
					$customerDetails['customer']['shipping_address'][0]['company'] = get_user_meta($customerId, "company", true) ? get_user_meta($customerId, "company", true) : '';
					$customerDetails['customer']['shipping_address'][0]['address_1'] = get_user_meta($customerId, "shipping_address_1", true);
					$customerDetails['customer']['shipping_address'][0]['address_2'] = get_user_meta($customerId, "shipping_address_2", true) ? get_user_meta($customerId, "shipping_address_2", true) : '';
					$customerDetails['customer']['shipping_address'][0]['city'] = get_user_meta($customerId, "shipping_city", true);
					$customerDetails['customer']['shipping_address'][0]['state'] = get_user_meta($customerId, "shipping_state", true);
					$customerDetails['customer']['shipping_address'][0]['postcode'] = get_user_meta($customerId, "shipping_postcode", true);
					$customerDetails['customer']['shipping_address'][0]['country'] = get_user_meta($customerId, "shipping_country", true);
					$customerDetails['customer']['shipping_address'][0]['is_default'] = 1;
					$customerDetails['customer']['shipping_address'][0]['phone'] = get_user_meta($customerId, "shipping_phone", true);

					$countryName = WC()->countries->countries[get_user_meta($customerId, "shipping_country", true)] ? WC()->countries->countries[get_user_meta($customerId, "shipping_country", true)] : get_user_meta($customerId, "shipping_country", true);
					$stateName = WC()->countries->states[get_user_meta($customerId, "shipping_country", true)][get_user_meta($customerId, "shipping_state", true)] ? WC()->countries->states[get_user_meta($customerId, "shipping_country", true)][get_user_meta($customerId, "shipping_state", true)] : get_user_meta($customerId, "shipping_state", true);

					$customerDetails['customer']['shipping_address'][0]['country_name'] = $countryName;
					$customerDetails['customer']['shipping_address'][0]['state_name'] = $stateName;
				} else {
					$shippingAddress = $wpdb->prefix . "multipleshippingaddress";
					$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($shippingAddress));
					if ($wpdb->get_var($query) == $shippingAddress) {
						$sql = "SELECT *  FROM " . $shippingAddress . " WHERE user_id=" . $customerId . " AND id=" . $shipId;
						$result = $wpdb->get_results($sql);
						if (!empty($result)) {
							$customerDetails['customer']['shipping_address'][0]['id'] = $result[0]->id;
							$customerDetails['customer']['shipping_address'][0]['first_name'] = $result[0]->first_name;
							$customerDetails['customer']['shipping_address'][0]['last_name'] = $result[0]->first_name;
							$customerDetails['customer']['shipping_address'][0]['company'] = $result[0]->company;
							$customerDetails['customer']['shipping_address'][0]['address_1'] = $result[0]->address_line_one;
							$customerDetails['customer']['shipping_address'][0]['address_2'] = $result[0]->address_line_two;
							$customerDetails['customer']['shipping_address'][0]['city'] = $result[0]->city;
							$customerDetails['customer']['shipping_address'][0]['state'] = $result[0]->state;
							$customerDetails['customer']['shipping_address'][0]['postcode'] = $result[0]->postcode;
							$customerDetails['customer']['shipping_address'][0]['country'] = $result[0]->country;
							$customerDetails['customer']['shipping_address'][0]['is_default'] = $result[0]->is_default;
							$customerDetails['customer']['shipping_address'][0]['phone'] = $result[0]->mobile_no;
							$countryName = WC()->countries->countries[$result->country] ? WC()->countries->countries[$result[0]->country] : $result[0]->country;
							$stateName = WC()->countries->states[$result->country][$result->state] ? WC()->countries->states[$result[0]->country][$result[0]->state] : $result[0]->state;
							$customerDetails['customer']['shipping_address'][0]['country_name'] = $countryName;
							$customerDetails['customer']['shipping_address'][0]['state_name'] = $stateName;

						}

					}
				}
			}
		}
		return $customerDetails;
	}
}
