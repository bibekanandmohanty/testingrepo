<?php
namespace ImprintNext\Cedapi\Model\Plugin;

class AbstractCart
{
    /**
     * @param \Magento\Checkout\Block\Cart\AbstractCart $subject
     * @return $result
     */
    public function afterGetItemRenderer(\Magento\Checkout\Block\Cart\AbstractCart $subject, $result)
    {
		$result->setTemplate('ImprintNext_Cedapi::cart/item/default.phtml');
		return $result;
    }

}
