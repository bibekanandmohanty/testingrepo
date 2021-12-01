<?php
/**
 * This Routes holds all the individual route for the Fonts
 *
 * PHP version 5.6
 *
 * @category  Fonts
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Fonts\Controllers\FontController;

// Instantiate the Container
$container = $app->getContainer();

//Font Routes List
$app->group(
    '/fonts', function () use ($app) {
        $app->get('', FontController::class . ':getFonts');
        $app->get('/most-used', FontController::class . ':mostUsedFonts');
        $app->get('/{id}', FontController::class . ':getFonts');
        $app->post('', FontController::class . ':saveFonts');
        $app->post('/{id}', FontController::class . ':updateFont');
        $app->delete('/{id}', FontController::class . ':deleteFont');
    }
)->add(new ValidateJWT($container));


// Categories Routes List
$app->delete('/categories/fonts/{id}',  FontController::class . ':deleteCategory')
    ->add(new ValidateJWT($container));
