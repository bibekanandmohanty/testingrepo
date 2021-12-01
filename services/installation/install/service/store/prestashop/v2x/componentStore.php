<?php

include_once dirname(__FILE__) . '/../../../../../../config/config.inc.php';

class StoreComponent
{
    /** @var int Module ID */
    public $id = null;
    /** @var float Version */
    public $version;
    /** @var array filled with known compliant PS versions */
    public $ps_versions_compliancy = array();
    /** @var string Unique name */
    public $name;
    /** @var string A little description of the module */
    public $description;
    /** @var int need_instance */
    public $need_instance = 1;
    /** @var bool Status */
    public $active = false;
    /** @var bool Is the module certified by addons.prestashop.com */
    public $enable_device = 7;
    /** @var array to store the limited country */
    public $limited_countries = array();
    /** for add group*/
    public $groupBox;
    public $available_date;
    /** @var array names of the controllers */
    public $controllers = array();
    /** @var array current language translations */
    protected $_lang = array();
    /** @var string Module web path (eg. '/shop/modules/modulename/')  */
    protected $_path = null;
    /**
     * @since 1.5.0.1
     * @var string Module local path (eg. '/home/prestashop/modules/modulename/')
     */
    protected $local_path = null;
    /** @var array Array filled with module errors */
    protected $_errors = array();
    /** @var array Array  array filled with module success */
    protected $_confirmations = array();
    /** @var string Main table used for modules installed */
    protected $table = 'module';
    /** @var string Identifier of the main table */
    protected $identifier = 'id_module';
    /** @var array Array cache filled with modules informations */
    protected static $modules_cache;
    /** @var array Array filled with cache translations */
    protected static $l_cache = array();
    /** @var Context */
    protected $context;
    protected static $update_translations_after_install = true;
    public $push_time_limit = 180;
    /** @var bool Random session for modules perfs logs*/
    const CACHE_FILE_TAB_MODULES_LIST = '/config/xml/tab_modules_list.xml';

    /**
     * Constructor
     *
     * @param string $name Module unique name
     * @param Context $context
     */
    public function __construct($name = null, Context $context = null)
    {
        if (isset($this->ps_versions_compliancy) && !isset($this->ps_versions_compliancy['min'])) {
            $this->ps_versions_compliancy['min'] = '1.4.0.0';
        }

        if (isset($this->ps_versions_compliancy) && !isset($this->ps_versions_compliancy['max'])) {
            $this->ps_versions_compliancy['max'] = _PS_VERSION_;
        }

        if (strlen($this->ps_versions_compliancy['min']) == 3) {
            $this->ps_versions_compliancy['min'] .= '.0.0';
        }

        if (strlen($this->ps_versions_compliancy['max']) == 3) {
            $this->ps_versions_compliancy['max'] .= '.999.999';
        }

        // Load context and smarty
        $this->context = $context ? $context : Context::getContext();
        if (is_object($this->context->smarty)) {
            $this->smarty = $this->context->smarty->createData($this->context->smarty);
        }
        // If the module has no name we gave him its id as name
        if ($this->name === null) {
            $this->name = $this->id;
        }
        // If the module has the name we load the corresponding data from the cache
        if ($this->name != null) {
            // If cache is not generated, we generate it
            if (self::$modules_cache == null && !is_array(self::$modules_cache)) {
                $id_shop = (Validate::isLoadedObject($this->context->shop) ? $this->context->shop->id : Configuration::get('PS_SHOP_DEFAULT'));

                self::$modules_cache = array();
                // Join clause is done to check if the module is activated in current shop context
                $result = Db::getInstance()->executeS('
                SELECT m.`id_module`, m.`name`, (
                    SELECT id_module
                    FROM `' . _DB_PREFIX_ . 'module_shop` ms
                    WHERE m.`id_module` = ms.`id_module`
                    AND ms.`id_shop` = ' . (int) $id_shop . '
                    LIMIT 1
                ) as mshop
                FROM `' . _DB_PREFIX_ . 'module` m');
                foreach ($result as $row) {
                    self::$modules_cache[$row['name']] = $row;
                    self::$modules_cache[$row['name']]['active'] = ($row['mshop'] > 0) ? 1 : 0;
                }
            }
            // We load configuration from the cache
            if (isset(self::$modules_cache[$this->name])) {
                if (isset(self::$modules_cache[$this->name]['id_module'])) {
                    $this->id = self::$modules_cache[$this->name]['id_module'];
                }
                foreach (self::$modules_cache[$this->name] as $key => $value) {
                    if (array_key_exists($key, $this)) {
                        $this->{$key} = $value;
                    }
                }
                $this->_path = __PS_BASE_URI__ . 'modules/' . $this->name . '/';
            }
            if (!$this->context->controller instanceof Controller) {
                self::$modules_cache = null;
            }
            //$this->local_path = _PS_MODULE_DIR_.$this->name.'/';
        }
    }

    /*
    - Name : checkStoreCredential
    - it will check if store access has been created or not
    - Return status created or not
     */
    protected function checkStoreCredential($data)
    {
        $status = 1;
        return array($status);
    }

    /*
    - Name : checkStoreCredWrite
    - it will check if store access has been written to XML or not
    - Return status created or not
     */
    protected function checkStoreCredWrite($dom)
    {
        $status = false;
        if ($dom->getElementsByTagName('ps_ws_auth_key')->item(0)->nodeValue != "" && $dom->getElementsByTagName('ps_shop_path')->item(0)->nodeValue != "") {
            $status = true;
        }
        return $status;
    }

    /*
    - Name : storeInstallProcess
    - it will create demo product
    - Return status created or not
     */
    protected function storeInstallProcess($dom, $baseURL, $basePATH, $dummyData)
    {
        /*Create dummy product*/
        if ($dummyData['setup_type'] == "auto") {
            $status = $this->createSampleProducts($dom, 9214);
        } else {
            $status = $this->createSampleProducts($dom, $dummyData['products']);
        }
        if ($status == 0) {
            $response = array("proceed_next" => false, "message" => "DUMMY_PRODUCT_NOT_CREATED");
        } else {
            $response = array("proceed_next" => true, "message" => "DUMMY_PRODUCT_CREATED");
        }
        return $response;
    }

    /*
    - Name : createSampleProducts
    - it will create dummy products.
    - Return status created or not
     */
    public function createSampleProducts($dom, $prodArr)
    {
        $status = 0;
        foreach ($prodArr as $productID) {
            $productData = file_get_contents(DUMMYDATADIR . "product_" . $productID . ".json");
            $productData = json_decode($productData, true);
            $createdProductId = $this->createProduct($productData);
            if ($createdProductId) {
                $status = $this->setBoundaryForDummyProduct($dom, $createdProductId, $productData['data']);
            }
        }
        return $status;
    }

    private function generateRandomString()
    {
        return rand();
    }

    /**
     *To check attribute is exist or not
     *
     * @param (Int)id_attribute_group
     * @param (String)name
     * @param (Int)id_lang
     * @return integer
     *
     */
    public function isAttributeExit($id_attribute_group, $name, $id_lang)
    {
        $result = Db::getInstance()->getValue('
            SELECT COUNT(*)
            FROM `' . _DB_PREFIX_ . 'attribute_group` ag
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang . ')
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
                ON a.`id_attribute_group` = ag.`id_attribute_group`
            LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang . ')
            ' . Shop::addSqlAssociation('attribute_group', 'ag') . '
            ' . Shop::addSqlAssociation('attribute', 'a') . '
            WHERE al.`name` = \'' . pSQL($name) . '\' AND ag.`id_attribute_group` = ' . (int) $id_attribute_group . '
            ORDER BY agl.`name` ASC, a.`position` ASC
        ');
        return ((int) $result > 0);
    }

    /**
     *Add product attribute value by group id in store backend
     *
     * @param Int(size)
     * @param Int(sizeGroupId)
     * @return Array
     *
     */
    public function createSizeAttributeValue($sizeGroupId, $size)
    {
        $sizeId = 0;
        $id_lang = Context::getContext()->language->id;
        $exitResult = $this->isAttributeExit($sizeGroupId, $size, $id_lang);
        if (empty($exitResult) && $size != '') {
            $attribute = new Attribute();
            $attribute->name = $this->createMultiLangFields($size);
            $attribute->id_attribute_group = $sizeGroupId;
            $attribute->color = '';
            $attribute->position = 0;
            $attribute->add();
        }
        $size_sql = "SELECT al.id_attribute from " . _DB_PREFIX_ . "attribute_lang as al,
        " . _DB_PREFIX_ . "attribute as atr
        where id_attribute_group =" . $sizeGroupId . " and atr.id_attribute = al.id_attribute and al.name='" . $size . "' and al.id_lang=" . intval($id_lang) . "";
        $row_size = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($size_sql);
        $sizeId = $row_size[0]['id_attribute'];
        return $sizeId;
    }

    public function createColorAttributeValue($attributeGroup, $parameter)
    {
        $colorId = 0;
        $id_lang = Context::getContext()->language->id;
        $colorGroupId = $attributeGroup['color_group_id'] ? $attributeGroup['color_group_id'] : 0;
        $colorName = $parameter['color_arr']['name'];
        $colorHexaValue = (isset($parameter['color_arr']['hex_code'])) ? $parameter['color_arr']['hex_code'] : '#ffffff';
        $checkExit = $this->isAttributeExit($colorGroupId, $colorName, $id_lang);
        if (empty($checkExit) && $colorHexaValue != '' && $colorName != '') {
            $newAttribute = new Attribute();
            $newAttribute->name = $colorName;
            $newAttribute->id_attribute_group = $colorGroupId;
            $newAttribute->color = $colorHexaValue;
            $newAttribute->position = 0;
            $colorId = $newAttribute->add();
        } else {
            $size_color = "SELECT al.id_attribute from " . _DB_PREFIX_ . "attribute_lang as al,
            " . _DB_PREFIX_ . "attribute as atr
            where id_attribute_group =" . $colorGroupId . " and atr.id_attribute = al.id_attribute and al.name='" . $colorName . "' and al.id_lang=" . intval($id_lang) . "";
            $row_color = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($size_color);
            $colorId = $row_color[0]['id_attribute'];
        }
        return $colorId;
    }

    /**
     *Create multi language in prestasho store
     *
     * @param (String)field
     * @return Boolean
     *
     */
    public function createMultiLangFields($field)
    {
        $res = array();
        foreach (Language::getIDs(false) as $id_lang) {
            $res[$id_lang] = $field;
        }

        return $res;
    }
    /*
    - Name : createProduct
    - it will create a product attribute set.
    - Return status created or not
     */
    private function createProduct($productData)
    {
        $colorId = 0;
        $productId = 0;
        $product = $productData['data'];
        $productName = $product['product_name'];
        if (isset($product['size'])) {
            $sizeArr = $product['size'] ? $product['size'] : array();
        }
        if (isset($product['Size'])) {
            $sizeArr = $product['Size'] ? $product['Size'] : array();
        }
        if (isset($product['color'])) {
            $colorArr = $product['color'][0] ? $product['color'][0] : array();
        }
        if (isset($product['Color'])) {
            $colorArr = $product['Color'][0] ? $product['Color'][0] : array();
        }
        $storeImages = $product['store_images'][0]['src'];
        $sqlLang = "SELECT id_lang FROM " . _DB_PREFIX_ . "lang";
        $languageArr = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlLang);
        $attributeGroup = $this->createAttributeGroup($languageArr);
        $colorGroupId = $attributeGroup['color_group_id'] ? $attributeGroup['color_group_id'] : 0;
        $sizeGroupId = $attributeGroup['size_group_id'] ? $attributeGroup['size_group_id'] : 0;
        if ($colorGroupId && !empty($colorArr)) {
            $parameter = array(
                "color_arr" => $colorArr,
                "size_arr" => $sizeArr,
            );
            $colorId = $this->createColorAttributeValue($attributeGroup, $parameter);
        }
        $langId = Context::getContext()->language->id;
        $sql = "SELECT id_product FROM " . _DB_PREFIX_ . "product_lang where name='" . $productName . "' and id_lang=" . $langId . "";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (empty($row[0]['id_product'])) {
            $description = $productName;
            $short_description = $productName;
            $n_link_rewrite = $this->generateRandomString() . $productName;
            $n_meta_title = 'ImprintNext Black Tshirt';
            $n_meta_description = $productName;
            $n_meta_keywords = $productName;
            $n_available_now = 'Available for order';
            $curDate = date('d-m-Y', time());
            $n_available_later = 'Available from ' . $curDate . '';
            $now = date('Y-m-d H:i:s', time());
            $qty = 1000;
            $totalQantity = 0;
            $id_category = $this->addCategoryByProduct($languageArr);
            $price = '100.00';
            $sku = $productName;
            $id_shop = (int) Context::getContext()->shop->id;
            $insert_sql = "INSERT INTO " . _DB_PREFIX_ . "product(id_supplier,id_manufacturer,id_category_default,id_tax_rules_group,price,reference,active,redirect_type,indexed,cache_default_attribute,date_add,date_upd,customize)
            VALUES('1','1','$id_category','1','$price','$sku','1','404','1','','$now','$now','1')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql);
            $productId = Db::getInstance()->Insert_ID();
            //ps_product_shop
            if (_PS_VERSION_ >= '1.7.2.2') {
                $product_shop_sql = "INSERT INTO `" . _DB_PREFIX_ . "product_shop` (`id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`,
                `minimal_quantity`, `price`, `active`, `redirect_type`, `available_for_order`, `condition`, `show_price`, `indexed`, `visibility`,
                `cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd`, `pack_stock_type`)
                 VALUES ('$productId', '$id_shop', '$id_category', '1', '1', '$price', '1', '404', '1', 'new', '1', '1', 'both', '0', '0', '$now', '$now', '3')";
            } else {
                $product_shop_sql = "INSERT INTO `" . _DB_PREFIX_ . "product_shop` (`id_product`, `id_shop`, `id_category_default`, `id_tax_rules_group`,
                `minimal_quantity`, `price`, `active`, `redirect_type`, `id_product_redirected`, `available_for_order`, `condition`, `show_price`, `indexed`, `visibility`,
                `cache_default_attribute`, `advanced_stock_management`, `date_add`, `date_upd`, `pack_stock_type`)
                 VALUES ('$productId', '$id_shop', '$id_category', '1', '1', '$price', '1', '404', '0', '1', 'new', '1', '1', 'both', '0', '0', '$now', '$now', '3')";
            }
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($product_shop_sql);
            //ps_product_lang
            $link_rewrite = strtolower($n_link_rewrite);
            if (preg_match('/\s/', $link_rewrite)) {
                $link_rewrite = str_replace(' ', '-', $link_rewrite);
            }

            foreach ($languageArr as $v) {
                $product_lang_sql = "INSERT INTO " . _DB_PREFIX_ . "product_lang(id_product,id_shop,id_lang,description,description_short,link_rewrite,
                meta_description,meta_keywords,meta_title,name,available_now,available_later)
                VALUES('$productId','$id_shop','" . $v['id_lang'] . "','$description','$short_description','$link_rewrite','$n_meta_description','$n_meta_keywords','$n_meta_title','$productName','$n_available_now','$n_available_later')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($product_lang_sql);
            }
            //add Category to product //
            $this->addToCategoriesToProduct($id_category, $productId);
            //product image add
            $url[] = $storeImages;
            $imageId = $this->addImageByProductId($url, $productId, $productName, $languageArr, $id_shop);

            if (!empty($sizeArr)) {
                foreach ($sizeArr as $key => $size) {
                    $sizeNameValue = $size['name'];
                    $sizeId = $this->createSizeAttributeValue($sizeGroupId, $sizeNameValue);
                    if ($sizeId) {
                        if ($key == 0) {
                            $defaultOn = 1;
                        } else {
                            $defaultOn = 0;
                        }
                        $attrId = $this->addProductAttributesByProductIds($sizeId, $productId, $sku, $colorId, $defaultOn, $id_shop);
                        if ($attrId) {
                            $this->upadteCacheProductAttrId($productId, $attrId);
                            $this->addProductStock($attrId, $productId, $totalQantity, $qty);
                            $this->addImageAttributes($attrId, $imageId);
                        }
                    }
                }
            }
            $this->updateTotalQuantityByPid($productId);
        } else {
            $productId = $row[0]['id_product'];
        }
        return $productId;
    }

    public function createAttributeGroup($lang_ids)
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        //add color attribute
        if($id_lang == 1){
            $colornName = 'Color';
        }else{
            $colornName = 'Colour';
        }
        $sql = "SELECT id_attribute_group from " . _DB_PREFIX_ . "attribute_group_lang where name='" . $colornName . "' and id_lang=" . $id_lang . "";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (empty($result[0]['id_attribute_group'])) {
            $sql = "SELECT position FROM " . _DB_PREFIX_ . "attribute_group order by position desc limit 1";
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $insert_sql = "INSERT INTO `" . _DB_PREFIX_ . "attribute_group` (`group_type`,`is_color_group`,`position`) VALUES('color','1','" . intval($row['0']['position'] + 1) . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql);
            $colorGroupId = $groupId = Db::getInstance()->Insert_ID();
            foreach ($lang_ids as $v) {
                $insert_sql1 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_lang (id_attribute_group,id_lang,name,public_name) VALUES(" . $groupId . ",'" . $v['id_lang'] . "','$colornName','color')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql1);
            }
            $insert_sql2 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_shop (id_attribute_group,id_shop) VALUES(" . $groupId . "," . $id_shop . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql2);
        } else {
            $colorGroupId = $result[0]['id_attribute_group'];

        }
        //add size attribue
        $sizeName = 'Size';
        $sqlZize = "SELECT id_attribute_group from " . _DB_PREFIX_ . "attribute_group_lang where name='" . $sizeName . "' and id_lang=" . $id_lang . "";
        $resultSize = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlZize);
        if (empty($resultSize[0]['id_attribute_group'])) {
            $sql = "SELECT position FROM " . _DB_PREFIX_ . "attribute_group order by position desc limit 1";
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $insert_sql = "INSERT INTO `" . _DB_PREFIX_ . "attribute_group` (`group_type`,`position`) VALUES('select','" . intval($row['0']['position'] + 1) . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql);
            $sizeGroupId = $groupId = Db::getInstance()->Insert_ID();
            foreach ($lang_ids as $v1) {
                $insert_sql1 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_lang (id_attribute_group,id_lang,name,public_name) VALUES(" . $groupId . ",'" . $v1['id_lang'] . "','$sizeName','size')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql1);
            }
            $insert_sql2 = "INSERT INTO " . _DB_PREFIX_ . "attribute_group_shop (id_attribute_group,id_shop) VALUES(" . $groupId . "," . $id_shop . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql2);
        } else {
            $sizeGroupId = $resultSize[0]['id_attribute_group'];

        }
        $attributeGroup['color_group_id'] = $colorGroupId;
        $attributeGroup['size_group_id'] = $sizeGroupId;
        return $attributeGroup;
    }

    /**
     *Add product combination/atrribute image
     *
     * @param (Int)idProductAttribute
     * @param (Array)imageArr
     * @return nothing
     *
     */
    private function addImageAttributes($idProductAttribute, $imageArr = array())
    {
        if (!is_array($imageArr)) {
            $imageArr = array($imageArr);
        }
        foreach ($imageArr as $v) {
            $sql = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_image (id_product_attribute,id_image) VALUES(" . intval($idProductAttribute) . "," . intval($v) . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
        }

    }

    /**
     *Update total quantity by product id after predeco product successfully added
     *
     * @param (Int)productId
     * @return nothing
     *
     */
    private function updateTotalQuantityByPid($productId)
    {
        $sql = "SELECT quantity FROM " . _DB_PREFIX_ . "stock_available WHERE id_product = " . $productId . " AND id_product_attribute !='0'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        foreach ($rows as $k => $v) {
            $totalQantity += $v['quantity'];
        }
        $query = "UPDATE " . _DB_PREFIX_ . "stock_available SET quantity= '" . $totalQantity . "' WHERE id_product = " . $productId . " AND id_product_attribute='0'";
        return Db::getInstance()->Execute($query);
    }

    /**
     *Add product combination/atrribute image
     *
     * @param (Int)idProductAttribute
     * @param (Int)image_id
     * @return nothing
     *
     */
    private function addProductStock($attrId = array(), $productId, $totalQantity, $qty, $isExitProduct = false, $conf_id = '')
    {
        $status = 1;
        if (!is_array($attrId)) {
            $attrId = array($attrId);
        }
        $id_shop = (int) Context::getContext()->shop->id;
        if ($isExitProduct) {
            $totalQantity = $conf_id ? $totalQantity : $totalQantity + $qty;
            $query = "UPDATE " . _DB_PREFIX_ . "stock_available SET quantity= '" . $totalQantity . "' WHERE id_product = " . $productId . " AND id_product_attribute='0'";
            Db::getInstance()->Execute($query);
            foreach ($attrId as $k => $v) {
                $sql = "INSERT INTO " . _DB_PREFIX_ . "stock_available (id_product,id_product_attribute,id_shop,id_shop_group,quantity,
                    out_of_stock) VALUES('$productId','$v','$id_shop','','$qty','2')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
            }
        } else {
            $sql = "INSERT INTO " . _DB_PREFIX_ . "stock_available (id_product,id_product_attribute,id_shop,id_shop_group,quantity,
                        out_of_stock) VALUES('$productId','','$id_shop','','$totalQantity','2')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
            foreach ($attrId as $k => $v) {
                $sql = "INSERT INTO " . _DB_PREFIX_ . "stock_available (id_product,id_product_attribute,id_shop,id_shop_group,quantity,
                    out_of_stock) VALUES('$productId','$v','$id_shop','','$qty','2')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
            }
        }
        return $status;
    }

    /**
     *Update product default attribute id
     *
     * @param (Int)productId
     * @param (Int)attrId
     * @return nothing
     *
     */
    private function upadteCacheProductAttrId($productId, $attrId)
    {
        $upadteSql = "UPDATE " . _DB_PREFIX_ . "product set cache_default_attribute =" . $attrId . " WHERE id_product = " . $productId . "";
        Db::getInstance()->Execute($upadteSql);
        $sql = "UPDATE " . _DB_PREFIX_ . "product_shop set cache_default_attribute =" . $attrId . " WHERE id_product = " . $productId . "";
        Db::getInstance()->Execute($sql);
    }

    public function addProductAttributesByProductIds($size_id, $productId, $sku, $color_id, $defaultOn, $id_shop)
    {
        $attrId = [];
        $attr_sql = "INSERT INTO " . _DB_PREFIX_ . "product_attribute(id_product,reference) VALUES('$productId','$sku')";
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($attr_sql);
        $attrId = $attr_id = Db::getInstance()->Insert_ID();
        //add product atttribute size and color
        if ($color_id) {
            $sqlColor = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_combination(id_attribute,id_product_attribute) VALUES('$color_id','$attr_id')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sqlColor);
        }
        $sqlSize = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_combination(id_attribute,id_product_attribute) VALUES('$size_id','$attr_id')";
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sqlSize);
        //ps_product_atrribute_shop
        if ($defaultOn) {
            $sql_pashop = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_shop(id_product,id_product_attribute,id_shop,default_on)
            VALUES(" . $productId . "," . $attr_id . "," . $id_shop . ", " . $defaultOn . ")";
        } else {
            $sql_pashop = "INSERT INTO " . _DB_PREFIX_ . "product_attribute_shop(id_product,id_product_attribute,id_shop)
            VALUES(" . $productId . "," . $attr_id . "," . $id_shop . ")";
        }
        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_pashop);

        return $attrId;

    }

    /**
     *add pre decorated  product iamge by product id
     *
     * @param (Int)productId
     * @param (Array)configFile
     * @param (String)product_name
     * @param (Int)lang_id
     * @param (Int)id_shop
     * @return  array
     *
     */
    public function addImageByProductId($configFile, $productId, $product_name, $lang_id, $id_shop, $isExitProduct = false)
    {
        if ($isExitProduct) {
            foreach ($configFile as $imageUrl) {
                $position = Image::getHighestPosition($productId) + 1;
                $image_sql1 = "INSERT INTO " . _DB_PREFIX_ . "image(id_product,position) VALUES('$productId','$position')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_sql1);
                $image_id[] = $id_iamge = Db::getInstance()->Insert_ID();
                //image_lang
                foreach ($lang_id as $v) {
                    $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_lang(id_image,id_lang,legend) VALUES('$id_iamge','" . $v['id_lang'] . "','$product_name')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                }
                //image_shop
                $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_shop(id_product,id_image,id_shop) VALUES('$productId','$id_iamge','$id_shop')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                //copy product image
                self::copyImg($productId, $id_iamge, $imageUrl, 'products', true);
            }
        } else {
            $i = 0;
            foreach ($configFile as $imageUrl) {
                $position = Image::getHighestPosition($productId) + 1;
                $cover = true; // or false;
                if ($i == 0) {
                    $image_sql = "INSERT INTO " . _DB_PREFIX_ . "image(id_product,position,cover) VALUES('$productId','$position','1')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_sql);
                    $image_id[] = $id_iamge = Db::getInstance()->Insert_ID();
                    //image_lang
                    foreach ($lang_id as $v1) {
                        $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_lang(id_image,id_lang,legend) VALUES('$id_iamge','" . $v1['id_lang'] . "','$product_name')";
                        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                    }
                    //image_shop
                    $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_shop(id_product,id_image,id_shop,cover) VALUES('$productId','$id_iamge','$id_shop','1')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                    //copy product image
                    self::copyImg($productId, $id_iamge, $imageUrl, 'products', true);
                } else {
                    $image_sql1 = "INSERT INTO " . _DB_PREFIX_ . "image(id_product,position) VALUES('$productId','$position')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_sql1);
                    $image_id[] = $id_iamge = Db::getInstance()->Insert_ID();
                    //image_lang
                    foreach ($lang_id as $v2) {
                        $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_lang(id_image,id_lang,legend) VALUES('$id_iamge','" . $v2['id_lang'] . "','$product_name')";
                        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                    }
                    //image_shop
                    $image_lan_sql = "INSERT INTO " . _DB_PREFIX_ . "image_shop(id_product,id_image,id_shop) VALUES('$productId','$id_iamge','$id_shop')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($image_lan_sql);
                    //copy product image
                    self::copyImg($productId, $id_iamge, $imageUrl, 'products', true);

                }
                $i++;
            }
        }
        return $image_id;
    }

    /**
     *copy product iamge
     *
     * @param (int)id_entity
     * @param (String)url
     * @param (int)id_image
     * @param (String)entity
     * @param (boolean)regenerate
     * @return boolean
     *
     */
    public function copyImg($id_entity, $id_image = null, $url, $entity = 'products', $regenerate = true)
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image($id_image);
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int) $id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int) $id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int) $id_entity;
                break;
        }
        $url = urldecode(trim($url));
        $parced_url = parse_url($url);
        if (isset($parced_url['path'])) {
            $uri = ltrim($parced_url['path'], '/');
            $parts = explode('/', $uri);
            foreach ($parts as &$part) {
                $part = rawurlencode($part);
            }
            unset($part);
            $parced_url['path'] = '/' . implode('/', $parts);
        }
        if (isset($parced_url['query'])) {
            $query_parts = array();
            parse_str($parced_url['query'], $query_parts);
            $parced_url['query'] = http_build_query($query_parts);
        }
        if (!function_exists('http_build_url')) {
            require_once _PS_TOOL_DIR_ . 'http_build_url/http_build_url.php';
        }
        $url = http_build_url('', $parced_url);
        $orig_tmpfile = $tmpfile;
        if (Tools::copy($url, $tmpfile)) {
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!ImageManager::checkImageMemoryLimit($tmpfile)) {
                @unlink($tmpfile);
                return false;
            }
            $tgt_width = $tgt_height = 0;
            $src_width = $src_height = 0;
            $error = 0;
            ImageManager::resize($tmpfile, $path . '.jpg', null, null, 'jpg', false, $error, $tgt_width, $tgt_height, 5,
                $src_width, $src_height);
            $images_types = ImageType::getImagesTypes($entity, true);
            if ($regenerate) {
                $previous_path = null;
                $path_infos = array();
                $path_infos[] = array($tgt_width, $tgt_height, $path . '.jpg');
                foreach ($images_types as $image_type) {
                    $tmpfile = self::get_best_paths($image_type['width'], $image_type['height'], $path_infos);

                    if (ImageManager::resize($tmpfile, $path . '-' . stripslashes($image_type['name']) . '.jpg', $image_type['width'],
                        $image_type['height'], 'jpg', false, $error, $tgt_width, $tgt_height, 5,
                        $src_width, $src_height)) {
                        // the last image should not be added in the candidate list if it's bigger than the original image
                        if ($tgt_width <= $src_width && $tgt_height <= $src_height) {
                            $path_infos[] = array($tgt_width, $tgt_height, $path . '-' . stripslashes($image_type['name']) . '.jpg');
                        }
                        if ($entity == 'products') {
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '.jpg');
                            }
                            if (is_file(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg')) {
                                unlink(_PS_TMP_IMG_DIR_ . 'product_mini_' . (int) $id_entity . '_' . (int) Context::getContext()->shop->id . '.jpg');
                            }
                        }
                    }
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
        } else {
            @unlink($orig_tmpfile);
            return false;
        }
        unlink($orig_tmpfile);
        return true;
    }
    /**
     *Get product image path from store
     *
     * @param Int($tgt_width)
     * @param Int($tgt_height)
     * @param Array($path_infos)
     * @return String
     *
     */
    public function get_best_paths($tgt_width, $tgt_height, $path_infos)
    {
        $path_infos = array_reverse($path_infos);
        $path = '';
        foreach ($path_infos as $path_info) {
            list($width, $height, $path) = $path_info;
            if ($width >= $tgt_width && $height >= $tgt_height) {
                return $path;
            }
        }
        return $path;
    }

    /**
     * addToCategories add this product to the category/ies if not exists.
     *
     * @param mixed $categories id_category or array of id_category
     * @return bool true if succeed
     */
    public function addToCategoriesToProduct($categories = array(), $productId)
    {

        if (!is_array($categories)) {
            $categories = array($categories);
        }
        $categories = array_map('intval', $categories);
        $current_categories = $this->getCategories();
        $current_categories = array_map('intval', $current_categories);
        // for new categ, put product at last position
        $res_categ_new_pos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT id_category, MAX(position)+1 newPos
            FROM `' . _DB_PREFIX_ . 'category_product`
            WHERE `id_category` IN(' . implode(',', $categories) . ')
            GROUP BY id_category');
        foreach ($res_categ_new_pos as $array) {
            $new_categories[(int) $array['id_category']] = (int) $array['newPos'];
        }
        $new_categ_pos = array();
        foreach ($categories as $id_category) {
            $new_categ_pos[$id_category] = isset($new_categories[$id_category]) ? $new_categories[$id_category] : 0;
        }
        $product_cats = array();
        foreach ($categories as $new_id_categ) {
            if (!in_array($new_id_categ, $current_categories)) {
                $product_cats[] = array(
                    'id_category' => (int) $new_id_categ,
                    'id_product' => (int) $productId,
                    'position' => (int) $new_categ_pos[$new_id_categ],
                );
            }
        }
        Db::getInstance()->insert('category_product', $product_cats);
    }

    public function getCategories()
    {
        try {
            $id_lang = Context::getContext()->language->id;
            $shop_id = Context::getContext()->shop->id;
            $sql = "SELECT DISTINCT c.id_category,cl.name FROM " . _DB_PREFIX_ . "category AS c," . _DB_PREFIX_ . "category_lang AS cl
            WHERE c.id_category = cl.id_category AND cl.id_lang='$id_lang' AND cl.id_shop='$shop_id' AND cl.name !='ROOT' AND c.id_parent = 2 order by c.id_category asc";
            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            $result = array();
            if (!empty($rows)) {
                foreach ($rows as $key => $value) {
                    $result[$key]['id'] = $value['id_category'];
                    $result[$key]['name'] = $value['name'];
                }
            }
            return json_encode(array('categories' => array_values($result)));
        } catch (PrestaShopDatabaseException $ex) {
            echo 'Other error: <br />' . $ex->getMessage();
        }

    }

    public function addCategoryByProduct($lang_ids)
    {
        $categoryName = array((int) Configuration::get('PS_LANG_DEFAULT') => 'ImprintNext');
        $description = 'Demo Category';
        $link_rewrite = array((int) Configuration::get('PS_LANG_DEFAULT') => 'imprintnext-tshirt');
        $meta_title = '';
        $meta_keywords = '';
        $meta_description = '';
        $data['id_parent'] = Configuration::get('PS_HOME_CATEGORY');
        $data['level_depth'] = $this->calcLevelDepth($data['id_parent']);
        $data['id_shop_default'] = (int) Context::getContext()->shop->id;
        $data['active'] = 1;
        $now = date('Y-m-d H:i:s', time());
        $data['date_add'] = $now;
        $data['date_upd'] = $now;
        $data['position'] = 1;
        $id_lang = Context::getContext()->language->id;
        $shop_id = Context::getContext()->shop->id;
        $sql = "SELECT id_category from " . _DB_PREFIX_ . "category_lang where name='" . $categoryName[1] . "' and id_shop=" . intval($shop_id) . " and id_lang =" . intval($id_lang) . "";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if ($result[0]['id_category']) {
            return $id_category = $result[0]['id_category'];
        } else {
            if (DB::getInstance()->insert('category', $data)) {
                $id_category = Db::getInstance()->Insert_ID();
                foreach ($lang_ids as $v) {
                    $datal['id_category'] = $id_category;
                    $datal['id_shop'] = (int) $shop_id;
                    $datal['id_lang'] = $v['id_lang'];
                    $datal['name'] = pSQL($categoryName[1]);
                    $datal['description'] = pSQL($description);
                    $datal['link_rewrite'] = pSQL($link_rewrite[1]);
                    $datal['meta_title'] = pSQL($meta_title);
                    $datal['meta_keywords'] = pSQL($meta_keywords);
                    $datal['meta_description'] = pSQL($meta_description);
                    if (!DB::getInstance()->insert('category_lang', $datal)) {
                        die('Error in category lang insert : ' . $id_category);
                    }

                }
                $dataShop['id_category'] = $id_category;
                $dataShop['id_shop'] = (int) $shop_id;
                $dataShop['position'] = 1;
                if (!DB::getInstance()->insert('category_shop', $dataShop)) {
                    die('Error in category shop insert : ' . $id_category);
                }
                $this->regenerateEntireNtreeCategory();
                $this->updateGroup($this->groupBox, $id_category);
            } else {
                die('Error in category insert : ' . $data['id_parent']);
            }
            return $id_category;
        }
    }

    /**
     *Generate entity tree category
     *
     * @param nothing
     * @return nothing
     *
     */
    public function regenerateEntireNtreeCategory()
    {
        $id = Context::getContext()->shop->id;
        $id_shop = $id ? $id : Configuration::get('PS_SHOP_DEFAULT');
        $categories = Db::getInstance()->executeS('
        SELECT c.`id_category`, c.`id_parent`
        FROM `' . _DB_PREFIX_ . 'category` c
        LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` cs
        ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $id_shop . ')
        ORDER BY c.`id_parent`, cs.`position` ASC');
        $categories_array = array();
        foreach ($categories as $category) {
            $categories_array[$category['id_parent']]['subcategories'][] = $category['id_category'];
        }
        $n = 1;
        if (isset($categories_array[0]) && $categories_array[0]['subcategories']) {
            $this->subTree($categories_array, $categories_array[0]['subcategories'][0], $n);
        }
    }

    /**
     *Assaign category under a category
     *
     * @param (Array)categories_array
     * @param (Int)id_category
     * @param (Int)n
     * @return nothing
     *
     */
    public function subTree(&$categories, $id_category, &$n)
    {
        $left = $n++;
        if (isset($categories[(int) $id_category]['subcategories'])) {
            foreach ($categories[(int) $id_category]['subcategories'] as $id_subcategory) {
                $this->subTree($categories, (int) $id_subcategory, $n);
            }
        }
        $right = (int) $n++;
        Db::getInstance()->execute('
        UPDATE ' . _DB_PREFIX_ . 'category
        SET nleft = ' . (int) $left . ', nright = ' . (int) $right . '
        WHERE id_category = ' . (int) $id_category . ' LIMIT 1');
    }

    public function updateGroup($list, $id_category)
    {
        $this->cleanGroups($id_category);
        if (empty($list)) {
            $list = array(Configuration::get('PS_UNIDENTIFIED_GROUP'), Configuration::get('PS_GUEST_GROUP'), Configuration::get('PS_CUSTOMER_GROUP'));
        }
        $this->addGroups($list, $id_category);
    }

    /**
     *Add category group
     *
     * @param (string)groups
     * @param (int)id_category
     * @return nothing
     *
     */
    public function addGroups($groups, $id_category)
    {
        foreach ($groups as $group) {
            if ($group !== false) {
                Db::getInstance()->insert('category_group', array('id_category' => (int) $id_category, 'id_group' => (int) $group));
            }
        }
    }

    public function cleanGroups($id_category)
    {
        return Db::getInstance()->delete('category_group', 'id_category = ' . (int) $id_category);
    }
    /*
    - Name : setBoundaryForDummyProduct
    - it will set boundary for newly created product.
    - Return status set or not
     */
    private function setBoundaryForDummyProduct($dom, $newProductID, $ParentData)
    {
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
        $insertProductSetting = "INSERT INTO product_settings(product_id,is_variable_decoration,is_ruler,is_crop_mark,is_safe_zone,crop_value,safe_value,is_3d_preview,3d_object_file,3d_object,scale_unit_id,store_id) VALUES(" . $newProductID . "," . $ParentData['is_variable_decoration'] . "," . $ParentData['is_ruler'] . "," . $ParentData['is_crop_mark'] . "," . $ParentData['is_safe_zone'] . "," . $ParentData['crop_value'] . "," . $ParentData['safe_value'] . "," . $ParentData['is_3d_preview'] . ",'" . $ParentData['3d_object_file'] . "','" . $ParentData['3d_object'] . "'," . $ParentData['scale_unit_id'] . ", 1)";
        $queryStatusPS = $conn->query($insertProductSetting);
        $prodSetID = mysqli_insert_id($conn);
        if ($queryStatusPS == false) {
            $errorMsg .= "- Data not inserted to domain_store_rel table. \n";
            $status = 0;
        }
        //Assign product image
        $productimageQRY = "INSERT INTO `product_image_settings_rel` (`product_setting_id`, `product_image_id`) VALUES (" . $prodSetID . "," . $ParentData['product_image_id'] . ")";
        $queryRun = $conn->query($productimageQRY);

        // insert print profile and product id relationship
        $insertRelation = "INSERT INTO print_profile_product_setting_rel(print_profile_id, product_setting_id) VALUES";
        foreach ($ParentData['print_profiles'] as $key => $rel) {
            if ($key > 0) {
                $insertRelation .= ", ";
            }
            $insertRelation .= "(" . $rel['id'] . "," . $prodSetID . ")";
        }
        $queryStatusPPM = $conn->query($insertRelation);

        // Insert sides into product_sides table and get side id
        foreach ($ParentData['sides'] as $side) {
            if ($side['index'] == '') {
                $side['index'] = 0;
            }
            $insertSideSetting = "INSERT INTO product_sides(product_setting_id,side_name,side_index,product_image_dimension,is_visible,product_image_side_id) VALUES(" . $prodSetID . ",'" . $side['name'] . "','" . $side['index'] . "','" . $side['dimension'] . "'," . $side['is_visible'] . "," . $side['image']['id'] . ")";
            $queryStatusS = $conn->query($insertSideSetting);

            $sideSetID = mysqli_insert_id($conn);
            if ($queryStatusS == false) {
                $errorMsg .= "- Data not inserted to domain_store_rel table. \n";
                $status = 0;
            }
            $setting = $side['decoration_settings'][0];
            // Insert data for each sides decoration settings
            $insertDecoSetting = "INSERT INTO product_decoration_settings(product_setting_id,product_side_id,name,dimension,print_area_id,sub_print_area_type,custom_min_height,custom_max_height,custom_min_width,custom_max_width,is_border_enable,is_sides_allow) VALUES(" . $prodSetID . "," . $sideSetID . ",'" . $setting['name'] . "','" . $setting['dimension'] . "','" . $setting['print_area_id'] . "','" . $setting['sub_print_area_type'] . "','" . 0 . "','" . 0 . "','" . 0 . "','" . 0 . "','" . $setting['is_border_enable'] . "','" . $setting['is_sides_allow'] . "')";
            $queryStatusDS = $conn->query($insertDecoSetting);
            $decoSetID = mysqli_insert_id($conn);

            $insertMethodSetRel = "INSERT INTO print_profile_decoration_setting_rel(print_profile_id, decoration_setting_id) VALUES";
            foreach ($setting['print_profiles'] as $key => $rel) {
                if ($key > 0) {
                    $insertMethodSetRel .= ", ";
                }
                $insertMethodSetRel .= "(" . $rel['id'] . "," . $decoSetID . ")";
            }
            $queryStatusPDM = $conn->query($insertMethodSetRel);
        }
        return $status;
    }

    /**
     * Get the depth level for the category
     *
     * @return int Depth level
     */
    public function calcLevelDepth($id_parent)
    {
        /* Root category */
        if (!$id_parent) {
            return 0;
        }
        $parent_category = new Category((int) $id_parent);
        if (!Validate::isLoadedObject($parent_category)) {
            throw new PrestaShopException('Parent category does not exist');
        }
        return $parent_category->level_depth + 1;
    }

    public function copyStoreThemeFiles()
    {
        if (!@copy(ROOTABSPATH . strtolower(STORETYPE) . '/frontendlc.php', DOCABSPATH . 'frontendlc.php')) {
            $errorMsg = '- frontendlc.php file didn\'t copy. \n';
            $status = 0;
        }
        if (!file_exists(DOCABSPATH . "PrestaShop-webservice-lib-master")) {
            mkdir(DOCABSPATH . 'PrestaShop-webservice-lib-master', 0755, true);
        }
        $this->recurse_copy(ROOTABSPATH . "prestashop/PrestaShop-webservice-lib-master", DOCABSPATH . "PrestaShop-webservice-lib-master");
        $this->recurse_copy(ROOTABSPATH . "prestashop/modules", DOCABSPATH . "modules");
        if (_PS_VERSION_ <= '1.7.3.4') {
            $this->recurse_copy(ROOTABSPATH . "prestashop/themes", DOCABSPATH . "themes");
        } else {
            if (_PS_VERSION_ <= '1.7.4.4') {
                $this->recurse_copy(ROOTABSPATH . "prestashop/theme1740", DOCABSPATH . "themes");
            } else {
                //For PS v1.7.4.5 to above
                $this->recurse_copy(ROOTABSPATH . "prestashop/theme1750", DOCABSPATH . "themes");
            }
        }
        if (_PS_VERSION_ >= '1.7.5.0') {
            $this->recurse_copy(ROOTABSPATH . "prestashop/src", DOCABSPATH . "src");
        }
    }

    /*
    @ Purpose : Recursively copy all the files & folders
    @ Param : SourceFolder and DestinationFolder with path
     */
    protected function recurse_copy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    @copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function checkAllStoreFiles()
    {
        $status = 0;
        //check if  all prestashop module folders are copied or not
        $folderArrayPrestashop = array(
            DOCABSPATH . "modules/imprintnextcustomprice",
            DOCABSPATH . "modules/imprintnextdesignertool",
            DOCABSPATH . "modules/imprintnextorderdetials",
            DOCABSPATH . "PrestaShop-webservice-lib-master",
        );
        foreach ($folderArrayPrestashop as $fileFolderPrestashop) {
            if (!is_dir($fileFolderPrestashop)) {
                $status = 1;
            }
        }
        //check if  all prestashop module files are copied or not
        $fileArrayPrestashop = array(
            DOCABSPATH . "modules/imprintnextcustomprice/imprintnextcustomprice.php",
            DOCABSPATH . "modules/imprintnextdesignertool/imprintnextdesignertool.php",
            DOCABSPATH . "modules/imprintnextorderdetials/imprintnextorderdetials.php",
            DOCABSPATH . "PrestaShop-webservice-lib-master/PSWebServiceLibrary.php",
        );
        foreach ($fileArrayPrestashop as $filePrestashop) {
            if (!file_exists($filePrestashop)) {
                $status = 1;
            }
        }
        return $status;
    }

    public function addWebServiceKey($xetoolDir)
    {
        $id = Context::getContext()->shop->id;
        $id_shop = $id ? $id : Configuration::get('PS_SHOP_DEFAULT');
        $value = '1';
        $result = 0;
        $date = date('Y-m-d H:i:s', time());

        //Set configuration for xetool dir
        $psXeTool = 'PS_XETOOL';
        $checkXetoolSql = "SELECT COUNT(*) AS nos from " . _DB_PREFIX_ . "configuration where name = '" . $psXeTool . "'";
        $rowXetool = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($checkXetoolSql);
        if ($rowXetool[0]['nos']) {
            $queryXetool = "UPDATE " . _DB_PREFIX_ . "configuration SET value= '" . $xetoolDir . "',date_upd='" . $date . "' WHERE name = '" . $psXeTool . "'";
            Db::getInstance()->Execute($queryXetool);
        } else {
            $xeToolInsertSql = "INSERT INTO `" . _DB_PREFIX_ . "configuration` (`name`,`value`,`date_add`,`date_upd`)
                VALUES ('" . $psXeTool . "','" . $xetoolDir . "','" . $date . "','" . $date . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($xeToolInsertSql);
        }

        //Check prestashop web service
        $name = 'PS_WEBSERVICE';
        $checkSql = "select COUNT(*) AS nos from " . _DB_PREFIX_ . "configuration where name = '" . $name . "'";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($checkSql);
        if ($row[0]['nos']) {
            $query = "UPDATE " . _DB_PREFIX_ . "configuration SET value= '" . $value . "',date_upd='" . $date . "' WHERE name = '" . $name . "'";
            Db::getInstance()->Execute($query);
        } else {
            $sql_insert = "INSERT INTO `" . _DB_PREFIX_ . "configuration` (`name`,`value`,`date_add`,`date_upd`)
                VALUES ('" . $name . "','" . $value . "','" . $date . "','" . $date . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_insert);
        }
        $key = $this->randString(); //create random string
        $description = 'imprintnext webservicekey';
        $className = 'WebserviceRequest';
        $resourceArr = array();
        $resourceArr = '{"resource_list": [{"name": "addresses","method": ["GET"]},
            {"name": "categories","method": ["GET", "POST", "PUT"]},
            {"name": "countries","method": ["GET"]},
            {"name": "customers","method": ["GET"]},
            {"name": "order_details","method": ["GET", "POST", "PUT"]},
            {"name": "order_states","method": ["GET", "POST", "PUT"]},
            {"name": "orders","method": ["GET", "POST", "PUT"]},
            {"name": "order_carriers","method": ["GET", "POST", "PUT"]},
            {"name": "order_payments","method": ["GET", "POST", "PUT"]},
            {"name": "order_slip","method": ["GET", "POST", "PUT"]},
            {"name": "order_invoices","method": ["GET", "POST", "PUT"]},
            {"name": "order_histories","method": ["GET", "POST", "PUT"]},
            {"name": "products","method": ["GET", "POST", "PUT"]},
            {"name": "states","method": ["GET"]},
            {"name": "stock_availables","method": ["GET", "POST", "PUT"]}]}';
        $resourceArr = json_decode($resourceArr, true);
        $sqlWebService = "select `key` from `" . _DB_PREFIX_ . "webservice_account` where description = '" . $description . "'";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlWebService);
        if (empty($row[0]['key'])) {
            $insert_sql = "INSERT INTO `" . _DB_PREFIX_ . "webservice_account` (`key`,`description`,`class_name`,`active`) VALUES('" . $key . "','" . $description . "','" . $className . "',1)";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($insert_sql);
            $webserviceId = Db::getInstance()->Insert_ID();
            $sql = "INSERT INTO " . _DB_PREFIX_ . "webservice_account_shop (id_webservice_account,id_shop) VALUES(" . $webserviceId . "," . $id_shop . ")";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
            foreach ($resourceArr['resource_list'] as $v) {
                foreach ($v['method'] as $v1) {
                    $sql_resurce = "INSERT INTO " . _DB_PREFIX_ . "webservice_permission (resource,method,id_webservice_account) VALUES('" . $v['name'] . "','" . $v1 . "'," . $webserviceId . ")";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_resurce);
                }
            }
        } else {
            $key = $row[0]['key'];
        }
        $url = $this->getStoreUrl();
        $xeStoreUrl = $url['xe_store_url'] . $xetoolDir;
        $result = $this->addCmsPageActivation($xeStoreUrl);
        $result = [
            'ps_ws_auth_key' => $key,
            'ps_shop_path' => $url['xe_store_url'],
        ];
        return $result;
    }

    public function getStoreUrl()
    {
        $custom_ssl_var = 0;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        }

        if ((bool) Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $baseUrl = _PS_BASE_URL_SSL_;
            $xeStoreUrl = _PS_BASE_URL_SSL_ . __PS_BASE_URI__;
        } else {
            $baseUrl = _PS_BASE_URL_;
            $xeStoreUrl = _PS_BASE_URL_ . __PS_BASE_URI__;
        }
        $result['base_url'] = $baseUrl;
        $result['xe_store_url'] = $xeStoreUrl;
        return $result;
    }

    private function randString()
    {
        $length = 32;
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
        $str = '';
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count - 1)];
        }
        return $str;
    }

    /*
    @ Purpose :Add prestashop cms page in step1
     */
    private function addCmsPageActivation($xeStoreUrl)
    {
        $value = '1';
        $result = 0;
        $errorMsg = '';
        $id = Context::getContext()->shop->id;
        $id_shop = $id ? $id : (int) Configuration::get('PS_SHOP_DEFAULT');
        $date = date('Y-m-d H:i:s', time());
        $name = 'PS_ALLOW_HTML_IFRAME';
        $sql_check = "select COUNT(*) AS nos from " . _DB_PREFIX_ . "configuration
                 where name = '" . $name . "'";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_check);
        if ($row[0]['nos']) {
            $query = "UPDATE " . _DB_PREFIX_ . "configuration SET value= '" . $value . "',date_upd='" . $date . "' WHERE name = '" . $name . "'";
            Db::getInstance()->Execute($query);
        } else {
            $sql_insert = "INSERT INTO `" . _DB_PREFIX_ . "configuration` (`name`,`value`,`date_add`,`date_upd`)
                VALUES ('" . $name . "'," . $value . ",'" . $date . "','" . $date . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_insert);
        }

        $sql_chk = "select COUNT(*) AS nos from " . _DB_PREFIX_ . "cms_lang
                 where meta_title = 'Designer Tool'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_chk);
        if (empty($rows[0]['nos'])) {
            $select_sql = "SELECT MAX( position ) FROM " . _DB_PREFIX_ . "cms ";
            $result_data = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($select_sql);
            $sql_id = "SELECT id_cms_category FROM " . _DB_PREFIX_ . "cms_category_lang WHERE name='Home'";
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_id);
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'cms` (`id_cms_category`,`position`, `active`,`indexation`) VALUES (' . intval($row[0]['id_cms_category']) . "," . intval($result_data['0']['MAX( position )'] + 1) . ",1,0)";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
            $id_cms = Db::getInstance()->Insert_ID();
            $meta_title = "Designer Tool";
            $content = '<p><iframe width="100%" height="784" id="tshirtIFrame" src="' . $xeStoreUrl . '/index.html"></iframe></p>';
            $link_rewrite = 'designer-tool';
            $id = Context::getContext()->shop->id;
            $idl = Context::getContext()->language->id;
            $id_lang = $idl ? $idl : (int) Configuration::get('PS_LANG_DEFAULT');
            $context = \Context::getContext();
            $langArr = Language::getLanguages(true, $context->shop->id);
            if (!empty($langArr)) {
                $sql_cms = '';
                foreach ($langArr as $key => $v) {
                    $sql_cms = "INSERT INTO " . _DB_PREFIX_ . "cms_lang (id_cms,id_lang,id_shop,meta_title,meta_description,meta_keywords,content,link_rewrite)
                    VALUES (" . $id_cms . "," . $v['id_lang'] . "," . $id_shop . ",'" . $meta_title . "','','','" . $content . "','" . $link_rewrite . "')";
                    Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_cms);
                }
            } else {
                $sql_cms = "INSERT INTO " . _DB_PREFIX_ . "cms_lang (id_cms,id_lang,id_shop,meta_title,meta_description,meta_keywords,content,link_rewrite)
                VALUES (" . $id_cms . "," . $id_lang . "," . $id_shop . ",'" . $meta_title . "','','','" . $content . "','" . $link_rewrite . "')";
                Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_cms);
            }
            $sql_cms_shop = 'INSERT INTO `' . _DB_PREFIX_ . 'cms_shop` (`id_cms`,`id_shop`) VALUES (' . intval($id_cms) . "," . intval($id_shop) . ")";
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_cms_shop);
        } else {
            $result = 1;
        }
        return $result;
    }

    public function getAttributeSet()
    {
        $id_lang = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        $sql = "SELECT agl.id_attribute_group,agl.name,agl.public_name from " . _DB_PREFIX_ . "attribute_group_lang AS agl," . _DB_PREFIX_ . "attribute_group_shop AS ags WHERE agl.id_attribute_group = ags.id_attribute_group AND agl.id_lang =" . $id_lang . " AND  ags.id_shop = " . $id_shop . "";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $result = array();
        if (!empty($row)) {
            $i = 0;
            foreach ($row as $v) {
                $result[$i]['id'] = $v['id_attribute_group'];
                $result[$i]['name'] = $v['name'];
                $result[$i]['public_name'] = $v['public_name'];
                $i++;
            }
        }
        return $result;
    }

    protected function getDummyProductURL($dom)
    {
        $id_lang = Context::getContext()->language->id;
        $shop_id = Context::getContext()->shop->id;
        $sql = "SELECT * from " . _DB_PREFIX_ . "category_lang WHERE name='ImprintNext' AND id_shop=" . intval($shop_id) . " AND id_lang =" . intval($id_lang) . "";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($result)) {
            $idCategory = $result[0]['id_category'];
            $linkRewrite = $result[0]['link_rewrite'];
            $link = new \Link();
            $url = $link->getCategoryLink($idCategory, $linkRewrite);
        } else {
            $custom_ssl_var = 0;
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $custom_ssl_var = 1;
            }
            if ((bool) Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
                $url = _PS_BASE_URL_SSL_;
            } else {
                $url = _PS_BASE_URL_;
            }
        }
        return $url;
    }

    public function alterProdutTable()
    {
        $status = 0;
        $status = 0;
        $sqlCatalog = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'is_catalog'";
        $rowscatalog = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlCatalog);
        if (!empty($rowscatalog)) {
            $sqlCatalogDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "product DROP `is_catalog`";
            $status = Db::getInstance()->Execute($sqlCatalogDropColumn);
            //Alter column
            $sqlCatalogAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `is_catalog` tinyint(1) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlCatalogAlter);
        } else {
            $sqlCatalogAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `is_catalog` tinyint(1) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlCatalogAlter);
        }
        $sql = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'xe_is_temp'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($rows)) {
            $sqlDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "product DROP `xe_is_temp`";
            $status = Db::getInstance()->Execute($sqlDropColumn);
            //Alter column
            $sqlAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `xe_is_temp` INT(20) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlAlter);
        } else {
            $sqlAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `xe_is_temp` INT(20) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlAlter);
        }
        $sql_alter_check = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'is_addtocart'";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_alter_check);
        if (!empty($result)) {
            $status = 1;
        } else {
            $sql_alter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `is_addtocart` tinyint(1) DEFAULT 0";
            $status = Db::getInstance()->Execute($sql_alter);
        }
        $sql_pa = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product_attribute LIKE 'xe_is_temp'";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_pa);
        if (!empty($row)) {
            $status = 1;
        } else {
            $sql1 = "ALTER TABLE " . _DB_PREFIX_ . "product_attribute ADD COLUMN `xe_is_temp` enum('0', '1') DEFAULT '0'";
            $status = Db::getInstance()->Execute($sql1);
        }
        $sql_pca = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product_attribute_combination LIKE 'xe_is_temp'";
        $rows1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_pca);
        if (!empty($rows1)) {
            $status = 1;
        } else {
            $sql2 = "ALTER TABLE " . _DB_PREFIX_ . "product_attribute_combination ADD COLUMN `xe_is_temp` enum('0', '1') DEFAULT '0'";
            $status = Db::getInstance()->Execute($sql2);
        }
        return $msg = $status ? $status : 0;
    }

    public function addCustomColumnProduct()
    {
        $sqlCheck = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "orders LIKE 'ref_id'";
        $rowsCheck = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlCheck);
        if (empty($rowsCheck)) {
            $sqlAlter = 'ALTER TABLE ' . _DB_PREFIX_ . 'orders ADD ref_id int(10) unsigned NOT NULL';
            $status = Db::getInstance()->Execute($sqlAlter);
        }
        $sqlOrderDetails = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "order_detail LIKE 'ref_id'";
        $rowOrderDetails = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlOrderDetails);
        if (!empty($rowOrderDetails)) {
            $sqlDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "order_detail DROP `ref_id`";
            $status = Db::getInstance()->Execute($sqlDropColumn);
            //Alter column
            $sqlOdAlter = "ALTER TABLE " . _DB_PREFIX_ . "order_detail ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlOdAlter);
        } else {
            $sqlOdAlter = "ALTER TABLE " . _DB_PREFIX_ . "order_detail ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlOdAlter);
        }

        $sql_product = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'customize'";
        $row_product = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_product);
        if (empty($row_product)) {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product ADD COLUMN customize tinyint(1) DEFAULT 0';
            Db::getInstance()->Execute($sql);
        }
        $sqlCartProduct = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "cart_product LIKE 'ref_id'";
        $rowCartProduct = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlCartProduct);
        if (!empty($rowCartProduct)) {
            $sqlDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "cart_product DROP `ref_id`";
            $status = Db::getInstance()->Execute($sqlDropColumn);
            //Alter column
            $sqlCpAlter = "ALTER TABLE " . _DB_PREFIX_ . "cart_product ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlCpAlter);
        } else {
            $sqlCpAlter = "ALTER TABLE " . _DB_PREFIX_ . "cart_product ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlCpAlter);
        }
    }
    /**
     *Create table for predeco and product relation
     *
     * @param nothing
     * @return nothing
     *
     */
    public function createNewTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_predeco_rel` (
        `pk_id` int(20) NOT NULL AUTO_INCREMENT,
        `parent_product_id` int(20) NOT NULL,
        `parent_variant_id` int(20) NOT NULL,
        `new_product_id` int(20) NOT NULL,
        `new_variant_id` int(20) NOT NULL,
        PRIMARY KEY (`pk_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8';
        Db::getInstance()->Execute($sql);
    }

    public function alterTable()
    {
        $sql_insert = "ALTER TABLE " . _DB_PREFIX_ . "cart_product DROP PRIMARY KEY,
                ADD PRIMARY KEY (`id_cart`, `id_product`, `id_address_delivery`, `id_product_attribute`, `ref_id`)";
        Db::getInstance()->Execute($sql_insert);
    }

    /**
     *To update between two hooks position after installation a module
     *
     * @param nothing
     * @return nothing
     *
     */
    public function tableValueInterChange()
    {
        $sql_hook = "SELECT id_hook from " . _DB_PREFIX_ . "hook where name='displayProductButtons' ";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_hook);
        $sql_module = "SELECT id_module from " . _DB_PREFIX_ . "module where name='imprintnextdesignertool' ";
        $row_module = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_module);

        $sql_hook1 = "SELECT id_module from " . _DB_PREFIX_ . "module where name='productpaymentlogos' ";
        $row_module1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_hook1);
        $sql = "UPDATE
        " . _DB_PREFIX_ . "hook_module AS rule1
        JOIN " . _DB_PREFIX_ . "hook_module AS rule2 ON
        ( rule1.id_module = " . $row_module1[0]['id_module'] . " AND rule2.id_module = " . $row_module[0]['id_module'] . " AND rule1.id_hook = " . $row[0]['id_hook'] . " AND rule2.id_hook = " . $row[0]['id_hook'] . ")
        OR ( rule1.id_module = " . $row_module[0]['id_module'] . " AND rule2.id_module = " . $row_module1[0]['id_module'] . " AND rule1.id_hook = " . $row[0]['id_hook'] . " AND rule2.id_hook = " . $row[0]['id_hook'] . ")
        SET
        rule1.position = rule2.position,
        rule2.position = rule1.position";
        Db::getInstance()->Execute($sql);
    }

    /**
     *Module installed in backend store admin
     *
     * @param nothing
     * @return sting
     *
     */
    public function installCustomModules()
    {
        $this->alterProdutTable();
        $this->addCustomColumnProduct();
        $module_list = array("module_list" => array(
            array(
                "name" => "imprintnextcustomprice",
                "version" => "1.0.0",
                "author" => "ImprintNext",
                "tab" => "customprice",
                "hook_name" => array("actionProductUpdate", "displayFooterProduct", "actionProductDelete", "actionCartSave"),
            ),
            array("name" => "imprintnextdesignertool",
                "version" => "1.0.0",
                "author" => "ImprintNext",
                "tab" => "designertool",
                "hook_name" => array("displayProductButtons", "newOrder", "actionOrderStatusPostUpdate", "displayShoppingCartFooter", "actionAdminControllerSetMedia", "actionProductUpdate", "displayAdminProductsExtra", "displayInvoice"),
            ),
            array("name" => "imprintnextorderdetials",
                "version" => "1.0.0",
                "author" => "ImprintNext",
                "tab" => "orderdetials",
                "hook_name" => array("orderConfirmation"),
            ),
        ));
        $resultData = Module::isInstalled($module_list['module_list'][0]['name']);
        if (empty($resultData)) {
            $this->createNewTable();
            $this->addTableAndColumn(); //to call to create table
            $this->alterTable(); //alter a table and a composite key
            foreach ($module_list['module_list'] as $k => $v) {
                $this->name = $v['name'];
                $hook_name = $v['hook_name'];
                $version = $v['version'];
                $shop_list = null;
                $return = true;
                if (is_array($hook_name)) {
                    $hook_names = $hook_name;
                } else {
                    $hook_names = array($hook_name);
                }
                //module in install
                //Check module name validation
                if (!Validate::isModuleName($this->name)) {
                    $msg = 'Unable to install the module (Module name is not valid).';
                }
                // Check PS version compliancy
                if (!$this->checkCompliancy()) {
                    $msg = 'The version of your module is not compliant with your PrestaShop version.';
                }
                // Check if module is installed
                $result = Module::isInstalled($this->name);
                if ($result) {
                    $msg = 'This module has already been installed';
                }
                // Install overrides
                if ($v['name'] == 'imprintnextcustomprice' || $v['name'] == 'imprintnextorderdetials') {
                    $this->installOverride($this->name);
                }
                // Install module and retrieve the installation id
                $result = Db::getInstance()->insert('module', array(
                    'name' => $this->name,
                    'active' => 1,
                    'version' => $version,
                ));
                if (!$result) {
                    $msg = 'Technical error: PrestaShop could not install this module.';
                }
                $this->id = Db::getInstance()->Insert_ID();
                Cache::clean('Module::isInstalled' . $this->name);
                // Enable the module for current shops in context
                $this->enable();
                // Permissions management
                if (_PS_VERSION_ <= '1.7.3.4') {
                    Db::getInstance()->execute('
                        INSERT INTO `' . _DB_PREFIX_ . 'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
                            SELECT id_profile, ' . (int) $this->id . ', 1, 1, 1
                            FROM ' . _DB_PREFIX_ . 'access a
                            WHERE id_tab = (
                                SELECT `id_tab` FROM ' . _DB_PREFIX_ . 'tab
                                WHERE class_name = \'AdminModules\' LIMIT 1)
                            AND a.`view` = 1)');

                    Db::getInstance()->execute('
                        INSERT INTO `' . _DB_PREFIX_ . 'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
                            SELECT id_profile, ' . (int) $this->id . ', 1, 0, 0
                            FROM ' . _DB_PREFIX_ . 'access a
                            WHERE id_tab = (
                                SELECT `id_tab` FROM ' . _DB_PREFIX_ . 'tab
                                WHERE class_name = \'AdminModules\' LIMIT 1)
                            AND a.`view` = 0)');
                }

                // Adding Restrictions for client groups
                Group::addRestrictionsForModule($this->id, Shop::getShops(true, null, true));
                Hook::exec('actionModuleInstallAfter', array('object' => $this));
                //end module intasll//
                foreach ($hook_names as $hook_name) {
                    if (!isset($this->id) || !is_numeric($this->id)) {
                        return false;
                    }

                    // Retrocompatibility
                    $hook_name_bak = $hook_name;
                    if ($alias = Hook::getRetroHookName($hook_name)) {
                        $hook_name = $alias;
                    }
                    // Get hook id
                    $id_hook = Hook::getIdByName($hook_name);
                    // If hook does not exist, we create it
                    if (!$id_hook) {
                        $new_hook = new Hook();
                        $new_hook->name = pSQL($hook_name);
                        $new_hook->title = pSQL($hook_name);
                        $new_hook->live_edit = (bool) preg_match('/^display/i', $new_hook->name);
                        $new_hook->position = (bool) $new_hook->live_edit;
                        $new_hook->add();
                        $id_hook = $new_hook->id;
                        if (!$id_hook) {
                            return false;
                        }
                    }
                    // If shop lists is null, we fill it with all shops
                    if (is_null($shop_list)) {
                        $shop_list = Shop::getCompleteListOfShopsID();
                    }

                    $shop_list_employee = Shop::getShops(true, null, true);

                    foreach ($shop_list as $shop_id) {
                        // Check if already register
                        $sql = 'SELECT hm.`id_module`
                            FROM `' . _DB_PREFIX_ . 'hook_module` hm, `' . _DB_PREFIX_ . 'hook` h
                            WHERE hm.`id_module` = ' . (int) $this->id . ' AND h.`id_hook` = ' . $id_hook . '
                            AND h.`id_hook` = hm.`id_hook` AND `id_shop` = ' . (int) $shop_id;
                        if (Db::getInstance()->getRow($sql)) {
                            continue;
                        }
                        // Get module position in hook
                        $sql = 'SELECT MAX(`position`) AS position
                            FROM `' . _DB_PREFIX_ . 'hook_module`
                            WHERE `id_hook` = ' . (int) $id_hook . ' AND `id_shop` = ' . (int) $shop_id;
                        if (!$position = Db::getInstance()->getValue($sql)) {
                            $position = 0;
                        }
                        // Register module in hook
                        $return &= Db::getInstance()->insert('hook_module', array(
                            'id_module' => (int) $this->id,
                            'id_hook' => (int) $id_hook,
                            'id_shop' => (int) $shop_id,
                            'position' => (int) ($position + 1),
                        ));

                        if (!in_array($shop_id, $shop_list_employee)) {
                            $where = '`id_module` = ' . (int) $this->id . ' AND `id_shop` = ' . (int) $shop_id;
                            $return &= Db::getInstance()->delete('module_shop', $where);
                        }
                    }
                }
            }
            if ($this->id) {
                if (_PS_VERSION_ <= '1.7.3.4') {
                    $this->tableValueInterChange();
                }
                $msg = "Module installed";
            }
        } else {
            $msg = "Module installed";
        }
        return $msg;
    }

    /**
     * Install overrides files for the module
     *
     * @return bool
     */
    public function installOverride($name)
    {
        if ($name == 'imprintnextcustomprice' || $name == 'imprintnextorderdetials') {
            if ($name == 'imprintnextorderdetials') {
                $override = array('classes/order/OrderDetail.php', 'classes/order/Order.php');
            } else {
                $override = array('classes/Cart.php', 'classes/Product.php', 'controllers/front/CartController.php');
            }
            foreach ($override as $file) {
                $explode = explode("/", $file);
                $file_name = $explode[count($explode) - 1];
                unset($explode[count($explode) - 1]);
                $folder = implode("/", $explode);
                @mkdir(_PS_OVERRIDE_DIR_ . $folder, 0777, true);
                if (_PS_VERSION_ <= '1.7.4.4' || $name == 'imprintnextorderdetials') {
                    @copy(_PS_MODULE_DIR_ . $name . '/override/' . $folder . "/" . $file_name, _PS_OVERRIDE_DIR_ . $folder . "/" . $file_name);
                } else {
                    @copy(_PS_MODULE_DIR_ . $name . '/overrides/' . $folder . "/" . $file_name, _PS_OVERRIDE_DIR_ . $folder . "/" . $file_name);
                }
                $old = @umask(0);
                @chmod(_PS_OVERRIDE_DIR_ . $folder . "/" . $file_name, 0777);
                @umask($old);
            }
        } else {
            return true;
        }

    }
    /**
     *Uninstall overides file
     *
     * @param nothing
     * @return boolean
     *
     */
    public function uninstallOverrides()
    {
        if (!is_dir($this->getLocalPath() . 'override')) {
            return true;
        }
        $result = true;
        foreach (Tools::scandir($this->getLocalPath() . 'override', 'php', '', true) as $file) {
            $class = basename($file, '.php');
            if (PrestaShopAutoload::getInstance()->getClassPath($class . 'Core') || Module::getModuleIdByName($class)) {
                $result &= $this->removeOverride($class);
            }
        }
        return $result;
    }
    /**
     *Check PS version compliancy
     *
     * @param nothing
     * @return boolean
     *
     */
    public function checkCompliancy()
    {
        if (version_compare(_PS_VERSION_, $this->ps_versions_compliancy['min'], '<') || version_compare(_PS_VERSION_, $this->ps_versions_compliancy['max'], '>')) {
            return false;
        } else {
            return true;
        }
    }
    /**
     *Create table for custom price table
     *
     * @param nothing
     * @return nothing
     *
     */
    public function addTableAndColumn()
    {
        $sqlDrop = array();
        $sqlDrop[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'imprintnext_cart_custom_price`';
        foreach ($sqlDrop as $v) {
            Db::getInstance()->Execute($v);
        }

        $sql = array();
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'imprintnext_cart_custom_price` (
              `id_cart` int(10) unsigned NOT NULL,
              `id_product` int(10) unsigned NOT NULL,
              `id_product_attribute` int(10) unsigned NOT NULL,
              `id_shop` int(10) unsigned NOT NULL,
              `custom_price` decimal(20,6) NOT NULL,
              `ref_id` VARCHAR(250) NOT NULL,
              PRIMARY KEY  (`id_cart`, `id_product`, `id_product_attribute`, `id_shop`, `ref_id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';
        foreach ($sql as $_sql) {
            Db::getInstance()->Execute($_sql);
        }
    }
    /**
     * Install module's controllers using public property $controllers
     * @return bool
     */
    private function installControllers()
    {
        $themes = Theme::getThemes();
        $theme_meta_value = array();
        foreach ($this->controllers as $controller) {
            $page = 'module-' . $this->name . '-' . $controller;
            $result = Db::getInstance()->getValue('SELECT * FROM ' . _DB_PREFIX_ . 'meta WHERE page="' . pSQL($page) . '"');
            if ((int) $result > 0) {
                continue;
            }

            $meta = new Meta();
            $meta->page = $page;
            $meta->configurable = 1;
            $meta->save();
            if ((int) $meta->id > 0) {
                foreach ($themes as $theme) {
                    /** @var Theme $theme */
                    $theme_meta_value[] = array(
                        'id_theme' => $theme->id,
                        'id_meta' => $meta->id,
                        'left_column' => (int) $theme->default_left_column,
                        'right_column' => (int) $theme->default_right_column,
                    );
                }
            } else {
                $this->_errors[] = sprintf(Tools::displayError('Unable to install controller: %s'), $controller);
            }
        }
        if (count($theme_meta_value) > 0) {
            return Db::getInstance()->insert('theme_meta', $theme_meta_value);
        }
        return true;
    }
    /**
     * Activate current module.
     *
     * @param bool $force_all If true, enable module for all shop
     */
    public function enable($force_all = false)
    {
        // Retrieve all shops where the module is enabled
        $list = Shop::getContextListShopID();
        if (!$this->id || !is_array($list)) {
            return false;
        }
        $sql = 'SELECT `id_shop` FROM `' . _DB_PREFIX_ . 'module_shop`
                WHERE `id_module` = ' . (int) $this->id .
            ((!$force_all) ? ' AND `id_shop` IN(' . implode(', ', $list) . ')' : '');
        // Store the results in an array
        $items = array();
        if ($results = Db::getInstance($sql)->executeS($sql)) {
            foreach ($results as $row) {
                $items[] = $row['id_shop'];
            }
        }
        // Enable module in the shop where it is not enabled yet
        foreach ($list as $id) {
            if (!in_array($id, $items)) {
                Db::getInstance()->insert('module_shop', array(
                    'id_module' => $this->id,
                    'id_shop' => $id,
                ));
            }
        }
        return true;
    }
    /**
     *Update translation after instalation mudule
     *
     * @param nothing
     * @return boolean
     *
     */
    public static function updateTranslationsAfterInstall($update = true)
    {
        Module::$update_translations_after_install = (bool) $update;
    }
    /**
     *Update translation after instalation mudule
     *
     * @param nothing
     * @return boolean
     *
     */
    public function updateModuleTranslations()
    {
        return Language::updateModulesTranslations(array($this->name));
    }
    /**
     * Install overrides files for the module
     *
     * @return bool
     */
    public function installOverrides($name)
    {
        if (!is_dir($this->getLocalPath($name) . 'override')) {
            return true;
        }
        $result = true;
        foreach (Tools::scandir($this->getLocalPath($name) . 'override', 'php', '', true) as $file) {
            $class = basename($file, '.php');
            if (PrestaShopAutoload::getInstance()->getClassPath($class . 'Core') || Module::getModuleIdByName($class)) {
                $result &= $this->addOverride($class, $name);
            }
        }
        return $result;
    }
    /**
     * Add all methods in a module override to the override class
     *
     * @param string $classname
     * @return bool
     */
    public function addOverride($classname, $name)
    {
        $orig_path = $path = PrestaShopAutoload::getInstance()->getClassPath($classname . 'Core');
        if (!$path) {
            $path = 'modules' . DIRECTORY_SEPARATOR . $classname . DIRECTORY_SEPARATOR . $classname . '.php';
        }
        $path_override = $this->getLocalPath($name) . 'override' . DIRECTORY_SEPARATOR . $path;
        if (!file_exists($path_override)) {
            return false;
        } else {
            file_put_contents($path_override, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($path_override)));
        }
        $pattern_escape_com = '#(^\s*?\/\/.*?\n|\/\*(?!\n\s+\* module:.*?\* date:.*?\* version:.*?\*\/).*?\*\/)#ism';
        // Check if there is already an override file, if not, we just need to copy the file
        if ($file = PrestaShopAutoload::getInstance()->getClassPath($classname)) {
            // Check if override file is writable
            $override_path = _PS_ROOT_DIR_ . '/' . $file;

            if ((!file_exists($override_path) && !is_writable(dirname($override_path))) || (file_exists($override_path) && !is_writable($override_path))) {
                throw new Exception(sprintf(Tools::displayError('file (%s) not writable'), $override_path));
            }

            // Get a uniq id for the class, because you can override a class (or remove the override) twice in the same session and we need to avoid redeclaration
            do {
                $uniq = uniqid();
            } while (class_exists($classname . 'OverrideOriginal_remove', false));

            // Make a reflection of the override class and the module override class
            $override_file = file($override_path);
            $override_file = array_diff($override_file, array("\n"));
            eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+' . $classname . '\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?#i'), array(' ', 'class ' . $classname . 'OverrideOriginal' . $uniq), implode('', $override_file)));
            $override_class = new ReflectionClass($classname . 'OverrideOriginal' . $uniq);

            $module_file = file($path_override);
            $module_file = array_diff($module_file, array("\n"));
            eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+' . $classname . '(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'), array(' ', 'class ' . $classname . 'Override' . $uniq), implode('', $module_file)));
            $module_class = new ReflectionClass($classname . 'Override' . $uniq);
            // Check if none of the methods already exists in the override class
            foreach ($module_class->getMethods() as $method) {
                if ($override_class->hasMethod($method->getName())) {
                    $method_override = $override_class->getMethod($method->getName());
                    if (preg_match('/module: (.*)/ism', $override_file[$method_override->getStartLine() - 5], $name) && preg_match('/date: (.*)/ism', $override_file[$method_override->getStartLine() - 4], $date) && preg_match('/version: ([0-9.]+)/ism', $override_file[$method_override->getStartLine() - 3], $version)) {
                        throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden by the module %3$s version %4$s at %5$s.'), $method->getName(), $classname, $name[1], $version[1], $date[1]));
                    }
                    throw new Exception(sprintf(Tools::displayError('The method %1$s in the class %2$s is already overridden.'), $method->getName(), $classname));
                }
                $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b' . $method->getName() . '\b))/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    $1", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                }
            }
            // Check if none of the properties already exists in the override class
            foreach ($module_class->getProperties() as $property) {
                if ($override_class->hasProperty($property->getName())) {
                    throw new Exception(sprintf(Tools::displayError('The property %1$s in the class %2$s is already defined.'), $property->getName(), $classname));
                }
                $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b' . $property->getName() . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    $1$2$3", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                }
            }
            foreach ($module_class->getConstants() as $constant => $value) {
                if ($override_class->hasConstant($constant)) {
                    throw new Exception(sprintf(Tools::displayError('The constant %1$s in the class %2$s is already defined.'), $constant, $classname));
                }
                $module_file = preg_replace('/(const\s)\s*(\b' . $constant . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    $1$2", $module_file);
                if ($module_file === null) {
                    throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                }
            }
            // Insert the methods from module override in override
            $copy_from = array_slice($module_file, $module_class->getStartLine() + 1, $module_class->getEndLine() - $module_class->getStartLine() - 2);
            array_splice($override_file, $override_class->getEndLine() - 1, 0, $copy_from);
            $code = implode('', $override_file);
            file_put_contents($override_path, preg_replace($pattern_escape_com, '', $code));
        } else {
            $override_src = $path_override;
            $override_dest = _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'override' . DIRECTORY_SEPARATOR . $path;
            $dir_name = dirname($override_dest);
            if (!$orig_path && !is_dir($dir_name)) {
                $oldumask = umask(0000);
                @mkdir($dir_name, 0777);
                umask($oldumask);
            }
            if (!is_writable($dir_name)) {
                throw new Exception(sprintf(Tools::displayError('directory (%s) not writable'), $dir_name));
            }
            $module_file = file($override_src);
            $module_file = array_diff($module_file, array("\n"));
            if ($orig_path) {
                do {
                    $uniq = uniqid();
                } while (class_exists($classname . 'OverrideOriginal_remove', false));
                eval(preg_replace(array('#^\s*<\?(?:php)?#', '#class\s+' . $classname . '(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'), array(' ', 'class ' . $classname . 'Override' . $uniq), implode('', $module_file)));
                $module_class = new ReflectionClass($classname . 'Override' . $uniq);
                // For each method found in the override, prepend a comment with the module name and version
                foreach ($module_class->getMethods() as $method) {
                    $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b' . $method->getName() . '\b))/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    $1", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override method %1$s in class %2$s.'), $method->getName(), $classname));
                    }
                }

                // Same loop for properties
                foreach ($module_class->getProperties() as $property) {
                    $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b' . $property->getName() . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    $1$2$3", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override property %1$s in class %2$s.'), $property->getName(), $classname));
                    }
                }

                // Same loop for constants
                foreach ($module_class->getConstants() as $constant => $value) {
                    $module_file = preg_replace('/(const\s)\s*(\b' . $constant . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    $1$2", $module_file);
                    if ($module_file === null) {
                        throw new Exception(sprintf(Tools::displayError('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                    }
                }
            }
            file_put_contents($override_dest, preg_replace($pattern_escape_com, '', $module_file));

            // Re-generate the class index
            Tools::generateIndex();
        }
        return true;
    }
    /**
     * Get local path for module
     *
     * @since 1.5.0
     * @return string
     */
    public function getLocalPath($name)
    {
        return $this->local_path = _PS_MODULE_DIR_ . $name . '/';
    }
    public function getStoreLangCurrency($storeId, $dom = "")
    {
        global $cookie;
        $currencyObj = new CurrencyCore($cookie->id_currency);
        $currency = $currencyObj->iso_code;
        $lang = Context::getContext()->language->iso_code;
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
