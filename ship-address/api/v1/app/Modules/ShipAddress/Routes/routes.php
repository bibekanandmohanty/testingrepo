<?php

/**
*
 * PHP version 5.6
 *
 * @category  Ship Address
 * @package   Production Hub
 * @author    Soumya <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\ShipAddress\Controllers\ShipAddressController;

// Instantiate the Container
$container = $app->getContainer();

// Ship To Address Routes List
$app->group(
	'/ship-to-address', function () use ($app) {
		$app->post('', ShipAddressController::class . ':createShipAddress');
		$app->get('', ShipAddressController::class . ':getShipAddressList');
		$app->get('/{id}', ShipAddressController::class . ':getShipAddressList');
		$app->post('/{id}', ShipAddressController::class . ':updateShipAddress');
		$app->delete('/{id}', ShipAddressController::class . ':deleteShipAddress');
	}
)->add(new ValidateJWT($container));