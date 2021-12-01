<?php
/**
 * This Routes holds all the individual route for the Shape
 *
 * PHP version 5.6
 *
 * @category  Shape
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Modules\Shapes\Controllers\ShapeController;
use App\Middlewares\ValidateJWTToken as ValidateJWT;

// Instantiate the Container
$container = $app->getContainer();

// Shapes Routes List
$app->group(
    '/shapes', function () use ($app) {
        $app->get('', ShapeController::class . ':getShapes');
        $app->get('/{id}', ShapeController::class . ':getShapes');
        $app->post('', ShapeController::class . ':saveShapes');
        $app->post('/{id}', ShapeController::class . ':updateShape');
        $app->delete('/{id}', ShapeController::class . ':deleteShape');
    }
)->add(new ValidateJWT($container));

// Categories Routes List
$app->delete('/categories/shapes/{id}',  ShapeController::class . ':deleteCategory')
    ->add(new ValidateJWT($container));
