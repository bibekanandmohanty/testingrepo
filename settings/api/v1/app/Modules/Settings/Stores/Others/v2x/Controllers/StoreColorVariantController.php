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
            $colorJson = '{"data":[{"id":"5","slug":"Grey","name":"Grey","hex_code":"#AAB2BD"},{"id":"6","slug":"Taupe","name":"Taupe","hex_code":"#CFC4A6"},{"id":"7","slug":"Beige","name":"Beige","hex_code":"#f5f5dc"},{"id":"8","slug":"White","name":"White","hex_code":"#ffffff"},{"id":"9","slug":"Off White","name":"Off White","hex_code":"#faebd7"},{"id":"10","slug":"Red","name":"Red","hex_code":"#E84C3D"},{"id":"11","slug":"Black","name":"Black","hex_code":"#434A54"},{"id":"12","slug":"Camel","name":"Camel","hex_code":"#C19A6B"},{"id":"13","slug":"Orange","name":"Orange","hex_code":"#F39C11"},{"id":"14","slug":"Blue","name":"Blue","hex_code":"#5D9CEC"},{"id":"15","slug":"Green","name":"Green","hex_code":"#A0D468"},{"id":"16","slug":"Yellow","name":"Yellow","hex_code":"#F1C40F"},{"id":"17","slug":"Brown","name":"Brown","hex_code":"#964B00"},{"id":"18","slug":"Pink","name":"Pink","hex_code":"#FCCACD"}],"colorId":"2","group_name":"Color"}';
            $colorArr = json_decode($colorJson, true);
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
        return true;
    }

}
