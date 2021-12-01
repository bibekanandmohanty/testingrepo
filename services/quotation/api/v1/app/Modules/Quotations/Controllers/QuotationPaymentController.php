<?php
/**
 * Manage Quotation Payment
 *
 * PHP version 5.6
 *
 * @category  Quotation_Payment
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Quotations\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Quotations\Controllers\QuotationController as QuotationController;
use App\Modules\Quotations\Models\QuotationPayment;
use App\Modules\Quotations\Models\Quotations;
use App\Modules\Quotations\Models\ProductionStatus;

/**
 * Quotations Controller
 *
 * @category Quotations
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class QuotationPaymentController extends QuotationController
{
    /**
     * POST: Quotation payment request
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   31 Mar 2019
     * @return json response
     */
    public function createPaymentLink($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Payment', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $getStoreDetails = get_store_details($request);
        // Save Quotation Status Data
        if (isset($allPostPutVars['data']) && $allPostPutVars['data'] != "") {
            $getAllFormData = json_clean_decode($allPostPutVars['data'], true);
            $quotationInit = new Quotations();
            $quotationId = to_int($getAllFormData['quote_id']);
            //Check for quotation
            $quotation = $quotationInit
                ->where('xe_id', $quotationId);
            if ($quotation->count() > 0) {
                $quotationData = $quotation->select('quote_id', 'quote_total')->first()->toArray();
                //Check payment amount should not greater then total amount
                if ($quotationData['quote_total'] >= $getAllFormData['request_payment']) {
                    $quotationDetails = $this->getQuotationDetails($request, $response, ['id' => $quotationId], 1);
                    $updateData = [
                        'request_payment' => $getAllFormData['request_payment'],
                        'invoice_id' => $quotationData['quote_id'],
                        'request_date' => date_time(
                            'today', [], 'string'
                        )
                    ];

                    if ($quotationInit->where('xe_id', $quotationId)->update($updateData)) {
                        //Get quotation details
                        $quotationDetails = $this->getQuotationDetails($request, $response, ['id' => $quotationId], 1);
                        $itemsList = $this->getQuoteItems($request, $response, ['id' => $quotationId], 1);
                        //generate invoice section 
                        $dir = $this->createInvoicePdf($request, $response, $quotationId, $quotationDetails, $itemsList);
                        //Bind email template
                        $templateData = $this->bindEmailTemplate('request_payment', $quotationDetails, $getStoreDetails);
                        $templateData = $templateData[0];
                        $mailResponse = $this->sendQuotationEmail($templateData, $quotationDetails['customer'], [$dir], $getStoreDetails);
                        //Adding to quote log
                        //Get currency from global setting
                        $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
                        $currency = $globalSettingData['currency']['value'];
                        $logData = [
                            'quote_id' => $getAllFormData['quote_id'],
                            'description' => 'Payment request is sent for '.$currency.$getAllFormData['request_payment'],
                            'user_type' => $getAllFormData['user_type'],
                            'user_id' => $getAllFormData['user_id'],
                            'created_date' => date_time(
                                'today', [], 'string'
                            )
                        ];
                        $this->addingQuotationLog($logData);
                        $jsonResponse = [
                            'status' => 1,
                            'url' => $url,
                            'message' => message('Payment', 'saved')
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
     * Send payment link to customer
     *
     * @param $mailData  Mail data Array
     * @param $url Payment URL
     *
     * @author debashrib@riaxe.com
     * @date   09 Apr 2019
     * @return json response
     */

    public function sendPaymentlink($mailData, $url)
    {
        $emailBody = 'Payment Link : <a href="'.$url.'" target="_blank">'.$url.'</a>';
        $mailContaint = [
            'from'=>['email'=> $mailData['from_email'],
                'name'=> $mailData['from_name']],
            'recipients'=> ['to'=>['email'=> $mailData['customer_email'],
                'name'=>$mailData['customer_name']],
                'reply_to'=>['email'=> $mailData['from_email'],
                    'name'=> $mailData['from_name']],
            ],
            'attachments'=>[$mailData['invoice_file']],
            'subject'=>'Quotation (#'.$quotationData['quote_id'].') Payment Link',
            'body'=> $emailBody,
        ];
        $mailResponse = mail($mailContaint);
        return $mailResponse;
    }

    /**
     * Generate Invoice
     *
     * @param $paymentId  Payment Id 
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   09 Apr 2019
     * @return json response
     */

    public function generateInvoice($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Invoice Download', 'error')
        ];
        $result = false;
        if (!empty($args['id']) && $args['id'] > 0) {
            $quotationId = $args['id'];
            $quotationInit = new Quotations();
            //Check for quotation
            $quotation = $quotationInit
                ->where('xe_id', $quotationId);
            if ($quotation->count() > 0) {
                $quotationData = $quotation->select('quote_id')->first()->toArray();
                $updateData = [
                    'invoice_id' => $quotationData['quote_id']
                ];
                $quotationInit->where('xe_id', $quotationId)->update($updateData);
            }
            $quotationDetails = $this->getQuotationDetails($request, $response, $args, 1);
            $itemsList = $this->getQuoteItems($request, $response, $args, 1);
            if ($dir= $this->createInvoicePdf($request, $response, $quotationId, $quotationDetails, $itemsList)) {
                //Download file in local system
                if (file_download($dir)) {
                    $result = true;
                    $serverStatusCode = OPERATION_OKAY;
                    $jsonResponse = [
                        'status' => 1
                    ];
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
     * @param $itemsList    Quotation Item List
     *
     * @author debashrib@riaxe.com
     * @date   15 Apr 2019
     * @return pdf file path
     */

    private function createInvoicePdf($request, $response, $quotationId, $quotationDetails, $itemsList) 
    {
        if (!empty($quotationId) && $quotationId > 0) {
          $getStoreDetails = get_store_details($request);
          if (!empty($_REQUEST['_token'])) {
                 $getToken = $_REQUEST['_token'];
            } else {
                $getToken = '';
            }
            //Get currency from global setting
            $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
            $currency = $globalSettingData['currency']['unicode_character'];
            //Get email setting data for sending email
            $emailSettingData = $this->getProductionSetting($request, $response, ['module_id' => 1, 'return_type' => 1]);
            $emailSettingData = $emailSettingData['data'];
            $customerName = ($quotationDetails['customer']['name'] != '') ? $quotationDetails['customer']['name'] : $quotationDetails['customer']['email'];
            $billingAddressArr = $quotationDetails['customer']['billing_address'];
            $billingAddress = $billingAddressArr['address_1'] != '' ? $billingAddressArr['address_1'].', '. $billingAddressArr['address_2'].'<br/>'.$billingAddressArr['country'].', '.$billingAddressArr['state'].',<br/>'.$billingAddressArr['city'].'-'.$billingAddressArr['postcode'] : '';

            $shippingId = $quotationDetails['shipping_id'];
            $finalShippingArr = array_filter($quotationDetails['customer']['shipping_address'], function ($item) use ($shippingId) {
                return $item['id'] == $shippingId;
            });
            $finalShippingArr = $finalShippingArr[array_keys($finalShippingArr)[0]];
            $shippingAddress = $finalShippingArr['address_1'] != '' ? $finalShippingArr['address_1'].', '. $finalShippingArr['address_2'].'<br/>'.$finalShippingArr['country'].', '.$finalShippingArr['state'].',<br/>'.$finalShippingArr['city'].'-'.$finalShippingArr['postcode'] : '';
            $paid_image = path('read', 'common').'paid.png';

            $html = '<!doctype html>
            <html lang="en-US">

            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            </head>
            <body style="margin: 0; padding: 0;">
                <div style="margin: 0px; padding: 0px; -webkit-box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); box-shadow: 0px 2px 20px 0px rgba(0, 0, 0, 0.06); background: #fff; position: relative; box-sizing: border-box; font-family: Arial, Helvetica, sans-serif;">
            
                <table width="100%" cellspacing="0" cellpadding="0" style="min-width: 100%;">
              <tr>
                <td style="vertical-align: top;">
                  <h3 class="title mb-3">Invoice</h3>
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Invoice Number</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>'.$quotationDetails['invoice_id'].'</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Created On</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>'.date("dS F, Y", strtotime($quotationDetails['created_at'])).'</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Shipping Date</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>'.date("dS F, Y", strtotime($quotationDetails['ship_by_date'])).'</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding: 0 20px 4px 0px;">Delivery Date</td>
                      <td style="padding: 0 20px 4px 0px;">
                        : <strong>'.date("dS F, Y", strtotime($quotationDetails['exp_delivery_date'])).'</strong>
                      </td>
                    </tr>
                  </table>
                </td>
                <td style="vertical-align: top; text-align: right; font-size: 14px;">';
                if ($emailSettingData['company_logo'] != ''){
                  $html .= '<figure style="margin: 0 0 0 auto; width: 120px;">
                    <img alt="logo" src="'.$emailSettingData['company_logo'].'" style="width: 100%;" />
                  </figure>';
                }
                $html .= '<address style="font-size: 14px; line-height: 22px;">
                    '.$emailSettingData['address'].',<br/>
                    '.$emailSettingData['country'].','.$emailSettingData['state'].','.$emailSettingData['city'].'-'.$emailSettingData['zip_code'].',<br/>
                    '.$emailSettingData['sender_email'].'<br/>
                    '.$emailSettingData['phone_number'].'
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
                  <h4 style="margin: 0 0 10px 0;">'.$customerName.'</h4>
                  <address style="font-size: 14px; line-height: 22px;">
                    '.$quotationDetails['customer']['email'].'<br/>
                    '.$quotationDetails['customer']['phone'].'
                  </address>
                </td>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  <small>Billing Address</small>
                  <address>
                    '.$billingAddress.'
                  </address>
                </td>
                <td style="vertical-align: top; font-size: 14px; line-height: 22px;">
                  <small>Shipping Address</small>
                  <address>
                    '.$shippingAddress.'
                  </address>
                </td>';
              }
                if ($quotationDetails['due_amount'] > 0) {
                $html .='<td style="vertical-align: top; text-align: right;">
                  <small>Balance Due (<span style="font-family: DejaVu Sans;">'.$currency.';</span>)</small>
                  <h1 style="margin: 7px 0;"><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$quotationDetails['due_amount'].'</h1>
                </td>';
                } else {
                $html .= '<td style="vertical-align: top; text-align: right;">
                <small>Balance Due (<span style="font-family: DejaVu Sans;">'.$currency.';</span>)</small>
                  <figure style="margin: 0 0 0 auto; width: 120px;">
                    <img alt="logo" src="'.$paid_image.'" style="width: 100%;" />
                  </figure>
                </td>';
               }
                
              $html .='</tr>
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
              $subtotal= 0;
            foreach ($itemsList as $itemKey => $items) {
                $productName = ($items['product_name'] != '') ? $items['product_name'] : 'N/A';
                $slNo = $itemKey+1;
                $backgroundColor = (($itemKey % 2) == 0) ? 'background-color: rgba(0, 0, 0, 0.05);' : '';
                $subtotal = $subtotal + $items['unit_total'];
                if ($items['product_availability'] == true) {
                  $html .= '<tr>
                    <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; '.$backgroundColor.'">'.$slNo.'</td>
                    <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; '.$backgroundColor.'" >'.$productName.'</td>
                    <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; '.$backgroundColor.'">';
                      
                          foreach ($items['product_variant'] as $key => $variants) {
                              if (!empty($variants['attribute'])) { 
                                  $html .= '<span>(';
                                  foreach ($variants['attribute'] as $attribute) {
                                      $html .= $attribute['name'].' / ';
                                  }
                                  $html .= $variants['quantity'].')</span> <br/>';
                              } else {
                                  $html .= 'Simple Product';
                              }
                          }
                      
                    $html .= '</td>
                    <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; '.$backgroundColor.'">';
                          foreach ($items['product_variant'] as $key => $variants) {
                              $html .= '<span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$variants['unit_price'].'<br/>';
                          }

                    $html .= '</td>
                    <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; padding: 0.75rem; text-align: left; border-right: 0; border-top: 0; '.$backgroundColor.'">'.$items['quantity'].'</td>
                    <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-right:1px solid #e3e3e3; padding: 0.75rem; text-align: left; '.$backgroundColor.'"><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$items['design_cost'].'</td>
                    <td valign="top" style="font-weight: 400; border-left:1px solid #e3e3e3; border-right:1px solid #e3e3e3; padding: 0.75rem; text-align: left; '.$backgroundColor.'"><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$items['unit_total'].'</td>
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
                  <h4 style="'.$display.'">Note to Recipient / Terms & Conditions</h4>
                  <p style="font-size: 14px; line-height: 22px; '.$display.'">
                    '.$quotationDetails['note'].'
                  </p>
                </td>
                <td style="width: 50%; text-align: right;">
                  <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 14px;">
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-right:0; border-bottom:0;
                            text-align: right;">Subtotal</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-bottom:0"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$subtotal.'</strong></td>
                    </tr>';
                    if ($quotationDetails['discount_type'] == 'percentage') {
                            $discountAmount = '<span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$subtotal * ($quotationDetails['discount_amount']/100);
                            $showDisPercent = ' ('.$quotationDetails['discount_amount'].'%)';
                        } else {
                            $discountAmount = '<span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$quotationDetails['discount_amount'];
                            $showDisPercent = '';
                        }
                    $html .= '<tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-right:0; border-bottom:0;">Discount('.ucfirst($quotationDetails['discount_type']).')</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-bottom:0;"><strong>'.$discountAmount.$showDisPercent.'</strong></td>
                    </tr>
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; border-right:0; border-bottom:0;">Shipping('.ucfirst($quotationDetails['shipping_type']).')</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3;text-align: right; border-bottom:0;"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$quotationDetails['shipping_amount'].'</strong></td>
                    </tr>';
                    if ($quotationDetails['is_rush'] == '1') {
                        if ($quotationDetails['rush_type'] == 'percentage') {
                            $rush = $subtotal * ($quotationDetails['rush_amount']/100);
                            $rushAmount = number_format($rush, 2, '.', '');
                            $showPercent = ' ('.$quotationDetails['rush_amount'].'%)';
                        } else {
                            $rushAmount = $quotationDetails['rush_amount'];
                            $showPercent = '';
                        }
                        $html .= '<tr>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; border-right:0; text-align: right;">Rush('.ucfirst($quotationDetails['rush_type']).')</td>
                          <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; text-align: right;"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$rushAmount.$showPercent.'</strong></td>
                        </tr>';
                    }
                    $taxAmount = $subtotal*($quotationDetails['tax_amount']/100);
                    $taxAmount = number_format($taxAmount, 2, '.', ''); //
                    $html .= '<tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; border-right:0; text-align: right;">Tax(%)</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; text-align: right;"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$taxAmount.' ('.$quotationDetails['tax_amount'].'%)</strong></td>
                    </tr>
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; border-right:0; text-align: right;">Due</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-bottom:0; text-align: right;"><strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$quotationDetails['due_amount'].'</strong></td>
                    </tr>
                    <tr>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; border-right:0; text-align: right;">Total</td>
                      <td style="padding: 6px 10px; border: 1px solid #e3e3e3; text-align: right; font-size: 20px;">
                        <strong><span style="font-family: DejaVu Sans;">'.$currency.';</span> '.$quotationDetails['quote_total'].'</strong>
                      </td>
                    </tr>
                  </table>
                  <small>
                    (All prices are shown in <span style="font-family: DejaVu Sans;">'.$currency.';</span>)
                  </small>
                </td>
              </tr>
            </table>
            <hr style="border-top: 2px dashed rgba(0, 0, 0, 0.15);margin: 60px 0;"/>
          </div>
        </body></html>';
            $filePath = path('abs', 'quotation').$quotationId.'/';
            $fileNames = create_pdf($html, $filePath, 'INVOICE-'.$quotationDetails['quote_id'], 'portrait');
            if ($fileNames) {
                //Download file in local system
                $dir = $filePath.$fileNames;
                return $dir;
            }
        }
        return false;
    }


    /**
     * GET: Single Quotation payment Details
     *
     * @param $quotationId  Quotation Id
     *
     * @author debashrib@riaxe.com
     * @date   31 Mar 2019
     * @return json response
     */
    public function getQuotationPayment($quotationId)
    {
        $paymentDetails = [];
        if ($quotationId > 0) {
            $paymentInit = new QuotationPayment();
            //Get if any payment made
            $payment = $paymentInit->where('quote_id', $quotationId);
            if ($payment->count() > 0) {
                $paymentDetails = $payment->get()->toArray();
            }
        }
        return $paymentDetails;
    }

    /**
     * POST: Receive Quotation Payment
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   31 Mar 2019
     * @return json response
     */
    public function receivePayment($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Payment', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $allPostPutVars = $forLogData =  json_clean_decode($allPostPutVars['data'], true);

        if ($allPostPutVars['quote_id'] != '' && $allPostPutVars['quote_id'] > 0) {
            $quoteId = to_int($allPostPutVars['quote_id']);
            unset($allPostPutVars['user_type'], $allPostPutVars['user_id']);
            $paymentInit = new QuotationPayment($allPostPutVars);
            if ($paymentInit->save()) {
                //Empty payment amount form quotation
                $quotationInit = new Quotations();
                $updateData = [
                    'request_payment' => '',
                    'request_date' => ''
                ];
                $quotationInit->where('xe_id', $quoteId)
                    ->update($updateData); 
                //Adding to quote log
                //Get currency from global setting
                $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
                $currency = $globalSettingData['currency']['value'];
                $logData = [
                    'quote_id' => $forLogData['quote_id'],
                    'description' => 'Payment is made for '.$currency.$forLogData['payment_amount'].' by '.$forLogData['payment_mode'],
                    'user_type' => $forLogData['user_type'],
                    'user_id' => $forLogData['user_id'],
                    'created_date' => date_time(
                        'today', [], 'string'
                    )
                ];
                $this->addingQuotationLog($logData);
                $quotationDetails = $this->getQuotationDetails($request, $response, ['id' => $quoteId], 1);
                //Bind email template
                $templateData = $this->bindEmailTemplate('receive_payment', $quotationDetails, $getStoreDetails);
                $templateData = $templateData[0];
                $mailResponse = $this->sendQuotationEmail($templateData, $quotationDetails['customer'], [], $getStoreDetails);
                //Get minimum pay amount for convert to order from setting
                $settingData = $this->getProductionSetting($request, $response, ['module_id' => 1, 'return_type' => 1]);
                $settingData = $settingData['data'];
                $isMinimumPercent = $settingData['is_minimum_payment'];
                $minimumPaidAmount = $settingData['minimum_payment_percent'];
                //Check the paid percentage
                $comPaidAmount = 0;
                $paymentInit = new QuotationPayment();
                $paymentDataArr =  $paymentInit->where('quote_id', $quoteId);
                if ($paymentDataArr->count() >= 0) {
                  $isConvertToOrder = false;
                  $paymentData = $paymentDataArr->get()->toArray();
                  $completedStatus = array_filter($paymentData, function($item) {
                      return $item['payment_status'] == 'paid';
                  });
                  $comAmountArr = array_column($completedStatus, 'payment_amount');
                  $comPaidAmount = array_sum($comAmountArr);
                  $quoteTotalAmount = $quotationDetails['quote_total'];
                  $paidPercentage = ($comPaidAmount/$quoteTotalAmount)*100;
                  $lastQuotationStatus = $quotationDetails['status_id'];
                  //If minimum amount paid convert it to order
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
                  if ($lastQuotationStatus != $orderStatusId) {
                    if (($paidPercentage >= $minimumPaidAmount) 
                      && $minimumPaidAmount > 0 && $isMinimumPercent == 1) {
                        $isConvertToOrder = true;
                    }
                  }
                  $jsonResponse = [
                    'status' => 1,
                    'is_convert_to_order' => $isConvertToOrder,
                    'message' => message('Quotation Payment', 'updated')
                  ];
              }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * DELETE: Delete payment
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   08 Apr 2019
     * @return json response wheather data is deleted or not
     */
    public function deletePayment($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Payment', 'error')
        ];
        $paymentId = $args['id'];

        if (!empty($paymentId) && $paymentId > 0) {
            $paymentInit = new QuotationPayment();
            $payment = $paymentInit->where(
                ['xe_id' => $paymentId]
            );

            if ($payment->count() > 0) {
                //Delete Payment
                if ($payment->delete()) {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Quotation Payment', 'deleted'),
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Single Quotation payment Details
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author debashrib@riaxe.com
     * @date   29 May 2019
     * @return json response
     */
    public function getQuotationPaymentLog($request, $response, $args, $returnType = 0)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'data' => [],
            'message' => message('Quotation Payment Log', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        $quotationInit = new Quotations();
        if (!empty($args['id'])) {
            $quoteId = to_int($args['id']);
            $quotation = $quotationInit->where(
                [
                'xe_id' => $quoteId,
                'store_id' => $getStoreDetails['store_id']
            ]);
            if ($quotation->count() > 0) {
                $paymentInit = new QuotationPayment();
                $paymentData =  $paymentInit->where('quote_id', $quoteId);
                $paymentLog = [];
                $comPaidAmount = 0;
                if ($paymentData->count() > 0) {
                    $paymentDataArr = $paymentData->get()->toArray();
                    $completedStatus = array_filter($paymentDataArr, function($item) {
                        return $item['payment_status'] == 'paid';
                    });
                    $comAmountArr = array_column($completedStatus, 'payment_amount');
                    $comPaidAmount = array_sum($comAmountArr);
                    foreach ($paymentDataArr as $payments) {
                        $newPayment = $payments;
                        $newPayment['description'] = "A payment of $".$payments['payment_amount']." is done by ".$payments['payment_mode']."." ; 
                        array_push($paymentLog, $newPayment);
                    }
                }
                $jsonResponse = [
                    'status' => 1,
                    'log_data' => $paymentLog,
                    'paid_amount' => $comPaidAmount
                ];
                if ($returnType == 1) {
                    return $jsonResponse;
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Update received payment
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   29 May 2019
     * @return json response
     */
    public function updatePayment($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Quotation Payment', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $allPostPutVars = $forLogData =  json_clean_decode($allPostPutVars['data'], true);

        if ($allPostPutVars['payment_id'] != '' && $allPostPutVars['payment_id'] > 0) {
            $paymentId = to_int($allPostPutVars['payment_id']);
            unset($allPostPutVars['user_type'], $allPostPutVars['user_id']);
            $paymentInit = new QuotationPayment();
            //Get Old payment data
            $oldPayment = $paymentInit->where('xe_id', $paymentId);
            if ($oldPayment->count() > 0) {
              $oldPaymentData = $oldPayment->get()->toArray();
              $oldPaymentData = $oldPaymentData[0];
              //Update Payment
              unset(
                  $allPostPutVars['payment_id'], 
                  $allPostPutVars['user_type'], 
                  $allPostPutVars['user_id']
              );
              $paymentInit->where('xe_id', $paymentId)
                  ->update($allPostPutVars); 
              //Get currency from global setting
              $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
              $currency = $globalSettingData['currency']['value'];
              //Adding to quote log
              $logData = [
                  'quote_id' => $forLogData['quote_id'],
                  'description' => 'Payment is updated from '.$currency.$oldPaymentData['payment_amount'].'  to '.$currency.$forLogData['payment_amount'],
                  'user_type' => $forLogData['user_type'],
                  'user_id' => $forLogData['user_id'],
                  'created_date' => date_time(
                      'today', [], 'string'
                  )
              ];
              $this->addingQuotationLog($logData);
              //Get quotation details
              $quotationDetails = $this->getQuotationDetails($request, $response, ['id' => $forLogData['quote_id']], 1);
              //Get minimum pay amount for convert to order from setting
              $settingData = $this->getProductionSetting($request, $response, ['module_id' => 1, 'return_type' => 1]);
              $settingData = $settingData['data'];
              $isMinimumPercent = $settingData['is_minimum_payment'];
              $minimumPaidAmount = $settingData['minimum_payment_percent'];
              //Check the paid percentage
              $comPaidAmount = 0;
              $paymentInit = new QuotationPayment();
              $paymentDataArr =  $paymentInit->where('quote_id', $forLogData['quote_id']);
              if ($paymentDataArr->count() >= 0) {
                $isConvertToOrder = false;
                $paymentData = $paymentDataArr->get()->toArray();
                $completedStatus = array_filter($paymentData, function($item) {
                    return $item['payment_status'] == 'paid';
                });
                $comAmountArr = array_column($completedStatus, 'payment_amount');
                $comPaidAmount = array_sum($comAmountArr);
                $quoteTotalAmount = $quotationDetails['quote_total'];
                $paidPercentage = ($comPaidAmount/$quoteTotalAmount)*100;
                $lastQuotationStatus = $quotationDetails['status_id'];
                //If minimum amount paid convert it to order
                if ($lastQuotationStatus != 5) {
                  if (($paidPercentage >= $minimumPaidAmount) 
                    && $minimumPaidAmount > 0 && $isMinimumPercent == 1) {
                      $isConvertToOrder = true;
                  }
                }
                $jsonResponse = [
                    'status' => 1,
                    'is_convert_to_order' => $isConvertToOrder,
                    'message' => message('Quotation Payment', 'updated')
                ];
              }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: PayPal Payment Integration  
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   10 June 2020
     * @return json response wheather data is saved or any error occured
     */
    public function paypalPayment($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Paypal', 'error')
        ];
        $allPostPutVars = $request->getParsedBody();
        $quotationId = to_int($allPostPutVars['quote_id']);
        if ($quotationId != '') {
            $userId = $allPostPutVars['user_id'];
            $userType = $allPostPutVars['user_type'];
            $quotationInit = new Quotations();
            $getOldQuotation = $quotationInit->where('xe_id', $quotationId);
            $payPalURL = '';
            if ($getOldQuotation->count() > 0) {
                $quoteData = $getOldQuotation->first()->toArray();
                //Get email setting data for sending email
                $settingData = $this->getProductionSetting($request, $response, ['module_id' => 1, 'return_type' => 1]);
                $settingData = $settingData['data'];
                $paymentSetting = $settingData['payment_methods'];
                $paypalSettingData = array_filter($paymentSetting, function($item) {
                        return $item['payment_type'] == 'PayPal';
                    });
                $paypalSettingData = $paypalSettingData[0];
                //Get Paypal setting Data
                $sandboxURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
                $liveURL = 'https://www.paypal.com/cgi-bin/webscr';
                //
                $stoken = 'quote_id=' . $quoteData['xe_id'].'&status=success';
                $stoken = base64_encode($stoken);
                $sUrl = 'quotation/quotation-approval?token=' . $stoken;

                $ctoken = 'quote_id=' . $quoteData['xe_id'].'&status=fail';
                $ctoken = base64_encode($ctoken);
                $cUrl = 'quotation/quotation-approval?token=' . $ctoken;

                $returnUrl = API_URL.$sUrl;
                $cancelUrl = API_URL.$cUrl;
                $notifyUrl = BASE_URL.'quotation-payment/paypal-payment/update';
                $paypal_path = ($paypalSettingData['payment_mode'] == 'test') ? $sandboxURL : $liveURL;

                //Insert a row in payment table before payment
                $savePaymentData = [
                    'quote_id' => $quoteData['xe_id'],
                    'payment_amount' => $allPostPutVars['payment_amount'],
                    'payment_mode' => 'online',
                    'payment_status' => 'pending',
                    'payment_date' => date_time(
                                'today', [], 'string'
                            )
                ];
                $paymentInit = new QuotationPayment($savePaymentData); 
                if ($paymentInit->save()) {
                    $lastPaymentId = $paymentInit->xe_id;
                    $paypalParam = array();
                    $paypalParam['cmd'] = '_xclick';
                    $paypalParam['business'] = $paypalSettingData['payment_setting']['merchant_email_id'];
                    //sb-aczhz1781272@personal.example.com 
                    //$paypalSettingData['payment_setting']['merchant_email_id'];
                    $paypalParam['item_name'] = $quoteData['quote_id'];
                    $paypalParam['amount'] = $allPostPutVars['payment_amount'];
                    $paypalParam['quantity'] = 1;
                    $paypalParam['item_number'] = $quoteData['xe_id'];
                    $paypalParam['currency_code'] = 'USD';
                    $paypalParam['custom'] = 'quotation-'.$quoteData['xe_id'].'~:~'.$lastPaymentId.'~:~'.$userId.'~:~'.$userType;
                    $paypalParam['rm'] = 2;
                    $paypalParam['return'] = $returnUrl;
                    $paypalParam['cancel_return'] = $cancelUrl;
                    $paypalParam['notify_url'] = $notifyUrl;
                    $payPalURL = "?";
                    foreach ($paypalParam as $key => $value) {
                        $payPalURL.=$key."=".$value."&";
                    }
                    $payPalURL=substr($payPalURL,0,-1);
                    $jsonResponse = [
                        'status' => 1,
                        'url' =>$paypal_path. $payPalURL
                    ];
                }
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * POST: Update payment data after success
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   10 June 2020
     * @return json response
     */
    public function updatePaypalResponse($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Paypal', 'error')
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $quotationId = to_int($allPostPutVars['quote_id']);
        if (isset($allPostPutVars['custom']) && $allPostPutVars['custom'] != '') {
            $customData = $allPostPutVars['custom'];
            $customDataArr = explode('~:~', $customData);
            $quotation = $customDataArr[0];
            $quotationArr = explode('-', $quotation);
            $quotationId = $quotationArr[1];
            $paymentId = $customDataArr[1];
            $userId = $customDataArr[2];
            $userType = $customDataArr[3];
            $txnId = $allPostPutVars['txn_id'];
            $paymentDate = $allPostPutVars['payment_date'];
            $paymentAmount = $allPostPutVars['payment_gross'];

            //Check if txn id is updated or not for this particular payment
            $paymentInit = new QuotationPayment();
            $checkPayment = $paymentInit->where([
              'xe_id' => $paymentId,
              'payment_status' => 'paid',
              'txn_id' => $txnId,
            ]);
            if ($txnId !='' && $checkPayment->count() == 0) {
              $updateData = [
                  'txn_id' => $txnId,
                  'payment_status' => 'paid'
              ];
              $paymentInit->where('xe_id', $paymentId)->update($updateData); 
              //Get currency from global setting
              $globalSettingData = $this->readSettingJsonFile($getStoreDetails['store_id']);
              $currency = $globalSettingData['currency']['value'];
              //Adding to quote log
              $logData = [
                  'quote_id' => $quotationId,
                  'description' => 'Payment is made for '.$currency.$paymentAmount.' through PayPal. Txn Id: '.$txnId,
                  'user_type' => $userType,
                  'user_id' => $userId,
                  'created_date' => date_time(
                      'today', [], 'string'
                  )
              ];
              $this->addingQuotationLog($logData);
              //Update quotation pdf in asset
              $this->createQuotationPdf($request, $response, ['id' => $quotationId], 1);

              //Get quotation total amount
              $quotationInit = new Quotations();
              $quotationData = $quotationInit->select('quote_total')
                  ->where('xe_id', $quotationId)->get()->toArray();
              $quoteTotalAmount = $quotationData[0]['quote_total'];
              //Get minimum pay amount for convert to order from setting
              $settingData = $this->getProductionSetting($request, $response, ['module_id' => 1, 'return_type' => 1]);
              $settingData = $settingData['data'];
              $isMinimumPercent = $settingData['is_minimum_payment'];
              $minimumPaidAmount = $settingData['minimum_payment_percent'];
              //Check the paid percentage
              $comPaidAmount = 0;
              $paymentDataArr =  $paymentInit->where('quote_id', $quotationId);
              if ($paymentDataArr->count() >= 0) {
                  $paymentData = $paymentDataArr->get()->toArray();
                  $completedStatus = array_filter($paymentData, function($item) {
                      return $item['payment_status'] == 'paid';
                  });
                  $comAmountArr = array_column($completedStatus, 'payment_amount');
                  $comPaidAmount = array_sum($comAmountArr);
                  $paidPercentage = ($comPaidAmount/$quoteTotalAmount)*100;
                  //If minimum amount paid convert it to order
                  if (($paidPercentage >= $minimumPaidAmount) && $minimumPaidAmount > 0 && $isMinimumPercent == 1) {
                      $requestData = [
                          'quote_id' => $quotationId,
                          'user_type' => $userType,
                          'user_id' => $userId
                      ];
                      $jsonResponse = $this->convertQuoteToOrder($request, $response, $requestData);
                      $this->moveQuoteFileToOrder($request, $response, ['id' => $quotationId, 'order_id' => $jsonResponse['data']], 1);
                  }
              }
            }
        }
    }

}
