<?php
namespace ImprintNext\Cedapi\Block\Product\View;

use Magento\Catalog\Block\Product\AbstractProduct;

class Customize extends AbstractProduct
{

    public $superAttributes = array();

    public function isCustomizeButton()
    {
        $product = $this->getProduct();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productId = $product->getId();
        $productModel = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $curlManager = $objectManager->get('\Magento\Framework\HTTP\Client\Curl');
        $baseUrl = $storeManager->getStore()->getBaseUrl();
        $storeId = $storeManager->getStore()->getStoreId();
        $xeIsDesigner = $productModel->getResource()->getAttribute("xe_is_designer")->getFrontend()->getValue($productModel);
        $url = $baseUrl.'designer/api/v1/multi-store/customize-button/'.$storeId;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        // Check show in designer and store both are active or not
        if ($xeIsDesigner = 'yes' && $result['data'] == 1) {
            return true;
        }
        return false;
    }
}
