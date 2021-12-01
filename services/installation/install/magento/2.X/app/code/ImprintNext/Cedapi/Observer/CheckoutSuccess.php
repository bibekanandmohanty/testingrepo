<?php
namespace ImprintNext\Cedapi\Observer;
use Magento\Framework\Event\ObserverInterface;

class CheckoutSuccess implements ObserverInterface{
    protected $_objectManager;
    protected $_logger;
    protected $_salesOrderModel;
    protected $_storeManager;
    protected $_curl;
	
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $_objectManager,
		\Psr\Log\LoggerInterface $_logger,
		\Magento\Sales\Model\Order $_salesOrderModel,
		\Magento\Store\Model\StoreManagerInterface $_storeManager,
		\Magento\Framework\HTTP\Client\Curl $_curl
    ) {
        $this->_objectManager = $_objectManager;
		$this->_logger = $_logger;
		$this->_salesOrderModel = $_salesOrderModel;
		$this->_storeManager = $_storeManager;
		$this->_curl = $_curl;
    }
	
    public function execute(\Magento\Framework\Event\Observer $observer){  
		$order_id = $observer->getData('order_ids');
	    $order = $this->_salesOrderModel->load($order_id);
		//Generate order files
		$orderId = $order->getId();
        $baseUrl= $this->_storeManager->getStore()->getBaseUrl();
        $url= $baseUrl.'xetool/api/v1/orders/create-order-files/'.$orderId;
        $this->_curl->get($url);
        $response = $this->_curl->getBody();

		$this->_objectManager->get('Magento\Checkout\Model\Session')->clearQuote();
		$expire=time()+60*60*24*30; //30 days
		setcookie("quoteId", "", $expire, "/"); 
	}
}