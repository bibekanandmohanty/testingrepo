<?php

namespace ImprintNext\Cedapi\Api;

interface CustomerInterface
{
    /**
     *
     * @api
     * @param int $store.
     * @param string $searchstring.
     * @param int $page.
     * @param int $limit.
     * @param string $order.
     * @param string $orderby.
     * @param string $customerNoOrder.
     * @param string $fromDate.
     * @param string $toDate.
     * @param string $fetch.
     * @return string All customer list in a json format.
     */
    public function getStoreCustomers($store, $searchstring, $page, $limit, $order, $orderby, $customerNoOrder, $fromDate, $toDate, $fetch);

    /**
     *
     * @api
     * @param int $store.
     * @param int $customerId.
     * @return string Customer details in a json format.
     */
    public function getStoreCustomerDetails($store, $customerId);

    /**
     *
     * @api
     * @return string total customer count.
     */
    public function getTotalCustomerCount();

    /**
     *
     * @api
     * @return string all countries.
     */
    public function getAllCountries();

    /**
     *
     * @api
     * @param string $countryCode.
     * @return string all states.
     */
    public function getAllStatesByCode($countryCode);

    /**
     *
     * @api
     * @param int $store.
     * @param string $data.
     * @return string response the created customer id in a json format.
     */
    public function createCustomer($store, $data);

    /**
     *
     * @api
     * @param int $store.
     * @param string $data.
     * @return string response the created customer id in a json format.
     */
    public function createShippingAddress($store, $data);

    /**
     *
     * @api
     * @param int $store.
     * @param string $data.
     * @return string response the created customer id in a json format.
     */
    public function updateShippingAddress($store, $data);

    /**
     *
     * @api
     * @param int $store.
     * @param string $customerIds.
     * @return string Customer details in a json format.
     */
    public function deleteCustomer($store, $customerIds);

    /**
     *
     * @api
     * @param int $store.
     * @param int $customerId.
     * @param int $shipId.
     * @param int $isAddress.
     * @return string Customer details in a json format.
     */
    public function getStoreCustomerDetailsWithShipId($store, $customerId, $shipId, $isAddress);

    /**
     *
     * @api
     * @param int $store.
     * @return string All customer count in a json format.
     */
    public function getStoreCustomerCount($store);

    /**
     *
     * @api
     * @param int $store.
     * @param int $page.
     * @param int $perPage.
     * @return string All customer id list in a json format.
     */
    public function getStoreCustomersId($store, $page, $perPage);
}
