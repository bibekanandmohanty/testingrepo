<?php

namespace ImprintNext\Cedapi\Model;

use ImprintNext\Cedapi\Api\ProductInterface;

class Product extends \Magento\Framework\Model\AbstractModel implements ProductInterface
{
    protected $_logger;
    protected $_eavConfig;
    protected $_productCollectionFactory;
    protected $_productModel;
    protected $_storeManager;
    protected $_categoryCollectionFactory;
    protected $_categoryFactory;
    protected $_categoryProductFactory;
    protected $_stockInterface;
    protected $_attributeSet;
    protected $_objectManager;
    protected $_indexerFactory;
    protected $_indexerCollectionFactory;
    protected $_productCopier;
    protected $_productFactory;
    protected $_productRepository;
    protected $_stockRegistry;
    protected $_eavSetupFactory;
    protected $_storeRepository;

    public function __construct(
        \Psr\Log\LoggerInterface $_logger,
        \Magento\Eav\Model\Config $_eavConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productCollectionFactory,
        \Magento\Catalog\Model\Product $_productModel,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $_categoryCollectionFactory,
        \Magento\Catalog\Model\Category $_categoryFactory,
        \Magento\Catalog\Model\CategoryFactory $_categoryProductFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $_stockInterface,
        \Magento\Eav\Model\Entity\Attribute\Set $_attributeSet,
        \Magento\Framework\ObjectManagerInterface $_objectManager,
        \Magento\Indexer\Model\IndexerFactory $_indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $_indexerCollectionFactory,
        \Magento\Catalog\Model\Product\Copier $_productCopier,
        \Magento\Catalog\Model\ProductFactory $_productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $_productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $_stockRegistry,
        \Magento\Eav\Setup\EavSetupFactory $_eavSetupFactory,
        \Magento\Store\Api\StoreRepositoryInterface $_storeRepository
    ) {
        $this->_logger = $_logger;
        $this->_eavConfig = $_eavConfig;
        $this->_productCollectionFactory = $_productCollectionFactory;
        $this->_productModel = $_productModel;
        $this->_stockInterface = $_stockInterface;
        $this->_categoryCollectionFactory = $_categoryCollectionFactory;
        $this->_storeManager = $_storeManager;
        $this->_categoryFactory = $_categoryFactory;
        $this->_categoryProductFactory = $_categoryProductFactory;
        $this->_attributeSet = $_attributeSet;
        $this->_objectManager = $_objectManager;
        $this->_indexerFactory = $_indexerFactory;
        $this->_indexerCollectionFactory = $_indexerCollectionFactory;
        $this->_productCopier = $_productCopier;
        $this->_productFactory = $_productFactory;
        $this->_productRepository = $_productRepository;
        $this->_stockRegistry = $_stockRegistry;
        $this->_eavSetupFactory = $_eavSetupFactory;
        $this->_storeRepository = $_storeRepository;
    }

    /**
     *
     * @api To fetch all products details.
     * @param int $filters.
     * @param int $categoryid.
     * @param string $searchstring.
     * @param int $store.
     * @param int $range.
     * @param int $offset.
     * @param int $limit.
     * @param int $isPredecorated.
     * @param int $isCatalog.
     * @param string $sku.
     * @param string $order.
     * @param string $orderby.
     * @return string The all products in a json format.
     */
    public function getProducts($filters, $categoryid, $searchstring, $store, $range, $offset, $limit, $isPredecorated, $isCatalog, $sku, $order, $orderby)
    {
        $category = $this->_categoryProductFactory->create()->load($categoryid);
        $price = 0;
        $collection = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addStoreFilter($store)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->setPageSize($limit)
            ->addCategoryFilter($category)
            ->addAttributeToFilter('name', array('like' => '%' . $searchstring . '%'))
            ->setCurPage($offset);
        //Filter by Predecorated product
        if(!empty($isPredecorated) && $isPredecorated > 0) {
            $collection->addAttributeToFilter('xe_is_template', 1);
        }
        //Filter by catalog product
        if(!empty($isCatalog) && $isCatalog > 0) {
            $collection->addAttributeToFilter('is_catalog', 1);
        }
        //Filter by SKU
        if(!empty($sku)) {
            $collection->addAttributeToFilter('sku', array('like' => '%' . $sku . '%'));
        }
        //Sort order
        if(!empty($order)){
            $collection->setOrder('sku', $order);
        }

        $collection->load();

        $pages = $collection->getLastPageNumber();
        $length = 0;
        $products = array();

        if (!empty($collection) && $offset <= $pages) {
            $length = $collection->getSize();
            $counter = 0;
            foreach ($collection as $productData) {
                $variantId = '';
                $isSoldOut = false;
                if ($productData->getTypeId() == 'configurable') {
                    $simpleProductColl = $productData->getTypeInstance()->getUsedProducts($productData);
                    foreach ($simpleProductColl as $productColl) {
                        $isSoldOut = false;
                        $price = number_format($productColl->getPrice(), 2);
                        $variantId = $productColl->getId();
                        $variantQty = $this->_stockInterface->getStockQty($variantId, $productData->getStore()->getWebsiteId());
                        if($variantQty == 0 || $variantQty == null){
                            $isSoldOut = true;
                        }
                    }
                } else {
                    $price = number_format($productData->getPrice(), 2);
                    $variantId = $productData->getId();
                    $variantQty = $this->_stockInterface->getStockQty($variantId, $productData->getStore()->getWebsiteId());
                    if($variantQty == 0 || $variantQty == null){
                        $isSoldOut = true;
                    }
                }
                $productimages = array();
                $i=0;
                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productData->getId());
                $galleryImages = $product->getMediaGalleryImages();
                foreach($galleryImages as $image) {
                    $productimages[$i] = $image->getUrl();
                    $i++;
                }
                $products[$counter] = array(
                    'id' => $productData->getId(),
                    'variation_id' => $variantId,
                    'name' => $productData->getName(),
                    'type' => ($productData->getTypeId() == 'configurable') ? 'variable': $productData->getTypeId(),
                    'sku' => $productData->getSku(),
                    'price' => (float) $price,
                    'image' => $productimages,
                    'stock' => $variantQty,
                    'is_sold_out' => $isSoldOut,
                    'custom_design_id' => $productData->getTemplateId(),
                    'is_redesign' => $productData->getXeIsDesigner(),
                    'is_decorated_product' => $productData->getXeIsTemplate()
                );
                $counter++;
            }
        }
        return json_encode(array('data' => $products, 'records' => $length));
    }

    /**
     *
     * @api To fetch a single product details by a product id.
     * @param int $productId.
     * @param int $configProductId.
     * @param int $minimalData.
     * @param int $store.
     * @return string The products details in a json format.
     */
    public function getProductById($productId, $configProductId, $minimalData, $store)
    {
        $product = $this->_productRepository->getById($productId, false, $store);
        $result = array();
        $variantId = '';
        $price = 0;
        $tier = array();
        //Fetching data by product type
        if ($product->getTypeId() == 'configurable') {
            $collection = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($collection as $productColl) {
                $price = $product->getFinalPrice();
                $variantId = $productColl->getId();
                //Fetch Tier price
                $productFinalPrice = $productColl->getFinalPrice();
                $tierPrices = $productColl->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
                //For Tier price
                if (is_array($tierPrices)) {
                    foreach ($tierPrices as $k => $tierPrice) {
                        $tier[$k]['quantity'] = (int) $tierPrice['price_qty'];
                        $tier[$k]['percentage'] = round(100 - $tierPrice['website_price'] / $productFinalPrice * 100);
                        $tier[$k]['price'] = number_format($tierPrice['website_price'], 2);
                    }
                }
            }
        } else {
            $price = $product->getPrice();
            $variantId = $product->getId();
            //Fetch Tier price
            $productFinalPrice = $product->getFinalPrice();
            $tierPrices = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
            //For Tier price
            if (is_array($tierPrices)) {
                foreach ($tierPrices as $k => $tierPrice) {
                    $tier[$k]['quantity'] = (int) $tierPrice['price_qty'];
                    $tier[$k]['percentage'] = round(100 - $tierPrice['website_price'] / $productFinalPrice * 100);
                    $tier[$k]['price'] = number_format($tierPrice['website_price'], 2);
                }
            }
        }

        $galleryImages = array();
        $productGallery = $this->_productModel->load($product->getId());
        $productimages = $productGallery->getMediaGalleryImages();
        $imageCounter=0;

        foreach($productimages as $productimage) {
            $galleryImages[$imageCounter]['src'] = $productimage['url'];
            $galleryImages[$imageCounter]['thumbnail'] = $productimage['url'];
            $imageCounter++;
        }

        $CproductModel = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($configProductId); 
        $attributesArray = [];
        $categories = array();
        if(sizeof($product->getCategoryIds()) > 0) {
            $i=0;
            foreach ($product->getCategoryIds() as $category) {
                $categoryFactory = $this->_categoryFactory->load($category);
                if($categoryFactory->getId() != 2){
                    $categories[$i]['id'] = $categoryFactory->getId();
                    $categories[$i]['name'] = $categoryFactory->getName();
                    $categories[$i]['parent_id'] = ($categoryFactory->getParentId() == 2)? 0 : $categoryFactory->getParentId();
                    $i++;
                }
            }
        }
        if($minimalData){
            //Send minimal product data
            $attributes = $product->getAttributes();
            if ($attributes) {
                foreach ($attributes as $attribute) {
                    $attrCode = $attribute->getAttributeCode();
                    $attrId = $attribute->getAttributeId();
                    if ($attribute->getIsVisibleOnFront()) {
                        $attrText = $product->getAttributeText($attrCode);
                        $attrOption = $product->getResource()->getAttribute($attribute);
                        $optionId = $attrOption->getSource()->getOptionId($attrText);
                        if ($attrText) {
                            $attributesArray[$attrCode]["id"] = $optionId;
                            $attributesArray[$attrCode]['name'] = $attrText;
                            $attributesArray[$attrCode]['attribute_id'] = $attrId;
                        }
                    }
                }
            }
            $result = array(
               'name' => $CproductModel->getName(),
               'price' => (float) $price,
               'tier_prices' => $tier,
               'images' => $galleryImages,
               'categories' => $categories,
               'attributes' =>  $attributesArray
            );
        }else{
            //Send all product Data
            $attributesArray = json_decode($this->getAllVariantsByProduct($product->getId(), $store), true);
            $qty = $this->_stockInterface->getStockQty($variantId, $product->getStore()->getWebsiteId());
            $result = array(
                   'id' => $product->getId(),
                   'name' => $CproductModel->getName(),
                   'sku' => $product->getSku(),
                   'type' => ($product->getTypeId() == 'configurable') ? 'variable': $product->getTypeId(),
                   'variant_id' => $variantId,
                   'description' => $product->getDescription(),
                   'price' => (float) $price,
                   'tier_prices' => $tier,
                   'stock_quantity' => $qty,
                   'images' => $galleryImages,
                   'categories' => $categories,
                   'attributes' =>  $attributesArray
            );
        }
        return json_encode(array('data' => $result));
    }

    /**
     *
     * @api To fetch all categories.
     * @param string $order.
     * @param string $orderby.
     * @param string $name.
     * @param int $store.
     * @return string The all category in a json format.
     */
    public function getCategories($order, $orderby, $name, $store)
    {
        $categories = array();
        $rootCategoryId = $this->_storeManager->getStore($store)->getRootCategoryId();
        $collection = $this->_categoryCollectionFactory->create();

        $collection->addAttributeToSelect('*')
            ->addIsActiveFilter(true)
            ->addAttributeToFilter('path', array('like' => "1/{$rootCategoryId}/%"))
            ->addAttributeToFilter('name', array('like' => '%' . $name . '%'))
            ->setOrder($orderby, $order);

        foreach ($collection as $category) {
            $categories[] = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'parent_id' => ($category->getParentId() == 2)? 0 : $category->getParentId()
            );
        }
        return json_encode($categories);
    }

    /**
     *
     * @api To fetch products available color variants by product id.
     * @param int $productId.
     * @param int $store.
     * @param string $attribute.
     * @return string color variants of a product in a json format.
     */
    public function getColorVariantsByProduct($productId, $store, $attribute)
    {
        $result = array();
        $inventory = array();
        $temp = array();
        $product = $this->_productModel->load($productId);
        $simpleProducts = $product->getTypeInstance()->getUsedProducts($product);

        foreach ($simpleProducts as $child) {
            $ids[] = $child->getId();
        }

        $simpleCollection = $this->_productCollectionFactory->create()
            ->addIdFilter($ids)
            ->addAttributeToSelect('*')
            ->addStoreFilter($store)
            ->groupByAttribute($attribute)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

        foreach ($simpleCollection as $child) {
            $qty = $this->_stockInterface->getStockQty($child->getId(), $child->getStore()->getWebsiteId());
            $version = $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
            if(strpos($version, '2.3') !== false){
                $salableQty = $this->_objectManager->get('Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku')->execute($child->getSku());
                $qty = $salableQty[0]['qty'];
            }
            $stockObject = $this->_objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($child->getId());
            $inventory['stock'] = $qty;
            $inventory['min_quantity'] = $stockObject->getMinSaleQty();
            $inventory['max_quantity'] = $stockObject->getMaxSaleQty();
            $inventory['quantity_increments'] = $stockObject->getData('qty_increments');
            $galleryImages = array();
            $productGallery = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($child->getId());
            $productimages = $productGallery->getMediaGalleryImages();
            $j=0;
            foreach($productimages as $productimage) {
                $galleryImages[$j]['image']['src'] = $productimage['url'];
                $galleryImages[$j]['image']['thumbnail'] = $productimage['url'];
                $j++;
            }
            $colorAttr = $child->getResource()->getAttribute($attribute);
            $colorId = $colorAttr->getSource()->getOptionId($child->getAttributeText($attribute));
            if (!in_array($colorId, $temp) && $colorId) {
                $result[] = array(
                    'id' => $colorId,
                    'name' => $child->getAttributeText($attribute),
                    'variant_id' => $child->getId(),
                    'inventory' => $inventory,
                    'price' => $child->getPrice(),
                    'sides' => $galleryImages
                );
            }
            $temp[] = $colorId;
        }
        // Check if color attribute not exist then pass the first attribute details.
        if(empty($result)){
            $productAttributes = $product->getAttributes();
            foreach ($productAttributes as $productAttribute) {
                if ($productAttribute->getIsVisibleOnFront()) {
                    $attrCode = $productAttribute->getAttributeCode();
                }
            }
            $simpleCollection = $this->_productCollectionFactory->create()
            ->addIdFilter($ids)
            ->addAttributeToSelect('*')
            ->addStoreFilter($store)
            ->groupByAttribute($attrCode)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

            foreach ($simpleCollection as $child) {
                $qty = $this->_stockInterface->getStockQty($child->getId(), $child->getStore()->getWebsiteId());
                $version = $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
                if(strpos($version, '2.3') !== false){
                    $salableQty = $this->_objectManager->get('Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku')->execute($child->getSku());
                    $qty = $salableQty[0]['qty'];
                }
                $stockObject = $this->_objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($child->getId());
                $inventory['stock'] = $qty;
                $inventory['min_quantity'] = $stockObject->getMinSaleQty();
                $inventory['max_quantity'] = $stockObject->getMaxSaleQty();
                $inventory['quantity_increments'] = $stockObject->getData('qty_increments');
                $galleryImages = array();
                $productGallery = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($child->getId());
                $productimages = $productGallery->getMediaGalleryImages();
                $j=0;
                foreach($productimages as $productimage) {
                    $galleryImages[$j]['image']['src'] = $productimage['url'];
                    $galleryImages[$j]['image']['thumbnail'] = $productimage['url'];
                    $j++;
                }
                $colorAttr = $child->getResource()->getAttribute($attrCode);
                $colorId = $colorAttr->getSource()->getOptionId($child->getAttributeText($attrCode));
                if (!in_array($colorId, $temp) && $colorId) {
                    $result[] = array(
                        'id' => $colorId,
                        'name' => $child->getAttributeText($attrCode),
                        'variant_id' => $child->getId(),
                        'inventory' => $inventory,
                        'price' => $child->getPrice(),
                        'sides' => $galleryImages
                    );
                }
                $temp[] = $colorId;
            }
        }
        return json_encode($result);
    }

    /**
     *
     * @api To fetch products all variants by product id.
     * @param int $productId.
     * @param int $store.
     * @return string all variants of a product in a json format.
     */
    public function getAllVariantsByProduct($productId, $store)
    {
        $result = array();
        $product = $this->_productModel->load($productId);
        $productTypeInstance = $this->_objectManager->get('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
        $productAttributeOptions = $productTypeInstance->getConfigurableAttributesAsArray($product);
        $resultCounter = 0;

        foreach ($productAttributeOptions as $key => $value) {
            $tmp_option = $value['values'];
            if(count($tmp_option) > 0)
            {
                $options = array();
                $optionCounter = 0;
                foreach ($tmp_option as $tmp) 
                {
                    $options[$optionCounter]['id'] = $tmp['value_index'];
                    $options[$optionCounter]['name'] = $tmp['label'];
                    $optionCounter++;
                }
                $result[$resultCounter] = array(
                    'id' => $key,
                    'name' => $value['attribute_code'],
                    'options' => $options
                );
                $resultCounter++;
            }
        }
        return json_encode($result);
    }

    /**
     *
     * @api To fetch all store attributes.
     * @param int $store.
     * @param string $type.
     * @return string all attributes in a json format.
     */
    public function getAttributes($store, $type)
    {
        $result = array();
        $collection = $this->_objectManager->create(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection::class);
        $collection->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4);
        $attrAll = $collection->load()->getItems();
        $resultCounter = 0;

        foreach ($attrAll as $key => $value) {
            $attrData = $value->getData();
            if($type == 'select'){
                if ($attrData['is_user_defined'] && ($attrData['attribute_code'] != 'merchant_center_category') && ($attrData['frontend_input'] == 'select')) {
                    $result[$resultCounter]['id'] = $attrData['attribute_id'];
                    $result[$resultCounter]['name'] = $attrData['attribute_code'];
                    $resultCounter++;
                }
            }else{
                if ($attrData['is_user_defined'] && ($attrData['attribute_code'] != 'merchant_center_category') && ($attrData['frontend_input'] == 'select') || ($attrData['frontend_input'] == 'checkbox')) {
                    $result[$resultCounter]['id'] = $attrData['attribute_id'];
                    $result[$resultCounter]['name'] = $attrData['frontend_label'];
                    $result[$resultCounter]['type'] = $attrData['frontend_input'];
                    $result[$resultCounter]['slug'] = $attrData['attribute_code'];
                    $result[$resultCounter]['terms'] = [];
                    //Get attribute options from attribute code
                    $attribute = $this->_eavConfig->getAttribute('catalog_product', $attrData['attribute_code']);
                    $optarr = array();
                    if ($attribute->usesSource()) {
                        $j = 0;
                        $optarr = $attribute->getSource()->getAllOptions();
                        foreach ($optarr as $optionKey => $option) {
                            if(!empty($option['value'] && !empty($option['label']))){
                                $result[$resultCounter]['terms'][$j]['id'] = $option['value'];
                                $result[$resultCounter]['terms'][$j]['name'] = $option['label'];
                                $j++;
                            }
                        }
                    }
                    $resultCounter++;
                }
            }
        }
        return json_encode($result);
    }

    /**
     *
     * @api Check duplicate product name or sku exist or not.
     * @param string $sku
     * @param string $name
     * @param int $store
     * @return string product id in json format.
     */
    public function checkDuplicateNameAndSku($sku, $name, $store)
    {
        //Check by SKU
        $dataSku = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')->addFieldToFilter('sku', array('in' => $sku))->getData();
        $existSku = array();

        if (!empty($dataSku)) {
            foreach ($dataSku as $skuValue) {
                $existSku[] = $skuValue['entity_id'];
            }
        }

        if(!empty($existSku)){
            return json_encode($existSku);
        }
        //Check by Name
        $dataName = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')->addFieldToFilter('name', array('in' => $name))->getData();
        $existName = array();

        if (!empty($dataName)) {
            foreach ($dataName as $nameValue) {
                $existName[] = $nameValue['entity_id'];
            }
        }
        if(!empty($existName)){
            return json_encode($existName);
        }
        return json_encode($existSku);
    }

    /**
     *
     * @api Create a pre-decorated simple product.
     * @param int $store.
     * @param string $data.
     * @return string response the created product id in a json format.
     */
    public function createPredecoSimpleProduct($store, $data)
    {
        $data = json_decode($data, true);
        extract($data);
        $response = array();
        $mediaAttribute = array('thumbnail', 'small_image', 'image', ' swatch_image');
        foreach ($data['attributes'] as $attrKey => $attrValue) {
            $options[] = $attrValue['attributeOption'];
        }
        $combinations = $this->variantCombinations($options);
        $productModel = $this->_productModel->load($parentProductId);
        if ($productModel->getTypeId() == 'configurable') {
            $simpleProductColl = $productModel->getTypeInstance()->getUsedProducts($productModel);
            $finalCombination = array();
            foreach ($simpleProductColl as $productColl) {
                $productAttributes = $productColl->getAttributes();
                $attributeCombination = array();
                foreach ($productAttributes as $attribute) {
                    $attrCode = $attribute->getAttributeCode();
                    if ($attribute->getIsVisibleOnFront()) {
                        $attrText = $productColl->getAttributeText($attrCode);
                        $attr = $productColl->getResource()->getAttribute($attrCode);
                        if ($attr->usesSource()) {
                            $optionId = $attr->getSource()->getOptionId($attrText);
                        }
                        $attributeCombination[] = $optionId;
                    }
                }
                if(in_array($attributeCombination, $combinations)){
                    $productId = $productColl->getId();
                    $productModel = $this->_productModel->load($productId);
                }
            }
        }

        // Creating a duplicate product from the product id
        $duplicated = $this->_productCopier->copy($productModel);

        // Re-loading the duplicated product to set it's SKU and change other information
        $duplicated = $duplicated->load($duplicated->getId());
        $duplicated->setSku($sku)
            ->setStatus(1)
            ->setName($name)
            ->setTypeId('simple')
            ->setVisibility(4)
            ->setPrice($regularPrice)
            ->setDescription($description)
            ->setShortDescription($shortDescription)
            ->setXeIsDesigner($isRedesign)
            ->setXeIsTemplate($isPredecorated)
            ->setTemplateId($predecoratedId)
            ->setCategoryIds($categories)
            ->setMediaGallery(array());

        $duplicated->save();

        // Update inventory of newly created product
        $stockItem = $this->_stockRegistry->getStockItemBySku($sku);
        $stockItem->setQty($stockQuantity);
        $this->_stockRegistry->updateStockItemBySku($sku, $stockItem);

        // Removing existing Images
        $loadedProduct = $this->_productFactory->create();
        $existingMediaGalleryEntries = $loadedProduct->load($duplicated->getId())->getMediaGalleryEntries();
        foreach ($existingMediaGalleryEntries as $key => $entry) {
            unset($existingMediaGalleryEntries[$key]);
        }
        $loadedProduct->setMediaGalleryEntries($existingMediaGalleryEntries);
        $this->_productRepository->save($loadedProduct);

        //Adding new images
        $updateProductImg = $this->_productModel->load($duplicated->getId());
        if (!empty($images)) {
            $count = 0;
            foreach ($images as $productImg):
                $imgUrl = $this->saveImage($productImg, $this->_objectManager);
                if ($count == 0):
                    $updateProductImg->addImageToMediaGallery($imgUrl, $mediaAttribute, false, false);
                else:
                    $updateProductImg->addImageToMediaGallery($imgUrl, null, false, false);
                endif;
                $count++;
            endforeach;
        }
        $updateProductImg->save();

        // Preparing result
        $response['id'] = $duplicated->getId();

        return json_encode($response);
    }

    /**
     *
     * @api Create a pre-decorated configurable product.
     * @param int $store.
     * @param string $data.
     * @return string response in a json format.
     */
    public function createPredecoConfigProduct($store, $data)
    {
        $data = json_decode($data, true);
        $attribute_set_name = $data['attributeSet'];
        $attributeSetId = $this->_attributeSet->load($attribute_set_name, 'attribute_set_name')->getAttributeSetId();
        $data['attributeSetId'] = $attributeSetId;
        $data['attributeOptionIds'] = array();
        $data['attributeCode'] = array();

        foreach ($data['attributes'] as $attrKey => $attrValue) {
            array_push($data['attributeOptionIds'], $attrValue['attributeId']);
            array_push($data['attributeCode'], $attrValue['attributeCode']);
            $options[] = $attrValue['attributeOption'];
        }
        $combinations = $this->variantCombinations($options);

        $mediaAttribute = array('thumbnail', 'small_image', 'image', ' swatch_image');
        $data['mediaAttribute'] = $mediaAttribute;
        $filepath = $this->_storeManager->getStore()->getBaseUrl('media') . 'import';
        $data['filepath'] = $filepath;
        $data['storeId'] = $store;
        $simpleProductName = $data['name'];
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        $data['websiteId'] = array($websiteId);
        if (empty($data['categories'])) {
            $rootcatId = $this->_storeManager->getStore($store)->getRootCategoryId();
            $data['categories'] = array($rootcatId);
        }
        $sku = $data['sku'];
        $categoryIds = $data['categories'];

        //Create new configure product
        $parentProductObj = $this->_productModel->load($data['parentProductId']);
        $data['weight'] = $parentProductObj->getWeight();
        $confId = $data['productId'];
        if(empty($confId) || $confId == 0){
            $confId = $this->createConfigurableProduct($data, $data['images']);
        }
        
        $childIds = array();
        foreach ($combinations as $combination) {
            $simpleProduct = $this->_objectManager->create('Magento\Catalog\Model\Product');
            $rand = rand(1, 9999);
            try {
                $simpleProduct
                    //Set all simple product data
                    ->setStoreId($data['storeId'])
                    ->setWebsiteIds($data['websiteId'])
                    ->setAttributeSetId($data['attributeSetId'])
                    ->setTypeId('simple')
                    ->setCreatedAt(strtotime('now'))
                    ->setSku($data['sku'].$rand)
                    ->setWeight($data['weight'])
                    ->setStatus(1)
                    ->setTaxClassId(0)
                    ->setVisibility(1)
                    ->setNewsFromDate('')
                    ->setNewsToDate('')
                    ->setPrice($data['regularPrice'])
                    ->setSpecialPrice('')
                    ->setSpecialFromDate('')
                    ->setSpecialToDate('')
                    ->setMetaTitle('metatitle')
                    ->setMetaKeyword('metakeyword')
                    ->setMetaDescription('metadescription')
                    ->setDescription($data['description'])
                    ->setShortDescription($data['shortDescription'])
                    ->setXeIsTemplate($data['isPredecorated'])
                    ->setTemplateId($data['predecoratedId']);
                $simpleProductName = $data['name'];
                foreach ($combination as $key => $value) {
                    $setOptions = "set" . ucfirst($data['attributeCode'][$key]);
                    $attribute = $this->_eavConfig->getAttribute('catalog_product', $data['attributeCode'][$key]);
                    $simpleProductName =  $simpleProductName .'-'. $attribute->getSource()->getOptionText($value);
                    $simpleProduct->$setOptions($value);
                }

                $simpleProduct->setName($simpleProductName);
                $simpleProduct->setStockData(array(
                    'use_config_manage_stock' => 0,
                    'manage_stock' => 1,
                    'is_in_stock' => 1,
                    'qty' => $data['stockQuantity'],
                    ))
                    ->setCategoryIds($data['categories']);
                // if (!empty($oldSimpleId)) {
                //     $img = array();
                //     foreach ($oldSimpleId->getMediaGalleryImages() as $image) {
                //         $imgData = $image->getUrl();
                //         $parts = parse_url($imgData);
                //         $str = $parts['path'];
                //         $str1 = explode('media', $str);
                //         $dir = $this->_storeManager->getStore()->getBaseUrl('media');
                //         $img[] = $dir . $str1[1];
                //     }
                //     $count = 0;
                //     foreach ($img as $image):
                //         $imgUrl = $this->saveImage($image, $this->_objectManager);
                //         if ($count == 0):
                //             $simpleProduct->addImageToMediaGallery($imgUrl, $mediaAttribute, false, false);
                //         else:
                //             $simpleProduct->addImageToMediaGallery($imgUrl, null, false, false);
                //         endif;
                //         $count++;
                //     endforeach;
                // }
                $simpleProduct->save();
                $childIds[] = $simpleProduct->getId();
            } catch (Exception $e) {
                $this->_logger->info($e->getMessage());
                $this->_logger->debug($e->getMessage());
            }
        }
    
        $response = array();
        if ($confId && !empty($childIds)) {
            $assigned_splist = $this->fetchSimpleProductOfConfigurable($confId);
            $assigned_splist = json_decode($assigned_splist);
            if (is_array($assigned_splist) && !empty($assigned_splist)) {
                $childIds = array_values(array_merge($assigned_splist, $childIds));
            }
            //associate new simple product with new configure product
            $result = $this->associateSimpleToConfigurableProduct($confId, $childIds, $data['attributes'], $attributeSetId, $data['attributeOptionIds'], $sku, $categoryIds);
            if($result){
                $response['id'] = $confId;
                $response['vids'] = $childIds;
            }
        }
        $indexerCollection = $this->_indexerCollectionFactory->create();
        $indexerIds = $indexerCollection->getAllIds();
        foreach ($indexerIds as $indexerId) {
            $indexer = $this->_indexerFactory->create();
            $indexer->load($indexerId);
            $indexer->reindexAll();
        }
        return json_encode($response);
    }

    private function variantCombinations($arrays, $i = 0) {
        if (!isset($arrays[$i])) {
            return array();
        }
        if ($i == count($arrays) - 1) {
            return $arrays[$i];
        }

        // get combinations from subsequent arrays
        $tmp = $this->variantCombinations($arrays, $i + 1);
        $combinations = array();
        // concat each array from tmp with each element from $arrays[$i]
        foreach ($arrays[$i] as $v) {
            foreach ($tmp as $t) {
                $combinations[] = is_array($t) ? 
                  array_merge(array($v), $t) :
                  array($v, $t);
            }
        }
        return $combinations;
    }

    /**
     *
     * @api Create configurable product.
     * @param string $data
     * @param string $configFile
     * @return int created config id.
     */
    public function createConfigurableProduct($data, $configFile)
    {
        extract($data);
        $confProductId = 0;
        $checkExistProduct = $this->_productModel->getIdBySku($data['sku']);

        if (empty($checkExistProduct)) {
            $configProduct = $this->_objectManager->create('Magento\Catalog\Model\Product');

            try {
                $configProduct
                    ->setStoreId($storeId)
                    ->setWebsiteIds($websiteId)
                    ->setAttributeSetId($attributeSetId)
                    ->setTypeId('configurable')
                    ->setCreatedAt(strtotime('now'))
                    ->setSku($sku)
                    ->setName($name)
                    ->setWeight($weight)
                    ->setStatus(1)
                    ->setTaxClassId(0)
                    ->setVisibility(4)
                    ->setXeIsDesigner($isRedesign)
                    ->setNewsFromDate('')
                    ->setNewsToDate('')
                    ->setPrice($regularPrice)
                    ->setSpecialPrice('')
                    ->setSpecialFromDate('')
                    ->setSpecialToDate('')
                    ->setMetaTitle($name)
                    ->setMetaKeyword('metakeyword')
                    ->setMetaDescription('metadescription')
                    ->setDescription($description)
                    ->setShortDescription($shortDescription)
                    ->setXeIsTemplate($isPredecorated)
                    ->setTemplateId($predecoratedId);
                if (!empty($configFile)) {
                    $count = 0;
                    foreach ($configFile as $image):
                        $imgUrl = $this->saveImage($image, $this->_objectManager);
                        if ($count == 0):
                            $configProduct->addImageToMediaGallery($imgUrl, $mediaAttribute, false, false);
                        else:
                            $configProduct->addImageToMediaGallery($imgUrl, null, false, false);
                        endif;
                        $count++;
                    endforeach;
                }
                $configProduct->setStockData(array(
                    'use_config_manage_stock' => 0,
                    'manage_stock' => 1,
                    'is_in_stock' => 1,
                    'qty' => $stockQuantity,
                    ))
                    ->setCategoryIds($categories);
                $configProduct->save();
                $createdConfigId = $configProduct->getId();
                $createdProductObj = $configProduct->load($createdConfigId);
                $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->setUsedProductAttributeIds($attributeOptionIds, $createdProductObj);
                $createdProductObj->setCanSaveConfigurableAttributes(true);
                $createdProductObj->save();
                $confProductId = $createdProductObj->getId();
            } catch (Exception $e) {
                $this->_logger->info($e->getMessage());
                $this->_logger->debug($e->getMessage());
            }
        } else {return 'Duplicate sku';}
        return $confProductId;
    }

    /**
     *
     * @api To fetch available simple product by a configurable product id.
     * @param int $confId
     * @return int already associated id.
     */
    public function fetchSimpleProductOfConfigurable($confId)
    {
        $parent = $this->_productModel->load($confId);
        $childProducts = $parent->getTypeInstance()->getChildrenIds($confId, true);

        foreach ($childProducts as $key => $value) {
            $childProduct = $value;
        }

        $childProduct = array_values($childProduct);
        $childProduct = json_encode($childProduct);
        $childProduct = preg_replace('/["]/', '', $childProduct);
        return $childProduct;
    }

    /**
     *
     * @api Associate simple to configurable product.
     * @param int $confId.
     * @param int $childIds.
     * @param string $attributes.
     * @param int $attributeSetId.
     * @param string $attributeOptionIds.
     * @param string $sku.
     * @param string $categoryIds.
     * @return int $already associated id.
     */
    public function associateSimpleToConfigurableProduct($confId, $childIds, $attributes, $attributeSetId, $attributeOptionIds, $sku, $categoryIds)
    {
        $configProduct = $this->_productModel->load($confId);

        $simpleProducts = $this->_productCollectionFactory->create()
            ->addIdFilter($childIds)
            ->addAttributeToSelect('price');

        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->setCanSaveCustomOptions(true);
        $configProduct->getTypeInstance()->setUsedProductAttributeIds($attributeOptionIds, $configProduct);
        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray($configProduct);
        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->setConfigurableAttributesData($configurableAttributesData);
        $configurableProductsData = array();
        $variants = array();

        foreach ($simpleProducts as $i => $simple) {
            foreach ($attributes as $key => $value) {
                $productData = array(
                    'label' => $simple->getAttributeText($value['attributeCode']),
                    'attribute_id' => $value['attributeId'],
                    'value_index' => (int) $simple->getColor(),
                    'is_percent' => 0,
                    'pricing_value' => $simple->getPrice(),
                );
                $configurableProductsData[$simple->getId()] = $productData;
                $configurableAttributesData[$key]['values'][] = $productData;
            }
        }

        $configProduct->setConfigurableProductsData($configurableProductsData);

        /* Associate simple product to configurable*/
        $attributeModel = $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute');
        $position = 0;
        foreach ($attributeOptionIds as $attributeId) {
            $data = array('attribute_id' => $attributeId, 'product_id' => $confId, 'position' => $position);
            $position++;
            $attributeModel->setData($data);
        }
        $configProduct->setAffectConfigurableProductAttributes($attributeSetId);
        $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->setUsedProductAttributeIds($attributeOptionIds, $configProduct);
        $configProduct->setNewVariationsAttributeSetId($attributeSetId);
        $configProduct->setAssociatedProductIds($childIds);
        $configProduct->setCanSaveConfigurableAttributes(true);
        $configProduct->save();

        //Update Config product's categories
        $CategoryLinkRepository =  $this->_objectManager->get('\Magento\Catalog\Api\CategoryLinkManagementInterface');
        $CategoryLinkRepository->assignProductToCategories($sku, $categoryIds);
        //End

        return $confId;
    }

    /**
     *
     * @api Save the uploaded image to media directory.
     * @param string $confId
     * @return string $filepath.
     */
    public function saveImage($img, $objectManager)
    {
        $imageFilename = basename($img);
        $image_type = substr(strrchr($imageFilename, "."), 1); //find the image extension
        $filename = md5($img . strtotime('now')) . '.' . $image_type;
        $mediaDir = $objectManager->get('Magento\Framework\App\Filesystem\DirectoryList')->getPath('media');
        $filepath = $mediaDir . '/' . $filename; //path for temp storage folder: pub/media
        file_put_contents($filepath, file_get_contents(trim($img))); //store the image from external url to the temp storage folder
        return $filepath;
    }

    /**
     *
     * @api To fetch total products count.
     * @param int $store.
     * @return string The total products count in a json format.
     */
    public function totalProductCount($store)
    {
        $length = 0;

        $collection = $this->_productCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addStoreFilter($store)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->load();

        if (!empty($collection)) {
            $length = $collection->getSize();
        }
        return json_encode(array('total' => $length));
    }

    /**
     *
     * @api
     * @param int $productId.
     * @param int $store.
     * @param int $simpleProductId.
     * @param string $color.
     * @param string $size.
     * @return string size and quantity in a json format.
     */
    public function getSizeAndQuantity($productId, $store, $simpleProductId, $color, $size)
    {
        $variant = array();
        $product = $this->_productRepository->getById($productId, false, $store);
        $collection = $product->getTypeInstance()->getUsedProducts($product);
        $childProduct = $this->_productModel->load($simpleProductId);
        $variantColor = $childProduct->getAttributeText($color);
        $checkSizeId = array();

        foreach ($collection as $productColl) {
            if ($productColl->getAttributeText($color) == $variantColor) {
                $colorText = $productColl->getAttributeText($color);
                $sizeText = $productColl->getAttributeText($size);
                $attr = $productColl->getResource()->getAttribute($color);
                $attr1 = $productColl->getResource()->getAttribute($size);

                if ($attr->usesSource()) {
                    $color_id = $attr->getSource()->getOptionId($colorText);
                    $size_id = $attr1->getSource()->getOptionId($sizeText);
                }

                $productFinalPrice = $productColl->getFinalPrice();
                $tierPrices = $productColl->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
                $tier = array();
                //For Tier price
                if (is_array($tierPrices)) {
                    foreach ($tierPrices as $k => $price) {
                        $tier[$k]['tierQty'] = (int) $price['price_qty'];
                        $tier[$k]['percentage'] = round(100 - $price['website_price'] / $productFinalPrice * 100);
                        $tier[$k]['tierPrice'] = number_format($price['website_price'], 2);
                    }
                }

                if (!in_array($size_id, $checkSizeId)) {
                    $attributes = $productColl->getAttributes();
                    $extraAttr = array();
                    //For Attributes
                    foreach ($attributes as $attribute) {
                        $attrCode = $attribute->getAttributeCode();
                        $attrId = $attribute->getAttributeId();
                        if ($attribute->getIsVisibleOnFront()) {
                            $attr = $productColl->getResource()->getAttribute($attrCode);
                            $attrText = $productColl->getAttributeText($attrCode);
                            if ($attrText) {
                                $extraAttr[$attrCode] = $attrText;
                                $extraAttr[$attrCode . "_id"] = $attrId;
                            }
                        }
                    }

                    $productStockObj = $this->_objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productColl->getId());
                    $qty = $productStockObj->getQty();
                    $version = $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
                    //For store 2.3.X
                    if(strpos($version, '2.3') !== false){
                        $salableQty = $this->_objectManager->get('Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku')->execute($productColl->getSku());
                        $qty = $salableQty[0]['qty'];
                    }

                    $minimumQuantity = $productStockObj->getMinSaleQty();
                    $maximumQuantity = $productStockObj->getMaxSaleQty();
                    $quantityIncreaments = $productStockObj->getData('qty_increments');
                    $inventory = array();
                    $inventory['stock'] = (int) $qty;
                    $inventory['min_quantity'] = (int) $minimumQuantity;
                    $inventory['max_quantity'] = (int) $maximumQuantity;
                    $inventory['quantity_increments'] = (int) $quantityIncreaments;

                    $variant[] = array(
                        'variant_id' => $productColl->getId(),
                        'inventory' => $inventory,
                        'price' => number_format($productColl->getPrice(), 2),
                        'tier_prices' => $tier,
                        'attributes' => $extraAttr,
                    );
                    $checkSizeId[] = $size_id;
                }
            }
        }
        $result = $variant;
        return json_encode($result);
    }

    /**
     * Get color array
     *
     * @api
     * @param string $color.
     * @return string color in a json format.
     */
    public function getColorArr($color)
    {
        $result = array();
        $attribute = $this->_eavConfig->getAttribute('catalog_product', $color);
        $colorId = $attribute->getId();
        if ($attribute->usesSource()) {
            $allColorOption = $attribute->getSource()->getAllOptions();
            $i=0;
            foreach ($allColorOption as $colorKey => $colorValue) {
                if(!empty($colorValue['value'])){
                    $result[$i]['id'] = $colorValue['value'];
                    $result[$i]['name'] = $colorValue['label'];
                    $i++;
                }
            }
        }
        return json_encode(array('colorId' => $colorId, 'data' => $result));
    }

    /**
     * Add a new option into the color attribue
     * 
     * @param int $colorAttrId.
     * @param string $colorAttrName.
     * @param string $colorOptionName.
     * @return string options id in a json format.
     */
    public function addColorOption($colorAttrId, $colorAttrName, $colorOptionName)
    {
        $result = array();
        $option['attribute_id'] = $colorAttrId;
        $option['value']['attribute_value'][0] = $colorOptionName;
        $eavSetup = $this->_eavSetupFactory->create();
        $eavSetup->addAttributeOption($option);
        $attribute = $this->_eavConfig->getAttribute('catalog_product', $colorAttrName);
        $source = $attribute->getSource();
        $options = $source->getAllOptions();
        foreach ($options as $optionValue) {
            if ($colorOptionName == $optionValue["label"]) {
                $value = $optionValue["value"];
            }
        }
        $result['id'] = $value;
        return json_encode($result);
    }

    /**
     * Get all products filter by categories
     *
     * @api
     * @param int $store.
     * @return string all products in a json format.
     */
    public function getAllProductByCategory($store)
    {
        $result = array();
        $resultCounter = 0;
        $rootCategoryId = $this->_storeManager->getStore($store)->getRootCategoryId();
        $categoryCollection = $this->_categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect('*')
            ->addIsActiveFilter(true)
            ->addAttributeToFilter('path', array('like' => "1/{$rootCategoryId}/%"));

        foreach ($categoryCollection as $category) {
            $productCollection = $this->_productCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addStoreFilter($store)
                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->setPageSize(7)
                ->addCategoryFilter($category)
                ->load();
            $products = array();
            if (!empty($productCollection)) {
                $productCounter = 0;
                foreach ($productCollection as $productData) {
                    if ($productData->getTypeId() == 'configurable') {
                        $simpleProductColl = $productData->getTypeInstance()->getUsedProducts($productData);
                        foreach ($simpleProductColl as $productColl) {
                            $price = number_format($productColl->getPrice(), 2);
                        }
                    } else {
                        $price = number_format($productData->getPrice(), 2);
                    }
                    $productimages = array();
                    $imageCounter=0;
                    $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($productData->getId());
                    $galleryImages = $product->getMediaGalleryImages();
                    foreach($galleryImages as $image) {
                        $productimages[$imageCounter] = $image->getUrl();
                        $imageCounter++;
                    }
                    
                    $products[$productCounter] = array(
                        'id' => $productData->getId(),
                        'name' => $productData->getName(),
                        'type' => ($productData->getTypeId() == 'configurable') ? 'variable': $productData->getTypeId(),
                        'sku' => $productData->getSku(),
                        'price' => (float) $price,
                        'image' => $productimages
                    );
                    $productCounter++;
                }
            }
            $result[$resultCounter] = array(
                'id' => $category->getId(),
                'name' => $category->getName(),
                'products' => $products
            );
            $resultCounter++;
        }
        return json_encode(array('categories' => $result));
    }

    /**
     *
     * @api
     * @param int $store.
     * @param int $productId.
     * @param int $variationId.
     * @param string $attribute.
     * @return string variant details in a json format.
     */
    public function getMultiAttributeVariantDetails($store, $productId, $variationId, $attribute)
    {
        $result = array();
        $product = $this->_productRepository->getById($productId, false, $store);
        $collection = $product->getTypeInstance()->getUsedProducts($product);
        foreach ($collection as $productColl) {
            //Fetch Tier price
            $productFinalPrice = $productColl->getFinalPrice();
            $tierPrices = $productColl->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
            $tier = array();
            //For Tier price
            if (is_array($tierPrices)) {
                foreach ($tierPrices as $k => $price) {
                    $tier[$k]['quantity'] = (int) $price['price_qty'];
                    $tier[$k]['percentage'] = round(100 - $price['website_price'] / $productFinalPrice * 100);
                    $tier[$k]['price'] = number_format($price['website_price'], 2);
                }
            }
            //Fetch inventory details
            $productStockObj = $this->_objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface')->getStockItem($productColl->getId());
            $qty = $productStockObj->getQty();
            $version = $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
            //For store 2.3.X
            if(strpos($version, '2.3') !== false){
                $salableQty = $this->_objectManager->get('Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku')->execute($productColl->getSku());
                $qty = $salableQty[0]['qty'];
            }
            $minimumQuantity = $productStockObj->getMinSaleQty();
            $maximumQuantity = $productStockObj->getMaxSaleQty();
            $quantityIncreaments = $productStockObj->getData('qty_increments');
            //Fetch all product attributes
            $attributes = $productColl->getAttributes();
            $extraAttr = array();
            $options = array();
            //For Attributes
            foreach ($attributes as $attribute) {
                $attrCode = $attribute->getAttributeCode();
                $attrId = $attribute->getAttributeId();
                if ($attribute->getIsVisibleOnFront()) {
                    $attrText = $productColl->getAttributeText($attrCode);
                    if ($attrText) {
                        $extraAttr[] = array(
                            'id' => $attrId,
                            'name' => $attrCode,
                            'option' => $attrText
                        );
                        $options[$attrCode] = $attrText;
                        $options[$attrCode . "_id"] = $attrId;
                    }
                }
            }
            $result[] = array(
                'id' => $productColl->getId(),
                'price' => number_format($productColl->getPrice(), 2),
                'tier_prices' => $tier,
                'stock_quantity' => (int) $qty,
                'min_quantity' => (int) $minimumQuantity,
                'max_quantity' => (int) $maximumQuantity,
                'quantity_increments' => (int) $quantityIncreaments,
                'attributes' => $extraAttr,
                'options' => $options
            );
        }
        return json_encode($result);
    }

    /**
     *
     * @api To fetch all store attributes.
     * @param int $store.
     * @param int $productId.
     * @param string $type.
     * @return string all attributes in a json format.
     */
    public function getAttributesByProductId($store, $productId, $type)
    {
        $result = array();
        $product = $this->_productModel->load($productId);
        $attributes = $product->getAttributes();
        $i = 0;
        foreach ($attributes as $attribute) {
            $attrCode = $attribute->getAttributeCode();
            $attrId = $attribute->getAttributeId();
            $attrLabel = $attribute->getStoreLabel();
            if ($attribute->getIsVisibleOnFront()) {
                $attrText = $product->getAttributeText($attrCode);
                $result[$i]["id"] = $attrId;
                $result[$i]['name'] = $attrLabel;
                
                $simpleCollection = $product->getTypeInstance()->getUsedProducts($product);
                //Get all attribute of old configure product
                if (!empty($simpleCollection)) {
                    $k = 0;
                    $temp = array();
                    foreach ($simpleCollection as $simple) {
                        $attr = $simple->getResource()->getAttribute($attrCode);
                        if ($attr->usesSource()) {
                            $attrId = $attr->getSource()->getOptionId($simple->getAttributeText($attrCode));
                            if (!in_array($attrId, $temp)) {
                                $optarr[$k]['id'] = $attrId;
                                $optarr[$k]['name'] = $attr->getSource()->getOptionText($attrId);
                                $k++;
                            }
                            $temp[] = $attrId;
                        }
                    }
                }
                $result[$i]['terms'] = $optarr;
                $i++;
            }
        }
        return json_encode($result);
    }

    /**
     * Add a new product from catalog
     * 
     * @param string $data.
     * @param string $price.
     * @param int $productId.
     * @return string product id in a json format.
     */
    public function addCatalogProductToStore($data, $price, $productId)
    {
        if($productId){
            $oldProduct = $this->_productRepository->getById($productId);
            $this->_productRepository->delete($oldProduct); 
        }
        $regularPrice = $price;
        $result = array();
        $attributeOptionIds = array();
        $data = json_decode($data, true);
        extract($data);
        $mediaAttribute = array('thumbnail', 'small_image', 'image', ' swatch_image');
        $checkExistProduct = $this->_productModel->getIdBySku($data['sku']);
        $storeId = $this->_storeManager->getStore()->getId();
        $websiteId[] = $this->_storeManager->getStore()->getWebsiteId();
        $attribute_set_name = 'ImprintNext';
        $attributeSetId = $this->_attributeSet->load($attribute_set_name, 'attribute_set_name')->getAttributeSetId();
        // Add Simple Product
        if(!empty($variations) && empty($checkExistProduct)){
            $simpleProductArr = array();
            $product = $this->_productFactory->create();
            foreach ($variations as $variation) {
                $simpleProduct = $this->_objectManager->create('Magento\Catalog\Model\Product');

                try {
                    $simpleProduct
                        //Set all simple product data
                        ->setStoreId($storeId)
                        ->setWebsiteIds($websiteId)
                        ->setAttributeSetId($attributeSetId)
                        ->setTypeId('simple')
                        ->setCreatedAt(strtotime('now'))
                        ->setSku($variation['sku'])
                        ->setName($variation['style_name'])
                        ->setWeight($variation['unit_weight'])
                        ->setStatus(1)
                        ->setTaxClassId(0)
                        ->setVisibility(1)
                        ->setIsCatalog(1)
                        ->setNewsFromDate('')
                        ->setNewsToDate('')
                        ->setPrice($regularPrice)
                        ->setSpecialPrice('')
                        ->setSpecialFromDate('')
                        ->setSpecialToDate('')
                        ->setMetaTitle('metatitle')
                        ->setMetaKeyword('metakeyword')
                        ->setMetaDescription('metadescription')
                        ->setDescription($description)
                        ->setShortDescription($description);
                        foreach ($variation['attributes'] as $key => $value) {
                            $isAttributeExist = $product->getResource()->getAttribute($key);
                            $optionId = '';
                            if ($isAttributeExist && $isAttributeExist->usesSource()) {
                                $optionId = $isAttributeExist->getSource()->getOptionId($value);
                            }
                            $setAttr = "set" . ucfirst($key);
                            $simpleProduct->$setAttr($optionId);
                        }
                    $simpleProduct->setStockData(array(
                        'use_config_manage_stock' => 0,
                        'manage_stock' => 1,
                        'is_in_stock' => 1,
                        'qty' => $variation['quantity'],
                        ));
                    $simpleProduct->setCategoryIds($categories);
                    if(!empty($variation['image_path'])){
                        $count = 0;
                        if($variation['image_path'][0]){
                            //foreach ($variation['image_path'] as $image):
                                $imgUrl = $this->saveImage($variation['image_path'][0], $this->_objectManager);
                                if ($count == 0):
                                    $simpleProduct->addImageToMediaGallery($imgUrl, $mediaAttribute, false, false);
                                else:
                                    $simpleProduct->addImageToMediaGallery($imgUrl, null, false, false);
                                endif;
                                $count++;
                            //endforeach;
                        }
                    }
                    $simpleProduct->save();
                    $simpleProductArr[] = (int) $simpleProduct->getId();
                } catch (Exception $e) {
                    $this->_logger->info($e->getMessage());
                    $this->_logger->debug($e->getMessage());
                }
            }
        }
        // Add Config product and associate with Simple product
        if (empty($checkExistProduct) && $type == 'variable') {
            $configProduct = $this->_objectManager->create('Magento\Catalog\Model\Product');
            foreach ($attributes as $key => $attrValue) {
                $attribute = $this->_eavConfig->getAttribute('catalog_product', $attrValue['name']);
                $attributeId = $attribute->getId();
                $attributes[$key]['attributeCode'] = strtolower($attrValue['name']);
                $attributes[$key]['attributeId'] = $attributeId;
                array_push($attributeOptionIds, $attributeId);
            }

            try {
                $configProduct
                    ->setStoreId($storeId)
                    ->setWebsiteIds($websiteId)
                    ->setAttributeSetId($attributeSetId)
                    ->setTypeId('configurable')
                    ->setCreatedAt(strtotime('now'))
                    ->setSku($sku)
                    ->setName($name)
                    ->setWeight($variations[0]['unit_weight'])
                    ->setStatus(1)
                    ->setTaxClassId(0)
                    ->setVisibility(4)
                    ->setXeIsDesigner(1)
                    ->setIsCatalog(1)
                    ->setNewsFromDate('')
                    ->setNewsToDate('')
                    ->setPrice($regularPrice)
                    ->setSpecialPrice('')
                    ->setSpecialFromDate('')
                    ->setSpecialToDate('')
                    ->setMetaTitle($name)
                    ->setMetaKeyword('metakeyword')
                    ->setMetaDescription('metadescription')
                    ->setDescription($description)
                    ->setShortDescription($description)
                    ->setXeIsTemplate(0)
                    ->setTemplateId(0);

                if (!empty($images)) {
                    $count = 0;
                    $imgUrl = $this->saveImage($images['src'], $this->_objectManager);
                    if ($count == 0):
                        $configProduct->addImageToMediaGallery($imgUrl, $mediaAttribute, false, false);
                    else:
                        $configProduct->addImageToMediaGallery($imgUrl, null, false, false);
                    endif;
                    $count++;
                }
                $configProduct->setStockData(array(
                    'use_config_manage_stock' => 0,
                    'manage_stock' => 1,
                    'is_in_stock' => 1,
                    'qty' => 1000,
                    ));
                $configProduct->setCategoryIds($categories);
                $configProduct->save();
                $createdConfigId = $configProduct->getId();
                $createdProductObj = $configProduct->load($createdConfigId);
                $this->_objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->setUsedProductAttributeIds($attributeOptionIds, $createdProductObj);
                $createdProductObj->setCanSaveConfigurableAttributes(true);
                $createdProductObj->save();
                $confProductId = $createdProductObj->getId();
                //associate new simple product with new configure product
                $categoryIds = $categories;
                $result = $this->associateSimpleToConfigurableProduct($confProductId, $simpleProductArr, $attributes, $attributeSetId, $attributeOptionIds, $sku, $categoryIds);
                if($result){
                    return json_encode($confProductId);
                }
            } catch (Exception $e) {
                $this->_logger->info($e->getMessage());
                $this->_logger->debug($e->getMessage());
            }
            return json_encode('Product not saved');
        } else {return 'Duplicate sku';}
    }

    /**
     * Get all Store
     *
     * @api
     * @return string stores in a json format.
     */
    public function getAllStores()
    {
        $result = array();
        $storeList = $this->_storeRepository->getList();
        $i=0;
        foreach ($storeList as $store) {
            if($store->getId()){
                $result[$i]['store_id'] = $store->getId();
                $result[$i]['store_url'] = $this->_storeManager->getStore($store->getId())->getBaseUrl();
                $result[$i]['is_active'] = $store->isActive();
                $i++; 
            }
        }
        return json_encode($result);
    }

    /**
     * Add new product category to Store
     *
     * @param string $catName.
     * @param int $catId.
     * @param int $store.
     * @return string category id in a json format.
     */
    public function createStoreProductCatagories($catName, $catId, $store)
    {
        $result = array();
        $rootCategoryId = ($catId > 0)? $catId : $this->_storeManager->getStore($store)->getRootCategoryId();
        $parentCategory = $this->_objectManager
                      ->create('Magento\Catalog\Model\Category')
                      ->load($rootCategoryId);
        $categoryObj = $this->_objectManager
                ->create('Magento\Catalog\Model\Category');
        // Check category exist or not
        $cateData = $categoryObj->getCollection()
                    ->addAttributeToFilter('name', $catName)
                    ->getFirstItem();
        if(empty($cateData->getId()))
        {
            $categoryObj->setPath($parentCategory->getPath())
                ->setParentId($rootCategoryId)
                ->setName($catName)
                ->setIsActive(true);
            $categoryObj->save();
            $result = $categoryObj->getId();
        }
        return json_encode($result);
    }

    /**
     * Remove product category from Store
     *
     * @param int $catId.
     * @param int $store.
     * @return string category id in a json format.
     */
    public function removeStoreProductCatagories($catId, $store)
    {
        $result = false;
        $rootCategoryId = $this->_storeManager->getStore($store)->getRootCategoryId();
        if($catId > $rootCategoryId){
            $categoryObj = $this->_objectManager
                ->create('Magento\Catalog\Model\Category')
                ->load($catId);
            $categoryObj->delete();
            $result = true;
        }
        return json_encode($result);
    }

    /**
     * Get available variants by product id from Store
     *
     * @param int $productId.
     * @param int $store.
     * @return string Variants details in a json format.
     */
    public function getVariants($productId, $store)
    {
        $result = array();
        $product = $this->_productModel->load($productId);
        $simpleProducts = $product->getTypeInstance()->getUsedProducts($product);
        foreach ($simpleProducts as $child) {
            $ids[] = $child->getId();
        }
        $simpleCollection = $this->_productCollectionFactory->create()
            ->addIdFilter($ids)
            ->addAttributeToSelect('*')
            ->addStoreFilter($store)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
        if (!empty($simpleCollection)) {
            foreach ($simpleCollection as $child) {
                $result[] = array(
                    'id' => $child->getId(),
                    'title' => $child->getName(),
                    'price' => $child->getFinalPrice()
                );
            }
        }
        return json_encode($result);
    }
}
