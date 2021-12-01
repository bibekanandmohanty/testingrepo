<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class imprintnextOrderdetials extends Module
{
    protected static $override = array(
        'classes/order/OrderDetail.php',
        'classes/order/Order.php',
    );

    /**
     * contructor of this module
     *
     * @param NULL
     */
    public function __construct()
    {
        $this->name = 'imprintnextorderdetials';
        $this->tab = 'orderdetials';
        $this->version = '1.0.0';
        $this->author = 'ImprintNext';
        $this->need_instance = 0;
        $this->module_key = '5sdg647sg7664eb9436343ea99e867hdf5443frfhh66eyeyc5c116fbcaafafeasgsg';

        parent::__construct();
        $this->displayName = $this->l('ImprintNext Order details');
        $this->description = $this->l('Display the customized products image.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Installation code of this module
     *
     * @param NULL
     */
    public function install()
    {

        $result = true;
        if ($result) {
            $result = parent::install() &&
            $this->alterTable('add') &&
            $this->registerHook('orderConfirmation') &&
            Configuration::updateValue('MYMODULE_NAME', 'imprintnextorderdetials');
        }
        if ($result) {
            foreach (self::$override as $file) {
                $explode = explode("/", $file);
                $file_name = $explode[count($explode) - 1];
                unset($explode[count($explode) - 1]);
                $folder = implode("/", $explode);
                @mkdir(_PS_OVERRIDE_DIR_ . $folder, 0777, true);
                @copy(_PS_MODULE_DIR_ . $this->name . '/override/' . $folder . "/" . $file_name, _PS_OVERRIDE_DIR_ . $folder . "/" . $file_name);
                $old = @umask(0);
                @chmod(_PS_OVERRIDE_DIR_ . $folder . "/" . $file_name, 0777);
                @umask($old);
            }
        }

        return $result;
    }

    /**
     * Un-instal code of this module
     *
     * @param NULL
     */
    public function uninstall()
    {
        $this->uninstallOverrides();
        $this->unregisterHook('orderConfirmation');
        return parent::uninstall();
    }
    /**
     * update order details with ref_id
     *
     * @param Array( $params)
     */
    public function alterTable($method)
    {
        if ($method == 'add') {
            $status = 0;
            $sql = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "order_detail LIKE 'ref_id'";
            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
            if (!empty($rows)) {
                $status = 1;
            } else {
                $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'order_detail ADD `ref_id`  VARCHAR(250) NOT NULL';
                $status = Db::getInstance()->Execute($sql);
            }

            $sqlCheck = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "orders LIKE 'ref_id'";
            $rowsCheck = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlCheck);
            if (empty($rowsCheck)) {
                $sqlAlter = 'ALTER TABLE ' . _DB_PREFIX_ . 'orders ADD ref_id int(10) unsigned NOT NULL';
                $status = Db::getInstance()->Execute($sqlAlter);
            }

        } else {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'order_detail DROP COLUMN `ref_id`';
            $status = Db::getInstance()->Execute($sql);
            $sqlOrder = 'ALTER TABLE ' . _DB_PREFIX_ . 'orders DROP COLUMN `ref_id`';
            $status = Db::getInstance()->Execute($sqlOrder);
        }
        return $status;
    }
    public function hookDisplayOrderConfirmation($params)
    {
        if (Configuration::get('PS_XETOOL')) {
            $xetoolDir = Configuration::get('PS_XETOOL');
        } else {
            $xetoolDir = 'designer';
        }
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

        if (!empty($params['order']->product_list)) {
            foreach ($params['order']->product_list as $v) {
                if ($v['ref_id'] >= 1 || $v['ref_id'] == -1) {
                    $refId = 1;
                }
            }
        }
        $cartId = 0;
        $refId = 0;
        if (!empty($params['order'])) {
            $orderId = $params['order']->id;
            $order = new Order($orderId);
            $products = $order->getProducts();
            if (!empty($products)) {
                foreach ($products as $v) {
                    if ($v['ref_id'] >= 1 || $v['ref_id'] == -1) {
                        $refId = 1;
                    }
                }
            }
            if ($refId > 0) {
                $query = "UPDATE " . _DB_PREFIX_ . "orders SET ref_id= " . $refId . " WHERE id_order = '" . $orderId . "'";
                Db::getInstance()->Execute($query);
            }
            $cartId = $params['order']->id_cart;
        }

        $this->context->smarty->assign(
            array(
                'cart_ids' => $cartId,
                'xeStoreUrl' => $xeStoreUrl . $xetoolDir,
            )
        );
        return $this->display(__FILE__, 'imprintnextorderdetials.tpl');
    }

}
