<?php
namespace ImprintNext\Cedapi\Observer;

use Magento\Framework\Event\ObserverInterface;

class PredispatchCheckoutCart implements ObserverInterface
{
    protected $_objectManager;
    protected $_logger;
    protected $_checkoutSessionModel;
    protected $_request;
    protected $_messangeManager;
    protected $_storeManager;
    protected $_cartHelper;
    protected $_curl;
    protected $_quoteRepository;
    protected $_productModel;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        \Psr\Log\LoggerInterface $_logger,
        \Magento\Checkout\Model\Session $_checkoutSessionModel,
        \Magento\Framework\App\Request\Http $_request,
        \Magento\Framework\Message\ManagerInterface $_messangeManager,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Magento\Checkout\Helper\Cart $_cartHelper,
        \Magento\Framework\HTTP\Client\Curl $_curl,
        \Magento\Quote\Model\QuoteRepository $_quoteRepository,
        \Magento\Catalog\Model\Product $_productModel
    ) {
        $this->_objectManager = $_objectManager;
        $this->_logger = $_logger;
        $this->_checkoutSessionModel = $_checkoutSessionModel;
        $this->_request = $_request;
        $this->_messangeManager = $_messangeManager;
        $this->_storeManager = $_storeManager;
        $this->_cartHelper = $_cartHelper;
        $this->_curl = $_curl;
        $this->_quoteRepository = $_quoteRepository;
        $this->_productModel = $_productModel;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteId = intval($this->_request->getParam('quoteId'));
        if ($quoteId == '' || $quoteId <= 0) {
            $quoteId == 0;
        }
        if ($quoteId && $quoteId > 0) {
            try {
                $quote = $this->_quoteRepository->get($quoteId);
                if ($quote->getItemsCount()) {
                    $cartsess = $this->_objectManager->get('Magento\Checkout\Model\Session');
                    $cartsess->setQuoteId($quoteId);
                    $cartsess->setLoadInactive(true);
                    $this->_messangeManager->addSuccess('Product is successfully added in to cart.');
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_messangeManager->addError($e->getMessage());
            }
            $redirectUrl = $this->_cartHelper->getCartUrl();
            $observer->getControllerAction()->getResponse()->setRedirect($redirectUrl);
        }
        // Update Quote Item
        $quoteObj = $this->_checkoutSessionModel->getQuote();
        $cartItems = $quoteObj->getAllItems();
        if(!empty($cartItems)){
            foreach ($cartItems as $item) {
                $productId = $item->getProductId();
                $productData = $this->_productModel->load($productId);
                $customDesignId = ($productData->getTemplate_id()) ? $productData->getTemplate_id() : 0 ;
                if($item->getCustomDesign() == 0 && $customDesignId != 0){
                    $item->setCustomDesign($customDesignId);
                    $item->save();
                }
            }
            $quoteObj->collectTotals()->save();
        }
    }
}
