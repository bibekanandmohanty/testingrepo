<?php
/**
 * Manage Quotations
 *
 * PHP version 5.6
 *
 * @category  Quotations
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Quotations\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Orders\Controllers\OrderDownloadController;
use App\Modules\Orders\Controllers\OrdersController;
use App\Modules\Quotations\Models\ProductionHubSetting;
use App\Modules\Quotations\Models\ProductionStatus;
use App\Modules\Quotations\Models\ProductionTags;
use App\Modules\Quotations\Models\QuotationConversationFiles;
use App\Modules\Quotations\Models\QuotationConversations;
use App\Modules\Quotations\Models\QuotationDynamicForm;
use App\Modules\Quotations\Models\QuotationDynamicFormAttributes;
use App\Modules\Quotations\Models\QuotationInternalNote;
use App\Modules\Quotations\Models\QuotationInternalNoteFiles;
use App\Modules\Quotations\Models\QuotationItemFiles;
use App\Modules\Quotations\Models\QuotationItems;
use App\Modules\Quotations\Models\QuotationItemVariants;
use App\Modules\Quotations\Models\QuotationLog;
use App\Modules\Quotations\Models\QuotationPayment;
use App\Modules\Quotations\Models\QuotationRequestDetails;
use App\Modules\Quotations\Models\QuotationRequestFormValues;
use App\Modules\Quotations\Models\Quotations;
use App\Modules\Quotations\Models\QuotationTagRelation;
use App\Modules\Customers\Controllers\CustomersController;
use App\Components\Models\ProductionAbbriviations;
use App\Modules\Quotations\Controllers\QuotationPaymentController;
use App\Modules\Products\Models\AppUnit;
use App\Modules\DecorationAreas\Models\PrintArea;
use App\Modules\Users\Models\User;
use App\Modules\Products\Controllers\ProductDecorationsController;
use App\Modules\Products\Controllers\ProductsController;
use Illuminate\Database\Capsule\Manager as DB;
use App\Modules\Orders\Models\Orders;
use ComponentStoreSpace\Controllers\StoreComponent;

/**
 * Quotations Controller
 *
 * @category Quotations
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class QuotationController extends ParentController
{
    /**
     * GET: Quotation Id
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   27 mar 2020
     * @return json response
     */
    public function getQuoteId($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Id', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $quotationInit = new Quotations();
        $lastQuoteId = '';
        $lastRecord = $quotationInit->select('quote_id')->latest()->first();
        if (!empty($lastRecord)) {
            $lastQuoteId = $lastRecord->quote_id;
        }
        //Generate Quote Id
        $quoteId = $this->generateQuoteId($request, $lastQuoteId);
        if ($quoteId != '') {
            $jsonResponse = [
                'status' => 1,
                'data' => $quoteId,
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    private function generateQuoteId($request, $lastQuoteId = '')
    {
        //Get quotation setting data
        $getStoreDetails = get_store_details($request);
        $settingInit = new ProductionHubSetting();
        $settingData = $settingInit->select('setting_value', 'flag')
            ->where([
                'module_id' => 1,
                'setting_key' => 'quote_id',
                'store_id' => $getStoreDetails['store_id'],
            ]);
        if ($settingData->count() > 0) {
            $settingDataArr = $settingData->first()->toArray();
            $settingValue = json_clean_decode($settingDataArr['setting_value'], true);
            $preFix = $settingValue['prefix'];
            $startingNum = $settingValue['starting_number'];
            $postFix = $settingValue['postfix'];
            $flag = 0;
            if ($settingDataArr['flag'] == 1 && $flag == 1) {
                $flag = 1;
                $newQuoteId = $preFix . $startingNum . $postFix;
            } else if ($lastQuoteId == '') {
                $newQuoteId = $preFix . $startingNum . $postFix;
            } else {
                $postFixLen = strlen($postFix);
                if(0 === strpos($lastQuoteId, $preFix)){
                    $withoutPrefix = substr($lastQuoteId, strlen($preFix)).'';
                }
                $quoteNum = substr($withoutPrefix, 0, -$postFixLen);
                //$quoteNum = preg_replace('/[^0-9]/', '', $lastQuoteId);
                $newQuoteNum = $quoteNum + 1;
                $newQuoteId = $preFix . $newQuoteNum . $postFix;
            }
            $quotationInit = new Quotations();
            $quoteData = $quotationInit->where(
                [
                    'store_id' => $getStoreDetails['store_id'],
                    'quote_id' => $newQuoteId,
                ]);
            if ($quoteData->count() > 0) {
                return $this->generateQuoteId($request, $newQuoteId);
            } else {
                return $newQuoteId;
            }
        }
    }

    /**
     * GET: Quotation Listing
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   24 Mar 2020
     * @return json response
     */
    public function getQuotationList($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        // Collect all Filter columns from url
        $page = $request->getQueryParam('page');
        $perpage = $request->getQueryParam('perpage');
        $sortBy = $request->getQueryParam('sortby');
        $order = $request->getQueryParam('order');
        $keyword = $request->getQueryParam('keyword');
        $customerId = $request->getQueryParam('customer_id');
        $tagId = $request->getQueryParam('tag_id');
        $from = $request->getQueryParam('from');
        $to = $request->getQueryParam('to');
        $statusId = $request->getQueryParam('status_id');
        $statusId = json_clean_decode($statusId, true);
        $agentId = $request->getQueryParam('agent_id');
        $paymentStatus = $request->getQueryParam('payment_status');
        $loginId = $request->getQueryParam('login_id');
        $quoteSource = $request->getQueryParam('quote_source');

        $quotationInit = new Quotations();
        $conversationInit = new QuotationConversations();
        $getQuotations = $quotationInit->join(
            'production_status',
            'quotations.status_id',
            '=',
            'production_status.xe_id')
            ->select('quotations.xe_id as id', 'quotations.quote_id', 'quotations.customer_id', 'quotations.quote_total as total_amount', 'quotations.status_id', 'quotations.agent_id', 'quotations.created_at as created_date', 'quotations.draft_flag', 'quotations.invoice_id', 'quotations.request_payment', 'quotations.request_date', 'production_status.status_name as quote_status', 'production_status.color_code', 'quotations.customer_name', 'quotations.customer_email', 'quotations.customer_availability', 'quotations.order_id', 'quotations.is_ready_to_send', 'quotations.quote_source', DB::raw("(SELECT count(xe_id) FROM `quote_items` WHERE quote_id = quotations.xe_id) as total_item"))
            ->where('quotations.store_id', $getStoreDetails['store_id']);
        $withoutFilterCount = $getQuotations->count();
        //$getQuotations->whereIn('quotations.customer_id', $allCustomerIds);
        

        //Search by quote title and quote id
        if (isset($keyword) && $keyword != "") {
            $getQuotations->where('quotations.title', 'LIKE', '%' . $keyword . '%')
                ->orWhere('quotations.quote_id', 'LIKE', '%' . $keyword . '%');
        }
        //Filter by customer
        if (isset($customerId) && $customerId > 0) {
            $getQuotations->where('quotations.customer_id', $customerId);
        }
        //Filter by status
        if (isset($statusId) && count($statusId) > 0) {
            $getQuotations->whereIn('quotations.status_id', $statusId);
        } else {
            //Get Order status id
            $statusInit = new ProductionStatus();
            $orderStatusId = 0;
            $getOrderedStatusData = $statusInit->select('xe_id')->where([
                'store_id' => $getStoreDetails['store_id'], 
                'slug' => 'ordered',
                'module_id' => 1
            ]);

            if ($getOrderedStatusData->count() > 0) {
               $getOrderedStatusDataArr = $getOrderedStatusData->first(); 
               $getOrderedStatusDataArr = json_clean_decode($getOrderedStatusDataArr, true);
               $orderStatusId = $getOrderedStatusDataArr['xe_id'];
            }
            $getQuotations->whereNotIn('quotations.status_id', [$orderStatusId]);
        }
        //Filter by agent
        if (isset($agentId) && $agentId > 0) {
            $getQuotations->where('quotations.agent_id', $agentId);
        }
        //Filter by login type
        if (isset($loginId) && $loginId > 0) {
            $getQuotations->where('quotations.agent_id', $loginId);
        }
        //Filter by tag
        if (isset($tagId) && $tagId != "") {
            $tagSearch = json_clean_decode($tagId, true);
            $getQuotations->whereHas(
                'quotationTag', function ($q) use ($tagSearch) {
                    return $q->whereIn('tag_id', $tagSearch);
                }
            );
        }
        //Filter by date
        if (isset($from) && isset($to)) {
            if ($from != "" && $to == '') {
                //Filter by only from date
                $getQuotations->where('quotations.created_at', '>=', $from);
            } else if ($from == "" && $to != '') {
                $to = date('Y-m-d H:i:s', strtotime($to . ' +1 day'));
                //Filter by only to date
                $getQuotations->where('quotations.created_at', '<=', $to);
            } else if ($from != "" && $to != '') {
                $to = date('Y-m-d H:i:s', strtotime($to . ' +1 day'));
                //Filter by from and to date
                $getQuotations->where('quotations.created_at', '>=', $from)
                    ->where('quotations.created_at', '<=', $to);
            }
        }
        //Filter by quote source
        if (isset($quoteSource) && $quoteSource != '') {
            $getQuotations->where('quotations.quote_source', $quoteSource);
        }

        // Total records including all filters
        $getTotalPerFilters = $getQuotations->count();
        $offset = 0;
        if (isset($page) && $page != "") {
            $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
            $offset = $totalItem * ($page - 1);
            $getQuotations->skip($offset)->take($totalItem);
        }
        // Sorting by column name and sord order parameter
        if (isset($sortBy) && $sortBy != "" && isset($order) && $order != "") {
            $getQuotations->orderBy('quotations.' . $sortBy, $order);
        }

        $userInit = new User();
        $agentData = $userInit->select('xe_id as id', 'name')->where('store_id', $getStoreDetails['store_id']);
        $agentListArr = json_clean_decode($agentData->get(), true);
        if ($getTotalPerFilters > 0) {
            $getQuotationData = $getQuotations->get();
            $quotationRes = [];
            foreach ($getQuotationData as $quotationData) {
                $cusId = $quotationData['customer_id'];
                $customerName = $quotationData['customer_name'];
                $customerEmail = $quotationData['customer_email'];
                if ($cusId != '') {
                    if ($customerName == '' && $customerEmail == '') {
                        if ($quotationData['customer_availability'] == 0) {
                            $customersControllerInit = new CustomersController();
                            $customerDetails = $customersControllerInit->getQuoteCustomerDetails($cusId, $getStoreDetails['store_id'], '');
                            if (!empty($customerDetails)) {
                                $customerName = $customerDetails['customer']['name'];
                                $customerEmail = $customerDetails['customer']['email'];
                            }
                        } else {
                            $customerName = 'No customer';
                            $customerEmail = 'No customer';
                        }
                    }
                } else {
                    $customerName = ' ';
                    $customerEmail = ' ';
                }
                
                $newQuoteArr = $quotationData;
                $newQuoteArr['customer_name'] = ($customerName != '') ? $customerName : $customerEmail;
                $newQuoteArr['edit_mode'] = ($customerName == 'No customer') ? false : true;
                // For due date
                $paymentInit = new QuotationPayment();
                $paymentData = $paymentInit->select('xe_id', 'payment_amount', 'payment_status')
                    ->where([
                        'quote_id' => $newQuoteArr['id'],
                        'payment_status' => 'paid'
                    ])->sum('payment_amount');
                $comPaidAmount = ($paymentData > 0) ? $paymentData : 0;
                $comPaidAmount = number_format($comPaidAmount, 2, '.', '');
                $newQuoteArr['due_amount'] = $newQuoteArr['total_amount'] - $comPaidAmount;
                $newQuoteArr['payment_status'] = 'pending';
                if ($newQuoteArr['due_amount'] == 0) {
                    $newQuoteArr['payment_status'] = 'paid';
                }
                //Calculate Paid payment percentage
                $percentage = 0;
                if ($newQuoteArr['total_amount'] != '' && $newQuoteArr['total_amount'] > 0) {
                    $percentage = ($comPaidAmount / $newQuoteArr['total_amount']) * 100;
                }
                $newQuoteArr['paid_percentage'] = $percentage;

                //Public Link
                $token = 'quote_id=' . $newQuoteArr['id'].'&store_id='.$getStoreDetails['store_id'];
                $token = base64_encode($token);
                $url = 'quotation/quotation-approval?token=' . $token;
                $newQuoteArr['public_url'] = API_URL . $url;
                //Get conversation unseen count
                $conversationCount = $conversationInit->where(['quote_id' => $newQuoteArr['id'], 'seen_flag' => '1'])->count();
                $newQuoteArr['conversation_unseen_count'] = $conversationCount;
                //Get Agent name
                if ($quotationData['agent_id'] != '') {
                    $agentId = $quotationData['agent_id'];
                    $agentArr = array_filter($agentListArr, function ($item) use ($agentId) {
                        return $item['id'] == $agentId;
                    });
                    $agentArr = $agentArr[array_keys($agentArr)[0]];
                }
                $newQuoteArr['agent_name'] = $quotationData['agent_id'] != '' ? $agentArr['name'] : '';
                $newQuoteArr['request_payment'] = ($newQuoteArr['request_payment'] == null) ? '0' : $newQuoteArr['request_payment'];
                //get order number 
                $orderNumber = '';
                if ($newQuoteArr['order_id'] != '' && $newQuoteArr['order_id'] > 0) {
                    //Get Order number
                    $ordersInit = new Orders;
                    $orders = $ordersInit->select('order_number')->where('order_id', $newQuoteArr['order_id']);
                    if ($orders->count() > 0) {
                        $orderData = json_clean_decode($orders->first(), true);
                        $orderNumber = $orderData['order_number'];
                    }
                }
                $newQuoteArr['order_number'] = ($orderNumber != '') ? $orderNumber : $newQuoteArr['order_id']; 
                unset(
                    $newQuoteArr['status_id']
                );
                //Filter by Payment Status
                if (isset($paymentStatus) && $paymentStatus != '') {
                    if ($newQuoteArr['payment_status'] == $paymentStatus) {
                        array_push($quotationRes, $newQuoteArr);
                    }

                } else {
                    array_push($quotationRes, $newQuoteArr);
                }
            }
            $jsonResponse = [
                'status' => 1,
                'records' => count($quotationRes),
                'total_records' => $getTotalPerFilters,
                'without_filter_count' => ($withoutFilterCount > 0) ? true : false,
                'data' => $quotationRes,
            ];
        }  else {
            $jsonResponse = [
                'status' => 1,
                'without_filter_count' => ($withoutFilterCount > 0) ? true : false,
                'message' => message('Quotation', 'not_found'),
                'data' => []
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Save Quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   24 Mar 2020
     * @return json response wheather data is saved or any error occured
     */
    public function saveQuotation($request, $response)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $getAllFormData = $quoteData = [];
        $allPostPutVars['store_id'] = $getStoreDetails['store_id'];
        $quotationInit = new Quotations();
        // Save Quotation Data
        if (isset($allPostPutVars['quote_data']) && $allPostPutVars['quote_data'] != "") {
            $getAllFormData = json_clean_decode($allPostPutVars['quote_data'], true);
            $eventType = isset($getAllFormData['event_type']) && $getAllFormData['event_type'] != '' ? $getAllFormData['event_type'] : 0;
            //Check for quote_id
            $quoteIdData = $quotationInit->where(
                [
                    'quote_id' => $getAllFormData['quote_id'],
                    'store_id' => $getStoreDetails['store_id'],
                ]);
            if ($quoteIdData->count() > 0) {
                $lastRecord = $quotationInit->select('quote_id')->latest()->first();
                if (!empty($lastRecord)) {
                    //Generate Quote Id
                    $quoteId = $this->generateQuoteId($request, $lastRecord->quote_id);
                    $getAllFormData['quote_id'] = $quoteId;
                }
            }

            $quoteData = $getAllFormData;
            unset($quoteData['tags'], $quoteData['items']);
            //Get customer data
            if (!empty($getAllFormData['customer_id']) && $getAllFormData['customer_id'] != '' && $getAllFormData['customer_id'] > 0) {
                $customersControllerInit = new CustomersController();
                $customerDetails = $customersControllerInit->getQuoteCustomerDetails($getAllFormData['customer_id'], $getStoreDetails['store_id'], '');
                if (!empty($customerDetails)) {
                    $customerName = $customerDetails['customer']['name'];
                    $customerEmail = $customerDetails['customer']['email'];
                }
                $quoteData += [
                    'customer_name' => (isset($customerName)) ? $customerName : '',
                    'customer_email' => (isset($customerEmail)) ? $customerEmail : '',
                ];
            }
            //Get Open status id
            $statusInit = new ProductionStatus();
            $getStatusData = $statusInit->select('xe_id')->where([
                'store_id' => $getStoreDetails['store_id'], 
                'slug' => 'open',
                'module_id' => 1
            ]);
            if ($getStatusData->count() > 0) {
               $getStatusDataArr = $getStatusData->first(); 
               $getStatusDataArr = json_clean_decode($getStatusDataArr, true);
            }
            $quoteData += [
                'store_id' => $getStoreDetails['store_id'],
                'status_id' => $getStatusDataArr['xe_id'],
            ];
            //If quotation is created by agent
            if ($quoteData['created_by'] == 'agent') {
                $quoteData['agent_id'] = $quoteData['created_by_id'];
            }
            //Check quotation is ready to send to customer or not
            if (!empty($getAllFormData['items']) 
                &&  !empty($getAllFormData['customer_id']) 
                && $getAllFormData['customer_id'] != '' 
                && $getAllFormData['customer_id'] > 0
                && $getAllFormData['title'] != ''
            ) {
                $quoteData += [
                    'is_ready_to_send' => 1,
                ];
            }
            $quotation = new Quotations($quoteData);
            if ($quotation->save()) {
                $quotationLastId = $quotation->xe_id;
                //Change the quotation setting flag value after quotation is created
                $this->changeSettingFlagValue($getStoreDetails['store_id'], 1, 'quote_id'); 
                // Save tags
                $tagArr = $getAllFormData['tags'];
                $this->saveQuoteTags($quotationLastId, $tagArr);

                // Save items
                $itemsArr = $getAllFormData['items'];
                $this->saveQuotationItems($quotationLastId, $itemsArr);
                //Adding to quote log
                $description = 'Quotation is created';
                if ($eventType == 2) {
                    //Save and send to customer
                    $this->sendToCustomer($request, $response, ['id' => $quotationLastId], 1);
                    $description = 'Quotation is created and sent to customer';
                } else if ($eventType == 4) {
                    //Save and download quotation
                    $description = 'Quotation is created and downloaded';
                }
                $logData = [
                    'quote_id' => $quotationLastId,
                    'description' => $description,
                    'user_type' => $quoteData['created_by'],
                    'user_id' => $quoteData['created_by_id'],
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                $this->addingQuotationLog($logData);
                $jsonResponse = [
                    'status' => 1,
                    'quote_id' => $quotationLastId,
                    'type' => ($eventType == 0) ? 'draft' : 'send', 
                    'message' => message('Quotation', 'saved'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Update Quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   26 Mar 2020
     * @return json response wheather data is updated or any error occured
     */
    public function updateQuotation($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $allPostPutVars = $updateData = json_clean_decode($allPostPutVars['quote_data'], true);
        $eventType = isset($$allPostPutVars['event_type']) && $$allPostPutVars['event_type'] != '' ? $$allPostPutVars['event_type'] : 0;

        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->where('xe_id', $quotationId);
            if ($getOldQuotation->count() > 0) {
                unset(
                    $updateData['tags'],
                    $updateData['items'],
                    $updateData['event_type'],
                    $updateData['user_type'],
                    $updateData['user_id'],
                    $updateData['created_by'],
                    $updateData['created_by_id'],
                    $updateData['quote_source']
                );
                if ($eventType == 2) {
                    //set flag as 0:send to customer
                    $updateData['draft_flag'] = "0";
                }
                //Get customer data 
                if (!empty($updateData['customer_id']) && $updateData['customer_id'] != '' && $updateData['customer_id'] > 0) {
                    $customersControllerInit = new CustomersController();
                    $customerDetails = $customersControllerInit->getQuoteCustomerDetails($updateData['customer_id'], $getStoreDetails['store_id'], '');
                    if (!empty($customerDetails)) {
                        $customerName = $customerDetails['customer']['name'];
                        $customerEmail = $customerDetails['customer']['email'];
                    }
                    $updateData += [
                        'customer_name' => (isset($customerName)) ? $customerName : '',
                        'customer_email' => (isset($customerEmail)) ? $customerEmail : ''
                    ];
                }
                //Check quotation is ready to send to customer or not
                if (!empty($allPostPutVars['items']) 
                    &&  !empty($updateData['customer_id']) 
                    && $updateData['customer_id'] != '' 
                    && $updateData['customer_id'] > 0
                    && $updateData['title'] != ''
                ) {
                    $updateData += [
                        'is_ready_to_send' => 1,
                    ];
                } else {
                    $updateData += [
                        'is_ready_to_send' => 0,
                    ];
                }
                $quotationInit->where('xe_id', $quotationId)
                    ->update($updateData);
                $description = 'Quotation is updated';
                // Save tags
                $tagArr = $allPostPutVars['tags'];
                $this->saveQuoteTags($quotationId, $tagArr);
                // Save items
                $itemsArr = $allPostPutVars['items'];
                $this->saveQuotationItems($quotationId, $itemsArr);

                if ($eventType == 2) {
                    //Save and send to customer
                    $this->sendToCustomer($request, $response, ['id' => $quotationId], 1);
                    $description = 'Quotation is updated and sent to customer';
                }
                //Check quotation is ready to send to customer or not
                if (!empty($itemsArr) 
                    &&  !empty($updateData['customer_id']) 
                    && $updateData['customer_id'] != '' 
                    && $updateData['customer_id'] > 0
                    && $updateData['title'] != ''
                ) {
                    $quotationInit->where('xe_id', $quotationId)
                    ->update([
                        'is_ready_to_send' => 1,
                    ]);
                } else {
                    $quotationInit->where('xe_id', $quotationId)
                    ->update([
                        'is_ready_to_send' => 0,
                    ]);
                }
                $logData = [
                    'quote_id' => $quotationId,
                    'description' => $description,
                    'user_type' => $allPostPutVars['user_type'],
                    'user_id' => $allPostPutVars['user_id'],
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                $this->addingQuotationLog($logData);
                $jsonResponse = [
                    'status' => 1,
                    'quote_id' => $quotationId,
                    'type' => ($eventType == 0) ? 'update' : 'send', 
                    'message' => message('Quotation', 'updated'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Save tags w.r.t Quotation
     *
     * @param $quotationId  Quotation ID
     * @param $tags         Multiple Tags
     *
     * @author debashrib@riaxe.com
     * @date   25th Mar 2020
     * @return boolean
     */
    private function saveQuoteTags($quotationId, $tags)
    {
        if (!empty($tags) && count($tags) > 0) {
            $tagsRelInit = new QuotationTagRelation();
            // Delete from relation table in case of update
            $quoteTags = $tagsRelInit->where('quote_id', $quotationId);
            $quoteTags->delete();
            $tagArr = [];
            foreach ($tags as $tagData) {
                $tagArr = [
                    'quote_id' => $quotationId,
                    'tag_id' => $tagData,
                ];
                $fileRes = $tagsRelInit->insert($tagArr);
            }
            return true;
        }
        return false;
    }

    /**
     * Save items w.r.t Quotation
     *
     * @param $quotationId  Quotation ID
     * @param $items        Multiple Items
     *
     * @author debashrib@riaxe.com
     * @date   25th Mar 2020
     * @return boolean
     */
    private function saveQuotationItems($quotationId, $items)
    {
        if ($quotationId > 0) {
            // Delete items and its details
            $this->deleteQuoteItems($quotationId);
            // Save Quotation Items
            if (!empty($items) && count($items) > 0) {
                $saveItems = [];
                // Delete items and its details
                $this->deleteQuoteItems($quotationId);
                $itemsArr = [];
                foreach ($items as $itemsData) {
                    $itemsArr = $itemsData;
                    $itemsArr['quote_id'] = $quotationId;
                    unset($itemsArr['product_variant']);
                    $itemsInit = new QuotationItems($itemsArr);
                    if ($itemsInit->save()) {
                        $itemLastId = $itemsInit->xe_id;
                        $productVariant = $itemsData['product_variant'];
                        if ($itemsData['artwork_type'] == 'uploaded_file') {
                            //Get Upload Designs and Data
                            $uploadDesignsArr = $itemsData['upload_designs'];
                            foreach ($uploadDesignsArr as $designs) {
                                $decorationAreasArr = $designs['decoration_area'];
                                foreach ($decorationAreasArr as $decorationAreas) {
                                    //upload Design
                                    $designFileName = $itemsData['product_id'] . '_' . $designs['side_id'] . '_' . $decorationAreas['decoration_area_id'] . '_'. $decorationAreas['decoration_settings_id'] . '_design';
                                    $previewFileName = $itemsData['product_id'] . '_' . $designs['side_id'] . '_' . $decorationAreas['decoration_area_id'] . '_'. $decorationAreas['decoration_settings_id'] . '_preview';
                                    $designFile = do_upload(
                                        $designFileName,
                                        path('abs', 'quotation') . $quotationId . '/' . $itemLastId . '/' . $designs['side_id'] . '/', [],
                                        'string'
                                    );
                                    $previewFile = do_upload(
                                        $previewFileName,
                                        path('abs', 'quotation') . $quotationId . '/' . $itemLastId . '/' . $designs['side_id'] . '/', [],
                                        'string'
                                    );
                                    //Design height and width
                                    $extraDataValue = '';
                                    if ($decorationAreas['design_width'] != '' 
                                        && $decorationAreas['design_height'] != '') {
                                        $extraData = [
                                            'design_height' => $decorationAreas['design_height'],
                                            'design_width' => $decorationAreas['design_width']
                                        ];
                                        $extraDataValue = json_encode($extraData);
                                    }
                                    $saveFileData = [
                                        'item_id' => $itemLastId,
                                        'side_id' => $designs['side_id'],
                                        'decoration_area_id' => $decorationAreas['decoration_area_id'],
                                        'print_method_id' => $decorationAreas['print_method_id'],
                                        'decoration_settings_id' => $decorationAreas['decoration_settings_id'],
                                        'file' => $designFile,
                                        'preview_file' => $previewFile,
                                        'extra_data' => $extraDataValue
                                    ];
                                    $itemFilesInit = new QuotationItemFiles($saveFileData);
                                    $fileRes = $itemFilesInit->save();
                                }
                            }
                        }
                        //Save product variant
                        foreach ($productVariant as $variants) {
                            $variantData = [
                                'item_id' => $itemLastId,
                                'variant_id' => $variants['variant_id'],
                                'quantity' => $variants['quantity'],
                                'unit_price' => $variants['unit_price'],
                                'attribute' => !empty($variants['attribute']) ? json_encode($variants['attribute']) : ''
                            ];
                            $variantsInit = new QuotationItemVariants($variantData);
                            $variantRes = $variantsInit->save();
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Delete items files
     *
     * @param $quotationId   Quotation Id
     *
     * @author debashrib@riaxe.com
     * @date   26th Mar 2020
     * @return boolean
     */
    private function deleteQuoteItems($quotationId)
    {
        $itemsInit = new QuotationItems();
        $itemFilesInit = new QuotationItemFiles();
        $variantsInit = new QuotationItemVariants();

        $quoteItems = $itemsInit->where('quote_id', $quotationId);
        if ($quoteItems->count() > 0) {
            $getQuoteItems = $quoteItems->get();
            foreach ($getQuoteItems as $itemDetails) {
                if ($itemDetails['artwork_type'] == 'uploaded_file') {
                    // Fetch Items File details
                    $itemFilesDetails = $itemFilesInit->where(
                        'item_id', $itemDetails['xe_id']
                    )->get();
                    if (!empty($itemFilesDetails)) {
                        $itemFilesInit->where('item_id', $itemDetails['xe_id'])->delete();
                    }
                }
                //Delete data from variants table
                $variantsInit->where('item_id', $itemDetails['xe_id'])->delete();
            }
            //Delete quotation folder
            $quoteFilesPath = path('abs', 'quotation') . $quotationId;
            if (file_exists($quoteFilesPath)) {
                $this->deleteQuoteFolder($quoteFilesPath);
            }
            $itemsInit->where('quote_id', $quotationId)->delete();
            return true;
        }
        return false;
    }

    /**
     * GET: Quotation Details
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     * @param $returnType     retun type
     *
     * @author debashrib@riaxe.com
     * @date   26 Mar 2020
     * @return json response
     */
    public function getQuotationDetails($request, $response, $args, $returnType = 0)
    {
        if (isset($_REQUEST['_token']) && !empty($_REQUEST['_token'])) {
             $getToken = $_REQUEST['_token'];
        } else {
            $getToken = '';
        }
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quotationInit = new Quotations();
            $getQuotations = $quotationInit
                ->join('production_status', 'quotations.status_id', '=', 'production_status.xe_id')
                ->select('quotations.xe_id', 'quotations.store_id', 'quotations.quote_id', 'quotations.customer_id', 'quotations.shipping_id', 'quotations.agent_id', 'quotations.created_by', 'quotations.created_by_id', 'quotations.quote_source', 'quotations.title', 'quotations.description', 'quotations.ship_by_date', 'quotations.exp_delivery_date', 'quotations.is_artwork', 'quotations.is_rush', 'quotations.rush_type', 'quotations.rush_amount', 'quotations.discount_type', 'quotations.discount_amount', 'quotations.shipping_type', 'quotations.shipping_amount', 'quotations.tax_amount', 'quotations.design_total', 'quotations.quote_total', 'quotations.note', 'quotations.status_id', 'quotations.draft_flag', 'quotations.reject_note', 'quotations.invoice_id', 'quotations.order_id', 'quotations.request_payment', 'quotations.request_date', 'quotations.created_at', 'quotations.updated_at', 'production_status.status_name as quote_status', 'production_status.color_code', 'quotations.customer_name', 'quotations.customer_email', 'quotations.customer_availability', 'quotations.order_id', 'quotations.is_ready_to_send', 'quotations.quotation_request_id')
                ->where(
                [
                    'quotations.store_id' => $getStoreDetails['store_id'],
                    'quotations.xe_id' => $quotationId,
                ]);
            $totalCounts = $getQuotations->count();
            if ($totalCounts > 0) {
                $notSetCustomerFlag = 0;
                $quotationData = $getQuotations->first();
                $quotationData = json_clean_decode($quotationData, true);
                // Get customer details
                $customerId = $quotationData['customer_id'];
                if ($customerId != '') {
                    $shippingId = ($quotationData['shipping_id'] != '') ? $quotationData['shipping_id'] : '';
                    $customersControllerInit = new CustomersController();
                    $customerDetails = $customersControllerInit->getQuoteCustomerDetails($customerId, $getStoreDetails['store_id'], $shippingId, true);
                    if (!empty($customerDetails)) {
                        $quotationData['customer'] = $customerDetails['customer'];
                        //update customer name and email
                        if (($quotationData['customer_name'] == '' || ($quotationData['customer_name'] != $customerDetails['customer']['name'])) || ($quotationData['customer_email'] == '' || $quotationData['customer_email'] != $customerDetails['customer']['email'])) {
                            $updateData = [
                                'customer_name' => $customerDetails['customer']['name'],
                                'customer_email' => $customerDetails['customer']['email'],
                                'customer_availability' => 0
                            ];
                            $quotationInit->where('xe_id', $quotationId)
                                ->update($updateData);
                        }
                    } else {
                        $quotationData['customer'] = []; 
                        $updateData = [
                                'customer_name' => '',
                                'customer_email' => '',
                                'customer_availability' => 1
                            ];
                            $quotationInit->where('xe_id', $quotationId)
                                ->update($updateData);
                    }
                } else {
                    $notSetCustomerFlag = 1;
                    $quotationData['customer'] = []; 
                }
                // Get Tag
                $quotationData['tags'] = $this->getQuoteTags($quotationId);
                //Get Agent name
                if ($quotationData['agent_id'] != '') {
                    $userInit = new User();
                    $userData = $userInit->select('name')->where('xe_id', '=', $quotationData['agent_id'])->first();
                }
                $quotationData['agent_name'] = $quotationData['agent_id'] != '' ? $userData['name'] : '';
                //Get Payments Details
                $paymentInit = new QuotationPayment();
                $paymentData =  $paymentInit->select('payment_amount')->where([
                    'quote_id' => $quotationId,
                    'payment_status' => 'paid'
                ]);
                $paymentDataArr = $paymentData->get()->toArray();
                $comAmountArr = array_column($paymentDataArr, 'payment_amount');
                $comPaidAmount = array_sum($comAmountArr);
                $comPaidAmount = number_format($comPaidAmount, 2, '.', '');
                $quotationData['due_amount'] = $quotationData['quote_total'] - $comPaidAmount;
                //Calculate Paid payment percentage
                $percentage = 0;
                if ($quotationData['quote_total'] != '' && $quotationData['quote_total'] > 0) {
                    $percentage = ($comPaidAmount / $quotationData['quote_total']) * 100;
                }
                $quotationData['paid_percentage'] = $percentage;
                $quotationData['request_payment'] = ($quotationData['request_payment'] == null) ? '0' : $quotationData['request_payment'];
                $orderNumber = '';
                if ($quotationData['order_id'] != '' && $quotationData['order_id'] > 0) {
                    //Get Order number
                    $ordersInit = new Orders;
                    $orders = $ordersInit->select('order_number')->where('order_id', $quotationData['order_id']);
                    if ($orders->count() > 0) {
                        $orderData = json_clean_decode($orders->first(), true);
                        $orderNumber = $orderData['order_number'];
                    }
                }
                $quotationData['order_number'] = ($orderNumber != '') ? $orderNumber : $quotationData['order_id'];
                //Public Link
                $token = 'quote_id=' . $quotationData['xe_id'].'&store_id='.$getStoreDetails['store_id'];
                $token = base64_encode($token);
                $url = 'quotation/quotation-approval?token=' . $token;
                $quotationData['public_url'] = API_URL . $url;
                //Get quotation request data
                $quotationRequestData = [];
                if ($quotationData['quote_source'] == 'tool' && $quotationData['quotation_request_id'] != '' && $quotationData['quotation_request_id'] > 0) {
                    $quotationRequestFormValuesInit = new QuotationRequestFormValues();
                    $requestFormData = $quotationRequestFormValuesInit->select('form_key', 'form_value', 'form_type')->where('quote_id', $quotationData['quote_id']);
                    if ($requestFormData->count() > 0) {
                        $quotationRequestData = json_clean_decode($requestFormData->get(), true);
                        $finalQuotationRequestData = [];
                        foreach ($quotationRequestData as $requestData) {
                            $tempQuotationRequestData = $requestData;
                            $tempQuotationRequestData['is_file_type'] = false;
                            if ($requestData['form_type'] == 'file') {
                                $fileArr = explode(', ', $requestData['form_value']);
                                if (count($fileArr) > 1) {
                                    foreach ($fileArr as $multipleFile) {
                                        $multipleFileArr[] = path('read', 'quotation_request') . $multipleFile;
                                    }
                                    $tempQuotationRequestData['form_value'] = $multipleFileArr;

                                } else {
                                    $tempQuotationRequestData['form_value'] = path('read', 'quotation_request') . $fileArr[0];
                                }
                                $tempQuotationRequestData['is_file_type'] = true;
                            }
                            unset($tempQuotationRequestData['form_type']);
                            array_push($finalQuotationRequestData, $tempQuotationRequestData);
                        }
                    }
                }
                $quotationData['quotation_request_data'] = $finalQuotationRequestData;
                if ($returnType == 1) {
                    return $quotationData;
                }

                if (!empty($quotationData)) {
                    if (!empty($customerDetails)) {
                        $jsonResponse = [
                            'status' => 1,
                            'data' => [
                                $quotationData,
                            ],
                        ];
                    } else {
                        if ($notSetCustomerFlag == 0) {
                            if ($quotationData['order_id'] != '') {
                                $jsonResponse = [
                                    'status' => 1,
                                    'data' => [
                                        $quotationData,
                                    ],
                                ];
                            } else {
                                $jsonResponse = [
                                    'status' => 0,
                                    'message' => 'Customer is deleted for this quotation'
                                ];
                            }
                        } else {
                            $jsonResponse = [
                                'status' => 1,
                                'data' => [
                                    $quotationData,
                                ],
                            ];
                        }
                    }
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Delete: Delete tag from the table if not
     * associate with quote
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   28 Mar 2020
     * @return Delete Json Status
     */
    public function deleteQuotationTag($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Tag', 'error'),
        ];
        if (!empty($args) && $args['id'] > 0) {
            $tagId = to_int($args['id']);
            $quoteTagRelInit = new QuotationTagRelation();
            $tags = $quoteTagRelInit->where('tag_id', $tagId);
            if ($tags->count() == 0) {
                $tagInit = new ProductionTags();
                $tag = $tagInit->find($tagId);
                if (isset($tag->xe_id) && $tag->xe_id != "" && $tag->xe_id > 0) {
                    $tag->delete();
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Quotation Tag', 'deleted'),
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Delete : Delete Status from the table if not
     * associate with quote
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   28 Mar 2020
     * @return Delete Json Status
     */
    public function deleteQuotationStatus($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Status', 'error'),
        ];
        if (!empty($args) && $args['id'] > 0) {
            $statusId = to_int($args['id']);
            $quotationInit = new Quotations();
            $statusInit = new ProductionStatus();
            $status = $statusInit->find($statusId);
            if (isset($status->xe_id) && $status->xe_id != "" && $status->xe_id > 0) {
                if ($status->delete()) {
                    //Get quotation id in which this status is assigned
                    $quote = $quotationInit->select('xe_id')->where('status_id', $statusId);
                    if ($quote->count() > 0) {
                        $quoteData = $quote->get();
                        $quoteData = json_clean_decode($quoteData, true);
                        $quoteIds = array_map(function ($item) {
                            return $item['xe_id'];
                        }, $quoteData);
                        //After delete change the quote status to default status
                        $quotationInit->whereIn('xe_id', $quoteIds)->update(['status_id' => 1]);
                    }
                }
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Quotation Status', 'deleted'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Sending pdf and email to customer
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     * @param $returnType     retun type
     *
     * @author debashrib@riaxe.com
     * @date   30 Mar 2020
     * @return boolean
     */
    public function sendToCustomer($request, $response, $args, $returnType = 0)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Mail', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->where('xe_id', $quotationId);
            $quotationDetails = $getOldQuotation->first()->toArray();
            //Get Payments Details
            $paymentInit = new QuotationPayment();
            $paymentData =  $paymentInit->select('payment_amount')->where([
                'quote_id' => $quotationId,
                'payment_status' => 'paid'
            ])->sum('payment_amount');
            $comPaidAmount = ($paymentData > 0) ? $paymentData : 0;
            $comPaidAmount = number_format($comPaidAmount, 2, '.', '');
            $quotationDetails['due_amount'] = $quotationDetails['quote_total'] - $comPaidAmount;
            $oldStatusId = $quotationDetails['status_id'];
            //Bind email template
            $templateData = $this->bindEmailTemplate('quote_sent', $quotationDetails, $getStoreDetails);
            $templateData = $templateData[0];
            
            $mailResponse = $this->sendQuotationEmail(
                $templateData, 
                [
                    'name' => $quotationDetails['customer_name'], 
                    'email' => $quotationDetails['customer_email']
                ], 
                [$dir], 
                $getStoreDetails);
            if (!empty($mailResponse['status']) && $mailResponse['status'] == 1) {
                //Update quotation draft status
                $statusInit = new ProductionStatus();
                $approvedStatusId = 0;
                $sentStatusId = 0;
                //get approved status id
                $getApprovedStatusData = $statusInit->select('xe_id')->where([
                    'store_id' => $getStoreDetails['store_id'], 
                    'slug' => 'approved',
                    'module_id' => 1
                ]);
                if ($getApprovedStatusData->count() > 0) {
                   $getApprovedStatusDataArr = $getApprovedStatusData->first(); 
                   $getApprovedStatusDataArr = json_clean_decode($getApprovedStatusDataArr, true);
                   $approvedStatusId = $getApprovedStatusDataArr['xe_id'];
                }
                //get sent status id
                $getSentStatusData = $statusInit->select('xe_id')->where([
                    'store_id' => $getStoreDetails['store_id'], 
                    'slug' => 'sent',
                    'module_id' => 1
                ]);
                if ($getSentStatusData->count() > 0) {
                   $getSentStatusDataArr = $getSentStatusData->first(); 
                   $getSentStatusDataArr = json_clean_decode($getSentStatusDataArr, true);
                   $sentStatusId = $getSentStatusDataArr['xe_id'];
                }
                if ($oldStatusId == $approvedStatusId
                    && ($quotationDetails['quote_total'] != $quotationDetails['due_amount'])
                ) {
                    $updateArr = [
                        'draft_flag' => '0',
                    ];
                } else {
                    $updateArr = [
                        'draft_flag' => '0',
                        'status_id' => $sentStatusId, //3: Sent
                    ];
                }
                $quotationInit->where('xe_id', $quotationId)
                    ->update($updateArr);
                if ($returnType == 0) {
                    //Adding to quote log
                    $logData = [
                        'quote_id' => $quotationId,
                        'description' => 'Quotation sent to customer',
                        'user_type' => $allPostPutVars['user_type'],
                        'user_id' => $allPostPutVars['user_id'],
                        'created_date' => date_time(
                            'today', [], 'string'
                        )
                    ];
                    $this->addingQuotationLog($logData);
                }
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Quotation Mail', 'done'),
                ];

            }
        }
        if ($returnType == 1) {
            return true;
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Quotation Listing for grid view
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   03 Apr 2020
     * @return json response
     */
    public function getQuotationCardView($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $quotationInit = new Quotations();
        $statusInit = new ProductionStatus();
        $conversationInit = new QuotationConversations();
        $itemsInit = new QuotationItems();
        $loginId = $request->getQueryParam('login_id');
        //Get agent data
        $userInit = new User();
        $agentData = $userInit->select('xe_id as id', 'name')->where('store_id', $getStoreDetails['store_id']);
        $agentListArr = json_clean_decode($agentData->get(), true);

        $quoteStatusData = $statusInit
            ->select('xe_id as status_id', 'status_name', 'color_code', 'slug as type')
            ->where(
                [
                    'store_id' => $getStoreDetails['store_id'],
                    'module_id' => 1,
                ])->orderBy('sort_order', 'ASC');
        if ($quoteStatusData->count() > 0) {
            $statusDataArr = $quoteStatusData->get();
            $statusDataRes = [];
            foreach ($statusDataArr as $statusData) {
                $newStatusArr = $statusData;
                $quoteDataRes = [];

                //Get total and pending amounts of quotation status wise
                $amountArr = $this->getTotalPaymentAmt($statusData['status_id']);
                $newStatusArr['total_amount'] = $amountArr['total_amount'];
                $newStatusArr['pending_amount'] = $amountArr['pending_amount'];
                //Get quotation
                $quotationDetails = [];
                $quotationData = $quotationInit
                    ->select('xe_id', 'quote_id', 'customer_id',
                        'agent_id', 'quote_source', 'title',
                        'quote_total as total_amount', 'created_at', 'invoice_id', 'request_payment', 'request_date', 'customer_name', 'customer_email', 'customer_availability', 'order_id', 'is_ready_to_send')
                    ->where([
                        'store_id' => $getStoreDetails['store_id'],
                        'status_id' => $statusData['status_id'],
                    ]);
                if (isset($loginId) && $loginId != "") {
                    $quotationData->where('agent_id', $loginId);
                }
                $quotationData->orderBy('created_at', 'DESC');
                if ($quotationData->count() > 0) {
                    $quotationDetails = $quotationData->get();
                    foreach ($quotationDetails as $quoteData) {
                        //Get customer details
                        $cusId = $quoteData['customer_id'];
                        $customerName = $quoteData['customer_name'];
                        $customerEmail = $quoteData['customer_email'];
                        if ($cusId != '') {
                            if ($customerName == '' && $customerEmail == '') {
                                if ($quoteData['customer_availability'] == 0) {
                                    $customersControllerInit = new CustomersController();
                                    $customerDetails = $customersControllerInit->getQuoteCustomerDetails($cusId, $getStoreDetails['store_id'], '');
                                    if (!empty($customerDetails)) {
                                        $customerName = $customerDetails['customer']['name'];
                                        $customerEmail = $customerDetails['customer']['email'];
                                    }
                                } else {
                                    $customerName = 'No customer';
                                    $customerEmail = 'No customer';
                                }
                            }
                        } else {
                            $customerName = ' ';
                            $customerEmail = ' ';
                        }
                        $newQuoteArr = $quoteData;
                        $newQuoteArr['customer_name'] = ($customerName != '') ? $customerName : $customerEmail;
                        $newQuoteArr['edit_mode'] = ($customerName == 'No customer') ? false : true;
                        //Get Agent name
                        if ($quoteData['agent_id'] != '') {
                            $agentId = $quoteData['agent_id'];
                            $agentArr = array_filter($agentListArr, function ($item) use ($agentId) {
                                return $item['id'] == $agentId;
                            });
                            $agentArr = $agentArr[array_keys($agentArr)[0]];
                        }
                        $newQuoteArr['agent_name'] = $quoteData['agent_id'] != '' ? $agentArr['name'] : '';
                        //Get Tags
                        $newQuoteArr['tags'] = $this->getQuoteTags($quoteData['xe_id']);
                        // For due date
                        $paymentInit = new QuotationPayment();
                        $paymentDataArr = $paymentInit->select('xe_id', 'payment_amount', 'payment_status')
                            ->where([
                                'quote_id' => $newQuoteArr['xe_id'],
                                'payment_status' => 'paid'
                            ])->sum('payment_amount');
                        $comPaidAmount = ($paymentDataArr > 0) ? $paymentDataArr : 0;
                        $comPaidAmount = number_format($comPaidAmount, 2, '.', '');
                        $newQuoteArr['due_amount'] = $newQuoteArr['total_amount'] - $comPaidAmount;
                        //Calculate Paid payment percentage
                        $percentage = 0;
                        if ($newQuoteArr['total_amount'] != '' && $newQuoteArr['total_amount'] > 0) {
                            $percentage = ($comPaidAmount / $newQuoteArr['total_amount']) * 100;
                        }
                        $newQuoteArr['paid_percentage'] = $percentage;
                        //Get conversation unseen count
                        $conversationCount = $conversationInit->where(['quote_id' => $newQuoteArr['xe_id'], 'seen_flag' => '1'])->count();
                        $newQuoteArr['conversation_unseen_count'] = $conversationCount;
                        //Total item quantity of quotation
                        $getItemQuantityArr = $itemsInit->select('quantity')->where('quote_id', $newQuoteArr['xe_id'])->get()->toArray();
                        $quantity = array_column($getItemQuantityArr, 'quantity');
                        $totalQuantity = array_sum($quantity);
                        $newQuoteArr['total_quantity'] = $totalQuantity;
                        //Public Link
                        $token = 'quote_id=' . $newQuoteArr['xe_id'].'&store_id='.$getStoreDetails['store_id'];
                        $token = base64_encode($token);
                        $url = 'quotation/quotation-approval?token=' . $token;
                        $newQuoteArr['public_url'] = API_URL.$url;
                        //Get Order number
                        $orderNumber = '';
                        if ($newQuoteArr['order_id'] != '' && $newQuoteArr['order_id'] > 0) {
                            //Get Order number
                            $ordersInit = new Orders;
                            $orders = $ordersInit->select('order_number')->where('order_id', $newQuoteArr['order_id']);
                            if ($orders->count() > 0) {
                                $orderData = json_clean_decode($orders->first(), true);
                                $orderNumber = $orderData['order_number'];
                            }
                        }
                        $newQuoteArr['order_number'] = ($orderNumber != '') ? $orderNumber : $newQuoteArr['order_id'];
                        array_push($quoteDataRes, $newQuoteArr);
                    }
                }
                $newStatusArr['quotations'] = $quoteDataRes;
                array_push($statusDataRes, $newStatusArr);
            }
            $jsonResponse = [
                'status' => 1,
                'data' => $statusDataRes,
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET Quotation total and pending amount status wise
     *
     * @param $statusId  Quotation status Id
     *
     * @author debashrib@riaxe.com
     * @date   03 Apr 2019
     * @return json response
     */
    private function getTotalPaymentAmt($statusId)
    {
        $amountArr = [
            'total_amount' => 0,
            'pending_amount' => 0,
        ];
        if (!empty($statusId)) {
            $quotationInit = new Quotations();
            $totalAmount = 0;
            $pendingAmount = 0;
            $quotation = $quotationInit->select('xe_id', 'quote_total')
                ->where('status_id', $statusId);
            if ($quotation->count() > 0) {
                $quotationData = $quotation->get();
                $quotationData = json_clean_decode($quotationData, true);
                $quotationTotalArr = array_column($quotationData, 'quote_total');
                $quotationIdArr = array_column($quotationData, 'xe_id');
                $totalAmount = array_sum($quotationTotalArr);
                if (!empty($quotationIdArr)) {
                    $paymentInit = new QuotationPayment();
                    $payment = $paymentInit->select('payment_amount')
                        ->whereIn('quote_id', $quotationIdArr)
                        ->where('payment_status', 'paid')->sum('payment_amount');
                    $totalPaidAmount = ($payment > 0) ? $payment : 0;
                }
                $pendingAmount = $totalAmount - $totalPaidAmount;
            }
            $amountArr = [
                'total_amount' => $totalAmount,
                'pending_amount' => $pendingAmount,
            ];
        }
        return $amountArr;
    }

    /**
     * Tags w.r.t quotation
     *
     * @param $quotationId  Quotation Id
     *
     * @author debashrib@riaxe.com
     * @date   04 Apr 2020
     * @return array
     */
    private function getQuoteTags($quotationId)
    {
        if ($quotationId != '') {
            $productionTagInit = new ProductionTags();
            $quoteTagRelInit = new QuotationTagRelation();
            $getTags = $quoteTagRelInit->where('quote_id', $quotationId);
            $tagRes = [];
            if ($getTags->count() > 0) {
                $tagDataArr = $getTags->get();
                foreach ($tagDataArr as $tagData) {
                    $newTagArr = [];
                    $tagName = $productionTagInit
                        ->where('xe_id', $tagData['tag_id'])->first();
                    $newTagArr = [
                        'id' => $tagName['xe_id'],
                        'name' => $tagName['name'],
                    ];
                    array_push($tagRes, $newTagArr);
                }
            }
        }
        return $tagRes;
    }

    /**
     * POST: Assign agent to quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   04 Apr 2020
     * @return json response wheather data is updated or any error occured
     */
    public function assignAgent($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Assign Agent', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->select('xe_id', 'agent_id')->where('xe_id', $quotationId);
            if ($getOldQuotation->count() > 0) {
                $getOldQuotationArr = json_clean_decode($getOldQuotation->first(), true);
                $previousAgentId = $getOldQuotationArr['agent_id']; 
                //print_r($getOldQuotationArr);exit;
                $userType = (isset($allPostPutVars['user_type']) && $allPostPutVars['user_type'] != '') ? $allPostPutVars['user_type'] : 'admin';
                $userId = (isset($allPostPutVars['user_id']) && $allPostPutVars['user_id'] != '') ? $allPostPutVars['user_id'] : 1;
                unset($allPostPutVars['user_type'], $allPostPutVars['user_id']);
                $quotationInit->where('xe_id', $quotationId)
                    ->update($allPostPutVars);
                //Adding to quote log
                $userName = '';
                $previousUserName = '';
                $userInit = new User();
                $agent = $userInit->select('xe_id', 'name')->where('xe_id', $allPostPutVars['agent_id']);
                if ($agent->count() > 0) {
                    $agentDetails = json_clean_decode($agent->first(), true);
                    $userName = $agentDetails['name'];
                }
                if ($previousAgentId == '') {
                   $description = 'Agent '.$userName.' is assigned';
                } else {
                    $previousAgent = $userInit->select('xe_id', 'name')->where('xe_id', $previousAgentId);
                    if ($previousAgent->count() > 0) {
                        $previousAgentDetails = json_clean_decode($previousAgent->first(), true);
                        $previousUserName = $previousAgentDetails['name'];
                    }
                    $description = 'Agent '.$userName.' is assigned and unassigned '.$previousUserName;
                }
                $logData = [
                    'quote_id' => $quotationId,
                    'description' => $description,
                    'user_type' => $userType,
                    'user_id' => $userId,
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                
                $this->addingQuotationLog($logData);
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Assign Agent', 'updated'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Adding data to quotation log
     *
     * @param $logData  Log data array
     *
     * @author debashrib@riaxe.com
     * @date   06 Apr 2020
     * @return boolean
     */
    public function addingQuotationLog($logData)
    {
        if (!empty($logData)) {
            $quotationLog = new QuotationLog($logData);
            if ($quotationLog->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * GET : Quotation log
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   03 Apr 2019
     * @return json response
     */
    public function getQuotationLog($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'data' => [],
            'message' => message('Quotation Log', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        $quotationInit = new Quotations();
        if (!empty($args['id'])) {
            $quoteId = to_int($args['id']);
            $quotation = $quotationInit->where(
                [
                    'xe_id' => $quoteId,
                    'store_id' => $getStoreDetails['store_id'],
                ]);
            $noteRes = [];
            $logRes = [];
            if ($quotation->count() > 0) {
                $quotationLogInit = new QuotationLog();
                $logs = $quotationLogInit->where('quote_id', $quoteId)
                    ->orderBy('created_date', 'DESC');

                if ($logs->count() > 0) {
                    $logArr = $logs->get();
                    foreach ($logArr as $logData) {
                        $newLogArr = $logData;
                        $userName = $newLogArr['user_type'];
                        if ($newLogArr['user_type'] == 'customer') {
                            // Get customer name
                            if ($newLogArr['user_id'] != '') {
                                $customersControllerInit = new CustomersController();
                                $customerDetails = $customersControllerInit->getQuoteCustomerDetails($newLogArr['user_id'], $getStoreDetails['store_id'], '');
                                if (!empty($customerDetails)) {
                                    $userName = ($customerDetails['customer']['name'] != '') ? $customerDetails['customer']['name'] : $customerDetails['customer']['email'];
                                }
                            }
                        } else if ($newLogArr['user_type'] == 'agent') {
                            //Get agent name
                            $userInit = new User();
                            $agent = $userInit->select('xe_id', 'name')->where('xe_id', $newLogArr['user_id']);
                            if ($agent->count() > 0) {
                                $agentDetails = json_clean_decode($agent->first(), true);
                                $userName = $agentDetails['name'];
                            }
                        }
                        $description = $newLogArr['description'] . ' by ' . $userName;
                        if ($newLogArr['description'] == 'Agent is assigned') {
                            $description = 'Agent ' . $userName . ' is assigned by Admin';
                        }
                        $newLogArr['description'] = $description;
                        $newLogArr['log_type'] = 'quote_log';
                        $newLogArr['created_at'] = $newLogArr['created_date'];
                        unset($newLogArr['created_date']);
                        array_push($logRes, $newLogArr);
                    }

                }
                //Get internal note data
                $internalNoteInit = new QuotationInternalNote();
                $internalNotes = $internalNoteInit->with('files')->where('quote_id', $quoteId)
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
                        $newNoteArr['description'] = $newNoteArr['note'] . ' by ' . $userName;
                        $newNoteArr['log_type'] = 'internal_note';
                        $newNoteArr['created_at'] = $newNoteArr['created_date'];
                        unset(
                            $newNoteArr['note'],
                            $newNoteArr['seen_flag'],
                            $newNoteArr['created_date']
                        );
                        array_push($noteRes, $newNoteArr);
                    }
                }
                $totalQuotationLogs = array_merge($logRes, $noteRes);
                // Sort the array by Created Date and time
                usort($totalQuotationLogs, 'date_compare');
                if (is_array($totalQuotationLogs) && !empty($totalQuotationLogs) > 0) {
                    $jsonResponse = [
                        'status' => 1,
                        'data' => $totalQuotationLogs,
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Assign agent to quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   07 Apr 2020
     * @return json response wheather data is updated or any error occured
     */
    public function changeQuotationStatus($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Status Change', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $updateData = $request->getParsedBody();
        //Get status data
        $updateType = $updateData['type'];
        $statusId = 0;
        $statusInit = new ProductionStatus();
        $getStatusData = $statusInit->select('xe_id')->where([
            'store_id' => $getStoreDetails['store_id'], 
            'slug' => $updateType,
            'module_id' => 1
        ]);
        if ($getStatusData->count() > 0) {
           $getStatusDataArr = $getStatusData->first(); 
           $getStatusDataArr = json_clean_decode($getStatusDataArr, true);
           $statusId = $getStatusDataArr['xe_id'];
        }
        unset($updateData['user_id'], $updateData['user_type'], $updateData['type']);
        $updateData['status_id'] = $statusId;
        $oldStatus = $newStatus = '';
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quotationInit = new Quotations();
            $newStatusId = $statusId;
            $getOldQuotation = $quotationInit
                ->join('production_status', 'quotations.status_id', '=', 'production_status.xe_id')
                ->select('quotations.xe_id', 'quotations.store_id', 'quotations.quote_id', 'quotations.customer_id', 'quotations.shipping_id', 'quotations.agent_id', 'quotations.created_by', 'quotations.created_by_id', 'quotations.quote_source', 'quotations.title', 'quotations.description', 'quotations.ship_by_date', 'quotations.exp_delivery_date', 'quotations.is_artwork', 'quotations.is_rush', 'quotations.rush_type', 'quotations.rush_amount', 'quotations.discount_type', 'quotations.discount_amount', 'quotations.shipping_type', 'quotations.shipping_amount', 'quotations.tax_amount', 'quotations.design_total', 'quotations.quote_total', 'quotations.note', 'quotations.status_id', 'quotations.draft_flag', 'quotations.reject_note', 'quotations.invoice_id', 'quotations.order_id', 'quotations.request_payment', 'quotations.request_date', 'quotations.created_at', 'quotations.updated_at', 'production_status.status_name as quote_status', 'production_status.color_code', 'quotations.customer_name', 'quotations.customer_email', 'quotations.customer_availability', DB::raw("(SELECT status_name FROM production_status WHERE xe_id = $newStatusId) as new_status"))
                ->where(
                [
                    'quotations.xe_id' => $quotationId,
                ]);
            if ($getOldQuotation->count() > 0) {
                $quoteData = $getOldQuotation->first()->toArray();
                $oldStatusId = $quoteData['status_id'];
                $oldStatus = $quoteData['quote_status'];
                $newStatus = $quoteData['new_status'];
                $updateData['draft_flag'] = '0';
                $quotationInit->where('xe_id', $quotationId)->update($updateData);
            }
            //Adding to quote log
            $logData = [
                'quote_id' => $quotationId,
                'description' => 'Status is changed to ' . $newStatus . ' from ' . $oldStatus,
                'user_type' => $allPostPutVars['user_type'],
                'user_id' => $allPostPutVars['user_id'],
                'created_date' => date_time(
                    'today', [], 'string'
                )
            ];
            $this->addingQuotationLog($logData);
            //If status is approved

            if ($updateType == 'approved') {
                //Get Payments Details
                $paymentInit = new QuotationPayment();
                $paymentData =  $paymentInit->select('payment_amount')->where([
                    'quote_id' => $quotationId,
                    'payment_status' => 'paid'
                ])->sum('payment_amount');
                $comPaidAmount = ($paymentData > 0) ? $paymentData : 0;
                $comPaidAmount = number_format($comPaidAmount, 2, '.', '');
                $quoteData['due_amount'] = $quoteData['quote_total'] - $comPaidAmount;
                //Bind email template
                $templateData = $this->bindEmailTemplate('quote_approval', $quoteData, $getStoreDetails);
                $templateData = $templateData[0];
                //Send Email
                $mailResponse = $this->sendQuotationEmail(
                    $templateData, 
                    [
                        'name' => $quoteData['customer_name'], 
                        'email' => $quoteData['customer_email']
                    ], 
                    [], 
                    $getStoreDetails);
            }
            $jsonResponse = [
                'status' => 1,
                'message' => message('Status Change', 'updated'),
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * DELETE: Delete a quotation along with tags, items and files
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   08 Apr 2019
     * @return json response wheather data is deleted or not
     */
    public function deleteQuotation($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation', 'error'),
        ];
        $success = 0;
        $quotationDelIds = $args['id'];

        $getDeleteIdsToArray = json_clean_decode($quotationDelIds, true);
        $totalCount = count($getDeleteIdsToArray);

        if (!empty($getDeleteIdsToArray)
            && count($getDeleteIdsToArray) > 0
        ) {
            $quotationInit = new Quotations();
            if ($quotationInit->whereIn('xe_id', $getDeleteIdsToArray)->count() > 0) {
                foreach ($getDeleteIdsToArray as $quotationId) {
                    $quotationData = $quotationInit->select('quote_id', 'quote_source', 'quotation_request_id')->where('xe_id', $quotationId);
                    $quotationDataArr = json_clean_decode($quotationData->first(), true); 
                    //Delete quotation tags
                    $tagRelationInit = new QuotationTagRelation();
                    $tagRelDelete = $tagRelationInit->where(
                        'quote_id', $quotationId
                    )->delete();
                    //Delete quotation items
                    $this->deleteQuoteItems($quotationId);
                    //Delete quotation internal note and files
                    $internalNoteInit = new QuotationInternalNote();
                    $notes = $internalNoteInit->where('quote_id', $quotationId);
                    if ($notes->count() > 0) {
                        $noteDataArr = $notes->get();
                        $noteFilesInit = new QuotationInternalNoteFiles();
                        foreach ($noteDataArr as $noteData) {
                            $noteFiles = $noteFilesInit->where('note_id', $noteData['xe_id']);
                            if ($noteFiles->count() > 0) {
                                $noteFileArr = $noteFiles->get();
                                foreach ($noteFileArr as $noteFiles) {
                                    $this->deleteOldFile(
                                        "quote_internal_note_files", "file", [
                                            'xe_id' => $noteFiles['xe_id'],
                                        ], path('abs', 'quotation') . 'internal-note/'
                                    );
                                }
                            }
                            //Delete note
                            $noteDelete = $internalNoteInit->where(
                                'xe_id', $noteData['xe_id']
                            )->delete();
                        }

                    }
                    //Delete quotation payment
                    $paymentInit = new QuotationPayment();
                    $PaymentDelete = $paymentInit->where(
                        'quote_id', $quotationId
                    )->delete();
                    //Delete quotation log
                    $quotationLogInit = new QuotationLog();
                    $logDelete = $quotationLogInit->where(
                        'quote_id', $quotationId
                    )->delete();
                    //delete quotation request data
                    if ($quotationDataArr['quote_source'] == 'tool') {
                        $quotationRequestDetailsInit = new QuotationRequestDetails();
                        $quotationRequestDetailsInit->where('quote_id', $quotationDataArr['quote_id'])->delete();
                        $quotationRequestFormValuesInit = new QuotationRequestFormValues();
                        $quotationRequestFormValuesInit->where('quote_id', $quotationDataArr['quote_id'])->delete();
                    }
                    $quotationInit->where('xe_id', $quotationId)->delete();
                    $success++;
                }
                $jsonResponse = [
                    'status' => 1,
                    'message' => $success . ' out of ' . $totalCount .
                    ' Quotation(s) deleted successfully',
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Download Quotation pdf in local system
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   09 Apr 2019
     * @return boolean
     */
    public function downloadQuotation($request, $response, $args)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Download', 'not_found'),
        ];
        $result = false;
        if (!empty($args['id']) && $args['id'] > 0) {
            $quotationId = $args['id'];
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->where('xe_id', $quotationId);
            if ($getOldQuotation->count() > 0) {
                $quotationDetails = $getOldQuotation->first()->toArray();
                $dir = path('abs', 'quotation') . $quotationId . '/'.$quotationDetails['quote_id'].'.pdf';
                //if (!file_exists($dir)) { 
                    $this->createQuotationPdf($request, $response, $args, 1);
                //}
                if (file_exists($dir)) {
                    //Download file in local system
                    if (file_download($dir, 0)) {
                        $result = true;
                        $serverStatusCode = OPERATION_OKAY;
                        $jsonResponse = [
                            'status' => 1,
                        ];
                    }
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Create Quotation pdf
     *
     * @param $quotationId     Quotation Id
     * @param $quotationDetails    Quotation Details
     * @param $itemList    Quotation Items List
     * @param $getStoreDetails    Store id
     *
     * @author debashrib@riaxe.com
     * @date   15 Apr 2019
     * @return pdf file path
     */
    public function createQuotationPdf($request, $response, $args, $returnType = 0)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Pdf', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $quotationId = to_int($args['id']);
        if ($quotationId > 0 && $quotationId != '') {
            //Get quotation details
            $quotationDetails = $this->getQuotationDetails($request, $response, $args, 1);
            $itemList = $this->getQuoteItems($request, $response, $args, 1);

            $filePath = path('abs', 'quotation') . $quotationId . '/';
            $fileDir = $filePath.$quotationDetails['quote_id'].'.pdf';
            if (file_exists($fileDir)) {
                delete_file($fileDir);
            }

            //Get currency from global setting
            $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
            $currency = $globalSettingData['currency']['unicode_character'];
            //Get email setting data for sending email
            $emailSettingData = $this->getProductionSetting($request, $response, ['module_id' => 1, 'return_type' => 1]);
            $emailSettingData = $emailSettingData['data'];
            $customerName = ($quotationDetails['customer']['name'] != '') ? $quotationDetails['customer']['name'] : $quotationDetails['customer']['email'];
            $billingAddressArr = $quotationDetails['customer']['billing_address'];
            $billingAddress = $billingAddressArr['address_1'] != '' ? $billingAddressArr['address_1'] . ', ' . $billingAddressArr['address_2'] . '<br/>' . $billingAddressArr['country'] . ', ' . $billingAddressArr['state'] . ',<br/>' . $billingAddressArr['city'] . '-' . $billingAddressArr['postcode'] : '';

            $shippingId = $quotationDetails['shipping_id'];
            $finalShippingArr = array_filter($quotationDetails['customer']['shipping_address'], function ($item) use ($shippingId) {
                return $item['id'] == $shippingId;
            });
            $finalShippingArr = $finalShippingArr[array_keys($finalShippingArr)[0]];
            $shippingAddress = $finalShippingArr['address_1'] != '' ? $finalShippingArr['address_1'] . ', ' . $finalShippingArr['address_2'] . '<br/>' . $finalShippingArr['country'] . ', ' . $finalShippingArr['state'] . ',<br/>' . $finalShippingArr['city'] . '-' . $finalShippingArr['postcode'] : '';

            $html = '<!doctype html>
            <html lang="en-US">

            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            </head>
            <body style="margin: 0; padding: 0;">
                <div style="margin: 0px; padding: 0px; background: #fff; -webkit-box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); position: relative; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif;">

                <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%;">
              <tr>
                <td style="vertical-align: top;">
                  <h3 class="title mb-3">Quotation</h3>
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Quotation Number</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . $quotationDetails['quote_id'] . '</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Shipping Date</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . date("dS F, Y", strtotime($quotationDetails['ship_by_date'])) . '</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Delivery Date</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>' . date("dS F, Y", strtotime($quotationDetails['exp_delivery_date'])) . '</strong>
                      </td>
                    </tr>
                  </table>
                </td>
                <td style="vertical-align: top; text-align: right; font-size: 14px;">';
            if ($emailSettingData['company_logo'] != '') {
                $html .= '<figure style="margin: 0 0 0 auto; width: 120px;">
                    <img alt="logo" src="' . $emailSettingData['company_logo'] . '" style="width: 100%;" />
                  </figure>';
            }
            $html .= '<address style="font-size: 14px; line-height: 22px;">
                    ' . $emailSettingData['address'] . ',<br/>
                    ' . $emailSettingData['country'] . ',' . $emailSettingData['state'] . ',' . $emailSettingData['city'] . '-' . $emailSettingData['zip_code'] . ',<br/>
                    ' . $emailSettingData['sender_email'] . '<br/>
                    ' . $emailSettingData['phone_number'] . '
                  </address>
                </td>
              </tr>
            </table>
            <hr style="margin-bottom: 30px; margin-top: 30px; width: 100%; border:1px solid #e3e3e3" />
            <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%; margin-bottom: 30px;">
              <tr>';
            if (!empty($quotationDetails['customer'])) {
                $html .= '<td style="vertical-align: top;">
                  <small>Quotation for</small>
                  <h4 style="margin: 0 0 10px 0;">' . $customerName . '</h4>
                  <address style="font-size: 14px; line-height: 22px;">
                    ' . $quotationDetails['customer']['email'] . '<br/>
                    ' . $quotationDetails['customer']['phone'] . '
                  </address>
                </td>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  <small>Billing Address</small>
                  <address>
                    ' . $billingAddress . '
                  </address>
                </td>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  <small>Shipping Address</small>
                  <address>
                    ' . $shippingAddress . '
                  </address>
                </td>';
            }
                $html .= '<td style="vertical-align: top; text-align: right;">
                  <small>Balance Due (<span style="font-family: DejaVu Sans;">'.$currency.';</span>)</small>
                  <h1 style="margin: 7px 0;"><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $quotationDetails['due_amount'] . '</h1>
                </td>
              </tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; line-height: 24px;">
              <thead>
                <tr>
                  <th colspan="2" style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Product Name
                  </th>
                  <th style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Product Details
                  </th>
                  <th style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Unit Price
                  </th>
                  <th style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0;">
                    Total Qty
                  </th>
                  <th style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left;">
                    Design Price
                  </th>
                  <th style="font-weight: 400; border:1px solid #e3e3e3; padding: 0.75rem; text-align: left;">
                    Total Price
                  </th>
                </tr>
              </thead>
              <tbody>';
            $subtotal = 0;
            foreach ($itemList as $itemKey => $items) {
                $productName = ($items['product_name'] != '') ? $items['product_name'] : 'N/A';
                $slNo = $itemKey + 1;
                $backgroundColor = (($itemKey % 2) == 0) ? 'background-color: rgba(0, 0, 0, 0.05);' : '';
                $subtotal = $subtotal + $items['unit_total'];
                if ($items['product_availability'] == true) {
                    $html .= '<tr>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $backgroundColor . '">' . $slNo . '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; ' . $backgroundColor . '" >' . $productName . '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; ' . $backgroundColor . '">';

                    foreach ($items['product_variant'] as $key => $variants) {
                        if (!empty($variants['attribute'])) {
                            $html .= '<span>(';
                            foreach ($variants['attribute'] as $attribute) {
                                $html .= $attribute['name'] . ' / ';
                            }
                            $html .= $variants['quantity'] . ')</span> <br/>';
                        } else {
                            $html .= 'Simple Product';
                        }
                    }

                    $html .= '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; ' . $backgroundColor . '">';
                    foreach ($items['product_variant'] as $key => $variants) {
                        $html .= '<span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $variants['unit_price'] . '<br/>';
                    }

                    $html .= '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; ' . $backgroundColor . '">' . $items['quantity'] . '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-right:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $backgroundColor . '"><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $items['design_cost'] . '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-right:1px solid #e3e3e3; padding: 0.75rem; text-align: left; ' . $backgroundColor . '"><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $items['unit_total'] . '</td>
                    </tr>';
                } else {
                    $html .= '<tr>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; background-color: rgb(247 89 64 / 5%);">' . $slNo . '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; background-color: rgb(247 89 64 / 5%);" >No product availabe</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; background-color: rgb(247 89 64 / 5%);">The product might be deleted or remove from store.</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; background-color: rgb(247 89 64 / 5%);"></td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; background-color: rgb(247 89 64 / 5%);">' . $items['quantity'] . '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-right:1px solid #e3e3e3; padding: 0.75rem; text-align: left; background-color: rgb(247 89 64 / 5%);"><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $items['design_cost'] . '</td>
                      <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-right:1px solid #e3e3e3; padding: 0.75rem; text-align: left; background-color: rgb(247 89 64 / 5%);"><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $items['unit_total'] . '</td>
                      </tr>';
                }
            }
            $display = ($quotationDetails['note'] == '') ? 'display: none;' : '';
            $html .= '</tbody>
            </table>
            <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 30px;">
              <tr>
                <td>
                  <h4 style="' . $display . '">Note to Recipient / Terms & Conditions</h4>
                  <p style="font-size: 14px; line-height: 22px; ' . $display . '">
                    ' . $quotationDetails['note'] . '
                  </p>
                </td>
                <td style="width: 50%; text-align: right;">
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-right:0; border-bottom:0;
                            text-align: right;">Subtotal</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-bottom:0"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $subtotal . '</strong></td>
                    </tr>';
            if ($quotationDetails['discount_type'] == 'percentage') {
                $discountAmount = '<span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $subtotal * ($quotationDetails['discount_amount'] / 100);
                $showDisPercent = ' (' . $quotationDetails['discount_amount'] . '%)';
            } else {
                $discountAmount = '<span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $quotationDetails['discount_amount'];
                $showDisPercent = '';
            }
            $html .= '<tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-right:0; border-bottom:0;">Discount(' . ucfirst($quotationDetails['discount_type']) . ')</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-bottom:0;"><strong>' . $discountAmount . $showDisPercent . '</strong></td>
                    </tr>
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-right:0; border-bottom:0;">Shipping(' . ucfirst($quotationDetails['shipping_type']) . ')</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3;text-align: right; border-bottom:0;"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span>'. $quotationDetails['shipping_amount'] . '</strong></td>
                    </tr>';
            if ($quotationDetails['is_rush'] == '1') {
                if ($quotationDetails['rush_type'] == 'percentage') {
                    $rush = $subtotal * ($quotationDetails['rush_amount'] / 100);
                    $rushAmount = number_format($rush, 2, '.', '');
                    $showPercent = ' (' . $quotationDetails['rush_amount'] . '%)';
                } else {
                    $rushAmount = $quotationDetails['rush_amount'];
                    $showPercent = '';
                }
                $html .= '<tr>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; border-right:0; text-align: right;">Rush(' . ucfirst($quotationDetails['rush_type']) . ')</td>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; text-align: right;"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $rushAmount . $showPercent . '</strong></td>
                        </tr>';
            }
            $taxAmount = $subtotal * ($quotationDetails['tax_amount'] / 100);
            $taxAmount = number_format($taxAmount, 2, '.', '');
            $html .= '<tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; border-right:0; text-align: right;">Tax(%)</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; text-align: right;"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $taxAmount . ' (' . $quotationDetails['tax_amount'] . '%)</strong></td>
                    </tr>
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-right:0; text-align: right;">Total</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; font-size: 20px;">
                        <strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $quotationDetails['quote_total'] . '</strong>
                      </td>
                    </tr>
                  </table>
                  <small>
                    (All prices are shown in <span style="font-family: DejaVu Sans;">'.$currency.';</span>)
                  </small>
                </td>
              </tr>
            </table>';
            foreach ($itemList as $itemKey => $quoteItems) {
                $orderItemNo = $itemKey + 1;
                if ($quoteItems['product_availability'] == true) {
                    $html .= '<table width="100%" cellspacing="0" cellpadding="0">
                  <tr>
                    <td colspan="2">
                      <h3 class="title mb-4">Order item #' . $orderItemNo . '</h3>
                    </td>
                  </tr>
                  <tr>
                    <td style="vertical-align: top;" width="40%">
                      <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                          <td style="padding: 5px;">
                            <figure style="width: 370px; margin: 0;">
                              <img src="' . $quoteItems['product_store_image']['src'] . '" style="width: 150px;" alt=""/>
                            </figure>
                          </td>
                        </tr>
                      </table>
                    </td>
                    <td style="vertical-align: top; padding-left: 40px;" width="60%">
                      <h3 style="font-size: 18px; margin-bottom: 20px;">
                        ' . $quoteItems['product_name'] . '
                      </h3>';
                    if ($quoteItems['product_id'] == $quoteItems['variant_id']) {
                        $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="border: 0px solid #e3e3e3; font-size: 14px;">
                        <tr>
                          <td style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">Product Type</td>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                            Simple Product
                          </td>
                        </tr>
                        <tr>
                          <td style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">Quantity</td>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                            ' . $quoteItems['product_variant'][0]['quantity'] . '
                          </td>
                        </tr>
                        <tr>
                          <td style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">Price</td>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                            <span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $quoteItems['product_variant'][0]['unit_price'] . '
                          </td>
                        </tr>
                      </table>';
                    } else {
                        $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="border: 0px solid #e3e3e3; font-size: 14px;">';
                        foreach ($quoteItems['product_variant'] as $variantKey => $variantsData) {
                            $varSlNo = $variantKey + 1;
                            if ($variantKey == 0) {
                                $html .= '
                            <tr>
                              <th style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">#</th>
                              ';
                                foreach ($variantsData['attribute'] as $attKey => $attribute) {
                                    $html .= '<th style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">' . $attKey . '</th>';
                                }
                                $html .= '
                              <th style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">Quantity</th>
                              <th style="border: 1px solid #d3d3d3; background-color: #eee; text-align: center;">Price</th>
                            </tr>';
                            }
                            $html .= '<tr>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                            ' . $varSlNo . '
                          </td>';
                            foreach ($variantsData['attribute'] as $attribute) {
                                $html .= '<td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                            ' . $attribute['name'] . '
                          </td>';
                            }
                            $html .= '<td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                            ' . $variantsData['quantity'] . '
                          </td>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3;">
                            <span style="font-family: DejaVu Sans;">'.$currency.';</span> ' . $variantsData['unit_price'] . '
                          </td>
                        </tr>';
                        }
                        $html .= '</table>';
                    }
                    $html .= '</td>
                  </tr>
                  <tr>
                    <td colspan="2">';
                    if (!empty($quoteItems['upload_designs'])) {
                      $html .= '<h4 style="font-size: 16px; margin-top: 20px;">
                        Artwork used
                      </h4>
                      ';
                    }
                    foreach ($quoteItems['upload_designs'] as $uploadDesigns) {
                        if (!empty($uploadDesigns['decoration_area'])) {
                            $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="border: 0px solid #e3e3e3; font-size: 14px;">
                        <tr>
                          <td style="padding: 12px; border: 1px solid #e3e3e3;">
                            ';
                            foreach ($uploadDesigns['decoration_area'] as $decorationArea) {
                                $html .= '<table width="100%" cellspacing="0" cellpadding="0">
                              <tr>
                                <td style="vertical-align: top;">
                                <h3>Preview</h3>
                                  <figure style="margin: 0; width: 150px; height: 150px;" >
                                    <img src="' . $decorationArea['upload_preview_url'] . '" alt="" style="width: 100%;" />
                                  </figure>
                                </td>
                                 <td style="vertical-align: top;">
                                 <h3>Design</h3>
                                  <figure style="margin: 0; width: 150px; height: 150px;">';
                                $html .= "<img src='" . stripslashes($decorationArea['upload_design_url']) . "' alt='' style='width: 100%;' />";
                                $html .= '</figure>
                                </td>
                                <td style="vertical-align: top;">
                                  <table>
                                    <tr>
                                      <td style="padding: 0 0px 4px 20px;">Product side</td>
                                      <td style="padding: 0 0px 4px 20px;"><strong>' . $uploadDesigns['side_name'] . '</strong></td>
                                    </tr>
                                    <tr>
                                      <td style="padding: 0 0px 4px 20px;">Print method</td>
                                      <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationArea['print_methods'] . '</strong></td>
                                    </tr>
                                     <tr>
                                      <td style="padding: 0 0px 4px 20px;">Name</td>
                                      <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationArea['decoration_area'] . '</strong></td>
                                    </tr>
                                    <tr>
                                      <td style="padding: 0 0px 4px 20px;">Decoration area name</td>
                                      <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationArea['print_area_name'] . '</strong></td>
                                    </tr>
                                     <tr>
                                      <td style="padding: 0 0px 4px 20px;">Height</td>
                                      <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationArea['height'] . ' ' . $decorationArea['measurement_unit'] . '</strong></td>
                                    </tr>
                                    <tr>
                                      <td style="padding: 0 0px 4px 20px;">Width</td>
                                      <td style="padding: 0 0px 4px 20px;"><strong>' . $decorationArea['width'] . ' ' . $decorationArea['measurement_unit'] . '</strong></td>
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
                } else {
                    $html .= '<table width="100%" cellspacing="0" cellpadding="0">
                  <tr>
                    <td colspan="2">
                      <h3 class="title mb-4">Order item #' . $orderItemNo . '</h3>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                      <h3 class="title mb-4">No product available. The product might be deleted or removed from the store.</h3>
                    </td>
                  </tr>
                  </table>';
                }
            }

            $html .= '</div>
        </body></html>';
            
            $fileNames = create_pdf($html, $filePath, $quotationDetails['quote_id'], "portrait");
            $dir = $filePath . $fileNames;
            if (file_exists($dir)) {
                if ($returnType == 1) {
                    return true;
                }
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Quotation Pdf', 'done'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Agent list with quotation count
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   13 May 2020
     * @return json response wheather data is added or any error occured
     */
    public function agentListWithQuote($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 0,
            'message' => message('Agent', 'error'),
        ];
        $userId = $request->getQueryParam('user_id');
        $userType = $request->getQueryParam('user_type');
        $view = $request->getQueryParam('view');
        $userInit = new User();
        $agentData = $userInit->select('xe_id as id', 'name', 'email');
        if (isset($view) && $view == 'list') {
            $agentData->whereNotIn('xe_id', [$userId]);
        }
        $agentData->where('store_id', $getStoreDetails['store_id']);
        
        $agentList = json_clean_decode($agentData->get(), true);
        $quotationInit = new Quotations();
        if (!empty($agentList)) {
            $agentResult = [];
            foreach ($agentList as $agents) {
                $newAgentList = $agents;
                //Get quotation count
                $quoteData = $quotationInit->where('agent_id', $agents['id']);
                $quoteCount = $quoteData->count();
                $newAgentList['quote_count'] = $quoteCount;
                array_push($agentResult, $newAgentList);
            }
            $jsonResponse = [
                'status' => 1,
                'data' => $agentResult,
            ];
        } else {
            $jsonResponse = [
                'status' => 1,
                'message' => message('Agent', 'not_found'),
                'data' => []
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET : Items w.r.t quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     * @param $returnType     Response return type
     *
     * @author debashrib@riaxe.com
     * @date   14 May 2020
     * @return json
     */
    public function getQuoteItems($request, $response, $args, $returnType = 0)
    {
        if (isset($_REQUEST['_token']) && !empty($_REQUEST['_token'])) {
             $getToken = $_REQUEST['_token'];
        } else {
            $getToken = '';
        }
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Items', 'error'),
        ];
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quoteItemsInit = new QuotationItems();
            $quoteItemFilesInit = new QuotationItemFiles();
            $variantsInit = new QuotationItemVariants();
            $getItems = $quoteItemsInit->where('quote_id', $quotationId);
            $itemDetails = [];
            //Get all measurment units
            $appUnitInit = new AppUnit();
            $initAppUnit = $appUnitInit->select('xe_id', 'name');
            $measurementUnitArr = $initAppUnit->get();
            $measurementUnitArr = json_clean_decode($measurementUnitArr, true);
            //Get All Print Profile
            $printAreaInit = new PrintArea();
            $printArea = $printAreaInit->select('xe_id', 'name', 'width', 'height');
            $allPrintMethodesArr = $printArea->get();
            $allPrintMethodesArr = json_clean_decode($allPrintMethodesArr, true);
            $orderDwonloadObj = new OrderDownloadController();
            if ($getItems->count() > 0) {
                $getItemsData = $getItems->get()->toArray();
                foreach ($getItemsData as $itemsData) {
                    $newItemData = $itemsData;
                    $decorationsInit = new ProductDecorationsController();
                    $productDecorationArr = $decorationsInit->getDecorationDetail($request, $response, ['product_id' => $itemsData['product_id']], 1); 
                    $productDecorationArr = $productDecorationArr['data'];
                    if (isset($productDecorationArr['price']) && $productDecorationArr['price'] != '' && $productDecorationArr['price'] > 0) {
                        $firstImageData = $productDecorationArr['store_images'][0];
                        if ($itemsData['is_decorated_product'] == 1) {
                            $productsControllerInit = new ProductsController();
                            $imageData = $productsControllerInit->getAssocProductImages($itemsData['product_id']);
                            $firstImageData = [
                                'src' => $imageData[0],
                                'thumbnail' => $imageData[0]
                            ];
                        }
                        $newItemData['product_store_image'] = $firstImageData;
                        $newItemData['product_name'] = $productDecorationArr['product_name'];
                        $newItemData['product_sku'] = $productDecorationArr['sku'];
                        $newItemData['product_price'] = $productDecorationArr['price'];
                        //add custom product price for custom vda product
                        if ($itemsData['is_custom_size'] == 1) {
                            $newItemData['custom_product_price'] = $itemsData['unit_total'] - $itemsData['design_cost'];
                        }
                        $newItemData['variant_id'] = $productDecorationArr['variant_id'];
                        $newItemData['upload_designs'] = [];
                        if ($itemsData['artwork_type'] == 'uploaded_file') {
                            $getFiles = $quoteItemFilesInit->where('item_id', $itemsData['xe_id']);
                            //Get product side data
                            $allSideArr = $productDecorationArr['sides'];
                            $type =  gettype($allSideArr);
                            if ($type == 'object') {
                                $allSideArr = json_clean_decode($allSideArr, true);
                            }
                            //Get measurement unit name
                            $measurementUnitId = $productDecorationArr['scale_unit_id'];
                            $unitArr = array_filter($measurementUnitArr, function ($item) use ($measurementUnitId) {
                                return $item['xe_id'] == $measurementUnitId;
                            });
                            $unitArr = $unitArr[array_keys($unitArr)[0]];
                            $measurementUnitName = $unitArr['name'];

                            $uploadDesigns = [];
                            if ($getFiles->count() > 0) {
                                $getSideDataArr = $getFiles->select('side_id')->groupBy('side_id')->get()->toArray();
                                foreach ($getSideDataArr as $sideKey => $sideData) {
                                    $newSideKey = $sideKey+1;
                                    $tempSideData = $sideData;
                                    $sideId = $tempSideData['side_id'];
                                    $sideArr = array_filter($allSideArr, function ($item) use ($sideId) {
                                        return $item['xe_id'] == $sideId;
                                    });
                                    
                                    $sideArr = $sideArr[array_keys($sideArr)[0]];
                                    $tempSideData['side_name'] = ($itemsData['is_variable_decoration'] == 0) ? $sideArr['side_name'] : 'Side '.$newSideKey;
                                    $tempSideData['thumbnail'] = ($itemsData['is_variable_decoration'] == 0) ? $sideArr['image']['thumbnail'] : $productDecorationArr['store_images'][0]['thumbnail'];
                                    $decorationDataArr = $quoteItemFilesInit->where(['item_id' => $itemsData['xe_id'], 'side_id' => $sideData['side_id']])->get();
                                    $decorationArea = [];
                                    foreach ($decorationDataArr as $decorationData) {
                                        $tempDecorationArea = $decorationData;
                                        $allDecoArr = $sideArr['product_decoration_setting'];
                                        $decorationAreaId = $decorationData['decoration_area_id'];
                                        $decorationSettingId = $decorationData['decoration_settings_id'];
                                        //print_r($allDecoArr);exit;
                                        $decoArr = array_filter($allDecoArr, function ($item) use ($decorationSettingId) {
                                            return $item['xe_id'] == $decorationSettingId;
                                        });
                                        $decoArr = $decoArr[array_keys($decoArr)[0]];
                                        $tempDecorationArea['decoration_settings_id'] = $decorationSettingId;
                                        $tempDecorationArea['decoration_area'] = ($itemsData['is_variable_decoration'] == 0) ? $decoArr['name'] : 'Side '.$newSideKey;
                                        $allPrintProfileArr = $decoArr['print_profile_decoration_settings'];
                                        //get print method
                                        $printMethodId = $decorationData['print_method_id'];
                                        $finalPrintMethod = [];
                                        foreach ($allPrintProfileArr as $printProfileArr) {
                                            $tempPrintMethods = $printProfileArr['print_profile'];
                                            $printProfileArr = array_filter($tempPrintMethods, function ($item) use ($printMethodId) {
                                                return $item['xe_id'] == $printMethodId;
                                            });
                                            $printProfileArr = $printProfileArr[array_keys($printProfileArr)[0]];
                                            if ($printProfileArr['xe_id'] == $printMethodId) {
                                                array_push($finalPrintMethod, $printProfileArr);
                                            }
                                        }
                                        if ($itemsData['is_variable_decoration'] == 1) {
                                            $getVdaPrintMethod =  DB::table("print_profiles")->select('name')->where('xe_id', '=', $printMethodId);
                                            $vdpPrintMethodData = $getVdaPrintMethod->first();
                                            $finalPrintMethod[0]['name'] = $vdpPrintMethodData->name;
                                        }
                                        $tempDecorationArea['print_methods'] = $finalPrintMethod[0]['name'];
                                        $printMethodsArr = array_filter($allPrintMethodesArr, function ($item) use ($decorationAreaId) {
                                            return $item['xe_id'] == $decorationAreaId;
                                        });
                                        $printMethodsArr = $printMethodsArr[array_keys($printMethodsArr)[0]];
                                        //Design hight and width
                                        $extraDataValue = $decorationData['extra_data'];
                                        $extraDataArr = json_clean_decode($extraDataValue, true);

                                        $tempDecorationArea['height'] = $printMethodsArr['height'];
                                        $tempDecorationArea['width'] = $printMethodsArr['width'];
                                        $tempDecorationArea['measurement_unit'] = $measurementUnitName;
                                        $tempDecorationArea['print_area_name'] = $printMethodsArr['name'];
                                        $tempDecorationArea['upload_design_url'] = path('read', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/' . $sideId . '/' . $decorationData['file'];
                                        $tempDecorationArea['upload_preview_url'] = path('read', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/' . $sideId . '/' . $decorationData['preview_file'];
                                        $tempDecorationArea['design_height'] = (!empty($extraDataArr) && isset($extraDataArr['design_height']) && $extraDataArr['design_height'] != '') ? $extraDataArr['design_height'] : '';
                                        $tempDecorationArea['design_width'] = (!empty($extraDataArr) && isset($extraDataArr['design_width']) && $extraDataArr['design_width'] != '') ? $extraDataArr['design_width'] : '';
                                        unset(
                                            $tempDecorationArea['xe_id'],
                                            $tempDecorationArea['item_id'],
                                            $tempDecorationArea['side_id'],
                                            $tempDecorationArea['extra_data']
                                        );
                                        array_push($decorationArea, $tempDecorationArea);
                                    }
                                    $tempSideData['decoration_area'] = $decorationArea;
                                    array_push($uploadDesigns, $tempSideData);
                                }
                                $newItemData['upload_designs'] = $uploadDesigns;
                            }
                        } else if ($itemsData['artwork_type'] == 'design_tool') {
                            $uploadDesigns = [];
                            $customDesignId = $itemsData['custom_design_id'];
                            $deisgnStatePath = path('abs', 'design_state') . 'artworks';
                            $orderJsonPath = $deisgnStatePath . '/' . $customDesignId . ".json";
                            //Json file path of pre-deco product
                            if (!file_exists($orderJsonPath)) {
                                $orderJsonPath = path('abs', 'design_state') . 'predecorators' . '/' . $customDesignId . ".json";
                            }
                            if (file_exists($orderJsonPath)) {
                                $orderJson = read_file($orderJsonPath);
                                $jsonContent = json_clean_decode($orderJson, true);
                                $designProductDataArr = $jsonContent['design_product_data'][0]['design_urls'];
                                $getSideDataArr = $jsonContent['sides'];
                                foreach ($getSideDataArr as $sideKey => $sideData) {
                                    $tempSideData['side_id'] = 0;
                                    $tempSideData['side_name'] = $sideData['side_name'];
                                    $tempSideData['thumbnail'] = $sideData['url'];
                                    $decorationArea = [];
                                    //
                                    $svgFileName = $customDesignId . '_' . $sideKey . ".svg";
                                    $pngFileName = $customDesignId . '_' . $sideKey . ".png";
                                    $path = path('abs', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/';
                                    $svgPath = $path . $svgFileName;
                                    $pngPath = $path . $pngFileName;
                                    if (!file_exists($path)) {
                                        create_directory($path);
                                    }
                                    if (!file_exists($orderJson)) {
                                        $parameter = array(
                                            'key' => $sideKey, 'ref_id' => $customDesignId,
                                            'item_path' => '',
                                            'svg_preview_path' => $svgPath,
                                            'value' => $sideData,
                                            'is_design' => 1,
                                        );
                                        $orderDwonloadObj->createSideSvgByOrderId($parameter);
                                        if (file_exists($svgPath)) {
                                            $orderDwonloadObj->svgConvertToPng($pngPath, $svgPath);
                                        }
                                    }
                                    foreach ($sideData['print_area'] as $printArea) {
                                        $newPrintAreaArr = [
                                            'decoration_area_id' => 0,
                                            'print_method_id' => $printArea['print_method_id'],
                                            'file' => '',
                                            'preview_file' => '',
                                            'decoration_area' => $printArea['name'],
                                            'print_methods' => $printArea['print_method_name'],
                                            'height' => $printArea['print_area']['height'],
                                            'width' => $printArea['print_area']['width'],
                                            'measurement_unit' => $sideData['print_unit'],
                                            'print_area_name' => $printArea['print_area']['name'],
                                            'upload_design_url' => path('read', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/' . $pngFileName,
                                            'upload_preview_url' => $designProductDataArr[$sideKey],
                                            'custom_size_dimension' => ($itemsData['is_variable_decoration'] == 1 && $itemsData['is_custom_size'] == 1) ? $itemsData['custom_size_dimension'] : '',
                                        ];
                                        array_push($decorationArea, $newPrintAreaArr);
                                    }
                                    $tempSideData['decoration_area'] = $decorationArea;
                                    array_push($uploadDesigns, $tempSideData);
                                }
                            }
                            $newItemData['upload_designs'] = $uploadDesigns;
                        }
                        //Get variants
                        $getVariants = $variantsInit->where('item_id', $itemsData['xe_id']);
                        $variants = [];
                        if ($getVariants->count() > 0) {
                            $getVariantData = $getVariants->get();
                            foreach ($getVariantData as $variantData) {
                                $newVariant = $variantData;
                                if (($variantData['attribute'] == '' || $variantData['attribute'] == 'null') && $variantData['variant_id'] != 0) {
                                    //get variant attribute
                                    if ($getToken != '') {
                                        $getAtt = call_curl(
                                            [],
                                            'product-details/' . $itemsData['product_id'] . '?variant_id=' . $variantData['variant_id'].'&_token='.$getToken, 'GET'
                                        );
                                    } else {
                                        $getAtt = call_curl(
                                            [],
                                            'product-details/' . $itemsData['product_id'] . '?variant_id=' . $variantData['variant_id'], 'GET'
                                        );
                                    }
                                    $getAtt = $getAtt['data'];
                                    $newVariant['attribute'] = !empty($getAtt['attributes']) ? $getAtt['attributes'] : [];
                                    unset($newVariant['item_id']);
                                    array_push($variants, $newVariant);
                                    //save attribute into database
                                    $updateAtt = [
                                        'attribute' => json_encode($getAtt['attributes'])
                                    ];
                                    $variantsInit = new QuotationItemVariants();
                                    $variantsInit->where('xe_id', $variantData['xe_id'])
                                        ->update($updateAtt);
                                } else {
                                    $newVariant['attribute'] = json_clean_decode($variantData['attribute'], true);
                                    unset($variantData['item_id']);
                                    array_push($variants, $newVariant);
                                }
                            }
                        }
                        $newItemData['product_variant'] = $variants;
                        $newItemData['product_availability'] = true;
                    } else {
                        $newItemData['product_availability'] = false;
                    }
                    array_push($itemDetails, $newItemData);
                }
                if (!empty($itemDetails)) {
                    if ($returnType == 1) {
                        return $itemDetails;
                    } else {
                        $jsonResponse = [
                            'status' => 1,
                            'data' => $itemDetails,
                        ];
                    }
                }
            } else {
                $jsonResponse = [
                    'status' => 1,
                    'data' => [],
                ];
            }
            
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: make quotation duplicate
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   14 May 2020
     * @return array
     */
    public function duplicateQuotation($request, $response)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Duplicate', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        if ($allPostPutVars['quote_id'] != '' && $allPostPutVars['quote_id'] > 0) {
            $quotationId = $allPostPutVars['quote_id'];
            $quotationInit = new Quotations();
            $tagInit = new QuotationTagRelation();
            $itemsInit = new QuotationItems();
            $variantsInit = new QuotationItemVariants();
            $itemFilesInit = new QuotationItemFiles();
            $quoteData = $quotationInit->where([
                'xe_id' => $quotationId,
                'store_id' => $getStoreDetails['store_id'],
            ]);
            if ($quoteData->count() > 0) {
                $quoteDetails = $quoteData->first();
                $quoteDetails = $oldQuotation = json_clean_decode($quoteDetails, true);
                unset(
                    $quoteDetails['xe_id'],
                    $quoteDetails['agent_id'],
                    $quoteDetails['created_at'],
                    $quoteDetails['updated_at']
                );
                $quoteDetails['quote_id'] = $this->generateQuoteId($request, $quoteDetails['quote_id']);
                //Get Open status id
                $statusInit = new ProductionStatus();
                $getStatusData = $statusInit->select('xe_id')->where([
                    'store_id' => $getStoreDetails['store_id'], 
                    'slug' => 'open',
                    'module_id' => 1
                ]);
                if ($getStatusData->count() > 0) {
                   $getStatusDataArr = $getStatusData->first(); 
                   $getStatusDataArr = json_clean_decode($getStatusDataArr, true);
                }
                $quoteDetails['status_id'] = $getStatusDataArr['xe_id'];
                $quoteDetails['draft_flag'] = '1';
                $quoteDetails['created_by'] = $allPostPutVars['user_type'];
                $quoteDetails['created_by_id'] = $allPostPutVars['user_id'];
                if ($allPostPutVars['user_type'] == 'agent') {
                    $quoteDetails['agent_id'] = $allPostPutVars['user_id'];
                }
                $quotation = new Quotations($quoteDetails);
                if ($quotation->save()) {
                    $quotationLastId = $quotation->xe_id;
                    //Get Tags
                    $tagData = $tagInit->select('tag_id')->where('quote_id', $quotationId);
                    if ($tagData->count()) {
                        $tagDetailsArr = $tagData->get()->toArray();
                        $tagDetailsArr = array_column($tagDetailsArr, 'tag_id');
                        //Save Tags
                        $this->saveQuoteTags($quotationLastId, $tagDetailsArr);
                    }
                    //get Items
                    $itemsData = $itemsInit->where('quote_id', $quotationId);
                    if ($itemsData->count() > 0) {
                        $itemsDetails = $itemsData->get()->toArray();
                        foreach ($itemsDetails as $items) {
                            $saveItems = $items;
                            unset($saveItems['xe_id']);
                            $saveItems['quote_id'] = $quotationLastId;
                            $itemsObj = new QuotationItems($saveItems);
                            if ($itemsObj->save()) {
                                $lastItemId = $itemsObj->xe_id;
                                //Get Files
                                $itemFilesData = $itemFilesInit->where('item_id', $items['xe_id']);
                                if ($itemFilesData->count() > 0) {
                                    $itemsFilesDetails = $itemFilesData->get()->toArray();
                                    foreach ($itemsFilesDetails as $files) {
                                        $files['item_id'] = $lastItemId;
                                        $filesObj = new QuotationItemFiles($files);
                                        //Copy files to new quotation
                                        $souFileDir = path('abs', 'quotation') . $quotationId . '/' . $items['xe_id'] . '/' . $files['side_id'] . '/' . $files['file'];
                                        $souPreviewDir = path('abs', 'quotation') . $quotationId . '/' . $items['xe_id'] . '/' . $files['side_id'] . '/' . $files['preview_file'];
                                        $disFileDir = path('abs', 'quotation') . $quotationLastId . '/' . $lastItemId . '/' . $files['side_id'];
                                        if (!file_exists($disFileDir)) {
                                            create_directory($disFileDir);
                                        }
                                        if (copy($souFileDir, $disFileDir . '/' . $files['file'])) {
                                            copy($souPreviewDir, $disFileDir . '/' . $files['preview_file']);
                                            $filesObj->save();
                                        }
                                    }
                                }
                                //Get Variant
                                $variantData = $variantsInit->where('item_id', $items['xe_id']);
                                if ($variantData->count() > 0) {
                                    $variantArr = $variantData->get()->toArray();
                                    foreach ($variantArr as $variants) {
                                        $variants['item_id'] = $lastItemId;
                                        $variantObj = new QuotationItemVariants($variants);
                                        $variantObj->save();
                                    }
                                }
                            }
                        }
                    }
                }
                //Added to quote log
                //Duplicate Log
                $logData = [
                    'quote_id' => $quotationId,
                    'description' => "Quote is duplicated to #" . $quoteDetails['quote_id'],
                    'user_type' => $allPostPutVars['user_type'],
                    'user_id' => $allPostPutVars['user_id'],
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                $this->addingQuotationLog($logData);
                //New quote log
                $logData = [
                    'quote_id' => $quotationLastId,
                    'description' => "Quotation is created from #" . $oldQuotation['quote_id'],
                    'user_type' => $allPostPutVars['user_type'],
                    'user_id' => $allPostPutVars['user_id'],
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                $this->addingQuotationLog($logData);
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Quotation Duplicate', 'clone'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Reject Quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   18 May 2020
     * @return json response wheather data is updated or any error occured
     */
    public function rejectQuotation($request, $response)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Reject', 'error'),
        ];
        $allPostPutVars = $updateData = $request->getParsedBody();
        if ($allPostPutVars['quote_id'] != '' && $allPostPutVars['quote_id'] > 0) {
            $quotationId = $allPostPutVars['quote_id'];
            $quotationInit = new Quotations();
            $quoteData = $quotationInit->where([
                'xe_id' => $quotationId,
                'store_id' => $getStoreDetails['store_id'],
            ]);
            if ($quoteData->count() > 0) {
                //Update status to reject
                //get rejected status id
                $rejectedStatusId = 0;
                if ($updateData['type'] == 'rejected') {
                    $statusInit = new ProductionStatus();
                    $getRejectedStatusData = $statusInit->select('xe_id')->where([
                        'store_id' => $getStoreDetails['store_id'], 
                        'slug' => 'rejected',
                        'module_id' => 1
                    ]);
                    if ($getRejectedStatusData->count() > 0) {
                       $getRejectedStatusDataArr = $getRejectedStatusData->first(); 
                       $getRejectedStatusDataArr = json_clean_decode($getRejectedStatusDataArr, true);
                       $rejectedStatusId = $getRejectedStatusDataArr['xe_id'];
                    }
                }
                
                unset(
                    $updateData['quote_id'],
                    $updateData['user_id'],
                    $updateData['user_type'],
                    $updateData['type']
                );
                $updateData['status_id'] = $rejectedStatusId;
                $quotationInit->where('xe_id', $quotationId)
                    ->update($updateData);
                //New quote log
                $logData = [
                    'quote_id' => $quotationId,
                    'description' => "Quotation is rejected",
                    'user_type' => $allPostPutVars['user_type'],
                    'user_id' => $allPostPutVars['user_id'],
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                $this->addingQuotationLog($logData);
                //Get Quotation details
                $quotationDetails = $this->getQuotationDetails($request, $response, ['id' => $quotationId], 1);
                //Bind email template
                $templateData = $this->bindEmailTemplate('quote_reject', $quotationDetails, $getStoreDetails);
                $templateData = $templateData[0];
                //Send Email
                $mailResponse = $this->sendQuotationEmail($templateData, $quotationDetails['customer'], [], $getStoreDetails);
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Quotation Reject', 'updated'),
                ];
            }

        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Quotation items list
     *
     * @param $quotationId  Quotation id
     *
     * @author debashrib@riaxe.com
     * @date   19 May 2019
     * @return json response
     */
    public function itemsList($request, $response, $quotationId)
    {
        if ($quotationId != '') {
            $quotationInit = new Quotations();
            $quoteItemsInit = new QuotationItems();
            $variantsInit = new QuotationItemVariants();
            $itemFilesInit = new QuotationItemFiles();
            $decorationsInit = new ProductDecorationsController();
            $getQuotations = $quotationInit->where(
                [
                    'xe_id' => $quotationId,
                ]);
            $totalCounts = $getQuotations->count();
            //Get all measurment units
            $appUnitInit = new AppUnit();
            $initAppUnit = $appUnitInit->select('xe_id', 'name');
            $measurementUnitArr = $initAppUnit->get();
            $measurementUnitArr = json_clean_decode($measurementUnitArr, true);
            //Get All Print Profile
            $printAreaInit = new PrintArea();
            $printArea = $printAreaInit->select('xe_id', 'name', 'width', 'height');
            $allPrintMethodesArr = $printArea->get();
            $allPrintMethodesArr = json_clean_decode($allPrintMethodesArr, true);
            if ($totalCounts > 0) {
                $getItemsData = $quoteItemsInit->where('quote_id', $quotationId);
                if ($getItemsData->count() > 0) {
                    $itemsDataArr = $getItemsData->get();
                    $itemListArr = [];
                    foreach ($itemsDataArr as $itemsData) {
                        $itemsArr = [];
                        $fileArr = [];
                        $getVariants = $variantsInit->where('item_id', $itemsData['xe_id']);
                        $getFiles = $itemFilesInit->select('side_id')->groupBy('side_id')
                            ->where('item_id', $itemsData['xe_id']);
                        $productDecorationArr = $decorationsInit->getDecorationDetail($request, $response, ['product_id' => $itemsData['product_id']], 1); 
                        $productDecorationArr = $productDecorationArr['data'];
                        if ($getFiles->count() > 0) {
                            $getFileArr = $getFiles->get()->toArray();
                            //Get product side data
                            $allSideArr = $productDecorationArr['sides'];
                            $type =  gettype($allSideArr);
                            if ($type == 'object') {
                                $allSideArr = json_clean_decode($allSideArr);
                            }
                            //Get measurement unit name
                            $measurementUnitId = $productDecorationArr['scale_unit_id'];
                            $unitArr = array_filter($measurementUnitArr, function ($item) use ($measurementUnitId) {
                                return $item['xe_id'] == $measurementUnitId;
                            });
                            $unitArr = $unitArr[array_keys($unitArr)[0]];
                            $measurementUnitName = $unitArr['name'];

                            foreach ($getFileArr as $files) {
                                $tempFiles = [];
                                $tempFiles['side_id'] = $files['side_id'];
                                $sideId = $files['side_id'];
                                $sideArr = array_filter($allSideArr, function ($item) use ($sideId) {
                                    return $item['xe_id'] == $sideId;
                                });
                                $sideArr = $sideArr[array_keys($sideArr)[0]];
                                $tempFiles['side_name'] = ($itemsData['is_variable_decoration'] == 0) ? $sideArr['side_name'] : 'Side '. $sideId;
                                $decorationDataArr = $itemFilesInit->where(['item_id' => $itemsData['xe_id'], 'side_id' => $sideId])->get();
                                $decorationArea = [];
                                foreach ($decorationDataArr as $decorationData) {
                                    $tempDecorationArea = $decorationData;
                                    $allDecoArr = $sideArr['product_decoration_setting'];
                                    $decorationAreaId = $decorationData['decoration_area_id'];
                                    $decoArr = array_filter($allDecoArr, function ($item) use ($decorationAreaId) {
                                        return $item['print_area_id'] == $decorationAreaId;
                                    });
                                    $decoArr = $decoArr[array_keys($decoArr)[0]];
                                    $tempDecorationArea['decoration_area'] = ($itemsData['is_variable_decoration'] == 0) ? $decoArr['name'] : 'Side '.$sideId;
                                    $allPrintProfileArr = $decoArr['print_profile_decoration_settings'];
                                    $printMethodId = $decorationData['print_method_id'];
                                    $finalPrintMethod = [];
                                    foreach ($allPrintProfileArr as $printProfileArr) {
                                        $tempPrintMethods = $printProfileArr['print_profile'];
                                        $printProfileArr = array_filter($tempPrintMethods, function ($item) use ($printMethodId) {
                                            return $item['xe_id'] == $printMethodId;
                                        });
                                        $printProfileArr = $printProfileArr[array_keys($printProfileArr)[0]];
                                        if ($printProfileArr['xe_id'] == $printMethodId) {
                                            array_push($finalPrintMethod, $printProfileArr);
                                        }
                                    }
                                    if ($itemsData['is_variable_decoration'] == 1) {
                                        $getVdaPrintMethod =  DB::table("print_profiles")->select('name')->where('xe_id', '=', $printMethodId);
                                        $vdpPrintMethodData = $getVdaPrintMethod->first();
                                        $finalPrintMethod[0]['name'] = $vdpPrintMethodData->name;
                                    }
                                    $tempDecorationArea['print_methods'] = $finalPrintMethod[0]['name'];
                                    $printMethodsArr = array_filter($allPrintMethodesArr, function ($item) use ($decorationAreaId) {
                                        return $item['xe_id'] == $decorationAreaId;
                                    });
                                    $printMethodsArr = $printMethodsArr[array_keys($printMethodsArr)[0]];
                                    //Design hight and width
                                    $extraDataValue = $decorationData['extra_data'];
                                    $extraDataArr = json_clean_decode($extraDataValue, true);

                                    $tempDecorationArea['height'] = $printMethodsArr['height'];
                                    $tempDecorationArea['width'] = $printMethodsArr['width'];
                                    $tempDecorationArea['measurement_unit'] = $measurementUnitName;
                                    $tempDecorationArea['print_area_id'] = $decorationAreaId;
                                    $tempDecorationArea['print_area_name'] = $printMethodsArr['name'];
                                    $tempDecorationArea['upload_design_url'] = path('read', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/' . $sideId . '/' . $decorationData['file'];
                                    $tempDecorationArea['design_file_path'] = path('abs', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/' . $sideId . '/' . $decorationData['file'];
                                    $tempDecorationArea['upload_preview_url'] = path('read', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/' . $sideId . '/' . $decorationData['preview_file'];
                                    $tempDecorationArea['preview_file_path'] = path('abs', 'quotation') . $quotationId . '/' . $itemsData['xe_id'] . '/' . $sideId . '/' . $decorationData['preview_file'];
                                    $tempDecorationArea['design_height'] = (!empty($extraDataArr) && isset($extraDataArr['design_height']) && $extraDataArr['design_height'] != '') ?$extraDataArr['design_height'] : '';
                                    $tempDecorationArea['design_width'] = (!empty($extraDataArr) && isset($extraDataArr['design_width']) && $extraDataArr['design_width'] != '') ?$extraDataArr['design_width'] : '';
                                    unset(
                                        $tempDecorationArea['xe_id'],
                                        $tempDecorationArea['item_id'],
                                        $tempDecorationArea['side_id'],
                                        $tempDecorationArea['extra_data']
                                    );
                                    array_push($decorationArea, $tempDecorationArea->toArray());
                                }
                                $tempFiles['decoration_area'] = $decorationArea;
                                array_push($fileArr, $tempFiles);
                            }
                        }
                        if ($getVariants->count() > 0) {
                            $variantDataArr = $getVariants->select('variant_id', 'quantity', 'unit_price', 'attribute')->get()->toArray();
                            foreach ($variantDataArr as $variantData) {
                                $itemsArr['variant_id'] = $variantData['variant_id'];
                                $itemsArr['quantity'] = $variantData['quantity'];
                                $itemsArr['overall_quantity'] = $itemsData['quantity'];
                                $itemsArr['unit_price'] = $variantData['unit_price'];
                                $itemsArr['product_id'] = $itemsData['product_id'];
                                $itemsArr['custom_design_id'] = ($itemsData['custom_design_id'] == 0 && $itemsData['artwork_type'] == 'uploaded_file') ? '-1' : $itemsData['custom_design_id'];
                                $itemsArr['design_cost'] = $itemsData['design_cost'];
                                $itemsArr['artwork_type'] = $itemsData['artwork_type'];
                                $itemsArr['is_variable_decoration'] = $itemsData['is_variable_decoration'];
                                $itemsArr['is_custom_size'] = $itemsData['is_custom_size'];
                                $itemsArr['custom_size_dimension'] = $itemsData['custom_size_dimension'];
                                $itemsArr['custom_size_dimension_unit'] = $itemsData['custom_size_dimension_unit'];
                                $itemsArr['product_name'] = $productDecorationArr['product_name'];
                                $itemsArr['product_attributes'] = json_clean_decode($variantData['attribute'], true);
                                $itemsArr['files'] = !empty($fileArr) ? $fileArr : [];
                                array_push($itemListArr, $itemsArr);
                            }
                        }

                    }
                }
            }
        }
        return $itemListArr;
    }


    /**
     * POST: Add internal note to quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   23 May 2020
     * @return json response wheather data is saved or any error occured
     */
    public function saveInternalNote($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Internal Note', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $quotationId = to_int($allPostPutVars['quote_id']);
        if ($quotationId != '') {
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->where('xe_id', $quotationId);
            if ($getOldQuotation->count() > 0) {

                $allPostPutVars['created_date'] = date_time(
                    'today', [], 'string'
                );
                $quoteInternalNote = new QuotationInternalNote($allPostPutVars);
                if ($quoteInternalNote->save()) {
                    $noteInsertId = $quoteInternalNote->xe_id;
                    $allFileNames = do_upload(
                        'upload',
                        path('abs', 'quotation') . 'internal-note/', [150],
                        'array'
                    );
                    //Save file name w.r.t note
                    if (!empty($allFileNames)) {
                        foreach ($allFileNames as $eachFile) {
                            $fileData = [
                                'note_id' => $noteInsertId,
                                'file' => $eachFile,
                            ];
                            $saveNoteFile = new QuotationInternalNoteFiles($fileData);
                            $saveNoteFile->save();
                        }
                    }
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Internal Note', 'saved'),
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Bulk actions for quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   23 May 2020
     * @return json response wheather data is saved or any error occured
     */
    public function bulkAction($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Bulk Action', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $quotationId = $allPostPutVars['quote_ids'];
        $eventType = $allPostPutVars['event_type'];
        $emailLogId = $allPostPutVars['email_log_id'];
        $totalEmailCount = $allPostPutVars['total_email_count'];
        if ($quotationId != '' && $quotationId > 0 && $eventType != '') {
            $quotationInit = new Quotations();
            $unsuccessEmails = '';
            $successEmails = '';
            $skippedEmails = '';
            $templateData = [];
            $quotation = $quotationInit->where('xe_id', $quotationId)->first();
            $quotationDetails = json_clean_decode($quotation, true);
            //Get Payments Details
            $paymentInit = new QuotationPayment();
            $paymentData =  $paymentInit->select('payment_amount')->where([
                'quote_id' => $quotationId,
                'payment_status' => 'paid'
            ])->sum('payment_amount');
            $comPaidAmount = ($paymentData > 0) ? $paymentData : 0;
            $comPaidAmount = number_format($comPaidAmount, 2, '.', '');
            $quotationDetails['due_amount'] = $quotationDetails['quote_total'] - $comPaidAmount;

            //check for customer
            if ($quotationDetails['customer_name'] == '' || $quotationDetails['customer_email'] == '') {
                $customersControllerInit = new CustomersController();
                $customerDetails = $customersControllerInit->getQuoteCustomerDetails($quotationDetails['customer_id'], $getStoreDetails['store_id'], '');
                $quotationDetails['customer_name'] = $customerDetails['customer']['name'];
                $quotationDetails['customer_email'] = $customerDetails['customer']['email'];
            }

            if ($quotationDetails['customer_email'] != '') {
                //Get Order status id
                $statusInit = new ProductionStatus();
                $orderStatusId = 0;
                $approvedStatusId = 0;
                $getOrderedStatusData = $statusInit->select('xe_id')->where([
                    'store_id' => $getStoreDetails['store_id'], 
                    'slug' => 'ordered',
                    'module_id' => 1
                ]);

                if ($getOrderedStatusData->count() > 0) {
                   $getOrderedStatusDataArr = $getOrderedStatusData->first(); 
                   $getOrderedStatusDataArr = json_clean_decode($getOrderedStatusDataArr, true);
                   $orderStatusId = $getOrderedStatusDataArr['xe_id'];
                }
                //Get Approved status id
                $getApprovedStatusData = $statusInit->select('xe_id')->where([
                    'store_id' => $getStoreDetails['store_id'], 
                    'slug' => 'approved',
                    'module_id' => 1
                ]);

                if ($getApprovedStatusData->count() > 0) {
                   $getApprovedStatusDataArr = $getApprovedStatusData->first(); 
                   $getApprovedStatusDataArr = json_clean_decode($getApprovedStatusDataArr, true);
                   $approvedStatusId = $getApprovedStatusDataArr['xe_id'];
                }
                if ($eventType == 'payment_reminder' && $quotationDetails['due_amount'] == 0) {
                    $skippedEmails = $quotationDetails['customer_email'];
                } else if ($eventType == 'approval_reminder' && ($quotationDetails['status_id'] == $approvedStatusId  || $quotationDetails['status_id'] == $orderStatusId)) {
                    $skippedEmails = $quotationDetails['customer_email'];
                } else {
                    if ($eventType == 'resend_quotation_mail') {
                        //Bind email template for bulk resend quotation mail
                        $templateData = $this->bindEmailTemplate('bulk_resend_quotation', $quotationDetails, $getStoreDetails);
                    } else if ($eventType == 'approval_reminder') {
                        //Bind email template for bulk quotation approval
                        $templateData = $this->bindEmailTemplate('bulk_quotation_approval', $quotationDetails, $getStoreDetails);
                    } else if ($eventType == 'payment_reminder') {
                        //Bind email template for bulk payment reminder
                        $templateData = $this->bindEmailTemplate('bulk_payment_reminder', $quotationDetails, $getStoreDetails);
                    }
                    $templateData = $templateData[0];
                    $mailResponse = $this->sendQuotationEmail(
                        $templateData, 
                        [
                            'name' => $quotationDetails['customer_name'], 
                            'email' => $quotationDetails['customer_email']
                        ], 
                        [], 
                        $getStoreDetails
                    );
                    if (!empty($mailResponse['status']) && $mailResponse['status'] == 1) {
                        $successEmails = $quotationDetails['customer_email'];
                    } else {
                        $unsuccessEmails = $quotationDetails['customer_email'];
                    }
                }
            } else {
                $skippedEmails = 'No Customer';
            }

            //Save data for Email Log
            if ($emailLogId == 0) {
                $emailLogData = [
                    'store_id' => $getStoreDetails['store_id'],
                    'module' => 'quotation',
                    'type' => $eventType,
                    'subject' => (!empty($templateData)) ? $templateData['subject'] : '',
                    'message' => (!empty($templateData)) ? $templateData['message'] : '',
                    'total_email_count' => $totalEmailCount,
                    'success_email' => $successEmails,
                    'failure_email' => $unsuccessEmails,
                    'skipped_email' => $skippedEmails,
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                $emailLogId = $this->saveDataForEmailLog($emailLogData);
            } else {
                $updateEmailLogData = [
                    'email_log_id' => $emailLogId,
                    'subject' => (!empty($templateData)) ? $templateData['subject'] : '',
                    'message' => (!empty($templateData)) ? $templateData['message'] : '',
                    'success_email' => $successEmails,
                    'failure_email' => $unsuccessEmails,
                    'skipped_email' => $skippedEmails,
                ];
                $emailLogId = $this->updateDataForEmailLog($updateEmailLogData);
            }
        }

        $jsonResponse = [
            'status' => 1,
            'success_mails' => $successEmails,
            'unsuccess_emails' => $unsuccessEmails,
            'skipped_emails' => $skippedEmails,
            'email_log_id' => $emailLogId
        ];
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Get: Get quotation items list
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   23 May 2020
     * @return json response
     */
    public function getQuotationItemsList($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Items', 'error'),
        ];
        $quotationIds = $args['id'];
        $quoteItemsArr = [];
        if (!empty($quotationIds)) {
            $getQuoteIdsToArray = json_clean_decode($quotationIds, true);
            if (count($getQuoteIdsToArray) > 0) {
                foreach ($getQuoteIdsToArray as $quotationId) {
                    $itemsArr = [];
                    $itemsArr[$quotationId] = $this->getQuoteItems($request, $response, ['id' => $quotationId], 1);
                    array_push($quoteItemsArr, $itemsArr);
                }
                $jsonResponse = [
                    'status' => 1,
                    'data' => $quoteItemsArr,
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Convert Quotation into Order
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   23 May 2020
     * @return json response
     */
    public function convertQuoteToOrder($request, $response, $otherParameter = [])
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Convert to order', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $quotationId = (isset($allPostPutVars['quote_id'])) ? $allPostPutVars['quote_id'] : $otherParameter['quote_id'];
        if ($quotationId != '') {
             //Check all items were having variants or not
            $quoteItemsInit = new QuotationItems();
            $variantsInit = new QuotationItemVariants();
            $variantFlag = 1;
            $getItems = $quoteItemsInit->select('xe_id')->where('quote_id', $quotationId);
            if ($getItems->count() > 0) {
                $getItemsData = $getItems->get()->toArray();
                foreach ($getItemsData as $items) {
                    $variantData = $variantsInit->where('item_id', $items['xe_id']);
                    if ($variantData->count() == 0) {
                        $variantFlag = 0;
                    } else {
                        $variantDataArr = $variantData->get();
                        $variantDataArr = json_clean_decode($variantDataArr, true);
                        foreach ($variantDataArr as $dataArr) {
                            $attributData = $dataArr['attribute'];
                            $attributDataArr = json_clean_decode($attributData, true);
                            foreach ($attributDataArr as $attKey => $attributes) {
                                if ((isset($attributes['is_selected']) && $attributes['is_selected'] == 0) || ($attKey == $attributes['name'])) {
                                    $variantFlag = 0;
                                }
                            }
                        }
                    }
                }
            }
            if ($variantFlag == 1) {
                $quotationInit = new Quotations();
                $quotationDetails = $quotationInit->where('xe_id', $quotationId)
                    ->first();
                $quotationDetails = json_clean_decode($quotationDetails, true);
                $quotationDetails['product_data'] = $this->itemsList($request, $response, $quotationId);
                $jsonData['data'] = json_encode($quotationDetails);
                if ($quotationDetails['order_id'] == '') {
                    $jsonResponse = call_curl(
                        $jsonData, "convert-quote-to-order", "POST");
                    if (!empty($jsonResponse) && $jsonResponse['status'] == 1) {
                        $logData = [
                            'quote_id' => $quotationId,
                            'description' => "Quote is coverted to order",
                            'user_type' => (isset($allPostPutVars['user_type'])) ? $allPostPutVars['user_type'] : $otherParameter['user_type'],
                            'user_id' => (isset($allPostPutVars['user_id'])) ? $allPostPutVars['user_id'] : $otherParameter['user_id'],
                            'created_date' => date_time(
                                'today', [], 'string'
                            )
                        ];
                        $this->addingQuotationLog($logData);
                        //Change the quotation status to ordered
                        $orderId = $jsonResponse['data'];
                        $orderNumber = (isset($jsonResponse['order_number']) && $jsonResponse['order_number'] != '') ? $jsonResponse['order_number'] : '';
                        //Get Order status id
                        $statusInit = new ProductionStatus();
                        $orderStatusId = 0;
                        $getOrderedStatusData = $statusInit->select('xe_id')->where([
                            'store_id' => $getStoreDetails['store_id'], 
                            'slug' => 'ordered',
                            'module_id' => 1
                        ]);

                        if ($getOrderedStatusData->count() > 0) {
                           $getOrderedStatusDataArr = $getOrderedStatusData->first(); 
                           $getOrderedStatusDataArr = json_clean_decode($getOrderedStatusDataArr, true);
                           $orderStatusId = $getOrderedStatusDataArr['xe_id'];
                        }
                        $quotationInit->where('xe_id', $quotationId)
                            ->update(['status_id' => $orderStatusId, 'order_id' => $orderId]); // 5:Ordered
                        //Add data to Orders table
                        $ordersInit = new OrdersController();
                        $ordersInit->saveDataForOrder($orderId, $quotationDetails['store_id'], $quotationDetails['customer_id'], $orderNumber);
                    }
                } else {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Convert to order', 'exist'),
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Convert to order', 'insufficient'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Get Email Template data
     *
     * @param $emailType  Email Template Type
     * @param $quotationDetails Quotation Details Array
     * @param $getStoreDetails Store Id
     *
     * @author debashrib@riaxe.com
     * @date   05 June 2020
     * @return array response
     */
    public function bindEmailTemplate($emailType, $quotationDetails, $getStoreDetails)
    {
        $resData = [];
        if ($emailType != '' && !empty($quotationDetails)) {
            //Bind email template
            $templateData = $this->getEmailTemplate(1, $getStoreDetails, $emailType);
            $string = $templateData[0]['message'];
            $ldelim = "{";
            $rdelim = "}";
            $pattern = "/" . preg_quote($ldelim) . "(.*?)" . preg_quote($rdelim) . "/";
            preg_match_all($pattern, $string, $matches);
            $abbriviationData = $matches[1];
            foreach ($abbriviationData as $abbrData) {
                $abbrName = '{'.$abbrData.'}';
                if (strpos($templateData[0]['message'], $abbrName) !== false) {
                    $abbrValue = $this->getAbbriviationValue($abbrName, $quotationDetails, $getStoreDetails['store_id']);
                    $templateData[0]['message'] = str_replace($abbrName, $abbrValue, $templateData[0]['message']);
                }
            }
            $resData = $templateData;
        }
        return $resData;
    }

    /**
     * Get Email Template Abbriviation Value
     *
     * @param $abbrName  Abbriviation Name
     * @param $quotationDetails Quotation Details Drray
     *
     * @author debashrib@riaxe.com
     * @date   05 June 2020
     * @return array response
     */

    public function getAbbriviationValue($abbrName, $quotationDetails, $storeId)
    {
        $quotationId = $quotationDetails['xe_id'];
        //Get setting data for default currency
        $globalSettingData = $this->readSettingJsonFile($storeId);
        $currency = $globalSettingData['currency']['value'];
        
        $abbrValue = '';
        //switch case
        switch ($abbrName) {
            case "{quote_id}":
                $abbrValue = $quotationDetails['quote_id'];
                break;
            case "{quote_date}":
                $abbrValue = date('m-d-Y', strtotime($quotationDetails['created_at']));
                break;
            case "{customer_name}":
                $abbrValue = ($quotationDetails['customer']['name'] != '') ? $quotationDetails['customer']['name'] : $quotationDetails['customer']['email'];
                break;
            case "{payment_date}":
                $abbrValue = ($quotationDetails['request_date'] != '' && $quotationDetails['request_date'] != '0000-00-00 00:00:00') ? date('m-d-Y', strtotime($quotationDetails['request_date'])) : '';
                break;
            case "{shipping_date}":
                $abbrValue = date('m-d-Y', strtotime($quotationDetails['ship_by_date']));
                break;
            case "{delivery_date}":
                $abbrValue = date('m-d-Y', strtotime($quotationDetails['exp_delivery_date']));
                break;
            case "{payment_amount}":
                //Get latest payment amount
                $paymentInit = new QuotationPayment();
                $paymentData =  $paymentInit->where('quote_id', $quotationId);
                $quotePaymentLog = $paymentData->get()->toArray();
                $latestPayment = end($quotePaymentLog);
                $paymentAmount = !empty($latestPayment) ? $latestPayment['payment_amount'] : 0;
                $abbrValue = $currency.number_format($paymentAmount, 2, '.', '');
                break;
            case "{payment_due_amount}":
                //Get latest payment amount
                $dueAmount = $quotationDetails['due_amount'];
                $abbrValue = $currency.number_format($dueAmount, 2, '.', '');
                break;
            case "{public_url}":
                $token = 'quote_id=' . $quotationDetails['xe_id'].'&store_id='.$storeId;
                $token = base64_encode($token);
                $url = 'quotation/quotation-approval?token=' . $token;
                $abbrValue = API_URL.$url;
                break;
            case "{reject_note}":
                $abbrValue = $quotationDetails['reject_note'];
                break;
            case "{request_payment_amount}":
                $amount = (($quotationDetails['request_payment'] != '') && $quotationDetails['request_payment'] > 0) ? $quotationDetails['request_payment'] : $quotationDetails['due_amount'];
                $abbrValue = $currency.number_format($amount, 2, '.', '');
                break;
            case "{quote_total_amount}":
                $quoteTotalAmont = $quotationDetails['quote_total'];
                $abbrValue = $currency.number_format($quoteTotalAmont, 2, '.', '');
                break;
            case "{quote_pdf_download}":
                $strResult = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                $token = substr(str_shuffle($strResult),  
                       0, 16); ;
                $abbrValue = BASE_URL . "quotation/download/" . $quotationId."?token=".$token;
                break;
            default:
                $abbrValue = $abbrName;
        }
        return $abbrValue;
    }

    /**
     * Send Email
     *
     * @param $emailType  Email Template Type
     * @param $quotationDetails Quotation Details Drray
     *
     * @author debashrib@riaxe.com
     * @date   05 June 2020
     * @return array response
     */
    public function sendQuotationEmail($templateData, $customerData, $attachments = [], $getStoreDetails)
    {
        //Get smtp email setting data for sending email
        $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
        $emailData = $globalSettingData['smpt_email_details']['email_address_details'];
        $smtpData = $globalSettingData['smpt_email_details']['smtp_details'];
        $fromEmail = $emailData['from_email'];
        $replyToEmail = $emailData['to_email'];
        $emailBody = $templateData['message'];
        if (empty($customerData)) {
            $customerData['email'] = $customerData['name'] = $replyToEmail;
        }
        $mailContaint = ['from' => ['email' => $fromEmail, 'name' => $fromEmail],
            'recipients' => [
                'to' => [
                    'email' => $customerData['email'],
                    'name' => $customerData['name'],
                ],
                'reply_to' => [
                    'email' => $replyToEmail,
                    'name' => $replyToEmail,
                ],
            ],
            'attachments' => ($attachments != '') ? $attachments : [],
            'subject' => $templateData['subject'],
            'body' => $emailBody,
            'smptData' => $smtpData
        ];
        if ($smtpData['smtp_host'] !='' 
            && $smtpData['smtp_user'] != ''
            && $smtpData['smtp_pass'] != ''
        ) {
                $mailResponse = email($mailContaint);
        } else {
            $mailResponse['status'] = 0;
        }
        return $mailResponse;
    }

    /**
     * POST: Add Conversation to quotation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   08 June 2020
     * @return json response wheather data is saved or any error occured
     */
    public function saveConversations($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Conversation', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $quotationId = to_int($allPostPutVars['quote_id']);
        if ($quotationId != '') {
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->where('xe_id', $quotationId);
            if ($getOldQuotation->count() > 0) {

                $allPostPutVars['created_date'] = date_time(
                    'today', [], 'string'
                );
                $QuoteConversations = new QuotationConversations($allPostPutVars);
                if ($QuoteConversations->save()) {
                    $convInsertId = $QuoteConversations->xe_id;
                    $allFileNames = do_upload(
                        'upload',
                        path('abs', 'quotation') . 'conversation/', [150],
                        'array'
                    );
                    //Save file name w.r.t note
                    if (!empty($allFileNames)) {
                        foreach ($allFileNames as $eachFile) {
                            $fileData = [
                                'conversation_id' => $convInsertId,
                                'file' => $eachFile,
                            ];
                            $saveConversationFile = new QuotationConversationFiles($fileData);
                            $saveConversationFile->save();
                        }
                    }
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Conversation', 'saved'),
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Get quotation conversation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   08 June 2020
     * @return json response
     */
    public function getConversations($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Conversation', 'not_found'),
        ];
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->where('xe_id', $quotationId);
            if ($getOldQuotation->count() > 0) {
                $conversationInit = new QuotationConversations();
                $conversations = $conversationInit->with('files')->where('quote_id', $quotationId);
                if ($conversations->count() > 0) {
                    $conversationArr = $conversations->get();
                    $jsonResponse = [
                        'status' => 1,
                        'data' => $conversationArr,
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Change quotation conversation seen flag
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   10 June 2020
     * @return json response
     */
    public function changeConversationSeenFlag($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Conversation Seen Flag', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $quotationId = to_int($allPostPutVars['quote_id']);
        if ($quotationId != '') {
            $QuoteConversationInit = new QuotationConversations();
            $QuoteConversationInit->where('quote_id', $quotationId)
                ->update(['seen_flag' => '0']);

            $jsonResponse = [
                'status' => 1,
                'message' => message('Conversation Seen Flag', 'done'),
            ];

        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Get quotation dynamic form attribute
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   17 June 2020
     * @return json response
     */
    public function getFormAttribute($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Form Attribute', 'not_found'),
        ];

        $formAttributeInit = new QuotationDynamicFormAttributes();
        if ($formAttributeInit->count() > 0) {
            $getFormAttribute = $formAttributeInit->get();
            $jsonResponse = [
                'status' => 1,
                'data' => $getFormAttribute,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Art Work Design
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   20 Jun 2020
     * @return json response wheather data is saved or any error occured
     */
    public function artWorkDesign($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Design Data', 'error'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $productData = [];
        if (isset($allPostPutVars['product_data'])) {
            $productData = json_clean_decode($allPostPutVars['product_data'], true);
        }

        if (!empty($productData)) {
            if (isset($allPostPutVars['design_data'])
                && $allPostPutVars['design_data'] != ''
            ) {
                $designData = json_clean_decode($allPostPutVars['design_data'], true);
                $productId = isset($designData['product_info']['product_id'])
                && $designData['product_info']['product_id'] != ''
                ? $designData['product_info']['product_id'] : null;
                $variantId = isset($productData[0]['variant_id'])
                && $productData[0]['variant_id'] != ''
                ? $productData[0]['variant_id'] : null;

                // Prepare array for saving design data
                $designDetails = [
                    'store_id' => $getStoreDetails['store_id'],
                    'product_setting_id' => (isset($allPostPutVars['product_setting_id'])
                        && $allPostPutVars['product_setting_id'] != "")
                    ? $allPostPutVars['product_setting_id'] : null,
                    'product_variant_id' => $variantId,
                    'product_id' => $productId,
                    'type' => (isset($allPostPutVars['template_type'])
                        && $allPostPutVars['template_type'] != "")
                    ? $allPostPutVars['template_type'] : "artwork",
                    'custom_price' => (isset($designData['custome_price'])
                        && $designData['custome_price'] > 0)
                    ? $designData['custome_price'] : 0.00,
                ];
                if (isset($designDetails) && !empty($designDetails != '')) {
                    // save design data and get customDesignId
                    $customDesignId = $this->saveDesignData(
                        $designDetails, $allPostPutVars['design_data'], ['directory' => 'artworks']
                    );
                    if ($customDesignId > 0) {
                        $jsonResponse = [
                            'status' => 1,
                            'custom_design_id' => $customDesignId,
                            'message' => message('Design Data', 'saved'),
                        ];
                    }
                }
            }
        }
        return response(
            $response, [
                'data' => $jsonResponse, 'status' => $serverStatusCode,
            ]
        );
    }

    /**
     * POST: Save Quotation Dynamic Form
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   17 June 2020
     * @return json response
     */
    public function saveDynamicForm($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Dynamic Form', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        if (isset($allPostPutVars['data']) && $allPostPutVars['data'] != "") {
            $dynamicForm = new QuotationDynamicForm($allPostPutVars['data']);
            if ($quoteInternalNote->save()) {
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Dynamic Form', 'saved'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Check setting for quotation creatation
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   22 June 2020
     * @return json response
     */
    public function checkSettingForQuote($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 0,
            'message' => message('Setting Check', 'error'),
        ];

        //Get quotation setting data for sending email
        $settingData = $this->getProductionSetting($request, $response, ['module_id' => 1, 'return_type' => 1]);
        $settingData = $settingData['data'];
        $quotationSetting = [];
        if (!empty($settingData)) {
            $quotationSetting['is_quote_id_enable'] = $settingData['is_quote_id_enable'];
            $quotationSetting['quote_id'] = ($settingData['quote_id']['prefix'] != ''
                && $settingData['quote_id']['starting_number'] != ''
                && $settingData['quote_id']['postfix'] != '') ? true : false;
            $quotationSetting['invoice_setting'] = ($settingData['sender_email'] != '' && $settingData['company_name'] != '' && $settingData['phone_number'] != '' && $settingData['city'] != '' && $settingData['country'] != '' && $settingData['zip_code'] != '' && $settingData['address'] != '' && $settingData['company_logo'] != '') ? true : false;
            //Email Template Data
            $templateData = $settingData['template_data'];
            $sendEmailTemp = array_filter($templateData, function ($item) {
                return $item['template_type_name'] == 'quote_sent';
            });
            $sendEmailTemp = $sendEmailTemp[array_keys($sendEmailTemp)[0]];
            $quotationSetting['quotation_sending_email'] = ($sendEmailTemp['is_configured'] == 1) ? true : false;
            //Get SMPT setup from general setting
            $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
            $emailData = $globalSettingData['smpt_email_details']['email_address_details'];
            $smtpData = $globalSettingData['smpt_email_details']['smtp_details'];

            $quotationSetting['smtp_data_setting'] = ($emailData['from_email'] != '' && $emailData['to_email'] != '' && $smtpData['smtp_host'] !='' && $smtpData['smtp_user'] != '' && $smtpData['smtp_pass'] != '' && $smtpData['smtp_from'] != '') ? true : false;
        }
        if (!empty($quotationSetting)) {
            $jsonResponse = [
                'status' => 1,
                'data' => $quotationSetting,
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Send quotation request
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   04 July 2020
     * @return json response
     */
    public function sendQuotationRequest($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quatation Request Data', 'error'),
        ];

        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
        //$quoteId = 'INQ' . getRandom();
        $fromEmail = $globalSettingData['smpt_email_details']['email_address_details']['from_email'];
        $settingsEmail = $globalSettingData['email'];
        if (!empty($settingsEmail) && !empty($fromEmail)) {
            if (!empty($allPostPutVars['data'])) {
                $requestDetails = json_clean_decode($allPostPutVars['data']);
                $productDetails = $requestDetails['product_details'];
                $variantDetails = $productDetails['variation'];
                if (!empty($productDetails)) {
                    $productsControllerInit = new ProductsController();
                    $productData = $productsControllerInit->getProductList($request, $response, ['id' => $productDetails['product_id']], 1);
                    $productVariantData = $productsControllerInit->getProductVariants($request, $response, ['pid' => $productDetails['product_id']], 1);
                }
                if ($requestDetails['form_details']) {
                    $emailParam = [];
                    //Save uploaded data
                    if (!empty($requestDetails['form_details']['attachments'])) {
                        $attachmentFiles = $requestDetails['form_details']['attachments'];
                        unset($requestDetails['form_details']['attachments']);
                        $attachmentKey = 1;
                        foreach ($attachmentFiles as $attachKey => $attachValue) {
                            $multipleFileArr = [];
                            $fileValueArr = explode(',', $attachValue);
                            if (count($fileValueArr) > 1) {
                                foreach ($fileValueArr as $multipleFiles) {
                                    $multipleFiles = trim($multipleFiles);
                                    $attachmentFileName = do_upload(
                                        $multipleFiles,
                                        path('abs', 'quotation_request'),
                                        'string'
                                    );
                                    $multipleFileArr[] = $attachmentFileName[0];
                                    $emailParam['attachments'][] = path('abs', 'quotation_request') . $attachmentFileName;
                                }
                            } else {
                                $attachmentFileName = do_upload(
                                    $fileValueArr[0],
                                    path('abs', 'quotation_request'),
                                    [],
                                    'string'
                                );
                                $emailParam['attachments'][] = path('abs', 'quotation_request') . $attachmentFileName;
                            }
                            
                            if (isset($multipleFileArr) && !empty($multipleFileArr)) {
                                $attachmentFileName = implode(', ', $multipleFileArr);
                            }
                            if ($attachmentFileName != '') {
                                $requestDetails['form_details'][$attachKey] = $attachmentFileName;
                                $requestDetails['form_details']['file_type'][] = $attachKey;
                                $attachmentKey = $attachmentKey + 1;
                            }
                        }
                    }
                    //Create quotation
                    if (isset($requestDetails['is_quotation_enable']) && $requestDetails['is_quotation_enable'] == 1) {
                        //Generate Quote Id
                        $quotationInit = new Quotations();
                        $lastQuotationRecord = $quotationInit->select('quote_id')->latest()->first();
                        if (!empty($lastQuotationRecord)) {
                            $quoteId = $this->generateQuoteId($request, $lastQuotationRecord->quote_id);
                        } else {
                            $quoteId = $this->generateQuoteId($request);
                        }
                        //Get Open status id
                        $statusInit = new ProductionStatus();
                        $getStatusData = $statusInit->select('xe_id')->where([
                            'store_id' => $getStoreDetails['store_id'], 
                            'slug' => 'open',
                            'module_id' => 1
                        ]);
                        if ($getStatusData->count() > 0) {
                           $getStatusDataArr = $getStatusData->first(); 
                           $getStatusDataArr = json_clean_decode($getStatusDataArr, true);
                        }
                        $quotationData = [
                            'store_id' => $getStoreDetails['store_id'],
                            'quote_id' => $quoteId,
                            'created_by' => 'customer',
                            'quote_source' => 'tool',
                            'design_total' => $requestDetails['total_price'],
                            'quote_total' => $requestDetails['total_price'],
                            'status_id' => $getStatusDataArr['xe_id'],
                            'draft_flag' => 1,
                            'discount_type' => 'flat',
                            'discount_amount' => 0,
                            'shipping_type' => 'express',
                            'shipping_amount' => 0,
                            'ship_by_date' => date_time(
                                            'today', [], 'string'
                                        ),
                            'exp_delivery_date' => date_time(
                                            'today', [], 'string'
                                        ),
                        ];
                        $customerId = (isset($requestDetails['customer_id']) && $requestDetails['customer_id'] > 0) ? $requestDetails['customer_id'] : '';
                        $shippingId = '';
                        $customerName = '';
                        $customerEmail = '';
                        if ($customerId != '') {
                            $customersControllerInit = new CustomersController();
                            $customerDetails = $customersControllerInit->getQuoteCustomerDetails($customerId, $getStoreDetails['store_id'], '', true);
                            if (!empty($customerDetails)) {
                                $customerName = $customerDetails['customer']['name'];
                                $customerEmail = $customerDetails['customer']['email'];
                            }
                            $shippingId = $customerDetails['customer']['shipping_address'][0]['id'];
                            $quotationData += [
                                'customer_id' => $customerId,
                                'shipping_id' => $shippingId,
                                'created_by_id' => $customerId,
                                'customer_name' => $customerName,
                                'customer_email' => $customerEmail,
                            ];
                        }
                        
                        $quotation = new Quotations($quotationData);
                        if ($quotation->save()) {
                            $quotationLastId = $quotation->xe_id;
                            //Add quotation item data with its variants
                            if (!empty($productDetails)) {
                                $quantityArr = array_column($productDetails['variation'], 'qty');
                                $totalQuantity = array_sum($quantityArr);
                                $itemData = [
                                    'quote_id' => $quotationLastId,
                                    'product_id' => $productDetails['product_id'],
                                    'quantity' => $totalQuantity,
                                    'artwork_type' => 'design_tool',
                                    'custom_design_id' => $requestDetails['design_details']['custom_design_id'],
                                    'design_cost' => $requestDetails['decoration_price'],
                                    'unit_total' =>  ''
                                ];
                                $quotationItems = new QuotationItems($itemData);
                                if ($quotationItems->save()) {
                                    $lastQuotationItemId = $quotationItems->xe_id;
                                    $totalUnitPrice = 0;
                                    foreach ($productDetails['variation'] as $variations) {
                                        $variantId = $variations['variant_id'];
                                        if (STORE_NAME == 'Opencart') {
                                            $storeCompoInit = new StoreComponent();
                                            $unitPrice = $storeCompoInit->getPriceByVariantId($variantId);
                                        } else {
                                            $unitPriceArr = array_filter($productVariantData, function ($item) use ($variantId) {
                                                return $item['id'] == $variantId;
                                            });
                                            $unitPriceArr = $unitPriceArr[array_keys($unitPriceArr)[0]];
                                            $unitPrice = $unitPriceArr['price'];
                                        }
                                        $totalUnitPrice = $totalUnitPrice + (($unitPrice * $variations['qty']) + $requestDetails['decoration_price']);
                                        $variations = [
                                            'item_id' => $lastQuotationItemId,
                                            'variant_id' => $variantId,
                                            'quantity' => $variations['qty'],
                                            'unit_price' => $unitPrice,
                                            'attribute' => ''
                                        ];
                                        $quotationItemVariants = new QuotationItemVariants($variations);
                                        $quotationItemVariants->save();
                                    }
                                    //Update total unit price in quotation items table
                                    $quotationItemsInit = new QuotationItems();
                                    $quotationItemsInit->where('xe_id', $lastQuotationItemId)
                                        ->update(['unit_total' => $totalUnitPrice]);
                                    //Add data to quotation log
                                    $logData = [
                                        'quote_id' => $quotationLastId,
                                        'description' => 'Quotation is created from designer tool',
                                        'user_type' => 'customer',
                                        'created_date' => date_time(
                                            'today', [], 'string'
                                        )
                                    ];
                                    $this->addingQuotationLog($logData);
                                }
                            }
                        }
                    } else {
                        $quoteId = 'INQ' . getRandom();
                    }
                    $emalContent = "Quotation request information: <br>";
                    $fileTypeArr = isset($requestDetails['form_details']['file_type']) ? $requestDetails['form_details']['file_type'] : [];
                    foreach ($requestDetails['form_details'] as $formKey => $formValue) {
                        if (!in_array($formKey, $fileTypeArr)) {
                            $formValue = !is_array($formValue) ? $formValue : json_clean_encode($formValue, true);
                            $emalContent .= "<span style='padding-left:2%'>" . $formKey . ": " . $formValue . "</span><br>";
                        }
                        if ($formKey != 'file_type') {
                            $fileType = '';
                            if (in_array($formKey, $fileTypeArr)) {
                                $fileType = 'file';
                            }
                            $formData = [
                                'quote_id' => $quoteId,
                                'form_key' => $formKey,
                                'form_value' => $formValue,
                                'form_type' => $fileType,
                            ];
                            $saveFormData = new QuotationRequestFormValues($formData);
                            $saveFormData->save();
                        }
                       
                    }
                    $emalContent .= "<br>";
                    if (!empty($requestDetails['product_details'])) {
                        $customDesignId = $requestDetails['design_details']['custom_design_id'];
                        if (!empty($customDesignId)) {
                            $svgJsonPath = path('abs', 'design_state') . 'artworks/' . $customDesignId . '.json';
                            if (file_exists($svgJsonPath)) {
                                $svgData = read_file($svgJsonPath);
                                $svgData = json_clean_decode($svgData, true);
                                if (!empty($svgData['design_product_data'])) {
                                    foreach ($svgData['design_product_data'] as $variantsKey => $variantsValue) {
                                        if (!empty($variantsValue['design_urls'])) {
                                            foreach ($variantsValue['design_urls'] as $designKey => $designs) {
                                                $emailParam['attachments'][] = str_replace(API_URL, rtrim(RELATIVE_PATH, WORKING_DIR) . '/', $designs);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        $emalContent .= 'Product Details: <br>';
                        $emalContent .= "<span style='padding-left:2%'>" . 'Product name' . ": " . $productData['data']['name']; //. "(SKU)" . "</span><br>";
                        if (!empty($productData['data']['sku'])) {
                            $emalContent .= " (sku - " . $productData['data']['sku'] . ")</spn>";
                        }
                        if (!empty($variantDetails)) {
                            $variantData = "";
                            foreach ($variantDetails as $variationKey => $variations) {
                                if ($productData['data']['type'] == 'variable') {
                                    $variantData .= "<br><span style='padding-left:4%'>";
                                    foreach ($variations as $ItemKey => $items) {
                                        if ($ItemKey != 'variant_id') {
                                            if ($ItemKey == 'qty') {
                                                $variantData = rtrim($variantData, '/');
                                                $variantData .= " - " . $items;
                                            } else {
                                                $variantData .=  $items . "/";
                                            }
                                        }
                                    }
                                    $variantData .= "</span>";
                                }
                                if ($productData['data']['type'] == 'simple') {
                                    $variantData .= "<span>";
                                    foreach ($variations as $ItemKey => $items) {
                                        if ($ItemKey == 'qty') {
                                            $variantData .= " - Qty - " . $items;
                                        }
                                    }
                                    $variantData .= "</span>";
                                }
                            }   
                            $emalContent .= $variantData . "<br>";
                        }
                        $requestData = [
                            'quote_id' => $quoteId,
                            'product_details' => !is_array($requestDetails['product_details'])
                            ? $requestDetails['product_details']
                            : json_encode($requestDetails['product_details']),
                            'design_details' => !is_array($requestDetails['design_details'])
                            ? $requestDetails['design_details']
                            : json_encode($requestDetails['design_details']),
                        ];
                        $saveRequestData = new QuotationRequestDetails($requestData);
                        $saveRequestData->save();
                        $lastSaveRequestDataId = $saveRequestData->xe_id;
                        //Update last quotation request id in quotation tgable
                        if (isset($quotationLastId) && isset($requestDetails['is_quotation_enable']) && $requestDetails['is_quotation_enable'] == 1 && $lastSaveRequestDataId > 0) {
                             $quotationInit->where('xe_id', $quotationLastId)
                                ->update(['quotation_request_id' => $lastSaveRequestDataId]);
                        }
                        $emailParam['message'] = $emalContent;
                        $emailParam['subject'] = 'Quotation request submitted';
                        $mailResponse = $this->sendQuotationEmail($emailParam, [], $emailParam['attachments'], $getStoreDetails);
                        //if ($mailResponse['status'] == 1) {
                            $jsonResponse = [
                                'status' => 1,
                                'message' => message('Quatation Request Data', 'saved'),
                                'quote_id' => $quotationLastId,
                            ];
                        //}
                    }
                }
            }
        } else {
            $jsonResponse = [
                'status' => 0,
                'message' => "No email is set in the admin section",
            ];
        }
        return response(
            $response, [
                'data' => $jsonResponse, 'status' => $serverStatusCode,
            ]
        );
    }

    /**
     * Move Quotation uploaded files to Oreder Folder
     *
     * @param $quotationId  Quotation Id
     * @param $orderId      Order Id
     *
     * @author debashrib@riaxe.com
     * @date   25th June 2020
     * @return boolean
     */
    public function moveQuoteFileToOrder($request, $response, $args, $returnType = 0)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quatation File', 'error'),
            'data' => false
        ];
        $getStoreDetails = get_store_details($request);
        $quotationId = to_int($args['id']);
        $orderId = !empty($request->getQueryParam('order_id')) ? $request->getQueryParam('order_id') : $args['order_id'];
        if ($quotationId != '' && $quotationId > 0 && $orderId != '' && $orderId > 0) {
            $decorationsInit = new ProductDecorationsController();
            $ordersInit = new OrdersController();
            $itemDataArr = $this->itemsList($request, $response, $quotationId);
            $orderItemsDetails = $ordersInit->getStoreItemsDetails($request, $response, ['id' => $orderId, 'store_id' => $getStoreDetails['store_id']], 1);
            //adding quotation in order details array
            $quotationInit = new Quotations();
            $getQuotation = $quotationInit->select('quote_id', 'quote_source')
                ->where('xe_id', $quotationId)
                ->get()->toArray();
            $quotationNumber = $getQuotation[0]['quote_id'];
            $orderItemsDetails['order_details']['quotation_id'] = $quotationNumber;
            $orderItemsDetails['order_details']['quote_id'] = $quotationId;
            $orderItemsDetails['order_details']['quote_source'] = $getQuotation[0]['quote_source'];
            
            $finalOrderItemsDetails = $orderItemsDetails;
            $orderItemsDetails = $orderItemsDetails['order_details']['order_items'];
            $finalOrderData = [];
            foreach ($orderItemsDetails as $itemsData) {
                $tempOrderData = $itemsData;
                $tempOrderData['is_quote_order'] = 1;
                $files = [];
                if ($itemsData['ref_id'] == '-1') {
                    $variantId = ($itemsData['product_id'] == $itemsData['variant_id']) ? 0 : $itemsData['variant_id'];
                    $productId = $itemsData['product_id'];
                    $decoArr = array_filter($itemDataArr, function ($item) use ($variantId, $productId) {
                        return ($item['product_id'] == $productId && $item['variant_id'] == $variantId);
                    });
                    $decoArr = $decoArr[array_keys($decoArr)[0]];
                    $files = $decoArr['files'];
                }
                $tempOrderData['file_data'] = $files;
                array_push($finalOrderData, $tempOrderData);
            }
            $finalOrderItemsDetails['order_details']['order_items'] = $finalOrderData;
            //Initiate order download controller
            $orderDwonloadObj = new OrderDownloadController();
            $quotationflag = true;
            $status = $orderDwonloadObj->createOrderAssetFile(['id' => $orderId], $finalOrderItemsDetails, $quotationflag);
            if ($status) {
                //Move file which are uploaded
                $finalArr = [];
                $finalItemArr = [];
                foreach ($itemDataArr as $items) {
                    $tempFilsArr = $items;
                    $productData = $decorationsInit->getDecorationDetail($request, $response, ['product_id' => $items['product_id']], 1); 
                    $newFileArr = [];
                    if ($items['is_variable_decoration'] == 0) {
                        $sizeDataArr = $productData['data']['sides'];
                        foreach ($sizeDataArr as $sideValue) {
                            $sideId = $sideValue['xe_id'];
                            $tempArr = [
                                'side_id' => $sideId,
                                'side_wise_files' => [],
                            ];
                            $sideArr = array_filter($items['files'], function ($item) use ($sideId) {
                                return $item['side_id'] == $sideId;
                            });
                            if (!empty($sideArr)) {
                                foreach ($sideArr as $value) {
                                    $tempArr = [
                                        'side_id' => $sideId,
                                        'side_wise_files' => $value['decoration_area'],
                                    ];
                                }
                            }
                            array_push($newFileArr, $tempArr);
                        }
                    } else {
                        foreach ($items['files'] as $filesArr) {
                            $tempArr = [
                                'side_id' => $filesArr['side_id'],
                                'side_wise_files' => $filesArr['decoration_area'][0],
                            ];
                            array_push($newFileArr, $tempArr);
                        }
                    }
                    $tempFilsArr['files'] = $newFileArr;
                    array_push($finalItemArr, $tempFilsArr);
                }
                foreach ($finalItemArr as $itemsArr) {
                    if ($itemsArr['custom_design_id'] == '-1') {
                        if ($itemsArr['variant_id'] == 0) {
                            $storeItemArr = array_filter($orderItemsDetails, function ($item) use ($itemsArr) {
                                return ($item['product_id'] == $itemsArr['product_id'] && $item['variant_id'] == $itemsArr['product_id']);
                            });
                        } else {
                            $storeItemArr = array_filter($orderItemsDetails, function ($item) use ($itemsArr) {
                                return ($item['product_id'] == $itemsArr['product_id'] && $item['variant_id'] == $itemsArr['variant_id']);
                            });
                        }
                        //$storeItemArr = $storeItemArr[array_keys($storeItemArr)[0]];
                        foreach ($storeItemArr as $storeItemArrValue) {
                            $itemId = $storeItemArrValue['item_id'];
                            foreach ($itemsArr['files'] as $key => $filesArr) {
                                $sideKey = $key + 1;
                                $side = 'side_' . $sideKey;
                                if (!empty($filesArr['side_wise_files'])) {
                                    foreach ($filesArr['side_wise_files'] as $files) {
                                        $disFilePath = path('abs', 'order') . $orderId . "/" . $itemId . "/" . $side;
                                        //Create folder
                                        if (!is_dir($disFilePath)) {
                                            if (!file_exists($disFilePath)) {
                                                mkdir($disFilePath, 0755, true);
                                            }
                                        }
                                        $disPreviewFilePath = $disFilePath . "/preview";
                                        if (!is_dir($disPreviewFilePath)) {
                                            if (!file_exists($disPreviewFilePath)) {
                                                mkdir($disPreviewFilePath, 0755, true);
                                            }
                                        }
                                        copy($files['preview_file_path'], $disPreviewFilePath . '/' . $files['preview_file']);
                                        copy($files['design_file_path'], $disFilePath . '/' . $files['file']);
                                    }
                                }
                            }
                        }
                    }
                }
                //Send Email
                //Get Quotation details
                $quotationData = $this->getQuotationDetails($request, $response, ['id' => $quotationId], 1);
                //Bind email template
                $templateData = $this->bindEmailTemplate('convert_to_order', $quotationData, $getStoreDetails);
                $templateData = $templateData[0];
                //Send Email
                $mailResponse = $this->sendQuotationEmail($templateData, $quotationData['customer'], [], $getStoreDetails);
            }
            $jsonResponse = [
                'status' => 1,
                'data' => true,
            ];
            if ($returnType == 1) {
                return $jsonResponse;
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Email Template data
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   09 Sept 2020
     * @return json response
     */
    public function getEmailTemplateData($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Email Template', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $quotationId = $allPostPutVars['quote_id'];
        $templateType = $allPostPutVars['template_type'];
        if ($quotationId !=''  && $quotationId > 0 &&$templateType != '') {
            $quotationDetails = $this->getQuotationDetails($request, $response, ['id' => $quotationId], 1);
            //print_r($quotationDetails);exit;
            $templateData = $this->bindEmailTemplate($templateType, $quotationDetails, $getStoreDetails);
            $templateData = $templateData[0]; 
            $jsonResponse = [
                'status' => 1,
                'data' => $templateData,
            ];
        }
        
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: All quotation Ids
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   15 dec 2020
     * @return json response
     */
    public function getAllQuotationIds($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Ids', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $quotationInit = new Quotations();
        $getIds = $quotationInit->select('xe_id')->where('store_id', $getStoreDetails['store_id']);
        if ($getIds->count() > 0) {
            $getIdData = $getIds->get();
            $jsonResponse = [
                'status' => 1,
                'data' => $getIdData,
            ];
        } else {
            $jsonResponse = [
                'status' => 1,
                'data' => []
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET : check if customer is existes or not in store
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $returnType Response return type
     *
     * @author debashrib@riaxe.com
     * @date   09 Sept 2020
     * @return json response
     */
    public function checkForCustomer($request, $response, $returnType = 0)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Customer Check', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $quotationId = $request->getQueryParam('quote_id');
        $customerId = $request->getQueryParam('customer_id');
        $shippingId = ($request->getQueryParam('shipping_id') != '') ? $request->getQueryParam('shipping_id') : '-1';
        $quotationInit = new Quotations();
        $customersControllerInit = new CustomersController();
        if ($quotationId != '' && $quotationId > 0) {
            if ($customerId != '' && $customerId > 0) {
                $customerDetails = $customersControllerInit->getQuoteCustomerDetails($customerId, $getStoreDetails['store_id'], $shippingId, true);
                if (!empty($customerDetails)) {
                    $getQuotations = $quotationInit->select('xe_id', 'customer_name', 'customer_email')
                        ->where('xe_id', $quotationId);
                    if ($getQuotations->count() > 0) {
                        $getQuotationData = json_clean_decode($getQuotations->first(), true);
                        if ($getQuotationData['customer_name'] == '' || ($getQuotationData['customer_name'] != $customerDetails['customer']['name']) || $getQuotationData['customer_email'] == '' || ($getQuotationData['customer_email'] != $customerDetails['customer']['email'])) {
                            $updateData = [
                                'customer_name' => $customerDetails['customer']['name'],
                                'customer_email' => $customerDetails['customer']['email'],
                                'customer_availability' => 0
                            ];
                            $quotationInit->where('xe_id', $quotationId)
                                ->update($updateData);
                        }
                    }
                    $jsonResponse = [
                        'status' => 1,
                        'data' => true,
                    ];

                } else {
                    $updateData = [
                        'customer_name' => '',
                        'customer_email' => '',
                        'customer_availability' => 1
                    ];
                    $quotationInit->where('xe_id', $quotationId)
                        ->update($updateData);
                    $jsonResponse = [
                        'status' => 1,
                        'data' => false,
                    ];
                }
            } else if ($customerId == '' || $customerId == null) {
                $jsonResponse = [
                    'status' => 1,
                    'data' => true,
                ];
            }
            
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }


     /**
     * GET : Quotation Item decoration details
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   23 feb 2020
     * @return json
     */
    public function getUploadedDecorationDetails($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Decoration', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        //Get all measurment units
        $appUnitInit = new AppUnit();
        $initAppUnit = $appUnitInit->select('xe_id', 'name');
        $measurementUnitArr = $initAppUnit->get();
        $measurementUnitArr = json_clean_decode($measurementUnitArr, true);
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $itemId = $request->getQueryParam('item_id');

            if ($quotationId != '' && $quotationId > 0 && $itemId != '' && $itemId > 0) {
                $quoteItemsInit = new QuotationItems();
                $quoteItemFilesInit = new QuotationItemFiles();
                $variantsInit = new QuotationItemVariants();
                $getItem = $quoteItemsInit->where(['quote_id' => $quotationId, 'xe_id' => $itemId]);
                if ($getItem->count() > 0) {
                    //Get quotation item details
                    $getItemData = $getItem->first();
                    $getItemData = json_clean_decode($getItemData, true);
                    $productId = $getItemData['product_id'];
                    //Get quotation item
                    $uploadedDataArr = [];
                    $uploadedData = $quoteItemFilesInit->where('item_id', $itemId);
                    if ($uploadedData->count() > 0) {
                        $uploadedDataArr = $uploadedData->get();
                        $uploadedDataArr = json_clean_decode($uploadedDataArr, true);
                    }
                    //Get Product decoration details
                    $decorationsInit = new ProductDecorationsController();
                    $productDecorationArr = $decorationsInit->getDecorationDetail($request, $response, ['product_id' => $productId], 1); 
                    $productDecorationArr = $productDecorationArr['data'];
                    //Get measurement unit name
                    $measurementUnitId = $productDecorationArr['scale_unit_id'];
                    $unitArr = array_filter($measurementUnitArr, function ($item) use ($measurementUnitId) {
                        return $item['xe_id'] == $measurementUnitId;
                    });
                    $unitArr = $unitArr[array_keys($unitArr)[0]];
                    $measurementUnitName = $unitArr['name'];
                    if ($productDecorationArr['is_variable_decoration'] == 1) {
                        //Design hight and width
                        $extraDataValue = $uploadedDataArr[0]['extra_data'];
                        $extraDataArr = json_clean_decode($extraDataValue, true);
                        $uploadedDecoratedData = [
                            'preview_file' => path('read', 'quotation') . $quotationId . '/' . $itemId . '/' . $uploadedDataArr[0]['side_id'] . '/' . $uploadedDataArr[0]['preview_file'],
                            'design_file' => path('read', 'quotation') . $quotationId . '/' . $itemId . '/' . $uploadedDataArr[0]['side_id'] . '/' . $uploadedDataArr[0]['file'],
                            'decoration_area_id' => $uploadedDataArr[0]['decoration_area_id'],
                            'print_method_id' => $uploadedDataArr[0]['print_method_id'],
                            'decoration_settings_id' => $uploadedDataArr[0]['decoration_settings_id'],
                            'design_height' => (!empty($extraDataArr) && isset($extraDataArr['design_height']) && $extraDataArr['design_height'] != '') ? $extraDataArr['design_height'] : '',
                            'design_width' => (!empty($extraDataArr) && isset($extraDataArr['design_width']) && $extraDataArr['design_width'] != '') ? $extraDataArr['design_width'] : '',
                            'measurement_unit' => $measurementUnitName,
                        ];
                        $productDecorationArr['product_decoration']['uploaded_decorated_data'] = $uploadedDecoratedData;
                    } else {
                        $allSideArr = $productDecorationArr['sides'];
                        $type =  gettype($allSideArr);
                        if ($type == 'object') {
                            $allSideArr = json_clean_decode($allSideArr, true);
                        }
                        $finalSideArr = [];
                        if (!empty($allSideArr)) {
                            foreach ($allSideArr as $sideKey => $sideArr) {
                                $sideId = $sideArr['xe_id'];
                                $tempSideArr = $sideArr;
                                $finalDecoData = [];
                                foreach ($sideArr['product_decoration_setting'] as $productDecorationSetting) {
                                    $tempDecoData = $productDecorationSetting;
                                    $decorationSettingsId = $productDecorationSetting['xe_id'];
                                    $uploadedDecoData = array_filter($uploadedDataArr, function ($item) use ($decorationSettingsId) {
                                        return $item['decoration_settings_id'] == $decorationSettingsId;
                                    });
                                    $uploadedDecoData = $uploadedDecoData[array_keys($uploadedDecoData)[0]];
                                    if (!empty($uploadedDecoData)) {
                                        //Design hight and width
                                        $extraDataValue = $uploadedDecoData['extra_data'];
                                        $extraDataArr = json_clean_decode($extraDataValue, true);
                                        $uploadedDecoratedData = [
                                            'preview_file' => path('read', 'quotation') . $quotationId . '/' . $itemId . '/' . $sideId . '/' . $uploadedDecoData['preview_file'],
                                            'design_file' => path('read', 'quotation') . $quotationId . '/' . $itemId . '/' . $sideId . '/' . $uploadedDecoData['file'],
                                            'decoration_area_id' => $uploadedDecoData['decoration_area_id'],
                                            'print_method_id' => $uploadedDecoData['print_method_id'],
                                            'decoration_settings_id' => $uploadedDecoData['decoration_settings_id'],
                                            'design_height' => (!empty($extraDataArr) && isset($extraDataArr['design_height']) && $extraDataArr['design_height'] != '') ? $extraDataArr['design_height'] : '',
                                            'design_width' => (!empty($extraDataArr) && isset($extraDataArr['design_width']) && $extraDataArr['design_width'] != '') ? $extraDataArr['design_width'] : '',
                                            'measurement_unit' => $measurementUnitName,
                                        ];
                                        $tempDecoData['uploaded_decorated_data'] = $uploadedDecoratedData;
                                        array_push($finalDecoData, $tempDecoData);
                                    }
                                }
                                
                                $tempSideArr['product_decoration_setting'] = (!empty($finalDecoData)) ? $finalDecoData : $sideArr['product_decoration_setting'];
                                array_push($finalSideArr, $tempSideArr);
                            }
                        }
                        $productDecorationArr['sides'] = $finalSideArr;
                    }
                }
                $jsonResponse = [
                    'status' => 1,
                    'data' => $productDecorationArr,
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }


    /**
     * Quotation Folder delete with its file
     *
     * @param $dir  folder path
     *
     * @author debashrib@riaxe.com
     * @date   23 feb 2020
     * @return json
     */
    private function deleteQuoteFolder($dir)
    {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file))
                $this->deleteQuoteFolder($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }

    /**
     * GET : Quotation data assigned to respective customer
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   24 feb 2020
     * @return json
     */
    public function quoteAssignedToCustomer($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Customer Quotation', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        $userType = $request->getQueryParam('user_type');
        $userId = $request->getQueryParam('user_id');
        
        if (!empty($args['customer_id'])) {
            $customerId = to_int($args['customer_id']);

            if ($customerId != '' && $customerId > 0) {
                $quotationInit = new Quotations();
                $quotationData = $quotationInit->join(
                    'production_status',
                    'quotations.status_id',
                    '=',
                    'production_status.xe_id')
                    ->select('quotations.xe_id', 'quotations.quote_id', 'quotations.title', 'quotations.quote_total', 'quotations.status_id', 'quotations.created_at', 'production_status.status_name as quote_status', 'production_status.color_code', DB::raw("(SELECT SUM(`quantity`) as total_quantity FROM `quote_items` WHERE quote_id = quotations.xe_id) as total_quantity"))
                    ->where([
                    'quotations.customer_id' => $customerId,
                    'quotations.store_id' => $getStoreDetails['store_id']
                    ]);
                if ($userType == 'agent') {
                    $quotationData->where('agent_id', $userId);
                }  
                $quotationData->orderBy('quotations.created_at', 'DESC');
                $totalCount = $quotationData->count();
                if ($totalCount > 0) {
                    $quotationDataArr = json_clean_decode($quotationData->get(), true);
                    $jsonResponse = [
                        'status' => 1,
                        'total' => $totalCount,
                        'data' => $quotationDataArr,
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

     /**
     * GET: Quotation request data from tool
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author diana@imprintnext.com
     * @date   06 July 2021
     * @return json response
     */
    public function getRequestQuotationData($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Request Data', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        if (!empty($args['id'])) {
            $quotationId = to_int($args['id']);
            $quotationInit = new Quotations();
            $getQuotations = $quotationInit
                ->select('quote_id', 'quote_source', 'quotation_request_id')
                ->where(
                [
                    'store_id' => $getStoreDetails['store_id'],
                    'xe_id' => $quotationId,
                ]);
            $totalCounts = $getQuotations->count();
            if ($totalCounts > 0) {
                $quotationData = $getQuotations->first();
                $quotationData = json_clean_decode($quotationData, true);
                //Get quotation request data
                $quotationRequestData = [];
                if ($quotationData['quote_source'] == 'tool' && $quotationData['quotation_request_id'] != '' && $quotationData['quotation_request_id'] > 0) {
                    $quotationRequestFormValuesInit = new QuotationRequestFormValues();
                    $requestFormData = $quotationRequestFormValuesInit->select('form_key', 'form_value', 'form_type')->where('quote_id', $quotationData['quote_id']);
                    if ($requestFormData->count() > 0) {
                        $quotationRequestData = json_clean_decode($requestFormData->get(), true);
                        $finalQuotationRequestData = [];
                        foreach ($quotationRequestData as $requestData) {
                            $tempQuotationRequestData = $requestData;
                            $tempQuotationRequestData['is_file_type'] = false;
                            if ($requestData['form_type'] == 'file') {
                                $fileArr = explode(', ', $requestData['form_value']);
                                if (count($fileArr) > 1) {
                                    foreach ($fileArr as $multipleFile) {
                                        $multipleFileArr[] = path('read', 'quotation_request') . $multipleFile;
                                    }
                                    $tempQuotationRequestData['form_value'] = $multipleFileArr;

                                } else {
                                    $tempQuotationRequestData['form_value'] = path('read', 'quotation_request') . $fileArr[0];
                                }
                                $tempQuotationRequestData['is_file_type'] = true;
                            }
                            unset($tempQuotationRequestData['form_type']);
                            array_push($finalQuotationRequestData, $tempQuotationRequestData);
                        }
                    }
                }
                $jsonResponse = [
                    'status' => 1,
                    'data' => $finalQuotationRequestData,
                ];
                    
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

}