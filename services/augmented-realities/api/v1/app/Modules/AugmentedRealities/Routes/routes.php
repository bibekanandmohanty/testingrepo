<?php
/**
 * This Routes holds all the individual route for the Distress
 *
 * PHP version 5.6
 *
 * @category  Augmented_Reality
 * @package   Add-on
 * @author    Tanmaya <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Modules\AugmentedRealities\Controllers\ARController as ARC;
use App\Middlewares\ValidateJWTToken as ValidateJWT;

// Instantiate the Container
$container = $app->getContainer();

// Augmented Reality Routes
$app->group(
    '/augmented-reality', function () use ($app) {
        $app->post('/pattern', ARC::class . ':savePattern');
        $app->post('', ARC::class . ':saveAugmentData');
    }
)->add(new ValidateJWT($container));