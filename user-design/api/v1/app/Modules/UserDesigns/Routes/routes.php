<?php
/**
 * This Routes holds all the individual route for the User Designs
 *
 * PHP version 5.6
 *
 * @category  Routes
 * @package   Routes
 * @author    Mukesh <mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\UserDesigns\Controllers\UserDesignController as UserDesign;

$container = $app->getContainer();

// User Design
$app->group(
    '/user-design', function () use ($app) {
        // Fetch all Saved Design list By Customer
        $app->get('', UserDesign::class . ':getUserDesignList');
        // Save new Records
        $app->post('', UserDesign::class . ':saveUserDesign');
        // Fetch Single Saved Design Details
        $app->get('/{id}', UserDesign::class . ':getUserDesign');
        // Delete UserDesign
        $app->delete('/{id}', UserDesign::class . ':deleteUserDesign');
    }
)->add(new ValidateJWT($container));
