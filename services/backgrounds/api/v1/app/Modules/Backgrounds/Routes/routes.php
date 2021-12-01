<?php
/**
 * This Routes holds all the individual route for the Backgrounds
 *
 * PHP version 5.6
 *
 * @category  Backgrounds
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Backgrounds\Controllers\BackgroundController;

// Instantiate the Container
$container = $app->getContainer();

// Backgrounds Routes List
$app->group(
    '/backgrounds', function () use ($app) {
        $app->get('', BackgroundController::class . ':getBackgrounds');
        $app->get('/{id}', BackgroundController::class . ':getBackgrounds');
        $app->post('', BackgroundController::class . ':saveBackgrounds');
        $app->post('/{id}', BackgroundController::class . ':updateBackground');
        $app->delete('/{id}', BackgroundController::class . ':deleteBackground');
    }
)->add(new ValidateJWT($container));

// Categories Routes List
$app->delete('/categories/backgrounds/{id}',  BackgroundController::class . ':deleteCategory')
    ->add(new ValidateJWT($container));
