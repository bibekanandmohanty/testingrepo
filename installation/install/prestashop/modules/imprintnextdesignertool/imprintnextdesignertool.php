<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class imprintnextDesignerTool extends Module
{

    /* @var boolean error */
    protected $_errors = false;

    public function __construct()
    {
        $this->name = 'imprintnextdesignertool';
        $this->tab = 'designertool'; // imprintnextProductCustomizer, imprintnextOrderDownload - front_office_features
        $this->version = '1.0.0';
        $this->author = 'ImprintNext';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ImprintNext Designer tool');
        $this->description = $this->l('Integrates the ImprintNext designer tool to prestashop store.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->l('No name provided');
        }

    }

    /**
     * Installation code of this module
     *
     * @param NULL
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install() &&

        // CUSTOMIZE BUTTON HOOKS
        $this->registerHook('displayProductButtons') &&
        $this->registerHook('newOrder') &&
        $this->registerHook('actionOrderStatusPostUpdate') &&

        // SHOPPING CART HOOK
        $this->registerHook('displayShoppingCartFooter') &&

        // PRODUCT CUSTOMIZER HOOKS
        $this->registerHook('actionAdminControllerSetMedia') &&
        $this->registerHook('actionProductUpdate') &&
        $this->registerHook('displayAdminProductsExtra') &&

        // Order Download HOOK
        $this->registerHook('displayInvoice') &&

        Configuration::updateValue('MYMODULE_NAME', 'shopping cart changes');
    }

    /**
     * Un-instal code of this module
     *
     * @param NULL
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * Show product custom buton in store front end
     *
     * @param Array( $params)
     */
    public function hookDisplayProductButtons($params)
    {
        if (Configuration::get('PS_XETOOL')) {
            $xetoolDir = Configuration::get('PS_XETOOL');
        } else {
            $xetoolDir = 'designer';
        }
        $lang_id = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        $is_addtocart = $params['product']['is_addtocart'];
        $customize = $params['product']['customize'];
        $xe_is_temp = $params['product']['xe_is_temp'];
        $product_id = (int) Tools::getValue('id_product');
        $custom_ssl_var = 0;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        }

        if ((bool) Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $ps_base_url = _PS_BASE_URL_SSL_;
            $xeStoreUrl = _PS_BASE_URL_SSL_ . __PS_BASE_URI__;
        } else {
            $ps_base_url = _PS_BASE_URL_;
            $xeStoreUrl = _PS_BASE_URL_ . __PS_BASE_URI__;
        }
        $product = new Product($product_id, true, $lang_id, $id_shop);

        if (Configuration::get('PS_BLOCK_CART_AJAX')) {

            $this->context->controller->registerJavascript('modules-imprintnextdesignertool', 'modules/' . $this->name . '/js/imprintnextcustomizebutton.js', ['position' => 'bottom', 'priority' => 500]);
            $this->context->controller->registerStylesheet('modules-imprintnextdesignertool', 'modules/' . $this->name . '/css/imprintnextcustomizebutton.css', ['media' => 'all', 'priority' => 500]);
        }
        $category = $this->getCategoryByPid($product_id);
        $cmsPage = $this->getCmsPageId();
        $this->context->smarty->assign(
            array(
                'my_module_name' => Configuration::get('MYMODULE_NAME'),
                'my_module_link' => $this->context->link->getModuleLink('imprintnextcustomizebutton', 'display'),
                'product_id' => $product_id,
                'xeStoreUrl' => $xeStoreUrl . $xetoolDir,
                'apiKey' => '',
                'product_details' => $product,
                'is_addtocart' => $is_addtocart,
                'allow_oosp' => $product->isAvailableWhenOutOfStock((int) $product->out_of_stock),
                'is_customize' => $customize,
                'xe_is_temp' => $xe_is_temp,
                'cms_page' => $cmsPage,
                'p_category' => $category,
                'store' => $id_shop,
            )
        );
        return $this->display(__FILE__, 'imprintnextcustomizebutton.tpl');
    }

    /**
     * GET: get CMS page URL
     *
     * @return string
     */
    public function getCmsPageId()
    {
        $lang_id = Context::getContext()->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        $select_sql = "SELECT * FROM " . _DB_PREFIX_ . "cms_lang where meta_title='Designer Tool' AND id_lang='$lang_id' AND id_shop='$id_shop' ";
        $result_data = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($select_sql);
        $context = \Context::getContext();
        if (!empty($result_data)) {
            $url = $context->link->getCMSLink($result_data[0]['id_cms'], $result_data[0]['link_rewrite']);
        }
        $find_str = 'controller=cms';
        if (strpos($url, $find_str) === false) {
            $url = $url . '?';
        } else {
            $url = $url . '&';
        }
        return $url;

    }

    /**
     * Call every new order
     *
     * @param  Array( $params)
     * @return string
     */
    public function hookNewOrder($params)
    {
        return $this->hookActionOrderStatusPostUpdate($params);
    }

    /**
     * hookActionOrderStatusPostUpdate() - creation of orders folder with svg and info.html
     *
     * @param  $params- prestashop parameter in md array
     * @return null
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        if (Configuration::get('PS_XETOOL')) {
            $xetoolDir = Configuration::get('PS_XETOOL');
        } else {
            $xetoolDir = 'designer';
        }
        if (!empty($params['order'])) {
            $orderId = $params['order']->id;
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
            $orderUrl = $xeStoreUrl . $xetoolDir . '/api/v1/orders/create-order-files/' . $orderId;
            $orderCh = curl_init();
            curl_setopt($orderCh, CURLOPT_URL, $orderUrl);
            curl_setopt($orderCh, CURLOPT_HEADER, array());
            curl_setopt($orderCh, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($orderCh, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($orderCh);
            curl_close($orderCh);
        }
    }
    /**
     * Hook for shopping cart footer
     *
     * @param Array( $params)
     */
    public function hookDisplayShoppingCartFooter($params)
    {
        $id_shop = (int) Context::getContext()->shop->id;
        $cmsPage = $this->getCmsPageId();
        if (Configuration::get('PS_XETOOL')) {
            $xetoolDir = Configuration::get('PS_XETOOL');
        } else {
            $xetoolDir = 'designer';
        }
        $this->context->controller->addCSS($this->_path . 'css/imprintnextshoppingcart.css', 'all');
        $custom_ssl_var = 0;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        }

        if ((bool) Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $ps_base_url = _PS_BASE_URL_SSL_;
            $xeStoreUrl = _PS_BASE_URL_SSL_ . __PS_BASE_URI__;
        } else {
            $ps_base_url = _PS_BASE_URL_;
            $xeStoreUrl = _PS_BASE_URL_ . __PS_BASE_URI__;
        }
        $cart_id = $this->context->cookie->id_cart;
        $products = $this->context->cart->getProducts();

        $this->context->smarty->assign(
            array(
                'products' => $products,
                'cart_id' => $cart_id,
                'xeStoreUrl' => $xeStoreUrl . $xetoolDir,
                'cms_page' => $cmsPage,
                'store' => $id_shop,
            )
        );
        return $this->display(__FILE__, 'imprintnextshoppingcart.tpl');
    }
    /**
     * Alter table for add custom field in product table
     *
     * @param  String( $method);
     * @return String
     */
    public function alterTable($method)
    {
        switch ($method) {
            case 'add':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product ADD COLUMN `customize` tinyint(1) DEFAULT 0';
                break;

            case 'remove':
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product DROP COLUMN `customize`';
                break;
        }
        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        return true;
    }
    /**
     * Alter table for add custom field in product table
     *
     * @param  Sting( $method);
     * @return String
     */
    public function prepareNewTab($id_product)
    {
        $this->context->smarty->assign(
            array(
                'custom_field' => $this->getCustomField((int) $id_product),
            )
        );
    }
    /**
     * Hooks for product extra buuton in strore admin
     *
     * @param  Array( $params);
     * @return String
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        if (Validate::isLoadedObject($product = new Product((int) $params['id_product']))) {
            $this->prepareNewTab($params['id_product']);
            return $this->display(__FILE__, '/views/imprintnextproductcustomizer.tpl');
        }
    }
    /**
     * Hooks for update product
     *
     * @param  Array( $params);
     * @return String
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        if ($this->context->controller->controller_name == 'AdminProducts' && Tools::getValue('id_product')) {
            $this->context->controller->addJS($this->_path . '/js/imprintnextproductcustomizer.js');
        }
    }
    /**
     * Hooks for product update
     *
     * @param  Array( $params);
     * @return String
     */
    public function hookActionProductUpdate($params)
    {
        $id_product = (int) Tools::getValue('id_product');
        if (!Db::getInstance()->update('product', array('customize' => pSQL(Tools::getValue('custom_field'))), ' id_product = ' . $id_product)) {
            $this->context->controller->_errors[] = Tools::displayError('Error: ') . mysql_error();
        }

    }
    /**
     * Get custom product id
     *
     * @param  Int( $id_product);
     * @return String
     */
    public function getCustomField($id_product)
    {
        $result = Db::getInstance()->getRow('SELECT customize FROM ' . _DB_PREFIX_ . 'product WHERE id_product = ' . (int) $id_product);
        if (!empty($result)) {
            return $result['customize'];
        }
    }

    /**
     * Hooks for after order details page in store admin
     *
     * @param  Array( $params);
     * @return String
     */
    public function hookDisplayInvoice($params)
    {
        if (Configuration::get('PS_XETOOL')) {
            $xetoolDir = Configuration::get('PS_XETOOL');
        } else {
            $xetoolDir = 'designer';
        }
        $this->context->controller->addCSS($this->_path . 'css/imprintnextorderdownload.css', 'all');
        $this->context->controller->addJS($this->_path . 'js/imprintnextorderdownload.js', 'all');
        $custom_ssl_var = 0;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $custom_ssl_var = 1;
        }

        if ((bool) Configuration::get('PS_SSL_ENABLED') && $custom_ssl_var == 1) {
            $ps_base_url = _PS_BASE_URL_SSL_;
            $xeStoreUrl = _PS_BASE_URL_SSL_ . __PS_BASE_URI__;
        } else {
            $ps_base_url = _PS_BASE_URL_;
            $xeStoreUrl = _PS_BASE_URL_ . __PS_BASE_URI__;
        }
        $this->context->smarty->assign(
            array(
                'order_id' => $params['id_order'],
                'xeStoreUrl' => $xeStoreUrl . $xetoolDir,
            )
        );
        return $this->display(__FILE__, '/views/imprintnextorderdownload.tpl');
    }

    /**
     *GET product categories
     *
     * @param  Int( $product_id);
     * @return String
     */
    private function getCategoryByPid($product_id) {
        $new_categories = '';
        $res_categ_new_pos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT id_category
        FROM `' . _DB_PREFIX_ . 'category_product`
        WHERE `id_product` = '.$product_id.'
        ORDER BY id_category ASC');
        if (!empty($res_categ_new_pos)) {
            foreach ($res_categ_new_pos as $array) {
                $new_categories .= $array['id_category'].',';
            }
            $new_categories = rtrim($new_categories, ',');
        }
        return $new_categories;
    }
    
}
