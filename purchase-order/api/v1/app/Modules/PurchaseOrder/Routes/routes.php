<?php
/**
 * This Routes holds all the individual route for the Purchase order
 *
 * PHP version 5.6
 *
 * @category  Production_Hub
 * @package   Purchase Order
 * @author    Soumya <soumays@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\PurchaseOrder\Controllers\PurchaseOrderController;

// Instantiate the Container
$container = $app->getContainer();

// Purchase Order Routes List

$app->group(
	'/purchase-order', function () use ($app) {
		$app->post('', PurchaseOrderController::class . ':createPurchaseOrder');
		$app->post('/update/{id}', PurchaseOrderController::class . ':updatePurchaseOrder');
		$app->get('/order-list', PurchaseOrderController::class . ':getOrderListFromPreviousPoDate');
		$app->get('/previous-order-list/{id}', PurchaseOrderController::class . ':gePreviousOrderList');
		$app->get('/current-order-list', PurchaseOrderController::class . ':getOrderListForPo');
		$app->get('/line-item-status', PurchaseOrderController::class . ':getPurchaseOrderLineItemStatus');
		$app->get('/items/{id}', PurchaseOrderController::class . ':getPurchaseOrderLineItemDetails');
		$app->get('/log/{id}', PurchaseOrderController::class . ':getPurchaseOrderLog');
		$app->get('', PurchaseOrderController::class . ':getPurchaseOrderList');
		$app->get('/po-id', PurchaseOrderController::class . ':getPurchaseOrderId');
		$app->get('/resend/{id}', PurchaseOrderController::class . ':sendToVendor');
		$app->get('/{id}/{type}', PurchaseOrderController::class . ':getPurchaseOrderList');
		//$app->get('/action/{id}/{type}', PurchaseOrderController::class . ':purchaseOrderAction');
		$app->post('/status/{id}', PurchaseOrderController::class . ':updatePurchaseOrderStatus');
		$app->delete('/{id}', PurchaseOrderController::class . ':deletePurchaseOrder');
		$app->post('/update-line-item-status', PurchaseOrderController::class . ':updatePurchaseOrderLineItemStatus');
		$app->post('/internal-note', PurchaseOrderController::class . ':saveInternalNote');

	}
)->add(new ValidateJWT($container));
$app->get('/purchase-order/action/{id}/{type}', PurchaseOrderController::class . ':purchaseOrderAction');