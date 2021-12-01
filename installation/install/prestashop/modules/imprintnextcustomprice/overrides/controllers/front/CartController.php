<?php
class CartController extends CartControllerCore
{
    public $php_self = 'cart';

    protected $id_product;
    protected $id_product_attribute;
    protected $id_address_delivery;
    protected $customization_id;
    protected $qty;
    protected $ref_id;
    public $ssl = true;

    protected $ajax_refresh = false;

    /**
     * Initialize cart controller
     * @see FrontController::init()
     */
    public function init()
    {

        parent::init();

        // Send noindex to avoid ghost carts by bots
        header('X-Robots-Tag: noindex, nofollow', true);

        // Get page main parameters
        $this->id_product = (int) Tools::getValue('id_product', null);
        $this->id_product_attribute = (int) Tools::getValue('id_product_attribute', Tools::getValue('ipa'));
        $this->customization_id = (int) Tools::getValue('id_customization');
        $this->qty = abs(Tools::getValue('qty', 1));
        $this->id_address_delivery = (int) Tools::getValue('id_address_delivery');
        $this->ref_id = ((int) Tools::getValue('ref_id') > 0) ? (int) Tools::getValue('ref_id') : 0;
        $this->setCartEdit();
    }

    /**
     * This process delete a product from the cart
     */
    protected function processDeleteProductInCart()
    {
        $customization_product = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'customization`'
            . ' WHERE `id_cart` = ' . (int) $this->context->cart->id
            . ' AND `id_product` = ' . (int) $this->id_product
            . ' AND `id_customization` != ' . (int) $this->customization_id
        );

        if (count($customization_product)) {
            $product = new Product((int) $this->id_product);
            if ($this->id_product_attribute > 0) {
                $minimal_quantity = (int) Attribute::getAttributeMinimalQty($this->id_product_attribute);
            } else {
                $minimal_quantity = (int) $product->minimal_quantity;
            }

            $total_quantity = 0;
            foreach ($customization_product as $custom) {
                $total_quantity += $custom['quantity'];
            }

            if ($total_quantity < $minimal_quantity) {
                $this->errors[] = $this->trans(
                    'You must add %quantity% minimum quantity',
                    array('%quantity%' => $minimal_quantity),
                    'Shop.Notifications.Error'
                );

                return false;
            }
        }

        $data = array(
            'id_cart' => (int) $this->context->cart->id,
            'id_product' => (int) $this->id_product,
            'id_product_attribute' => (int) $this->id_product_attribute,
            'customization_id' => (int) $this->customization_id,
            'id_address_delivery' => (int) $this->id_address_delivery,
        );

        Hook::exec('actionObjectProductInCartDeleteBefore', $data, null, true);
        $ref_id = ((int) Tools::getValue('ref_id') > 0) ? (int) Tools::getValue('ref_id') : 0;
        if ($this->context->cart->deleteProduct(
            $this->id_product,
            $this->id_product_attribute,
            $this->customization_id,
            $this->id_address_delivery,
            $ref_id
        )) {
            Hook::exec('actionObjectProductInCartDeleteAfter', $data);

            if (!Cart::getNbProducts((int) $this->context->cart->id)) {
                $this->context->cart->setDeliveryOption(null);
                $this->context->cart->gift = 0;
                $this->context->cart->gift_message = '';
                $this->context->cart->update();
            }

            $isAvailable = $this->areProductsAvailable();
            if (true !== $isAvailable) {
                $this->updateOperationError[] = $isAvailable;
            }
        }

        CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
    }

    /**
     * Check if the products in the cart are available.
     *
     * @return bool|string
     */
    protected function areProductsAvailable()
    {
        $product = $this->context->cart->checkQuantities(true);

        if (true === $product || !is_array($product)) {
            return true;
        }
        if ($product['active']) {
            return $this->trans(
                'The item %product% in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.',
                array('%product%' => $product['name']),
                'Shop.Notifications.Error'
            );
        }

        return $this->trans(
            'This product (%product%) is no longer available.',
            array('%product%' => $product['name']),
            'Shop.Notifications.Error'
        );
    }

    /**
     * This process add or update a product in the cart
     */
    protected function processChangeProductInCart()
    {
        $mode = (Tools::getIsset('update') && $this->id_product) ? 'update' : 'add';
        $ErrorKey = ('update' === $mode) ? 'updateOperationError' : 'errors';

        if (Tools::getIsset('group')) {
            $this->id_product_attribute = (int) Product::getIdProductAttributeByIdAttributes(
                $this->id_product,
                Tools::getValue('group')
            );
        }

        if ($this->qty == 0) {
            $this->{$ErrorKey}[] = $this->trans(
                'Null quantity.',
                array(),
                'Shop.Notifications.Error'
            );
        } elseif (!$this->id_product) {
            $this->{$ErrorKey}[] = $this->trans(
                'Product not found',
                array(),
                'Shop.Notifications.Error'
            );
        }

        $product = new Product($this->id_product, true, $this->context->language->id);
        if (!$product->id || !$product->active || !$product->checkAccess($this->context->cart->id_customer)) {
            $this->{$ErrorKey}[] = $this->trans(
                'This product (%product%) is no longer available.',
                array('%product%' => $product->name),
                'Shop.Notifications.Error'
            );

            return;
        }

        if (!$this->id_product_attribute && $product->hasAttributes()) {
            $minimum_quantity = ($product->out_of_stock == 2)
            ? !Configuration::get('PS_ORDER_OUT_OF_STOCK')
            : !$product->out_of_stock;
            $this->id_product_attribute = Product::getDefaultAttribute($product->id, $minimum_quantity);
            // @todo do something better than a redirect admin !!
            if (!$this->id_product_attribute) {
                Tools::redirectAdmin($this->context->link->getProductLink($product));
            }
        }

        $qty_to_check = $this->qty;
        $cart_products = $this->context->cart->getProducts();

        if (is_array($cart_products)) {
            foreach ($cart_products as $cart_product) {
                if ($this->productInCartMatchesCriteria($cart_product)) {
                    $qty_to_check = $cart_product['cart_quantity'];

                    if (Tools::getValue('op', 'up') == 'down') {
                        $qty_to_check -= $this->qty;
                    } else {
                        $qty_to_check += $this->qty;
                    }

                    break;
                }
            }
        }

        // Check product quantity availability
        if ('update' !== $mode && $this->shouldAvailabilityErrorBeRaised($product, $qty_to_check)) {
            $this->{$ErrorKey}[] = $this->trans(
                'The item %product% in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.',
                array('%product%' => $product->name),
                'Shop.Notifications.Error'
            );
        }

        // Check minimal_quantity
        if (!$this->id_product_attribute) {
            if ($qty_to_check < $product->minimal_quantity) {
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    array('%product%' => $product->name, '%quantity%' => $product->minimal_quantity),
                    'Shop.Notifications.Error'
                );

                return;
            }
        } else {
            $combination = new Combination($this->id_product_attribute);
            if ($qty_to_check < $combination->minimal_quantity) {
                $this->errors[] = $this->trans(
                    'The minimum purchase order quantity for the product %product% is %quantity%.',
                    array('%product%' => $product->name, '%quantity%' => $combination->minimal_quantity),
                    'Shop.Notifications.Error'
                );

                return;
            }
        }

        // If no errors, process product addition
        if (!$this->errors) {
            // Add cart if no cart found
            if (!$this->context->cart->id) {
                if (Context::getContext()->cookie->id_guest) {
                    $guest = new Guest(Context::getContext()->cookie->id_guest);
                    $this->context->cart->mobile_theme = $guest->mobile_theme;
                }
                $this->context->cart->add();
                if ($this->context->cart->id) {
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
            }

            // Check customizable fields

            if (!$product->hasAllRequiredCustomizableFields() && !$this->customization_id) {
                $this->{$ErrorKey}[] = $this->trans(
                    'Please fill in all of the required fields, and then save your customizations.',
                    array(),
                    'Shop.Notifications.Error'
                );
            }

            if (!$this->errors) {
                $ref_id = ((int) Tools::getValue('ref_id') > 0) ? (int) Tools::getValue('ref_id') : 0;
                $cart_rules = $this->context->cart->getCartRules();
                $available_cart_rules = CartRule::getCustomerCartRules(
                    $this->context->language->id,
                    (isset($this->context->customer->id) ? $this->context->customer->id : 0),
                    true,
                    true,
                    true,
                    $this->context->cart,
                    false,
                    true
                );
                $update_quantity = $this->context->cart->updateQty(
                    $this->qty,
                    $this->id_product,
                    $this->id_product_attribute,
                    $this->customization_id,
                    Tools::getValue('op', 'up'),
                    $this->id_address_delivery,
                    null,
                    true,
                    true,
                    $ref_id
                );
                if ($update_quantity < 0) {
                    // If product has attribute, minimal quantity is set with minimal quantity of attribute
                    $minimal_quantity = ($this->id_product_attribute)
                    ? Attribute::getAttributeMinimalQty($this->id_product_attribute)
                    : $product->minimal_quantity;
                    $this->{$ErrorKey}[] = $this->trans(
                        'You must add %quantity% minimum quantity',
                        array('%quantity%' => $minimal_quantity),
                        'Shop.Notifications.Error'
                    );
                } elseif (!$update_quantity) {
                    $this->errors[] = $this->trans(
                        'You already have the maximum quantity available for this product.',
                        array(),
                        'Shop.Notifications.Error'
                    );
                } elseif ($this->shouldAvailabilityErrorBeRaised($product, $qty_to_check)) {
                    // check quantity after cart quantity update
                    $this->{$ErrorKey}[] = $this->trans(
                        'The item %product% in your cart is no longer available in this quantity. You cannot proceed with your order until the quantity is adjusted.',
                        array('%product%' => $product->name),
                        'Shop.Notifications.Error'
                    );
                }
            }
        }
        $removed = CartRule::autoRemoveFromCart();
        CartRule::autoAddToCart();
    }

    /**
     * Check product quantity availability.
     *
     * @param Product $product
     * @param int $qtyToCheck
     *
     * @return bool
     */
    protected function shouldAvailabilityErrorBeRaised($product, $qtyToCheck)
    {
        if (($this->id_product_attribute)) {
            return !Product::isAvailableWhenOutOfStock($product->out_of_stock)
            && !Attribute::checkAttributeQty($this->id_product_attribute, $qtyToCheck);
        } elseif (Product::isAvailableWhenOutOfStock($product->out_of_stock)) {
            return false;
        }

        // product quantity is the available quantity after decreasing products in cart
        $productQuantity = Product::getQuantity(
            $this->id_product,
            $this->id_product_attribute,
            null,
            $this->context->cart,
            $this->customization_id
        );

        return $productQuantity < 0;
    }

    /**
     * Check cart edit is enabled or not.
     *
     * @param Nothing
     *
     * @return set in smart variable
     */
    private function setCartEdit()
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

        $settingUrl = $orderUrl = $xeStoreUrl . $xetoolDir . '/api/v1/settings';
        $settingArray = $this->getGeneralSetting($settingUrl);
        $cartEditEnabled = $settingArray['cart_setting']['cart_edit']['is_enabled'];
        if ($cartEditEnabled) {
            $cartEdit = 1;
        } else {
            $cartEdit = 0;
        }
        $this->context->smarty->assign(
            array(
                'is_cart_edit' => $cartEdit,
            )
        );
    }

    /**
     * Call generall settings through Curl
     *
     * @param Nothing
     *
     * @return array
     */
    private function getGeneralSetting($url)
    {
        //  Initiate curl
        $ch = curl_init();
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the url
        curl_setopt($ch, CURLOPT_URL, $url);
        // Execute
        $result = curl_exec($ch);
        // Closing
        curl_close($ch);
        return json_decode($result, true);
    }

}
