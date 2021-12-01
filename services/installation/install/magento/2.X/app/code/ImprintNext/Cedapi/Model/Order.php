<?php

namespace ImprintNext\Cedapi\Model;

use ImprintNext\Cedapi\Api\OrderInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Order extends \Magento\Framework\Model\AbstractModel implements OrderInterface
{
    protected $_orderModel;
    protected $_productModel;
    protected $_storeManager;
    protected $_timeZone;
    protected $_countryFactory;
    protected $_orderCollectionFactory;
    protected $_objectManager;
    protected $_statusCollectionFactory;
    protected $_orderItemRepository;

    public function __construct(
        \Magento\Sales\Model\Order $_orderModel,
        \Magento\Catalog\Model\Product $_productModel,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_timeZone,
        \Magento\Directory\Model\CountryFactory $_countryFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollectionFactory,
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $_statusCollectionFactory,
        \Magento\Sales\Api\OrderItemRepositoryInterface $_orderItemRepository
    ) {
        $this->_orderModel = $_orderModel;
        $this->_productModel = $_productModel;
        $this->_storeManager = $_storeManager;
        $this->_timeZone = $_timeZone;
        $this->_countryFactory = $_countryFactory;
        $this->_orderCollectionFactory = $_orderCollectionFactory;
        $this->_objectManager = $_objectManager;
        $this->_statusCollectionFactory = $_statusCollectionFactory;
        $this->_orderItemRepository = $_orderItemRepository;
    }

    /**
     *
     * @api
     * @param int $store.
     * @param string $search.
     * @param int $page.
     * @param int $per_page.
     * @param string $after.
     * @param string $before.
     * @param string $order.
     * @param string $orderby.
     * @param int $customize.
     * @param int $customerId.
     * @return string The all orders in a json format.
     */
    public function getOrders($store, $search, $page, $per_page, $after, $before, $order, $orderby, $customize, $customerId)
    {
        $fromDate = date('Y-m-d', strtotime($after));
        $toDate = date('Y-m-d', strtotime($before));
        $totalOrder = $this->_orderCollectionFactory->create()->addAttributeToSelect('*')
            ->addAttributeToFilter('store_id', array('eq' => $store))
            ->load();
        if($search){
            $orderCollection = $this->_orderCollectionFactory->create()->addAttributeToSelect('*')
                ->addAttributeToFilter('store_id', array('eq' => $store))
                ->setPageSize($per_page)
                ->setOrder($orderby, $order)
                ->addFieldToFilter(
                    array('customer_firstname', 'customer_lastname', 'customer_middlename', 'increment_id'),
                    array(
                        array("like" => "%{$search}%"),
                        array("like" => "%{$search}%"),
                        array("like" => "%{$search}%"),
                        array("like" => "%{$search}%"),
                    )
                )
                ->setCurPage($page)
                ->load();
        }elseif($customerId > 0){
            $orderCollection = $this->_orderCollectionFactory->create()->addAttributeToSelect('*')
                ->addAttributeToFilter('store_id', array('eq' => $store))
                ->addAttributeToFilter('customer_id', array('eq' => $customerId))
                ->setPageSize($per_page)
                ->setOrder($orderby, $order)
                ->setCurPage($page)
                ->load();
        }else{
            $orderCollection = $this->_orderCollectionFactory->create()->addAttributeToSelect('*')
                ->addAttributeToFilter('store_id', array('eq' => $store))
                ->setPageSize($per_page)
                ->setOrder($orderby, $order)
                ->setCurPage($page)
                ->load();
        }

        $orderArr = array();
        $i = 0;
        if (isset($customize) && $customize) {
            foreach ($orderCollection as $orderValue) {
                $timeZone = $this->_timeZone->getConfigTimezone('store', $orderValue->getStore());
                $date = $this->_timeZone->formatDateTime(new \DateTime($orderValue->getCreatedAt()), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, null, $timeZone);
                $date = date('Y-m-d H:i:s', strtotime($date));
                $orderDate = date('Y-m-d', strtotime($date));
                if (($orderDate >= $fromDate) && ($orderDate <= $toDate)) {
                    $orderId = $orderValue->getId();
                    $isCustomize = $this->checkCutomizeByOrderId($orderId, $customize);
                    if ($isCustomize) {
                        $orderArr[$i] = array(
                            'id' => $orderId,
                            'order_number' => $orderValue->getIncrementId(),
                            'status' => $orderValue->getStatus(),
                            'created_date' => $date,
                            'customer_first_name' => $orderValue->getBillingAddress()->getFirstName(),
                            'customer_last_name' => $orderValue->getBillingAddress()->getLastName(),
                            'total_amount' => (float) $orderValue->getGrandTotal(),
                            'order_total_quantity' =>(int) $orderValue->gettotalQtyOrdered(),
                            'production' => '',
                            'currency' => $orderValue->getData('base_currency_code'),
                            'is_customize' => $isCustomize,
                        );
                        $i++;
                    }
                }
            }
        }else{
            foreach ($orderCollection as $orderValue) {
                $timeZone = $this->_timeZone->getConfigTimezone('store', $orderValue->getStore());
                $date = $this->_timeZone->formatDateTime(new \DateTime($orderValue->getCreatedAt()), \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, null, $timeZone);
                $date = date('Y-m-d H:i:s', strtotime($date));
                $orderDate = date('Y-m-d', strtotime($date));
                if (($orderDate >= $fromDate) && ($orderDate <= $toDate)) {
                    $orderId = $orderValue->getId();
                    $isCustomize = $this->checkCutomizeByOrderId($orderId, $customize);
                    $orderArr[$i] = array(
                        'id' => $orderId,
                        'order_number' => $orderValue->getIncrementId(),
                        'status' => $orderValue->getStatus(),
                        'created_date' => $date,
                        'customer_first_name' => $orderValue->getBillingAddress()->getFirstName(),
                        'customer_last_name' => $orderValue->getBillingAddress()->getLastName(),
                        'total_amount' => (float) $orderValue->getGrandTotal(),
                        'order_total_quantity' =>(int) $orderValue->getTotalQtyOrdered(),
                        'production' => '',
                        'currency' => $orderValue->getData('base_currency_code'),
                        'is_customize' => $isCustomize,
                    );
                    $i++;
                }
            }
        }
        return json_encode(array('is_Fault' => 0, 'order_list' => $orderArr, 'total_records' => count($orderCollection)));
    }

    /**
     *
     *date created 20-12-19(dd-mm-yy)
     *date modified (dd-mm-yy)
     *Check customize order by order id
     * @return int.
     */
    private function checkCutomizeByOrderId($orderId, $customize)
    {
        $order = $this->_orderModel->load($orderId);
        $orderItems = $order->getItemsCollection();
        $orderItems->getSelect()->group('order_id');
        $customizeData = 0;
        foreach ($orderItems as $key => $value) {
            if(!$value->getData('parent_item_id') && $value->getData('custom_design') ){
                $customizeData = 1;
            }
        }
        return $customizeData;
    }

    /**
     *
     *date created 20-10-19(dd-mm-yy)
     *date modified (dd-mm-yy)
     *Get Product Thumbnail URL
     * @return URL.
     */
    public function getThumbnailURL()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        if (strpos($_SERVER['DOCUMENT_ROOT'], '/pub') !== false && strpos($_SERVER['DOCUMENT_ROOT'], '/public_html') == false) {
            $img = (string) $baseUrl . 'media/catalog/product';
        } else {
            $img = (string) $baseUrl . 'pub/media/catalog/product';
        }
        return $img;
    }

    /**
     *
     * @api
     * @param int $productId.
     * @param int $store.
     * @return string product images in a json format.
     */
    public function getProductImages($productId, $store)
    {
        $result = array();
        $productdata = $this->_productModel->load($productId);
        $productImages = $productdata->getMediaGalleryImages()->setOrder('position', 'ASC');
        $productImagesLength = $productImages->getSize();
        $imageArr['images'] = array();
        if ($productImagesLength > 0) {
            $i = 0;
            foreach ($productImages as $productImage) {
                $imageId = $productImage->getId();
                $curImage = $productImage->getUrl();
                $curThumb = $this->getThumbnailURL() . $productImage->getFile();
                $result[$i]['id'] = $imageId;
                $result[$i]['src'] = $curImage;
                $result[$i]['thumbnail'] = $curThumb;
                $i++;
            }
        }
        return $result;
    }

    /**
     *
     * @api
     * @param int $orderId.
     * @param int $minimalData.
     * @param int $isPurchaseOrder.
     * @param int $store.
     * @return string The order details in a json format.
     */
    public function getOrderDetails($orderId, $minimalData, $isPurchaseOrder, $store)
    {
        $orderDetails = array();
        $order = $this->_orderModel->load($orderId);
        if($minimalData){
            $orderDetails['order_id'] = (int) $order->getId();
            $orderDetails['order_incremental_id'] = $order->getIncrementId();
            $orderDetails['customer_id'] = $order->getCustomerId();
            $orderDetails['store_id'] = $order->getStoreId();
            $orderItems = $order->getItemsCollection();
            $orderCounter = 0;
            foreach ($orderItems as $item) {
                $simpleProduct = $this->_productModel->loadByAttribute('sku', $item->getSku());
                if (!$item->getParentItemId()) {
                    $orderDetails['order_items'][$orderCounter]['item_id'] = (int) $item->getId();
                    $orderDetails['order_items'][$orderCounter]['print_status'] = '';
                    $orderDetails['order_items'][$orderCounter]['product_id'] = (int) $item->getProductId();
                    $orderDetails['order_items'][$orderCounter]['variant_id'] = (int) (!empty($simpleProduct)) ? $simpleProduct->getId() : $item->getProductId();
                    $orderDetails['order_items'][$orderCounter]['product_sku'] = $item->getSku();
                    $orderDetails['order_items'][$orderCounter]['product_name'] = $item->getName();
                    $orderDetails['order_items'][$orderCounter]['quantity'] = intval($item->getQtyOrdered());
                    $orderDetails['order_items'][$orderCounter]['ref_id'] = intval($item->getCustomDesign());
                    if($isPurchaseOrder){
                        $categories = $simpleProduct->getCategoryIds();
                        $attributes = $simpleProduct->getAttributes();
                        if ($attributes) {
                            $attributesArray = [];
                            foreach ($attributes as $attribute) {
                                $attrCode = $attribute->getAttributeCode();
                                $attrId = $attribute->getAttributeId();
                                if ($attribute->getIsVisibleOnFront()) {
                                    $attrText = $simpleProduct->getAttributeText($attrCode);
                                    $attrOption = $simpleProduct->getResource()->getAttribute($attribute);
                                    $optionId = $attrOption->getSource()->getOptionId($attrText);
                                    if ($attrText) {
                                        $attributesArray[$attrCode]["id"] = $optionId;
                                        $attributesArray[$attrCode]['name'] = $attrText;
                                        $attributesArray[$attrCode]['attribute_id'] = $attrId;
                                    }
                                }
                            }
                        }
                        $orderDetails['order_items'][$orderCounter]['images'] = $this->getProductImages($item->getProductId(), $store);
                        $orderDetails['order_items'][$orderCounter]['categories'] = $categories;
                        $orderDetails['order_items'][$orderCounter]['attributes'] = $attributesArray;
                    }
                    $orderCounter++;
                }
            }
            return json_encode(array('order_details' => $orderDetails));
        }else{
            $totalAmount = $order->getGrandTotal() - $order->getTaxAmount() - $order->getShippingAmount() + $order->getDiscountAmount();
            $orderDetails['id'] = (int) $order->getId();
            $orderDetails['order_number'] = $order->getIncrementId();
            $orderDetails['status'] = $order->getStatus();
            $orderDetails['created_date'] = $order->getCreatedAt();
            $orderDetails['customer_id'] = $order->getCustomerId();
            $orderDetails['store_id'] = $order->getStoreId();
            $orderDetails['customer_email'] = $order->getCustomerEmail();
            $orderDetails['customer_first_name'] = $order->getData('customer_firstname');
            $orderDetails['customer_last_name'] = $order->getData('customer_lastname');
            $orderDetails['total_amount'] = (float) $totalAmount;
            $orderDetails['total_tax'] = (float) $order->getTaxAmount();
            $orderDetails['total_shipping'] = (float) $order->getShippingAmount();
            $orderDetails['total_discounts'] = (float) $order->getDiscountAmount();
            $orderDetails['currency'] = $order->getData('base_currency_code');
            $orderDetails['note'] = $order->getData('customer_note') ? $order->getData('customer_note') : '';
            $orderDetails['payment'] = $order->getPayment()->getMethodInstance()->getTitle();
            $orderDetails['store_url'] = $this->_storeManager->getStore()->getBaseUrl();
            $orderDetails['production'] = '';

            // Get total order count by a customer
            $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $select = $connection->select()
                ->from($resource->getTableName('sales_order'), 'COUNT(*)')
                ->where('customer_id=?', $order->getData('customer_id'));
            $orderDetails['total_orders'] = (int) $connection->fetchOne($select);
            $orderItems = $order->getItemsCollection();
            $shippingAddress = $order->getShippingAddress();
            if (!empty($shippingAddress)) {
                $countryCode = $shippingAddress->getData('country_id');
                $country = $this->_countryFactory->create()->loadByCode($countryCode);
                $shippingStreet = $shippingAddress->getStreet();
                $orderDetails['shipping']['first_name'] = $shippingAddress->getFirstname() ? $shippingAddress->getFirstname() : '';
                $orderDetails['shipping']['last_name'] = $shippingAddress->getLastname() ? $shippingAddress->getLastname() : '';
                $orderDetails['shipping']['fax'] = $shippingAddress->getFax() ? $shippingAddress->getFax() : '';
                $orderDetails['shipping']['postcode'] = $shippingAddress->getPostcode() ? $shippingAddress->getPostcode() : '';
                $orderDetails['shipping']['telephone'] = $shippingAddress->getTelephone() ? $shippingAddress->getTelephone() : '';
                $orderDetails['shipping']['city'] = $shippingAddress->getCity() ? $shippingAddress->getCity() : '';
                if (isset($shippingStreet[0])) {
                    $orderDetails['shipping']['address_1'] = $shippingStreet[0] ? $shippingStreet[0] : '';
                }
                if (isset($shippingStreet[1])) {
                    $orderDetails['shipping']['address_2'] = $shippingStreet[1] ? $shippingStreet[1] : '';
                }
                $orderDetails['shipping']['state'] = $shippingAddress->getRegion() ? $shippingAddress->getRegion() : '';
                $orderDetails['shipping']['company'] = $shippingAddress->getCompany() ? $shippingAddress->getCompany() : '';
                $orderDetails['shipping']['email'] = $shippingAddress->getEmail() ? $shippingAddress->getEmail() : '';
                $orderDetails['shipping']['country'] = $country->getName() ? $country->getName() : '';
            }
            $billingAddress = $order->getBillingAddress();
            $billingStreet = $billingAddress->getStreet();
            $orderDetails['billing']['first_name'] = $billingAddress->getFirstname() ? $billingAddress->getFirstname() : '';
            $orderDetails['billing']['last_name'] = $billingAddress->getLastname() ? $billingAddress->getLastname() : '';
            $orderDetails['billing']['postcode'] = $billingAddress->getPostcode() ? $billingAddress->getPostcode() : '';
            $orderDetails['billing']['telephone'] = $billingAddress->getTelephone() ? $billingAddress->getTelephone() : '';
            $orderDetails['billing']['state'] = $billingAddress->getRegion() ? $billingAddress->getRegion() : '';
            $orderDetails['billing']['city'] = $billingAddress->getCity() ? $billingAddress->getCity() : '';
            if (isset($billingStreet[0])) {
                $orderDetails['billing']['address_1'] = $billingStreet[0] ? $billingStreet[0] : '';
            }
            if (isset($billingStreet[1])) {
                $orderDetails['billing']['address_2'] = $billingStreet[1] ? $billingStreet[1] : '';
            }
            $orderDetails['billing']['company'] = $billingAddress->getCompany() ? $billingAddress->getCompany() : '';
            $orderDetails['billing']['email'] = $billingAddress->getEmail() ? $billingAddress->getEmail() : '';
            $orderDetails['billing']['telephone'] = $billingAddress->getTelephone() ? $billingAddress->getTelephone() : '';
            $orderDetails['billing']['country'] = $country->getName() ? $country->getName() : '';
            $orderDetails['orders'] = array();
            $simpindex = 0;
            foreach ($orderItems as $item) {
                $product = $this->_productModel->load($item->getProductId());
                $attributes = $product->getAttributes();
                $customOptions = $product->getOptions();
                $simpleProduct = $this->_productModel->loadByAttribute('sku', $item->getSku());
                if (!$item->getParentItemId()) {
                    $orderDetails['orders'][$simpindex]['id'] = (int) $item->getId();
                    $orderDetails['orders'][$simpindex]['product_id'] = (int) $item->getProductId();
                    $orderDetails['orders'][$simpindex]['variant_id'] = (int) (!empty($simpleProduct)) ? $simpleProduct->getId() : $item->getProductId();
                    $orderDetails['orders'][$simpindex]['custom_design_id'] = ($item->getCustomDesign()) ? intval($item->getCustomDesign()) : '';
                    $orderDetails['orders'][$simpindex]['sku'] = $item->getSku();
                    $orderDetails['orders'][$simpindex]['name'] = $item->getName();
                    $orderDetails['orders'][$simpindex]['quantity'] = intval($item->getQtyOrdered());
                    $orderDetails['orders'][$simpindex]['total'] = (float) $item->getData('row_total');
                    $orderDetails['orders'][$simpindex]['price'] = (float) ($item->getPrice());
                    $orderDetails['orders'][$simpindex]['images'] = $this->getProductImages($orderDetails['orders'][$simpindex]['product_id'], $store);
                    $simpindex++;
                }
            }
            return json_encode(array('is_Fault' => 0, 'order_list' => $orderDetails));
        }
    }

    /**
     *
     * @api
     * @param int $orderId.
     * @param int $store.
     * @return string The order log details in a json format.
     */
    public function getOrderlogByOrderId($orderId, $store)
    {
        $orderDetails = array();
        $order = $this->_orderModel->load($orderId);
        $orderDetails['id'] = (int) $order->getId();
        $orderDetails['order_id'] = $order->getIncrementId();
        $orderDetails['agent_type'] = 'admin';
        $orderDetails['agent_id'] = '';
        $orderDetails['store_id'] = $store;
        $orderDetails['message'] = $order->getStatusLabel();
        $orderDetails['log_type'] = 'order_status';
        $orderDetails['status'] = 'New';
        $orderDetails['created_at'] = $order->getCreatedAt();
        $orderDetails['updated_at'] = $order->getUpdatedAt();
        return json_encode(array('order_details' => $orderDetails));
    }

    /**
     *
     * @api
     * @param int $store.
     * @return string The order all statuses in a json format.
     */
    public function getAllOrderStatuses($store)
    {
        $orderStatuses = array();
        $options = $this->_statusCollectionFactory->create()->toOptionArray();
        $i = 0;
        foreach ($options as $option) {
            $orderStatuses[$i]['value'] = $option['label'];
            $orderStatuses[$i]['key'] = $option['value'];
            $i++;
        }
        return json_encode($orderStatuses);
    }

    /**
     *
     * @api
     * @param int $orderId.
     * @param string $orderStatus.
     * @return string success or failure in a json format.
     */
    public function updateOrderStatusByOrderId($orderId, $orderStatus)
    {   
        $result = 'success';
        try{
            $order = $this->_orderModel->load($orderId);
            $order->setState($orderStatus)->setStatus($orderStatus);
            $order->save();
        }catch (\Exception $e) {
            $result = 'failure';
        }
        return json_encode($result);
    }

    /**
     *
     * @api
     * @param string $orderData.
     * @return string success or failure in a json format.
     */
    public function placeOrderFromQuotation($orderData)
    {   
        $response = array();
        $orderData = json_decode($orderData, true);
        $store = $this->_storeManager->getStore($orderData['store_id']);
        $customerRepository = $this->_objectManager->get('\Magento\Customer\Api\CustomerRepositoryInterface');
        $addressFactory = $this->_objectManager->get('\Magento\Customer\Model\AddressFactory');
        $quoteRepository = $this->_objectManager->get('\Magento\Quote\Model\QuoteFactory');
        $productFactory = $this->_objectManager->get('\Magento\Catalog\Model\ProductFactory');
        $cartRepositoryInterface = $this->_objectManager->get('\Magento\Quote\Api\CartRepositoryInterface');
        $cartManagementInterface = $this->_objectManager->get('\Magento\Quote\Api\CartManagementInterface');
        //Get shipping method
        $scopeConfig = $this->_objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $shipconfig = $this->_objectManager->get('\Magento\Shipping\Model\Config');
        $activeCarriers = $shipconfig->getActiveCarriers();
        foreach($activeCarriers as $carrierCode => $carrierModel)
        {
            $options = array();
            if( $carrierMethods = $carrierModel->getAllowedMethods() )
            {
                foreach ($carrierMethods as $methodCode => $method)
                {
                    $code= $carrierCode.'_'.$methodCode;
                    $options=array('value'=>$code,'label'=>$method);

                }
            }
            $methods[]=$options;
        }
        $freeShippingArr = array_filter($methods, function ($item){
            return $item['value'] == 'freeshipping_freeshipping';
        });
        if (count($freeShippingArr) > 0) {
            $freeShippingArr = $freeShippingArr[array_keys($freeShippingArr)[0]];
            $shippingMethod = $freeShippingArr;
        } else {
            $shippingMethod = $methods[0];
        }
        try {
            $customer = $customerRepository->getById($orderData['customer_id']);
            $quote = $quoteRepository->create();
            $quote->setStore($store);
            $quote->setCurrency();
            $quote->assignCustomer($customer);
            foreach($orderData['product_data'] as $item){
                $productRepository = $this->_objectManager->get('\Magento\Catalog\Api\ProductRepositoryInterface');
                $productByItem = $productRepository->getById($item['product_id'], false, null, true);
                if ($productByItem->getData('type_id') == 'configurable') {
                    //Get super_attribute
                    $product = $this->_productModel->load($item['variant_id']);
                    $attributes = $product->getAttributes();
                    if ($attributes) {
                        foreach ($attributes as $attribute) {
                            $attrCode = $attribute->getAttributeCode();
                            $attrId = $attribute->getAttributeId();
                            if ($attribute->getIsVisibleOnFront()) {
                                $attrText = $product->getAttributeText($attrCode);
                                $attrOption = $product->getResource()->getAttribute($attribute);
                                $optionId = $attrOption->getSource()->getOptionId($attrText);
                                if ($attrText) {
                                    $attributesArray[$attrId] = $optionId;
                                }
                            }
                        }
                    }
                    $objParam = new \Magento\Framework\DataObject(
                        [
                            'product_id' => $item['product_id'],
                            'qty' => $item['quantity'],
                            'super_attribute' => $attributesArray
                        ]
                    );
                    $quote->addProduct(
                        $productByItem, $objParam
                    );
                } else {
                    $quote->addProduct(
                        $productByItem, intval($item['quantity'])
                    );
                }
                
            }

            $billingAddress = $addressFactory->create()->load($customer->getDefaultBilling());
            $shippingAddress = $addressFactory->create()->load($orderData['shipping_id']);

            $quote->getBillingAddress()->addData($billingAddress->getData());
            $quote->getShippingAddress()->addData($shippingAddress->getData());

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                            ->collectShippingRates()
                            ->setShippingMethod($shippingMethod['value']); 
            $quote->setPaymentMethod('checkmo'); 
            $quote->setInventoryProcessed(false); 
            $quote->save();

            $quote->getPayment()->importData(['method' => 'checkmo']);
            $quote->collectTotals()->save();

            $quoteItems = $quote->getAllItems();
            foreach ($quoteItems as $quoteItem) {
                $itemProductId = $quoteItem->getProductId();
                $simpleProduct = $this->_productModel->loadByAttribute('sku', $quoteItem->getSku());
                $simpleProductId = $simpleProduct->getId();
                foreach ($orderData['product_data'] as $productItem) {
                    if ($productItem['variant_id'] == 0) {
                        $simpleProductId = 0;
                    } 
                    if ($productItem['product_id'] == $itemProductId && $productItem['variant_id'] == $simpleProductId) {
                        $customId = $productItem['custom_design_id'];
                        $designCost = $productItem['design_cost'] / $productItem['overall_quantity'];
                        $customPrice = $productItem['unit_price'] + $designCost;
                        $quoteItem->setCustomDesign($customId);
                        $quoteItem->setCustomPrice($customPrice);
                        $quoteItem->setOriginalCustomPrice($customPrice);
                        $quoteItem->save();
                    }
                }
            }
            
            $quote->collectTotals()->save();
            $quote = $cartRepositoryInterface->get($quote->getId());
            $orderId = $cartManagementInterface->placeOrder($quote->getId());
            $order = $this->_orderModel->load($orderId);
            $orderNumber = $order->getIncrementId();
            $response = [
                'id' => $orderId,
                'order_number' => $orderNumber
            ];
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode($response);
        }
        return json_encode($response);
    }

    /**
     *
     * @api
     * @param int $orderId.
     * @param int $orderItemId.
     * @param int $isCustomer.
     * @param int $store.
     * @return string The order item details in a json format.
     */
    public function getOrderLineItemDetails($orderId, $orderItemId, $isCustomer, $store)
    {
        $orderDetails = array();
        $order = $this->_orderModel->load($orderId);
        
        $totalAmount = $order->getGrandTotal() - $order->getTaxAmount() - $order->getShippingAmount() + $order->getDiscountAmount();
        $orderDetails['order_id'] = (int) $order->getId();
        $orderDetails['order_number'] = $order->getIncrementId();
        $orderDetails['item_id'] = $orderItemId;

        //Item details
        $itemCollection = $this->_orderItemRepository->get($orderItemId);
        $simpleProduct = $this->_productModel->loadByAttribute('sku', $itemCollection->getSku());
        $categories = $simpleProduct->getCategoryIds();
        $attributes = $simpleProduct->getAttributes();
        if ($attributes) {
            $attributesArray = [];
            foreach ($attributes as $attribute) {
                $attrCode = $attribute->getAttributeCode();
                $attrId = $attribute->getAttributeId();
                if ($attribute->getIsVisibleOnFront()) {
                    $attrText = $simpleProduct->getAttributeText($attrCode);
                    $attrOption = $simpleProduct->getResource()->getAttribute($attribute);
                    $optionId = $attrOption->getSource()->getOptionId($attrText);
                    if ($attrText) {
                        $attributesArray[$attrCode]["id"] = $optionId;
                        $attributesArray[$attrCode]['name'] = $attrText;
                        $attributesArray[$attrCode]['attribute_id'] = $attrId;
                    }
                }
            }
        }
        $orderDetails['product_id'] = (int) $itemCollection->getProductId();
        $orderDetails['variant_id'] = (int) $simpleProduct->getId();
        $orderDetails['name'] = $itemCollection->getName();
        $orderDetails['quantity'] = intval($itemCollection->getQtyOrdered());
        $orderDetails['sku'] = $itemCollection->getSku();
        $orderDetails['price'] = (float) ($itemCollection->getPrice());
        $orderDetails['total'] = (float) $itemCollection->getData('row_total');
        $orderDetails['custom_design_id'] = intval($itemCollection->getCustomDesign());
        $orderDetails['images'] = $this->getProductImages($itemCollection->getProductId(), $store);
        $orderDetails['categories'] = $categories;
        $orderDetails['attributes'] = $attributesArray;

        //Customer details
        $orderDetails['customer_id'] = $order->getCustomerId();
        $orderDetails['customer_email'] = $order->getCustomerEmail();
        $orderDetails['customer_first_name'] = $order->getData('customer_firstname');
        $orderDetails['customer_last_name'] = $order->getData('customer_lastname');

        //Check if true pass the address
        if($isCustomer){
            $shippingAddress = $order->getShippingAddress();
            if (!empty($shippingAddress)) {
                $countryCode = $shippingAddress->getData('country_id');
                $country = $this->_countryFactory->create()->loadByCode($countryCode);
                $shippingStreet = $shippingAddress->getStreet();
                $orderDetails['shipping']['first_name'] = $shippingAddress->getFirstname() ? $shippingAddress->getFirstname() : '';
                $orderDetails['shipping']['last_name'] = $shippingAddress->getLastname() ? $shippingAddress->getLastname() : '';
                $orderDetails['shipping']['company'] = $shippingAddress->getCompany() ? $shippingAddress->getCompany() : '';
                if (isset($shippingStreet[0])) {
                    $orderDetails['shipping']['address_1'] = $shippingStreet[0] ? $shippingStreet[0] : '';
                }
                if (isset($shippingStreet[1])) {
                    $orderDetails['shipping']['address_2'] = $shippingStreet[1] ? $shippingStreet[1] : '';
                }
                $orderDetails['shipping']['city'] = $shippingAddress->getCity() ? $shippingAddress->getCity() : '';
                $orderDetails['shipping']['state'] = $shippingAddress->getRegion() ? $shippingAddress->getRegion() : '';
                $orderDetails['shipping']['country'] = $country->getName() ? $country->getName() : '';
                $orderDetails['shipping']['postcode'] = $shippingAddress->getPostcode() ? $shippingAddress->getPostcode() : '';
            }
            $billingAddress = $order->getBillingAddress();
            $billingStreet = $billingAddress->getStreet();
            $orderDetails['billing']['first_name'] = $billingAddress->getFirstname() ? $billingAddress->getFirstname() : '';
            $orderDetails['billing']['last_name'] = $billingAddress->getLastname() ? $billingAddress->getLastname() : '';
            $orderDetails['billing']['company'] = $billingAddress->getCompany() ? $billingAddress->getCompany() : '';
            if (isset($billingStreet[0])) {
                $orderDetails['billing']['address_1'] = $billingStreet[0] ? $billingStreet[0] : '';
            }
            if (isset($billingStreet[1])) {
                $orderDetails['billing']['address_2'] = $billingStreet[1] ? $billingStreet[1] : '';
            }
            $orderDetails['billing']['city'] = $billingAddress->getCity() ? $billingAddress->getCity() : '';
            $orderDetails['billing']['state'] = $billingAddress->getRegion() ? $billingAddress->getRegion() : '';
            $orderDetails['billing']['country'] = $country->getName() ? $country->getName() : '';
            $orderDetails['billing']['postcode'] = $billingAddress->getPostcode() ? $billingAddress->getPostcode() : '';
        }
        return json_encode($orderDetails);
    }
}
