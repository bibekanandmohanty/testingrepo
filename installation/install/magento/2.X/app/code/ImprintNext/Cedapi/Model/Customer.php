<?php

namespace ImprintNext\Cedapi\Model;

use ImprintNext\Cedapi\Api\CustomerInterface;

class Customer extends \Magento\Framework\Model\AbstractModel implements CustomerInterface
{
    protected $_customerFactory;
    protected $_countryFactory;
    protected $_customerRepository;
    protected $_addressRepository;
    protected $_orderCollectionFactory;
    protected $_objectManager;
    protected $_productModel;

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $_customerFactory,
        \Magento\Directory\Model\CountryFactory $_countryFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $_customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $_addressRepository,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollectionFactory,
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        \Magento\Catalog\Model\Product $_productModel
    ) {
        $this->_customerFactory = $_customerFactory;
        $this->_countryFactory = $_countryFactory;
        $this->_customerRepository = $_customerRepository;
        $this->_addressRepository = $_addressRepository;
        $this->_orderCollectionFactory = $_orderCollectionFactory;
        $this->_objectManager = $_objectManager;
        $this->_productModel = $_productModel;
    }

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
    public function getStoreCustomers($store, $searchstring, $page, $limit, $order, $orderby, $customerNoOrder, $fromDate, $toDate, $fetch)
    {
        $result = array();

        try {
            // Get total number of customers
            $customerTotalCollection = $this->_customerFactory->create()->getCollection()
                ->addAttributeToSelect("*")
                ->addAttributeToFilter('firstname', array('like' => '%' . $searchstring . '%'))
                ->load();
            $length = $customerTotalCollection->getSize();

            // Get customers by filters
            $customerCollection = $this->_customerFactory->create()->getCollection()
                ->addAttributeToSelect("*")
                ->addAttributeToFilter('firstname', array('like' => '%' . $searchstring . '%'))
                ->setCurPage($page)
                ->setPageSize($limit)
                ->setOrder('created_at', $order)
                ->load();

            $i = 0;
            foreach($customerCollection as $customer){
                $telephone = '';
                $company = '';

                if($customer->getIsActive()){
                    $customerRepo = $this->_customerRepository->getById($customer->getId());
                    $billingAddressId = $customerRepo->getDefaultBilling();
                    if($billingAddressId){
                        $billingAddress = $this->_addressRepository->getById($billingAddressId);
                        $telephone = $billingAddress->getTelephone();
                        $company = $billingAddress->getCompany();
                    }

                    // Get total order count by a customer
                    $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
                    $connection = $resource->getConnection();
                    $select = $connection->select()
                        ->from($resource->getTableName('sales_order'), 'COUNT(*)')
                        ->where('customer_id=?', $customer->getId());
                    $totalOrderCount = (int) $connection->fetchOne($select);

                    //Get last order of a customer
                    $lastOrder = $this->_objectManager->get('Magento\Sales\Model\Order')
                        ->getCollection()
                        ->addFieldToFilter('customer_id', $customer->getId())
                        ->setOrder('created_at','DESC')
                        ->getFirstItem();
                    if($customerNoOrder == 'true'){
                        if($totalOrderCount == 0){
                            $result[$i] = array(
                                'id' => $customer->getId(),
                                'first_name' => $customer->getFirstname(),
                                'last_name' => $customer->getLastname(),
                                'email' => $customer->getEmail(),
                                'contact_no' => $telephone,
                                'total_orders' => $totalOrderCount,
                                'last_order_id' => $lastOrder->getData('entity_id'),
                                'date_created' => $customer->getCreatedAt()
                            );
                            $i++;
                        }
                    }elseif($fetch == 'all'){
                        $result[$i] = array(
                            'id' => $customer->getId(),
                            'first_name' => $customer->getFirstname(),
                            'last_name' => $customer->getLastname(),
                            'email' => $customer->getEmail(),
                            'contact_no' => $telephone,
                            'total_orders' => $totalOrderCount,
                            'last_order_id' => $lastOrder->getData('entity_id'),
                            'date_created' => $customer->getCreatedAt()
                        );
                        $i++;
                    }else{
                        if($totalOrderCount > 0){
                            $result[$i] = array(
                                'id' => $customer->getId(),
                                'first_name' => $customer->getFirstname(),
                                'last_name' => $customer->getLastname(),
                                'email' => $customer->getEmail(),
                                'contact_no' => $telephone,
                                'total_orders' => $totalOrderCount,
                                'last_order_id' => $lastOrder->getData('entity_id'),
                                'date_created' => $customer->getCreatedAt()
                            );
                            $i++;
                        }
                    }
                }
            }
            if(count($result) < $limit){
                $length = count($result);
            }
            return json_encode(array('total_user' => $length, 'customer_list' => $result));
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode(array('total_user' => 0, 'customer_list' => $result));
        }
    }

    /**
     *
     * @api
     * @param int $store.
     * @param int $customerId.
     * @return string Customer details in a json format.
     */
    public function getStoreCustomerDetails($store, $customerId)
    {
        $result = array();
        $billingAddressArr = array();
        $shippingAddressArr = array();
        $orders = array();
        $avgOrderAmount = 0;

        try {
            $customerRepo = $this->_customerRepository->getById($customerId);

            if(!empty($customerRepo)){
                $billingAddressId = $customerRepo->getDefaultBilling();
                $shippingAddressId = $customerRepo->getDefaultShipping();
                if($billingAddressId){
                    // Get billing address details
                    $billingAddress = $this->_addressRepository->getById($billingAddressId);
                    $billingCountry = $this->_countryFactory->create()->loadByCode($billingAddress->getCountryId());
                    $contactNo = $billingAddress->getTelephone();
                    $companyName = $billingAddress->getCompany();
                    $billingAddress2 = (isset($billingAddress->getStreet()[1]) && $billingAddress->getStreet()[1] !='')? $billingAddress->getStreet()[1] :'';
                    $billingAddressArr = array(
                        'first_name' => $customerRepo->getFirstname(),
                        'last_name' => $customerRepo->getLastname(),
                        'email' => $customerRepo->getEmail(),
                        'phone' => (!empty($contactNo))? $contactNo : '',
                        'address_1' => (!empty($billingAddress->getStreet()[0]))? $billingAddress->getStreet()[0] : '',
                        'address_2' => (!empty($billingAddress2))? $billingAddress2 : '',
                        'city' => (!empty($billingAddress->getCity()))? $billingAddress->getCity() : '',
                        'state' => (!empty($billingAddress->getRegion()->getRegion()))? $billingAddress->getRegion()->getRegion() : '',
                        'postcode' => (!empty($billingAddress->getPostcode()))? $billingAddress->getPostcode() : '',
                        'country' => $billingAddress->getCountryId()
                    );
                } else {
                    $billingAddressArr = array(
                        'first_name' => $customerRepo->getFirstname(),
                        'last_name' => $customerRepo->getLastname(),
                        'email' => $customerRepo->getEmail(),
                        'phone' => '',
                        'address_1' => '',
                        'address_2' => '',
                        'city' => '',
                        'state' => '',
                        'postcode' => '',
                        'country' => ''
                    );
                }

                $shippingAddressArr = $this->getShippingAddressByCuctomerId($customerId);
                if (empty($shippingAddressArr)) {
                    $shippingAddressArr[] = array(
                        'first_name' => $customerRepo->getFirstname(),
                        'last_name' => $customerRepo->getLastname(),
                        'email' => $customerRepo->getEmail(),
                        'phone' => '',
                        'address_1' => '',
                        'address_2' => '',
                        'city' => '',
                        'state' => '',
                        'phone' => '',
                        'postcode' => '',
                        'country' => ''
                    );
                }

                // Get total order count by a customer
                $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();
                $select = $connection->select()
                    ->from($resource->getTableName('sales_order'), 'COUNT(*)')
                    ->where('customer_id=?', $customerRepo->getId());
                $totalOrderCount = (int) $connection->fetchOne($select);

                //Get last order of a customer
                $order = $this->_objectManager->get('Magento\Sales\Model\Order')
                    ->getCollection()
                    ->addFieldToFilter('customer_id', $customerRepo->getId())
                    ->setOrder('created_at','DESC')
                    ->getFirstItem();

                // Get all orders of a customer
                $collection = $this->_orderCollectionFactory->create($customerRepo->getId())
                    ->addFieldToSelect('*')
                    ->setOrder('created_at','desc');

                $totalOrderAmount = 0;
                $counter = 0;
                foreach ($collection as $value) {
                    $totalOrderAmount = $totalOrderAmount + $value->getSubTotal();
                    $orderItems = $order->getItemsCollection();
                    $lineItems = array();
                    $orderItemCounter = 0;
                    foreach ($orderItems as $item) {
                        $simpleProduct = $this->_productModel->loadByAttribute('sku', $item->getSku());
                        if (!$item->getParentItemId() && intval($item->getCustomDesign())) {
                            $lineItems[$orderItemCounter] = array(
                                'product_id' => (int) $item->getProductId(),
                                'variant_id' => (int) (!empty($simpleProduct)) ? $simpleProduct->getId() : $item->getProductId(),
                                'custom_design_id' => intval($item->getCustomDesign())
                            );
                            $orderItemCounter++;
                        }
                    }
                    $orders[$counter] = array(
                        "id" => $value->getId(),
                        "quantity" => (int) $value->getData('total_qty_ordered'),
                        "currency" => $value->getData('base_currency_code'),
                        "total_amount" => number_format($value->getSubTotal(), 2),
                        "created_date" => $value->getData('created_at'),
                        "lineItems" => $lineItems
                    );
                    $counter++;
                }

                // Calculate average order of a customer
                if($totalOrderCount)
                    $avgOrderAmount = number_format($totalOrderAmount / $totalOrderCount, 2);
                
                $result = array(
                    'id' => $customerRepo->getId(),
                    'first_name' => $customerRepo->getFirstname(),
                    'last_name' => $customerRepo->getLastname(),
                    'email' => $customerRepo->getEmail(),
                    'profile_pic' => '',
                    'phone' => (!empty($contactNo))? $contactNo : '',
                    'total_orders' => $totalOrderCount,
                    'total_order_amount' => (float) $totalOrderAmount,
                    'average_order_amount' => (float) $avgOrderAmount,
                    'last_order' => $order->getData('created_at'),
                    'last_order_id' => $order->getData('entity_id'),
                    'date_created' => $customerRepo->getCreatedAt(),
                    'billing_address' => $billingAddressArr,
                    'shipping_address' => $shippingAddressArr,
                    'orders' => $orders,
                );
            }
            return json_encode($result);
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode($result);
        }
    }

    /**
     *
     * @api
     * @return string total customer count.
     */
    public function getTotalCustomerCount()
    {
        $length = 0;
        try {
            // Get total number of customers
            $customerTotalCollection = $this->_customerFactory->create()->getCollection()
                ->addAttributeToSelect("*")
                ->load();
            $length = $customerTotalCollection->getSize();
            return json_encode(array('user_count' => $length));
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode(array('user_count' => 0));
        }
    }

    /**
     *
     * @api
     * @return string all countries.
     */
    public function getAllCountries()
    {
        $result = array();
        try {
            $countriesCollection = $this->_countryFactory->create()->getCollection()
                ->addFieldToSelect('*')
                ->addOrder('country_id', 'asc')
                ->load();
            //$length = $countriesCollection->getSize();
            $i = 0;
            foreach ($countriesCollection as $country) {
                $result[$i] = array(
                    'countries_code' => $country->getCountryId(),
                    'countries_name' => $country->getName()
                );
                $i++;
            }
            return json_encode(array('countries' => $result));
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode($result);
        }
    }

    /**
     *
     * @api
     * @param string $countryCode.
     * @return string All states list country code wise.
     */
    public function getAllStatesByCode($countryCode)
    {
        $result = array();
        try {
            $stateCollection = $this->_countryFactory->create()->setCountryId(
                $countryCode
            )->getLoadedRegionCollection();
            //$length = $countriesCollection->getSize();
            $i = 0;
            foreach ($stateCollection as $state) {
                $result[$i] = array(
                    'state_code' => $state->getCode(),
                    'state_name' => $state->getName()
                );
                $i++;
            }
            return json_encode(array('states' => $result));
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode($result);
        }
    }


     /**
     *
     * @api
     * @param int $store.
     * @param string $data.
     * @return string Customer details in a json format.
     */
    public function createCustomer($store, $data)
    {
        $response = array();
        $data = json_decode($data, true);
        $billingAddress = array(
            '0' => $data['billing_address_1'],
            '1' => $data['billing_address_2']
        );
        $shippingAddress = array(
            '0' => $data['shipping_address_1'],
            '1' => $data['shipping_address_2']
        );

        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $websiteId = $storeManager->getStore($store)->getWebsiteId();
        $customer = $this->_customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->setStoreId($store);
        $customer->loadByEmail($data['user_email']);

        if (!$customer->getId()) {
            //Save Customer
            $customer->setEmail($data['user_email']);
            $customer->setFirstname($data['first_name']);
            $customer->setLastname($data['last_name']);
            $customer->setPassword($data['user_password']);
            $customer->setConfirmation($data['user_password']);
            $customer->setPasswordCreatedAt(time());
            $customer->setForceConfirmed(true);
            $customer->save();
            //Save shipping address
            $addresss = $this->_objectManager->get('\Magento\Customer\Model\AddressFactory');
            $address = $addresss->create();
            $address->setCustomerId($customer->getId())
                    ->setFirstname($data['first_name'])
                    ->setLastname($data['last_name'])
                    ->setCountryId($data['shipping_country_code'])
                    ->setPostcode($data['shipping_postcode'])
                    ->setCity($data['shipping_city'])
                    ->setRegion($data['shipping_state_code'])
                    ->setTelephone($data['billing_phone'])
                    ->setCompany($data['company_name'])
                    ->setStreet($shippingAddress)
                    ->setIsDefaultBilling(false)
                    ->setIsDefaultShipping('1')
                    ->setSaveInAddressBook('1');
            $address->save();
            //Save billing address
            $addresss = $this->_objectManager->get('\Magento\Customer\Model\AddressFactory');
            $address = $addresss->create();
            $address->setCustomerId($customer->getId())
                    ->setFirstname($data['first_name'])
                    ->setLastname($data['last_name'])
                    ->setCountryId($data['billing_country_code'])
                    ->setPostcode($data['billing_postcode'])
                    ->setCity($data['billing_city'])
                    ->setRegion($data['billing_state_code'])
                    ->setTelephone($data['billing_phone'])
                    ->setCompany($data['company_name'])
                    ->setStreet($billingAddress)
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping(false)
                    ->setSaveInAddressBook('1');
            $address->save();
            $response = [
                'status' => 1,
                'store_customer_id' => $customer->getId(),
                'message' => 'Customer with email ' . $data['user_email'] . ' is successfully created.'
            ];
            
        } else {
            $response = [
                'status' => 0,
                'message' => 'Customer with email ' . $data['user_email'] . ' is already exists.'
            ];
        }
        return json_encode($response);
    }

    /**
     *
     * @api
     * @param int $customerId.
     * @return string Customer details in a json format.
     */
    public function getShippingAddressByCuctomerId($customerId)
    {
        $customer = $this->_customerFactory->create();
        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $websiteId = $storeManager->getWebsite()->getWebsiteId();
        $customer->setWebsiteId($websiteId);
        $customerModel = $customer->load($customerId);
 
        $customerRepo = $this->_customerRepository->getById($customerId);
        $billingAddressId = $customerRepo->getDefaultBilling();
        $shippingAddressId = $customerRepo->getDefaultShipping();

        $customerAddressData = [];
        if (!empty($customerModel->getAddresses())) {
            foreach ($customerModel->getAddresses() as $customerAddres)
            {
                if ($billingAddressId != $customerAddres['entity_id']) {
                    $shippingAddress = $this->_addressRepository->getById($customerAddres['entity_id']);
                    $shippingAddress2 = (isset($shippingAddress->getStreet()[1]) && $shippingAddress->getStreet()[1] !='')?$shippingAddress->getStreet()[1]:'';
                    $customerAddressData[] = [
                        'first_name' => $customerAddres['firstname'],
                        'last_name' => $customerAddres['lastname'],
                        'company' => $customerAddres['company'],
                        'address_1' => (!empty($shippingAddress->getStreet()[0]))? $shippingAddress->getStreet()[0] : '',
                        'address_2' => (!empty($shippingAddress2))? $shippingAddress2 : '',
                        'city' => $customerAddres['city'],
                        'postcode' => $customerAddres['postcode'],
                        'country' => $customerAddres['country_id'],
                        'state' => (!empty($shippingAddress->getRegion()->getRegionCode()))? $shippingAddress->getRegion()->getRegionCode() : '',
                        'phone' =>  $shippingAddress->getTelephone(),
                        'id' => $customerAddres['entity_id'],
                        'country_name' => $shippingAddress->getCountryId(),
                        'state_name' => $customerAddres['region'],
                        'is_default' => ($shippingAddressId == $customerAddres['entity_id']) ? 1 : 0
                    ];
                }
            }
        }
        return $customerAddressData;
    }

    /**
     *
     * @api
     * @param int $store.
     * @param string $data.
     * @return string Customer details in a json format.
     */
    public function createShippingAddress($store, $data)
    {
        $response = array();
        $data = json_decode($data, true);
        $customerRepo = $this->_customerRepository->getById($data['user_id']);
        $billingAddressId = $customerRepo->getDefaultBilling();
        
        if(!empty($customerRepo)){
            $contactNo = (isset($data['mobile_no']) && $data['mobile_no'] != '') ? $data['mobile_no'] : '0000000000';
            //Get Default Billing 
            if(!$billingAddressId) {
                //If Default billing address not added
                $billingAddress = array(
                    'firstname' => $data['first_name'],
                    'lastname' => $data['last_name'],
                    'company' => $data['company'],
                    'street' => array(
                        '0' => $data['address_1'],
                        '1' => $data['address_2']
                    ),
                    'city' => $data['city'],
                    'country_id' => $data['country'],
                    'region' => $data['state'],
                    'postcode' => $data['post_code'],
                    'telephone' => $contactNo
                );
                // set customer shipping address
                $addresss = $this->_objectManager->get('\Magento\Customer\Model\AddressFactory');
                $customBillingAddress = $addresss->create();
                $customBillingAddress->setData($billingAddress)->setCustomerId($data['user_id'])->setIsDefaultBilling('1')->setIsDefaultShipping('0')->setSaveInAddressBook('1');
                $customBillingAddress->save();
            }
            $shippingAddress = array(
                'firstname' => $data['first_name'],
                'lastname' => $data['last_name'],
                'company' => $data['company'],
                'street' => array(
                    '0' => $data['address_1'],
                    '1' => $data['address_2']
                ),
                'city' => $data['city'],
                'country_id' => $data['country'],
                'region' => $data['state'],
                'postcode' => $data['post_code'],
                'telephone' => $contactNo
            );
            // set customer shipping address
            $addresss = $this->_objectManager->get('\Magento\Customer\Model\AddressFactory');
            $customShippingAddress = $addresss->create();
            $customShippingAddress->setData($shippingAddress)->setCustomerId($data['user_id'])->setIsDefaultBilling('0')->setIsDefaultShipping('1')->setSaveInAddressBook('1');
            $customShippingAddress->save();

            $response = [
                'status' => 1,
                'message' => 'A new address added successfully.'
            ];
        } else {
            $response = [
                'status' => 0,
                'message' => 'Customer not exists in store.'
            ];
        }
        return json_encode($response);
    }

    /**
     *
     * @api
     * @param int $store.
     * @param string $data.
     * @return string Customer details in a json format.
     */
    public function updateShippingAddress($store, $data)
    {
        $response = array();
        $data = json_decode($data, true);
        $shippingAddress = array(
            '0' => $data['address_1'],
            '1' => $data['address_2']
        );
        $address = $this->_objectManager->create('Magento\Customer\Model\Address')->load($data['shipping_id']);
        $address->setCustomerId($data['user_id'])
            ->setFirstname($data['first_name'])
            ->setLastname($data['last_name'])
            ->setCountryId($data['country'])
            ->setPostcode($data['post_code'])
            ->setCity($data['city'])
            ->setRegion($data['state'])
            ->setTelephone($data['mobile_no'])
            ->setCompany($data['company'])
            ->setStreet($shippingAddress);
        $address->save();
        $response = [
            'status' => 1,
            'message' => 'Shipping address update successfully.'
        ];
        return json_encode($response);
        
    }

    /**
     *
     * @api
     * @param int $store.
     * @param string $customerIds.
     * @return string Customer details in a json format.
     */
    public function deleteCustomer($store, $customerIds)
    {
        $result = array();
        $customerIdArr = explode(',',$customerIds);
        try {
            foreach ($customerIdArr as $customerId) {
                $this->_customerRepository->deleteById($customerId);
            }
            $result = [
                'status' => 1,
                'message' => 'Customer deleted successfully.'
            ];
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode($result);
        }
        return json_encode($result);
    }

    /**
     *
     * @api
     * @param int $store.
     * @param int $customerId.
     * @param int $shipId.
     * @param int $isAddress.
     * @return string Customer details in a json format.
     */
    public function getStoreCustomerDetailsWithShipId($store, $customerId, $shipId, $isAddress)
     {
        $result = array();
        $billingAddressArr = array();
        $shippingAddressArr = array();
        try {
            $customer=$this->_customerFactory->create();
            $customerRepo = $customer->load($customerId);
            if(!empty($customerRepo->getData())){
                $result['id'] = $customerRepo->getId();
                $result['name'] = $customerRepo->getFirstname().' '.$customerRepo->getLastname();
                $result['email'] = $customerRepo->getEmail();
                if($isAddress){ 
                    $billingAddressId = $customerRepo->getDefaultBilling();
                    $shippingAddressId = $customerRepo->getDefaultShipping();
                    if($billingAddressId){
                        // Get billing address details
                        $billingAddress = $this->_addressRepository->getById($billingAddressId);
                        $billingCountry = $this->_countryFactory->create()->loadByCode($billingAddress->getCountryId());
                        $contactNo = $billingAddress->getTelephone();
                        $companyName = $billingAddress->getCompany();
                        $billingAddress2 = (isset($billingAddress->getStreet()[1]) && $billingAddress->getStreet()[1] !='')? $billingAddress->getStreet()[1] :'';
                        $billingAddressArr = array(
                            'first_name' => $customerRepo->getFirstname(),
                            'last_name' => $customerRepo->getLastname(),
                            'email' => $customerRepo->getEmail(),
                            'phone' => (!empty($contactNo))? $contactNo : '',
                            'address_1' => (!empty($billingAddress->getStreet()[0]))? $billingAddress->getStreet()[0] : '',
                            'address_2' => (!empty($billingAddress2))? $billingAddress2 : '',
                            'city' => (!empty($billingAddress->getCity()))? $billingAddress->getCity() : '',
                            'state' => (!empty($billingAddress->getRegion()->getRegion()))? $billingAddress->getRegion()->getRegion() : '',
                            'postcode' => (!empty($billingAddress->getPostcode()))? $billingAddress->getPostcode() : '',
                            'country' => $billingAddress->getCountryId(),
                            'company' => ''
                        );
                    } else {
                        $billingAddressArr = array(
                            'first_name' => $customerRepo->getFirstname(),
                            'last_name' => $customerRepo->getLastname(),
                            'email' => $customerRepo->getEmail(),
                            'phone' => '',
                            'address_1' => '',
                            'address_2' => '',
                            'city' => '',
                            'state' => '',
                            'postcode' => '',
                            'country' => '',
                            'company' => ''
                        );
                    }
                    // Get shipping address details
                    $shippingAddressArr = $this->getShippingAddressByCuctomerId($customerId);
                    $result['phone'] = (!empty($contactNo))? $contactNo : '';
                    $result['billing_address'] = $billingAddressArr;
                    $result['shipping_address'] = $shippingAddressArr;
                }
            }
            return json_encode($result);
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode($result);
        }
    }

    /**
     *
     * @api
     * @param int $store.
     * @return string All customer count in a json format.
     */
    public function getStoreCustomerCount($store)
    {
        $result = 0;
        try {
            // Get total number of customers
            $customerTotalCollection = $this->_customerFactory->create()->getCollection()
                ->addAttributeToSelect("*")
                ->load();
            $result = $customerTotalCollection->getSize();
            
            return json_encode($result);
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode(0);
        }
    }

    /**
     *
     * @api
     * @param int $store.
     * @param int $page.
     * @param int $perPage.
     * @return string All customer id list in a json format.
     */
    public function getStoreCustomersId($store, $page, $perPage)
    {
        $result = array();

        try {
            // Get customers by filters
            $customerCollection = $this->_customerFactory->create()->getCollection()
                ->addAttributeToSelect("*")
                ->setCurPage($page)
                ->setPageSize($perPage)
                ->load();

            $i = 0;
            foreach($customerCollection as $customer){
                if($customer->getIsActive()){
                    $result[$i] = array(
                        'id' => $customer->getId()
                    );
                    $i++;
                }
            }
            return json_encode($result);
        } catch (Exception $e) {
            $session->addError($e->getMessage());
            return json_encode($result);
        }
    }
}
