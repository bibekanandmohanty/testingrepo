<?php
/**
 * Manage Production
 *
 * PHP version 5.6
 *
 * @category  Productions
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Productions\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Components\Models\ProductionAbbriviations;
use App\Components\Models\PurchaseOrderStatus as PurchaseOrderStatus;
use App\Modules\Orders\Controllers\OrdersController;
use App\Modules\PrintProfiles\Models\PrintProfile;
use App\Modules\Productions\Models\Orders;
use App\Modules\Productions\Models\ProductionEmailTemplates;
use App\Modules\Productions\Models\ProductionHubSetting;
use App\Modules\Productions\Models\ProductionJobAgents;
use App\Modules\Productions\Models\ProductionJobLog;
use App\Modules\Productions\Models\ProductionJobNoteFiles;
use App\Modules\Productions\Models\ProductionJobNotes;
use App\Modules\Productions\Models\ProductionJobs;
use App\Modules\Productions\Models\ProductionJobStages;
use App\Modules\Productions\Models\ProductionStatus;
use App\Modules\Productions\Models\PurchaseOrderItems;
use App\Modules\Productions\Models\StatusAssigneeRel;
use App\Modules\Productions\Models\StatusFeatures;
use App\Modules\Productions\Models\StatusPrintProfileRel;
use App\Modules\Productions\Models\User;
use App\Modules\Productions\Models\UserRole;
use App\Modules\Productions\Models\UserRoleRel;
use Illuminate\Database\Capsule\Manager as DB;
use App\Modules\Customers\Controllers\CustomersController;
use App\Modules\Productions\Models\ProductionJobHolidays;

/**
 * Production Controller
 *
 * @category Productions
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class ProductionController extends ParentController {

	/**
	 * Delete : Delete Status from the table if not
	 * associate with quote
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   24 Sept 2020
	 * @return Delete Json Status
	 */
	public function deleteProductionStatus($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Status', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$moduleId = $request->getQueryParam('module_id') ? $request->getQueryParam('module_id') : '';
		if (!empty($args) && $args['id'] > 0) {
			$statusId = to_int($args['id']);
			$module = $request->getQueryParam('module') ? $request->getQueryParam('module') : '';
			if ($module == 'po') {
				$purchaseOrderStatusInit = new PurchaseOrderStatus();
				$purchaseOrderStatusArr = $purchaseOrderStatusInit->select('xe_id')
					->where(
						[
							'xe_id' => $statusId,
							'store_id' => $getStoreDetails['store_id'],
						]
					);
				if ($purchaseOrderStatusArr->count() > 0) {
					$xeId = $purchaseOrderStatusArr->get()->toArray();
					$purchaseOrderStatusInit->where(['xe_id' => $xeId[0]['xe_id']])->delete();
					$jsonResponse = [
						'status' => 1,
						'message' => message('Purchase order status', 'deleted'),
					];
				}

			} else {
				$statusInit = new ProductionStatus();
				$status = $statusInit->find($statusId);
				$oldStatusName = $status->status_name;
				if (isset($status->xe_id) && $status->xe_id != "" && $status->xe_id > 0) {
					if ($status->delete()) {
						//Delete Email template associated with status
						$oldTemplateTypeName = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $oldStatusName)));
						$templateInit = new ProductionEmailTemplates();
						$tempDataArr = $templateInit->select('xe_id')
							->where(
								[
									'template_type_name' => $oldTemplateTypeName,
									'module_id' => 4,
									'store_id' => $getStoreDetails['store_id'],
								]
							);
						if ($tempDataArr->count() > 0) {
							$tempData = $tempDataArr->get();
							$tempData = json_clean_decode($tempData, true);
							$templateTypeId = $tempData[0]['xe_id'];
							$emailTemp = $templateInit->find($templateTypeId);
							$emailTemp->delete();
						}
						//Delete data from production_status_print_profile_rel table
						$statusPrintProfileRelInit = new StatusPrintProfileRel();
						$statusPrintProfileRelInit->where(['status_id' => $statusId])->delete();

						//Delete data from production_status_features table
						$statusFeaturesInit = new StatusFeatures();
						$statusFeaturesInit->where(['status_id' => $statusId])->delete();

						//Delete data from production_status_assignee_rel table
						$statusAssigneeRelInit = new StatusAssigneeRel();
						$statusAssigneeRelInit->where(['status_id' => $statusId])->delete();
					}
					$jsonResponse = [
						'status' => 1,
						'message' => message('Production Status', 'deleted'),
					];
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Generate Job id
	 *
	 * @param $request  Slim's Request object
	 *
	 * @author debashrib@riaxe.com
	 * @date   25 Sept 2020
	 * @return json response
	 */

	private function generateJobId($request, $lastJobId = '') {
		//Get quotation setting data
		$getStoreDetails = get_store_details($request);
		$settingInit = new ProductionHubSetting();
		$settingData = $settingInit->select('setting_value', 'flag')
			->where([
				'module_id' => 4,
				'setting_key' => 'job_card',
				'store_id' => $getStoreDetails['store_id'],
			]);
		if ($settingData->count() > 0) {
			$settingDataArr = $settingData->first()->toArray();
			$settingValue = json_clean_decode($settingDataArr['setting_value'], true);
			$preFix = isset($settingValue['prefix']) ? $settingValue['prefix'] : '';
			$startingNum = isset($settingValue['starting_number']) ? $settingValue['starting_number'] : '';
			$postFix = isset($settingValue['postfix']) ? $settingValue['postfix'] : '';
			$flag = 0;
			if ($settingDataArr['flag'] == 1 && $flag == 1) {
				$flag = 1;
				$newJobId = $preFix . $startingNum . $postFix;
			} else if ($lastJobId == '') {
				$newJobId = $preFix . $startingNum . $postFix;
			} else {
				$postFixLen = strlen($postFix);
				if(0 === strpos($lastJobId, $preFix)){
                    $withoutPrefix = substr($lastJobId, strlen($preFix)).'';
                }
                $jobNum = substr($withoutPrefix, 0, -$postFixLen);
				//$jobNum = preg_replace('/[^0-9]/', '', $lastJobId);
				$newJobNum = $jobNum + 1;
				$newJobId = $preFix . $newJobNum . $postFix;
			}
			$productionJobInit = new ProductionJobs();
			$jobData = $productionJobInit->where(
				[
					'job_id' => $newJobId,
				]);
			if ($jobData->count() > 0) {
				return $this->generateJobId($request, $newJobId);
			} else {
				return $newJobId;
			}
		}
	}

	/**
	 * POST: Create Production Job
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   25 Sept 2020
	 * @return json response
	 */
	public function createProductionJob($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$productionJobInit = new ProductionJobs();
		$orderInit = new Orders();
		$allPostPutVars = $request->getParsedBody();
		$orderIdArr = $allPostPutVars['order_id'];
		$orderIdArr = json_clean_decode($orderIdArr, true);
		if (isset($orderIdArr) && !empty($orderIdArr)) {
			//check for agent assignment in all stages
			$settingData = $this->getProductionSetting($request, $response, ['module_id' => 4, 'return_type' => 1]);
			$settingData = $settingData['data'];
			$statusDataArr = $settingData['status'];
			$isAgentAssign = true;
			foreach ($statusDataArr as $stage) {
				if (empty($stage['assignee_id'])) {
					$isAgentAssign = false;
				}
			}
			if ($isAgentAssign) {
				$emailData = [];
				$finalSendingEmailData = [];
				foreach ($orderIdArr as $orderId) {
					$ordersControllerInit = new OrdersController();
	            	$orderDetails = $ordersControllerInit->getOrderList($request, $response, ['id' => $orderId], 1);
					$orderDetails = $orderDetails['data'];
					$customerId = $orderDetails['customer_id'];
					$customerName = ($orderDetails['customer_first_name'] != '') ? $orderDetails['customer_first_name'] . ' ' . $orderDetails['customer_last_name'] : $orderDetails['customer_email'];
					$orderItems = $orderDetails['orders'];
					$checkDuplicateOrder = $productionJobInit->where('order_id', $orderId);
					//Check if production job is created for this order or not
					if ($checkDuplicateOrder->count() == 0) {
						//Change the Order Production Status to In-Progress
						$checkOrderProductionJob = $orderInit->where([
							'order_id' => $orderId,
							'production_status' => 0,
						]);
						//Check if production job is started
						if ($checkOrderProductionJob->count() == 0) {
							//Update production_status
							$orderInit->where([
								'order_id' => $orderId,
								'store_id' => $getStoreDetails['store_id'],
							])->update([
								'production_status' => '1',
								'customer_id' => $customerId,
							]);

							$lastJobId = '';
							foreach ($orderItems as $items) {
								//Generate Production Job Id
								$lastRecord = $productionJobInit->select('job_id')->latest()->first();
								if (!empty($lastRecord)) {
									$lastJobId = $lastRecord->job_id;
								}
								$jobId = $this->generateJobId($request, $lastJobId);
								$postData = [
									'store_id' => $getStoreDetails['store_id'],
									'job_id' => $jobId,
									'order_id' => $orderId,
									'order_item_id' => $items['id'],
									'order_item_quantity' => $items['quantity'],
									'job_title' => ($items['sku'] != '') ? $items['name'] . ':' . $items['sku'] : $items['name'],
									'job_status' => 'progressing',
									'current_stage_id' => 0,
									'created_at' => date_time(
										'today', [], 'string'
									)
								];
								$productionJob = new ProductionJobs($postData);
								if ($productionJob->save()) {
									$productionJobLastId = $productionJob->xe_id;
									//Change the production setting flag value after production job is created
	                				$this->changeSettingFlagValue($getStoreDetails['store_id'], 4, 'job_card'); 
									//Adding to production job log for job creation
									$logData = [
										'job_id' => $productionJobLastId,
										'title' => 'Job created',
										'description' => 'New job #' . $jobId . ' created.',
										'user_type' => $allPostPutVars['user_type'],
										'user_id' => $allPostPutVars['user_id'],
										'created_date' => date_time(
											'today', [], 'string'
										)
									];
									$this->addingProductionLog($logData);

									$decorationSettingsData = $items['decoration_settings_data'];
									$localStageFlag = 0;
									if (!empty($decorationSettingsData)) {
										//Get all the print method associated with this item
										$printMethodArr = $this->getPrintMethodOfOrder($items);
										$firstPrintMethodId = $printMethodArr[0]['print_method_id'];
										$firstPrintMethodName = $printMethodArr[0]['print_method_name'];
										$startingDate = date_time(
											'today', [], 'string'
										);
										//$finalSendingEmailData = [];
										foreach ($printMethodArr as $printKey => $method) {
											//Get associated stage
											$stageArr = $this->getStagesWrtPrintMethod($method['print_method_id']);
											foreach ($stageArr as $stageKey => $stages) {
												$productionJobStageInit = new ProductionJobStages();
												$checkStageData = $productionJobStageInit->where('job_id', $productionJobLastId);
												$checkStageDataCount = $checkStageData->count();
												$localStageFlag = 1;
												$stageId = $stages['status_id'];
												$stageName = $stages['status_name'];
												$stageColorCode = $stages['color_code'];
												$stageDuration = $stages['duration'];
												$expCompletionDate = date('Y-m-d H:i:s', strtotime('+' . $stageDuration . ' hour', strtotime($startingDate)));
												$getStartNDuedate = $this->calculateDueDate($request, $response, $startingDate, $stageDuration);
												$stageData = [
													'job_id' => $productionJobLastId,
													'print_method_id' => ($stages['is_global'] == 0) ? $method['print_method_id'] : 0,
													'stages_id' => $stageId,
													'stage_name' => $stageName,
													'stage_color_code' => $stageColorCode,
													'created_date' => $startingDate,
													'starting_date' => ($stageKey == 0 && $checkStageDataCount == 0) ? $getStartNDuedate['start_date'] : '1000-01-01 00:00:00',
													'exp_completion_date' => ($stageKey == 0 && $checkStageDataCount == 0) ? $getStartNDuedate['due_date'] : '1000-01-01 00:00:00',
													'status' => ($stageKey == 0 && $checkStageDataCount == 0) ? 'in-progress' : 'not-started',
												];
												$productionJobStage = new ProductionJobStages($stageData);
												$productionJobStage->save();
												if ($stageKey == 0 && $checkStageDataCount == 0) {
													//update current stage in production table
													$currentStageId = $productionJobStage->xe_id;
													$productionJobInit = new ProductionJobs();
													$productionJobInit->where('xe_id', $productionJobLastId)
														->update([
															'current_stage_id' => $currentStageId,
														]);

													//Add assignee data
													$assignee = $this->saveAssigneeData($request, $productionJobLastId, $stageId, $currentStageId);
													if (!empty($assignee)) {
														$type = (isset($assignee['is_group']) && $assignee['is_group'] == 0) ? 'Agent' : 'Group';
														$names = (isset($assignee['names']) && $assignee['names'] != '') ? $assignee['names'] : '';
													}
													$tempSendingEmailData = [
														'customer_id' => '',
														'job_id' => $productionJobLastId,
														'stages_id' => '',
														'is_group' => $assignee['is_group'],
														'agent_ids' => $assignee['agent_id_arr'],
													];
													array_push($finalSendingEmailData, $tempSendingEmailData);
													//Adding to production job log for job creation
													$logData = [
														'job_id' => $productionJobLastId,
														'title' => 'Job assigned',
														'description' => 'Job #' . $jobId . ' assigned to ' . $type . ' ' . $names . ' for ' . $stageName . '.',
														'user_type' => $allPostPutVars['user_type'],
														'user_id' => $allPostPutVars['user_id'],
														'created_date' => date_time(
															'today', [], 'string'
														)
													];
													$this->addingProductionLog($logData);
												}
											}
										}
										//Save global stages
										//Get All Global Status
										if ($localStageFlag != 0) {
											$statusFeaturesInit = new StatusFeatures();
											$globalStatus = $statusFeaturesInit
												->select('production_status_features.status_id', 'production_status_features.duration', 'production_status.status_name', 'production_status.color_code')
												->join('production_status', 'production_status_features.status_id', '=', 'production_status.xe_id')
												->where(['production_status_features.is_global' => 1, 'production_status.store_id' => $getStoreDetails['store_id']])->orderBy('production_status.sort_order', 'ASC');
											if ($globalStatus->count() > 0) {
												$globalStatusArr = $globalStatus->get();
												foreach ($globalStatusArr as $globalStatus) {
													$globalStageData = [
														'job_id' => $productionJobLastId,
														'print_method_id' => 0,
														'stages_id' => $globalStatus['status_id'],
														'stage_name' => $globalStatus['status_name'],
														'stage_color_code' => $globalStatus['color_code'],
														'created_date' => $startingDate,
														'starting_date' => '1000-01-01 00:00:00',
														'exp_completion_date' => '1000-01-01 00:00:00',
														'status' => 'not-started',
													];
													$productionJobGlobalStage = new ProductionJobStages($globalStageData);
													$productionJobGlobalStage->save();
												}
											}
										} else {
											//Get All Global Status
											$statusFeaturesInit = new StatusFeatures();
											$globalStatus = $statusFeaturesInit
												->select('production_status_features.status_id', 'production_status_features.duration', 'production_status.status_name', 'production_status.color_code')
												->join('production_status', 'production_status_features.status_id', '=', 'production_status.xe_id')
												->where(['production_status_features.is_global' => 1,
													'production_status.store_id' => $getStoreDetails['store_id']])->orderBy('production_status.sort_order', 'ASC');
											if ($globalStatus->count() > 0) {
												$nonDecoGlobalStatusArr = $globalStatus->get();
												$nonDecoGlobalStatusArr = json_clean_decode($nonDecoGlobalStatusArr, true);
												$startingDate = date_time(
													'today', [], 'string'
												);
												$duration = $nonDecoGlobalStatusArr[0]['duration'];
												$expCompletionDate = date('Y-m-d H:i:s', strtotime('+' . $duration . ' hour', strtotime($startingDate)));
												$getStartNDuedate = $this->calculateDueDate($request, $response, $startingDate, $duration);
												//$finalSendingEmailData = [];
												foreach ($nonDecoGlobalStatusArr as $globalKey => $nonDecoGlobalStatus) {
													$globalStageData = [
														'job_id' => $productionJobLastId,
														'print_method_id' => 0,
														'stages_id' => $nonDecoGlobalStatus['status_id'],
														'stage_name' => $nonDecoGlobalStatus['status_name'],
														'stage_color_code' => $nonDecoGlobalStatus['color_code'],
														'created_date' => $startingDate,
														'starting_date' => ($globalKey == 0) ? $getStartNDuedate['start_date'] : '1000-01-01 00:00:00',
														'exp_completion_date' => ($globalKey == 0) ? $getStartNDuedate['due_date'] : '1000-01-01 00:00:00',
														'status' => ($globalKey == 0) ? 'in-progress' : 'not-started',
													];
													$productionJobGlobalStage = new ProductionJobStages($globalStageData);
													$productionJobGlobalStage->save();
													if ($globalKey == 0) {
														//update current stage in production table
														$currentStageId = $productionJobGlobalStage->xe_id;
														$productionJobInit = new ProductionJobs();
														$productionJobInit->where('xe_id', $productionJobLastId)
															->update([
																'current_stage_id' => $currentStageId,
															]);

														//Add assignee data
														$assignee = $this->saveAssigneeData($request, $productionJobLastId, $nonDecoGlobalStatus['status_id'], $currentStageId);
														if (!empty($assignee)) {
															$type = (isset($assignee['is_group']) && $assignee['is_group'] == 0) ? 'Agent' : 'Group';
															$names = (isset($assignee['names']) && $assignee['names'] != '') ? $assignee['names'] : '';
														}
														$tempSendingEmailData = [
															'customer_id' => '',
															'job_id' => $productionJobLastId,
															'stages_id' => '',
															'is_group' => $assignee['is_group'],
															'agent_ids' => $assignee['agent_id_arr'],
														];
														array_push($finalSendingEmailData, $tempSendingEmailData);

														//Adding to production job log for job creation
														$logData = [
															'job_id' => $productionJobLastId,
															'title' => 'Job assigned',
															'description' => 'Job #' . $jobId . ' assigned to ' . $type . ' ' . $names . ' for ' . $nonDecoGlobalStatus['status_name'] . '.',
															'user_type' => $allPostPutVars['user_type'],
															'user_id' => $allPostPutVars['user_id'],
															'created_date' => date_time(
																'today', [], 'string'
															)
														];
														$this->addingProductionLog($logData);
													}
												}
											}
										}
									} else {
										//Get All Global Status
										$statusFeaturesInit = new StatusFeatures();
										$globalStatus = $statusFeaturesInit
											->select('production_status_features.status_id', 'production_status_features.duration', 'production_status.status_name', 'production_status.color_code')
											->join('production_status', 'production_status_features.status_id', '=', 'production_status.xe_id')
											->where(['production_status_features.is_global' => 1, 'production_status.store_id' => $getStoreDetails['store_id']])->orderBy('production_status.sort_order', 'ASC');
										if ($globalStatus->count() > 0) {
											$nonDecoGlobalStatusArr = $globalStatus->get();
											$nonDecoGlobalStatusArr = json_clean_decode($nonDecoGlobalStatusArr, true);
											$startingDate = date_time(
												'today', [], 'string'
											);
											$duration = $nonDecoGlobalStatusArr[0]['duration'];
											$expCompletionDate = date('Y-m-d H:i:s', strtotime('+' . $duration . ' hour', strtotime($startingDate)));
											$getStartNDuedate = $this->calculateDueDate($request, $response, $startingDate, $duration);
											//$finalSendingEmailData = [];
											foreach ($nonDecoGlobalStatusArr as $globalKey => $nonDecoGlobalStatus) {
												$globalStageData = [
													'job_id' => $productionJobLastId,
													'print_method_id' => 0,
													'stages_id' => $nonDecoGlobalStatus['status_id'],
													'stage_name' => $nonDecoGlobalStatus['status_name'],
													'stage_color_code' => $nonDecoGlobalStatus['color_code'],
													'created_date' => $startingDate,
													'starting_date' => ($globalKey == 0) ? $getStartNDuedate['start_date'] : '1000-01-01 00:00:00',
													'exp_completion_date' => ($globalKey == 0) ? $getStartNDuedate['due_date'] : '1000-01-01 00:00:00',
													'status' => ($globalKey == 0) ? 'in-progress' : 'not-started',
												];
												$productionJobGlobalStage = new ProductionJobStages($globalStageData);
												$productionJobGlobalStage->save();
												if ($globalKey == 0) {
													//update current stage in production table
													$currentStageId = $productionJobGlobalStage->xe_id;
													$productionJobInit = new ProductionJobs();
													$productionJobInit->where('xe_id', $productionJobLastId)
														->update([
															'current_stage_id' => $currentStageId,
														]);

													//Add assignee data
													$assignee = $this->saveAssigneeData($request, $productionJobLastId, $nonDecoGlobalStatus['status_id'], $currentStageId);
													if (!empty($assignee)) {
														$type = (isset($assignee['is_group']) && $assignee['is_group'] == 0) ? 'Agent' : 'Group';
														$names = (isset($assignee['names']) && $assignee['names'] != '') ? $assignee['names'] : '';
													}
													$tempSendingEmailData = [
														'customer_id' => '',
														'job_id' => $productionJobLastId,
														'stages_id' => '',
														'is_group' => $assignee['is_group'],
														'agent_ids' => $assignee['agent_id_arr'],
													];
													array_push($finalSendingEmailData, $tempSendingEmailData);

													//Adding to production job log for job creation
													$logData = [
														'job_id' => $productionJobLastId,
														'title' => 'Job assigned',
														'description' => 'Job #' . $jobId . ' assigned to ' . $type . ' ' . $names . ' for ' . $nonDecoGlobalStatus['status_name'] . '.',
														'user_type' => $allPostPutVars['user_type'],
														'user_id' => $allPostPutVars['user_id'],
														'created_date' => date_time(
															'today', [], 'string'
														)
													];
													$this->addingProductionLog($logData);
												}
											}
										}
									}
								}
							}
						}
					}
				}

				$jsonResponse = [
					'status' => 1,
					'email_data' => $finalSendingEmailData,
					'message' => message('Production Job', 'saved'),
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => message('Production Job', 'insufficient'),
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get Stages w.r.t Print Method
	 *
	 * @param $printMethodId  Print Method Id
	 *
	 * @author debashrib@riaxe.com
	 * @date   25 Sept 2020
	 * @return json response
	 */

	private function getStagesWrtPrintMethod($printMethodId) {
		$stagesDetails = [];
		if ($printMethodId > 0) {
			$statusPrintProfileRelInit = new StatusPrintProfileRel();
			$stagesData = $statusPrintProfileRelInit->select('status_id')->where('print_profile_id', $printMethodId);
			if ($stagesData->count() > 0) {
				$stagesDataArr = $stagesData->get();
				$stagesDataArr = json_clean_decode($stagesDataArr, true);
				foreach ($stagesDataArr as $stages) {
					$tempArr = $stages;
					$statusFeaturesInit = new StatusFeatures();
					$featureData = $statusFeaturesInit->where('status_id', $stages['status_id']);
					$productionStatusInit = new ProductionStatus();
					$statusData = $productionStatusInit->where('xe_id', $stages['status_id'])->orderBy('sort_order', 'ASC');
					$statusDataArr = $statusData->get();
					$tempArr['status_name'] = $statusDataArr[0]['status_name'];
					$tempArr['color_code'] = $statusDataArr[0]['color_code'];
					if ($featureData->count() > 0) {
						$featureDataArr = $featureData->get();
						$featureDataArr = json_clean_decode($featureDataArr, true);
						$tempArr['duration'] = $featureDataArr[0]['duration'];
						$tempArr['is_group'] = $featureDataArr[0]['is_group'];
						$tempArr['is_global'] = $featureDataArr[0]['is_global'];
					}
					array_push($stagesDetails, $tempArr);
				}
			}
		}
		return $stagesDetails;
	}

	/**
	 * Get All Print Method associated with order item
	 *
	 * @param $orderItemsArr  Order Items Array
	 *
	 * @author debashrib@riaxe.com
	 * @date   25 Sept 2020
	 * @return json response
	 */

	private function getPrintMethodOfOrder($orderItemsArr) {
		$printMethodArr = [];
		if (!empty($orderItemsArr)) {
			foreach ($orderItemsArr['decoration_settings_data'] as $settingData) {
				foreach ($settingData['decoration_data'] as $decorationData) {
					$tempPrintMethod['print_method_id'] = $decorationData['print_profile_id'];
					$tempPrintMethod['print_method_name'] = $decorationData['print_profile_name'];
					if (!in_array($tempPrintMethod, $printMethodArr)) {
						array_push($printMethodArr, $tempPrintMethod);
					}
				}
			}
		}
		return $printMethodArr;
	}

	/**
	 * Adding data to production log
	 *
	 * @param $logData  Log data array
	 *
	 * @author debashrib@riaxe.com
	 * @date   06 Apr 2020
	 * @return boolean
	 */
	public function addingProductionLog($logData) {
		if (!empty($logData)) {
			$productionLog = new ProductionJobLog($logData);
			if ($productionLog->save()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * GET: Production Job List View
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   26 Sept 2020
	 * @return json response
	 */
	public function getProductionListView($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$productionJobInit = new ProductionJobs();
		// Collect all Filter columns from url
		$page = $request->getQueryParam('page');
		$perpage = $request->getQueryParam('perpage');
		$sortBy = $request->getQueryParam('sortby');
		$order = $request->getQueryParam('order');
		$productionJobStatus = $request->getQueryParam('production_job_status');
		$keyword = $request->getQueryParam('keyword');
		$stageId = $request->getQueryParam('stage_id');
		$from = $request->getQueryParam('from');
		$to = $request->getQueryParam('to');
		$agentIdArr = $request->getQueryParam('agent_id');
		$agentIdArr = json_clean_decode($agentIdArr, true);
		$orderIdArr = $request->getQueryParam('order_id');
		$orderIdArr = json_clean_decode($orderIdArr, true);
		$customerId = $request->getQueryParam('customer_id');
		$printMethodArr = $request->getQueryParam('print_methods');
		$printMethodArr = json_clean_decode($printMethodArr, true);
		$jobId = $request->getQueryParam('job_id');

		$productionJob = $productionJobInit
			->join('production_job_stages', 'production_jobs.current_stage_id', '=', 'production_job_stages.xe_id')
			->join('orders', 'production_jobs.order_id', '=', 'orders.order_id')
			->select('production_jobs.xe_id', 'production_jobs.store_id', 'production_jobs.job_id', 'production_jobs.order_id', 'production_jobs.order_item_id', 'production_jobs.order_item_quantity', 'production_jobs.job_title', 'production_jobs.job_status', 'production_jobs.note', 'production_jobs.comp_percentage', 'production_jobs.due_date', 'production_jobs.scheduled_date', 'production_jobs.created_at', 'production_jobs.current_stage_id', 'production_job_stages.xe_id as current_xe_id', 'production_job_stages.job_id as current_job_id', 'production_job_stages.print_method_id', 'production_job_stages.stages_id', 'production_job_stages.stage_name', 'production_job_stages.stage_color_code', 'production_job_stages.created_date', 'production_job_stages.starting_date', 'production_job_stages.exp_completion_date', 'production_job_stages.completion_date', 'production_job_stages.status', 'production_job_stages.message', 'orders.customer_id', 'orders.order_number')
			->where('production_jobs.store_id', $getStoreDetails['store_id']);

		//Filter by order
		if (isset($orderIdArr) && !empty($orderIdArr)) {
			$productionJob->whereIn('production_jobs.order_id', $orderIdArr);
		}
		//Filter by customer
		if (isset($customerId) && $customerId > 0) {
			$productionJob->where('orders.customer_id', $customerId);
		}

		//Filter by Production job
		if (isset($jobId) && $jobId > 0) {
			$productionJob->where('production_jobs.xe_id', $jobId);
		}

		//Filter by print method id
		if (isset($printMethodArr) && !empty($printMethodArr)) {
			$productionJob->whereIn('production_job_stages.print_method_id', $printMethodArr);
		}

		//Filter by keywords
		if (isset($keyword) && $keyword != "") {
			$productionJob->where('production_jobs.job_title', 'LIKE', '%' . $keyword . '%')
				->orWhere('production_jobs.job_id', 'LIKE', '%' . $keyword . '%')
				->orWhere('production_jobs.order_id', 'LIKE', '%' . $keyword . '%');
		}

		//Filter by expected completion date
		if (isset($from) && isset($to) && $from != "" && $to != "") {
			$to = date('Y-m-d H:i:s', strtotime($to . ' +1 day'));
			$productionJob->where('production_job_stages.exp_completion_date', '>=', $from)
				->where('production_job_stages.exp_completion_date', '<=', $to);
		}

		//Filter by current stage status
		if (isset($productionJobStatus) && $productionJobStatus != '') {
			$productionJob->where('production_job_stages.status', '=', $productionJobStatus);
		}

		//Filter by current stage id
		if (isset($stageId) && $stageId > 0) {
			$productionJob->where('production_job_stages.stages_id', '=', $stageId);
		}

		//Filter by agents
		if (isset($agentIdArr) && !empty($agentIdArr)) {
			$userRoleRelInit = new UserRoleRel();
			$userRole = $userRoleRelInit->select('role_id')->whereIn('user_id', $agentIdArr);
			$groupIdArr = [];
			$jobIdDataArr = [];
			if ($userRole->count() > 0) {
				$userRoleData = $userRole->get();
				$userRoleData = json_clean_decode($userRoleData, true);
				$groupIds = array_column($userRoleData, 'role_id');
				$groupIdArr = array_unique($groupIds);
			}
			$productionJobAgentInit = new ProductionJobAgents();
			$agentJobId = $productionJobAgentInit->select('job_id', 'job_stage_id')->whereIn('agent_id', $agentIdArr)
				->orWhere(function ($query) use ($groupIdArr) {
					$query->where('agent_id', $groupIdArr)
						->where('is_group', 1);
				});
			if ($agentJobId->count() > 0) {
				$jobIdData = $agentJobId->get();
				$jobIdData = json_clean_decode($jobIdData, true);
				foreach ($jobIdData as $id) {
					$finalJob = $productionJobInit->where([
						'xe_id' => $id['job_id'],
						'current_stage_id' => $id['job_stage_id'],
					]);
					if ($finalJob->count() > 0) {
						$finalData = $finalJob->get();
						$finalData = json_clean_decode($finalData, true);
						array_push($jobIdDataArr, $finalData[0]['xe_id']);
					}
				}
			}
			$productionJob->whereIn('production_jobs.xe_id', $jobIdDataArr);
		}

		$getTotalPerFilters = $productionJob->count();
		$offset = 0;
		if (isset($page) && $page != "") {
			$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
			$offset = $totalItem * ($page - 1);
			$productionJob->skip($offset)->take($totalItem);
		}
		// Sorting by column name and sord order parameter
		if (isset($sortBy) && $sortBy != "" && isset($order) && $order != "") {
			$productionJob->orderBy($sortBy, $order);
		}
		if ($getTotalPerFilters > 0) {
			$getProductionJobData = [];
			$productionJobArr = $productionJob->get();
			foreach ($productionJobArr as $productionJob) {
				$tempProductionJob = $productionJob;
				//$currentStage = $this->getJobCurrentStage($productionJob['xe_id']);
				$tempProductionJob['order_number'] = ($tempProductionJob['order_number'] != '') ? $tempProductionJob['order_number'] : $tempProductionJob['order_id']; 
				$currentStage = [
					'xe_id' => $productionJob['current_xe_id'],
					'job_id' => $productionJob['current_job_id'],
					'print_method_id' => $productionJob['print_method_id'],
					'stages_id' => $productionJob['stages_id'],
					'status_name' => $productionJob['stage_name'],
					'status_color_code' => $productionJob['stage_color_code'],
					'created_date' => $productionJob['created_date'],
					'starting_date' => $productionJob['starting_date'],
					'exp_completion_date' => $productionJob['exp_completion_date'],
					'completion_date' => $productionJob['completion_date'],
					'status' => $productionJob['status'],
					'message' => $productionJob['message'],
				];
				unset($tempProductionJob['current_xe_id'], $tempProductionJob['current_job_id'], $tempProductionJob['print_method_id'], $tempProductionJob['stages_id'], $tempProductionJob['stage_name'], $tempProductionJob['stage_color_code'], $tempProductionJob['created_date'], $tempProductionJob['starting_date'], $tempProductionJob['exp_completion_date'], $tempProductionJob['completion_date'], $tempProductionJob['status'], $tempProductionJob['message']);
				//Get assignee
				$productionJobAgentInit = new ProductionJobAgents();
				$assigneeData = $productionJobAgentInit->select('is_group', 'agent_id')->where([
					'job_id' => $currentStage['job_id'],
					'job_stage_id' => $currentStage['xe_id'],
				]);
				$finalAssignee = [];
				$finalIsGroup = [];
				if ($assigneeData->count() > 0) {
					$assigneeDataArr = $assigneeData->get();
					$assigneeDataArr = json_clean_decode($assigneeDataArr, true);
					foreach ($assigneeDataArr as $assignee) {
						array_push($finalAssignee, $assignee['agent_id']);
					}
				}
				$currentStage['is_group'] = $assigneeDataArr[0]['is_group'];
				$currentStage['assignee_data'] = $finalAssignee;
				$groupAgentId = [];
				if ($currentStage['is_group'] == 1) {
					foreach ($finalAssignee as $assignee) {
						$agentDetailsArr = array_filter($allAgentArr, function ($item) use ($assignee) {
							return $item['role_id'] == $assignee;
						});
						foreach ($agentDetailsArr as $agents) {
							array_push($groupAgentId, $agents['id']);
						}
					}
				}
				//Get PO Status
				$poStatus = '';
				$poStatusColorCode = '';
				$purchaseOrderItemInit = new PurchaseOrderItems();
				$purchaseOrderItem = $purchaseOrderItemInit
					->join('po_line_item_status', 'po_line_item_status.xe_id', '=', 'purchase_order_items.status_id')
					->select('po_line_item_status.status_name', 'po_line_item_status.color_code')
					->where([
						'purchase_order_items.order_id' => $productionJob['order_id'],
						'purchase_order_items.order_item_id' => $productionJob['order_item_id'],
					]);

				if ($purchaseOrderItem->count() > 0) {
					$poData = $purchaseOrderItem->get();
					$poData = json_clean_decode($poData, true);
					$poStatus = $poData[0]['status_name'];
					$poStatusColorCode = $poData[0]['color_code'];
				}
				$tempProductionJob['po_status'] = $poStatus;
				$tempProductionJob['po_status_color_code'] = $poStatusColorCode;
				$tempProductionJob['current_stage'] = $currentStage;

				array_push($getProductionJobData, $tempProductionJob);
			}
			$jsonResponse = [
				'status' => 1,
				'records' => count($getProductionJobData),
				'total_records' => $getTotalPerFilters,
				'data' => $getProductionJobData,
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => message('Production Job', 'not_found'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get the current stage of production job
	 *
	 * @param $jobId  Production job id
	 *
	 * @author debashrib@riaxe.com
	 * @date   28 Sept 2020
	 * @return json response
	 */

	private function getJobCurrentStage($jobId) {
		$productionJobStageInit = new ProductionJobStages();
		$currentStage = [];
		if ($jobId > 0) {
			$jobStage = $productionJobStageInit
				->select('xe_id', 'job_id', 'print_method_id', 'stages_id', 'stage_name as status_name', 'stage_color_code as status_color_code', 'created_date', 'starting_date', 'exp_completion_date', 'completion_date', 'status', 'message')
				->where('job_id', $jobId)
				->where('status', '!=', 'not-started')
				->skip(0)->take(1)
				->orderBy('xe_id', 'DESC');
			if ($jobStage->count() > 0) {
				$jobStageArr = $jobStage->get();
				$jobStageArr = json_clean_decode($jobStageArr, true);
				$currentStage = $jobStageArr[0];
			}
		}
		return $currentStage;
	}

	/**
	 * GET: Production Job List View
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   26 Sept 2020
	 * @return json response
	 */
	public function getProductionCardView($request, $response) {
		$getStoreDetails = get_store_details($request);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job', 'error'),
		];
		// Collect all Filter columns from url
		$page = $request->getQueryParam('page');
		$perpage = $request->getQueryParam('perpage');
		$orderId = $request->getQueryParam('order_id');
		$customerId = $request->getQueryParam('customer_id');
		$printMethodArr = $request->getQueryParam('print_methods');
		$printMethodArr = json_clean_decode($printMethodArr, true);
		$status = $request->getQueryParam('status');

		$orderInit = new Orders();
		$orderData = $orderInit
			->select('xe_id', 'order_id', 'artwork_status', 'order_status', 'po_status', 'production_status', 'production_percentage', 'store_id', 'customer_id', 'order_number', DB::raw('(SELECT created_at FROM production_jobs WHERE order_id = orders.order_id LIMIT 1) as created_at '))
			->where('store_id', $getStoreDetails['store_id'])
			->where('production_status', '!=', '0');

		if (isset($orderId) && $orderId != '') {
			$orderData->where('order_id', $orderId);
		}

		if (isset($customerId) && $customerId != '') {
			$orderData->where('customer_id', $customerId);
		}

		//filter by status
		if (isset($status) && $status != '') {
			if ($status == 'completed') {
				$orderData->where('production_status', '2');
			} else if ($status == 'inprogress') {
				$orderData->where('production_status', '1');
			}
		}

		if (isset($printMethodArr) && !empty($printMethodArr)) {
			$productionJobInit = new ProductionJobs();
			$productionJob = $productionJobInit
				->join('production_job_stages', 'production_jobs.current_stage_id', '=', 'production_job_stages.xe_id')
				->select('production_jobs.order_id')
				->where('production_jobs.store_id', $getStoreDetails['store_id'])
				->whereIn('production_job_stages.print_method_id', $printMethodArr);
			if ($productionJob->count() > 0) {
				$productionJobData = $productionJob->get();
				$productionJobData = json_clean_decode($productionJobData, true);
				$orderIdsArr = array_column($productionJobData, 'order_id');
				$finalOrderIdsArr = array_unique($orderIdsArr);
				if (!empty($finalOrderIdsArr)) {
					$orderData->whereIn('order_id', $finalOrderIdsArr);
				}
			}

		}

		$getTotalPerFilters = $orderData->count();
		$offset = 0;
		if (isset($page) && $page != "") {
			$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
			$offset = $totalItem * ($page - 1);
			$orderData->skip($offset)->take($totalItem);
		}
		$orderData->orderBy('created_at', 'DESC');
		if ($getTotalPerFilters > 0) {
			$orderDataArr = $orderData->get();
			$cardViewData = [];
			foreach ($orderDataArr as $orders) {
				$orderId = $orders['order_id'];
				$tempOrderData['order_id'] = $orderId;
				$tempOrderData['order_number'] = ($orders['order_number'] != '') ? $orders['order_number'] : $orderId;
				$tempOrderData['order_production_status'] = ($orders['production_status'] == '1') ? 'In-Progress' : 'Completed';
				$tempOrderData['order_com_percentage'] = $orders['production_percentage'];
				$tempOrderData['items'] = [];
				array_push($cardViewData, $tempOrderData);
			}
			$jsonResponse = [
				'status' => 1,
				'records' => count($cardViewData),
				'total_records' => $getTotalPerFilters,
				'data' => $cardViewData,
			];
		} else {
			$jsonResponse = [
				'status' => 1,
				'data' => [],
				'message' => message('Production Job', 'not_found'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Production Job List View
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   06 Oct 2020
	 * @return json response
	 */
	public function getCardViewDetails($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Production Job', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        if (!empty($args['id'])) {
            $agentId = $request->getQueryParam('agent_id');
            $finalResult = [];
            $globalStatus = [];
            $orderId = $args['id'];
            $ordersControllerInit = new OrdersController();
            $orderDetails = $ordersControllerInit->getOrderList($request, $response, $args, 1);
            $orderItems = $orderDetails['data']['orders'];
            //Get all Agent list 
            $userInit = new User();
            $getUser = $userInit->select('admin_users.xe_id as id', 'admin_users.name', 'admin_users.email', 'user_role_rel.role_id')
                ->join('user_role_rel', 'admin_users.xe_id', '=', 'user_role_rel.user_id') 
                ->where('admin_users.store_id', $getStoreDetails['store_id']);
            $allAgentArr = json_clean_decode($getUser->get(), true);
            $itemData = [];
            $finalGlobalStatus = [];
            $productionJobInit = new ProductionJobs();
            $productionJobStageInit = new ProductionJobStages();
            $globalProductionJob = $productionJobInit->where('order_id', $orderId);
            if ($globalProductionJob->count() > 0) {
                $globalProductionJob = $globalProductionJob->get();
                $globalProductionJob = json_clean_decode($globalProductionJob, true);
                $globalProductionJob = $globalProductionJob[0];
                $globalJobId = $globalProductionJob['xe_id'];
                $globalStageData = $productionJobStageInit->select('stages_id')->where([
                    'job_id' => $globalJobId,
                    'print_method_id' => 0
                ])->orderBy('xe_id', 'ASC');
                if ($globalStageData->count() > 0) {
                    $globalStageDataArr = $globalStageData->get();
                    $globalStageDataArr = json_clean_decode($globalStageDataArr, true);
                }
            }
            foreach ($orderItems as $items) {
                $productionJob = $productionJobInit->where([
                    'order_id' => $orderId,
                    'order_item_id' => $items['id']
                ]);
                if ($productionJob->count() > 0) {
                    $productionJobData = $productionJob->get();
                    $productionJobData = json_clean_decode($productionJobData, true);
                    $tempItemData = $productionJobData[0];
                }
                $tempItemData['product_id'] = $items['product_id'];
                $tempItemData['product_name'] = $items['name'];
                $tempItemData['product_sku'] = $items['sku'];
                $tempItemData['quantity'] = $items['quantity'];
                //Get print methods
                $printMethodArr = $this->getPrintMethodOfOrder($items);
                $printMethodStagesData = [];
                foreach ($printMethodArr as $printMethod) {
                    $tempPrintMethodStagesData = $printMethod;
                    $productionJobStages = $productionJobStageInit->where([
                        'job_id' => $tempItemData['xe_id'],
                        'print_method_id' => $printMethod['print_method_id']
                    ])->orderBy('xe_id', 'ASC');
                    if ($productionJobStages->count() > 0) {
                        $productionJobStageArr = $productionJobStages->get();
                        $productionJobStageArr = json_clean_decode($productionJobStageArr, true);
                        $finalStageData = [];
                        foreach ($productionJobStageArr as $stageData) {
                            $tempStageData = $stageData;
                            //Get assignee
                            $productionJobAgentInit = new ProductionJobAgents();
                            $assigneeData = $productionJobAgentInit->where([
                                'job_id' => $stageData['job_id'],
                                'job_stage_id' => $stageData['xe_id']
                            ]);
                            $finalAssignee = [];
                            $finalIsGroup = [];
                            if ($assigneeData->count() > 0) {
                                $assigneeDataArr = $assigneeData->get();
                                foreach ($assigneeDataArr as $assignee) {
                                    array_push($finalAssignee, $assignee['agent_id']);
                                    array_push($finalIsGroup, $assignee['is_group']);
                                }
                            }
                            $tempStageData['is_group'] = $finalIsGroup[0];
                            $tempStageData['assignee_data'] = $finalAssignee;
                            $groupAgentId = [];
                            if ($finalIsGroup[0] == 1) {
                                foreach($finalAssignee as $assignee) {
                                    $agentDetailsArr = array_filter($allAgentArr, function ($item) use ($assignee) {
                                        return $item['role_id'] == $assignee;
                                    });
                                    foreach ($agentDetailsArr as $agents) {
                                        array_push($groupAgentId, $agents['id']);
                                    }
                                } 
                            }
                            //For agent view
                            if (isset($agentId) && $agentId != '' && $agentId > 0) {
                                if (in_array($agentId, $finalAssignee) || in_array($agentId, $groupAgentId)) {
                                    $tempStageData['is_agent_operate'] = 1;
                                } else {
                                    $tempStageData['is_agent_operate'] = 0;
                                }
                            }
                            $tempStageData['created_date'] = ($tempStageData['created_date'] != '0000-00-00 00:00:00' && $tempStageData['created_date'] != null) ? $tempStageData['created_date'] : '';
                            $tempStageData['starting_date'] = ($tempStageData['starting_date'] != '0000-00-00 00:00:00' && $tempStageData['starting_date'] != null) ? $tempStageData['starting_date'] : '';
                            $tempStageData['exp_completion_date'] = ($tempStageData['exp_completion_date'] != '0000-00-00 00:00:00' && $tempStageData['exp_completion_date'] != null) ? $tempStageData['exp_completion_date'] : '';
                            $tempStageData['completion_date'] = ($tempStageData['completion_date'] != '0000-00-00 00:00:00' && $tempStageData['completion_date'] != null) ? $tempStageData['completion_date'] : '';
                            array_push($finalStageData, $tempStageData);
                        }
                    }
                    $tempPrintMethodStagesData['stages_data'] = $finalStageData;
                    array_push($printMethodStagesData, $tempPrintMethodStagesData);
                }
                $tempItemData['print_method'] = $printMethodStagesData;
                array_push($itemData, $tempItemData);
            }
            //Get Global Stages
            foreach ($globalStageDataArr as $globals) {
                $tempData['stage_id'] = $globals['stages_id'];
                $jobData = $productionJobInit->where([
                    'order_id' => $orderId
                ]);
                if ($jobData->count() > 0) {
                    $productionJobData = $jobData->get();
                    $productionJobData = json_clean_decode($productionJobData, true);
                    $globalStageDataArr = [];
                    foreach ($productionJobData as $productionJob) {
                        $orderItemId = $productionJob['order_item_id'];
                        $orderItemDetails = array_filter($orderItems, function ($item) use ($orderItemId) {
                            return $item['id'] == $orderItemId;
                        });
                        $orderItemDetails = $orderItemDetails[array_keys($orderItemDetails)[0]];
                        $globalStages = $productionJobStageInit
                        ->select('production_job_stages.xe_id', 'production_job_stages.job_id', 'production_job_stages.print_method_id', 'production_job_stages.stages_id', 'production_job_stages.stage_name', 'production_job_stages.stage_color_code', 'production_job_stages.created_date', 'production_job_stages.starting_date', 'production_job_stages.exp_completion_date', 'production_job_stages.completion_date', 'production_job_stages.status', 'production_job_stages.message', 'production_jobs.job_id as job_id_name', 'production_jobs.job_title')
                        ->join('production_jobs', 'production_job_stages.job_id', '=', 'production_jobs.xe_id')
                        ->where([
                            'production_job_stages.job_id' => $productionJob['xe_id'],
                            'production_job_stages.print_method_id' => 0,
                            'production_job_stages.stages_id' => $globals['stages_id']
                        ]);
                        if ($globalStages->count() > 0) {
                            $globalStageData = $globalStages->get();
                            $globalStageData = json_clean_decode($globalStageData, true);
                            $globalStageData = $globalStageData[0];
                            $firstStageName = $globalStageData['stage_name'];
                            $firstStageColorCode = $globalStageData['stage_color_code'];
                            $globalStageData['created_date'] = ($globalStageData['created_date'] != '0000-00-00 00:00:00' && $globalStageData['created_date'] != null) ? $globalStageData['created_date'] : '';
                            $globalStageData['starting_date'] = ($globalStageData['starting_date'] != '0000-00-00 00:00:00' && $globalStageData['starting_date'] != null) ? $globalStageData['starting_date'] : '';
                            $globalStageData['exp_completion_date'] = ($globalStageData['exp_completion_date'] != '0000-00-00 00:00:00' && $globalStageData['exp_completion_date'] != null) ? $globalStageData['exp_completion_date'] : '';
                            $globalStageData['completion_date'] = ($globalStageData['completion_date'] != '0000-00-00 00:00:00' && $globalStageData['completion_date'] != null) ? $globalStageData['completion_date'] : '';
                            $globalStageData['quantity'] = $orderItemDetails['quantity'];
                            //Get assignee
                            $productionJobAgentInit = new ProductionJobAgents();
                            $assigneeData = $productionJobAgentInit->where([
                                'job_id' => $productionJob['xe_id'],
                                'job_stage_id' => $productionJob['current_stage_id']
                            ]);
                            $finalAssignee = [];
                            $finalIsGroup = [];
                            if ($assigneeData->count() > 0) {
                                $assigneeDataArr = $assigneeData->get();
                                foreach ($assigneeDataArr as $assignee) {
                                    array_push($finalAssignee, $assignee['agent_id']);
                                    array_push($finalIsGroup, $assignee['is_group']);
                                }
                            }
                            $globalStageData['is_group'] = $finalIsGroup[0];
                            $globalStageData['assignee_data'] = $finalAssignee;
                            $groupAgentId = [];
                            if ($finalIsGroup[0] == 1) {
                                foreach($finalAssignee as $assignee) {
                                    $agentDetailsArr = array_filter($allAgentArr, function ($item) use ($assignee) {
                                        return $item['role_id'] == $assignee;
                                    });
                                    foreach ($agentDetailsArr as $agents) {
                                        array_push($groupAgentId, $agents['id']);
                                    }
                                } 
                            }
                            //For agent view
                            if (isset($agentId) && $agentId != '' && $agentId > 0) {
                                if (in_array($agentId, $finalAssignee) || in_array($agentId, $groupAgentId)) {
                                    $globalStageData['is_agent_operate'] = 1;
                                } else {
                                    $globalStageData['is_agent_operate'] = 0;
                                }
                            }
                            array_push($globalStageDataArr, $globalStageData);
                        }
                        
                    }
                }
                if (!empty($globalStageDataArr)) {
                    $tempData['stage_name'] = $firstStageName;
                    $tempData['stage_color_code'] = $firstStageColorCode;
                    //$tempData['assignee_data'] = [];
                    $tempData['stage_data'] = $globalStageDataArr;
                    array_push($finalGlobalStatus, $tempData);
                }
            }
            $finalResult['items'] = $itemData;
            $finalResult['global_status'] = $finalGlobalStatus;
            
            $jsonResponse = [
                'status' => 1,
                'data' => $finalResult,
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

	/**
	 * POST: Mark Production job stage as completed
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   29 Sept 2020
	 * @return json response
	 */
	public function productionJobStageOperations($request, $response) {
		$getStoreDetails = get_store_details($request);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Stage Operation', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$productionJobStageId = isset($allPostPutVars['current_stage_id']) ? $allPostPutVars['current_stage_id'] : 0;
		$type = isset($allPostPutVars['type']) ? $allPostPutVars['type'] : '';
		$nextProductionJobStageId = (isset($allPostPutVars['next_stage_id']) && $allPostPutVars['next_stage_id'] > 0) ? $allPostPutVars['next_stage_id'] : 0;

		if ($productionJobStageId && $type != '') {
			$productionJobInit = new ProductionJobs();
			$productionJobStageInit = new ProductionJobStages();
			$orderInit = new Orders();
			$stageData = $productionJobStageInit->where([
				'xe_id' => $productionJobStageId,
			]);
			if ($stageData->count() > 0) {
				$stageDataArr = $stageData->get();
				$stageDataArr = json_clean_decode($stageDataArr, true);
				$stageDataArr = $stageDataArr[0];
				//Get Production Job Data
				$productionJobData = $this->getProductJobData($stageDataArr['job_id']);
				$orderId = $productionJobData['order_id'];
				$jobIdName = $productionJobData['job_id'];
				$customerId = $productionJobData['customer_id'];

				//Get Production Stage Data
				$productionStatusData = $this->getProductJobStageData($stageDataArr['stages_id']);
				$stageName = $productionStatusData['status_name'];
				//Get New stage details
				if ($nextProductionJobStageId > 0) {
					$nextStage = $productionJobStageInit->where('xe_id', $nextProductionJobStageId);
					$nextStageData = $nextStage->get();
					$nextStageData = json_clean_decode($nextStageData, true);
					$newStageName = $nextStageData[0]['stage_name'];
				}
				//Get All Print Profile
				$printMethodData = $this->getPrintMethodData($stageDataArr['print_method_id']);
				$printMethodName = $printMethodData['name'];

				if ($type == 'completed') {
					//Set Status to Completed
					$productionJobStageInit->where('xe_id', $productionJobStageId)
						->update([
							'completion_date' => date_time(
								'today', [], 'string'
							),
							'status' => 'completed'
						]);

					//Start next job
					$assignee = [];
					if ($nextProductionJobStageId > 0) {
						$nextStageData = $productionJobStageInit->where('xe_id', $nextProductionJobStageId);
						if ($nextStageData->count() > 0) {
							$nextStageDetails = $nextStageData->get();
							$nextStageDetails = json_clean_decode($nextStageDetails, true);

							$nextProductionStatusData = $this->getProductJobStageData($nextStageDetails[0]['stages_id']);
							$nextDuration = $nextProductionStatusData['feature_data']['duration'];
							$nextStartingDate = date_time(
								'today', [], 'string'
							);
							$nextExpCompletionDate = date('Y-m-d H:i:s', strtotime('+' . $nextDuration . ' hour', strtotime($nextStartingDate)));
							$getStartNDuedate = $this->calculateDueDate($request, $response, $nextStartingDate, $nextDuration);
							//Start the next stage
							$productionJobStageInit->where('xe_id', $nextProductionJobStageId)
								->update([
									'starting_date' => $getStartNDuedate['start_date'],
									'exp_completion_date' =>  $getStartNDuedate['due_date'],
									'status' => 'in-progress',
								]);
							//Update the current stage in production
							$productionJobInit = new ProductionJobs();
							$productionJobInit->where('xe_id', $nextStageDetails[0]['job_id'])
								->update([
									'current_stage_id' => $nextProductionJobStageId,
								]);
							//Add assignee data
							$assignee = $this->saveAssigneeData($request, $nextStageDetails[0]['job_id'], $nextStageDetails[0]['stages_id'], $nextProductionJobStageId);
							//Adding to production job log
							if ($printMethodName != '') {
								if ($newStageName != '') {
									if (!empty($assignee)) {
										$type = (isset($assignee['is_group']) && $assignee['is_group'] == 0) ? 'Agent' : 'Group';
										$names = (isset($assignee['names']) && $assignee['names'] != '') ? $assignee['names'] : '';
									}
									$description = 'Status of Job #' . $jobIdName . ' changed from ' . $stageName . ' to ' . $newStageName . ' for print method ' . $printMethodName . ' and auto assigned to ' . $type . ' ' . $names . '.';
								} else {
									$description = 'Status of Job #' . $jobIdName . ' changed from ' . $stageName . ' to ' . $newStageName . ' for print method ' . $printMethodName . '.';
								}
							} else {
								if ($newStageName != '') {
									if (!empty($assignee)) {
										$type = (isset($assignee['is_group']) && $assignee['is_group'] == 0) ? 'Agent' : 'Group';
										$names = (isset($assignee['names']) && $assignee['names'] != '') ? $assignee['names'] : '';
									}
									$description = 'Status of Job #' . $jobIdName . ' changed from ' . $stageName . ' to ' . $newStageName . ' and auto assigned to ' . $type . ' ' . $names . '.';
								} else {
									$description = 'Status of Job #' . $jobIdName . ' changed from ' . $stageName . ' to ' . $newStageName . '.';
								}
							}
							$logData = [
								'job_id' => $stageDataArr['job_id'],
								'title' => 'Status changed and auto assigned',
								'description' => $description,
								'user_type' => $allPostPutVars['user_type'],
								'user_id' => $allPostPutVars['user_id'],
								'created_date' => date_time(
									'today', [], 'string'
								)
							];
							$this->addingProductionLog($logData);
						}
					}

					//Update the completed percentage
					$totalStages = $productionJobStageInit->where('job_id', $stageDataArr['job_id']);
					$totalStagesCount = $totalStages->count();
					$completedStages = $productionJobStageInit->where([
						'job_id' => $stageDataArr['job_id'],
						'status' => 'completed',
					]);
					$completedStagesCount = $completedStages->count();
					$percentage = ($completedStagesCount / $totalStagesCount) * 100;
					$productionJobInit->where('xe_id', $stageDataArr['job_id'])
						->update([
							'comp_percentage' => round($percentage),
						]);
					//Mark the job as completed
					if ($totalStagesCount == $completedStagesCount) {
						$jobId = $stageDataArr['job_id'];
						$productionJobInit->where('xe_id', $stageDataArr['job_id'])
							->update([
								'job_status' => 'completed',
							]);
						//Add to production log
						$logData = [
							'job_id' => $stageDataArr['job_id'],
							'title' => '<span  class="text-success">Job completed</span>',
							'description' => '<span  class="text-success">Job #' . $jobIdName . ' is completed.</span>',
							'user_type' => $allPostPutVars['user_type'],
							'user_id' => $allPostPutVars['user_id'],
							'created_date' => date_time(
								'today', [], 'string'
							)
						];
						$this->addingProductionLog($logData);
						//Change the order completed percentage
						$allProductionJob = $productionJobInit->where('order_id', $orderId);
						$totalProductionJobCount = $allProductionJob->count();
						$complatedProductionJob = $productionJobInit->where([
							'order_id' => $orderId,
							'job_status' => 'completed',
						]);
						$complatedProductionJobCount = $complatedProductionJob->count();
						$orderPercentage = ($complatedProductionJobCount / $totalProductionJobCount) * 100;
						$orderInit->where('order_id', $orderId)
							->update([
								'production_percentage' => round($orderPercentage),
							]);

						if ($totalProductionJobCount == $complatedProductionJobCount) {
							$orderInit->where('order_id', $orderId)
								->update([
									'production_status' => '2',
								]);
						}
					}
				}
			}
			$currentOrder = $orderInit->where('order_id', $orderId);
			$currentOrderData = $currentOrder->get();
			$currentOrderData = json_clean_decode($currentOrderData, true);
			$orderStatus = ($currentOrderData[0]['production_status'] == '1') ? 'In-Progress' : 'Completed';
			$orderPercentage = $currentOrderData[0]['production_percentage'];
			$sendingEmailData = [
				'customer_id' => $customerId,
				'job_id' => $stageDataArr['job_id'],
				'stages_id' => $stageDataArr['stages_id'],
				'is_group' => ($nextProductionJobStageId > 0) ? $assignee['is_group'] : '',
				'agent_ids' => ($nextProductionJobStageId > 0) ? $assignee['agent_id_arr'] : [],
			];
			$jsonResponse = [
				'status' => 1,
				'order_production_status' => $orderStatus,
				'order_com_percentage' => $orderPercentage,
				'email_data' => $sendingEmailData,
				'message' => message('Stage Operation', 'done'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get production job data
	 *
	 * @param $jobId  Production job id
	 *
	 * @author debashrib@riaxe.com
	 * @date   29 Sept 2020
	 * @return json response
	 */

	private function getProductJobData($jobId) {
		$productionJobDataArr = [];
		if ($jobId != '' && $jobId > 0) {
			$productionJobInit = new ProductionJobs();
			$productionJob = $productionJobInit
				->select('production_jobs.xe_id', 'production_jobs.job_id', 'production_jobs.order_id', 'production_jobs.order_item_id', 'production_jobs.order_item_quantity', 'production_jobs.job_title', 'production_jobs.job_status', 'production_jobs.note', 'production_jobs.comp_percentage', 'production_jobs.due_date', 'production_jobs.scheduled_date', 'production_jobs.created_at', 'production_jobs.current_stage_id', 'orders.customer_id')
				->join('orders', 'production_jobs.order_id', '=', 'orders.order_id')
				->where('production_jobs.xe_id', $jobId);
			$productionJobData = $productionJob->get();
			$productionJobData = json_clean_decode($productionJobData, true);
			$productionJobDataArr = $productionJobData[0];
		}
		return $productionJobDataArr;
	}

	/**
	 * Get production job stage data
	 *
	 * @param $stageId  Production job stage id
	 *
	 * @author debashrib@riaxe.com
	 * @date   29 Sept 2020
	 * @return json response
	 */

	private function getProductJobStageData($stageId) {
		$productionJobStageDataArr = [];
		if ($stageId != '' && $stageId > 0) {
			$productionStatusInit = new ProductionStatus();
			$productionStatus = $productionStatusInit->where('xe_id', $stageId);
			$productionStatusData = $productionStatus->get();
			$productionStatusData = json_clean_decode($productionStatusData, true);
			$productionJobStageDataArr = $productionStatusData[0];
			// Get Feature Data
			$stageFeatureDataArr = [];
			$statusFeatureInit = new StatusFeatures();
			$stageFeature = $statusFeatureInit->where('status_id', $stageId);
			if ($stageFeature->count() > 0) {
				$stageFeatureData = $stageFeature->get();
				$stageFeatureData = json_clean_decode($stageFeatureData, true);
				$stageFeatureDataArr = $stageFeatureData[0];
			}
			$productionJobStageDataArr['feature_data'] = $stageFeatureDataArr;
		}
		return $productionJobStageDataArr;
	}

	/**
	 * Get print method data
	 *
	 * @param $printMethodId  Print method id
	 *
	 * @author debashrib@riaxe.com
	 * @date   29 Sept 2020
	 * @return json response
	 */

	private function getPrintMethodData($printMethodId) {
		$printMethodDataArr = [];
		if ($printMethodId != '' && $printMethodId > 0) {
			$printProfileInit = new PrintProfile();
			$printMethodData = $printProfileInit->select('name')->where('xe_id', $printMethodId)->first();
			$printMethodDataArr = json_clean_decode($printMethodData, true);
		}
		return $printMethodDataArr;
	}

	/**
	 * POST: Production job stage dealy
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   29 Sept 2020
	 * @return json response
	 */
	public function productionJobStageDelayed($request, $response) {
		$getStoreDetails = get_store_details($request);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Stage Delayed', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$productionJobStageInit = new ProductionJobStages();
		$today = date_time(
			'today', [], 'string'
		);
		$productionJob = $productionJobStageInit->where('status', 'in-progress')
			->where('exp_completion_date', '<', $today);
		$productionJobIdArr = [];
		if ($productionJob->count() > 0) {
			$productionJobArr = $productionJob->get();
			foreach ($productionJobArr as $jobData) {
				$jobId = $jobData['job_id'];
				$printMethodId = $jobData['print_method_id'];
				$stageId = $jobData['stages_id'];
				//Update status as delayed
				$productionJobStageInit->where('xe_id', $jobData['xe_id'])
					->update([
						'status' => 'delay',
					]);
				//Get Production Job Data
				$productionJobData = $this->getProductJobData($jobId);
				$jobIdName = $productionJobData['job_id'];
				//Get Production Stage Data
				$productionStatusData = $this->getProductJobStageData($stageId);
				$stageName = $productionStatusData['status_name'];
				//Get All Print Profile
				$printMethodData = $this->getPrintMethodData($printMethodId);
				$printMethodName = $printMethodData['name'];
				if ($printMethodName != '') {
					$description = '<span  class="text-danger">Due date of job #' . $jobIdName . ' for print method ' . $printMethodName . ' is over and status set as "Delayed".</span>';
				} else {
					$description = '<span  class="text-danger">Due date of job #' . $jobIdName . ' is over and status set as "Delayed".</span>';
				}
				$logData = [
					'job_id' => $jobId,
					'title' => '<span  class="text-danger">Overdue Alert</span>',
					'description' => $description,
					'user_type' => $allPostPutVars['user_type'],
					'user_id' => $allPostPutVars['user_id'],
					'created_date' => date_time(
						'today', [], 'string'
					)
				];
				$this->addingProductionLog($logData);
				$tempData['production_job_stage_id'] = $jobData['xe_id'];
				$tempData['status'] = 'delay';
				//Get stage is global or not
				$statusFeatureInit = new StatusFeatures();
				$statusFeature = $statusFeatureInit->where('status_id', $stageId);
				if ($statusFeature->count() > 0) {
					$statusFeatureData = $statusFeature->get();
					$statusFeatureData = json_clean_decode($statusFeatureData, true);
					$isGlobal = $statusFeatureData[0]['is_global'];
					$tempData['is_global'] = $isGlobal;
				}

				array_push($productionJobIdArr, $tempData);
			}
		}
		$jsonResponse = [
			'status' => 1,
			'data' => $productionJobIdArr,
			'message' => message('Stage Delayed', 'done'),
		];
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Production Job Log
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   29 Sept 2020
	 * @return json response
	 */
	public function getProductionJobLogs($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Log', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		if (!empty($args['id'])) {
			$productionJobId = $args['id'];
			$productionJobLogInit = new ProductionJobLog();
			$logData = $productionJobLogInit->where('job_id', $productionJobId)
				->orderBy('created_date', 'DESC');
			$noteRes = [];
			$finalLogData = [];
			if ($logData->count() > 0) {
				$logDataArr = $logData->get();
				foreach ($logDataArr as $logs) {
					$tempLogData = $logs;
					$userName = $logs['user_type'];
					if ($logs['user_type'] == 'agent') {
						//Get agent name
						$userInit = new User();
						$agent = $userInit->select('xe_id', 'name')->where('xe_id', $logs['user_id']);
						if ($agent->count() > 0) {
                            $agentDetails = json_clean_decode($agent->first(), true);
                            $userName = $agentDetails['name'];
                        }
					} else if ($logs['user_type'] == 'customer') {
						//Get customer details
						$customersControllerInit = new CustomersController();
						$customerDetails = $customersControllerInit->getQuoteCustomerDetails($logs['user_id'], $getStoreDetails['store_id'], '');
                        if (!empty($customerDetails)) {
                            $userName = ($customerDetails['customer']['name'] != '') ? $customerDetails['customer']['name'] : $customerDetails['customer']['email'];
                        }
					}
					$tempLogData['user_name'] = $userName;
					$tempLogData['title'] = stripslashes($logs['title']);
					$tempLogData['description'] = stripslashes($logs['description']);
					$tempLogData['created_at'] = $logs['created_date'];
					$tempLogData['log_type'] = 'job_log';
					unset(
						$logs['created_date']
					);
					array_push($finalLogData, $tempLogData);
				}

			}
			//Get internal note data
			$internalNoteInit = new ProductionJobNotes();
			$internalNotes = $internalNoteInit->with('files')->where('job_id', $productionJobId)
				->orderBy('created_date', 'DESC');
			if ($internalNotes->count() > 0) {
				$noteDataArr = $internalNotes->get();
				foreach ($noteDataArr as $noteData) {
					$newNoteArr = $noteData;
					$userName = $newNoteArr['user_type'];
					if ($newNoteArr['user_type'] == 'agent') {
						//Get agent name
						$userInit = new User();
						$agent = $userInit->select('xe_id', 'name')->where('xe_id', $newNoteArr['user_id']);
						if ($agent->count() > 0) {
                            $agentDetails = json_clean_decode($agent->first(), true);
                            $userName = $agentDetails['name'];
                        }
					}
					$newNoteArr['title'] = 'Internal note added by ' . $userName;
					$newNoteArr['description'] = $newNoteArr['note'];
					$newNoteArr['log_type'] = 'internal_note';
					$newNoteArr['user_name'] = $userName;
					$newNoteArr['created_at'] = $newNoteArr['created_date'];
					unset(
						$newNoteArr['note'],
						$newNoteArr['seen_flag'],
						$newNoteArr['created_date']
					);
					array_push($noteRes, $newNoteArr);
				}
			}
			$totalProductionJobLogs = array_merge($finalLogData, $noteRes);
			// Sort the array by Created Date and time
			usort($totalProductionJobLogs, 'date_compare');
			if (is_array($totalProductionJobLogs) && !empty($totalProductionJobLogs) > 0) {
				$jsonResponse = [
					'status' => 1,
					'data' => $totalProductionJobLogs,
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Production Job Details
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   29 Sept 2020
	 * @return json response
	 */
	public function getProductionJobDetails($request, $response, $args, $returnType = 0) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Log', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$currentStageId = $request->getQueryParam('current_stage_id');
		if (!empty($args['id'])) {
			$productionJobId = $args['id'];
			$productionJobInit = new ProductionJobs();

			$productionJob = $productionJobInit->where([
				'store_id' => $getStoreDetails['store_id'],
				'xe_id' => $productionJobId,
			]);

			$finalProductJobData = [];
			if ($productionJob->count() > 0) {
				$productionJobArr = $productionJob->get();
				$productionJobArr = json_clean_decode($productionJobArr, true);
				$productionJobArr = $productionJobArr[0];
				$orderId = $productionJobArr['order_id'];
				$orderItemId = $productionJobArr['order_item_id'];
				//Get Order data
				$orderData = $this->getOrderItemData($getStoreDetails['store_id'], $orderId, $orderItemId);
				$finalProductJobData['product_image'] = $orderData['orders']['store_image'];
				$finalProductJobData['order_data'] = $orderData;
				$currentStage = $this->getJobCurrentStage($productionJobArr['xe_id']);
				$userInit = new User();
				$allAgent = $userInit->select('xe_id as id', 'name')->where('store_id', $getStoreDetails['store_id']);
				$allAgentArr = json_clean_decode($allAgent->get(), true);
				$userRoleInit = new UserRole();
				$allGroup = $userRoleInit->select('xe_id as id', 'role_name')->where('store_id', $getStoreDetails['store_id']);
				$allGroupArr = json_clean_decode($allGroup->get(), true);

				$productionJobAgentInit = new ProductionJobAgents();
				$assigneeData = $productionJobAgentInit->where([
					'job_id' => $currentStage['job_id'],
					'job_stage_id' => $currentStage['xe_id'],
				]);
				$finalAssignee = [];
				$finalIsGroup = [];
				if ($assigneeData->count() > 0) {
					$assigneeDataArr = $assigneeData->get();
					foreach ($assigneeDataArr as $assignee) {
						if ($assignee['is_group'] == 0) {
							$agentId = $assignee['agent_id'];
							$agentDetails = array_filter($allAgentArr, function ($item) use ($agentId) {
								return $item['id'] == $agentId;
							});
							$agentDetails = $agentDetails[array_keys($agentDetails)[0]];
							$tempData['id'] = $agentId;
							$tempData['name'] = $agentDetails['name'];
						} else {
							$groupId = $assignee['agent_id'];
							$groupDetails = array_filter($allGroupArr, function ($item) use ($groupId) {
								return $item['id'] == $groupId;
							});
							$groupDetails = $groupDetails[array_keys($groupDetails)[0]];
							$tempData['id'] = $groupId;
							$tempData['name'] = $groupDetails['role_name'];
						}
						array_push($finalAssignee, $tempData);
						array_push($finalIsGroup, $assignee['is_group']);
					}
				}
				$currentStage['is_group'] = $finalIsGroup[0];
				$currentStage['assignee_data'] = $finalAssignee;
				$nextStageId = $this->getNextStageId($productionJobArr['xe_id'], $productionJobArr['current_stage_id']);
				$productionJobArr['next_stage_id'] = ($nextStageId > 0) ? $nextStageId : '';
				$poStatus = '';
				$poStatusColorCode = '';
				$purchaseOrderItemInit = new PurchaseOrderItems();
				$purchaseOrderItem = $purchaseOrderItemInit
					->join('po_line_item_status', 'po_line_item_status.xe_id', '=', 'purchase_order_items.status_id')
					->select('po_line_item_status.status_name', 'po_line_item_status.color_code')
					->where([
						'purchase_order_items.order_id' => $productionJobArr['order_id'],
						'purchase_order_items.order_item_id' => $productionJobArr['order_item_id'],
					]);
				if ($purchaseOrderItem->count() > 0) {
					$poData = $purchaseOrderItem->get();
					$poData = json_clean_decode($poData, true);
					$poStatus = $poData[0]['status_name'];
					$poStatusColorCode = $poData[0]['color_code'];
				}
				$productionJobArr['po_status'] = $poStatus;
				$productionJobArr['po_status_color_code'] = $poStatusColorCode;
				$productionJobArr['current_stage'] = $currentStage;
				if ($currentStageId != '') {
					$productionJobStagesInit = new ProductionJobStages();
					$getStageData = $productionJobStagesInit->select('status', 'stage_name')->where([
						'job_id' => $productionJobId,
						'xe_id' => $currentStageId
					]);
					$showMarkAsDone = true;
					if ($getStageData->count() > 0) {
						$stageDataArr = $getStageData->first();
						$stageDataArr = json_clean_decode($stageDataArr, true);
						if ($stageDataArr['status'] == 'completed' || $stageDataArr['status'] == 'not-started') {
							$showMarkAsDone = false;
						}
					}
					$productionJobArr['show_mark_as_done'] = $showMarkAsDone;
					$productionJobArr['qr_current_stage'] = $stageDataArr['stage_name'];
				}
				$token = 'job_id=' . $productionJobId.'&current_stage_id='.$currentStage['xe_id'].'&store_id='.$getStoreDetails['store_id'];
	            $token = base64_encode($token);
	            $url = 'quotation/production-job?token=' . $token;
	            $url = API_URL . $url;
				$productionJobArr['qr_code_url'] = $url;
				$finalProductJobData['production_job'] = $productionJobArr;
			}
			$jsonResponse = [
				'status' => 1,
				'data' => $finalProductJobData,
			];
			if ($returnType == 1) {
				return $finalProductJobData;
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get Order Data
	 *
	 * @param $orderId  Order Id
	 * @param $itemId  Order Item Id
	 *
	 * @author debashrib@riaxe.com
	 * @date   30 Sept 2020
	 * @return array response
	 */

	private function getOrderItemData($storeId, $orderId, $itemId) {
		$orderDetails = [];
		if ($orderId != '' && $orderId > 0 && $itemId != '' && $itemId > 0) {
			$orderInit = new OrdersController();
			$storeResponse = $orderInit->getStoreOrderLineItemDetails($orderId, $itemId, $is_customer = true, $storeId);
			$getdesignData['order_id'] = $storeResponse['order_id'];
			$getdesignData['order_number'] = $storeResponse['order_number'];
			$getdesignData['id'] = $storeResponse['item_id'];
			$getdesignData['product_id'] = $storeResponse['product_id'];
			$getdesignData['product_id'] = $storeResponse['product_id'];
			$getdesignData['variant_id'] = $storeResponse['variant_id'];
			$getdesignData['name'] = $storeResponse['name'];
			$getdesignData['quantity'] = $storeResponse['quantity'];
			$getdesignData['sku'] = $storeResponse['sku'];
			$getdesignData['price'] = $storeResponse['price'];
			$getdesignData['total'] = $storeResponse['total'];
			$getdesignData['total'] = $storeResponse['total'];
			$getdesignData['images'] = $storeResponse['images'];
			$getdesignData['custom_design_id'] = $storeResponse['custom_design_id'];
			$designData[] = $getdesignData;
			$designDataResponse = $orderInit->getOrderItemDesignData($designData);
			$itemDataArr['id'] = $storeResponse['order_id'];
			$itemDataArr['order_number'] = $storeResponse['order_number'];
			$itemDataArr['total_amount'] = $storeResponse['total'];
			$itemDataArr['customer_first_name'] = ($storeResponse['customer_first_name']) ? $storeResponse['customer_first_name'] : "";
			$itemDataArr['customer_last_name'] = ($storeResponse['customer_last_name']) ? $storeResponse['customer_last_name'] : "";
			$itemDataArr['customer_email'] = $storeResponse['customer_email'];
			$itemDataArr['customer_id'] = $storeResponse['customer_id'];
			$itemDataArr['billing'] = $storeResponse['billing'];
			$itemDataArr['shipping'] = $storeResponse['shipping'];
			$itemDataArr['orders'] = $designDataResponse[0];
		}
		return $itemDataArr;
	}

	/**
	 * Add assignee data
	 *
	 * @param $jobId  Production job id
	 * @param $stageId  Production job status id
	 * @param $jobStageId  Production job stage id
	 *
	 * @author debashrib@riaxe.com
	 * @date   01 Oct 2020
	 * @return array response
	 */

	private function saveAssigneeData($request, $jobId, $stageId, $jobStageId) {
		$result = [];
		$getStoreDetails = get_store_details($request);
		if ($jobId != '' && $jobId > 0 && $stageId != '' && $stageId > 0 && $jobStageId != '' && $jobStageId > 0) {
			//Get assignee
			$statusAssigneeRelInit = new StatusAssigneeRel();
			$assigneeData = $statusAssigneeRelInit->where('status_id', $stageId);
			$finalAssignee = [];
			$success = 0;
			if ($assigneeData->count() > 0) {
				$assigneeDataArr = $assigneeData->get();
				$userInit = new User();
				$allAgent = $userInit->select('xe_id as id', 'name')->where('store_id', $getStoreDetails['store_id']);
				$agentListArr = json_clean_decode($allAgent->get(), true);
				//Get all group name
				$userRoleInit = new UserRole();
				$allGroup = $userRoleInit->select('xe_id as id', 'role_name')->where('store_id', $getStoreDetails['store_id']);
				$allGroupArr = json_clean_decode($allGroup->get(), true);
				foreach ($assigneeDataArr as $assignee) {
					$tempData['agent_id'] = $assignee['assignee_id'];
					//Get stage is global or not
					$statusFeatureInit = new StatusFeatures();
					$statusFeature = $statusFeatureInit->where('status_id', $stageId);
					if ($statusFeature->count() > 0) {
						$statusFeatureData = $statusFeature->get();
						$statusFeatureData = json_clean_decode($statusFeatureData, true);
						$isGroup = $statusFeatureData[0]['is_group'];
						$tempData['is_group'] = $isGroup;
					}
					array_push($finalAssignee, $tempData);
				}
				//Save assignee data in 'production_job_agents' table
				$allAgentsName = '';
				$agentIdsArr = [];
				foreach ($finalAssignee as $assigneeData) {
					$saveData = [
						'job_id' => $jobId,
						'job_stage_id' => $jobStageId,
						'is_group' => $assigneeData['is_group'],
						'agent_id' => $assigneeData['agent_id'],
					];
					$productionJobAgent = new ProductionJobAgents($saveData);
					if ($productionJobAgent->save()) {
						$success++;
						$isGroup = $assigneeData['is_group'];
						$agentId = $assigneeData['agent_id'];
						if ($isGroup == 0) {
							$agentArr = array_filter($agentListArr, function ($item) use ($agentId) {
								return $item['id'] == $agentId;
							});
							$agentArr = $agentArr[array_keys($agentArr)[0]];
							$allAgentsName .= ', ' . $agentArr['name'];
						} else {
							$groupDetails = array_filter($allGroupArr, function ($item) use ($agentId) {
								return $item['id'] == $agentId;
							});
							$groupDetails = $groupDetails[array_keys($groupDetails)[0]];
							$allAgentsName .= ', ' . $groupDetails['role_name'];
						}
						array_push($agentIdsArr, $agentId);
					}
				}
			}
		}
		if ($success > 0) {
			$result = [
				'is_group' => $isGroup,
				'agent_id_arr' => $agentIdsArr,
				'names' => trim($allAgentsName, ", "),
			];
		}
		return $result;

	}

	/**
	 * POST: Change Assignee
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   01 Oct 2020
	 * @return json response
	 */
	public function changeStageAssignee($request, $response) {
		$getStoreDetails = get_store_details($request);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Stage Assignee', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$jobId = (isset($allPostPutVars['job_id']) && $allPostPutVars['job_id'] != '') ? $allPostPutVars['job_id'] : 0;
		$jobStageId = (isset($allPostPutVars['stage_id']) && $allPostPutVars['stage_id'] != '') ? $allPostPutVars['stage_id'] : 0;
		$agentIdArr = (isset($allPostPutVars['agent_ids']) && !empty($allPostPutVars['agent_ids'])) ? $allPostPutVars['agent_ids'] : [];
		$agentIdArr = json_clean_decode($agentIdArr, true);
		if ($jobId > 0 && $jobStageId > 0 && !empty($agentIdArr)) {
			//Get production job stage data
			$productionJobStageInit = new ProductionJobStages();
			$productionJobStageData = $productionJobStageInit->where('xe_id', $jobStageId);
			if ($productionJobStageData->count() > 0) {
				$productionJobStageDataArr = $productionJobStageData->get();
				$productionJobStageDataArr = json_clean_decode($productionJobStageDataArr, true);
			}

			$productionJobAgentInit = new ProductionJobAgents();
			$productionJobAgent = $productionJobAgentInit->where([
				'job_id' => $jobId,
				'job_stage_id' => $jobStageId,
			]);
			if ($productionJobAgent->count() > 0) {
				$productionJobAgentData = $productionJobAgent->get();
				$productionJobAgentData = json_clean_decode($productionJobAgentData, true);
				$userInit = new User();
				$allAgent = $userInit->select('xe_id as id', 'name')->where('store_id', $getStoreDetails['store_id']);
				$agentListArr = json_clean_decode($allAgent->get(), true);
				//Get all group name
				$userRoleInit = new UserRole();
				$allGroup = $userRoleInit->select('xe_id as id', 'role_name')->where('store_id', $getStoreDetails['store_id']);
				$allGroupArr = json_clean_decode($allGroup->get(), true);

				//Delete data from table
				$deleteAgents = $productionJobAgentInit->where([
					'job_id' => $jobId,
					'job_stage_id' => $jobStageId,
				]);
				$deleteAgents->delete();
				//Save new agents

				$allNewAgentsName = '';
				$agentIdsArr = [];
				foreach ($agentIdArr as $agentIds) {
					$saveData = [
						'job_id' => $jobId,
						'job_stage_id' => $jobStageId,
						'is_group' => $allPostPutVars['is_group'],
						'agent_id' => $agentIds,
					];
					$productionJobAgent = new ProductionJobAgents($saveData);
					$productionJobAgent->save();

					//Send Email to agent
					if ($allPostPutVars['is_group'] == 0) {
						$agentArr = array_filter($agentListArr, function ($item) use ($agentIds) {
							return $item['id'] == $agentIds;
						});
						$agentArr = $agentArr[array_keys($agentArr)[0]];
						$allAgentsName .= ', ' . $agentArr['name'];
					} else {
						$groupDetails = array_filter($allGroupArr, function ($item) use ($agentIds) {
							return $item['id'] == $agentIds;
						});
						$groupDetails = $groupDetails[array_keys($groupDetails)[0]];
						$allAgentsName .= ', ' . $groupDetails['role_name'];
					}
					array_push($agentIdsArr, $agentIds);
				}
				$allAgentsName = trim($allAgentsName, ", ");
				//Get Production Job Data
				$productionJobData = $this->getProductJobData($jobId);
				$jobIdName = $productionJobData['job_id'];
				//Get Production Stage Data
				$productionStatusData = $this->getProductJobStageData($productionJobStageDataArr[0]['stages_id']);
				$stageName = $productionStatusData['status_name'];
				//Adding to production job log for job creation
				$type = ($allPostPutVars['is_group'] == 0) ? 'Agent' : 'Group';
				$logData = [
					'job_id' => $jobId,
					'title' => 'Agent re-assigned',
					'description' => $type . ' ' . $allAgentsName . ' re-assigned to job #' . $jobIdName . ' for stage ' . $stageName . '.',
					'user_type' => $allPostPutVars['user_type'],
					'user_id' => $allPostPutVars['user_id'],
					'created_date' => date_time(
						'today', [], 'string'
					)
				];
				$this->addingProductionLog($logData);
				$sendingEmailData = [
					'customer_id' => '',
					'job_id' => $jobId,
					'stages_id' => '',
					'is_group' => $allPostPutVars['is_group'],
					'agent_ids' => $agentIdsArr,
				];

				$jsonResponse = [
					'status' => 1,
					'email_data' => $sendingEmailData,
					'message' => message('Stage Assignee', 'updated'),
				];
			}

		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Get orders for create production job
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   01 Oct 2020
	 * @return json response
	 */
	public function getProductionOrderList($request, $response) {
		$getStoreDetails = get_store_details($request);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Orders', 'error'),
		];
		$orderInit = new Orders();
		$orders = $orderInit->where('production_status', '0')
			->where('store_id', $getStoreDetails['store_id'])
			->orderBy('xe_id', 'DESC');
		$totalCount = $orders->count();
		if ($totalCount > 0) {
			$ordersData = $orders->get();
			$ordersData = json_clean_decode($ordersData, true);
			foreach ($ordersData as $orderKey => $orders) {
				if ($orders[$orderKey]['order_number'] == '') {
					$ordersData[$orderKey]['order_number'] = $orders['order_id'];
				}
			}
			$jsonResponse = [
				'status' => 1,
				'total' => $totalCount,
				'data' => $ordersData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Add internal note to production job
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   03 Oct 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveProductionJobInternalNote($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Note', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$jobId = to_int($allPostPutVars['job_id']);
		if ($jobId != '') {
			$productionJobInit = new ProductionJobs();
			$getOldJob = $productionJobInit->where('xe_id', $jobId);
			if ($getOldJob->count() > 0) {

				$allPostPutVars['created_date'] = date_time(
					'today', [], 'string'
				);
				$jobInternalNote = new ProductionJobNotes($allPostPutVars);
				if ($jobInternalNote->save()) {
					$noteInsertId = $jobInternalNote->xe_id;
					$allFileNames = do_upload(
						'upload',
						path('abs', 'production') . 'internal-note/', [150],
						'array'
					);
					//Save file name w.r.t note
					if (!empty($allFileNames)) {
						foreach ($allFileNames as $eachFile) {
							$fileData = [
								'note_id' => $noteInsertId,
								'file' => $eachFile,
							];
							$saveNoteFile = new ProductionJobNoteFiles($fileData);
							$saveNoteFile->save();
						}
					}
					$jsonResponse = [
						'status' => 1,
						'message' => message('Production Job Note', 'saved'),
					];
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST: Add note to production job
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   03 Oct 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveProductionJobNote($request, $response) 
	{
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Note', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$jobId = to_int($allPostPutVars['job_id']);
		if ($jobId != '') {
			$productionJobInit = new ProductionJobs();
			$getOldJob = $productionJobInit->where('xe_id', $jobId);
			if ($getOldJob->count() > 0) {
				$getOldJobData = $getOldJob->get();
				$getOldJobData = json_clean_decode($getOldJobData, true);
				$jobIdName = $getOldJobData[0]['job_id'];
				$note = $getOldJobData[0]['note'];
				if ($allPostPutVars['user_type'] == 'agent') {
					$userInit = new User();
					$agent = $userInit->select('xe_id', 'name')->where('xe_id', $allPostPutVars['user_id']);
					if ($agent->count() > 0) {
                        $agentDetails = json_clean_decode($agent->first(), true);
                        $userName = $agentDetails['name'];
                    }
				} else {
					$username = 'Admin';
				}
				if ($note == '') {
					$title = 'Note added by ' . $userName;
				} else {
					$title = 'Note updated by ' . $userName;
				}
				$productionJobInit->where('xe_id', $jobId)
					->update([
						'note' => $allPostPutVars['note'],
					]);
				//Adding to production job log
				$logData = [
					'job_id' => $jobId,
					'title' => $title,
					'description' => $allPostPutVars['note'],
					'user_type' => $allPostPutVars['user_type'],
					'user_id' => $allPostPutVars['user_id'],
					'created_date' => date_time(
						'today', [], 'string'
					)
				];
				$this->addingProductionLog($logData);
			}
			$jsonResponse = [
				'status' => 1,
				'message' => message('Production Job Note', 'updated'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Send mail to agent when job is assigned
	 *
	 * @param $jobId  Production job id
	 * @param $isGroup  Is group
	 * @param $agentId  Agent Id
	 *
	 * @author debashrib@riaxe.com
	 * @date   03 Oct 2020
	 * @return array response
	 */

	private function sendEmailToAgent($request, $response, $jobId, $isGroup, $agentId, $getStoreDetails) 
	{
		$mailResponse = [];
		if ($agentId != '' && $agentId > 0 && $jobId != '' && $jobId > 0) {
			$productionJobInit = new ProductionJobs();

			$productionJob = $productionJobInit->where([
				'xe_id' => $jobId,
			]);
			if ($productionJob->count() > 0) {
				$productionJobArr = $productionJob->get();
				$productionJobArr = json_clean_decode($productionJobArr, true);
				$productionJobArr = $productionJobArr[0];
				$currentStage = $this->getJobCurrentStage($productionJobArr['xe_id']);
				$jobIdName = $productionJobArr['job_id'];
				$dueDate = date("M d, Y h:i A", strtotime($currentStage['exp_completion_date']));
				$link = API_URL . 'production-hub/production/' . $productionJobArr['xe_id'];
			}
			// Check the setting
			$settingData = $this->getProductionSetting($request, $response, ['module_id' => 4, 'return_type' => 1]);
			$settingData = $settingData['data'];
			if ($settingData['is_communication_enabled']) {
				//Get smtp email setting data for sending email
				$globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
				$emailData = $globalSettingData['smpt_email_details']['email_address_details'];
				$smtpData = $globalSettingData['smpt_email_details']['smtp_details'];
				$fromEmail = $emailData['from_email'];
				$replyToEmail = $emailData['to_email'];
				//Get all Agent list
				$userInit = new User();
            	$getUser = $userInit->select('admin_users.xe_id as id', 'admin_users.name', 'admin_users.email', 'user_role_rel.role_id')
                ->join('user_role_rel', 'admin_users.xe_id', '=', 'user_role_rel.user_id') 
                ->where('admin_users.store_id', $getStoreDetails['store_id']);
                $allAgentArr = json_clean_decode($getUser->get(), true);

				$subject = 'A new job #' . $jobIdName . ' assigned.';

				if ($isGroup == 0) {
					$agentDetails = array_filter($allAgentArr, function ($item) use ($agentId) {
						return $item['id'] == $agentId;
					});
					$agentDetails = $agentDetails[array_keys($agentDetails)[0]];
					$emailBody = '<span>Hello ' . $agentDetails['name'] . ',</span><br><br><span>A new job #' . $jobIdName . ' is assigned to you. The due date is ' . $dueDate . '.</span><br><span><a href="" target="' . $link . '">' . $link . '</a> to view the job details.</span><br><br><span>Thanks</span>';
					$mailContaint = ['from' => ['email' => $fromEmail, 'name' => $fromEmail],
						'recipients' => [
							'to' => [
								'email' => $agentDetails['email'],
								'name' => $agentDetails['name'],
							],
							'reply_to' => [
								'email' => $replyToEmail,
								'name' => $replyToEmail,
							],
						],
						'attachments' => ($attachments != '') ? $attachments : [],
						'subject' => $subject,
						'body' => $emailBody,
						'smptData' => $smtpData,
					];
					$mailResponse = email($mailContaint);
				} else {
					$agentDetailsArr = array_filter($allAgentArr, function ($item) use ($agentId) {
						return $item['role_id'] == $agentId;
					});
					foreach ($agentDetailsArr as $agents) {
						$emailBody = '<span>Hello ' . $agents['name'] . ',</span><br><br><span>A new job #' . $jobIdName . ' is assigned to you. The due date is {due_date}.</span><br><span><a href="" target="' . $link . '">' . $link . '</a> to view the job details.</span><br><br><span>Thanks</span>';
						$mailContaint = ['from' => ['email' => $fromEmail, 'name' => $fromEmail],
							'recipients' => [
								'to' => [
									'email' => $agents['email'], //'debashrib@riaxe.com',//
									'name' => $agents['name'],
								],
								'reply_to' => [
									'email' => $replyToEmail,
									'name' => $replyToEmail,
								],
							],
							'attachments' => ($attachments != '') ? $attachments : [],
							'subject' => $subject,
							'body' => $emailBody,
							'smptData' => $smtpData,
						];
						$mailResponse = email($mailContaint);
					}
				}
			}
		}
		return $mailResponse;
	}

	/**
	 * GET: Production Job Calender View
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   09 Oct 2020
	 * @return json response
	 */
	public function getProductionCalenderView($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$productionJobInit = new ProductionJobs();
		// Collect all Filter columns from url
		$agentId = $request->getQueryParam('agent_id');
		$from = $request->getQueryParam('from');
		$to = $request->getQueryParam('to');
		$agentIdArr = $request->getQueryParam('agent_id');
		$agentIdArr = json_clean_decode($agentIdArr, true);
		$printMethodArr = $request->getQueryParam('print_methods');
		$printMethodArr = json_clean_decode($printMethodArr, true);
		$stageId = $request->getQueryParam('stage_id');

		$productionJob = $productionJobInit
			->join('production_job_stages', 'production_jobs.current_stage_id', '=', 'production_job_stages.xe_id')
			->select('production_jobs.xe_id', 'production_jobs.job_id', 'production_jobs.order_id', 'production_jobs.order_item_id', 'production_jobs.order_item_quantity as quantity', 'production_jobs.job_title', 'production_jobs.current_stage_id', 'production_job_stages.stages_id', 'production_job_stages.stage_name', 'production_job_stages.stage_color_code', 'production_job_stages.exp_completion_date', 'production_job_stages.status')
			->where('production_jobs.store_id', $getStoreDetails['store_id'])
			->where('production_job_stages.status', '!=', 'completed');

		if (isset($from) && isset($to) && $from != "" && $to != "") {
			$to = date('Y-m-d H:i:s', strtotime($to . ' +1 day'));
			$productionJob->where('production_job_stages.exp_completion_date', '>=', $from)
				->where('production_job_stages.exp_completion_date', '<=', $to);
		}

		//Filter by print method id
		if (isset($printMethodArr) && !empty($printMethodArr)) {
			$productionJob->whereIn('production_job_stages.print_method_id', $printMethodArr);
		}

		//Filter by current stage id
		if (isset($stageId) && $stageId > 0) {
			$productionJob->where('production_job_stages.stages_id', '=', $stageId);
		}

		$productionJob->orderBy('production_jobs.created_at', 'DESC');
		$getTotalPerFilters = $productionJob->count();
		//Get all Agent list
		$userInit = new User();
        $getUser = $userInit->select('admin_users.xe_id as id', 'admin_users.name', 'admin_users.email', 'user_role_rel.role_id')
            ->join('user_role_rel', 'admin_users.xe_id', '=', 'user_role_rel.user_id') 
            ->where('admin_users.store_id', $getStoreDetails['store_id']);
        $allAgentArr = json_clean_decode($getUser->get(), true);
		if ($getTotalPerFilters > 0) {
			$getProductionJobData = [];
			$productionJobArr = $productionJob->get();
			$productionJobArr = json_clean_decode($productionJobArr, true);
			$finalProductionJob = [];
			foreach ($productionJobArr as $productionJobs) {
				$tempProductionJob = $productionJobs;
				//Get assignee
				$productionJobAgentInit = new ProductionJobAgents();
				$assigneeData = $productionJobAgentInit->where([
					'job_id' => $productionJobs['xe_id'],
					'job_stage_id' => $productionJobs['current_stage_id'],
				]);
				$finalAssignee = [];
				$finalIsGroup = [];
				if ($assigneeData->count() > 0) {
					$assigneeDataArr = $assigneeData->get();
					foreach ($assigneeDataArr as $assignee) {
						array_push($finalAssignee, $assignee['agent_id']);
						array_push($finalIsGroup, $assignee['is_group']);
					}
				}
				$tempProductionJob['is_group'] = $finalIsGroup[0];
				$tempProductionJob['assignee_data'] = $finalAssignee;
				$nextStageId = $this->getNextStageId($productionJobs['xe_id'], $productionJobs['current_stage_id']);
				$tempProductionJob['next_stage_id'] = ($nextStageId > 0) ? $nextStageId : '';
				$groupAgentId = [];
				if ($finalIsGroup[0] == 1) {
					foreach ($finalAssignee as $assignee) {
						$agentDetailsArr = array_filter($allAgentArr, function ($item) use ($assignee) {
							return $item['role_id'] == $assignee;
						});
						foreach ($agentDetailsArr as $agents) {
							array_push($groupAgentId, $agents['id']);
						}
					}
				}
				//For agent view
				if (isset($agentIdArr) && !empty($agentIdArr)) {
					$usedIds = [];
					foreach ($agentIdArr as $agentId) {
						//This condition is when agent is assigned
						if ($finalIsGroup[0] == 0 && in_array($agentId, $finalAssignee) && !in_array($tempProductionJob['xe_id'], $usedIds)) {
							$tempProductionJob['is_agent_operate'] = 1;
							array_push($finalProductionJob, $tempProductionJob);
							$usedIds[count($usedIds)] = $tempProductionJob['xe_id'];
							//This condition is when group is assigned
						} else if ($finalIsGroup[0] == 1 && in_array($agentId, $groupAgentId) && !in_array($tempProductionJob['xe_id'], $usedIds)) {
							$tempProductionJob['is_agent_operate'] = 1;
							array_push($finalProductionJob, $tempProductionJob);
							$usedIds[count($usedIds)] = $tempProductionJob['xe_id'];
						}
					}

				} else {
					$tempProductionJob['is_agent_operate'] = 0;
					array_push($finalProductionJob, $tempProductionJob);
				}

			}
			$jsonResponse = [
				'status' => 1,
				'data' => $finalProductionJob,
			];
		} else {
			$jsonResponse = [
				'status' => 1,
				'data' => [],
				'message' => message('Production Job', 'not_found'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get the next stage id
	 *
	 * @param $jobId Production Job Id
	 * @param $currentStageId Production job stage id
	 *
	 * @author debashrib@riaxe.com
	 * @date   09 Oct 2020
	 * @return json response
	 */
	private function getNextStageId($jobId, $currentStageId) {
		$nextStageId = 0;
		if ($jobId != '' && $jobId > 0 && $currentStageId != '' && $currentStageId > 0) {
			//$productionJobInit = new ProductionJobs();
			$productionJobStageInit = new ProductionJobStages();
			$allStages = $productionJobStageInit->where('job_id', $jobId)->orderBy('xe_id', 'ASC');
			if ($allStages->count() > 0) {
				$allStageDataArr = $allStages->get();
				$allStageDataArr = json_clean_decode($allStageDataArr, true);
				$nextStageData = [];
				foreach ($allStageDataArr as $stageKey => $stageData) {
					if ($stageData['xe_id'] == $currentStageId) {
						$nextStageKey = $stageKey + 1;
					}
				}
				$nextStageData = $allStageDataArr[$nextStageKey];
				if (!empty($nextStageData)) {
					$nextStageId = $nextStageData['xe_id'];
				}
			}
		}
		return $nextStageId;
	}

	/**
	 * POST: Change exp completion date of stage
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   03 Oct 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function changeExpCompletionData($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Due Date', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$jobStageId = to_int($allPostPutVars['job_stage_id']);
		$expCompletionDate = $allPostPutVars['exp_completion_date'];
		$newTime = $allPostPutVars['time'];
		if ($jobStageId != '' && $jobStageId > 0 && $expCompletionDate != '') {
			$productionJobStageInit = new ProductionJobStages();
			$getOldJobStage = $productionJobStageInit->where('xe_id', $jobStageId);
			if ($getOldJobStage->count() > 0) {
				$getOldJobStageData = $getOldJobStage->get();
				$getOldJobStageData = json_clean_decode($getOldJobStageData, true);
				$oldExpCompDate = $getOldJobStageData[0]['exp_completion_date'];
				$jobId = $getOldJobStageData[0]['job_id'];
				$printMethodId = $getOldJobStageData[0]['print_method_id'];
				$stageName = $getOldJobStageData[0]['stage_name'];
				$oldStatus = $getOldJobStageData[0]['status'];
				$oldExpCompDateArr = explode(' ', $oldExpCompDate);
				$time = (isset($newTime) && $newTime != '') ? $newTime : $oldExpCompDateArr[1];
				$displayOldExpCompDate = date("M d, Y h:i A", strtotime($oldExpCompDate));
				$newExpCompDate = $expCompletionDate . ' ' . $time;
				$displayNewExpCompDate = date("M d, Y h:i A", strtotime($newExpCompDate));
				$currentDateTime = date_time(
					'today', [], 'string'
				);
				if ($newExpCompDate > $currentDateTime) {
					$status = 'in-progress';
				} else if ($newExpCompDate < $currentDateTime) {
					$status = 'delay';
				}
				$productionJobStageInit->where('xe_id', $jobStageId)
					->update([
						'exp_completion_date' => $newExpCompDate,
						'status' => $status,
					]);

				//Fetch data after updating
				$currentData = $productionJobStageInit
					->join('production_jobs', 'production_job_stages.job_id', '=', 'production_jobs.xe_id')
					->select('production_job_stages.xe_id', 'production_job_stages.job_id', 'production_job_stages.print_method_id', 'production_job_stages.stages_id', 'production_job_stages.stage_name', 'production_job_stages.stage_color_code', 'production_job_stages.created_date', 'production_job_stages.starting_date', 'production_job_stages.exp_completion_date', 'production_job_stages.completion_date', 'production_job_stages.status', 'production_job_stages.message', 'production_jobs.job_id as job_id_name')
					->where('production_job_stages.xe_id', $jobStageId);
				$currentDataArr = $currentData->get();
				$currentDataArr = json_clean_decode($currentDataArr, true);

				$jobIdName = $currentDataArr[0]['job_id_name']; //$productionJobData['job_id'];
				//Get All Print Profile
				$printMethodData = $this->getPrintMethodData($printMethodId);
				$printMethodName = $printMethodData['name'];
				if ($printMethodName != '') {
					$description = 'Due date of job #' . $jobIdName . ' changed from ' . $displayOldExpCompDate . ' to ' . $displayNewExpCompDate . ' for print method ' . $printMethodName . ' of stage ' . $stageName . '.';
				} else {
					$description = 'Due date of job #' . $jobIdName . ' changed from ' . $displayOldExpCompDate . ' to ' . $displayNewExpCompDate . ' of stage ' . $stageName . '.';
				}
				$logData = [
					'job_id' => $jobId,
					'title' => 'Due date changed',
					'description' => $description,
					'user_type' => $allPostPutVars['user_type'],
					'user_id' => $allPostPutVars['user_id'],
					'created_date' => date_time(
						'today', [], 'string'
					)
				];
				$this->addingProductionLog($logData);
			}
			$jsonResponse = [
				'status' => 1,
				'data' => $currentDataArr[0],
				'message' => message('Due Date', 'updated'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Get orders for which production job created
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   13 Oct 2020
	 * @return json response
	 */
	public function getProductionJobOrderList($request, $response) {
		$getStoreDetails = get_store_details($request);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Orders', 'error'),
		];
		$orderInit = new Orders();
		$orders = $orderInit->where('store_id', $getStoreDetails['store_id'])
			->where('production_status', '!=', '0')
			->orderBy('xe_id', 'DESC');
		$totalCount = $orders->count();
		if ($totalCount > 0) {
			$ordersData = $orders->get();
			$jsonResponse = [
				'status' => 1,
				'total' => $totalCount,
				'data' => $ordersData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Get production job list
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   13 Oct 2020
	 * @return json response
	 */
	public function getProductionJobList($request, $response) {
		$getStoreDetails = get_store_details($request);
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job', 'error'),
		];
		$productionJobInit = new ProductionJobs();
		$productionJob = $productionJobInit->select('xe_id', 'job_id')->where('store_id', $getStoreDetails['store_id'])
			->orderBy('xe_id', 'DESC');
		$totalCount = $productionJob->count();
		if ($totalCount > 0) {
			$productionJobData = $productionJob->get();
			$jsonResponse = [
				'status' => 1,
				'total' => $totalCount,
				'data' => $productionJobData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Production Activity Log
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   26 Sept 2020
	 * @return json response
	 */
	public function getProductionActivityLog($request, $response) 
	{
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$productionJobInit = new ProductionJobs();
		// Collect all Filter columns from url
		$orderIdArr = $request->getQueryParam('order_id');
		$orderIdArr = json_clean_decode($orderIdArr, true);
		$jobId = $request->getQueryParam('job_id');
		$stageId = $request->getQueryParam('stage_id');
		$agentIdArr = $request->getQueryParam('agent_id');
		$agentIdArr = json_clean_decode($agentIdArr, true);
		$printMethodArr = $request->getQueryParam('print_methods');
		$printMethodArr = json_clean_decode($printMethodArr, true);
		$isOverDue = $request->getQueryParam('is_over_due');
		$from = $request->getQueryParam('from');
		$to = $request->getQueryParam('to');
		$customerId = $request->getQueryParam('customer_id');

		$productionJob = $productionJobInit
			->join('production_job_stages', 'production_jobs.current_stage_id', '=', 'production_job_stages.xe_id')
			->select('production_jobs.xe_id', 'production_job_stages.stages_id')
			->where('production_jobs.store_id', $getStoreDetails['store_id']);

		//Filter by customer
		$customerOrderData = [];
		if (isset($customerId) && $customerId > 0) {
			$orderInit = new Orders();
			$orderData = $orderInit
				->select('order_id')
				->where('store_id', $getStoreDetails['store_id'])
				->where('production_status', '!=', '0')
				->where('customer_id', $customerId);
			if ($orderData->count() > 0) {
				$orderDataArr = $orderData->get();
				$orderDataArr = json_clean_decode($orderDataArr, true);
				foreach ($orderDataArr as $orders) {
					array_push($customerOrderData, $orders['order_id']);
				}
			}
		}
		if (empty($orderIdArr)) {
			$orderIdArr = $customerOrderData;
		} else {
			$orderIdArr = array_merge($customerOrderData, $orderIdArr);
		}
		//Filter by order
		if (isset($orderIdArr) && !empty($orderIdArr)) {
			$productionJob->whereIn('production_jobs.order_id', $orderIdArr);
		}

		//Filter by Production job
		if (isset($jobId) && $jobId > 0) {
			$productionJob->where('production_jobs.xe_id', $jobId);
		}

		//Filter by Production job current stage
		if (isset($stageId) && $stageId > 0) {
			$productionJob->where('production_job_stages.stages_id', $stageId);
		}

		//Filter by Production job current stage over due
		if (isset($isOverDue) && $isOverDue == 1) {
			$productionJob->where('production_job_stages.status', 'delay');
		}

		//Filter by print method id
		if (isset($printMethodArr) && !empty($printMethodArr)) {
			$productionJob->whereIn('production_job_stages.print_method_id', $printMethodArr);
		}

		//Get all Agent list
		$userInit = new User();
        $getUser = $userInit->select('admin_users.xe_id as id', 'admin_users.name', 'admin_users.email', 'user_role_rel.role_id')
            ->join('user_role_rel', 'admin_users.xe_id', '=', 'user_role_rel.user_id') 
            ->where('admin_users.store_id', $getStoreDetails['store_id']);
        $allAgentArr = json_clean_decode($getUser->get(), true);

		$getTotalPerFilters = $productionJob->count();
		if ($getTotalPerFilters > 0) {
			$getProductionJobData = [];
			$productionJobs = $productionJob->get();
			$productionJobArr = json_clean_decode($productionJobs, true);
			$finalProductionJobIds = [];
			foreach ($productionJobArr as $productionJobs) {
				//Get assignee
				$productionJobAgentInit = new ProductionJobAgents();
				$assigneeData = $productionJobAgentInit->select('is_group', 'agent_id')->where([
					'job_id' => $productionJobs['xe_id'],
					'job_stage_id' => $productionJobs['current_stage_id'],
				]);
				$finalAssignee = [];
				$finalIsGroup = [];
				if ($assigneeData->count() > 0) {
					$assigneeDataArr = $assigneeData->get();
					$assigneeDataArr = json_clean_decode($assigneeDataArr, true);
					foreach ($assigneeDataArr as $assignee) {
						array_push($finalAssignee, $assignee['agent_id']);
					}
				}
				$isGroup = $assigneeDataArr[0]['is_group'];
				$groupAgentId = [];
				if ($isGroup == 1) {
					foreach ($finalAssignee as $assignee) {
						$agentDetailsArr = array_filter($allAgentArr, function ($item) use ($assignee) {
							return $item['role_id'] == $assignee;
						});
						foreach ($agentDetailsArr as $agents) {
							array_push($groupAgentId, $agents['id']);
						}
					}
				}
				if (isset($agentIdArr) && !empty($agentIdArr)) {
					foreach ($agentIdArr as $agentId) {
						//This condition is when agent is assigned
						if ($isGroup == 0 && in_array($agentId, $finalAssignee)) {
							array_push($finalProductionJobIds, $productionJobs['xe_id']);
							//This condition is when group is assigned
						} else if ($isGroup == 1 && in_array($agentId, $groupAgentId)) {
							array_push($finalProductionJobIds, $productionJobs['xe_id']);
						}
					}
				} else {
					array_push($finalProductionJobIds, $productionJobs['xe_id']);
				}

			}
			//Get production log
			$productionJobLogInit = new ProductionJobLog();

			$logData = $productionJobLogInit->whereIn('job_id', $finalProductionJobIds);

			if (isset($from) && isset($to) && $from != "" && $to != "") {
				$to = date('Y-m-d H:i:s', strtotime($to . ' +1 day'));
				$logData->where('created_date', '>=', $from)
					->where('created_date', '<=', $to);
			}
			$logData->orderBy('created_date', 'DESC');
			$finalLogData = [];
			if ($logData->count() > 0) {
				$logDataArr = $logData->get();
				foreach ($logDataArr as $logs) {
					$tempLogData = $logs;
					$userName = $logs['user_type'];
					if ($logs['user_type'] == 'agent') {
						//Get agent name
						$userInit = new User();
						$agent = $userInit->select('xe_id', 'name')->where('xe_id', $logs['user_id']);
						if ($agent->count() > 0) {
                            $agentDetails = json_clean_decode($agent->first(), true);
                            $userName = $agentDetails['name'];
                        }
					}
					$tempLogData['user_name'] = $userName;
					$tempLogData['title'] = stripslashes($logs['title']);
					$tempLogData['description'] = stripslashes($logs['description']);
					$tempLogData['created_at'] = $logs['created_date'];
					$tempLogData['log_type'] = 'job_log';
					unset(
						$logs['created_date']
					);
					array_push($finalLogData, $tempLogData);
				}

			}

			//Get internal note data
			$internalNoteInit = new ProductionJobNotes();
			$internalNotes = $internalNoteInit->with('files')->whereIn('job_id', $finalProductionJobIds);
			if (isset($from) && isset($to) && $from != "" && $to != "") {
				$to = date('Y-m-d H:i:s', strtotime($to . ' +1 day'));
				$internalNotes->where('created_date', '>=', $from)
					->where('created_date', '<=', $to);
			}
			$internalNotes->orderBy('created_date', 'DESC');
			$noteRes = [];
			if ($internalNotes->count() > 0) {
				$noteDataArr = $internalNotes->get();
				foreach ($noteDataArr as $noteData) {
					$newNoteArr = $noteData;
					$userName = $newNoteArr['user_type'];
					if ($newNoteArr['user_type'] == 'agent') {
						//Get agent name
						$userInit = new User();
						$agent = $userInit->select('xe_id', 'name')->where('xe_id', $newNoteArr['user_id']);
						if ($agent->count() > 0) {
                            $agentDetails = json_clean_decode($agent->first(), true);
                            $userName = $agentDetails['name'];
                        }
					}
					$newNoteArr['title'] = 'Internal note added by ' . $userName;
					$newNoteArr['description'] = $newNoteArr['note'];
					$newNoteArr['log_type'] = 'internal_note';
					$newNoteArr['user_name'] = $userName;
					$newNoteArr['created_at'] = $newNoteArr['created_date'];
					unset(
						$newNoteArr['note'],
						$newNoteArr['seen_flag'],
						$newNoteArr['created_date']
					);
					array_push($noteRes, $newNoteArr);
				}
			}
			$totalProductionJobLogs = array_merge($finalLogData, $noteRes);
			// Sort the array by Created Date and time
			usort($totalProductionJobLogs, 'date_compare');
			if (is_array($totalProductionJobLogs) && !empty($totalProductionJobLogs)) {
				$jsonResponse = [
					'status' => 1,
					'data' => $totalProductionJobLogs,
				];
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => message('Production Job', 'not_found'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Send email to customer after completion of stage
	 *
	 * @param $customerId Customer Id
	 * @param $jobId Production Job Id
	 * @param $stageId Production job stage id
	 *
	 * @author debashrib@riaxe.com
	 * @date   19 Oct 2020
	 * @return json response
	 */
	private function sendEmailToCustomer($request, $customerId, $jobId, $stageId) 
	{
		$getStoreDetails = get_store_details($request);
		$mailResponse = [];
		if ($customerId != '' && $jobId > 0 && $stageId > 0) {

			//Get production job details
			$productionJobInit = new ProductionJobs();
			$productionJob = $productionJobInit
				->join('orders', 'production_jobs.order_id', '=', 'orders.order_id')
				->select('production_jobs.xe_id', 'production_jobs.store_id', 'production_jobs.job_id', 'production_jobs.order_id', 'production_jobs.order_item_id', 'production_jobs.order_item_quantity', 'production_jobs.job_title', 'production_jobs.job_status', 'production_jobs.note', 'production_jobs.comp_percentage', 'production_jobs.due_date', 'production_jobs.scheduled_date', 'production_jobs.created_at', 'production_jobs.current_stage_id', 'orders.customer_id')
				->where([
					'production_jobs.xe_id' => $jobId,
					'production_jobs.store_id' => $getStoreDetails['store_id'],
				]);
			$productionJobData = $productionJob->get();
			$productionJobData = json_clean_decode($productionJobData, true);

			//Get customer data
			if ($customerId > 0) {
				$customersControllerInit = new CustomersController();
				$customerDetails = $customersControllerInit->getQuoteCustomerDetails($customerId, $getStoreDetails['store_id'], '');
				if (!empty($customerDetails)) {
					$customerName = ($customerDetails['customer']['name'] != '') ? $customerDetails['customer']['name'] : $customerDetails['customer']['email'];
			 		$customerEmail = $customerDetails['customer']['email'];
				}
			} else if ($customerId == 0) {
				$orderId = $productionJobData[0]['order_id'];
				$ordersControllerInit = new OrdersController();
            	$orderDetails = $ordersControllerInit->getOrderList($request, $response, ['id' => $orderId], 1);
				$orderDetails = $orderDetails['data'];
				$customerName = ($orderDetails['customer_first_name'] != '') ? $orderDetails['customer_first_name'] . ' ' . $orderDetails['customer_last_name'] : $orderDetails['customer_email'];
				$customerEmail = $orderDetails['customer_email'];
			}
			//Get stage data
			$productionStatuInit = new ProductionStatus();
			$stageData = $productionStatuInit->where('xe_id', $stageId);
			if ($stageData->count() > 0) {
				$stageDataArr = $stageData->get();
				$stageDataArr = json_clean_decode($stageDataArr, true);
				$statusName = $stageDataArr[0]['status_name'];
				$templateTypeName = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $statusName)));
				$templateInit = new ProductionEmailTemplates();
				$tempDataArr = $templateInit
					->where(
						[
							'template_type_name' => $templateTypeName,
							'module_id' => 4,
							'store_id' => $getStoreDetails['store_id'],
						]
					);
				if ($tempDataArr->count() > 0) {
					$templateData = $tempDataArr->get();
					$templateData = json_clean_decode($templateData, true);
					$isConfigured = $templateData[0]['is_configured'];
					$subject = $templateData[0]['subject'];
					$message = $templateData[0]['message'];
					if ($isConfigured == 1 && $subject != '' && $message != '') {
						$abbrivationsInit = new ProductionAbbriviations();
						$getAbbrivations = $abbrivationsInit->where('module_id', 4);
						$getAbbrivations = $getAbbrivations->get();
						$abbriviationData = json_clean_decode($getAbbrivations, true);
						foreach ($abbriviationData as $abbrData) {
							$abbrValue = $this->getAbbriviationValue($abbrData['abbr_name'], $productionJobData[0], $getStoreDetails);
							if (strpos($message, $abbrData['abbr_name']) !== false) {
								$message = str_replace($abbrData['abbr_name'], $abbrValue, $message);
							}
							if (strpos($subject, $abbrData['abbr_name']) !== false) {
								$subject = str_replace($abbrData['abbr_name'], $abbrValue, $subject);
							}
						}
						//Get smtp email setting data for sending email
						$globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
						$emailData = $globalSettingData['smpt_email_details']['email_address_details'];
						$smtpData = $globalSettingData['smpt_email_details']['smtp_details'];
						$fromEmail = $emailData['from_email'];
						$replyToEmail = $emailData['to_email'];
						$mailContaint = ['from' => ['email' => $fromEmail, 'name' => $fromEmail],
							'recipients' => [
								'to' => [
									'email' => $customerEmail,
									'name' => $customerName,
								],
								'reply_to' => [
									'email' => $replyToEmail,
									'name' => $replyToEmail,
								],
							],
							'attachments' => [],
							'subject' => $subject,
							'body' => $message,
							'smptData' => $smtpData,
						];
						$mailResponse = email($mailContaint);
					}
				}
			}
		}
		return $mailResponse;
	}

	/**
	 * Get Email Template Abbriviation Value
	 *
	 * @param $abbrName  Abbriviation Name
	 * @param $productionJobData Production job data array
	 *
	 * @author debashrib@riaxe.com
	 * @date   19 Oct 2020
	 * @return array response
	 */

	private function getAbbriviationValue($abbrName, $productionJobData, $getStoreDetails) 
	{
		//Get Customer Details
		$customersControllerInit = new CustomersController();
		$customerDetails = $customersControllerInit->getQuoteCustomerDetails($productionJobData['customer_id'], $getStoreDetails['store_id'], '');
		if (!empty($customerDetails)) {
			$customerName = ($customerDetails['customer']['name'] != '') ? $customerDetails['customer']['name'] : '';
	 		$customerEmail = ($customerDetails['customer']['email'] != '' ) ? $customerDetails['customer']['email'] : '';
		}
		//Get production job current stage
		$currentStage = $this->getJobCurrentStage($productionJobData['xe_id']);
		$printMethodId = $currentStage['print_method_id'];
		$printMethodName = '';
		if ($printMethodId > 0) {
			$printMethodData = $this->getPrintMethodData($printMethodId);
			$printMethodName = $printMethodData['name'];
		}
		$abbrValue = '';
		if ($abbrName == '{job_id}') {
			$abbrValue = $productionJobData['job_id'];
		} else if ($abbrName == '{order_id}') {
			$abbrValue = $productionJobData['order_id'];
		} else if ($abbrName == '{customer_name}') {
			$abbrValue = $customerName;
		} else if ($abbrName == '{customer_email}') {
			$abbrValue = $customerEmail;
		} else if ($abbrName == '{item_name}') {
			$abbrValue = $productionJobData['job_title'];
		} else if ($abbrName == '{order_item_id}') {
			$abbrValue = $productionJobData['order_item_id'];
		} else if ($abbrName == '{print_profile}') {
			$abbrValue = $printMethodName;
		} else if ($abbrName == '{stage_name}') {
			$abbrValue = $currentStage['status_name'];
		}
		return $abbrValue;
	}

	/**
	 * POST: Sending email to customer and agents
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   27 Oct 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function sendEmail($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Sending Email', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $request->getParsedBody();
		$emailDataArr = $allPostPutVars['email_data'];
		$emailDataArr = json_clean_decode($emailDataArr);
		if (!empty($emailDataArr)) {
			foreach ($emailDataArr as $emailData) {
				if ($emailData['customer_id'] != ''
					&& $emailData['stages_id'] != ''
					&& $emailData['job_id'] > 0) {
					//Sending mail to customer
					$this->sendEmailToCustomer($request, $emailData['customer_id'], $emailData['job_id'], $emailData['stages_id']);
				}

				if ($emailData['job_id'] > 0
					&& !empty($emailData['agent_ids'])) {
					foreach ($emailData['agent_ids'] as $agentId) {
						$this->sendEmailToAgent($request, $response, $emailData['job_id'], $emailData['is_group'], $agentId, $getStoreDetails);
					}
				}
			}
			$jsonResponse = [
				'status' => 1,
				'message' => message('Sending Email', 'done'),
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}


	/**
	 * GET: Download Production Job as in pdf formate
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   03 June 2020
	 * @return json response
	 */
	public function downloadProductionJob($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Download', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		if (!empty($args['id'])) {
			$productionJobId = $args['id'];
			$settingData = $this->getProductionSetting($request, $response, ['module_id' => 4, 'return_type' => 1]);
			$settingData = $settingData['data'];
			$getProductionJobDetails = $this->getProductionJobDetails($request, $response, $args, 1);
		 	$dir = $this->createProductionJobPdf($getProductionJobDetails, $settingData);

		 	if (file_exists($dir['dir'])) {
                //Download file in local system
                if (file_download($dir['dir'])) {
                	if ($dir['qrcode_path'] != '') {
                		unlink($dir['qrcode_path']);
                	}
                    $result = true;
                    $serverStatusCode = OPERATION_OKAY;
                    $jsonResponse = [
                        'status' => 1,
                    ];
                }
            }
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}


	 /**
     * Create Production Job pdf
     *
     * @param $getProductionJobDetails     Production job details
     *
     * @author debashrib@riaxe.com
     * @date   06 June 2019
     * @return pdf file path
     */
    public function createProductionJobPdf($getProductionJobDetails, $settingData)
    {
    	if (!empty($getProductionJobDetails)) {
    		$productionJobData = $getProductionJobDetails['production_job'];
    		$orderData = $getProductionJobDetails['order_data'];
    		$barcode = generate_barcode($productionJobData['job_id']);
			$barcodeImageSrc = 'data:image/png;base64,' . base64_encode($barcode);

			$isBarcodeEnable = $settingData['is_barcode_enable'];
			$timeFormate = $settingData['time_format'];
			$createdDate = date("M d,Y h:i a", strtotime($productionJobData['created_at']));
			$dueDate = date("M d,Y h:i a", strtotime($productionJobData['current_stage']['exp_completion_date']));
			if ($timeFormate == 24) {
				$createdDate = date("M d,Y H:i", strtotime($productionJobData['created_at']));
				$dueDate = date("M d,Y H:i", strtotime($productionJobData['current_stage']['exp_completion_date']));
			}
			$file = '';
			if ($isBarcodeEnable) {
				$token = 'job_id=' . $productionJobData['xe_id'].'&current_stage_id='.$productionJobData['current_stage']['xe_id'].'&store_id='.$productionJobData['store_id'];
	            $token = base64_encode($token);
	            $url = 'quotation/production-job?token=' . $token;
	            $url = API_URL . $url;
	            $uniqid = uniqid();
	            $file = path('abs', 'production').$uniqid.".png";
	            $showFile = path('read', 'production').$uniqid.".png";
	            $text = $url;
	            $ecc = 'L';
				$pixel_Size = 10;
				$frame_Size = 10;
				generate_qrcode($text, $file, $ecc, $pixel_Size, $frame_size);
			}
    		$html = '<body style="margin: 0; padding: 0;">
                <div style="margin: 0px; padding: 0px; background: #fff; -webkit-box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); position: relative; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif;">

                <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%;">
                <tr>
                	<td>
	                	<img src="' . $barcodeImageSrc . '" style="height:100%; max-height:100px; margin-top: 20px;">
	                </td>
                </tr>
                </table>
                <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%;">
              <tr>
                <td style="vertical-align: top;">
                  <h3 class="title mb-3">Production job</h3>
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Job id</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>#' . $productionJobData['job_id'] . '</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Job title</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . $productionJobData['job_title'] . '</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Created date</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . $createdDate . '</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Due date</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . $dueDate . '</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Stage</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . $productionJobData['current_stage']['status_name'] . '</strong>
                      </td>
                    </tr>
                  </table>
                </td>
                <td style="vertical-align: top; text-align: right; font-size: 14px;">
                <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">';
                if ($isBarcodeEnable) {
                $html .= '<tr>
	                <td align="right">
	                	<img src="' . $showFile . '" style="height:100%; max-height:100px; margin-top: 20px;">
	                </td>
                </tr>';
            	}
                $html .= '</table>
                </td>
              </tr>
            </table>
            <hr style="margin-bottom: 30px; margin-top: 30px; width: 100%; border:1px solid #e3e3e3" />
            <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%; margin-bottom: 30px;">
              <tr>';
          	if ($orderData['customer_first_name'] != '') {
      			$customerName = $orderData['customer_first_name'] . ' ' . $orderData['customer_last_name'];
          	} else {
          		$customerName = $orderData['billing']['first_name'] . ' ' . $orderData['billing']['last_name'];
          	}
            $html .= '<td style="padding: 0 20px 4px 0px; font-size: 14px;">Customer name : <strong>' . $customerName . '</strong></td>
              </tr>';	
          	if ($orderData['customer_email'] != '') {
            $html .= '<tr>
              	<td style="padding: 0 20px 4px 0px; font-size: 14px;">Customer email : <strong>' . $orderData['customer_email'] . '</strong></td>
              </tr>';
          	}	
            $html .= '<tr>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  Billing address
                  <p><strong>
                    ' . $orderData['billing']['address_1'] . ', ' . $orderData['billing']['address_2'] . '<br/>
                    ' . $orderData['billing']['city'] . ', ' . $orderData['billing']['state'] . '<br/>
                    ' . $orderData['billing']['country'] . '-' . $orderData['billing']['postcode'] . '
                  </strong></p>
                </td>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  Shipping address';
				if ($orderData['shipping']['address_1'] != '') {
					$html .= '<p><strong>
                    ' . $orderData['shipping']['address_1'] . ', ' . $orderData['shipping']['address_2'] . '<br/>
                    ' . $orderData['shipping']['city'] . ', ' . $orderData['shipping']['state'] . '<br/>
                    ' . $orderData['shipping']['country'] . '-' . $orderData['shipping']['postcode'] . '
                  </strong></p>';
				}
				$html .= '</td>
              </tr>
            </table>';
            $display = ($productionJobData['note'] == '') ? 'display: none;' : '';
            $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
              <tr>
                <td>
                  <h4 style="' . $display . '">Notes</h4>
                  <p style="font-size: 14px; line-height: 22px; ' . $display . '">
                    ' . $productionJobData['note'] . '
                  </p>
                </td>
              </tr>
            </table>';
				
			$html .= '<table width="100%" cellspacing="0" cellpadding="0">
              <tr>
                <td colspan="2">
                  <h3 class="title mb-4">Order Id #' . $orderData['order_number'] . '</h3>
                </td>
              </tr>
              <tr>
                <td style="vertical-align: top;">
                  <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                      <td style="padding: 5px;">
                        <figure style="width: 100px; margin: 0;">
                          <img src="' . $getProductionJobDetails['product_image'][0]['thumbnail'] . '" style="width: 100%;" alt=""/>
                        </figure>
                      </td>
                    </tr>
                  </table>
                </td>
                <td style="vertical-align: top; padding-left: 40px;">
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Product name </td>
                      <td style="padding: 0 20px 4px 0px;">
                        <strong>:' . $orderData['orders']['name'] . '</strong>
                      </td>
                    </tr>
                     <tr>
                      <td style="padding: 0 20px 4px 0px;">SKU </td>
                      <td style="padding: 0 20px 4px 0px;">
                        <strong>:' . $orderData['orders']['sku'] . '</strong>
                      </td>
                    </tr>
                     <tr>
                      <td style="padding: 0 20px 4px 0px;">Quantity </td>
                      <td style="padding: 0 20px 4px 0px;">
                        <strong>:' . $orderData['orders']['quantity'] . '</strong>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
              <tr>
                <td colspan="2">
                  <h4 style="font-size: 16px; margin-top: 20px;">
                    Artwork used
                  </h4>
                  ';
                    foreach ($orderData['orders']['decoration_settings_data'] as $key => $decorationSettingsData) {
                        if (!empty($decorationSettingsData['decoration_data'])) {
                            $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="border: 0px solid #e3e3e3; font-size: 14px;">
                    <tr>
                      <td style="padding: 12px; border: 1px solid #e3e3e3;">
                        ';
                            foreach ($decorationSettingsData['decoration_data'] as $decoKey => $decorationData) {
                                $html .= '<table width="100%" cellspacing="0" cellpadding="0">
                          <tr>
                            <td style="vertical-align: top;">
                            <h3>Preview</h3>
                              <figure style="margin: 0; width: 150px; height: 150px;" >
                                <img src="' . $orderData['orders']['images'][$key]['src'] . '" alt="" style="width: 150px; height: 150px;" />
                              </figure>
                            </td>
                             <td style="vertical-align: top;">
                             <h3>Design</h3>
                              <figure style="margin: 0; width: 150px; height: 150px;">';
								$html .= "<img src='" . $decorationData['png_url'] . "' alt='' style='width: 150px; height: 150px;' /></figure>";
								if ($decorationData['design_width'] != '' && $decorationData['design_height'] != '') {
									$html .= '<p>' . $decorationData['design_width'] . ' X ' . $decorationData['design_height'] . ' (W X H) ' . $decorationData['print_unit'] . ' </p>';
								}
								$html .= '
                            </td>
                            <td style="vertical-align: top;">
                              <table>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Decoration area name</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['decoration_name'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Decoration area</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_area_name'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Print method</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_profile_name'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Width</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_area_width'] . ' ' . $decorationData['print_unit'] . '</strong></td>
                                </tr>
                                <tr>
                                  <td style="padding: 0 0px 4px 20px;">Height</td>
                                  <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationData['print_area_height'] . ' ' . $decorationData['print_unit'] . '</strong></td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                          </table>';
                            }
                            $html .= '</td></tr></table>';
                        }
                    }

                    $html .= '</td>
              </tr>
            </table>';

                
            $html .= '</div>
        	</body>';
        	$filePath = path('abs', 'production');
		 	$fileNames = create_pdf($html, $filePath, $productionJobData['job_id'], "portrait");
            $dir = $filePath . $fileNames;

            if (file_exists($dir)) {
            	$return = [
            		'dir' => $dir,
            		'qrcode_path' => $file
            	];
            	return $return;
            }
    	}
    }

    /**
	 * calculate due date 
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   06 June 2021
	 * @return date
	 */
	private function calculateDueDate($request, $response, $startingDate, $stageDuration) 
	{
		$settingData = $this->getProductionSetting($request, $response, ['module_id' => 4, 'return_type' => 1]);
		$settingData = $settingData['data'];
	
		$startingHours = '00:01';
		$endingHours  = '23:59';
		$weekendArr = [];
		$holidayListArr = [];
		
		if (isset($settingData['working_hours']) && !empty($settingData['working_hours'])) {
			$workingHours = $settingData['working_hours'];
			$startingHours = ($settingData['time_format'] == '12') ? date("H:i:s", strtotime($workingHours['starts_at'])) : $workingHours['starts_at'];
			$endingHours = ($settingData['time_format'] == '12') ? date("H:i:s", strtotime($workingHours['ends_at'])) : $workingHours['ends_at'];
		}
		if (isset($settingData['weekends']) && !empty($settingData['weekends'])) {
			$weekendArr = $settingData['weekends'];
		}
		if (isset($settingData['holiday_list']) && !empty($settingData['holiday_list'])) {
			$holidayListArr = array_column($settingData['holiday_list'], 'date');
		}
		$currentDate = date('Y-m-d', strtotime($startingDate));

		$finalStartingDate = $this->checkDueDateForAllCond($startingDate, $holidayListArr, $weekendArr, $startingHours, $endingHours);
		$expDueDate = $this->getExpDueDate($finalStartingDate, $stageDuration, $holidayListArr, $weekendArr, $startingHours, $endingHours);
		$result = [
			'start_date' => $finalStartingDate,
			'due_date' => $expDueDate
		];
		return $result;
	}

	private function checkDueDateForAllCond($startingDate, $holidayList, $weekendArr, $startingHours, $endingHours) 
	{
		//Check if starting date is working day or not
		$startingDate = $this->checkForHolidayList($startingDate, $holidayList);
		//Check if starting date is come under weekends
		$startingDate = $this->checkForWeekend($startingDate, $weekendArr, $holidayList);
		//Check for working hour
		$startingDate = $this->checkForWorkingHours($startingDate, $holidayList, $weekendArr, $startingHours, $endingHours);
		return $startingDate;
	}

	private function checkForHolidayList($date, $holidayList) 
	{
		$checkDate = date('Y-m-d', strtotime($date));
		if (!in_array($checkDate, $holidayList)) {
	  		return $date; 
	  	} else {
	  		$newDate = date('Y-m-d', strtotime($date . ' +1 day'));
	  		return $this->checkForHolidayList($newDate, $holidayList);
	  	}
	} 

	private function checkForWeekend($date, $weekendArr, $holidayList) 
	{
		$currentDay = strtolower(date('D', strtotime($date)));
		if (!in_array($currentDay, $weekendArr)) {
	  		return $date; 
	  	} else {
	  		$newDate = date('Y-m-d', strtotime($date . ' +1 day'));
	  		$newDate = $this->checkForHolidayList($newDate, $holidayList);
	  		return $this->checkForWeekend($newDate, $weekendArr, $holidayList);
	  	}
	}

	private function checkForWorkingHours($date, $holidayList, $weekendArr, $startingHours, $endingHours) 
	{
		$onlyDate = date('Y-m-d', strtotime($date));
		$startingDateTime = $onlyDate.' '.$startingHours;
		$endingDateTime = $onlyDate.' '.$endingHours;
		if (($date >= $startingDateTime) && ($date <= $endingDateTime)) {
		    return $date; 
	    //Check if date is before starting Hours
		} else if ($date < $startingDateTime) {
			return $startingDateTime;
		//Check if date is after ending Hours
		} else if ($date > $endingDateTime) {
			$newDate = date('Y-m-d', strtotime($onlyDate . ' +1 day'));
			//Check if starting date is working day or not
			$newDate = $this->checkForHolidayList($newDate, $holidayList);
			//Check if starting date is come under weekends
			$newDate = $this->checkForWeekend($newDate, $weekendArr, $holidayList);
			$newDate = date('Y-m-d', strtotime($newDate));
			$newStartingDate = $newDate.' '.$startingHours;
			return $newStartingDate;
		}
	}

	private function getExpDueDate($startDate, $remainingHours, $holidayList, $weekendArr, $startingHours, $endingHours) 
	{
		$remainingHoursArr = explode('.', $remainingHours);
		$stageDurationInSec = $remainingHours * 3600;
		
		//todays work
		$startingDateOnly = date('Y-m-d', strtotime($startDate));
		$endTimeForDate = $startingDateOnly.' '.$endingHours;
		$workOnDayInsec = strtotime($endTimeForDate) - strtotime($startDate);
		$remainingHoursInSec = $stageDurationInSec - $workOnDayInsec;
		$remainingworkingHours = round(($remainingHoursInSec / 3600), 2);
		if ($remainingworkingHours == 0 || $remainingworkingHours < 0) {
			$hours = floor($stageDurationInSec / 3600);
			$minutes = floor(($stageDurationInSec/60) - ($hours * 60));
			$expDeleviryDate = date('Y-m-d H:i:s', strtotime('+' . $hours . ' hour +' . $minutes . ' minutes', strtotime($startDate)));
			return $expDeleviryDate;
		} else {
			$onlyDate = date('Y-m-d', strtotime($startDate));
			$newDate = date('Y-m-d', strtotime($onlyDate . ' +1 day'));
			$newDate = $this->checkDueDateForAllCond($newDate, $holidayList, $weekendArr, $startingHours, $endingHours);
			return $this->getExpDueDate($newDate, $remainingworkingHours, $holidayList, $weekendArr, $startingHours, $endingHours);
		}
	}

	/**
	 * GET: Production Job Holiday List 
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   10 June 2020
	 * @return json response
	 */
	public function getProductionHolidayList($request, $response) 
	{
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'data' => [],
		];
		$getStoreDetails = get_store_details($request);
		$productionJobHolidaysInit = new ProductionJobHolidays();
		$holidayList = $productionJobHolidaysInit->where('store_id', $getStoreDetails['store_id'])
			->orderBy('date', 'ASC');
		if ($holidayList->count() > 0) {
			$getHolidayListData = $holidayList->get();
			$jsonResponse = [
				'status' => 1,
				'data' => $getHolidayListData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}


	/**
	 * POST: Save Production Job Holiday List 
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   10 June 2020
	 * @return json response
	 */
	public function saveProductionHolidayList($request, $response) 
	{
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Holiday List', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $request->getParsedBody();

		if ($allPostPutVars['date'] != '' && $allPostPutVars['holiday_name'] != '' && $allPostPutVars['day'] != '') {
			$productionJobHolidaysInit = new ProductionJobHolidays();
			$holidayList = $productionJobHolidaysInit->where([
				'store_id' => $getStoreDetails['store_id'],
				'date' => $allPostPutVars['date']
			]);
			if ($holidayList->count() == 0) {
				//Add holiday
				$saveData = [
					'store_id' => $getStoreDetails['store_id'],
					'holiday_name' => $allPostPutVars['holiday_name'],
					'day' => $allPostPutVars['day'],
					'date' => $allPostPutVars['date'],
				];
				$holidaySave = new ProductionJobHolidays($saveData);
				if ($holidaySave->save()) {
					$holidayList = $productionJobHolidaysInit->where('store_id', $getStoreDetails['store_id'])
						->orderBy('date', 'ASC');
					if ($holidayList->count() > 0) {
						$getHolidayListData = $holidayList->get();
					}
					$jsonResponse = [
						'status' => 1,
						'message' => message('Production Job Holiday List', 'saved'),
						'data' => $getHolidayListData
					];
				}
				
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => message('Production Job Holiday List', 'exist'),
				];
			}
		}
		
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Production Job Stage Progress
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   15 June 2021
	 * @return json response
	 */
	public function getProductionStageProgress($request, $response, $args) 
	{
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Stage Progress', 'not_found'),
		];
		$getStoreDetails = get_store_details($request);
		if (!empty($args['id'])) {
			$productionJobId = $args['id'];
			$productionJobInit = new ProductionJobs();

			$productionJob = $productionJobInit->where([
				'store_id' => $getStoreDetails['store_id'],
				'xe_id' => $productionJobId,
			]);
			$userInit = new User();
			$allAgent = $userInit->select('xe_id as id', 'name')->where('store_id', $getStoreDetails['store_id']);
			$allAgentArr = json_clean_decode($allAgent->get(), true);
			$userRoleInit = new UserRole();
			$allGroup = $userRoleInit->select('xe_id as id', 'role_name')->where('store_id', $getStoreDetails['store_id']);
			$allGroupArr = json_clean_decode($allGroup->get(), true);
			$finalProductJobData = [];
			if ($productionJob->count() > 0) {
				$productionJobStagesInit = new ProductionJobStages();
				$stageData = $productionJobStagesInit->select('xe_id', 'stage_name', 'stage_color_code', 'starting_date', 'exp_completion_date', 'completion_date', 'status', 'print_method_id')->where('job_id', $productionJobId)->orderBy('xe_id', 'ASC');
				if ($stageData->count() > 0) {
					$stageDataArr = $stageData->get();
					foreach ($stageDataArr as $stageKey => $stageData) {
						//Get print method name
						$printMethodData = $this->getPrintMethodData($stageData['print_method_id']);
						$printMethodName = $printMethodData['name'];
						$stageDataArr[$stageKey]['print_method_name'] = $printMethodName;
						
						$productionJobAgentInit = new ProductionJobAgents();
						$assigneeData = $productionJobAgentInit->where([
							'job_id' => $productionJobId,
							'job_stage_id' => $stageData['xe_id'],
						]);
						$finalAssignee = [];
						$finalIsGroup = [];
						if ($assigneeData->count() > 0) {
							$assigneeDataArr = $assigneeData->get();
							foreach ($assigneeDataArr as $assignee) {
								if ($assignee['is_group'] == 0) {
									$agentId = $assignee['agent_id'];
									$agentDetails = array_filter($allAgentArr, function ($item) use ($agentId) {
										return $item['id'] == $agentId;
									});
									$agentDetails = $agentDetails[array_keys($agentDetails)[0]];
									$tempData['id'] = $agentId;
									$tempData['name'] = $agentDetails['name'];
								} else {
									$groupId = $assignee['agent_id'];
									$groupDetails = array_filter($allGroupArr, function ($item) use ($groupId) {
										return $item['id'] == $groupId;
									});
									$groupDetails = $groupDetails[array_keys($groupDetails)[0]];
									$tempData['id'] = $groupId;
									$tempData['name'] = $groupDetails['role_name'];
								}
								array_push($finalAssignee, $tempData);
								array_push($finalIsGroup, $assignee['is_group']);
							}
						}
						$stageDataArr[$stageKey]['agents'] = $finalAssignee;
					}
				}
				$jsonResponse = [
					'status' => 1,
					'data' => $stageDataArr,
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * POST : Update Production holiday list
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   18 June 2020
	 * @return Updated Json Status
	 */
	public function updateProductionHolidayList($request, $response, $args) 
	{
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Holiday List', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$getStoreDetails = get_store_details($request);
		if (!empty($args) && $args['id'] > 0) {
			$holidayId = to_int($args['id']);
			$getHolidayListData = [];
			$productionJobHolidaysInit = new ProductionJobHolidays();
			$holidayData = $productionJobHolidaysInit->where('xe_id', $holidayId);
			if ($holidayData->count() > 0) {
				$productionJobHolidaysInit->where([
					'xe_id' => $holidayId,
					'store_id' => $getStoreDetails['store_id'],
				])->update([
					'holiday_name' => $allPostPutVars['holiday_name'],
					'day' => $allPostPutVars['day'],
					'date' => $allPostPutVars['date'],
				]);
				$holidayList = $productionJobHolidaysInit->where('store_id', $getStoreDetails['store_id'])
					->orderBy('date', 'ASC');
				if ($holidayList->count() > 0) {
					$getHolidayListData = $holidayList->get();
				}
				$jsonResponse = [
					'status' => 1,
					'message' => message('Production Job Holiday List', 'updated'),
					'data' => $getHolidayListData
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Delete : Delete Production holiday list
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   10 June 2020
	 * @return Delete Json Status
	 */
	public function deleteProductionHolidayList($request, $response, $args) 
	{
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Production Job Holiday List', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		if (!empty($args) && $args['id'] > 0) {
			$holidayId = to_int($args['id']);
			$getHolidayListData = [];
			$productionJobHolidaysInit = new ProductionJobHolidays();
			$holidayData = $productionJobHolidaysInit->where('xe_id', $holidayId);
			if ($holidayData->count() > 0) {
				$productionJobHolidaysInit->where('xe_id', $holidayId)->delete();
				$holidayList = $productionJobHolidaysInit->where('store_id', $getStoreDetails['store_id'])
					->orderBy('date', 'ASC');
				if ($holidayList->count() > 0) {
					$getHolidayListData = $holidayList->get();
				}
				$jsonResponse = [
					'status' => 1,
					'message' => message('Production Job Holiday List', 'deleted'),
					'data' => $getHolidayListData
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

}