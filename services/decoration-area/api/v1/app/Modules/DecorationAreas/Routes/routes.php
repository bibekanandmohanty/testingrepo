<?php
/**
 * This Routes holds all the individual route for the Print Area and Print Area
 * Type
 *
 * PHP version 5.6
 *
 * @category  Print_Area
 * @package   Print_Area
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\DecorationAreas\Controllers\PrintAreasController as PrintArea;
use App\Modules\DecorationAreas\Controllers\PrintAreaTypesController as PrintAreaType;

$container = $app->getContainer();

/**
 * Print Area Routes list
 */
$app->group(
    '/print-areas', function () use ($app) {
        $app->get('', PrintArea::class . ':getPrintAreas');
        $app->post('', PrintArea::class . ':savePrintArea');
        $app->post('/{id}', PrintArea::class . ':updatePrintArea');
        $app->delete('/{id}', PrintArea::class . ':deletePrintArea');
    }
)->add(new ValidateJWT($container));

/**
 * Print Area Type Routes List
 */
$app->group(
    '/print-area-types', function () use ($app) {
        $app->get('', PrintAreaType::class . ':getPrintAreaType');
        $app->get('/{id}', PrintAreaType::class . ':getPrintAreaType');
        $app->post('', PrintAreaType::class . ':savePrintAreaType');
        $app->post('/{id}', PrintAreaType::class . ':updatePrintAreaType');
        $app->delete('/{id}', PrintAreaType::class . ':deletePrintAreaType');
    }
)->add(new ValidateJWT($container));
