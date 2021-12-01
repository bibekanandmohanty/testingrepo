<?php
/**
 * This Routes holds all the individual route for the Cart
 *
 * PHP version 5.6
 *
 * @category  Carts
 * @package   Store
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Carts\Controllers\CartsController;

// Instantiate the Container
$container = $app->getContainer();

//Routs for Cart getTotalCartItem
$app->group(
    '/carts', function () use ($app) {
        $app->post('', CartsController::class . ':addToCart');
    }
)->add(new ValidateJWT($container));

//Routs for Direct product add to Cart
$app->group(
    '/carts', function () use ($app) {
        $app->post('/directcart', CartsController::class . ':addTemplateToCart');
    }
);
// fetch namenumber data to show in cart page. all store. common api
$app->group(
    '/carts', function () use ($app) {
        $app->get('/nameNumber', CartsController::class . ':getNameNumberData');
        $app->get('/isNameNum', CartsController::class . ':isNameNumberItem');
    }
);

$app->group(
    '/kiosk', function () use ($app) {
        $app->post('/payment', CartsController::class . ':makeKioskPayment');
    }
)->add(new ValidateJWT($container));