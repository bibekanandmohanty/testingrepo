<?php
/**
 * Manage Customer
 *
 * PHP version 5.6
 *
 * @category  Customers
 * @package   Eloquent
 * @author    
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace MultiStoreStoreSpace\Controllers;

use ComponentStoreSpace\Controllers\StoreComponent;
class StoreMultiStoreController extends StoreComponent
{
    /**
     * GET: Get all blogs
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author 
     * @date   25 March 2021
     * @return Array
     */

    public function getAllStores()
    {

        $store = $this->webService->getMultiStore();
        $storeList = [];
        foreach ($store as $key => $value) {
            $storeList[$key]['store_id'] = $value['id_shop'];
            $storeList[$key]['store_name'] = "Prestashop";
            $storeList[$key]['store_url'] = $value['domain'];
            $storeList[$key]['is_active'] = true;
        }

        return $storeList;
    }

}
