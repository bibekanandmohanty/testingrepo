<?php
/**
 * This Routes holds all the individual route for the User Images
 *
 * PHP version 5.6
 *
 * @category  User Images
 * @package   Assets
 * @author    Mukesh <mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Modules\Images\Controllers\ImageController;
use App\Middlewares\ValidateJWTToken as ValidateJWT;

// Instantiate the Container
$container = $app->getContainer();


//  User Images Routes List
$app->group(
    '/images', function () use ($app) {
        $app->get('/{id}', ImageController::class . ':getUserImages');
        $app->post('', ImageController::class . ':saveUserImages');
        $app->post('/filter-image', ImageController::class . ':filterImage');
    }
)->add(new ValidateJWT($container));

//  Browser Images Routes List
$app->group(
    '/browser-image', function () use ($app) {
        $app->get('/{id}', ImageController::class . ':getBrowserImages');
        $app->post('', ImageController::class . ':saveBrowserImages');
        $app->post('/delete', ImageController::class . ':deleteBrowserImages');
    }
)->add(new ValidateJWT($container));