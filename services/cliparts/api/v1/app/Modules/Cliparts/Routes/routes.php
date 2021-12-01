<?php
/**
 * This Routes holds all the individual route for the Clipart
 *
 * PHP version 5.6
 *
 * @category  CLipart
 * @package   Assets
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Cliparts\Controllers\CategoryController as Category;
use App\Modules\Cliparts\Controllers\ClipartController;
use App\Modules\Cliparts\Controllers\ZipController;

// Instantiate the Container
$container = $app->getContainer();

// Cliparts Routes List
$app->group(
    '/cliparts', function () use ($app) {
        $app->get('', ClipartController::class . ':getCliparts');
        $app->post('', ClipartController::class . ':saveCliparts');
        $app->get('/most-used', ClipartController::class . ':mostUsedCliparts');
        $app->get('/{id}', ClipartController::class . ':getCliparts');
        $app->post('/{id}', ClipartController::class . ':updateClipart');
        $app->delete('/{id}', ClipartController::class . ':deleteClipart');
        $app->post('/import/zip', ZipController::class . ':zipImport');
    }
)->add(new ValidateJWT($container));

// Categories Routes List
$app->delete('/categories/cliparts/{id}', ClipartController::class . ':deleteCategory')
    ->add(new ValidateJWT($container));
