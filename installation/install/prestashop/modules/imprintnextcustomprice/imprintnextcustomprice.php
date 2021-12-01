<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class imprintnextcustomprice extends Module
{
    const DBTABLE_NAME = 'imprintnext_cart_custom_price';
    protected static $overrides = array(
        'classes/Cart.php', 'classes/Product.php', 'controllers/front/CartController.php',
    );

    protected $_hooks = array(
        'actionProductUpdate',
        'displayFooterProduct',
        'actionProductDelete',
        'actionCartSave');

    /**
     * contructor of this module
     *
     * @param NULL
     *
     */
    public function __construct()
    {
        $this->name = 'imprintnextcustomprice';
        $this->tab = 'customprice';
        $this->version = '1.0.0';
        $this->author = 'ImprintNext';
        $this->need_instance = 0;
        $this->module_key = '5sdg647sg7664eb9436343ea99e867hdf5443frfhh66eyeyc5c116fbcaafafeasgsg';

        parent::__construct();
        $this->displayName = $this->l('ImprintNext Custom price');
        $this->description = $this->l('Adds the customization price to the product price.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * fetch table name
     *
     * @param NULL
     *
     */
    public static function fetchDbTableName()
    {
        return _DB_PREFIX_ . self::DBTABLE_NAME;
    }

    /**
     * Installation code of this module
     *
     * @param NULL
     *
     */
    public function install()
    {
        $install = true;
        if ($install) {
            $install = parent::install();
            foreach (self::$overrides as $file) {
                $explode = explode("/", $file);
                $file_name = $explode[count($explode) - 1];
                unset($explode[count($explode) - 1]);
                $folder = implode("/", $explode);
                @mkdir(_PS_OVERRIDE_DIR_ . $folder, 0777, true);
                if (_PS_VERSION_ <= '1.7.4.4') {
                    @copy(_PS_MODULE_DIR_ . $this->name . '/override/' . $folder . "/" . $file_name, _PS_OVERRIDE_DIR_ . $folder . "/" . $file_name);
                } else {
                    @copy(_PS_MODULE_DIR_ . $this->name . '/overrides/' . $folder . "/" . $file_name, _PS_OVERRIDE_DIR_ . $folder . "/" . $file_name);
                }
                $old = @umask(0);
                @chmod(_PS_OVERRIDE_DIR_ . $folder . "/" . $file_name, 0777);
                @umask($old);
            }
            foreach ($this->_hooks as $hook) {
                $this->registerHook($hook);
            }
        }
        return $install;
    }

    /**
     * Un-instal code of this module
     *
     * @param NULL
     *
     */
    public function uninstall()
    {
        $this->uninstallOverrides();
        foreach ($this->_hooks as $hook) {
            $this->unregisterHook($hook);
        }
        return parent::uninstall();
    }

    /**
     * fetch customized price details
     *
     * @param NULL
     *
     */
    public static function fetchCustomizedPriceDetails($id_product, $store_id)
    {
        return Db::getInstance()->getRow('
            SELECT * FROM `' . self::fetchDbTableName() . '`
            WHERE `id_product` = ' . $id_product . '
            AND `id_shop` = ' . $store_id . '
        ');
    }

    /**
     * hook for actionProductUpdate
     *
     * @param $params
     *
     */
    public function hookActionProductUpdate($params)
    {
        if (Tools::isSubmit($this->name)) {
            $id_product = (int) Tools::getValue('id_product');
            $data = Tools::getValue($this->name);
            if ($id_product) {
                $data['id_product'] = $id_product;
                Db::getInstance()->autoExecute(self::fetchDbTableName(), $data, 'REPLACE');
            }
        }
    }

    /**
     * hook for actionProductDelete
     *
     * @param $params
     *
     */
    public function hookActionProductDelete($params)
    {
        if ($id = (int) $params['product']->id) {
            $sql = array();
            $sql[] = 'DELETE FROM `' . self::fetchDbTableName() . '` WHERE `id_product` = ' . $id . ';';

            foreach ($sql as $_sql) {
                Db::getInstance()->Execute($_sql);
            }
        }
    }

    /**
     * hook for actionCartSave
     *
     * @param $params
     * Used for override, not required
     *
     */
    public function hookActionCartSave($params)
    {
        // continue //
    }

    /**
     * fetch shop_id
     *
     * @param $params
     * Used for override, not required
     *
     */
    public function fetchIdShopId()
    {
        $shop_id = 1;
        if (!is_null(Shop::getContextShopID())) {
            $shop_id = Shop::getContextShopID();
        }
        return $shop_id;
    }

    /**
     * fetch shop_id
     *
     * @param $params
     * Used for override, not required
     *
     */
    public function checkCustomizedPriceAdded($id_product)
    {
        return 1;
    }

    /**
     * fetch Customized Price
     *
     * @param $params
     * Used for override, not required
     *
     */
    public function fetchCustomizedPrice($id_product, $id_product_attribute, $id_cart, $id_shop, $ref_id)
    {
        $row = Db::getInstance()->getRow('
            SELECT `custom_price` FROM `' . self::fetchDbTableName() . '`
            WHERE   `id_product` = ' . (int) $id_product . '
                AND `id_product_attribute` = ' . (int) $id_product_attribute . '
                AND `id_cart` = ' . (int) $id_cart . '
                AND `id_shop` = ' . (int) $id_shop . '
                AND `ref_id` = ' . $ref_id . '
        ');
        return $row['custom_price'];
    }

    /**
     * GET: Get product discount/tier price by product id
     *
     * @param $productId product id
     * @param $productPrice product price
     *
     * @author radhanatham@riaxe.com
     * @date   09 July 2020
     * @return price
     */
    public function calculateTierPrice($productid, $productPrice, $orderQty)
    {
        $tierPrice = $this->getDiscountPrice($productid, $productPrice);
        $price = 0;
        if (!empty($tierPrice)) {
            for ($k = 0; $k < sizeof($tierPrice); $k++) {
                if ($k < sizeof($tierPrice) - 1) {
                    if ($orderQty >= $tierPrice[$k]['quantity'] && $orderQty < $tierPrice[$k + 1]['quantity']) {
                        $price = floatval($tierPrice[$k]['price']);
                        break;
                    } else if ($orderQty < $tierPrice[$k]['quantity']) {
                        $price = floatval($productPrice);
                        break;
                    }
                } else if ($orderQty >= $tierPrice[$k]['quantity']) {
                    $price = floatval($tierPrice[$k]['price']);
                    break;
                } else if ($orderQty < $tierPrice[$k]['quantity']) {
                    $price = floatval($productPrice);
                    break;
                }
            }
            return $price;
        } else {
            return $productPrice;
        }
    }

    /**
     * GET: Get product discount/tier price by product id
     *
     * @param $productId product id
     * @param $productPrice product price
     *
     * @author radhanatham@riaxe.com
     * @date   09 July 2020
     * @return Array
     */
    public function getDiscountPrice($productId, $productPrice)
    {
        $tierPrice = array();
        $tirePriceSql = "SELECT reduction,from_quantity,reduction_type FROM " . _DB_PREFIX_ . "specific_price WHERE id_product=" . $productId . ""; //Tier price for all country
        $resultTirePrice = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($tirePriceSql);
        foreach ($resultTirePrice as $k => $v) {
            if ($v['reduction_type'] == 'percentage') {
                $reduct = number_format($v['reduction'], 2);
                $number = $percentage = substr($reduct, strpos($reduct, ".") + 1);
            } else {
                $number = $v['reduction'];
                $percentage = 100 / ($productPrice / $v['reduction']);
                $percentage = number_format($percentage, 2);
            }
            $tierPrice[$k]['quantity'] = intval($v['from_quantity']);
            $tierPrice[$k]['percentage'] = floatval($percentage);
            $reducePrice = $v['reduction_type'] == 'percentage' ? $productPrice * ($number / 100) : $number;
            $tierPrice[$k]['price'] = number_format($productPrice - $reducePrice, 5);
        }
        return $tierPrice;
    }
}
