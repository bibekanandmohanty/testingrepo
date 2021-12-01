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
    use App\Modules\PrintProfiles\Controllers\PrintProfilesController as PrintProfile;
    use App\Middlewares\ValidateJWTToken as ValidateJWT;

    $container = $app->getContainer();

    /**
     * Print Profile Routes list 
     */
    $app->group('/print-profiles', function () use ($app) {
        $app->get('', PrintProfile::class . ':getPrintProfile');
        $app->post('', PrintProfile::class . ':savePrintProfile');
        $app->put('/{id}', PrintProfile::class . ':updatePrintProfile');
        $app->delete('/{id}', PrintProfile::class . ':deletePrintProfile');
    });