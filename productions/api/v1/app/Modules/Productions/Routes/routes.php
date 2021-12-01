<?php
/**
 * This Routes holds all the individual route for the Productions
 *
 * PHP version 5.6
 *
 * @category  Productions
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Productions\Controllers\ProductionController;

// Instantiate the Container
$container = $app->getContainer();

//Productions Routes List
$app->group(
    '/productions', function () use ($app) {
        $app->get('/order-list', ProductionController::class . ':getProductionOrderList');
        $app->get('/jobs-order-list', ProductionController::class . ':getProductionJobOrderList');
        $app->get('/jobs-list', ProductionController::class . ':getProductionJobList');
        $app->post('/create-job', ProductionController::class . ':createProductionJob');
        $app->get('/list-view', ProductionController::class . ':getProductionListView');
        $app->get('/card-view', ProductionController::class . ':getProductionCardView');
        $app->get('/calender-view', ProductionController::class . ':getProductionCalenderView');
        $app->get('/activity-log', ProductionController::class . ':getProductionActivityLog');
        $app->post('/change-stage', ProductionController::class . ':productionJobStageOperations');
        $app->post('/stage-delayed', ProductionController::class . ':productionJobStageDelayed');
        $app->post('/change-assignee', ProductionController::class . ':changeStageAssignee');
        $app->post('/change-comp-date', ProductionController::class . ':changeExpCompletionData');
        $app->post('/job-internal-note', ProductionController::class . ':saveProductionJobInternalNote');
        $app->post('/job-note', ProductionController::class . ':saveProductionJobNote');
        $app->post('/send-email', ProductionController::class . ':sendEmail');
        $app->get('/holiday-list', ProductionController::class . ':getProductionHolidayList');
        $app->post('/holiday-list', ProductionController::class . ':saveProductionHolidayList');
        $app->get('/card-view/{id}', ProductionController::class . ':getCardViewDetails');
        $app->get('/job-details/{id}', ProductionController::class . ':getProductionJobDetails');
        $app->get('/job-log/{id}', ProductionController::class . ':getProductionJobLogs');
        $app->delete('/status/{id}', ProductionController::class . ':deleteProductionStatus');
        $app->get('/download/{id}', ProductionController::class . ':downloadProductionJob');
        $app->delete('/holiday-list/{id}', ProductionController::class . ':deleteProductionHolidayList');
        $app->post('/holiday-list/{id}', ProductionController::class . ':updateProductionHolidayList');
        $app->get('/stages-progress/{id}', ProductionController::class . ':getProductionStageProgress');
    }
)->add(new ValidateJWT($container));


