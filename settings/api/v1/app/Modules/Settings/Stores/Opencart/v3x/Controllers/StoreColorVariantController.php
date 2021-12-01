<?php
/**
 * Manage Opencart Store Colors
 *
 * PHP version 5.6
 *
 * @category  Store_Color
 * @package   Store
 * @author    Mukesh Pradhan<mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace SwatchStoreSpace\Controllers;

use App\Modules\Settings\Models\ColorSwatch;
use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Color Controller
 *
 * @category Store_Color
 * @package  Store
 * @author   Mukesh Pradhan<mukeshp@riaxe.com>
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
     * @author mukeshp@riaxe.com
     * @date   01 May 2020
     * @return Array of Color terms
     */
    public function getColorVariants($response)
    {
        $storeResponse = [];
        $endPoint = 'products/attributes';
        try {
            $getProductAttributes = $this->getAttributes();
            // Get Settings Attribute Name
            $attributeName = $this->getAttributeName();
            if (!empty($attributeName) && $attributeName['color'] != "") {
                // Get Product Attributes
                foreach ($getProductAttributes as $attributes) {
                    if (isset($attributes['name']) 
                        && $attributes['name'] == $attributeName['color']
                    ) {
                        $colorId = $attributes['id'];
                        $getAttributeTerms = $this->getAttributeValuesById($colorId);
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
     * @author mukeshp@riaxe.com
     * @date   01 May 2020
     * @return Array records and server status
     */
    public function saveColor($name, $colorId)
    {
        $storeResponse = [];

        if (!empty($colorId) && $colorId > 0 ) {
            try {
                $isOptValueExist = $this->getExistingAttributeValue($colorId,$name);
                if (!$isOptValueExist) {
                    $storeResponse = $this->addAttributeValue($colorId,$name);
                }
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
