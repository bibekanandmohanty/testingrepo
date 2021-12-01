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
use App\Modules\Distresses\Controllers\DistressController;
use App\Middlewares\ValidateJWTToken as ValidateJWT;

// Instantiate the Container
$container = $app->getContainer();

// Distresses Routes List
$app->group(
    '/distresses', function () use ($app) {
        $app->get('', DistressController::class . ':getDistresses');
        $app->get('/{id}', DistressController::class . ':getDistresses');
        $app->post('', DistressController::class . ':saveDistresses');
        $app->post('/{id}', DistressController::class . ':updateDistress');
        $app->delete('/{id}', DistressController::class . ':deleteDistress');
    }
)->add(new ValidateJWT($container));

// Categories Routes List
$app->delete(
    '/categories/distresses/{id}',  DistressController::class . ':deleteCategory'
)->add(new ValidateJWT($container));