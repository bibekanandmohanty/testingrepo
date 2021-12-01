<?php
/**
 * This Routes holds all the individual route for the Distress
 *
 * PHP version 5.6
 *
 * @category  Distress
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Modules\Masks\Controllers\MaskController;
use App\Middlewares\ValidateJWTToken as ValidateJWT;

// Instantiate the Container
$container = $app->getContainer();

// Masks Routes List
$app->group(
    '/masks', function () use ($app) {
        $app->get('', MaskController::class . ':getMasks');
        $app->get('/{id}', MaskController::class . ':getMasks');
        $app->post('', MaskController::class . ':saveMasks');
        $app->post('/{id}', MaskController::class . ':updateMask');
        $app->delete('/{id}', MaskController::class . ':deleteMask');
    }
)->add(new ValidateJWT($container));
