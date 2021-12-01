<?php
/**
 * Index File for Word Clouds
 *
 * PHP version 5.6
 *
 * @category  Word Clouds
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
$vendor->setPsr4(
    "MultiStoreStoreSpace\\", 
    "app/Modules/MultiStore/Stores/" . STORE_NAME . "/" . STORE_VERSION . "/"
);
require __DIR__ . '/Routes/routes.php';
