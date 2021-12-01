<?php

namespace ImprintNext\Cedapi\Api;

interface ProductInterface
{
    /**
     *
     * @api To fetch all products details.
     * @param int $filters.
     * @param int $categoryid.
     * @param string $searchstring.
     * @param int $store.
     * @param int $range.
     * @param int $offset.
     * @param int $limit.
     * @param int $isPredecorated.
     * @param int $isCatalog.
     * @param string $sku.
     * @param string $order.
     * @param string $orderby.
     * @return string The all products in a json format.
     */
    public function getProducts($filters, $categoryid, $searchstring, $store, $range, $offset, $limit, $isPredecorated, $isCatalog, $sku, $order, $orderby);
    /**
     *
     * @api To fetch a single product details by a product id.
     * @param int $productId.
     * @param int $configProductId.
     * @param int $minimalData.
     * @param int $store.
     * @return string The products details in a json format.
     */
    public function getProductById($productId, $configProductId, $minimalData, $store);
    /**
     *
     * @api To fetch all categories.
     * @param string $order.
     * @param string $orderby.
     * @param string $name.
     * @param int $store.
     * @return string The all category in a json format.
     */
    public function getCategories($order, $orderby, $name, $store);
    /**
     *
     * @api To fetch products available color variants by product id.
     * @param int $productId.
     * @param int $store.
     * @param string $attribute.
     * @return string color variants of a product in a json format.
     */
    public function getColorVariantsByProduct($productId, $store, $attribute);
    /**
     *
     * @api To fetch products all variants by product id.
     * @param int $productId.
     * @param int $store.
     * @return string all variants of a product in a json format.
     */
    public function getAllVariantsByProduct($productId, $store);
    /**
     *
     * @api To fetch all store attributes.
     * @param int $store.
     * @param string $type.
     * @return string all attributes in a json format.
     */
    public function getAttributes($store, $type);
    /**
     *
     * @api Check duplicate product name or sku exist or not.
     * @param string $sku
     * @param string $name
     * @param int $store
     * @return string product id in json format.
     */
    public function checkDuplicateNameAndSku($sku, $name, $store);
    /**
     *
     * @api Create a pre-decorated simple product.
     * @param int $store.
     * @param string $data.
     * @return string response the created product id in a json format.
     */
    public function createPredecoSimpleProduct($store, $data);
    /**
     *
     * @api Create a pre-decorated configurable product.
     * @param int $store.
     * @param string $data.
     * @return string response in a json format.
     */
    public function createPredecoConfigProduct($store, $data);
    /**
     *
     * @api To fetch total products count.
     * @param int $store.
     * @return string The total products count in a json format.
     */
    public function totalProductCount($store);
    /**
     *
     * @api
     * @param int $productId.
     * @param int $store.
     * @param int $simpleProductId.
     * @param string $color.
     * @param string $size.
     * @return string size and quantity in a json format.
     */
    public function getSizeAndQuantity($productId, $store, $simpleProductId, $color, $size);
    /**
     * Get color array
     *
     * @api
     * @param string $color.
     * @return string color in a json format.
     */
    public function getColorArr($color);
    /**
     * Add a new option into the color attribue
     * 
     * @param int $colorAttrId.
     * @param string $colorAttrName.
     * @param string $colorOptionName.
     * @return string options id in a json format.
     */
    public function addColorOption($colorAttrId, $colorAttrName, $colorOptionName);
    /**
     * Get all products filter by categories
     *
     * @api
     * @param int $store.
     * @return string all products in a json format.
     */
    public function getAllProductByCategory($store);
    /**
     *
     * @api
     * @param int $store.
     * @param int $productId.
     * @param int $variationId.
     * @param string $attribute.
     * @return string variant details in a json format.
     */
    public function getMultiAttributeVariantDetails($store, $productId, $variationId, $attribute);
    /**
     *
     * @api To fetch all store attributes.
     * @param int $store.
     * @param int $productId.
     * @param string $type.
     * @return string all attributes in a json format.
     */
    public function getAttributesByProductId($store, $productId, $type);
    /**
     * Add a new product from catalog
     * 
     * @param string $data.
     * @param string $price.
     * @param int $productId.
     * @return string product id in a json format.
     */
    public function addCatalogProductToStore($data, $price, $productId);
    /**
     * Get all Store
     *
     * @api
     * @return string stores in a json format.
     */
    public function getAllStores();
    /**
     * Add new product category to Store
     *
     * @param string $catName.
     * @param int $catId.
     * @param int $store.
     * @return string category id in a json format.
     */
    public function createStoreProductCatagories($catName, $catId, $store);
    /**
     * Remove product category from Store
     *
     * @param int $catId.
     * @param int $store.
     * @return string category id in a json format.
     */
    public function removeStoreProductCatagories($catId, $store);
    /**
     * Get available variants by product id from Store
     *
     * @param int $productId.
     * @param int $store.
     * @return string Variants details in a json format.
     */
    public function getVariants($productId, $store);
}
