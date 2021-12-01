<?php
class ControllerExtensionFeedWebApi extends Controller
{
    private $debug = false;

    /*Get Orders of Open-cart START*/
    public function getOrders()
    {
        $json = array('is_Fault' => 0);
        $isCustomize = 0;
        $isCusPro = 0;
        $sort = "";
        // Loading order Models
        $this->load->language('account/order');
        $this->load->model('account/order');
        //Loading page start & limit
        if (isset($this->request->get['start'])) {
            $start = $this->request->get['start'];
        } else {
            $start = 0;
        }
        if (isset($this->request->get['customer_id'])) {
            $customer_id = $this->request->get['customer_id'];
        } else {
            $customer_id = 0;
        }
        if (isset($this->request->get['per_page'])) {
            $limit = $this->request->get['per_page'];
        } else {
            $limit = 10000000;
        }
        if (isset($this->request->get['is_customize'])) {
            $isPreDeco = $this->request->get['is_customize'];
        } else {
            $isPreDeco = 0;
        }
        if (isset($this->request->get['order'])) {
            $order = trim(strtoupper($this->request->get['order']));
        } else {
            $order = "DESC";
        }

        if (isset($this->request->get['from'])) {
            $from = trim(strtoupper($this->request->get['from']));
        } else {
            $from = "";
        }

        if (isset($this->request->get['to'])) {
            $to = trim(strtoupper($this->request->get['to']));
        } else {
            $to = "";
        }
        if (isset($this->request->get['order_by'])) {
            if ($this->request->get['order_by'] == "name") {
                $sort = "o.firstname";
            } else {
                $sort = "o.order_id";
            }
        } else {
            $sort = "";
        }
        if (isset($this->request->get['search'])) {
            $search = $this->request->get['search'];
            if (is_numeric($search)) {
                $idSearch = $search;
                $custSearch = "";
            } else {
                $custSearch = $search;
                $idSearch = "";
            }
        } else {
            $custSearch = "";
            $idSearch = "";
        }
        $last_order_id = isset($this->request->get['last_order_id']) ? $this->request->get['last_order_id'] : 0;
        $filter_data = array(
            'start' => $start,
            'limit' => $limit,
            'order' => $order,
            'filter_customer' => $custSearch,
            'filter_order_id' => $idSearch,
            'sort' => $sort,
            'filter_date_added_from' => $from,
            'filter_date_added_to' => $to,
            'customer_id' => $customer_id,
        );
        if (isset($last_order_id) && $last_order_id != 0) {
            $filter_data['last_order_id'] = $last_order_id;
        }

        $results = $this->model_account_order->getOrderList($filter_data);
        foreach ($results as $result) {
            $isCusPro = 0;
            $isCustomize = 0;
            $orderProduct = $this->model_account_order->getOrderProducts($result['order_id']);
           
            $totalOrderQty = 0;
            foreach ($orderProduct as $orderItem) {
                $isCusPro = 0;
                $isCustomize = 0;
                $totalOrderQty += $orderItem['quantity'];
                $productOption = $this->model_account_order->getOrderOptions($result['order_id'], $orderItem['order_product_id']);
                foreach ($productOption as $key =>$itemOption) {
                    $optionName = $itemOption['name'];
                    if ($optionName == 'refid') {
                        $isCustomize = $itemOption['value'];
                        break;
                    }

                }
            }
            if ($isCustomize > 0 || $isCustomize == '-1') {
                $isCusPro = 1;
            }
            if ($isPreDeco) {
                if ($isCusPro) {
                    $json['order_list'][] = array(
                        'id' => $result['order_id'],
                        'order_number' => $result['order_id'],
                        'customer_first_name' => $result['firstname'],
                        'customer_last_name' => $result['lastname'],
                        'total_amount' => $result['total'],
                        'currency' => $result['currency_code'],
                        'created_date' => gmdate('Y-m-d H:i', strtotime($result['date_added'])),
                        'status' => $result['status'],
                        'is_customize' => $isCusPro,
                        'production' => '',
                        'order_total_quantity' => $totalOrderQty,
                    );
                }
            } else {
                $json['order_list'][] = array(
                    'id' => $result['order_id'],
                    'order_number' => $result['order_id'],
                    'customer_first_name' => $result['firstname'],
                    'customer_last_name' => $result['lastname'],
                    'total_amount' => $result['total'],
                    'currency' => $result['currency_code'],
                    'created_date' => gmdate('Y-m-d H:i', strtotime($result['date_added'])),
                    'status' => $result['status'],
                    'is_customize' => $isCusPro,
                    'production' => '',
                    'order_total_quantity' => $totalOrderQty,
                );
            }
        }
        if (!isset($json['order_list']) && empty($json['order_list'])) {
            $json['order_list'] = array();
        }

        if ($this->debug) {
            print_r($json);
        } else {
            $this->response->setOutput(json_encode($json));
        }
    }
    /*Get Orders of Open-cart END*/

    /*Get Order Details of Open-cart START*/
    public function getOrderDetails()
    {
        $json = array('is_Fault' => 0);
        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $storeURL = $this->config->get('config_ssl');
        } else {
            $storeURL = $this->config->get('config_url');
        }
        $this->load->model('account/order');
        $this->load->language('account/order');
        # -- $_GET params ------------------------------
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }

        if (isset($this->request->get['is_purchase_order'])) {
            $is_purchase_order = $this->request->get['is_purchase_order'];
        } else {
            $is_purchase_order = false;
        }
        $size = isset($this->request->get['size'])?$this->request->get['size']:'size';
        $color = isset($this->request->get['color'])?$this->request->get['color']:'color';
        # -- End $_GET params --------------------------
        $results = $this->model_account_order->getOrderDetail($order_id);
        $orderProduct = $this->model_account_order->getOrderProducts($results['order_id']);
        $OrderHistories = $this->model_account_order->getOrderHistories($results['order_id']);
        $orderTotalDetails = $this->model_account_order->getOrderTotals($results['order_id']);
        
        $amountDetails = array();
        foreach ($orderTotalDetails as $key => $value) {
            $amountDetails[$value['code']] = $value['value'];
        }
        $order_status = 0;
        $item = array();
        foreach ($OrderHistories as $orderstatus) {
            $order_status = $orderstatus['status'];
        }
        $json['orderIncrementId'] = $results['order_id'];
        $json['order_details']['id'] = $results['order_id'];
        $json['order_details']['customer_id'] = $results['customer_id'];
        $json['order_details']['amount_details'] = $amountDetails;
        $json['order_details']['currency'] = $results['currency_code'];
        $json['order_details']['total'] = $results['total'];
        $json['order_details']['status'] = $order_status;
        $json['order_details']['date_created'] = gmdate('Y-m-d H:i:s', strtotime($results['date_added']));
        $json['order_details']['date_modified'] = gmdate('Y-m-d H:i:s', strtotime($results['date_modified']));
        $json['order_details']['customer_name'] = $results['firstname'] . ' ' . $results['lastname'];
        $json['order_details']['customer_email'] = $results['email'];
        $json['order_details']['payment_method_title'] = $results['payment_method'];
        $json['order_details']['shipping_method'] = $results['shipping_method'];

        $json['order_details']['shipping'] = array(
            'first_name' => ($results['shipping_firstname'] != '') ? $results['shipping_firstname'] : $results['payment_firstname'],
            'last_name' => ($results['shipping_lastname'] != '') ? $results['shipping_lastname'] : $results['payment_lastname'],
            'fax' => $results['fax'],
            'region' => ($results['shipping_zone'] != '') ? $results['shipping_zone'] : $results['payment_zone'],
            'postcode' => ($results['shipping_postcode'] != '') ? $results['shipping_postcode'] : $results['payment_postcode'],
            'phone' => $results['telephone'],
            'company' => ($results['shipping_company'] != '') ? $results['shipping_company'] : $results['payment_company'],
            'address_1' => ($results['shipping_address_1'] != '') ? $results['shipping_address_1'] : $results['payment_address_1'],
            'address_2' => ($results['shipping_address_2'] != '') ? $results['shipping_address_2'] : $results['payment_address_2'],
            'city' => ($results['shipping_city'] != '') ? $results['shipping_city'] : $results['payment_city'],
            'email' => $results['email'],
            'country' => ($results['shipping_country'] != '') ? $results['shipping_country'] : $results['payment_country'],
            'state' => ($results['shipping_zone'] != '') ? $results['shipping_zone'] : $results['payment_zone'],
        );
        $json['order_details']['billing'] = array(
            'first_name' => $results['payment_firstname'],
            'last_name' => $results['payment_lastname'],
            'fax' => $results['fax'],
            'phone' => $results['telephone'],
            'email' => $results['email'],
            'city' => $results['payment_city'],
            'address_1' => $results['payment_address_1'],
            'address_2' => $results['payment_address_2'],
            'state' => $results['payment_zone'],
            'postcode' => $results['payment_postcode'],
            'country' => $results['payment_country'],
            'region' => $results['payment_zone'],
            'company' => $results['payment_company'],
        );
        $i = 0;
        foreach ($orderProduct as $orderItem) {
            $productOption = $this->model_account_order->getOrderOptions($results['order_id'], $orderItem['order_product_id']);
            $this->load->model('catalog/product');
            $productDetail = $this->model_catalog_product->getProduct($orderItem['product_id']);
            $xesize = '';
            $xecolor = '';
            $ref_id = 0;
            if ($is_purchase_order) {
                $productCategories = $this->model_catalog_product->getCategories($orderItem['product_id']);
                $catIds = array();
                // Fetch categories
                if (!empty($productCategories)) {
                    foreach ($productCategories as $key => $value) {
                        $catIds[$key] = $value['category_id'];
                    }
                }
                $productImg = $storeURL . 'image/' . $productDetail['image'];
                $item[$i]['images'] = array(array('src' => $productImg, 'thumbnail' => $productImg));
                $item[$i]['categories'] = $catIds;
                $j = 0;
                foreach ($productOption as $itemOption) {
                    $optionName = $itemOption['name'];
                    if ($optionName != 'xe_is_design' && $optionName != 'disable_addtocart' && $optionName != 'refid') {
                        $item[$i]['attributes'][$j]['name'] = $optionName;
                        $item[$i]['attributes'][$j]['value'] = $itemOption['value'];
                        $item[$i]['attributes'][$j]['product_option_id'] = $itemOption['product_option_id'];
                        $item[$i]['attributes'][$j]['product_option_value_id'] = $itemOption['product_option_value_id'];
                        $j++;
                        if ($optionName == $size) {
                            $item[$i]['xe_size'] = $itemOption['value'];
                        } elseif ($optionName == $color) {
                            $item[$i]['xe_color'] = $itemOption['value'];
                        }
                    } elseif ($optionName == 'refid') {
                        $item[$i]['ref_id'] = $itemOption['value'];
                    }
                }
            }
            $item[$i]['product_id'] = $orderItem['product_id'];
            $item[$i]['sku'] = $productDetail['sku'];
            $item[$i]['price'] = $orderItem['price'];
            $item[$i]['total'] = $orderItem['total'];
            $item[$i]['quantity'] = $orderItem['quantity'];
            $item[$i]['itemStatus'] = '';
            //$item[$i]['print_status'] = $orderItem['print_status'];
            $item[$i]['id'] = $orderItem['order_product_id'];
            $item[$i]['order_product_id'] = $orderItem['order_product_id'];
            if (!$is_purchase_order) {
                foreach ($productOption as $itemOption) {
                    $optionName = $itemOption['name'];
                    if ($optionName != 'xe_is_design' && $optionName != 'disable_addtocart') {
                        if ($optionName == $size) {
                            $item[$i]['xe_size'] = $itemOption['value'];
                        } elseif ($optionName == $color) {
                            $item[$i]['xe_color'] = $itemOption['value'];
                        } elseif ($optionName == 'refid') {
                            $item[$i]['ref_id'] = $itemOption['value'];
                        }

                        if ($optionName != 'refid') {
                            $item[$i]['attributes'][$optionName] = $itemOption['value'];
                        }
                    }
                }
            }
            
            if (array_key_exists('xe_size', $item[$i])) {
                $item[$i]['name'] = $orderItem['name'] . " - " . $item[$i]['xe_color'] . " - " . $item[$i]['xe_size'];
            } else {
                $item[$i]['name'] = $orderItem['name'];
            }
            $i++;
        }
        $json['order_details']['order_items'] = $item;
        if ($this->debug) {
            print_r($json);
        } else {
            $this->response->setOutput(json_encode($json,JSON_NUMERIC_CHECK));
        }
    }
    /*Get Order Details of Open-cart END*/

    /*Get Product Info START*/
    public function getProductInfo()
    {
        $this->load->model('catalog/product');
        # -- $_GET params ------------------------------
        try
        {
            if (isset($this->request->get['id'])) {
                $product_id = $this->request->get['id'];
            } else {
                $product_id = 0;
            }
            # -- End $_GET params --------------------------
            $product = $this->model_catalog_product->getProduct($product_id);
            $json['product'] = array(
                'id' => $product['product_id'],
                'name' => $product['name'],
                'description' => html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'),
            );
            $this->response->setOutput(json_encode($json));
        } catch (Exception $e) {
            $json['success'] = false;
            $json['errmsg'] = $e->getMessage();
            $this->response->setOutput(json_encode($json));
        }
    }
    /*Get Product Info END*/

    /* Update print_status on order_Item START */
    public function updatePrintStatus()
    {
        $json = array('success' => true);
        if (isset($this->request->get['order_product_id'])) {
            $order_product_id = $this->request->get['order_product_id'];
        } else {
            $order_product_id = 0;
        }
        $this->load->model('account/xe_custom_order');
        $results = $this->model_account_xe_custom_order->setPrintStatus($order_product_id, 1);
        if ($this->debug) {
            print_r($json);
        } else {
            $this->response->setOutput(json_encode(array('is_fault' => 0, 'successmessage' => 'order item status successfully updated to printed')));
        }
    }
    /* Get Orders Graph */
    public function getOrdersGraph()
    {

        $json = array('is_Fault' => 0);

        // Loading order Models

        $this->load->language('account/order');

        $this->load->model('account/order');

        //Loading page start & limit

        if (isset($this->request->get['from'])) {

            $from = $this->request->get['from'];

        }
        if (isset($this->request->get['to'])) {

            $to = $this->request->get['to'];

        } else {

            $limit = 10000000;

        }

        $filter_data = array();

        $filter_data = array(
            'filter_date_added_from' => $from,
            'filter_date_added_to' => $to,
        );

        $results = $this->model_account_order->getOrderList($filter_data);
        $date_array = array();
        $orderArr = array();
        $i = 0;
        foreach ($results as $result) {
            $date = gmdate('Y-m-d', strtotime($result['date_added']));
            if (empty($date_array) || !in_array($date, $date_array)) {
                $date_array[] = $date;
                $orderArr[$i]['date'] = $date;
                $orderArr[$i]['sales'] = 1;
                $i++;
            } else {
                $key = array_search($date, $date_array);
                $orderArr[$key]['sales'] = (int) $orderArr[$key]['sales'] + 1;
            }

        }
        $json = $orderArr;
        if ($this->debug) {

            print_r($json);

        } else {

            $this->response->setOutput(json_encode($json));

        }

    }

    /*Get OrdersZap of Open-cart START*/
    public function getOrdersZap()
    {
        $json = array('is_Fault' => 0);
        // Loading order Models
        $this->load->language('account/order');
        $this->load->model('account/order');
        //Loading page start & limit
        if (isset($this->request->get['start'])) {
            $start = $this->request->get['start'];
        } else {
            $start = 0;
        }
        if (isset($this->request->get['limit'])) {
            $limit = $this->request->get['limit'];
        } else {
            $limit = 10000000;
        }
        $last_order_id = isset($this->request->get['last_order_id']) ? $this->request->get['last_order_id'] : 0;
        $filter_data = array(
            'start' => $start,
            'limit' => $limit,
            'order' => 'DESC',
        );
        if (isset($last_order_id) && $last_order_id != 0) {
            $filter_data['last_order_id'] = $last_order_id;
        }

        $results = $this->model_account_order->getOrderList($filter_data);
        foreach ($results as $result) {
            $order_detail = $this->model_account_order->getOrderDetail($result['order_id']);
            $shipping_address = array(
                'first_name' => ($order_detail['shipping_firstname'] != '') ? $order_detail['shipping_firstname'] : $order_detail['payment_firstname'],
                'last_name' => ($order_detail['shipping_lastname'] != '') ? $order_detail['shipping_lastname'] : $order_detail['payment_lastname'],
                'fax' => $order_detail['fax'],
                'region' => ($order_detail['shipping_zone'] != '') ? $order_detail['shipping_zone'] : $order_detail['payment_zone'],
                'postcode' => ($order_detail['shipping_postcode'] != '') ? $order_detail['shipping_postcode'] : $order_detail['payment_postcode'],
                'telephone' => $order_detail['telephone'],
                'company' => ($order_detail['shipping_company'] != '') ? $order_detail['shipping_company'] : $order_detail['payment_company'],
                'address_1' => ($order_detail['shipping_address_1'] != '') ? $order_detail['shipping_address_1'] : $order_detail['payment_address_1'],
                'address_2' => ($order_detail['shipping_address_2'] != '') ? $order_detail['shipping_address_2'] : $order_detail['payment_address_2'],
                'city' => ($order_detail['shipping_city'] != '') ? $order_detail['shipping_city'] : $order_detail['payment_city'],
                'email' => $order_detail['email'],
                'country' => ($order_detail['shipping_country'] != '') ? $order_detail['shipping_country'] : $order_detail['payment_country'],
                'state' => ($order_detail['shipping_zone'] != '') ? $order_detail['shipping_zone'] : $order_detail['payment_zone'],
            );
            $billing_address = array(
                'first_name' => $order_detail['payment_firstname'],
                'last_name' => $order_detail['payment_lastname'],
                'fax' => $order_detail['fax'],
                'telephone' => $order_detail['telephone'],
                'email' => $order_detail['email'],
                'city' => $order_detail['payment_city'],
                'address_1' => $order_detail['payment_address_1'],
                'address_2' => $order_detail['payment_address_2'],
                'state' => $order_detail['payment_zone'],
                'postcode' => $order_detail['payment_postcode'],
                'country' => $order_detail['payment_country'],
                'region' => $order_detail['payment_zone'],
                'company' => $order_detail['payment_company'],
            );
            $json['order_list'][] = array(
                'order_id' => $result['order_id'],
                'id' => $result['order_id'],
                'order_date' => gmdate('d.m.Y H:i', strtotime($result['date_added'])),
                'order_status' => $result['status'],
                'customer_name' => $result['customer'],
                'billing_address' => $billing_address,
                'shipping_address' => $shipping_address,
            );
        }
        if (!isset($json['order_list']) && empty($json['order_list'])) {
            $json['order_list'] = array();
        }

        if ($this->debug) {
            print_r($json);
        } else {
            $this->response->setOutput(json_encode($json));
        }
    }
    /*Get OrdersZap of Open-cart END*/

    /*Update print_status END*/

    private function init()
    {
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->config->get('web_api_status')) {
            $this->error(10, 'API is disabled');
        }

        if ($this->config->get('web_api_key') && (!isset($this->request->get['key']) || $this->request->get['key'] != $this->config->get('web_api_key'))) {
            $this->error(20, 'Invalid secret key');
        }
    }

    /**
     * Error message responser
     *
     * @param string $message  Error message
     */
    private function error($code = 0, $message = '')
    {
        # setOutput() is not called, set headers manually
        header('Content-Type: application/json');

        $json = array(
            'success' => false,
            'code' => $code,
            'message' => $message,
        );

        if ($this->debug) {
            echo '<pre>';
            print_r($json);
        } else {
            echo json_encode($json);
        }

        exit();
    }
    /*Get Order Item Details of Open-cart START*/
    public function getOrderItemDetails()
    {
        $this->load->model('account/order');
        $this->load->language('account/order');
        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $storeURL = $this->config->get('config_ssl');
        } else {
            $storeURL = $this->config->get('config_url');
        }
        # -- $_GET params ------------------------------
        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        if (isset($this->request->get['order_item_id'])) {
            $order_item_id = $this->request->get['order_item_id'];
        } else {
            $order_item_id = 0;
        }
        $size = isset($this->request->get['size'])?$this->request->get['size']:'size';
        $color = isset($this->request->get['color'])?$this->request->get['color']:'color';
        # -- End $_GET params --------------------------
        $results = $this->model_account_order->getOrderDetail($order_id);
        $orderProduct = $this->model_account_order->getOrderProduct($results['order_id'],$order_item_id);
        $order_status = 0;
        $item = array();
        $json['order_item_details']['order_id'] = $results['order_id'];
        $json['order_item_details']['order_number'] = $results['order_id'];
        $json['order_item_details']['customer_id'] = $results['customer_id'];
        $json['order_item_details']['total'] = $results['total'];
        $json['order_item_details']['customer_first_name'] = $results['firstname'];
        $json['order_item_details']['customer_last_name'] = $results['lastname'];
        $json['order_item_details']['customer_email'] = $results['email'];
        $json['order_item_details']['payment_method_title'] = $results['payment_method'];
        $json['order_item_details']['shipping_method'] = $results['shipping_method'];

        $json['order_item_details']['shipping'] = array(
            'first_name' => ($results['shipping_firstname'] != '') ? $results['shipping_firstname'] : $results['payment_firstname'],
            'last_name' => ($results['shipping_lastname'] != '') ? $results['shipping_lastname'] : $results['payment_lastname'],
            'fax' => $results['fax'],
            'region' => ($results['shipping_zone'] != '') ? $results['shipping_zone'] : $results['payment_zone'],
            'postcode' => ($results['shipping_postcode'] != '') ? $results['shipping_postcode'] : $results['payment_postcode'],
            'phone' => $results['telephone'],
            'company' => ($results['shipping_company'] != '') ? $results['shipping_company'] : $results['payment_company'],
            'address_1' => ($results['shipping_address_1'] != '') ? $results['shipping_address_1'] : $results['payment_address_1'],
            'address_2' => ($results['shipping_address_2'] != '') ? $results['shipping_address_2'] : $results['payment_address_2'],
            'city' => ($results['shipping_city'] != '') ? $results['shipping_city'] : $results['payment_city'],
            'email' => $results['email'],
            'country' => ($results['shipping_country'] != '') ? $results['shipping_country'] : $results['payment_country'],
            'state' => ($results['shipping_zone'] != '') ? $results['shipping_zone'] : $results['payment_zone'],
        );
        $json['order_item_details']['billing'] = array(
            'first_name' => $results['payment_firstname'],
            'last_name' => $results['payment_lastname'],
            'fax' => $results['fax'],
            'phone' => $results['telephone'],
            'email' => $results['email'],
            'city' => $results['payment_city'],
            'address_1' => $results['payment_address_1'],
            'address_2' => $results['payment_address_2'],
            'state' => $results['payment_zone'],
            'postcode' => $results['payment_postcode'],
            'country' => $results['payment_country'],
            'region' => $results['payment_zone'],
            'company' => $results['payment_company'],
        );
        $i = 0;
        $productOption = $this->model_account_order->getOrderOptions($results['order_id'], $orderProduct['order_product_id']);
        $this->load->model('catalog/product');
        $productDetail = $this->model_catalog_product->getProduct($orderProduct['product_id']);
        $xesize = '';
        $xecolor = '';
        $ref_id = 0;
        $productCategories = $this->model_catalog_product->getCategories($orderProduct['product_id']);
        $catIds = array();
        // Fetch categories
        if (!empty($productCategories)) {
            foreach ($productCategories as $key => $value) {
                $catIds[$key] = $value['category_id'];
            }
        }
        $productImg = $storeURL . 'image/' . $productDetail['image'];
        $json['order_item_details']['images'] = array(array('src' => $productImg, 'thumbnail' => $productImg));
        $json['order_item_details']['categories'] = $catIds;

        $json['order_item_details']['product_id'] = $orderProduct['product_id'];
        $json['order_item_details']['sku'] = $productDetail['sku'];
        $json['order_item_details']['price'] = $orderProduct['price'];
        $json['order_item_details']['total'] = $orderProduct['total'];
        $json['order_item_details']['quantity'] = $orderProduct['quantity'];
        $json['order_item_details']['item_id'] = $orderProduct['order_product_id'];
        $j=0;
        foreach ($productOption as $itemOption) {
            $optionName = $itemOption['name'];
            if ($optionName != 'xe_is_design' && $optionName != 'disable_addtocart' && $optionName != 'refid') {
                $json['order_item_details']['attributes'][$j]['name'] = $optionName;
                $json['order_item_details']['attributes'][$j]['value'] = $itemOption['value'];
                $json['order_item_details']['attributes'][$j]['product_option_id'] = $itemOption['product_option_id'];
                $json['order_item_details']['attributes'][$j]['product_option_value_id'] = $itemOption['product_option_value_id'];
                $j++;
                if ($optionName == $size) {
                    $item[$i]['xe_size'] = $itemOption['value'];
                } elseif ($optionName == $color) {
                    $item[$i]['xe_color'] = $itemOption['value'];
                }
            } elseif ($optionName == 'refid') {
                $ref_id = $itemOption['value'];
            }
        }
        $json['order_item_details']['custom_design_id'] = $ref_id;
        
        if (array_key_exists('xe_size', $item[$i])) {
            $json['order_item_details']['name'] = $orderProduct['name'] . " - " . $item[$i]['xe_color'] . " - " . $item[$i]['xe_size'];
        } else {
            $json['order_item_details']['name'] = $orderProduct['name'];
        }
        if ($this->debug) {
            print_r($json);
        } else {
            $this->response->setOutput(json_encode($json,JSON_NUMERIC_CHECK));
        }
    }
    /*Get Order Item Details of Open-cart END*/
}
