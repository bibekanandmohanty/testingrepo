<?php
/**
 * Index file for Products
 *
 * PHP version 5.6
 *
 * @category  Products
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin ProductsController
 */
$vendor->setPsr4(
    "ProductStoreSpace\\", 
    "app/Modules/Products/Stores/" . STORE_NAME . "/" . STORE_VERSION . "/"
);
require __DIR__ . '/Routes/routes.php';