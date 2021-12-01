<?php
    /**
     * 
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
    use App\Modules\PrintAreas\Controllers\PrintAreasController as PrintArea;
    use App\Middlewares\ValidateJWTToken as ValidateJWT;

    $container = $app->getContainer();


    /**
     * Print Area Routes list 
     */
    $app->group('/print-areas', function () use ($app) {
        $app->get('', PrintArea::class . ':getPrintAreas');
        $app->post('', PrintArea::class . ':savePrintArea');
        $app->put('/{id}', PrintArea::class . ':updatePrintArea');
        $app->delete('/{id}', PrintArea::class . ':deletePrintArea');
    });
    