<?php
namespace ImprintNext\Cedapi\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddQuoteAfter implements ObserverInterface{
    public function execute(\Magento\Framework\Event\Observer $observer){ 
		$quote = $observer->getEvent()->getQuote();
		$expire = time() + 60 * 60 * 24 * 30;
		$quoteId = $quote->getId();
        if($quoteId > 0) setcookie("quoteId", base64_encode($quoteId), $expire, "/");
	}
}