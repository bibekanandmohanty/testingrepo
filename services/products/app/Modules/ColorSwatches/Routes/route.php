<?php
    /**
     * @category   Routes
     * @package    Eloquent
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     Another Author <>
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @package_version@1.0
     */

    use Slim\Http\Request;
    use Slim\Http\Response;
    use App\Modules\ColorSwatches\Controllers\ColorSwatchesController as ColorSwatch;
    use App\Middlewares\ValidateJWTToken as ValidateJWT;

    $container = $app->getContainer();

    /**
     * Color Swatch Routes list
     */
    $app->group('/color-swatch', function () use ($app) {
        $app->get('', ColorSwatch::class . ':getColorSwatch');
        $app->get('/{id}', ColorSwatch::class . ':getColorSwatch');
        $app->post('', ColorSwatch::class . ':saveColorSwatch');
        $app->put('/{color_swatch_id}', ColorSwatch::class . ':updateColorSwatch');
        $app->delete('/{color_swatch_id}', ColorSwatch::class . ':deleteColorSwatch');
    });