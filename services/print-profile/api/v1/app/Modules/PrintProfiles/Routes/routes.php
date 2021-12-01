<?php
/**
 * This Routes holds all the individual route for the Clipart
 *
 * PHP version 5.6
 *
 * @category  Print_Profile
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\PrintProfiles\Controllers\PricingController as Pricing;
use App\Modules\PrintProfiles\Controllers\PrintProfilesController as PrintProfile;

$container = $app->getContainer();

// Print Profile Routes list

$app->group(
    '/print-profiles', function () use ($app) {
        $app->get('', PrintProfile::class . ':getAllPrintProfiles')->add(new ValidateJWT($container));
        // Dashboard count fetching (JWT Excluded)
        $app->get('/count', PrintProfile::class . ':getDataForDashboard');
        $app->get('/assets', PrintProfile::class . ':getAssets');
        $app->get('/{id}', PrintProfile::class . ':getSinglePrintProfile')->add(new ValidateJWT($container));
        $app->post('', PrintProfile::class . ':savePrintProfile')->add(new ValidateJWT($container));
        $app->delete('/{id}', PrintProfile::class . ':deletePrintProfile')->add(new ValidateJWT($container));
        $app->get(
            '/toggle-disable/{id}', PrintProfile::class . ':disablePrintProfile'
        )->add(new ValidateJWT($container));
        $app->post('/clone', PrintProfile::class . ':clonePrintProfile')->add(new ValidateJWT($container));
        // Update Print Profile
        $app->post('/{id}', PrintProfile::class . ':updatePrintProfile')->add(new ValidateJWT($container));
        $app->post(
            '/assign/category', PrintProfile::class . ':assignCategoryToPrintProfile'
        )->add(new ValidateJWT($container));
        $app->get(
            '/{id}/assign/products', PrintProfile::class . ':getProductsRelation'
        )->add(new ValidateJWT($container));
        $app->get(
            '/{id}/assign/assets', PrintProfile::class . ':getAssetsRelation'
        )->add(new ValidateJWT($container));
        $app->post(
            '/assign/products', PrintProfile::class . ':saveProductsRelation'
        )->add(new ValidateJWT($container));
        $app->post(
            '/assign/assets', PrintProfile::class . ':saveAssetsRelation'
        )->add(new ValidateJWT($container));
    }
);

// Print Profile Pricing

$app->group(
    '/print-profile-pricings', function () use ($app) {
        $app->get('', Pricing::class . ':getPricingDetails');
        $app->get('/{id}', Pricing::class . ':getPricingDetails');
        $app->post('', Pricing::class . ':savePricing');
        $app->post('/{id}', Pricing::class . ':updatePricing');
        $app->delete('/{id}', Pricing::class . ':deletePricingDetails');
    }
)->add(new ValidateJWT($container));

$app->group(
    '/print-profile-attributes', function () use ($app) {
        $app->get('', PrintProfile::class . ':getAttributeRelationDetails');
        $app->post('', PrintProfile::class . ':saveAttributeRelationDetails');
    }
)->add(new ValidateJWT($container));


$app->group(
    '/print-profiles', function () use ($app) {
        $app->get('/{id}/fonts/categories', PrintProfile::class . ':getFontCategories');
        $app->get('/{print_profile_id}/fonts/categories/{id}', PrintProfile::class . ':getFontsByCategory');
    }
)->add(new ValidateJWT($container));
