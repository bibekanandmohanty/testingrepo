<?php
/**
 * This Routes holds all the individual route for the Word Cloud
 *
 * PHP version 5.6
 *
 * @category  Word_Cloud
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Modules\WordClouds\Controllers\WordCloudController;
use App\Middlewares\ValidateJWTToken as ValidateJWT;

// Instantiate the Container
$container = $app->getContainer();


//  Word Cloud Routes List
$app->group(
    '/word-clouds', function () use ($app) {
        $app->get('', WordCloudController::class . ':getWordClouds');
        $app->get('/{id}', WordCloudController::class . ':getWordClouds');
        $app->post('', WordCloudController::class . ':saveWordClouds');
        $app->post('/{id}', WordCloudController::class . ':updateWordCloud');
        $app->delete('/{id}', WordCloudController::class . ':deleteWordClouds');
    }
)->add(new ValidateJWT($container));

// Categories Routes List
$app->delete('/categories/word-clouds/{id}',  WordCloudController::class . ':deleteCategory')
    ->add(new ValidateJWT($container));

