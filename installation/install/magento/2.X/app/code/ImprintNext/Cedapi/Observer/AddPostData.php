<?php
namespace ImprintNext\Cedapi\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddPostData implements ObserverInterface{
    protected $_logger;
    protected $_request;
	
    public function __construct(
		\Psr\Log\LoggerInterface $_logger,
		\Magento\Framework\App\Request\Http $_request
    ) {
		$this->_logger = $_logger;
		$this->_request = $_request;
    }
	
    public function execute(\Magento\Framework\Event\Observer $observer){ 
		$handle = $this->_request->getFullActionName();
		if ($handle == 'checkout_cart_add') {
			$item = $observer->getProduct();
			$data['microtime'] = microtime(true);
            $item->addCustomOption('do_not_merge', serialize($data));	
		}
	}
}