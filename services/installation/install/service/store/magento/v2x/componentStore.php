<?php
use Magento\Framework\App\Bootstrap;

$docAbsPath = getcwd();
if(strpos($_SERVER['DOCUMENT_ROOT'], '/pub') !== false && strpos($_SERVER['DOCUMENT_ROOT'], '/public_html') == false){
    require_once $docAbsPath . '/../../../../app/bootstrap.php';
}else{
    require_once $docAbsPath . '/../../../app/bootstrap.php';
}

class StoreComponent
{
	/*
	- Name : objectManagerInstance
	- it will create a object manager instance for magento 2.x
	- Return object
	 */
	private function objectManagerInstance()
	{
	    $bootstrap = Bootstrap::create(BP, $_SERVER);
	    $objectManager = $bootstrap->getObjectManager();
	    $appState = $objectManager->get("Magento\Framework\App\State");
	    $appState->setAreaCode('frontend');
	    return $objectManager;
	}

	/*
	- Name : CreateStoreCredential
	- it will create a user access token to communicate with store
	- Return access token key
	 */
	protected function CreateStoreCredential($data)
	{
		$result = [];
	    if (isset($data) && isset($data['type'])) {
		    extract($data);
		    $objectManager = $this->objectManagerInstance();
		    $storeVersion = $objectManager->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
		    if ($type == 'n') {
		        $integrationExists = $objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($name, 'name')->getData();
		        if (empty($integrationExists)) {
		            $integrationData = array(
		                'name' => $name,
		                'email' => $email,
		                'status' => '1',
		                'endpoint' => XEPATH,
		                'setup_type' => '0',
		            );
		            try {
		                // Code to create Integration
		                $integrationFactory = $objectManager->get('Magento\Integration\Model\IntegrationFactory')->create();
		                $integration = $integrationFactory->setData($integrationData);
		                $integration->save();
		                $integrationId = $integration->getId();
		                $consumerName = 'Integration' . $integrationId;
		                // Code to create consumer
		                $oauthService = $objectManager->get('Magento\Integration\Model\OauthService');
		                $consumer = $oauthService->createConsumer(['name' => $consumerName]);
		                $consumerId = $consumer->getId();
		                $integration->setConsumerId($consumer->getId());
		                $integration->save();
		                // Code to grant permission
		                $authrizeService = $objectManager->get('Magento\Integration\Model\AuthorizationService');
		                $authrizeService->grantAllPermissions($integrationId);
		                // Code to authorize
		                $token = $objectManager->get('Magento\Integration\Model\Oauth\Token');
		                $uri = $token->createVerifierToken($consumerId);
		                $token->setType('access');
		                $token->save();
		                $accessToken = $token->getToken();
		                $msg = "";
		                $status = 1;
		            } catch (Exception $e) {
		                $msg = $e->getMessage();
		                $objectManager->get('Psr\Log\LoggerInterface')->debug($msg);
		                $status = 0;
		            }
		        } else {
		        	$msg = "";
			        $status = 1;
			        $consumerId = $objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($integrationExists['integration_id'])->getConsumerId();
			        $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
			        $tableName = $connection->getTableName('oauth_token');
			        $token = $connection->fetchAll("SELECT token FROM " . $tableName . " WHERE consumer_id='" . $consumerId . "' LIMIT 1;");
			        $accessToken = $token[0]['token'];
		        }
		    } else if ($type == 'e') {
		        $msg = "";
		        $status = 1;
		        $consumerId = $objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($integration_id)->getConsumerId();
		        $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
		        $tableName = $connection->getTableName('oauth_token');
		        $token = $connection->fetchAll("SELECT token FROM " . $tableName . " WHERE consumer_id='" . $consumerId . "' LIMIT 1;");
		        $accessToken = $token[0]['token'];
		    }
		    if ($accessToken && $status == 1) {
		        $result = [
		        	'api_user' => $email,
		        	'api_integration_name' => $name,
		        	'api_access_token' => $accessToken,
		        	'magento_version' => $storeVersion
		        ];
		    }
		}
		return $result;
	}

	/*
	- Name : checkStoreCredential
	- it will check if store access has been created or not
	- Return status created or not
	 */
	protected function checkStoreCredential($data){
		extract($data);
		$errorMsg = '';
		try {
	        $objectManager = $this->objectManagerInstance();
		    $integrationExists = $objectManager->get('Magento\Integration\Model\IntegrationFactory')->create()->load($integration_name, 'name')->getData();
	        if (is_array($integrationExists)) {
	            $status = 1; //'success';
	        }
	    } catch (Exception $e) {
	        $status = 0;
	        $errorMsg = 'AUTHENTICATION_ERROR';
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error :'  . $e->getMessage() . "\n");
	    }
	    return array($status, $errorMsg);
	}

	/*
	- Name : checkStoreCredWrite
	- it will check if store access has been written to XML or not
	- Return status created or not
	 */
	protected function checkStoreCredWrite($dom){
		$status = false;
		if ($dom->getElementsByTagName('api_user')->item(0)->nodeValue != "" && $dom->getElementsByTagName('api_integration_name')->item(0)->nodeValue != "" && $dom->getElementsByTagName('api_access_token')->item(0)->nodeValue != "") {
			$status = true;
		}
		return $status;
	}

	/*
	- Name : storeInstallProcess
	- it will create a CMS page and a demo product
	- Return status created or not
	 */
	protected function storeInstallProcess($dom, $baseURL, $basePATH, $dummyData){
		/*Create a CMS page with an iFrame to load designer tool*/
		$this->createCmsPage($dom);
		/*Create a product attribute set and attributes*/
		$this->createAttributes();
		/*Create dummy product*/
		if ($dummyData['setup_type'] == "auto") {
			$status = $this->createSampleProducts($dom, array(9214));
		}else{
			$status = $this->createSampleProducts($dom, $dummyData['products']);
		}
		if($status == 0){
			$response = array("proceed_next" => false, "message" => "DUMMY_PRODUCT_NOT_CREATED");
	 	}else{
			$response = array("proceed_next" => true, "message" => "DUMMY_PRODUCT_CREATED");
		}
		return $response;
	}

	/*
	- Name : createCmsPage
	- it will create a CMS page for designer tool.
	- Return status created or not
	 */
	private function createCmsPage($dom)
	{
		$status = 1;
	    $identifier = 'imprint-next';
	    $objectManager = $this->objectManagerInstance();
	    $page = $objectManager->create('Magento\Cms\Model\Page');
	    $exist = $page->getCollection()->addFieldToFilter('identifier', $identifier)->getData();
	    $xetoolDir = $dom->getElementsByTagName('xetool_dir')->item(0)->nodeValue;
	    if (empty($exist)) {
	        $content = '<p>{{block type="core/template" name="myDesignerSesId" template="cedapi/productdesigner.phtml"}}<iframe id="tshirtIFrame" style="border: none;" src="{{config path="web/secure/base_url"}}'.$xetoolDir.'/index.html" height="780" width="100%"></iframe></p>';
	        $page->setTitle('ImprintNext Online Designer Tool')
	            ->setIdentifier($identifier)
	            ->setIsActive(true)
	            ->setPageLayout('1column')
	            ->setStores(array(0))
	            ->setContent($content)
	            ->save();
	    } else {
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': You have already created this CMS Page.' . "\n");
	        $status = 0;
	    }
	    return $status;
	}

	/*
	- Name : createAttributes
	- it will create a product attributes.
	- Return status created or not
	 */
	private function createAttributes()
	{
	    $objectManager = $this->objectManagerInstance();
	    $status = 0;
	    $attributeSetName = 'ImprintNext';
	    $color = 'color';
	    $clabel = 'Color';
	    $size = 'size';
	    $slabel = 'Size';
	    $xeIsDesigner = 'xe_is_designer';
	    $dlabel = 'Show in Designer';
	    $xeIsTemplate = 'xe_is_template';
	    $templateLabel = 'Pre Decorated Product';
	    $templateId = 'template_id';
	    $templateIdLabel = 'Pre Decorated Id';
	    $disableAddtocart = 'disable_addtocart';
	    $addtocartLabel = 'Disable Addtocart Button';
	    $isCatalog = 'is_catalog';
	    $isCatalogLabel = 'Catalog Product';
	    $installer = $objectManager->create('Magento\Eav\Model\Entity\Attribute\Set');
	    $attributeSetId = $installer->load($attributeSetName, 'attribute_set_name')->getAttributeSetId();

	    if (empty($attributeSetId) || !isset($attributeSetId)) {
	        $this->createAttributeSet($attributeSetName);
	    }
	    $attributeobj = $objectManager->create('Magento\Eav\Model\ResourceModel\Entity\Attribute');
	    $attribute = $objectManager->create('Magento\Catalog\Model\Entity\Attribute');
	    $options = $objectManager->create('Magento\Eav\Setup\EavSetupFactory');
	    $groupId = $installer->load($attributeSetName, 'attribute_set_name')->getDefaultGroupId();
	    //create color attribute
	    $attributeColorId = $attributeobj->getIdByCode('catalog_product', $color);
	    if (!$attributeColorId) {
	        $attributeColorData = [
	            'entity_type_id' => 4,
	            'attribute_set_id' => $attributeSetId,
	            'attribute_group_id' => $groupId,
	            'attribute_code' => $color,
	            'frontend_input' => 'select',
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'frontend_label' => $clabel,
	            'backend_type' => 'varchar',
	            'is_required' => 0,
	            'is_user_defined' => 1,
	        ];
	        $attribute->setData($attributeColorData);
	        $attribute->save();
	    }
        $attribute_arr = array('Red', 'Blue', 'Yellow');
        $option = array();
        $attributeColorId = $attributeobj->getIdByCode('catalog_product', $color);
        $option['attribute_id'] = $attributeColorId;
        foreach ($attribute_arr as $key => $value) {
            $option['value'][$value][0] = $value;
        }
        $eavSetup = $options->create();
        $eavSetup->addAttributeOption($option);
	    //create size attribute
	    $attributeSizeId = $attributeobj->getIdByCode('catalog_product', $size);
	    if (!$attributeSizeId) {
	        $attributeSizeData = [
	            'entity_type_id' => 4,
	            'attribute_set_id' => $attributeSetId,
	            'attribute_group_id' => $groupId,
	            'attribute_code' => $size,
	            'frontend_input' => 'select',
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'frontend_label' => $slabel,
	            'backend_type' => 'varchar',
	            'is_required' => 0,
	            'is_user_defined' => 1,
	        ];
	        $attribute->setData($attributeSizeData);
	        $attribute->save();
	    }
        $attribute_arr = array('L', 'M', 'S');
        $option = array();
        $attributeSizeId = $attributeobj->getIdByCode('catalog_product', $size);
        $option['attribute_id'] = $attributeSizeId;
        foreach ($attribute_arr as $key => $value) {
            $option['value'][$value][0] = $value;
        }
        $eavSetup = $options->create();
        $eavSetup->addAttributeOption($option);
	    //create Show in Designer attribute
	    $attributeDesignerId = $attributeobj->getIdByCode('catalog_product', $xeIsDesigner);
	    if (!$attributeDesignerId) {
	        $attributeDesignerData = array(
	            'entity_type_id' => 4,
	            'attribute_code' => $xeIsDesigner,
	            'frontend_input' => 'boolean',
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'frontend_label' => $dlabel,
	            'backend_type' => 'int',
	            'is_required' => 1,
	            'is_user_defined' => 1,
	            'attribute_set_id' => $attributeSetId,
	            'attribute_group_id' => $groupId
	        );
	        $attribute->setData($attributeDesignerData);
	        $attribute->save();
	        $attributeDesignerId = $attributeobj->getIdByCode('catalog_product', $xeIsDesigner);
	    }
	    //create Pre Decorated Product attribute
	    $attributeTemplateId = $attributeobj->getIdByCode('catalog_product', $xeIsTemplate);
	    if (!$attributeTemplateId) {
	        $attributeTemplateData = array(
	            'entity_type_id' => 4,
	            'attribute_code' => $xeIsTemplate,
	            'frontend_input' => 'boolean',
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'frontend_label' => $templateLabel,
	            'backend_type' => 'int',
	            'is_required' => 0,
	            'is_user_defined' => 1,
	            'attribute_set_id' => $attributeSetId,
	            'attribute_group_id' => $groupId
	        );
	        $attribute->setData($attributeTemplateData);
	        $attribute->save();
	        $attributeTemplateId = $attributeobj->getIdByCode('catalog_product', $xeIsTemplate);
	    }
	    //create Pre Decorated Product Id attribute
	    $textAttributeTemplateId = $attributeobj->getIdByCode('catalog_product', $templateId);
	    if (!$textAttributeTemplateId) {
	        $attributeTemplateData = array(
	            'entity_type_id' => 4,
	            'attribute_code' => $templateId,
	            'frontend_input' => 'text',
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'frontend_label' => $templateIdLabel,
	            'backend_type' => 'varchar',
	            'is_required' => 0,
	            'is_user_defined' => 1,
	            'attribute_set_id' => $attributeSetId,
	            'attribute_group_id' => $groupId
	        );
	        $attribute->setData($attributeTemplateData);
	        $attribute->save();
	        $textAttributeTemplateId = $attributeobj->getIdByCode('catalog_product', $templateId);
	    }
	    //create Disable Addtocart Button attribute
	    $attributeDisableId = $attributeobj->getIdByCode('catalog_product', $disableAddtocart);
	    if (!$attributeDisableId) {
	        $attributeDisableData = array(
	            'entity_type_id' => 4,
	            'attribute_code' => $disableAddtocart,
	            'frontend_input' => 'boolean',
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'frontend_label' => $addtocartLabel,
	            'backend_type' => 'int',
	            'is_required' => 0,
	            'is_user_defined' => 1,
	            'attribute_set_id' => $attributeSetId,
	            'attribute_group_id' => $groupId
	        );
	        $attribute->setData($attributeDisableData);
	        $attribute->save();
	        $attributeDisableId = $attributeobj->getIdByCode('catalog_product', $disableAddtocart);
	    }
	    //create Catalog Product attribute to know the product from catalog or not
	    $attributeIsCatalogId = $attributeobj->getIdByCode('catalog_product', $isCatalog);
	    if (!$attributeIsCatalogId) {
	        $attributeDisableData = array(
	            'entity_type_id' => 4,
	            'attribute_code' => $isCatalog,
	            'frontend_input' => 'boolean',
	            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
	            'frontend_label' => $isCatalogLabel,
	            'backend_type' => 'int',
	            'is_required' => 0,
	            'is_user_defined' => 1,
	            'attribute_set_id' => $attributeSetId,
	            'attribute_group_id' => $groupId
	        );
	        $attribute->setData($attributeDisableData);
	        $attribute->save();
	        $attributeIsCatalogId = $attributeobj->getIdByCode('catalog_product', $isCatalog);
	    }
	    //Assign attributes to attribute set
	    $attribute_code = array($attributeColorId => $color, $attributeSizeId => $size, $attributeDesignerId => $xeIsDesigner, $attributeTemplateId => $xeIsTemplate, $textAttributeTemplateId =>$templateId, $attributeDisableId => $disableAddtocart, $attributeIsCatalogId => $isCatalog);
	    $installer1 = $objectManager->create('Magento\Eav\Setup\EavSetup');
	    $attribute_set_id = $installer1->getAttributeSetId('catalog_product', $attributeSetName);
	    $group_name = 'General';
	    $attribute_group_id = $installer1->getAttributeGroupId('catalog_product', $attribute_set_id, $group_name);
	    $entityTypeId = $objectManager->create('Magento\Eav\Model\Entity\Type')->loadByCode('catalog_product')->getId();
	    try {
	        foreach ($attribute_code as $k => $att) {
	            if ($att == $xeIsDesigner || $att == $xeIsTemplate || $att == $templateId || $att == $disableAddtocart || $att == $isCatalog) {
	                $installer1->updateAttribute('catalog_product', $k, array('is_required' => false));
	            }
	            if ($att == $color || $att == $size) {
	                $installer1->updateAttribute('catalog_product', $k, array('required' => 1, 'is_html_allowed_on_front' => 1, 'is_visible_on_front' => 1, 'used_in_product_listing' => true));
	            }
	            $installer1->addAttributeToSet($entityTypeId, $attribute_set_id, $attribute_group_id, $k);
	        }
	        $msg = "";
	        $status = 1;
	    } catch (Exception $e) {
	        $msg = 'Unable to associate attributes to attribute set.';
	        $status = 0;
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $msg);
	    }
	    return array($status, $msg);
	}

	/*
	- Name : createAttributeSet
	- it will create a product attribute set.
	- Return status created or not
	 */
	private function createAttributeSet($setName)
	{
		$status = 0;
	    try {
	        $objectManager = $this->objectManagerInstance();
	        $attributeSet = $objectManager->create('Magento\Eav\Model\Entity\Attribute\Set');
	        $entityTypeId = $objectManager->create('Magento\Eav\Model\Entity\Type')->loadByCode('catalog_product')->getId();
	        $attributeSet->setData(array(
	            'attribute_set_name' => $setName,
	            'entity_type_id' => $entityTypeId,
	            'sort_order' => 200,
	        ));
	        $attributeSet->validate();
	        $attributeSet->save();
	        $baseSetId = 4;
	        $attributeSet->initFromSkeleton($baseSetId)->save();
	        $status = 1;
	    } catch (Exception $e) {
	        $msg = '- Unable to create attribute set.';
	        $status = 0;
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $msg);
	    }
	    return $status;
	}

	/*
	- Name : createSampleProducts
	- it will create dummy products.
	- Return status created or not
	 */
	private function createSampleProducts($dom, $prodArr)
	{
		$status = 0;
		foreach ($prodArr as $productID) {
			$productData = file_get_contents(DUMMYDATADIR."product_".$productID.".json");
			$productData = json_decode($productData, true);
			$createdProductId = $this->createProduct($dom, $productData);
			$status = $this->setBoundaryForDummyProduct($dom, $createdProductId, $productData['data']);
		}
		return $status;
	}

	/*
	- Name : createProduct
	- it will create a product attribute set.
	- Return status created or not
	 */
	private function createProduct($dom, $productData)
	{
	    $sProductName = $productData['data']['product_name'] . 'Simple';
	    $sSku = 'ssku';
	    $cProductName = $productData['data']['product_name'];
	    $cSku = 'csku';
	    $configProductId = 0;
	    $mediaAttribute = array('thumbnail', 'small_image', 'image');
	    $attributeSetName = 'ImprintNext';
	    $attributeColorCode = 'color';
	    $attributeSizeCode = 'size';
	    $colorMethod = 'set' . ucfirst($attributeColorCode);
	    $sizeMethod = 'set' . ucfirst($attributeSizeCode);
	    $objectManager = $this->objectManagerInstance();
	    if ($attributeSetName == 'Default') {
	        $attributeSetId = 4;
	    } else {
	        $attributeSetId = $objectManager->create('Magento\Eav\Model\Entity\Attribute\Set')->load($attributeSetName, 'attribute_set_name')->getAttributeSetId();
	    }
	    $attributeObj = $objectManager->create('Magento\Eav\Model\ResourceModel\Entity\Attribute');
	    $attributeColorId = $attributeObj->getIdByCode('catalog_product', $attributeColorCode);
	    $attributeSizeId = $attributeObj->getIdByCode('catalog_product', $attributeSizeCode);
	    $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
	    $rootCatId = $storeManager->getStore()->getRootCategoryId();
	    $productModel = $objectManager->create('Magento\Catalog\Model\Product');
	    $existProduct = $productModel->loadByAttribute('name', $sProductName);
	    $attr = $productModel->getResource()->getAttribute($attributeColorCode);
	    $optarr = array();
	    if ($attr->usesSource()) {
	        $optarr = $attr->getSource()->getAllOptions();
	        $optionColorId = $optarr[1]['value'];
	    }
	    $attr = $productModel->getResource()->getAttribute($attributeSizeCode);
	    if ($attr->usesSource()) {
	        $optarr = $attr->getSource()->getAllOptions();
	        $optionSizeId = $optarr[1]['value'];
	    }
	    /* Simple Product Insert Section */
	    if (empty($existProduct)) {
	        $simpleProduct = $objectManager->create('Magento\Catalog\Model\Product');
	        $simpleProduct->setWebsiteIds(array(1))
	            ->setAttributeSetId($attributeSetId)
	            ->setTypeId('simple')
	            ->setSku($sSku)
	            ->setName($sProductName)
	            ->setWeight(2)
	            ->setStatus(1)
	            ->setTaxClassId(0)
	            ->setVisibility(1)
	            ->setColor($optionColorId)
	            ->setSize($optionSizeId)
	            ->setPrice(500)
	            ->setXeIsDesigner(0)
	            ->setXeIsTemplate(0)
	            ->setDescription('Test Product. Not for sold.')
	            ->$colorMethod($optionColorId)
	            ->$sizeMethod($optionSizeId);
	        $count = 0;
	        $imgArray = array($productData['data']['store_images'][0]['src']);
	        foreach ($imgArray as $image):
	            $imgUrl = $this->saveProductImage($image, $objectManager);
	            if ($count == 0) {
	                $simpleProduct->addImageToMediaGallery($imgUrl, $mediaAttribute, false, false);
	            } else {
	                $simpleProduct->addImageToMediaGallery($imgUrl, null, false, false);
	            }
	            $count++;
	        endforeach;
	        $simpleProduct->setStockData(array(
	            'use_config_manage_stock' => 0,
	            'manage_stock' => 1,
	            'min_sale_qty' => 1,
	            'max_sale_qty' => 30,
	            'is_in_stock' => 1,
	            'qty' => 200
	        )
	        )
	            ->setCategoryIds(array($rootCatId));
	        $simpleProduct->save();
	        $simplProductId = $simpleProduct->getId();
	    } else {
	        $simpleProductData = $existProduct->getData();
	        $simplProductId = $simpleProductData['entity_id'];
	    }
	    /* Configurable Product Insert Section */
	    if (isset($simplProductId) && $simplProductId) {
	        $existConfigProduct = $productModel->loadByAttribute('name', $cProductName);
	        if (empty($existConfigProduct)) {
	            $configProduct = $objectManager->create('Magento\Catalog\Model\Product');
	            $configProduct->setStoreId(1)
	                ->setWebsiteIds(array(1))
	                ->setAttributeSetId($attributeSetId)
	                ->setTypeId('configurable')
	                ->setSku($cSku)
	                ->setName($cProductName)
	                ->setWeight(213)
	                ->setStatus(1)
	                ->setTaxClassId(0)
	                ->setVisibility(4)
	                ->setXeIsDesigner(1)
	                ->setXeIsTemplate(0)
	                ->setPrice(500000.00)
	                ->setDescription('Test Product. Not for sold.');
	            $configProduct->setStockData(array(
	                'use_config_manage_stock' => 0,
	                'manage_stock' => 1,
	                'min_sale_qty' => 1,
	                'max_sale_qty' => 30,
	                'is_in_stock' => 1,
	                'qty' => 200,
	            )
	            )
	                ->setCategoryIds(array($rootCatId)); //assign product to categories
	            $count = 0;
	            $imgArray = array($productData['data']['store_images'][0]['src']);
	            foreach ($imgArray as $image) {
	                $imgUrl = $this->saveProductImage($image, $objectManager);
	                if ($count == 0) {
	                    $configProduct->addImageToMediaGallery($imgUrl, $mediaAttribute, false, false);
	                } else {
	                    $configProduct->addImageToMediaGallery($imgUrl, null, false, false);
	                }
	                $count++;
	            }
	            $configProduct->save();
	            $conf_id1 = $configProduct->getId();
	            $product = $configProduct->load($conf_id1);

	            $objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->setUsedProductAttributeIds(array($attributeColorId, $attributeSizeId), $product);
	            $product->setCanSaveConfigurableAttributes(true);
	            $product->save();
	            try {
	                $configProductId = $product->getId();
	                $productURL = $product->getProductUrl();
	                // Starting session
					session_start();
					// Storing session data
					$_SESSION["productURL"] = $productURL;
	            } catch (Exception $e) {
	                $msg = 'exception: ' . $e->getMessage();
	                $this->xe_log("\n" . date("Y-m-d H:i:s") . $msg . "\n");
	            }
	        } else {
	            $cpid = $existConfigProduct->getData();
	            $configProductId = $cpid['entity_id'];
	        }
	    }
	    /* Associate simple product to configurable*/
	    if (isset($simplProductId) && isset($configProductId)) {
	        $this->associateSimpleToConfigurableProduct($configProductId, $simplProductId, $attributeColorCode, $attributeSizeCode, $attributeColorId, $attributeSizeId, $attributeSetId);
	        
	    }
	    return $configProductId;
	}

	/*
	- Name : associateSimpleToConfigurableProduct
	- it will associate a simple product to config product.
	- Return status created or not
	 */
	private function associateSimpleToConfigurableProduct($confId, $childIds, $attributeColorCode, $attributeSizeCode, $attribute_colorid, $attribute_sizeid, $attribute_set_id)
	{
		$status = 1;
	    try {
	        $objectManager = $this->objectManagerInstance();
	        $configProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($confId);
	        $productCollectionFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
	        $simpleProducts = $productCollectionFactory->create()
	            ->addIdFilter($childIds)
	            ->addAttributeToSelect($attributeColorCode)
	            ->addAttributeToSelect($attributeSizeCode)
	            ->addAttributeToSelect('price');
	        $configProduct->setCanSaveConfigurableAttributes(true);
	        $configProduct->setCanSaveCustomOptions(true);
	        $configProduct->getTypeInstance()->setUsedProductAttributeIds(array($attribute_colorid, $attribute_sizeid), $configProduct);
	        $configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray($configProduct);
	        $configProduct->setCanSaveConfigurableAttributes(true);
	        $configProduct->setConfigurableAttributesData($configurableAttributesData);
	        $configurableProductsData = array();
	        $variants = array();
	        foreach ($simpleProducts as $i => $simple) {
	            $variants[$i]['color_id'] = (int) $simple->getXeColor();
	            $variants[$i]['size_id'] = (int) $simple->getXeSize();
	            $colors[] = (int) $simple->getXeColor();
	            $productData = array(
	                'label' => $simple->getAttributeText($attributeColorCode),
	                'attribute_id' => $attribute_colorid,
	                'value_index' => (int) $simple->getColor(),
	                'is_percent' => 0,
	                'pricing_value' => $simple->getPrice(),
	            );
	            $configurableProductsData[$simple->getId()] = $productData;
	            $configurableAttributesData[0]['values'][] = $productData;
	            $productData = array(
	                'label' => $simple->getAttributeText($attributeSizeCode),
	                'attribute_id' => $attribute_sizeid,
	                'value_index' => (int) $simple->getSize(),
	                'is_percent' => 0,
	                'pricing_value' => $simple->getPrice(),
	            );
	            $configurableProductsData[$simple->getId()] = $productData;
	            $configurableAttributesData[1]['values'][] = $productData;
	        }
	        $configProduct->setConfigurableProductsData($configurableProductsData);
	        $attributeModel = $objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute');
	        $position = 0;
	        $attributes = array($attribute_colorid, $attribute_sizeid);
	        foreach ($attributes as $attributeId) {
	            $data = array('attribute_id' => $attributeId, 'product_id' => $confId, 'position' => $position);
	            $position++;
	            $attributeModel->setData($data);
	        }
	        $configProduct->setAffectConfigurableProductAttributes($attribute_set_id);
	        $objectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable')->setUsedProductAttributeIds($attributes, $configProduct);
	        $configProduct->setNewVariationsAttributeSetId($attribute_set_id);
	        $configProduct->setAssociatedProductIds(array($childIds));
	        $configProduct->setCanSaveConfigurableAttributes(true);
	        $configProduct->save();
	    } catch (Exception $e) {
	        $msg = 'exception: ' . $e->getMessage();
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ':' . $msg);
	        $status = 0;
	    }
	    return $status;
	}

	/*
	- Name : saveProductImage
	- it will upload and save the product image.
	- Return image file path
	 */
	private function saveProductImage($img, $objectManager)
	{
	    $imageFilename = basename($img);
	    $image_type = substr(strrchr($imageFilename, "."), 1);
	    $filename = md5($img . strtotime('now')) . '.' . $image_type;
	    $mediaDir = $objectManager->get('Magento\Framework\App\Filesystem\DirectoryList')->getPath('media');
	    $filepath = $mediaDir . '/' . $filename;
	    file_put_contents($filepath, file_get_contents(trim($img)));
	    return $filepath;
	}

	/*
	- Name : setBoundaryForDummyProduct
	- it will set boundary for newly created product.
	- Return status set or not
	 */
	private function setBoundaryForDummyProduct($dom, $newProductID, $ParentData) {
		$host = $dom->getElementsByTagName('host')->item(0)->nodeValue;
		$user = $dom->getElementsByTagName('dbuser')->item(0)->nodeValue;
		$password = $dom->getElementsByTagName('dbpass')->item(0)->nodeValue;
		$dbName = $dom->getElementsByTagName('dbname')->item(0)->nodeValue;
		$port = $dom->getElementsByTagName('port')->item(0)->nodeValue;
		$status = 1;
		try {
	        error_reporting(0);
	        if (isset($port) && $port != '') {
	            $conn = new mysqli($host, $user, $password, $dbName, $port);
	        } else {
	            $conn = new mysqli($host, $user, $password);
	            $conn->select_db($dbName);
	        }
	    } catch (Exception $e) {
	        $error = "- Database Connection failed. Error: " . $e->getMessage() . "\n";
	        $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Database Connection failed: ' . $e->getMessage() . "\n");
	        $response = array("proceed_next" => false, "message" => "DATABASE_CONN_ERROR");
	        return $response;exit();
	    }
	    // Insert product id into product_setting table and get xe_id
	    $insertProductSetting = "INSERT INTO product_settings(product_id,is_variable_decoration,is_ruler,is_crop_mark,is_safe_zone,crop_value,safe_value,is_3d_preview,3d_object_file,3d_object,scale_unit_id,store_id) VALUES(".$newProductID."," . $ParentData['is_variable_decoration'] ."," . $ParentData['is_ruler']. "," . $ParentData['is_crop_mark']. "," . $ParentData['is_safe_zone']. "," . $ParentData['crop_value']. "," . $ParentData['safe_value']. "," . $ParentData['is_3d_preview']. ",'" . $ParentData['3d_object_file']. "','" . $ParentData['3d_object']. "'," . $ParentData['scale_unit_id']. " , 1)";
        $queryStatusPS = $conn->query($insertProductSetting);
        $prodSetID = mysqli_insert_id($conn);
        if ($queryStatusPS == false) {
            $errorMsg .= "- Data not inserted to domain_store_rel table. \n";
            $status = 0;
        }
        //Assign product image
        $productimageQRY = "INSERT INTO `product_image_settings_rel` (`product_setting_id`, `product_image_id`) VALUES (".$prodSetID."," . $ParentData['product_image_id'] .")";
        $queryRun = $conn->query($productimageQRY);
        // insert print profile and product id relationship
        $insertRelation = "INSERT INTO print_profile_product_setting_rel(print_profile_id, product_setting_id) VALUES";
        foreach ($ParentData['print_profiles'] as $key => $rel) {
        	if ($key > 0) {
        		$insertRelation .= ", "; 
        	}
        	 $insertRelation .= "(".$rel['id']."," . $prodSetID. ")";
        }
        $queryStatusPPM = $conn->query($insertRelation);

        // Insert sides into product_sides table and get side id
        foreach ($ParentData['sides'] as $side) {
		    $insertSideSetting = "INSERT INTO product_sides(product_setting_id,side_name,side_index,product_image_dimension,is_visible,product_image_side_id) VALUES(".$prodSetID.",'" . $side['name'] ."','" . $side['index']. "','" . $side['dimension']. "'," . $side['is_visible']. "," . $side['image']['id']. ")";
	        $queryStatusS = $conn->query($insertSideSetting);
	        $sideSetID = mysqli_insert_id($conn);
	        if ($queryStatusS == false) {
	            $errorMsg .= "- Data not inserted to domain_store_rel table. \n";
	            $status = 0;
	        }
	        $setting = $side['decoration_settings'][0];
	        // Insert data for each sides decoration settings
	        $insertDecoSetting = "INSERT INTO product_decoration_settings(product_setting_id,product_side_id,name,dimension,print_area_id,sub_print_area_type,custom_min_height,custom_max_height,custom_min_width,custom_max_width,is_border_enable,is_sides_allow) VALUES(".$prodSetID."," . $sideSetID .",'" . $setting['name']. "','" . $setting['dimension']. "','" . $setting['print_area_id']. "','" . $setting['sub_print_area_type']. "','" . 0 ."','" . 0 ."','" . 0 ."','" . 0 ."','" . $setting['is_border_enable']."','" . $setting['is_sides_allow']. "')";
	        $queryStatusDS = $conn->query($insertDecoSetting);
	        $decoSetID = mysqli_insert_id($conn);

	        $insertMethodSetRel = "INSERT INTO print_profile_decoration_setting_rel(print_profile_id, decoration_setting_id) VALUES";
	        foreach ($setting['print_profiles'] as $key => $rel) {
	        	if ($key > 0) {
	        		$insertMethodSetRel .= ", "; 
	        	}
	        	 $insertMethodSetRel .= "(".$rel['id']."," . $decoSetID. ")";
	        }
	        $queryStatusPDM = $conn->query($insertMethodSetRel);
        }
        return $status;
	}

	/*
	- Name : getDummyProductURL
	- it will fetch and return store URL.
	- Return store URL
	 */
	protected function getDummyProductURL($dom){
		// Starting session
		session_start();
        return $_SESSION["productURL"];
	}

	public function getStoreLangCurrency($storeId, $dom = "")
	{
		$objectManager = $this->objectManagerInstance();
		$store = $objectManager->get('Magento\Store\Api\Data\StoreInterface');
		$lang = $store->getLocaleCode();
		$currency = $store->getCurrentCurrency()->getCode();
		if ( strlen( $lang ) > 0 ) {
			$language = explode( '_', $lang )[0];
		}
		$response = [
			'currency' => $currency,
			'language' => $language,
			'storeId'  => $storeId,
		];
    	return json_encode($response);
	}
}
?>