<?php

namespace ImprintNext\Cedapi\Api;

interface OrderInterface
{
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
     * @return string The all products in a json format.
     */
    public function getOrders($store, $search, $page, $per_page, $after, $before, $order, $orderby, $customize, $customerId);
    /**
     *
     * @api
     * @param int $orderId.
     * @param int $minimalData.
     * @param int $isPurchaseOrder.
     * @param int $store.
     * @return string The order details in a json format.
     */
    public function getOrderDetails($orderId, $minimalData, $isPurchaseOrder, $store);
    /**
     *
     * @api
     * @param int $orderId.
     * @param int $store.
     * @return string The order log details in a json format.
     */
    public function getOrderlogByOrderId($orderId, $store);
    /**
     *
     * @api
     * @param int $store.
     * @return string The order all statuses in a json format.
     */
    public function getAllOrderStatuses($store);
    /**
     *
     * @api
     * @param int $orderId.
     * @param string $orderStatus.
     * @return string success or failure in a json format.
     */
    public function updateOrderStatusByOrderId($orderId, $orderStatus);
    /**
     *
     * @api
     * @param string $orderData.
     * @return string success or failure in a json format.
     */
    public function placeOrderFromQuotation($orderData);
    /**
     *
     * @api
     * @param int $orderId.
     * @param int $orderItemId.
     * @param int $isCustomer.
     * @param int $store.
     * @return string The order details in a json format.
     */
    public function getOrderLineItemDetails($orderId, $orderItemId, $isCustomer, $store);
}
