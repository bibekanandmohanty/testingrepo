<?php
/**
 * Routes
 *
 * PHP version 5.6
 *
 * @category  Routes
 * @package   SLIM_Routes
 * @author    Chandrakanta Haransingh <chandrakanta@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin ProductsController
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Currency\Controllers\CurrencyController as Currency;

$container = $app->getContainer();

$app->group(
    '/currency', function () use ($app) {
        $app->get('/{base}/{symbol}', Currency::class . ':getPrice');
    }
)->add(new ValidateJWT($container));