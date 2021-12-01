<?php
/**
 * Routes
 *
 * PHP version 5.6
 *
 * @category  Routes
 * @package   SLIM_Routes
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin ProductsController
 */

use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Settings\Controllers\ColorSwatchesController as ColorSwatch;
use App\Modules\Settings\Controllers\ColorVariantController as ColorVariants;
use App\Modules\Settings\Controllers\SettingController as Setting;

$container = $app->getContainer();

/**
 * Settings Routes List
 */
$app->group(
    '/settings', function () use ($app) {
        $app->get('/units', Setting::class . ':getUnitValues');
        $app->get('/currencies', Setting::class . ':getCurrencyValues');
        $app->get('/form-values', Setting::class . ':getDynamicFormValues');
        $app->get('', Setting::class . ':getSettings');
        $app->post('', Setting::class . ':saveSettings');
        $app->delete('/{id}', Setting::class . ':deleteSetting');
        $app->post('/s3', Setting::class . ':saveS3Credentials');
        $app->get('/s3', Setting::class . ':getS3Credentials');
    }
)->add(new ValidateJWT($container));

$app->get(
    '/settings/carts', Setting::class . ':getCartEditSetting'
);

$app->get(
    '/template-products', Setting::class . ':getTemplateSettingOfProducts'
);


/**
 * Language Routes List
 */
$app->group(
    '/languages', function () use ($app) {
        $app->get('', Setting::class . ':getLanguage');
        $app->get('/multiple', Setting::class . ':resetMultiLanguage');
        $app->get('/key', Setting::class . ':getDefaultLangKey');
        $app->get('/{id}', Setting::class . ':getLanguage');
        $app->post('', Setting::class . ':saveLanguage');
        $app->post('/{id}', Setting::class . ':updateLanguage');
        $app->post('/key/save', Setting::class . ':saveDefaultLangKey');
        $app->delete('/{id}', Setting::class . ':deleteLanguage');
        $app->get('/default/{id}', Setting::class . ':defaultLanguage');
        $app->get('/enable/{id}', Setting::class . ':enableLanguage');
    }
)->add(new ValidateJWT($container));


/**
 * Color Swatch Routes list
 */
$app->group(
    '/color-swatches', function () use ($app) {
        $app->get('', ColorSwatch::class . ':getColorSwatch');
        $app->get('/{id}', ColorSwatch::class . ':getColorSwatch');
        $app->post('', ColorSwatch::class . ':saveColorSwatch');
        $app->post('/{color_swatch_id}', ColorSwatch::class . ':updateColorSwatch');
    }
)->add(new ValidateJWT($container));

/**
 * Save Store Color
 */
$app->group(
    '/store-color', function () use ($app) {
        $app->post('', ColorSwatch::class . ':saveStoreColor');
    }
)->add(new ValidateJWT($container));

/**
 * Color Type Routes List
 */
$app->group(
    '/color-types', function () use ($app) {
        $app->get('', ColorSwatch::class . ':getColorType');
    }
)->add(new ValidateJWT($container));
