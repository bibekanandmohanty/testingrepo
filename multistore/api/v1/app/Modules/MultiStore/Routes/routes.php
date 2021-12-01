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
use App\Modules\MultiStore\Controllers\MultiStoreController;

// Instantiate the Container
$container = $app->getContainer();
$app->group(
	'/multi-store', function () use ($app) {
		$app->get('/pending', MultiStoreController::class . ':getPendingStoreList');
		$app->get('/available', MultiStoreController::class . ':getAvailableStoreList');
		$app->get('/active', MultiStoreController::class . ':getActiveStoreList');
		$app->post('/import', MultiStoreController::class . ':importStore');
		$app->post('/update', MultiStoreController::class . ':updateStoreStatus');
		$app->delete('/delete/{id}', MultiStoreController::class . ':deleteStore');
	}
)->add(new ValidateJWT($container));
$app->get('/multi-store/customize-button/{id}', MultiStoreController::class . ':enableCustomizeButton');
