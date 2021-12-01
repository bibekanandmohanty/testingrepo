<?php
/**
 * Manage Woocommerce Store Colors
 *
 * PHP version 5.6
 *
 * @category  Store_Color
 * @package   Store
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace SwatchStoreSpace\Controllers;

use CommonStoreSpace\Controllers\StoreController;

/**
 * Store Color Controller
 *
 * @category Store_Color
 * @package  Store
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreColorVariantController extends StoreController {
	/**
	 * Instantiate Constructer
	 */
	public function __construct() {
		parent::__construct();
	}
	/**
	 * Get: Get the list of color attributes from the WooCommerce API
	 *
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Dec 2019
	 * @return Array of Color terms
	 */
	public function getColorVariants($request, $response, $args) {
		$storeResponse = [];
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		$endPoint = 'products/attributes';
		try {
			$getProductAttributes = $this->plugin->get($endPoint, ['store_id' => $storeId]);
			// Get Settings Attribute Name
			$attributeName = $this->getAttributeName();
			if (!empty($attributeName) && $attributeName['color'] != "") {
				// Get Product Attributes
				foreach ($getProductAttributes as $attributes) {
					if (isset($attributes['name'])
						&& $attributes['name'] == $attributeName['color']
					) {
						$colorId = $attributes['id'];
						$termEndPoint = 'products/attributes/terms';
						$options = ['store_id' => $storeId, 'attribute_name' => $attributes['name']];
						$getAttributeTerms = $this->plugin->get($termEndPoint, $options);
						if (isset($getAttributeTerms)
							&& count($getAttributeTerms) > 0
						) {
							$storeResponse = [
								'color_id' => $colorId,
								'attribute_terms' => $getAttributeTerms,
							];
						}
					}
				}

			}
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Color Variant',
					],
				]
			);
		}
		return $storeResponse;
	}
	/**
	 * Post: Save Color terms into the store
	 *
	 * @param $name    Name of the color term
	 * @param $colorId Id of the color attribute
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Dec 2019
	 * @return Array records and server status
	 */
	public function saveColor($name, $colorId, $storeId) {
		$endPoint = 'products/attributes/create';
		$storeResponse = [];

		if (!empty($colorId) && $colorId > 0) {
			try {
				$option = array('store_id' => $storeId, 'color_id' => $colorId, 'name' => $name);
				$storeResponse = $this->plugin->post($endPoint, array('attributes_option' => $option));
			} catch (\Throwable $th) {
				// Store exception in logs
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Save Color',
						],
					]
				);
			}
		}
		return $storeResponse;
	}
}
