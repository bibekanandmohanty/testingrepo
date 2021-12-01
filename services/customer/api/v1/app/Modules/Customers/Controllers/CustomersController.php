<?php
/**
 * Manage Customers
 *
 * PHP version 5.6
 *
 * @category  Customer
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Customers\Controllers;
use CustomerStoreSpace\Controllers\StoreCustomersController;
use App\Modules\Customers\Models\CustomerInternalNotes;
use App\Modules\Customers\Models\CustomerInternalNoteFiles;
use App\Modules\Users\Models\User;

/**
 * Customers Controller
 *
 * @category Class
 * @package  Customer
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class CustomersController extends StoreCustomersController {
	/**
	 * GET: List of Customers
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   13 Nov 2019
	 * @return All/Single Customer(s) List
	 */
	public function allCustomers($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Customer', 'not_found'),
			'data' => [],
		];
		$isLineItem = to_int((!empty($request->getQueryParam('orders'))
			&& $request->getQueryParam('orders') != "")
			? $request->getQueryParam('orders') : 0);
		$storeResponse = $this->getCustomers($request, $response, $args);
		if (!empty($storeResponse)) {
			// For updating preview images
			if ($isLineItem) {
				foreach ($storeResponse['orders'] as $orderKey => $orderDetails) {
					if ((array_key_exists('lineItems', $orderDetails) && !empty($orderDetails['lineItems']))) {
						$k = 0;
						foreach ($orderDetails['lineItems'] as $itemsKey => $itemsValue) {
							$designImages = [];
							if (!empty($itemsValue['custom_design_id'])) {
								$customDesignId = $itemsValue['custom_design_id'];
								$deisgnStatePath = path('abs', 'design_state') . 'carts';
								$predecoPath = path('abs', 'design_state') . 'predecorators';
								$orderJsonPath = $deisgnStatePath . '/' . $customDesignId . ".json";
								$orderPredecoPath = $predecoPath . '/' . $customDesignId . ".json";
								if (file_exists($orderJsonPath)) {
									$orderJson = read_file($orderJsonPath);
									$jsonContent = json_clean_decode($orderJson, true);
								} elseif (file_exists($orderPredecoPath)) {
									$orderJson = read_file($orderPredecoPath);
									$jsonContent = json_clean_decode($orderJson, true);
								}
								if (!empty($jsonContent['design_product_data'])) {
									foreach ($jsonContent['design_product_data'] as $designImage) {
										if ($itemsValue['variant_id'] == 0 || in_array($itemsValue['variant_id'], $designImage['variant_id'])) {
											if (!empty($designImage['design_urls'])) {
												foreach ($designImage['design_urls'] as $image) {
													$designImages[] = [
														'src' => $image,
														'thumbnail' => $image,
													];
												}
											}
										}
									}
								}
							}
							if (count($designImages) > 0) {
								$storeResponse['orders'][$orderKey]['lineItems'][$itemsKey]['images'] = $designImages;
							}
						}
					}
				}
			}
			// End
			$totalCustomer = count($storeResponse['customer_list']);
			$customer_count = $storeResponse['total_user'];
			if (isset($args['id'])) {
				$totalCustomer = 1;
			}
			$jsonResponse = [
				'status' => empty($totalCustomer) ? 0 : 1,
				'records' => isset($args['id']) ? 1 : $totalCustomer,
				'total_customer' => isset($args['id']) ? 1 : $customer_count,
				'data' => isset($args['id']) ? $storeResponse : $storeResponse['customer_list'],
			];
			if (empty($totalCustomer)) {
				$jsonResponse += ['message' => 'The record(s) you requested not found'];
			}
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * GET:Delete shipping address
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   31 March 2020
	 * @return String
	 */
	public function deleteShipping($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->deleteShippingAddress($request, $response, $args);
		if (empty($storeResponse)) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Response Error',

			];
		} else {
			$jsonResponse = [
				'status' => $storeResponse['status'],
				'message' => $storeResponse['message'],
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * POST:Update shipping address
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   31 March 2020
	 * @return String
	 */
	public function updateShipping($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->updateShippingAddress($request, $response, $args);
		if (empty($storeResponse)) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Response Error',
			];
		} else {
			$jsonResponse = [
				'status' => $storeResponse['status'],
				'message' => $storeResponse['message'],
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * POST:Create shipping address
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   31 March 2020
	 * @return String
	 */
	public function createShipping($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->createShippingAddress($request, $response, $args);
		if (empty($storeResponse)) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Response Error',
			];
		} else {
			$jsonResponse = [
				'status' => $storeResponse['status'],
				'message' => $storeResponse['message'],
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * POST:Create customer
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function customerCreate($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->createCustomer($request, $response, $args);
		if (empty($storeResponse)) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Response Error',
			];
		} else {
			$jsonResponse = [
				'status' => $storeResponse['status'],
				'message' => $storeResponse['message'],
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * POST:Update customer
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function customerUpdate($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->updateCustomer($request, $response, $args);
		if (empty($storeResponse)) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Response Error',
			];
		} else {
			$jsonResponse = [
				'status' => $storeResponse['status'],
				'message' => $storeResponse['message'],
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * POST:Update customer
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function customerDelete($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->deleteCustomer($request, $response, $args);
		if (empty($storeResponse)) {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Response Error',
			];
		} else {
			$jsonResponse = [
				'status' => $storeResponse['status'],
				'message' => $storeResponse['message'],
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * GET :All Countries
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author soumya@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	Public function allCountries($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->getAllCountries($request, $response);
		$jsonResponse = [
			'status' => 0,
			'message' => 'no data found',
		];
		if (!empty($storeResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $storeResponse,
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);

	}
	/**
	 * GET:Get all states by country code
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   01 April 2020
	 * @return Array
	 */
	public function allStates($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$storeResponse = $this->getAllStates($request, $response, $args);
		$jsonResponse = [
			'status' => 0,
			'message' => 'no data found',
		];
		if (!empty($storeResponse)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $storeResponse,
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}
	/**
	 * GET:Customer abbriviation values
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   21 September 2020
	 * @return Json
	 */
	public function getCustomerAbbriviationValues($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Email template', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$template_type_name = $allPostPutVars['template_type_name'];
		$module_id = $allPostPutVars['module_id'];
		$getStoreDetails = get_store_details($request);
		$responseData = array();
		if (!empty($template_type_name) && !empty($module_id)) {
			$templateData = $this->getEmailTemplate($module_id, $getStoreDetails, $template_type_name);
			$subjectString = $templateData[0]['subject'];
			$messageString = $templateData[0]['message'];
			$ldelim = "{";
			$rdelim = "}";
			$pattern = "/" . preg_quote($ldelim) . "(.*?)" . preg_quote($rdelim) . "/";
			preg_match_all($pattern, $subjectString, $matches);
			$subjectAbbriviation = $matches[1];
			preg_match_all($pattern, $messageString, $matches);
			$messageAbbriviation = $matches[1];
			$abbriviationData = array_merge($subjectAbbriviation, $messageAbbriviation);
			$abbriviationData = array_unique($abbriviationData);

			$customerDetails = $this->getCustomers($request, $response, $args);
			foreach ($abbriviationData as $abbrData) {
				$abbrName = '{' . $abbrData . '}';
				if ($abbrName !== false) {
					$abbrValue = $this->getAbbriviationValues($abbrName, $customerDetails);
					$templateData[0]['subject'] = str_replace($abbrName, $abbrValue, $templateData[0]['subject']);
					$templateData[0]['message'] = str_replace($abbrName, $abbrValue, $templateData[0]['message']);
				}
			}
			$responseData = $templateData;
			$jsonResponse = [
				'status' => 1,
				'data' => $responseData,
			];
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Email template type mismatch / module id  empty ',
			];

		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET:Customer abbriviation values
	 *
	 * @param $abbriviationName
	 * @param $customerDetails
	 *
	 * @author soumya@riaxe.com
	 * @date   21 September 2020
	 * @return String
	 */
	public function getAbbriviationValues($abbriviationName, $customerDetails) {
		$abbrValue = "";
		switch ($abbriviationName) {
		case "{customer_name}":
			$abbrValue = ($customerDetails['first_name'] != '') ? $customerDetails['first_name'] . ' ' . $customerDetails['last_name'] : $customerDetails['email'];
			break;
		case "{customer_address}":
			$billing_address = $customerDetails['billing_address'];
			$address_1 = $billing_address['address_1'] ? $billing_address['address_1'] : '';
			$address_2 = $billing_address['address_2'] ? $billing_address['address_2'] : '';
			$city = $billing_address['city'] ? $billing_address['city'] : '';
			$state = $billing_address['state'] ? $billing_address['state'] : '';
			$country = $billing_address['country'] ? $billing_address['country'] : '';
			$postCode = $billing_address['postcode'] ? $billing_address['postcode'] : '';
			$abbrValue = $address_1 . ' ' . $address_2 . ' ' . $city . '' . $state . ' ' . $postCode . ' ' . $country;
			break;
		case "{customer_email}":
			$abbrValue = $customerDetails['email'] ? $customerDetails['email'] : '';
			break;
		case "{signup_date}":
			$abbrValue = $customerDetails['date_created'] ? $customerDetails['date_created'] : '';
			break;
		case "{order_value}":
			$abbrValue = $customerDetails['total_order_amount'] ? $customerDetails['total_order_amount'] : '';
			break;
		case "{number_of_orders}":
			$abbrValue = $customerDetails['total_orders'] ? $customerDetails['total_orders'] : '';
			break;
		case "{last_order}":
			$abbrValue = $customerDetails['last_order_id'] ? $customerDetails['last_order_id'] : '';
			break;
		case "{mobile_no}":
			$abbrValue = $customerDetails['billing_address']['phone'] ? $customerDetails['billing_address']['phone'] : '';
			break;
		default:
			$abbrValue = $abbriviationName;
		}
		return $abbrValue;
	}
	/**
	 * GET:Customer abbriviation values
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   21 September 2020
	 * @return Json
	 */
	public function sendPromotionalEmail($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Save order token ', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$customerIds = $allPostPutVars['customer_id'];
		$template_type_name = $allPostPutVars['template_type'];
		$emailLogId = $allPostPutVars['email_log_id'];
		$totalEmailCount = $allPostPutVars['total_email_count'];
		$module_id = 6;
		$getStoreDetails = get_store_details($request);
		$templateData = $this->getEmailTemplate($module_id, $getStoreDetails, $template_type_name);
		$unsuccessEmails = '';
		$successEmails = '';
		$skippedEmails = '';
		if ($customerIds != '' && $customerIds > 0 && !empty($templateData)) {
			$customerDetails = $this->getCustomers($request, $response, ['id' => $customerIds]);
			$emailTemplateData = $this->bindPromotionalEmailTemplate($template_type_name, $customerDetails, $getStoreDetails, $module_id);
			$mailResponse = $this->sendEmailToCustomer($emailTemplateData[0], $customerDetails['email'], $customerDetails['first_name'] . '' . $customerDetails['last_name']);
			if (!empty($mailResponse['status']) && $mailResponse['status'] == 1) {
				$successEmails = $customerDetails['email'];
			} else {
				$unsuccessEmails = $customerDetails['email'];
			}
			//Save data for Email Log
			if ($emailLogId == 0) {
				$emalLogData = [
					'store_id' => $getStoreDetails['store_id'],
					'module' => 'customer',
					'type' => $template_type_name,
					'subject' => $emailTemplateData[0]['subject'],
					'message' => $emailTemplateData[0]['message'],
					'total_email_count' => $totalEmailCount,
					'success_email' => $successEmails,
					'failure_email' => $unsuccessEmails,
					'skipped_email' => $skippedEmails,
					'created_date' => date_time(
						'today', [], 'string'
					)
				];
				$emailLogId = $this->saveDataForEmailLog($emalLogData);
			} else {
				$updateEmailLogData = [
					'email_log_id' => $emailLogId,
					'subject' => $emailTemplateData[0]['subject'],
					'message' => $emailTemplateData[0]['message'],
					'success_email' => $successEmails,
					'failure_email' => $unsuccessEmails,
					'skipped_email' => $skippedEmails,
				];
				$emailLogId = $this->updateDataForEmailLog($updateEmailLogData);
			}
			$jsonResponse = [
				'status' => 1,
				'success_mails' => $successEmails,
				'unsuccess_emails' => $unsuccessEmails,
				'skipped_emails' => $skippedEmails,
				'email_log_id' => $emailLogId,
			];

		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'Customer id empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET:Promotional email template data
	 *
	 * @param $emailType
	 * @param $customerDetails
	 * @param $storeId
	 * @param $module_id
	 *
	 * @author soumya@riaxe.com
	 * @date   21 September 2020
	 * @return Array
	 */
	public function bindPromotionalEmailTemplate($emailType, $customerDetails, $getStoreDetails, $module_id) {
		$resData = [];
		if ($emailType != '' && !empty($customerDetails)) {
			$templateData = $this->getEmailTemplate($module_id, $getStoreDetails, $emailType);
			$subjectString = $templateData[0]['subject'];
			$messageString = $templateData[0]['message'];
			$ldelim = "{";
			$rdelim = "}";
			$pattern = "/" . preg_quote($ldelim) . "(.*?)" . preg_quote($rdelim) . "/";
			preg_match_all($pattern, $subjectString, $matches);
			$subjectAbbriviation = $matches[1];
			preg_match_all($pattern, $messageString, $matches);
			$messageAbbriviation = $matches[1];
			$abbriviationData = array_merge($subjectAbbriviation, $messageAbbriviation);
			$abbriviationData = array_unique($abbriviationData);
			if (!empty($abbriviationData)) {
				foreach ($abbriviationData as $abbrData) {
					$abbrName = '{' . $abbrData . '}';
					if ($abbrName !== false) {
						$abbrValue = $this->getAbbriviationValues($abbrName, $customerDetails);
						$templateData[0]['subject'] = str_replace($abbrName, $abbrValue, $templateData[0]['subject']);
						$templateData[0]['message'] = str_replace($abbrName, $abbrValue, $templateData[0]['message']);
					}
				}
			}

			$resData = $templateData;
		}
		return $resData;
	}
	/**
	 * Email send to Customer
	 *
	 * @param $templateData
	 * @param $customerEmail
	 * @param $customerName
	 *
	 * @author soumya@riaxe.com
	 * @date   21 September 2020
	 * @return Array
	 */
	public function sendEmailToCustomer($templateData, $customerEmail, $customerName) {
		$smtpEmailSettingData = call_curl([],
			'settings', 'GET', true
		);
		$attachments = [];
		$emailData = $smtpEmailSettingData['general_settings']['email_address_details'];
		$smtpData = $smtpEmailSettingData['general_settings']['smtp_details'];
		$fromEmail = $emailData['from_email'];
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
			'attachments' => ($attachments != '') ? $attachments : [],
			'subject' => $templateData['subject'],
			'body' => $templateData['message'],
			'smptData' => $smtpData,
		];
		if ($smtpData['smtp_host'] != '' && $smtpData['smtp_user'] != '' && $smtpData['smtp_pass'] != '') {
			$mailResponse = email($mailContaint);
		} else {
			$mailResponse['status'] = 0;
		}
		return $mailResponse;
	}
	/**
	 * GET:Country & State Name
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   21 September 2020
	 * @return Json
	 */
	public function getCountryStateName($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Parameter empty ', 'error'),
		];
		if (!empty($args)) {
			$storeResponse = $this->getStoreCountryState($request, $response, $args);
			$jsonResponse = [
				'status' => 1,
				'data' => $storeResponse,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);

	}
	/**
	 * GET: Total customer
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   16 December 2020
	 * @return Json
	 */
	public function getTotalCustomerCount($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('User count ', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$storeId = $getStoreDetails['store_id'] ? $getStoreDetail['store_id'] : 1;

		$totalCustomer = $this->getTotalStoreCustomer($storeId);
		if ($totalCustomer > 0) {
			$jsonResponse = [
				'status' => 1,
				'total_customer' => $totalCustomer,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);

	}
	/**
	 * GET:Customer ids
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumya@riaxe.com
	 * @date   16 December 2020
	 * @return Json
	 */
	public function allCustomersIds($request, $response, $args) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('User Deatils ', 'error'),
		];
		$customerIds = $this->getStoreCustomerId($request, $response, $args);
		if (!empty($customerIds)) {
			$jsonResponse = [
				'status' => 1,
				'data' => $customerIds,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
     * POST: Add internal note to customer
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   24 Feb 2021
     * @return json response wheather data is saved or any error occured
     */
    public function saveInternalNote($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Internal Note', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $customerId = to_int($allPostPutVars['customer_id']);
        if ($customerId != '') {
            $allPostPutVars['created_date'] = date_time(
                'today', [], 'string'
            );
            $allPostPutVars['store_id'] = $getStoreDetails['store_id'];
            $customerInternalNotes = new CustomerInternalNotes($allPostPutVars);
            if ($customerInternalNotes->save()) {
                $noteInsertId = $customerInternalNotes->xe_id;
                $allFileNames = do_upload(
                    'upload',
                    path('abs', 'customer') . 'internal-note/', [150],
                    'array'
                );
                //Save file name w.r.t note
                if (!empty($allFileNames)) {
                    foreach ($allFileNames as $eachFile) {
                        $fileData = [
                            'note_id' => $noteInsertId,
                            'file' => $eachFile,
                        ];
                        $saveNoteFile = new CustomerInternalNoteFiles($fileData);
                        $saveNoteFile->save();
                    }
                }
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Internal Note', 'saved'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }


    /**
     * GET : Customer internal note
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   25 Feb 2019
     * @return json response
     */
    public function getInternalNote($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'data' => [],
            'message' => message('Internal Note', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        $noteRes = [];
        if (!empty($args['id'])) {
            $customerId = to_int($args['id']);
            //Get internal note data
            $internalNoteInit = new CustomerInternalNotes();
            $internalNotes = $internalNoteInit->with('files')
            	->where([
            		'store_id' => $getStoreDetails['store_id'],
            		'customer_id' => $customerId
            	])->orderBy('created_date', 'DESC');
            if ($internalNotes->count() > 0) {
                $noteDataArr = $internalNotes->get();
                foreach ($noteDataArr as $noteData) {
                    $newNoteArr = $noteData;
                    $userName = $newNoteArr['user_type'];
                    //Get user name
                    $userInit = new User();
                    $agent = $userInit->select('xe_id', 'name')->where('xe_id', $newNoteArr['user_id']);
                    if ($agent->count() > 0) {
                        $agentDetails = json_clean_decode($agent->first(), true);
                        $userName = $agentDetails['name'];
                    }
                    $newNoteArr['description'] = $newNoteArr['note'];
                    $newNoteArr['created_by'] = $userName;
                    $newNoteArr['created_at'] = $newNoteArr['created_date'];
                    unset(
                        $newNoteArr['note'],
                        $newNoteArr['seen_flag'],
                        $newNoteArr['created_date']
                    );
                    array_push($noteRes, $newNoteArr);
                }
            }
                
            $jsonResponse = [
                'status' => 1,
                'data' => $noteRes,
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
