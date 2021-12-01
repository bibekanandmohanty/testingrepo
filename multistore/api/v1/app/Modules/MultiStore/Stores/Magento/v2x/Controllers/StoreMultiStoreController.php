<?php
/**
 * This Controller used to save, fetch or delete Magento Stores on various
 * endpoints
 *
 * PHP version 5.6
 *
 * @category  Magento_API
 * @package   Store
 * @author    Tapas Ranjan<tapasranjanp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace MultiStoreStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Store Product Controller
 *
 * @category Magento_API
 * @package  Store
 * @author   Tapas Ranjan<tapasranjanp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class StoreMultiStoreController extends StoreComponent
{
    /**
     * GET: Get all blogs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author soumyas@riaxe.com
     * @date   30 Dec 2020
     * @return Array
     */

    public function getAllStores()
    {
        $storeResponse = [];
        $filters = array();
        try {
            $result = $this->apiCall('Product', 'getAllStores', $filters);
            $result = $result->result;
            $storeResponse = json_clean_decode($result, true);
        } catch (\Exception $e) {
            // Store exception in logs
            create_log(
                'store', 'error',
                [
                    'message' => $e->getMessage(),
                    'extra' => [
                        'module' => 'Get all store.',
                    ],
                ]
            );
        }
        return $storeResponse;
    }
}
