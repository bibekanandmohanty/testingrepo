<?php
/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
use PrestaShop\PrestaShop\Adapter\ServiceLocator;

class Order extends OrderCore
{
    /** @var int Delivery address id */
    public $id_address_delivery;

    /** @var int Invoice address id */
    public $id_address_invoice;

    public $id_shop_group;

    public $id_shop;

    /** @var int Cart id */
    public $id_cart;

    /** @var int Currency id */
    public $id_currency;

    /** @var int Language id */
    public $id_lang;

    /** @var int Customer id */
    public $id_customer;

    /** @var int Carrier id */
    public $id_carrier;

    /** @var int Order Status id */
    public $current_state;

    /** @var string Secure key */
    public $secure_key;

    /** @var string Payment method */
    public $payment;

    /** @var string Payment module */
    public $module;

    /** @var float Currency exchange rate */
    public $conversion_rate;

    /** @var bool Customer is ok for a recyclable package */
    public $recyclable = 1;

    /** @var bool True if the customer wants a gift wrapping */
    public $gift = 0;

    /** @var string Gift message if specified */
    public $gift_message;

    /** @var bool Mobile Theme */
    public $mobile_theme;

    /**
     * @var string Shipping number
     *
     * @deprecated 1.5.0.4
     * @see OrderCarrier->tracking_number
     */
    public $shipping_number;

    /** @var float Discounts total */
    public $total_discounts;

    public $total_discounts_tax_incl;
    public $total_discounts_tax_excl;

    /** @var float Total to pay */
    public $total_paid;

    /** @var float Total to pay tax included */
    public $total_paid_tax_incl;

    /** @var float Total to pay tax excluded */
    public $total_paid_tax_excl;

    /** @var float Total really paid @deprecated 1.5.0.1 */
    public $total_paid_real;

    /** @var float Products total */
    public $total_products;

    /** @var float Products total tax included */
    public $total_products_wt;

    /** @var float Shipping total */
    public $total_shipping;

    /** @var float Shipping total tax included */
    public $total_shipping_tax_incl;

    /** @var float Shipping total tax excluded */
    public $total_shipping_tax_excl;

    /** @var float Shipping tax rate */
    public $carrier_tax_rate;

    /** @var float Wrapping total */
    public $total_wrapping;

    /** @var float Wrapping total tax included */
    public $total_wrapping_tax_incl;

    /** @var float Wrapping total tax excluded */
    public $total_wrapping_tax_excl;

    /** @var int Invoice number */
    public $invoice_number;

    /** @var int Delivery number */
    public $delivery_number;

    /** @var string Invoice creation date */
    public $invoice_date;

    /** @var string Delivery creation date */
    public $delivery_date;

    /** @var bool Order validity: current order status is logable (usually paid and not canceled) */
    public $valid;

    /** @var string Object creation date */
    public $date_add;

    /** @var string Object last modification date */
    public $date_upd;

    /**
     * @var string Order reference, this reference is not unique, but unique for a payment
     */
    public $reference;

    /**
     * @var int Round mode method used for this order
     */
    public $round_mode;

    /**
     * @var int Round type method used for this order
     */
    public $round_type;

    /**
     * @var int Round type method used for this order
     */
    public $ref_id;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'orders',
        'primary' => 'id_order',
        'fields' => array(
            'id_address_delivery' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_address_invoice' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_cart' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_currency' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_shop_group' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_carrier' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'current_state' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'secure_key' => array('type' => self::TYPE_STRING, 'validate' => 'isMd5'),
            'payment' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true),
            'module' => array('type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true),
            'recyclable' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'gift' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'gift_message' => array('type' => self::TYPE_STRING, 'validate' => 'isMessage'),
            'mobile_theme' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
            'total_discounts' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_discounts_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_discounts_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_paid' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_paid_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_paid_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_paid_real' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_products' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_products_wt' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_shipping' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_shipping_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_shipping_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'carrier_tax_rate' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'),
            'total_wrapping' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_wrapping_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'total_wrapping_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'round_mode' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'round_type' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'shipping_number' => array('type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber'),
            'conversion_rate' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
            'invoice_number' => array('type' => self::TYPE_INT),
            'delivery_number' => array('type' => self::TYPE_INT),
            'invoice_date' => array('type' => self::TYPE_DATE),
            'delivery_date' => array('type' => self::TYPE_DATE),
            'valid' => array('type' => self::TYPE_BOOL),
            'reference' => array('type' => self::TYPE_STRING),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'ref_id' => array('type' => self::TYPE_INT),
        ),
    );

    protected $webserviceParameters = array(
        'objectMethods' => array('add' => 'addWs'),
        'objectNodeName' => 'order',
        'objectsNodeName' => 'orders',
        'fields' => array(
            'id_address_delivery' => array('xlink_resource' => 'addresses'),
            'id_address_invoice' => array('xlink_resource' => 'addresses'),
            'id_cart' => array('xlink_resource' => 'carts'),
            'id_currency' => array('xlink_resource' => 'currencies'),
            'id_lang' => array('xlink_resource' => 'languages'),
            'id_customer' => array('xlink_resource' => 'customers'),
            'id_carrier' => array('xlink_resource' => 'carriers'),
            'current_state' => array(
                'xlink_resource' => 'order_states',
                'setter' => 'setWsCurrentState',
            ),
            'module' => array('required' => true),
            'invoice_number' => array(),
            'invoice_date' => array(),
            'delivery_number' => array(),
            'delivery_date' => array(),
            'valid' => array(),
            'date_add' => array(),
            'date_upd' => array(),
            'shipping_number' => array(
                'getter' => 'getWsShippingNumber',
                'setter' => 'setWsShippingNumber',
            ),
        ),
        'associations' => array(
            'order_rows' => array('resource' => 'order_row', 'setter' => false, 'virtual_entity' => true,
                'fields' => array(
                    'id' => array(),
                    'product_id' => array('required' => true, 'xlink_resource' => 'products'),
                    'product_attribute_id' => array('required' => true),
                    'product_quantity' => array('required' => true),
                    'product_name' => array('setter' => false),
                    'product_reference' => array('setter' => false),
                    'product_ean13' => array('setter' => false),
                    'product_isbn' => array('setter' => false),
                    'product_upc' => array('setter' => false),
                    'product_price' => array('setter' => false),
                    'id_customization' => array('required' => false, 'xlink_resource' => 'customizations'),
                    'unit_price_tax_incl' => array('setter' => false),
                    'unit_price_tax_excl' => array('setter' => false),
                    'ref_id' => array('setter' => false),
                ), ),
        ),
    );

    public function getWsOrderRows()
    {
        $query = '
            SELECT
            `id_order_detail` as `id`,
            `product_id`,
            `product_price`,
            `id_order`,
            `product_attribute_id`,
            `id_customization`,
            `product_quantity`,
            `product_name`,
            `product_reference`,
            `product_ean13`,
            `product_isbn`,
            `product_upc`,
            `unit_price_tax_incl`,
            `unit_price_tax_excl`,
            `ref_id`
            FROM `' . _DB_PREFIX_ . 'order_detail`
            WHERE id_order = ' . (int) $this->id;
        $result = Db::getInstance()->executeS($query);

        return $result;
    }
    
}
