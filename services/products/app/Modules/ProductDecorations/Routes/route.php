<?php
    /**
     * @category   Decoration Setting
     * @package    Eloquent
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     Another Author <>
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @package_version@1.0
     */

    use Slim\Http\Request;
    use Slim\Http\Response;
    use App\Modules\ProductDecorations\Controllers\ProductDecorationsController as ProductDecoration;
    use App\Middlewares\ValidateJWTToken as ValidateJWT;

    $container = $app->getContainer();

    /**
     * Product Decoration Settings Data Save
     */
    $app->group('/decorations', function () use ($app) {
        $app->post('', ProductDecoration::class . ':saveProductDecorations');
        $app->get('/{product_id}', ProductDecoration::class . ':getProductDecorations');
        $app->put('/{product_id}', ProductDecoration::class . ':updateProductDecorations');
    });