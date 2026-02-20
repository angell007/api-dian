<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization, Access-Control-Allow-Origin');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: GET, POST');
// header('Access-Control-Request-Headers: *');

// UBL 2.1
Route::prefix('/ubl2.1')->group(function () {
    // Configuration
    Route::prefix('/config')->group(function () {
        Route::post('/{nit}/{dv?}', 'Api\ConfigurationController@store');
    });
});

Route::middleware('auth:api')->group(function () {
    // UBL 2.1
    Route::prefix('/ubl2.1')->group(function () {
        // Configuration
        Route::prefix('/config')->group(function () {
            Route::put('/software', 'Api\ConfigurationController@storeSoftware');
            Route::put('/certificate', 'Api\ConfigurationController@storeCertificate');
            Route::put('/resolution', 'Api\ConfigurationController@storeResolution');
            Route::put('/environment', 'Api\ConfigurationController@storeEnvironment');
        });

        // Invoice
        Route::prefix('/invoice')->group(function () {
            Route::post('/{testSetId}', 'Api\InvoiceController@testSetStore');
            Route::post('/', 'Api\InvoiceController@store');
        });

        // Credit Notes
        Route::prefix('/credit-note')->group(function () {
            Route::post('/{testSetId}', 'Api\CreditNoteController@testSetStore');
            Route::post('/', 'Api\CreditNoteController@store');
        });

        // Debit Notes
        Route::prefix('/debit-note')->group(function () {
            Route::post('/{testSetId}', 'Api\DebitNoteController@testSetStore');
            Route::post('/', 'Api\DebitNoteController@store');
        });
        
        // Payoroll
        Route::prefix('/payroll')->group(function () {
            Route::post('/{testSetId}', 'Api\PayrollController@testSetStore');
            Route::post('/', 'Api\PayrollController@store');
        });
        
        // Payroll Note
        Route::prefix('/payroll-note')->group(function () {
            Route::post('/{testSetId}', 'Api\PayrollNoteController@testSetStore');
            Route::post('/', 'Api\PayrollNoteController@store');
        });

        //invoice received
        Route::prefix('/invoice-received')->group(function () {
            Route::post('/{testSetId}', 'Api\InvoiceReceivedController@testSetStore');
            Route::post('/', 'Api\InvoiceReceivedController@store');
        });

         //invoice rejected
         Route::prefix('/invoice-rejected')->group(function () {
            Route::post('/{testSetId}', 'Api\RejectDocumentController@testSetStore');
            Route::post('/', 'Api\RejectDocumentController@store');
        });
        
        Route::prefix('/receipt-good-or-service')->group(function () {
            Route::post('/{testSetId}', 'Api\ReceiptGoodOrServiceController@testSetStore');
            Route::post('/', 'Api\ReceiptGoodOrServiceController@store');
        });

        //express Acceptance
        Route::prefix('/express-acceptance')->group(function () {
            Route::post('/{testSetId}', 'Api\ExpressAcceptanceController@testSetStore');
            Route::post('/', 'Api\ExpressAcceptanceController@store');
        });

        //implicit Acceptance
        Route::prefix('/tacit-acceptance')->group(function () {
            
            Route::post('/{testSetId}', 'Api\ImplicitAcceptanceController@testSetStore');
            Route::post('/', 'Api\ImplicitAcceptanceController@store');
        });


         //Soport
         Route::prefix('/support-document')->group(function () {
            Route::post('/{testSetId}', 'Api\SupportDocumentController@testSetStore');
            Route::post('/', 'Api\SupportDocumentController@store');
        });
         //Soport
         Route::prefix('/supportdocument')->group(function () {
        // 
            Route::post('/{testSetId}', 'Api\SupportDocumentController@testSetStore');
            Route::post('/', 'Api\SupportDocumentController@store');
        });
        // Status
        Route::prefix('/status')->group(function () {
            Route::post('/zip/{trackId}', 'Api\StateController@zip');
            Route::post('/document/{trackId}', 'Api\StateController@document');
        });
        Route::prefix('/numbering')->group(function () {
            Route::post('/range/{accountCode}/{accountCodeT}/{softwareCode}', 'Api\StateController@numbering');
        });
        
    });

    // XML para RIPS FEV (invoice y attached)
    Route::prefix('/xml')->group(function () {
        Route::get('/invoice/{resolution_id}/{filename}', 'Api\XmlController@invoice');
        Route::get('/attached/{resolution_id}/{filename}', 'Api\XmlController@attached');
    });
});


Route::post('/testt', function(){
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        return "Cache limpio!";
});
     