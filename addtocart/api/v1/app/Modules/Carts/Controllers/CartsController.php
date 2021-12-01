<?php
/**
 * Manage Carts
 *
 * PHP version 5.6
 *
 * @category  Carts
 * @package   Store
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Carts\Controllers;

use CartStoreSpace\Controllers\StoreCartsController;
use App\Modules\Settings\Models\Setting;
use App\Modules\Carts\Models\Transactions;

/**
 * Carts Controller
 *
 * @category Carts
 * @package  Store
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class CartsController extends StoreCartsController
{
    /**
     * POST: add To Cart
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   06 Jan 2020
     * @return json response wheather data is saved or any error occured
     */
    public function addToCart($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [];
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
                $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != 'off') ? 'https' : 'http';
                $blobURL = "blob:" . $protocol . "://";
                if (strpos($allPostPutVars['design_data'], $blobURL) !== false) {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => "Add to cart failed",
                    ];

                } else {
                    $designData = json_clean_decode($allPostPutVars['design_data'], true);

                    $productId = isset($designData['product_info']['product_id'])
                    && $designData['product_info']['product_id'] != ''
                    ? $designData['product_info']['product_id'] : null;
                    // Prepare array for saving design data
                    $designDetails = [
                        'store_id' => $getStoreDetails['store_id'],
                        'product_setting_id' => (isset($designData['product_settings_id'])
                            && $designData['product_settings_id'] > 0)
                        ? $designData['product_settings_id'] : null,
                        'product_variant_id' => (isset($designData['product_variant_id'])
                            && $designData['product_variant_id'] > 0)
                        ? $designData['product_variant_id'] : null,
                        'product_id' => $productId,
                        'type' => (isset($designData['template_type'])
                            && $designData['template_type'] != "")
                        ? $designData['template_type'] : "cart",
                        'custom_price' => (isset($designData['custome_price'])
                            && $designData['custome_price'] > 0)
                        ? $designData['custome_price'] : 0.00,
                    ];
                    if (isset($designDetails) && !empty($designDetails != '')) {
                        // save design data and get customDesignId
                        $customDesignId = $this->saveDesignData(
                            $designDetails, $allPostPutVars['design_data'], ['directory' => 'carts']
                        );
                        if ($customDesignId > 0) {
                            $jsonResponse = $this->addToStoreCart($request, $response, $customDesignId);
                            $jsonResponse['customDesignId'] = $customDesignId;
                        }
                    }
                }
            }
        } else {
            $jsonResponse = [
                'status' => 0,
                'message' => array('is_Fault' => 1),
            ];
        }
        return response(
            $response, [
                'data' => $jsonResponse, 'status' => $serverStatusCode,
            ]
        );
    }

    /**
     * POST: Add Template Product To Cart
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author radhanatham@riaxe.com
     * @date   15 July 2020
     * @return json response wheather data is saved or any error occured
     */
    public function addTemplateToCart($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $customDesignId = $allPostPutVars['design_id'] ? $allPostPutVars['design_id'] : 0;
        if ($customDesignId) {
            $jsonResponse = $this->addTemplateProductToCart($request, $response, $customDesignId);
            $deisgnStatePath = path('abs', 'design_state') . 'carts';
            $deisgnPreviewPath = path('abs', 'design_preview') . 'carts';
            $deisgnStatePredecoPath = path('abs', 'design_state') . 'predecorators';
            $templatesDesignPrvPath = path('abs', 'design_preview') . 'templates';
            if (file_exists($deisgnStatePredecoPath . '/' . $customDesignId . '.json')) {
                $designStateJson = read_file(
                    $deisgnStatePredecoPath . '/' . $customDesignId . '.json'
                );
                $jsonContent = json_clean_decode($designStateJson, true);
                $productId = $allPostPutVars['product_id'];
                $variantId = $allPostPutVars['variant_id'] ? $allPostPutVars['variant_id'] : $productId;
                $jsonContent['product_info']['product_id'] = $productId;
                $jsonContent['design_product_data'][0]['variant_id'][] = $variantId;
                $status = write_file(
                    $deisgnStatePath . '/' . $customDesignId . '.json', json_encode($jsonContent)
                );
            }
        } else {
            $jsonResponse = [
                'status' => 0,
                'message' => array('is_Fault' => 1),
            ];
        }
        return response(
            $response, [
                'data' => $jsonResponse, 'status' => $serverStatusCode,
            ]
        );
    }

    public function getNameNumberData($request, $response){
        $serverStatusCode = OPERATION_OKAY;
        $customDesignId = $request->getQueryParam('design_id') ? $request->getQueryParam('design_id') : 0;
        $variantID = $request->getQueryParam('variant_id') ? $request->getQueryParam('variant_id') : 0;
        $stateDesignPath = path('abs', 'design_state') . 'carts/' . $customDesignId . '.json';
        $stateJson = json_clean_decode(file_get_contents($stateDesignPath), true);
        $nameNumData = $stateJson['name_number'];
        if (strtolower(STORE_NAME) == "shopify") {
            $storeProductInit = new StoreProductsController();
            $parentVariantID = $storeProductInit->getOriginalVarID($variantID);
            $thisvariantID = $parentVariantID;
        }else{
            $thisvariantID = $variantID; 
        }
        $nameNumberInfo = array();
        foreach ($nameNumData as $number => $record) {
            $thisVariantArr = array_column($record, 'variantId');
            $thisRecordInfo = array();
            if (in_array($thisvariantID, $thisVariantArr)) {
                $nameNumberInfo['fields'] = array_column($record, 'name');
                $nameNumberInfo['placeholder'] = array_column($record, 'placeholder');
                $thisRecordInfo = array_column($record, 'value');
                $nameNumberInfo['values'][] = $thisRecordInfo;
            }
        }
        $lastArrayKey = array_search('Quantity', $nameNumberInfo['fields']);
        $targetItemNum = $lastArrayKey + 1;
        $nameNumberInfo['fields'] = array_slice($nameNumberInfo['fields'], 0, $targetItemNum);
        foreach ($nameNumberInfo['values'] as $key => $value) {
            $nameNumberInfo['values'][$key] = array_slice($value, 0, $targetItemNum);
        }
        return response(
            $response, [
                'data' => $nameNumberInfo, 'status' => $serverStatusCode,
            ]
        );
    }

    public function isNameNumberItem($request, $response){
        $serverStatusCode = OPERATION_OKAY;
        $customDesignId = $request->getQueryParam('design_id') ? $request->getQueryParam('design_id') : 0;
        $variantID = $request->getQueryParam('variant_id') ? $request->getQueryParam('variant_id') : 0;
        $stateDesignPath = path('abs', 'design_state') . 'carts/' . $customDesignId . '.json';
        $stateJson = json_clean_decode(file_get_contents($stateDesignPath), true);
        $nameNumData = $stateJson['name_number'];
        if (!empty($nameNumData)) {
            $nameNumberStaus['name_number'] = true;
        }else{
            $nameNumberStaus['name_number'] = false;
        }
        return response(
            $response, [
                'data' => $nameNumberStaus, 'status' => $serverStatusCode,
            ]
        );
    }

    /**
     * POST: make strie payment for kiosk
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashisd@riaxe.com
     * @date   23 March 2021
     * @return json response of payment status
     */
    public function makeKioskPayment($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        include_once getcwd() . '/app/Dependencies/stripe-php/init.php';
        $allPostPutVars = $request->getParsedBody();
        $data = [];
        $settingInit = new Setting();
        $getSettings = $settingInit->where('setting_key', '=', 'secret_key');
        $kioskSetting = $getSettings->get()->toArray();
        if (empty($kioskSetting) && empty($kioskSetting[0]['setting_value'])) {
            print_r("kiosk setting not found");exit();
        }
        if (!empty($allPostPutVars) && !empty($allPostPutVars['stripe_token'])) {
            $totalPrice = $allPostPutVars['total_price'];
            $STRIPE_API_KEY = $kioskSetting[0]['setting_value'];
            // Retrieve stripe token, card and user info from the submitted form data
            $token = $allPostPutVars['stripe_token'];
            $name = $allPostPutVars['name'];
            $email = $allPostPutVars['email'];
            $itemPrice = $totalPrice;
            $currency = $allPostPutVars['currency'] ? $allPostPutVars['currency'] : 'USD';
            $phone = $allPostPutVars['phone'] ? $allPostPutVars['phone'] : '';
            $address = array('line1' => '510 Townsend St',
                            'postal_code' => '98140',
                            'city' => 'San Francisco',
                            'state' => 'CA',
                            'country' => 'US');
            // Set API key
            \Stripe\Stripe::setApiKey($STRIPE_API_KEY);

            // Add customer to stripe
            try {
                $customer = \Stripe\Customer::create(array('name'=> $name, 'address'=> $address,'email' => $email, 'source' => $token));
            } catch (\Stripe\Exception\CardException $e) {
                // Since it's a decline, \Stripe\Exception\CardException will be caught
                $apiErrorArr[] = $e->getMessage();
                $api_error = $e->getMessage();
            } catch (\Stripe\Exception\RateLimitException $e) {
                // Too many requests made to the API too quickly
                $apiErrorArr[] = $e->getMessage();
                $api_error = $e->getMessage();
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Invalid parameters were supplied to Stripe's API
                $apiErrorArr[] = $e->getMessage();
                print_r($apiErrorArr);exit();
                $api_error = $e->getMessage();
            } catch (\Stripe\Exception\AuthenticationException $e) {
                // Authentication with Stripe's API failed
                // (maybe you changed API keys recently)
                $apiErrorArr[] = $e->getMessage();
                $api_error = $e->getMessage();
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                // Network communication with Stripe failed
                $apiErrorArr[] = $e->getMessage();
                $api_error = $e->getMessage();
            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Display a very generic error to the user, and maybe send
                // yourself an email
                $apiErrorArr[] = $e->getMessage();
                $api_error = $e->getMessage();
            } catch (Exception $e) {
                // Something else happened, completely unrelated to Stripe
                $apiErrorArr[] = $e->getMessage();
                $api_error = $e->getMessage();
            }
            if (empty($apiErrorArr) && $customer) {
                // Convert price to cents
                $itemPriceCents = ($itemPrice * 100);
                // Charge a credit or  card
                try {

                    $charge = \Stripe\Charge::create(array(
                        'customer' => $customer->id,
                        'amount' => $itemPriceCents,
                        'currency' => $currency,
                        'description' => 'kiosk payment'
                    ));
                } catch (\Stripe\Exception\CardException $e) {
                    // Since it's a decline, \Stripe\Exception\CardException will be caught
                    $apiErrorArr[] = $e->getMessage();
                    $api_error = $e->getMessage();
                } catch (\Stripe\Exception\RateLimitException $e) {
                    // Too many requests made to the API too quickly
                    $apiErrorArr[] = $e->getMessage();
                    $api_error = $e->getMessage();
                } catch (\Stripe\Exception\InvalidRequestException $e) {
                    // Invalid parameters were supplied to Stripe's API
                    $apiErrorArr[] = $e->getMessage();
                    $api_error = $e->getMessage();
                } catch (\Stripe\Exception\AuthenticationException $e) {
                    // Authentication with Stripe's API failed
                    // (maybe you changed API keys recently)
                    $apiErrorArr[] = $e->getMessage();
                    $api_error = $e->getMessage();
                } catch (\Stripe\Exception\ApiConnectionException $e) {
                    // Network communication with Stripe failed
                    $apiErrorArr[] = $e->getMessage();
                    $api_error = $e->getMessage();
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    // Display a very generic error to the user, and maybe send
                    // yourself an email
                    $apiErrorArr[] = $e->getMessage();
                    $api_error = $e->getMessage();
                } catch (Exception $e) {
                    // Something else happened, completely unrelated to Stripe
                    $apiErrorArr[] = $e->getMessage();
                    $api_error = $e->getMessage();
                }

                if (empty($apiErrorArr) && $charge) {
                    // Retrieve charge details
                    $chargeJson = $charge->jsonSerialize();
                    // Check whether the charge is successful
                    if ($chargeJson['amount_refunded'] == 0 && empty($chargeJson['failure_code']) && $chargeJson['paid'] == 1 && $chargeJson['captured'] == 1) {
                        // Transaction details
                        $transactionID = $chargeJson['balance_transaction'];
                        $paidAmount = $chargeJson['amount'];
                        $paidAmount = ($paidAmount / 100);
                        $paidCurrency = $chargeJson['currency'];
                        $paymentStatus = $chargeJson['status'];
                        // If the order is successful
                        if ($paymentStatus == 'succeeded') {
                            $dateTime = date('Y-m-d H:i:s');
                            $transactions = [
                                'amount' => $paidAmount,
                                'transaction_id' => $transactionID,
                                'payment_mode' => 'Online',
                                'currency' => $paidCurrency,
                                'updated_at' => $dateTime,
                                'created_at' => $dateTime,
                            ];
                            $saveTransactions = new Transactions($transactions);
                            $saveTransactions->save();
                            $status = 1;
                            $statusMsg = 'Your Payment has been Successful!';
                            $data['transaction_id'] = $saveTransactions->xe_id;
                        } else {
                            $status = 0;
                            $statusMsg = "Your Payment has Failed! 12333";
                        }
                    } else {
                        $status = 0;
                        $statusMsg = "Transaction has been failed!";
                    }
                } else {
                    $status = 0;
                    $statusMsg = "Charge creation failed! $api_error";
                }
            } else {
                $status = 0;
                $statusMsg = "Invalid card details! $api_error";
            }
        } else {
            $status = 0;
            $statusMsg = "Error on form submission.";
        }
        $transactionInfo = [
            'status' => $status,
            'message' => $statusMsg,
            'data' => $data,
        ];
        return response(
            $response, [
                'data' => $transactionInfo, 'status' => $serverStatusCode,
            ]
        );
    }
}
