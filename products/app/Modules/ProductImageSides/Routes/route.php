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
    use App\Modules\ProductImageSides\Controllers\ProductImagesController as ProductImages;
    use App\Middlewares\ValidateJWTToken as ValidateJWT;

    $container = $app->getContainer();

    /**
     * Product Images and Sides Routes List
     */
    $app->group('/image-sides', function () use ($app) {
        $app->get('', ProductImages::class . ':getProductImages');
        $app->get('/{product_image_id}', ProductImages::class . ':getProductImages');
        $app->post('', ProductImages::class . ':saveProductImages');
        $app->put('/{product_image_id}', ProductImages::class . ':updateProductImages');
        $app->delete('/{ids}', ProductImages::class . ':productImageDelete');
        // Enable/Disable Product Images
        $app->get('/disable-toggle/{id}', ProductImages::class . ':disableProductImage');
    });
    