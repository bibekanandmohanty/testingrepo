<?php
/**
 * This Routes holds all the individual route for the Graphic Font
 *
 * PHP version 5.6
 *
 * @category  Graphic_Font
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Modules\GraphicFonts\Controllers\GraphicFontController;
use App\Middlewares\ValidateJWTToken as ValidateJWT;

// Instantiate the Container
$container = $app->getContainer();

// Graphic Fonts Routes List
$app->group(
    '/graphic-fonts', function () use ($app) {
        $app->get('', GraphicFontController::class . ':getGraphicFonts');
        $app->get('/all', GraphicFontController::class . ':getAllGraphicFonts');
        $app->get('/{id}', GraphicFontController::class . ':getGraphicFonts');
        $app->post('', GraphicFontController::class . ':saveGraphicFonts');
        $app->post('/{id}', GraphicFontController::class . ':updateGraphicFonts');
        $app->delete('/{id}', GraphicFontController::class . ':deleteGraphicFonts');
    }
)->add(new ValidateJWT($container));
