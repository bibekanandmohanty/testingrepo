<?php

namespace ImprintNext\Cedapi\Model;

use ImprintNext\Cedapi\Api\CartInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Cart extends \Magento\Framework\Model\AbstractModel implements CartInterface
{
    protected $_logger;
    protected $_productModel;
    protected $_quote;
    protected $_storeManager;
    protected $_productRepository;
    protected $_cartHelper;
    protected $_cart;
    protected $_quoteItem;
    protected $_objectManager;

    public function __construct(
        \Psr\Log\LoggerInterface $_logger,
        \Magento\Catalog\Model\Product $_productModel,
        \Magento\Quote\Model\QuoteFactory $_quote,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        ProductRepositoryInterface $_productRepository,
        \Magento\Checkout\Helper\Cart $_cartHelper,
        \Magento\Checkout\Model\Cart $_cart,
        \Magento\Quote\Model\Quote\Item $_quoteItem,
        \Magento\Framework\ObjectManagerInterface $_objectManager
    ) {
        $this->_logger = $_logger;
        $this->_productModel = $_productModel;
        $this->_quote = $_quote;
        $this->_storeManager = $_storeManager;
        $this->_productRepository = $_productRepository;
        $this->_cartHelper = $_cartHelper;
        $this->_cart = $_cart;
        $this->_quoteItem = $_quoteItem;
        $this->_objectManager = $_objectManager;
    }

    /**
     *
     * @api
     * @param int $quoteId.
     * @param int $store.
     * @param int $customerId.
     * @param int $cartItemId.
     * @param int $customDesignId.
     * @param string $productsData.
     * @param string $action.
     * @return string The all products in a json format.
     */
    public function addToCart($quoteId, $store, $customerId, $cartItemId, $customDesignId, $productsData, $action)
    {
        $productsData = json_decode($productsData, true);
        if (!$store) {
            return json_encode(array('is_Fault' => 1, 'faultMessage' => 'Invalid Store'));
        }
        foreach ($productsData as $key => $value) {
            $sepArrProduct = $productsData[$key];
            $result = $this->add($quoteId, $sepArrProduct, $store, $customDesignId);
        }
        return $result;
    }

    /**
     * @param  $quoteId
     * @param  $productsData
     * @param  $$customDesignId
     * @param  $store
     * @return bool
     */
    public function add($quoteId, $productsData, $store, $customDesignId)
    {
        $productsData = $this->prepareProductsData($productsData);
        if (empty($productsData)) {
            return json_encode(array('is_Fault' => 1, 'faultMessage' => 'Invalid product data'));
        }
        $errors = array();
        $productByItem = $this->_productRepository->getById($productsData['product_id'], false, null, true);
        if ($productByItem->getData('type_id') == 'configurable') {
            $configProd = $this->_productRepository->getById($productByItem->getData('entity_id'));
            $super_attrs = array();
            $super_attrs_code = array();
            $configurableAttributeCollection = $configProd->getTypeInstance()->getConfigurableAttributes($configProd);
            foreach ($configurableAttributeCollection as $attribute) {
                $super_attrs[$attribute->getProductAttribute()->getAttributeCode()] = $attribute->getProductAttribute()->getId();
                $super_attrs_code[] = $attribute->getProductAttribute()->getAttributeCode();
            }
            $super_attribute_values = array();
            foreach ($super_attrs as $supercode => $superid) {
                $supervalue = $this->setOrAddOptionAttribute($supercode, $productsData['options'][$supercode]);
                if (!$supervalue) {
                    return json_encode(array('is_Fault' => 1, 'faultMessage' => 'Please specify correct options of product. The option ' . $supercode . ' with value ' . $productsData['options'][$supercode] . ' not exist.'));
                }
                $super_attribute_values[$superid] = $supervalue;
            }
            if (count($super_attribute_values) != count($super_attrs)) {
                return json_encode(array('is_Fault' => 1, 'faultMessage' => 'Please specify correct options.'));
            }
            $productsData["super_attribute"] = $super_attribute_values;
        }
        $productRequest = new \Magento\Framework\DataObject($productsData);
        $productRequest->setItem($productRequest);
        $data['microtime'] = microtime(true);
        $productByItem->addCustomOption('do_not_merge', serialize($data));
        try {
            if (!$quoteId){
                $quoteItem = $this->_cart->getQuote()->addProduct($productByItem, $productRequest);
            } else{
                $quote = $this->_quote->create()->load($quoteId);
                $result = $quote->addProduct($productByItem, $productRequest);
                $quoteItem = $quote->getItemByProduct($productByItem);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $errors[] = $e->getMessage();
        }
        $quoteItem->setCustomDesign($customDesignId);
        $product = $quoteItem->getProduct();
        $quoteItem->addOption($product->getCustomOption('do_not_merge'));
        $tierPrice = 0;
        $tierPrices = array();
        $quoteProduct = $this->_productRepository->getById($productsData['variant_id'], false, $store);
        $tierPrices = $quoteProduct->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        if (is_array($tierPrices)) {
            foreach ($tierPrices as $price) {
                if ($productsData['total_qty'] > 0) {
                    if ($productsData['total_qty'] >= (int) $price['price_qty']) {
                        $tierPrice = number_format($price['website_price'], 2);
                    }
                } else {
                    if ($productsData['qty'] >= (int) $price['price_qty']) {
                        $tierPrice = number_format($price['website_price'], 2);
                    }
                }
            }
        }
        if (!empty($quoteProduct)) {
            if(isset($productsData['is_variable_decoration']) && $productsData['is_variable_decoration']){
                $customPrice = $productsData['added_price'];
            }else{
                if ($tierPrice == 0) {
                    $customPrice = $quoteProduct->getPrice() + $productsData['added_price'];
                } else {
                    $customPrice = $tierPrice + $productsData['added_price'];
                }
            }
            $quoteItem->setCustomPrice($customPrice);
            $quoteItem->setOriginalCustomPrice($customPrice);
        }
        if ($productsData['qty'] > 0) {
            $quoteItem->setQty($productsData['qty']);
        }
        if (!empty($errors)) {
            return json_encode(array('is_Fault' => 1, 'faultMessage' => implode(PHP_EOL, $errors)));
        }
        try {
            if (!$quoteId){
                $this->_cart->save();
                $quoteId = $this->_cart->getQuote()->getId();
            }else{
                $quote->collectTotals();
                $quote->save();
                $quoteId = $quote->getId();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return json_encode(array('is_Fault' => 1, 'faultMessage' => $e->getMessage()));
        }
        $url = $this->_storeManager->getStore($store)->getBaseUrl().'checkout/cart/';
        return json_encode(array('is_Fault' => 0, 'quoteId' => $quoteId, 'checkoutURL' => $url));
    }

    /**
     * Base preparation of product data
     *
     * @param mixed $data
     * @return null|array
     */
    protected function prepareProductsData($data)
    {
        return is_array($data) ? $data : null;
    }

    /**
     * Retrieve attribute option value
     *
     * @param array $attribute
     * @param string $attrValue
     * @return array
     */
    protected function setOrAddOptionAttribute($attribute, $attrValue)
    {
        $options = $this->_productModel->getResource()
            ->getAttribute($attribute)
            ->getSource()
            ->getAllOptions(false);
        $value_exists = false;
        foreach ($options as $option) {
            if ($option['label'] == $attrValue) {
                $value_exists = true;
                return $option['value'];
                break;
            }
        }
        return false;
    }

    /**
     * @api
     * @param int $quoteId
     * @param int $store
     * @param int $customerId
     * @return string No of cart qty.
     */
    public function getTotalCartItem($quoteId, $store, $customerId)
    {
        if ($customerId && $customerId > 0) {
            $quoteCollection = $this->_quote->create()->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('store_id', $store)
                ->addOrder('updated_at');
            $quote = $quoteCollection->getFirstItem();
            $quoteId = (int) $quote->getId();
        }
        if ($quoteId > 0) {
            $quote = $this->_quote->create()->load($quoteId);
            $itemQty = (int) $quote->getItemsQty();
        } else {
            $itemQty = 0;
        }
        $url = $this->_cartHelper->getCartUrl();
        $url= ($quoteId > 0)? $url.'?quoteId='.$quoteId : $url;
        return json_encode(array('is_Fault' => 0, 'totalCartItem' => $itemQty, 'checkoutURL' => $url), JSON_UNESCAPED_SLASHES);
    }

    /**
     * @api
     * @param int $cartItemId
     * @return string status of cart item remove.
     */
    public function removeCartItem($cartItemId)
    {
        $result = 'success';
        if($cartItemId){
            $quoteItem =  $this->_quoteItem->load($cartItemId);
            $quoteItem->delete();
        }else{
            $result = 'failure';
        }
        return json_encode($result);
    }
}
