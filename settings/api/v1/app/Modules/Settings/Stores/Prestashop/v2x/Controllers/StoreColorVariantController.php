<?php
/**
 * This Controller used to save, fetch or delete Prestashop Products on various
 * endpoints
 *
 * PHP version 5.6
 *
 * @category  Prestashop_API
 * @package   Store
 * @author    Radhanatha <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace SwatchStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Product Controller
 *
 * @category Prestashop_API
 * @package  Store
 * @author   Radhanatha <radhanatham@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreColorVariantController extends StoreComponent
{
    /**
     * Get: Get the list of color attributes from the Prestashop API
     *
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Array of Color terms
     */
    public function getColorVariants($response)
    {
        $storeResponse = [];
        try {
            $attributeName = $this->getAttributeName();
            $groupName = $attributeName['color'];
            if ($groupName == '') {
                $groupName = 'Color';
            } else {
                $groupName = ucfirst($groupName);
            }
            $filters = array(
                'color' => $groupName,
            );
            $colorArr = $this->webService->getColors(
                $filters
            );
            $getProductAttributes = $colorArr;
            if (!empty($getProductAttributes)) {
                $colorId = $getProductAttributes['colorId'];
                if (!empty($getProductAttributes)) {
                    $storeResponse = [
                        'color_id' => $colorId,
                        'attribute_terms' => $getProductAttributes['data'],
                    ];
                }
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
     * POST: Save color by Prestashop API
     *
     * @param $param Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   13 March 2020
     * @return Boolean
     */
    public function saveColorValue($param)
    {
        return $this->webService->saveColorValue($param);
    }

}
