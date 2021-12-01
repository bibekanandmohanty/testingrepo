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
     * Get: Get the list of color attributes from the Magento API
     *
     * @param $response Slim's Response object
     *
     * @author tapasranjanp@riaxe.com
     * @date   05 March 2020
     * @return Array of Color terms
     */
    public function getColorVariants($response)
    {
        $storeResponse = [];
        try {
            $attributeName = $this->getAttributeName();
            $filters = array(
                'color' => $attributeName['color']
            );
            $result = $this->apiCall(
                'Product', 'getColorArr', $filters
            );
            $result = $result->result;
            $getProductAttributes = json_clean_decode($result, true);
            $colorId = $getProductAttributes['colorId'];
            if (!empty($getProductAttributes)) {
                $storeResponse = [
                    'color_id' => $colorId,
                    'attribute_terms' => $getProductAttributes['data'],
                ];
            }
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get color variants',
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
     * @author tapasranjanp@riaxe.com
     * @date   18 April 2020
     * @return Array records and server status
     */
    public function saveColor($name, $colorId)
    {
        $storeResponse = [];
        if (!empty($colorId) && $colorId > 0 ) {
            try {
                $attributeName = $this->getAttributeName();
                $filters = array(
                    'colorAttrId' => $colorId,
                    'colorAttrName' => $attributeName['color'],
                    'colorOptionName' => $name
                );
                $result = $this->apiCall('Product', 'addColorOption', $filters);
                $result = $result->result;
                $storeResponse = json_clean_decode($result, true);
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
