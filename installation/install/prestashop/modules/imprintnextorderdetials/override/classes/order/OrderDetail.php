<?php
class OrderDetail extends OrderDetailCore
{
    /** @var int */
    public $id_order_detail;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_order_invoice;

    /** @var int */
    public $product_id;

    /** @var int */
    public $id_shop;

    /** @var int */
    public $product_attribute_id;

    /** @var int */
    public $id_customization;

    /** @var string */
    public $product_name;

    /** @var int */
    public $product_quantity;

    /** @var int */
    public $product_quantity_in_stock;

    /** @var int */
    public $product_quantity_return;

    /** @var int */
    public $product_quantity_refunded;

    /** @var int */
    public $product_quantity_reinjected;

    /** @var float */
    public $product_price;

    /** @var float */
    public $original_product_price;

    /** @var float */
    public $unit_price_tax_incl;

    /** @var float */
    public $unit_price_tax_excl;

    /** @var float */
    public $total_price_tax_incl;

    /** @var float */
    public $total_price_tax_excl;

    /** @var float */
    public $reduction_percent;

    /** @var float */
    public $reduction_amount;

    /** @var float */
    public $reduction_amount_tax_excl;

    /** @var float */
    public $reduction_amount_tax_incl;

    /** @var float */
    public $group_reduction;

    /** @var float */
    public $product_quantity_discount;

    /** @var string */
    public $product_ean13;

    /** @var string */
    public $product_isbn;

    /** @var string */
    public $product_upc;

    /** @var string */
    public $product_reference;

    /** @var string */
    public $product_supplier_reference;

    /** @var float */
    public $product_weight;

    /** @var float */
    public $ecotax;

    /** @var float */
    public $ecotax_tax_rate;

    /** @var int */
    public $discount_quantity_applied;

    /** @var string */
    public $download_hash;

    /** @var int */
    public $download_nb;

    /** @var datetime */
    public $download_deadline;

    /** @var string $tax_name **/
    public $tax_name;

    /** @var float $tax_rate **/
    public $tax_rate;

    /** @var float $tax_computation_method **/
    public $tax_computation_method;

    /** @var int Id tax rules group */
    public $id_tax_rules_group;

    /** @var int Id warehouse */
    public $id_warehouse;

    /** @var float additional shipping price tax excl */
    public $total_shipping_price_tax_excl;

    /** @var float additional shipping price tax incl */
    public $total_shipping_price_tax_incl;

    /** @var float */
    public $purchase_supplier_price;

    /** @var float */
    public $original_wholesale_price;

    /** @var int */
    public $ref_id;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array('table' => 'order_detail', 'primary' => 'id_order_detail', 'fields' => array('id_order' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true), 'id_order_invoice' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'), 'id_warehouse' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true), 'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true), 'product_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'), 'product_attribute_id' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'), 'id_customization' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'), 'product_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true), 'product_quantity' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true), 'product_quantity_in_stock' => array('type' => self::TYPE_INT, 'validate' => 'isInt'), 'product_quantity_return' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'), 'product_quantity_refunded' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'), 'product_quantity_reinjected' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'), 'product_price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true), 'reduction_percent' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), 'reduction_amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'reduction_amount_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'reduction_amount_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'group_reduction' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), 'product_quantity_discount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), 'product_ean13' => array('type' => self::TYPE_STRING, 'validate' => 'isEan13'), 'product_isbn' => array('type' => self::TYPE_STRING, 'validate' => 'isIsbn'), 'product_upc' => array('type' => self::TYPE_STRING, 'validate' => 'isUpc'), 'product_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference'), 'product_supplier_reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference'), 'product_weight' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), 'tax_name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'), 'tax_rate' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), 'tax_computation_method' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'), 'id_tax_rules_group' => array('type' => self::TYPE_INT, 'validate' => 'isInt'), 'ecotax' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), 'ecotax_tax_rate' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat'), 'discount_quantity_applied' => array('type' => self::TYPE_INT, 'validate' => 'isInt'), 'download_hash' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'), 'download_nb' => array('type' => self::TYPE_INT, 'validate' => 'isInt'), 'download_deadline' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'), 'unit_price_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'unit_price_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'total_price_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'total_price_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'total_shipping_price_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'total_shipping_price_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'purchase_supplier_price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'original_product_price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'original_wholesale_price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'), 'ref_id' => array('type' => self::TYPE_STRING)));

    /**
     * Create an order detail liable to an id_order
     * @param object $order
     * @param object $cart
     * @param array $product
     * @param int $id_order_status
     * @param int ho $product['id_product'];exit;_order_invoice
     * @param bool $use_taxes set to false if you don't want to use taxes
     */
    protected function create(Order $order, Cart $cart, $product, $id_order_state, $id_order_invoice, $use_taxes = true, $id_warehouse = 0)
    {
        if ($use_taxes) {
            $this->tax_calculator = new TaxCalculator();
        }

        $this->id = null;
        $this->product_id = (int) $product['id_product'];
        $this->product_attribute_id = $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : 0;
        $this->id_customization = $product['id_customization'] ? (int) $product['id_customization'] : 0;
        $this->product_name = $product['name'] .
            ((isset($product['attributes']) && $product['attributes'] != null) ?
            ' - ' . $product['attributes'] : '');

        $this->product_quantity = (int) $product['cart_quantity'];
        $this->product_ean13 = empty($product['ean13']) ? null : pSQL($product['ean13']);
        $this->product_isbn = empty($product['isbn']) ? null : pSQL($product['isbn']);
        $this->product_upc = empty($product['upc']) ? null : pSQL($product['upc']);
        $this->product_reference = empty($product['reference']) ? null : pSQL($product['reference']);
        $this->product_supplier_reference = empty($product['supplier_reference']) ? null : pSQL($product['supplier_reference']);
        $this->product_weight = $product['id_product_attribute'] ? (float) $product['weight_attribute'] : (float) $product['weight'];
        $this->id_warehouse = $id_warehouse;

        $product_quantity = (int) Product::getQuantity($this->product_id, $this->product_attribute_id);
        $this->product_quantity_in_stock = ($product_quantity - (int) $product['cart_quantity'] < 0) ?
        $product_quantity : (int) $product['cart_quantity'];

        $this->setVirtualProductInformation($product);
        $this->checkProductStock($product, $id_order_state);

        if ($use_taxes) {
            $this->setProductTax($order, $product);
        }
        $this->setShippingCost($order, $product);
        $this->setDetailProductPrice($order, $cart, $product);

        // Set order invoice id
        $this->id_order_invoice = (int) $id_order_invoice;

        // Set shop id
        $this->id_shop = (int) $product['id_shop'];
        $this->ref_id = $product['ref_id'];

        // Add new entry to the table
        $this->save();

        if ($use_taxes) {
            $this->saveTaxCalculator($order);
        }
        unset($this->tax_calculator);
    }

}
