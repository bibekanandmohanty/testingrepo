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
    use App\Modules\PrintAreaTypes\Controllers\PrintAreaTypesController as PrintAreaType;
    use App\Middlewares\ValidateJWTToken as ValidateJWT;

    $container = $app->getContainer();

    /**
     * Print Area Routes List
     */
    $app->group('/print-area-types', function () use ($app) {
        $app->get('', PrintAreaType::class . ':getPrintAreaType');
        $app->get('/{id}', PrintAreaType::class . ':getPrintAreaType');
        $app->post('', PrintAreaType::class . ':savePrintAreaType');
        $app->put('/{id}', PrintAreaType::class . ':updatePrintAreaType');
        $app->delete('/{id}', PrintAreaType::class . ':deletePrintAreaType');
    });