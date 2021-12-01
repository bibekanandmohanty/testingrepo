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

use CommonStoreSpace\Controllers\StoreController;

/**
 * Store Order Controller
 *
 * @category Store_Order
 * @package  Order
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreOrdersController extends StoreController {
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
	public function __construct() {
		parent::__construct();
	}
	/**
	 * Get list of product or a Single product from the WooCommerce API
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Order List in Json format
	 */
	public function getOrders($request, $response, $args) {
		$orders = [];
		$orderOptions = [];
		$orderCount = 0;
		$storeResponse = [];
		$orderId = to_int($args['id']);
		if (!empty($request->getQueryParam('store_id'))) {
			$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		} else {
			$storeId = $args['store_id'] ? $args['store_id'] : 1;
		}
		try {
			if (!empty($orderId)) {
				// Fetch Single Order Details
				$orderOpion = array('store_id' => $storeId, 'order_id' => $orderId);
				$singleOrderDetails = $this->plugin->get('order_details', array('order_option' => $orderOpion));
				if (!empty($singleOrderDetails)) {
					$storeResponse = [
						'total_records' => 1,
						'order_details' => $singleOrderDetails,
					];
				}

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
					'order_status' => $request->getQueryParam('order_status'),
					'store_id' => $storeId,
				];
				$options = [];
				foreach ($filters as $filterKey => $filterValue) {
					if (isset($filterValue) && $filterValue != "") {
						$options += [$filterKey => $filterValue];
					}
				}
				// Fetch All Orders
				// Calling to Custom API for getting Order List
				$orders = object_to_array($this->plugin->get('orders', $options));
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
	 * Get list of Order Logs
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Responce object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Order List in Json format
	 */
	public function getStoreLogs($request, $response, $args) {
		include_once $this->storePath['abspath'] . "wp-blog-header.php";
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$apiEndpoint = 'orders/' . $args['id'];
		$storeResponse = [];
		if (is_multisite()) {
			switch_to_blog($storeId);
		}
		try {
			$storeResp = wc_get_order($args['id']);
			if (!empty($storeResp->get_id()) && $storeResp->get_id() > 0) {
				$storeResponse[] = [
					'order_id' => $storeResp->get_id(),
					'message' => $storeResp->get_status(),
					'log_type' => 'order_status',
					'status' => 'new',
					'created_at' => date(
						$this->dateFormat, strtotime($storeResp->get_date_created())
					),
					'updated_at' => date(
						$this->dateFormat, strtotime($storeResp->get_date_modified())
					),
				];

				/**
				 * Woocommerce has no payment history logic. So we need to break one
				 * record to multiple histories.  If customer paid for the order then,
				 * paid details will be pushed to the histiry
				 */
				if (!empty($storeResp->get_date_paid())) {
					$storeResponse[] = [
						'order_id' => $storeResp->get_id(),
						'message' => !empty($storeResp->get_date_paid()->date("j/M/Y g:i:s")) ? 'Paid' : 'Not-paid',
						'date_paid' => !empty($storeResp->get_date_paid()->date("j/M/Y g:i:s"))
						? $storeResp->get_date_paid()->date("j/M/Y g:i:s") : null,
						'payment_method' => !empty($storeResp->get_payment_method())
						? $storeResp->get_payment_method() : null,
						'payment_method_title' => !empty($storeResp->get_payment_method_title())
						? $storeResp->get_payment_method_title() : null,
						'log_type' => 'payment_status',
						'status' => 'new',
						'created_at' => date(
							$this->dateFormat, strtotime($storeResp->get_date_created())
						),
						'updated_at' => date(
							$this->dateFormat, strtotime($storeResp->get_date_modified())
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
	public function _getVariableImageSizes($imagePath, $resolution) {
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
	public function _getlineItemDetails($lineItems) {
		$lineOrders = [];
		foreach ($lineItems as $orderDetailsKey => $orderDetails) {
			$productImages = [];
			try {
				$getProductImages = $this->plugin->get(
					'product/images',
					[
						'product_id' => $orderDetails['product_id'],
						'variant_id' => isset($orderDetails['variation_id'])
						&& $orderDetails['variation_id'] > 0
						? $orderDetails['variation_id'] : $orderDetails['product_id'],
					]
				);
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
			foreach ($orderDetails['meta_data'] as $metaItems) {
				$name = str_replace("pa_", "", $metaItems['key']);
				if ($name == 'custom_design_id') {
					$customDesignId = $metaItems['value'];
				}
			}
			if (!empty($getProductImages)) {
				foreach ($getProductImages['images'] as $prodImg) {
					$productImages[] = [
						'src' => $prodImg['src'],
						'thumbnail' => $prodImg['thumbnail'],
					];
				}
			}
			$lineOrders[$orderDetailsKey] = [
				'id' => $orderDetails['id'],
				'product_id' => $orderDetails['product_id'],
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
	 * @author malay@riaxe.com
	 * @date   4th Mar 2020
	 * @return json
	 */
	public function orderItemDetails($request, $response, $args) {
		$storeResponse = [];
		try {
			if (!empty($request->getQueryParam('store_id'))) {
				$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
			} else {
				$storeId = $args['store_id'];
			}
			$orderOpion = array('store_id' => $storeId, 'order_id' => $args['id']);
			$singleOrderDetails = $this->plugin->get('order_item_details', array('order_item_option' => $orderOpion));
			if (!empty($singleOrderDetails)) {
				$storeResponse['order_details'] = $singleOrderDetails;
			}

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
	 * @author soumyas@riaxe.com
	 * @date   03 June 2020
	 * @return Array
	 */
	public function getDefaultOrderStatuses($storeId) {
		$option = ['store_id' => $storeId];
		$orderStatus = $this->plugin->get('store_order_statuses');
		return $orderStatus;
	}
	/**
	 * POST : Order Status changed
	 *
	 * @param orderId
	 * @param orderData
	 *
	 * @author soumyas@riaxe.com
	 * @date   03 June 2020
	 * @return String
	 */
	public function updateStoreOrderStatus($orderId, $orderData) {
		include_once $this->storePath['abspath'] . "wp-blog-header.php";
		global $woocommerce;
		$order_status = '';
		global $woocommerce;
		$order_status = '';
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$order = wc_get_order($orderId);
		if (!empty($order)) {
			$statusResponse['id'] = $order->update_status($orderData['statusKey']);
		}
		if ($statusResponse['id'] > 0) {
			$order_status = "success";
		}
		return $order_status;
	}

	/**
	 * GET: Get Customer Details
	 *
	 * @param $customerId
	 *
	 * @author soumyas@riaxe.com
	 * @date   16 May 2020
	 * @return Array
	 */
	public function getCustomerDetailsById($customer_id, $storeId = 1) {
		include_once $this->storePath['abspath'] . "wp-blog-header.php";
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$customerDetails = [];
		/*GET BILLING DETAILS*/
		$customerDetails['billing']['first_name'] = get_user_meta($customer_id, "first_name", true);
		$customerDetails['billing']['last_name'] = get_user_meta($customer_id, "last_name", true);
		$customerDetails['billing']['address_1'] = get_user_meta($customer_id, "billing_address_1", true);
		$customerDetails['billing']['address_2'] = get_user_meta($customer_id, "billing_address_2", true);
		$customerDetails['billing']['city'] = get_user_meta($customer_id, "billing_city", true);
		$customerDetails['billing']['state'] = get_user_meta($customer_id, "billing_state", true);
		$customerDetails['billing']['postcode'] = get_user_meta($customer_id, "billing_postcode", true);
		$customerDetails['billing']['country'] = get_user_meta($customer_id, "billing_country", true);
		$customerDetails['billing']['email'] = get_user_meta($customer_id, "billing_email", true);
		$customerDetails['billing']['phone'] = get_user_meta($customer_id, "billing_phone", true);

		/*GET SHIPPING DETAILS*/
		$customerDetails['shipping']['first_name'] = !empty(get_user_meta($customer_id, "shipping_first_name", true)) ? get_user_meta($customer_id, "shipping_first_name", true) : get_user_meta($customer_id, "first_name", true);
		$customerDetails['shipping']['last_name'] = !empty(get_user_meta($customer_id, "shipping_last_name", true)) ? get_user_meta($customer_id, "shipping_last_name", true) : get_user_meta($customer_id, "last_name", true);
		$customerDetails['shipping']['address_1'] = get_user_meta($customer_id, "shipping_address_1", true);
		$customerDetails['shipping']['address_2'] = get_user_meta($customer_id, "shipping_address_2", true);
		$customerDetails['shipping']['city'] = get_user_meta($customer_id, "shipping_city", true);
		$customerDetails['shipping']['postcode'] = get_user_meta($customer_id, "shipping_postcode", true);
		$customerDetails['shipping']['country'] = get_user_meta($customer_id, "shipping_country", true);
		$customerDetails['shipping']['state'] = get_user_meta($customer_id, "shipping_state", true);
		return $customerDetails;
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

	public function storeOrder($queryArray) {
		include_once $this->storePath['abspath'] . "wp-blog-header.php";
		$storeId = $queryArray['store_id'] ? $queryArray['store_id'] : 1;
		$customerId = $queryArray['customer_id'];
		$quoteId = $queryArray['quote_id'] ? $queryArray['quote_id'] : 0;
		$isArtwork = $queryArray['is_artwork'] ? $queryArray['is_artwork'] : 0;
		$isRush = $queryArray['is_rush'] ? $queryArray['is_rush'] : 0;
		$rushType = $queryArray['rush_type'] ? $queryArray['rush_type'] : '';
		$rushAmount = $queryArray['rush_amount'] ? $queryArray['rush_amount'] : 0;
		$discountType = $queryArray['discount_type'] ? $queryArray['discount_type'] : '';
		$discountAmount = $queryArray['discount_amount'] ? $queryArray['discount_amount'] : 0;
		$shippingType = $queryArray['shipping_type'] ? $queryArray['shipping_type'] : '';
		$shippingAmount = $queryArray['shipping_amount'] ? $queryArray['shipping_amount'] : 0;
		$designTotal = $queryArray['design_total'] ? $queryArray['design_total'] : 0;
		$quoteTotal = $queryArray['quote_total'] ? $queryArray['quote_total'] : 0;
		$shippingId = $queryArray['shipping_id'] ? $queryArray['shipping_id'] : 0;
		$note = $queryArray['note'] ? $queryArray['note'] : '';
		if (is_multisite()) {
			switch_to_blog($storeId);
		}
		global $woocommerce;
		$order = wc_create_order();
		$customerDetails = $this->getCustomerDetailsById($customerId, $storeId);

		$order->set_address($customerDetails['billing'], 'billing');

		if ($shippingId == 0) {
			/*get  shipping from store*/
			$order->set_address($customerDetails['shipping'], 'shipping');
		} else {
			/* get multipleshippingaddress */
			$data = array(
				'customerId' => $customerId,
				'shippingId' => $shippingId,

			);
			$shipping = array();
			$shippingAddress = $this->plugin->get('order_shipping_address', ['shipping_data' => $data]);
			if (!empty($shippingAddress)) {
				$shipping['shipping']['first_name'] = $shippingAddress[0]['first_name'];
				$shipping['shipping']['last_name'] = $shippingAddress[0]['last_name'];
				$shipping['shipping']['address_1'] = $shippingAddress[0]['address_line_one'];
				$shipping['shipping']['address_2'] = $shippingAddress[0]['address_line_two'];
				$shipping['shipping']['city'] = $shippingAddress[0]['city'];
				$shipping['shipping']['postcode'] = $shippingAddress[0]['postcode'];
				$shipping['shipping']['country'] = $shippingAddress[0]['country'];
				$shipping['shipping']['state'] = $shippingAddress[0]['state'];

				$order->set_address($shipping['shipping'], 'shipping');
			}

		}
		// Set other details
		$order->set_customer_id($customerId);
		$order->set_currency(get_woocommerce_currency());
		$order->set_prices_include_tax(0);
		$order->set_customer_note(isset($queryArray['note']) ? $queryArray['note'] : '');
		//$order->set_status( 'wc-processing' );

		$produtData = $queryArray['product_data'];

		// Line items
		foreach ($produtData as $line_item) {
			$produtArray['product_id'] = $line_item['product_id'];
			$produtArray['variation_id'] = $line_item['variant_id'];

			$produtArray['quantity'] = $line_item['quantity'];
			$produtArray['meta_data'] = array(
				array(
					'key' => 'custom_design_id',
					'value' => $line_item['custom_design_id'] ? $line_item['custom_design_id'] : 0,
				),
				array(
					'key' => 'artwork_type',
					'value' => $line_item['artwork_type'],
				),
				array(
					'key' => 'design_cost',
					'value' => $line_item['design_cost'],
				),
			);
			$product = wc_get_product(isset($line_item['variant_id']) && $line_item['variant_id'] > 0 ? $line_item['variant_id'] : $line_item['product_id']);
			$price = $product->get_price() + ($line_item['design_cost'] / $line_item['quantity']);
			$product->set_price($price);
			$product_item_id = $order->add_product($product, $line_item['quantity'], $produtArray);
			wc_add_order_item_meta($product_item_id, "custom_design_id", $line_item['custom_design_id'] ? $line_item['custom_design_id'] : 0);
			wc_add_order_item_meta($product_item_id, "artwork_type", $line_item['artwork_type']);
			wc_add_order_item_meta($product_item_id, "design_cost", $line_item['design_cost']);
		}
		// Fee items
		$order_id = $order->get_id();

		$order->add_meta_data('is_vat_exempt', 'yes', true);
		$fees = array('rush', 'shipping', 'tax', 'discount');
		foreach ($fees as $fee) {
			if (isset($queryArray[$fee . '_amount']) && $queryArray[$fee . '_amount'] != '' && $queryArray[$fee . '_amount'] > 0) {

				$lable = ($fee == 'rush') ? 'Rush Surcharge' : ucwords($fee);
				$amount = ($fee == 'tax') ? ($designTotal * $queryArray[$fee . '_amount']) / 100 : $queryArray[$fee . '_amount'];
				$amount = ($fee == 'discount') ? -$amount : $amount;

				$item_id = wc_add_order_item($order_id, array('order_item_name' => $lable, 'order_item_type' => 'fee'));
				if ($item_id) {
					wc_add_order_item_meta($item_id, '_line_total', $amount);
					wc_add_order_item_meta($item_id, '_line_tax', 0);
					wc_add_order_item_meta($item_id, '_line_subtotal', $amount);
					wc_add_order_item_meta($item_id, '_line_subtotal_tax', 0);
				}
			}

		}
		// Set calculated totals
		$order->calculate_totals();

		$order->set_total($quoteTotal);

		// Save order to database (returns the order ID)
		$order_id = $order->save();

		$order->update_status('wc-processing');

		// Update order meta data
		$order->update_meta_data('_quote_id', $quoteId);
		$order->update_meta_data('_is_artwork', $isArtwork);
		$order->update_meta_data('_is_rush', $isRush);
		$order->update_meta_data('_rush_type', $rushType);
		$order->update_meta_data('_rush_amount', $rushAmount);
		$order->update_meta_data('_discount_type', $discountType);
		$order->update_meta_data('_discount_amount', $discountAmount);
		$order->update_meta_data('_shipping_type', $shippingType);
		$order->update_meta_data('_shipping_amount', $shippingAmount);
		$order->update_meta_data('_design_total', $designTotal);
		$order->update_meta_data('_quote_total', $quoteTotal);

		// Returns the order ID
		return array('id' => $order_id);

	}
	/**
	 * POST: Get Order items
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   22 July 2020
	 * @return json
	 */
	public function archiveOrderById($request, $response, $args) {
		$storeResponse = [];
		$orderIds = $request->getParsedBody();
		try {
			// Calling to Custom API for getting Archive status
			$archiveStatus = object_to_array($this->plugin->post('orders/archive', $orderIds));
			// print_r($archiveStatus); exit;
			$storeResponse = $archiveStatus;
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Archive Status',
					],
				]
			);
		}
		return $storeResponse;
	}
	/**
	 * GET: Order details
	 *
	 * @param $order_id
	 * @param $store_id
	 *
	 * @author soumyas@riaxe.com
	 * @date   11 December 2020
	 * @return Array
	 */
	public function getStoreOrderLineItemDetails($order_id, $orderItemId, $is_customer, $store_id) {
		require_once $this->storePath['abspath'] . "wp-blog-header.php";
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$orderResponse = [];
		$i = 0;
		if (!empty($store_id) && !empty($order_id) && !empty($orderItemId)) {
			$order = wc_get_order($order_id);
			$item = new \WC_Order_Item_Product($orderItemId);
			$product = $item->get_product();
			$product_sku = null;
			if (is_object($product)) {
				$product_sku = $product->get_sku();
			}
			$image_id = $product->get_image_id();
			$image_url = wp_get_attachment_image_url($image_id, 'full');
			$thumbnail_url = wp_get_attachment_image_url($image_id, 'thumbnail');
			$orderResponse['order_id'] = $order_id;
			$orderResponse['order_number'] = $order_id;
			$orderResponse['item_id'] = $orderItemId;
			$orderResponse['product_id'] = $item->get_product_id();
			$variation_id = $item->get_variation_id();
			$id = isset($variation_id) && $variation_id > 0 ? $item->get_variation_id() : $item->get_product_id();
			$orderResponse['variant_id'] = isset($variation_id) && $variation_id > 0 ? $item->get_variation_id() : $item->get_product_id();
			$orderResponse['name'] = $item->get_name();
			$orderResponse['quantity'] = $item->get_quantity();
			$orderResponse['sku'] = $product_sku;
			if ($is_customer == true) {
				$orderResponse['price'] = $product->get_price();
				$orderResponse['total'] = $item->get_total();
			}
			$orderResponse['images'][] = array('src' => $image_url, 'thumbnail' => $thumbnail_url);
			$categoryIds = $this->getCategoryByProductId($item->get_product_id());
			$product = wc_get_product($id);
			$attributes = $product->get_attributes();
			$orderResponse['categories'] = $categoryIds;
			$attribute = [];
			if ($orderResponse['product_id'] != $orderResponse['variant_id']) {
				foreach ($attributes as $key => $value) {
					$key = urldecode($key);
					$attrTermDetails = get_term_by('slug', $value, $key);
					if (empty($attrTermDetails)) {
						$attrTermDetails = get_term_by('name', $value, $key);
					}
					$term = wc_attribute_taxonomy_id_by_name($key);
					$attrName = wc_attribute_label($key);
					$attrValId = $attrTermDetails->term_id;
					$attrValName = $attrTermDetails->name;
					$attribute[$attrName]['id'] = $attrValId;
					$attribute[$attrName]['name'] = $attrValName;
					$attribute[$attrName]['attribute_id'] = $term;
					$attribute[$attrName]['hex-code'] = '';
				}
			} else {
				foreach ($attributes as $attrKey => $attributelist) {
					if ($attrKey != 'pa_xe_is_designer' && $attrKey != 'pa_is_catalog') {
						foreach ($attributelist['options'] as $key => $value) {
							$term = wc_attribute_taxonomy_id_by_name($attributelist['name']);
							$attrName = wc_attribute_label($attributelist['name']);
							$attrValId = $value;
							$attrTermDetails = get_term_by('id', absint($value), $attributelist['name']);
							$attrValName = $attrTermDetails->name;
							$attribute[$attrName]['id'] = $attrValId;
							$attribute[$attrName]['name'] = $attrValName;
							$attribute[$attrName]['attribute_id'] = $term;
							$attribute[$attrName]['hex-code'] = '';
						}
					}
				}
			}
			$orderResponse['attributes'] = $attribute;
			if ($is_customer == true) {
				$custom_design_id = $item->get_meta('custom_design_id') ? $item->get_meta('custom_design_id') : 0;
				$orderResponse['custom_design_id'] = $custom_design_id;
				$customerDetails = [];
				$order = wc_get_order($order_id);
				$orderResponse['customer_id'] = $order->get_customer_id();
				$user_data = get_userdata($order->get_customer_id());
				$orderResponse['customer_email'] = $user_data->data->user_email;
				$orderResponse['customer_first_name'] = get_user_meta($order->get_customer_id(), 'first_name', true);
				$orderResponse['customer_last_name'] = get_user_meta($order->get_customer_id(), 'last_name', true);
				// BILLING INFORMATION
				$orderResponse['billing']['first_name'] = $order->get_billing_first_name();
				$orderResponse['billing']['last_name'] = $order->get_billing_last_name();
				$orderResponse['billing']['company'] = $order->get_billing_company() ? $order->get_billing_company() : '';
				$orderResponse['billing']['address_1'] = $order->get_billing_address_1();
				$orderResponse['billing']['address_2'] = $order->get_billing_address_2();
				$orderResponse['billing']['city'] = $order->get_billing_city();
				$orderResponse['billing']['state'] = $order->get_billing_state();
				$orderResponse['billing']['country'] = $order->get_billing_country();
				$orderResponse['billing']['postcode'] = $order->get_billing_postcode();
				// SHIPPING INFORMATION
				$orderResponse['shipping']['first_name'] = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : '';
				$orderResponse['shipping']['last_name'] = $order->get_shipping_last_name() ? $order->get_shipping_last_name() : '';
				$orderResponse['shipping']['address_1'] = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : '';
				$orderResponse['shipping']['address_2'] = $order->get_shipping_address_2() ? $order->get_shipping_address_2() : '';
				$orderResponse['shipping']['city'] = $order->get_shipping_city() ? $order->get_shipping_city() : '';
				$orderResponse['shipping']['state'] = $order->get_shipping_state() ? $order->get_shipping_state() : '';
				$orderResponse['shipping']['country'] = $order->get_shipping_country() ? $order->get_shipping_country() : '';
				$orderResponse['shipping']['postcode'] = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : '';
				//$orderResponse['customer_details'] = $customerDetails;
			}

		}
		return $orderResponse;
	}
	/**
	 * GET: All Category by product id
	 *
	 * @param $productId
	 *
	 * @author soumyas@riaxe.com
	 * @date   11 December 2020
	 * @return Array
	 */
	public function getCategoryByProductId($productId) {
		$product = wc_get_product($productId);
		$categoryIds = $product->get_category_ids();
		return $categoryIds;

	}
	/**
	 * GET: Product extra tnformation
	 *
	 * @param $productId
	 * @param $variation_id
	 *
	 * @author soumyas@riaxe.com
	 * @date   04 Jan 2021
	 * @return Array
	 */
	public function getProductExtraInformation($product_id, $variation_id) {
		require_once $this->storePath['abspath'] . "wp-blog-header.php";
		$productExtraInformation = [];
		$attribute = [];
		$product = wc_get_product($product_id);
		$image_id = $product->get_image_id();
		$image_url = wp_get_attachment_image_url($image_id, 'full');
		$thumbnail_url = wp_get_attachment_image_url($image_id, 'thumbnail');
		$productExtraInformation['images'][] = array('src' => $image_url, 'thumbnail' => $thumbnail_url);
		$categoryIds = $product->get_category_ids();
		$productExtraInformation['categories'] = $categoryIds;
		$id = ($product_id != $variation_id) ? $variation_id : $product_id;
		$productAttributes = wc_get_product($id);
		$attributes = $productAttributes->get_attributes();
		if ($product_id != $variation_id) {
			foreach ($attributes as $key => $value) {
				$key = urldecode($key);
				$attrTermDetails = get_term_by('slug', $value, $key);
				if (empty($attrTermDetails)) {
					$attrTermDetails = get_term_by('name', $value, $key);
				}
				$term = wc_attribute_taxonomy_id_by_name($key);
				$attrName = wc_attribute_label($key);
				$attrValId = $attrTermDetails->term_id;
				$attrValName = $attrTermDetails->name;
				$attribute[$attrName]['id'] = $attrValId;
				$attribute[$attrName]['name'] = $attrValName;
				$attribute[$attrName]['attribute_id'] = $term;
				$attribute[$attrName]['hex_code'] = '';
			}

		} else {
			foreach ($attributes as $attrKey => $attributelist) {
				if ($attrKey != 'pa_xe_is_designer' && $attrKey != 'pa_is_catalog') {
					foreach ($attributelist['options'] as $key => $value) {
						$term = wc_attribute_taxonomy_id_by_name($attributelist['name']);
						$attrName = wc_attribute_label($attributelist['name']);
						$attrValId = $value;
						$attrTermDetails = get_term_by('id', absint($value), $attributelist['name']);
						$attrValName = $attrTermDetails->name;
						$attribute[$attrName]['id'] = $attrValId;
						$attribute[$attrName]['name'] = $attrValName;
						$attribute[$attrName]['attribute_id'] = $term;
						$attribute[$attrName]['hex_code'] = '';
					}
				}
			}
		}
		$productExtraInformation['attributes'] = $attribute;
		return $productExtraInformation;
	}
}