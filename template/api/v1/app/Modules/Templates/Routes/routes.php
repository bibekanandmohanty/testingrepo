<?php
/**
 * This Routes holds all the individual route for the Templates
 *
 * PHP version 5.6
 *
 * @category  Template
 * @package   Template
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Templates\Controllers\CategoryController as Category;
use App\Modules\Templates\Controllers\TemplateController as Templates;

// Instantiate the Container
$container = $app->getContainer();

// Templates Route
$app->group(
    '/templates', function () use ($app) {
        // Fetch all Templates
        $app->get('', Templates::class . ':getTemplates');
        // Dashboard Count fetching
        $app->get('/most-used', Templates::class . ':mostUsedTemplate');
        // Fetch Single Template
        $app->get('/{id}', Templates::class . ':getTemplates');
        // Save new Records
        $app->post('', Templates::class . ':saveDesigns');
        // Update existing Records
        $app->post('/{id}', Templates::class . ':updateDesigns');
        // Delete existing Record
        $app->delete('/{id}', Templates::class . ':deleteTemplate');
    }
)->add(new ValidateJWT($container));

// Templates Update Route
$app->group(
    '/designs', function () use ($app) {
        // Update existing Records
        $app->post('/{id}', Templates::class . ':updateTemplates');
    }
)->add(new ValidateJWT($container));

// Categories Routes List
$app->delete('/categories/templates/{id}',  Templates::class . ':deleteCategory')
    ->add(new ValidateJWT($container));