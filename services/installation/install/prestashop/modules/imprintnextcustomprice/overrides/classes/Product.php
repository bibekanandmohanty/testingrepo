<?php
class Product extends ProductCore
{
    /** @var string Tax name */
    public $tax_name;

    /** @var string Tax rate */
    public $tax_rate;

    /** @var int Manufacturer id */
    public $id_manufacturer;

    /** @var int Supplier id */
    public $id_supplier;

    /** @var int default Category id */
    public $id_category_default;

    /** @var int default Shop id */
    public $id_shop_default;

    /** @var string Manufacturer name */
    public $manufacturer_name;

    /** @var string Supplier name */
    public $supplier_name;

    /** @var string Name */
    public $name;

    /** @var string Long description */
    public $description;

    /** @var string Short description */
    public $description_short;

    /** @var int Quantity available */
    public $quantity = 0;

    /** @var int Minimal quantity for add to cart */
    public $minimal_quantity = 1;

    /** @var string available_now */
    public $available_now;

    /** @var string available_later */
    public $available_later;

    /** @var float Price in euros */
    public $price = 0;

    public $specificPrice = 0;

    /** @var float Additional shipping cost */
    public $additional_shipping_cost = 0;

    /** @var float Wholesale Price in euros */
    public $wholesale_price = 0;

    /** @var bool on_sale */
    public $on_sale = false;

    /** @var bool online_only */
    public $online_only = false;

    /** @var string unity */
    public $unity = null;

    /** @var float price for product's unity */
    public $unit_price;

    /** @var float price for product's unity ratio */
    public $unit_price_ratio = 0;

    /** @var float Ecotax */
    public $ecotax = 0;

    /** @var string Reference */
    public $reference;

    /** @var string Supplier Reference */
    public $supplier_reference;

    /** @var string Location */
    public $location;

    /** @var string Width in default width unit */
    public $width = 0;

    /** @var string Height in default height unit */
    public $height = 0;

    /** @var int Number of check enable product for customization for imprintnext */
    public $customize = 0;

    /** @varstring Enumerated (enum) of check predeco product for imprintnext*/
    public $xe_is_temp = 0;

    /** @var int Number of check add to cart button enable or not for imprintnext*/
    public $is_addtocart = 0;

    /** @var int Number of check catalog product*/
    public $is_catalog = 0;

    /** @var string Depth in default depth unit */
    public $depth = 0;

    /** @var string Weight in default weight unit */
    public $weight = 0;

    /** @var string Ean-13 barcode */
    public $ean13;

    /** @var string ISBN */
    public $isbn;

    /** @var string Upc barcode */
    public $upc;

    /** @var string Friendly URL */
    public $link_rewrite;

    /** @var string Meta tag description */
    public $meta_description;

    /** @var string Meta tag keywords */
    public $meta_keywords;

    /** @var string Meta tag title */
    public $meta_title;

    /** @var bool Product statuts */
    public $quantity_discount = 0;

    /** @var bool Product customization */
    public $customizable;

    /** @var bool Product is new */
    public $new = null;

    /** @var int Number of uploadable files (concerning customizable products) */
    public $uploadable_files;

    /** @var int Number of text fields */
    public $text_fields;

    /** @var bool Product statuts */
    public $active = true;

    /** @var bool Product statuts */
    public $redirect_type = '';

    /** @var bool Product statuts */
    public $id_type_redirected = 0;

    /** @var bool Product available for order */
    public $available_for_order = true;

    /** @var string Object available order date */
    public $available_date = '0000-00-00';

    /** @var bool Will the condition select should be visible for this product ? */
    public $show_condition = false;

    /** @var string Enumerated (enum) product condition (new, used, refurbished) */
    public $condition;

    /** @var bool Show price of Product */
    public $show_price = true;

    /** @var bool is the product indexed in the search index? */
    public $indexed = 0;

    /** @var string ENUM('both', 'catalog', 'search', 'none') front office visibility */
    public $visibility;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /*** @var array Tags */
    public $tags;

    /** @var int temporary or saved object */
    public $state = self::STATE_SAVED;

    /**
     * Type of delivery time
     *
     * Choose which parameters use for give information delivery.
     * 0 - none
     * 1 - use default information
     * 2 - use product information
     *
     * @var integer
     */
    public $additional_delivery_times = 1;

    /**
     * @var float Base price of the product
     * @deprecated 1.6.0.13
     */
    public $base_price;

    public $id_tax_rules_group = 1;

    /**
     * We keep this variable for retrocompatibility for themes
     * @deprecated 1.5.0
     */
    public $id_color_default = 0;

    /**
     * @since 1.5.0
     * @var bool Tells if the product uses the advanced stock management
     */
    public $advanced_stock_management = 0;
    public $out_of_stock;
    public $depends_on_stock;

    public $isFullyLoaded = false;

    public $cache_is_pack;
    public $cache_has_attachments;
    public $is_virtual;
    public $id_pack_product_attribute;
    public $cache_default_attribute;

    /**
     * @var string If product is populated, this property contain the rewrite link of the default category
     */
    public $category;

    /**
     * @var int tell the type of stock management to apply on the pack
     */
    public $pack_stock_type = 3;

    public static $_taxCalculationMethod = null;
    protected static $_prices = array();
    protected static $_pricesLevel2 = array();
    protected static $_incat = array();

    /**
     * @since 1.5.6.1
     * @var array $_cart_quantity is deprecated since 1.5.6.1
     */
    protected static $_cart_quantity = array();

    protected static $_tax_rules_group = array();
    protected static $_cacheFeatures = array();
    protected static $_frontFeaturesCache = array();
    protected static $producPropertiesCache = array();

    /** @var array cache stock data in getStock() method */
    protected static $cacheStock = array();

    const STATE_TEMP = 0;
    const STATE_SAVED = 1;

    public static $definition = array(
        'table' => 'product',
        'primary' => 'id_product',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => array(
            /* Classic fields */
            'id_shop_default' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_manufacturer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_supplier' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32),
            'supplier_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 32),
            'location' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64),
            'width' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'height' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'is_addtocart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'is_catalog' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'xe_is_temp' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'customize' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'depth' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'weight' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'quantity_discount' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'ean13' => array('type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13),
            'isbn' => array('type' => self::TYPE_STRING, 'validate' => 'isIsbn', 'size' => 32),
            'upc' => array('type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12),
            'cache_is_pack' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'cache_has_attachments' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'is_virtual' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'state' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'additional_delivery_times' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),

            /* Shop fields */
            'id_category_default' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'id_tax_rules_group' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'on_sale' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'online_only' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'ecotax' => array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'minimal_quantity' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'price' => array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice', 'required' => true),
            'wholesale_price' => array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'unity' => array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'),
            'unit_price_ratio' => array('type' => self::TYPE_FLOAT, 'shop' => true),
            'additional_shipping_cost' => array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isPrice'),
            'customizable' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'text_fields' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'uploadable_files' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),
            'active' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'redirect_type' => array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'),
            'id_type_redirected' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'),
            'available_for_order' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'available_date' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat'),
            'show_condition' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'condition' => array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isGenericName', 'values' => array('new', 'used', 'refurbished'), 'default' => 'new'),
            'show_price' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'indexed' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'visibility' => array('type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isProductVisibility', 'values' => array('both', 'catalog', 'search', 'none'), 'default' => 'both'),
            'cache_default_attribute' => array('type' => self::TYPE_INT, 'shop' => true),
            'advanced_stock_management' => array('type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool'),
            'date_add' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate'),
            'pack_stock_type' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt'),

            /* Lang fields */
            'meta_description' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'meta_keywords' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'meta_title' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128),
            'link_rewrite' => array(
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isLinkRewrite',
                'required' => false,
                'size' => 128,
                'ws_modifier' => array(
                    'http_method' => WebserviceRequest::HTTP_POST,
                    'modifier' => 'modifierWsLinkRewrite',
                ),
            ),
            'name' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => false, 'size' => 128),
            'description' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'description_short' => array('type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'),
            'available_now' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255),
            'available_later' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'IsGenericName', 'size' => 255),
        ),
        'associations' => array(
            'manufacturer' => array('type' => self::HAS_ONE),
            'supplier' => array('type' => self::HAS_ONE),
            'default_category' => array('type' => self::HAS_ONE, 'field' => 'id_category_default', 'object' => 'Category'),
            'tax_rules_group' => array('type' => self::HAS_ONE),
            'categories' => array('type' => self::HAS_MANY, 'field' => 'id_category', 'object' => 'Category', 'association' => 'category_product'),
            'stock_availables' => array('type' => self::HAS_MANY, 'field' => 'id_stock_available', 'object' => 'StockAvailable', 'association' => 'stock_availables'),
        ),
    );
    public static function getPriceStatic($id_product, $usetax = true, $id_product_attribute = null, $decimals = 6, $divisor = null,
        $only_reduc = false, $usereduc = true, $quantity = 1, $force_associated_tax = false, $id_customer = null, $id_cart = null,
        $id_address = null, &$specific_price_output = null, $with_ecotax = true, $use_group_reduction = true, Context $context = null,
        $use_customer_price = 0, $id_customization = null) {
        $ref_id = $use_customer_price;
        if (!$context) {
            $context = Context::getContext();
        }

        $module = Module::getInstanceByName('imprintnextcustomprice');
        $normalPrice = parent::getPriceStatic($id_product, $usetax, $id_product_attribute, $decimals, $divisor,
            $only_reduc, $usereduc, $quantity, $force_associated_tax, $id_customer, $id_cart,
            $id_address, $specific_price_output, $with_ecotax, $use_group_reduction, $context,
            true, $id_customization = null);
        if (!$module->checkCustomizedPriceAdded($id_product) || is_null($id_cart)) {
            return $normalPrice;
        }

        $custom_product_price = $module->fetchCustomizedPrice($id_product, $id_product_attribute, $id_cart, $context->shop->id, $ref_id);
        if (Validate::isFloat($custom_product_price)) {
            $custom_price = $module->calculateTierPrice($id_product, $custom_product_price, $quantity);
            if ($usetax) {
                $id_country = (int) $context->country->id;
                $id_state = 0;
                $zipcode = 0;

                if (!$id_address) {
                    $id_address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
                }

                if ($id_address) {
                    $address_infos = Address::getCountryAndState($id_address);
                    if ($address_infos['id_country']) {
                        $id_country = (int) $address_infos['id_country'];
                        $id_state = (int) $address_infos['id_state'];
                        $zipcode = $address_infos['postcode'];
                    }
                } else if (isset($context->customer->geoloc_id_country)) {
                    $id_country = (int) $context->customer->geoloc_id_country;
                    $id_state = (int) $context->customer->id_state;
                    $zipcode = (int) $context->customer->postcode;
                }
                $address = new Address();
                $address->id_country = $id_country;
                $address->id_state = $id_state;
                $address->postcode = $zipcode;

                $tax_manager = TaxManagerFactory::getManager($address, Product::getIdTaxRulesGroupByIdProduct((int) $id_product, $context));
                $product_tax_calculator = $tax_manager->getTaxCalculator();
                $custom_price = $product_tax_calculator->addTaxes($custom_price);
            }
            $custom_price = Tools::ps_round($custom_price, $decimals);
            if ($custom_price < 0) {
                $custom_price = 0;
            }
            return $custom_price;
        }
        return $normalPrice;
    }
}
