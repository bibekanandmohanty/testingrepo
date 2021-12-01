<?php
/**
 * This Routes holds all the individual route for the ColorPallete
 *
 * PHP version 5.6
 *
 * @category  ColorPalettes
 * @package   Assets
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\ColorPalettes\Controllers\CategoryController as Category;
use App\Modules\ColorPalettes\Controllers\ColorPaletteController as ColorPalette;

// Instantiate the Container
$container = $app->getContainer();

//Color Palletes Routes List
$app->group(
    '/color-palettes', function () use ($app) {
        $app->get('', ColorPalette::class . ':getColors');
        $app->get('/{id}', ColorPalette::class . ':getColors');
        $app->post('', ColorPalette::class . ':saveColors');
        $app->post('/{id}', ColorPalette::class . ':updateColor');
        $app->delete('/{id}', ColorPalette::class . ':deleteColor');
        $app->delete(
            '/categories/{id}', ColorPalette::class . ':deleteColorCategory'
        );
    }
)->add(new ValidateJWT($container));
