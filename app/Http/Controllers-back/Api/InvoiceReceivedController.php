<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Company;
use App\TaxTotal;
use App\PaymentForm;
use App\TypeDocument;
use App\TypeDocumentIdentification;
use App\PayrollPeriod;
use App\PaymentMethod;
use App\AllowanceCharge;
use App\BillingReference;
use App\LegalMonetaryTotal;
use Illuminate\Http\Request;
use App\Traits\DocumentTrait;
use App\Http\Controllers\Controller;
use App\InvoiceLine as CreditNoteLine;
use App\Http\Requests\Api\InvoiceReceivedRequest;
use Stenfrank\UBL21dian\XAdES\SignExpressAcceptance;
use Stenfrank\UBL21dian\Templates\SOAP\SendPayrollAsync;
use Stenfrank\UBL21dian\Templates\SOAP\SendExpressAcceptanceAsync;
use Carbon\Carbon;

class InvoiceReceivedController extends Controller
{
   
    const envirom = ['production'=>1, 'debug'=> 2  ];
    use DocumentTrait;

    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\InvoiceReceivedRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(InvoiceReceivedRequest $request)
    {
        // User
        $environment = InvoiceReceivedController::envirom['production'] ;
               
        $date = $request->date ?  $request->date : Carbon::now()->format('Y-m-d') ;
        $time = $request->time ?  $request->time : Carbon::now()->format('H:i:s') ;
       
        $cude_propio = $request->cude_propio ;
        
        $reference = collect();
        $reference->code = $request->reference['code'];
        $reference->cufe = $request->reference['cufe'];
       
        $reference->type_document = TypeDocument::findOrFail($request->reference['type_document_id'])->code;
        // User
        $user = auth()->user();

        $company = $user->company;
        
        // Type document
        $typeDocument = TypeDocument::findOrFail($request->type_document_id);

        $person = collect();

        foreach ($request->person as $key => $value) {
          $person->$key= $value;
        }
        
        $person->type_document_identification=TypeDocumentIdentification::findOrFail($person->type_document_identification_id);

          // Resolution

        $resolution = $request->resolution;
        $code = $request->code;

        $customerAll = collect($request->supplier);

        $customer = new User($customerAll->toArray());
        $customer->company = new Company($customerAll->toArray());
          // Create XML
        $notification = $this->createXML(
          compact('user','typeDocument'
                  ,'company' ,'reference', 'resolution','customer',
                  'time','date','cude_propio','code','environment','person' ));
           
       // Signature XML
        $signNotification = new SignExpressAcceptance($company->certificate->path, $company->certificate->password );
        $signNotification->typeFile = 'ExpressAcceptance';
        $signNotification->softwareID = $company->software->identifier;
        $signNotification->pin = $company->software_nomina->pin;

        
        $sendExpressAcceptanceAsync = new SendExpressAcceptanceAsync($company->certificate->path, $company->certificate->password);
        //$SendExpressAcceptanceAsync = new SendPayrollAsync($company->certificate->path, $company->certificate->password);
        $sendExpressAcceptanceAsync->To = $company->software_nomina->url;
        $sendExpressAcceptanceAsync->fileName = "{$request->file}.xml";
        $sendExpressAcceptanceAsync->contentFile =  $this->zipBase64($company,$resolution, $signNotification->sign($notification),$request->file);
        

        return response()->json([
            'message' => "{$typeDocument->name} #generada con éxito",
            'cune' =>  $signNotification->getCune(),
            'ResponseDian' => $sendExpressAcceptanceAsync->signToSend()->getResponseToObject(),
            'ZipBase64Bytes' => $sendExpressAcceptanceAsync->contentFile,
        ]);
        
     
    }
    /**
     * Test set store description].
     *
     * @param \App\Http\Requests\Api\InvoiceReceivedRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function testSetStore(InvoiceReceivedRequest $request, $testSetId)
    {
     
        $environment = PayrollController::envirom['debug'];
        
        
        $date = $request->date ?  $request->date : Carbon::now()->format('Y-m-d') ;
        $time = $request->time ?  $request->time : Carbon::now()->format('H:i:s') ;
       
        $cude_propio = $request->cude_propio ;
        
        $reference = collect();
        $reference->code = $request->reference['code'];
        $reference->cufe = $request->reference['cufe'];
        $reference->type_document = TypeDocument::findOrFail($request->reference['type_document_id'])->code;
        // User
        $user = auth()->user();

        $company = $user->company;
        
       
        // Type document
        $typeDocument = TypeDocument::findOrFail($request->type_document_id);


     
        $person = collect();

        foreach ($request->person as $key => $value) {
          $person->$key= $value;
        }
        

        $person->type_document_identification=TypeDocumentIdentification::findOrFail($person->type_document_identification_id);
    

        
          // Resolution
       

        $resolution = $request->resolution;
        $code = $request->code;

        $customerAll = collect($request->supplier);

        $customer = new User($customerAll->toArray());
        $customer->company = new Company($customerAll->toArray());
          // Create XML
        $notification = $this->createXML(
          compact('user','typeDocument'
                  ,'company' ,'reference', 'resolution','customer',
                  'time','date','cude_propio','code','environment','person' ));

       // Signature XML
        $signNotification = new SignExpressAcceptance($company->certificate->path, $company->certificate->password );
        $signNotification->typeFile = 'ExpressAcceptance';
        $signNotification->softwareID = $company->software->identifier;
        $signNotification->pin = $company->software_nomina->pin;

        
        
        $sendExpressAcceptanceAsync = new SendExpressAcceptanceAsync($company->certificate->path, $company->certificate->password);
        //$SendExpressAcceptanceAsync = new SendPayrollAsync($company->certificate->path, $company->certificate->password);
        $sendExpressAcceptanceAsync->To = $company->software_nomina->url;
        $sendExpressAcceptanceAsync->fileName = "{$request->file}.xml";
        $sendExpressAcceptanceAsync->contentFile = $this->zipBase64Communication($company, $signNotification->sign($notification),$request->file);
        $sendExpressAcceptanceAsync->testSetId = $testSetId;
        
        

        return response()->json([
            'message' => "{$typeDocument->name} #generada con éxito",
            'cune' =>  $signNotification->getCune(),
            'ResponseDian' => $sendExpressAcceptanceAsync->signToSend()->getResponseToObject(),
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ]);
        
     
    }
}
