<?php

/**
 * This Routes holds all the individual route for the User Images
 *
 * PHP version 5.6
 *
 * @category  Vendors
 * @package   Production Hub
 * @author    Soumya <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Vendors\Controllers\VendorController;
// Instantiate the Container
$container = $app->getContainer();
//echo 11111;exit;
//Vendor Routes List
$app->group(
	'/vendors', function () use ($app) {
		$app->get('', VendorController::class . ':getVendorList');
		$app->get('/product-category', VendorController::class . ':getProductCategory');
		$app->post('', VendorController::class . ':createVendor');
		$app->get('/{id}', VendorController::class . ':getVendorDetails');
		$app->post('/{id}', VendorController::class . ':updateVendor');
		$app->delete('/{id}', VendorController::class . ':deleteVendor');
	}
)->add(new ValidateJWT($container));