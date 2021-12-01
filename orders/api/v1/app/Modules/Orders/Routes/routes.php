<?php
/**
 * This Routes holds all the individual route for the Clipart
 *
 * PHP version 5.6
 *
 * @category  Orders
 * @package   Orders
 * @author    Tanmaya Patra <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Orders\Controllers\DownloadArtworkController;
use App\Modules\Orders\Controllers\OrderDownloadController as OrderDownload;
use App\Modules\Orders\Controllers\OrdersController;

$container = $app->getContainer();

//Routs for Order Log
$app->post(
	'/order-logs', OrdersController::class . ':saveOrderLogs'
)->add(new ValidateJWT($container));
$app->post('/convert-quote-to-order', OrdersController::class . ':convertToOrder');
//$app->get('/order-logs/{id}', OrdersController::class . ':getOrderLogs');
//$app->post('/order-artwork-status/{id}', OrdersController::class . ':updateOrderArtworkStatus');

$app->get(
	'/order-logs/{id}', OrdersController::class . ':getOrderLogs'
);

$app->post(
	'/order-artwork-status/{id}', OrdersController::class . ':updateOrderArtworkStatus'
)->add(new ValidateJWT($container));

$app->get(
	'/orders/{id}', OrdersController::class . ':getOrderList'
);

$app->get(
	'/orders', OrdersController::class . ':getOrderList'
)->add(new ValidateJWT($container));

//For download orders
$app->get(
	'/order-download', OrderDownload::class . ':downloadOrder'
);

$app->get(
	'/orders-graph', OrdersController::class . ':getOrdersGraph'
)->add(new ValidateJWT($container));

$app->post(
	'/orders/archive', OrdersController::class . ':archiveOrders'
)->add(new ValidateJWT($container));

//Create Order Asset Folder
$app->get(
	'/orders/create-order-files/{id}',
	OrdersController::class . ':generateOrderFiles'
);

// It will only be used by Shopify store
$app->get(
	'/edit-shopify', OrdersController::class . ':editShopifyProduct'
)->add(new ValidateJWT($container));

//For packing slip download
$app->get(
	'/invoice-download/{id}', OrdersController::class . ':downloadPackingSlip'
)->add(new ValidateJWT($container));
$app->get('/order-status', OrdersController::class . ':getAllOrderStatus')->add(new ValidateJWT($container));
$app->post('/order-status/{id}', OrdersController::class . ':updateOrderStatus')->add(new ValidateJWT($container));
$app->post('/send-to-print-shop', OrdersController::class . ':sendToPrintShop')->add(new ValidateJWT($container));
$app->post('/order-abbriviation', OrdersController::class . ':getOrderAbbriviationValues')->add(new ValidateJWT($container));
$app->get('/download-artwork/{token}', DownloadArtworkController::class . ':downloadOrderArtwork');
$app->post('/download-order-artwork-file', OrderDownload::class . ':downloadOrderArtworkFile');
$app->post('/create-order-artwork-file', OrderDownload::class . ':createdOrderArtworkFile');
//Get Orders items from store
$app->get('/orders-items-from-store/{id}', OrdersController::class . ':getStoreItemsDetails');

//Download Work order slips
$app->get('/download-work-order-slip/{id}', OrdersController::class . ':downloadWorkOrderSlip')->add(new ValidateJWT($container));

