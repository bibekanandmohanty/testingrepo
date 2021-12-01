<?php
/**
 * This Routes holds all the individual route for the Quotations
 *
 * PHP version 5.6
 *
 * @category  Quotations
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Quotations\Controllers\PurchaseOrderController;
use App\Modules\Quotations\Controllers\QuotationController;
use App\Modules\Quotations\Controllers\QuotationPaymentController;
use App\Modules\Quotations\Controllers\VendorController;

// Instantiate the Container
$container = $app->getContainer();

//Quotation Routes List
$app->group(
    '/quotation', function () use ($app) {
        $app->get('/id', QuotationController::class . ':getQuoteId');
        $app->get('/all-ids', QuotationController::class . ':getAllQuotationIds');
        $app->post('', QuotationController::class . ':saveQuotation');
        $app->get('', QuotationController::class . ':getQuotationList');
        $app->get('/check-customer', QuotationController::class . ':checkForCustomer');
        $app->post('/email-template', QuotationController::class . ':getEmailTemplateData');
        $app->get('/card-view', QuotationController::class . ':getQuotationCardView');
        $app->get('/agent-list', QuotationController::class . ':agentListWithQuote');
        $app->get('/check-settings', QuotationController::class . ':checkSettingForQuote');
        $app->get('/form-attribute', QuotationController::class . ':getFormAttribute');
        $app->post('/duplicate', QuotationController::class . ':duplicateQuotation');
        $app->post('/request', QuotationController::class . ':sendQuotationRequest');
        $app->post('/internal-note', QuotationController::class . ':saveInternalNote');
        $app->post('/conversation', QuotationController::class . ':saveConversations');
        $app->post('/bulk-action', QuotationController::class . ':bulkAction');
        $app->post('/convet-to-order', QuotationController::class . ':convertQuoteToOrder');
        $app->post('/reject', QuotationController::class . ':rejectQuotation');
        $app->post('/{id}', QuotationController::class . ':updateQuotation');
        $app->get('/{id}', QuotationController::class . ':getQuotationDetails');
        $app->delete('/tag/{id}', QuotationController::class . ':deleteQuotationTag');
        $app->delete('/status/{id}', QuotationController::class . ':deleteQuotationStatus');
        $app->post('/assign/{id}', QuotationController::class . ':assignAgent');
        $app->get('/log/{id}', QuotationController::class . ':getQuotationLog');
        $app->post('/status/{id}', QuotationController::class . ':changeQuotationStatus');
        $app->delete('/{id}', QuotationController::class . ':deleteQuotation');
        //$app->get('/download/{id}', QuotationController::class . ':downloadQuotation');
        $app->get('/items/{id}', QuotationController::class . ':getQuotationItemsList');
        $app->get('/item-list/{id}', QuotationController::class . ':getQuoteItems');
        $app->get('/conversation/{id}', QuotationController::class . ':getConversations');
        $app->post('/send-to-customer/{id}', QuotationController::class . ':sendToCustomer');
        $app->get('/create-quote-pdf/{id}', QuotationController::class . ':createQuotationPdf');
        $app->get('/move-order-file/{id}', QuotationController::class . ':moveQuoteFileToOrder');
        $app->post('/conversation/seen-flag', QuotationController::class . ':changeConversationSeenFlag');
        $app->get('/uploaded-decoration/{id}', QuotationController::class . ':getUploadedDecorationDetails');
        $app->get('/customer-relation/{customer_id}', QuotationController::class . ':quoteAssignedToCustomer');
        $app->get('/request-quote/{id}', QuotationController::class . ':getRequestQuotationData');
    }
)->add(new ValidateJWT($container));

//Quotation Payment Routes List
$app->group(
    '/quotation-payment', function () use ($app) {
        $app->post('/link', QuotationPaymentController::class . ':createPaymentLink');
        $app->post('/receive', QuotationPaymentController::class . ':receivePayment');
        $app->post('/update', QuotationPaymentController::class . ':updatePayment');
        $app->post('/paypal-payment', QuotationPaymentController::class . ':paypalPayment');
        $app->get('/generate-invoice/{id}', QuotationPaymentController::class . ':generateInvoice');
        $app->get('/log/{id}', QuotationPaymentController::class . ':getQuotationPaymentLog');
        $app->delete('/{id}', QuotationPaymentController::class . ':deletePayment');
    }
)->add(new ValidateJWT($container));

// Artwork Routes List
$app->group(
    '/artwork-design', function () use ($app) {
        $app->post('', QuotationController::class . ':artWorkDesign');
    }
)->add(new ValidateJWT($container));

$app->post('/quotation-payment/paypal-payment/update', QuotationPaymentController::class . ':updatePaypalResponse');
//download quotation pdf
$app->get('/quotation/download/{id}', QuotationController::class . ':downloadQuotation');
