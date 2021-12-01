<?php
/**
 * Index file for Products
 *
 * PHP version 5.6
 *
 * @category  Products
 * @package   Products
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin ProductsController
 */
$vendor->setPsr4(
    "SwatchStoreSpace\\", "app/Modules/Settings/Stores/" . STORE_NAME . "/" . STORE_VERSION . "/"
);
/**
 * Initilize all Routes
 */
require __DIR__ . '/Routes/routes.php';