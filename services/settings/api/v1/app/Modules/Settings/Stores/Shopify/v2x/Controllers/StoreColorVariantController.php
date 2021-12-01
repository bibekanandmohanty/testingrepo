<?php
/**
 * This Controller used to save, fetch or delete Magento Products on various
 * endpoints
 *
 * PHP version 5.6
 *
 * @category  Magento_API
 * @package   Store
 * @author    Tapas <tapasranjanp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace SwatchStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Product Controller
 *
 * @category Magento_API
 * @package  Store
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreColorVariantController extends StoreComponent
{
    /**
     * Instantiate Constructer
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Get: Get the list of color attributes from the WooCommerce API
     *
     * @param $response Slim's Response object
     *
     * @author debashisd@riaxe.com
     * @date   5 Dec 2019
     * @return Array of Color terms
     */
    public function getColorVariants($response)
    {
        $storeResponse = [
            'status' => 1,
            'color_id' => null,
            'attribute_terms' => [],
        ];
        try {
            $attributeName = $this->getAttributeName();
            $getProductAttributes = $this->getColorAttributes();
            $colorId = 'color';
            if (!empty($getProductAttributes)) {
                $storeResponse = [
                    'status' => 1,
                    'color_id' => $colorId,
                    'attribute_terms' => $getProductAttributes['data'],
                ];
            }
        } catch (\Exception $e) {
            $storeResponse = [
                'status' => 0,
                'message' => message('Variant Data', 'insufficient'),
                'exception' => show_exception() === true ? $e->getMessage() : ''
            ];
        }
        return $storeResponse;
    }
}