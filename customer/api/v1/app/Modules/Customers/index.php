<?php
/**
 * Index File for Carts
 *
 * PHP version 5.6
 *
 * @category  Carts
 * @package   Store
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
$vendor->setPsr4(
    "CustomerStoreSpace\\", "app/Modules/Customers/Stores/" 
        . STORE_NAME . "/" . STORE_VERSION . "/"
);
require __DIR__ . '/Routes/routes.php';
