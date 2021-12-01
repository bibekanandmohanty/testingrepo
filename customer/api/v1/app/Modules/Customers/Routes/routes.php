<?php
/**
 * This Routes holds all the individual route for the Clipart
 *
 * PHP version 5.6
 *
 * @category  Customer
 * @package   Store
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Customers\Controllers\CustomersController as Customers;

$container = $app->getContainer();

$app->group(
	'/customers', function () use ($app) {
		$app->get('', Customers::class . ':allCustomers');
		$app->get('/total', Customers::class . ':getTotalCustomerCount');
		$app->get('/ids', Customers::class . ':allCustomersIds');
		$app->post('', Customers::class . ':customerCreate');
		$app->get('/{id}', Customers::class . ':allCustomers');
		$app->post('/promotional_email', Customers::class . ':sendPromotionalEmail');
		$app->post('/internal-note', Customers::class . ':saveInternalNote');
		$app->get('/internal-note/{id}', Customers::class . ':getInternalNote');
		$app->post('/abbriviation-values/{id}', Customers::class . ':getCustomerAbbriviationValues');
		$app->post('/shipping', Customers::class . ':createShipping');
		$app->post('/shipping/{id}', Customers::class . ':updateShipping');
		$app->delete('/shipping/{id}', Customers::class . ':deleteShipping');
		$app->post('/{id}', Customers::class . ':customerUpdate');
		$app->delete('/{id}', Customers::class . ':customerDelete');
	}
)->add(new ValidateJWT($container));

$app->group(
	'/country', function () use ($app) {
		$app->get('', Customers::class . ':allCountries');
	}
);
$app->group(
	'/state', function () use ($app) {
		$app->get('/{country_code}', Customers::class . ':allStates');
	}
);
$app->get('/country-state-name/{code}/{state_code}', Customers::class . ':getCountryStateName');
