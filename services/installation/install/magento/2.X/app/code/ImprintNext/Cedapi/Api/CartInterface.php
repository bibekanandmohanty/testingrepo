<?php

namespace ImprintNext\Cedapi\Api;

interface CartInterface
{
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
    public function addToCart($quoteId, $store, $customerId, $cartItemId, $customDesignId, $productsData, $action);

    /**
     * @api
     * @param int $quoteId
     * @param int $store
     * @param int $customerId
     * @return string No of cart qty.
     */
    public function getTotalCartItem($quoteId, $store, $customerId);

    /**
     * @api
     * @param int $cartItemId
     * @return string status of cart item remove.
     */
    public function removeCartItem($cartItemId);
}
