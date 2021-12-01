<?php
/**
 * 2007-2017 PrestaShop
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
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;

class ProductController extends ProductControllerCore
{
    public $php_self = 'product';

    /** @var Product */
    protected $product;

    /** @var Category */
    protected $category;

    protected $redirectionExtraExcludedKeys = ['id_product_attribute', 'rewrite'];

    /**
     * @var array
     */
    protected $combinations;

    protected $quantity_discounts;
    protected $adminNotifications = array();

    /**
     * Assign template vars related to page content.
     *
     * @see FrontController::initContent()
     */
    public function initContent()
    {

        if (!$this->errors) {
            if (Pack::isPack((int) $this->product->id) && !Pack::isInStock((int) $this->product->id)) {
                $this->product->quantity = 0;
            }

            $this->product->description = $this->transformDescriptionWithImg($this->product->description);

            $priceDisplay = Product::getTaxCalculationMethod((int) $this->context->cookie->id_customer);
            $productPrice = 0;
            $productPriceWithoutReduction = 0;

            if (!$priceDisplay || $priceDisplay == 2) {
                $productPrice = $this->product->getPrice(true, null, 6);
                $productPriceWithoutReduction = $this->product->getPriceWithoutReduct(false, null);
            } elseif ($priceDisplay == 1) {
                $productPrice = $this->product->getPrice(false, null, 6);
                $productPriceWithoutReduction = $this->product->getPriceWithoutReduct(true, null);
            }

            if (Tools::isSubmit('submitCustomizedData')) {
                // If cart has not been saved, we need to do it so that customization fields can have an id_cart
                // We check that the cookie exists first to avoid ghost carts
                if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
                    $this->context->cart->add();
                    $this->context->cookie->id_cart = (int) $this->context->cart->id;
                }
                $this->pictureUpload();
                $this->textRecord();
            } elseif (Tools::getIsset('deletePicture') && !$this->context->cart->deleteCustomizationToProduct($this->product->id, Tools::getValue('deletePicture'))) {
                $this->errors[] = $this->trans('An error occurred while deleting the selected picture.', array(), 'Shop.Notifications.Error');
            }

            $pictures = array();
            $text_fields = array();
            if ($this->product->customizable) {
                $files = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_FILE, true);
                foreach ($files as $file) {
                    $pictures['pictures_' . $this->product->id . '_' . $file['index']] = $file['value'];
                }

                $texts = $this->context->cart->getProductCustomization($this->product->id, Product::CUSTOMIZE_TEXTFIELD, true);

                foreach ($texts as $text_field) {
                    $text_fields['textFields_' . $this->product->id . '_' . $text_field['index']] = str_replace('<br />', "\n", $text_field['value']);
                }
            }

            $this->context->smarty->assign(array(
                'pictures' => $pictures,
                'textFields' => $text_fields));

            $this->product->customization_required = false;
            $customization_fields = $this->product->customizable ? $this->product->getCustomizationFields($this->context->language->id) : false;
            if (is_array($customization_fields)) {
                foreach ($customization_fields as &$customization_field) {
                    if ($customization_field['type'] == 0) {
                        $customization_field['key'] = 'pictures_' . $this->product->id . '_' . $customization_field['id_customization_field'];
                    } elseif ($customization_field['type'] == 1) {
                        $customization_field['key'] = 'textFields_' . $this->product->id . '_' . $customization_field['id_customization_field'];
                    }
                }
                unset($customization_field);
            }

            // Assign template vars related to the category + execute hooks related to the category
            $this->assignCategory();
            // Assign template vars related to the price and tax
            $this->assignPriceAndTax();

            // Assign attributes combinations to the template
            $this->assignAttributesCombinations();

            // Pack management
            $pack_items = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : array();

            $assembler = new ProductAssembler($this->context);
            $presenter = new ProductListingPresenter(
                new ImageRetriever(
                    $this->context->link
                ),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->getTranslator()
            );
            $presentationSettings = $this->getProductPresentationSettings();

            $presentedPackItems = array();
            foreach ($pack_items as $item) {
                $presentedPackItems[] = $presenter->present(
                    $this->getProductPresentationSettings(),
                    $assembler->assembleProduct($item),
                    $this->context->language
                );
            }

            $this->context->smarty->assign('packItems', $presentedPackItems);
            $this->context->smarty->assign('noPackPrice', $this->product->getNoPackPrice());
            $this->context->smarty->assign('displayPackPrice', ($pack_items && $productPrice < $this->product->getNoPackPrice()) ? true : false);
            $this->context->smarty->assign('packs', Pack::getPacksTable($this->product->id, $this->context->language->id, true, 1));

            $accessories = $this->product->getAccessories($this->context->language->id);
            if (is_array($accessories)) {
                foreach ($accessories as &$accessory) {
                    $accessory = $presenter->present(
                        $presentationSettings,
                        Product::getProductProperties($this->context->language->id, $accessory, $this->context),
                        $this->context->language
                    );
                }
                unset($accessory);
            }

            if ($this->product->customizable) {
                $customization_datas = $this->context->cart->getProductCustomization($this->product->id, null, true);
            }

            $product_for_template = $this->getTemplateVarProduct();

            $filteredProduct = Hook::exec(
                'filterProductContent',
                array('object' => $product_for_template),
                $id_module = null,
                $array_return = false,
                $check_exceptions = true,
                $use_push = false,
                $id_shop = null,
                $chain = true
            );
            if (!empty($filteredProduct['object'])) {
                $product_for_template = $filteredProduct['object'];
            }

            $productManufacturer = new Manufacturer((int) $this->product->id_manufacturer, $this->context->language->id);

            $manufacturerImageUrl = $this->context->link->getManufacturerImageLink($productManufacturer->id);
            $undefinedImage = $this->context->link->getManufacturerImageLink(null);
            if ($manufacturerImageUrl === $undefinedImage) {
                $manufacturerImageUrl = null;
            }

            $productBrandUrl = $this->context->link->getManufacturerLink($productManufacturer->id);

            $this->context->smarty->assign(array(
                'priceDisplay' => $priceDisplay,
                'productPriceWithoutReduction' => $productPriceWithoutReduction,
                'customizationFields' => $customization_fields,
                'id_customization' => empty($customization_datas) ? null : $customization_datas[0]['id_customization'],
                'accessories' => $accessories,
                'product' => $product_for_template,
                'products' => $product_for_template,
                'displayUnitPrice' => (!empty($this->product->unity) && $this->product->unit_price_ratio > 0.000000) ? true : false,
                'product_manufacturer' => $productManufacturer,
                'manufacturer_image_url' => $manufacturerImageUrl,
                'product_brand_url' => $productBrandUrl,
            ));

            // Assign attribute groups to the template
            $this->assignAttributesGroups($product_for_template);
        }

        parent::initContent();
    }
    /**
     * Assign template vars related to page content.
     *
     * @see getvariantid by productid
     */
    public function getVariantIdByPid($pid, $default_attribute_id)
    {
        $sql_exit_pre = "SELECT parent_variant_id,parent_product_id FROM " . _DB_PREFIX_ . "product_predeco_rel WHERE new_product_id=" . $pid . " AND new_variant_id=" . $default_attribute_id . " ";
        $row_pre = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_exit_pre);
        if (!empty($row_pre)) {
            $combinationsIds = $row_pre[0]['parent_variant_id'];
            $parentProductId = $row_pre[0]['parent_product_id'];
            $language = new Language(true);
            $productArr = array('id_product' => $parentProductId, 'id_product_attribute' => $combinationsIds);
            $imageRetrievers = new ImageRetriever($this->context->link);
            $ImageLists['image'] = $imageRetrievers->getProductImages($productArr, $language);
            $ImageLists['parent_variant_id'] = $combinationsIds;
            return $ImageLists;
        }
    }
    /**
     * Assign template vars related to page content.
     *
     * @see Get all product cover images
     */
    public function getCoverImages(array $presentedProducts, $id_product_attribute)
    {
        if (isset($id_product_attribute)) {
            foreach ($presentedProducts as $image) {
                if (isset($image['cover']) && null !== $image['cover']) {
                    $presentedProducts['cover'] = $image;

                    break;
                }
            }
        }
        if (!isset($presentedProducts['cover'])) {
            if (count($presentedProducts) > 0) {
                $presentedProducts['cover'] = array_values($presentedProducts)[0];
            } else {
                $presentedProducts['cover'] = null;
            }
        }
        return $presentedProducts['cover'];
    }

}
