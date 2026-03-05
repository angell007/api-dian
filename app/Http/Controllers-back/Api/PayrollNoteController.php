<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Company;
use App\TaxTotal;
use App\PaymentForm;
use App\TypeDocument;
use App\PayrollPeriod;
use App\PaymentMethod;
use App\AllowanceCharge;
use App\BillingReference;
use App\LegalMonetaryTotal;
use Illuminate\Http\Request;
use App\Traits\DocumentTrait;
use App\Http\Controllers\Controller;
use App\InvoiceLine as CreditNoteLine;
use App\Http\Requests\Api\PayrollRequest;
use Stenfrank\UBL21dian\XAdES\SignPayrollNote;
use Stenfrank\UBL21dian\Templates\SOAP\SendPayrollAsync;
use Stenfrank\UBL21dian\Templates\SOAP\SendTestSetAsync;
use Carbon\Carbon;

class PayrollNoteController extends Controller
{
   
    const envirom = ['production'=>1, 'debug'=> 2  ];
    use DocumentTrait;

    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\CreditNoteRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // User
       
        $header = collect();
             
        $header->payroll_period = PayrollPeriod::where('name',$request->payroll_period)->firstOrfail()['code'];
          
        $header->date = $request->date ?  $request->date : Carbon::now()->format('Y-m-d') ;
        $header->date_pay = $request->date_pay ?  $request->date_pay : Carbon::now()->format('d-m-Y') ;
        $header->hour = $request->hour ?  $request->hour : Carbon::now()->format('H:i:s') ;
        $header->cune_propio = $request->cune_propio ;
        $header->environment = PayrollController::envirom['production'] ;
        $header->observation =  $request->observation ;
        $header->date_start_period =  $request->date_start_period ;
        $header->date_end_period =  $request->date_end_period ;
        $header->qr = 'https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey='.$header->cune_propio;
        
        $header->note_type = $request->note_type ;
        $header->code_payroll = $request->code_payroll ;
        $header->cune_payroll = $request->cune_payroll ;
        $header->date_payroll = $request->date_payroll ;
        
        
        $header->integration_date = $request->get('integration_date',null);
        
        $header->number =  $request->number ;
        $header->prefix =  $request->prefix ;
        $header->code =  $request->code ; // prefix + numer
        
        $accrued =  $request->accrued;
        $deductions =  $request->deductions;
        $totals =  $request->totals;
        
        $person =  $request->person;
        $person = collect($person);
        //dd($person);
        
        
        $pay = collect( $request->pay );
        
        
        // User
        $user = auth()->user();
    
        $company = $user->company;
        
       
        // Type document
        $typeDocument = TypeDocument::findOrFail($request->type_document_id);
        

        $resolution =  collect();
        $resolution->number =  $request->resolution_number;
        
    
          // Create XML
        $payroll = $this->createXML(compact('user','typeDocument','company' ,'header', 'resolution',
                                        'person' ,'pay' , 'accrued', 'deductions', 'totals'));

    
       // Signature XML
        $signCreditNote = new SignPayrollNote($company->certificate->path, $company->certificate->password );
        $signCreditNote->typeFile = 'Payroll';
        $signCreditNote->softwareID = $company->software_nomina->identifier;
        $signCreditNote->pin = $company->software_nomina->pin;
        $signCreditNote->typePayrollXml = $typeDocument->code;
        $signCreditNote->note_type =  $request->note_type;

        $signCreditNote->DocEmp = $person['identifier'];
        
        
        $sendTestSetAsync = new SendPayrollAsync($company->certificate->path, $company->certificate->password);
        //$sendTestSetAsync = new SendPayrollAsync($company->certificate->path, $company->certificate->password);
        $sendTestSetAsync->To = $company->software_nomina->url;
        $sendTestSetAsync->fileName = "nie{$request->file}.xml";
        $sendTestSetAsync->contentFile = $this->zipBase64Payroll($company, $signCreditNote->sign($payroll),$request->file);
     
        
        

        return response()->json([
            'message' => "{$typeDocument->name} #generada con éxito",
            'cune' =>  $signCreditNote->getCune(),
            'ResponseDian' => $sendTestSetAsync->signToSend()->getResponseToObject(),
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ]);
        
     
    }
    /**
     * Test set store description].
     *
     * @param \App\Http\Requests\Api\CreditNoteRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function testSetStore(PayrollRequest $request, $testSetId)
    {
          $environment = PayrollController::envirom['debug'];
        
        $header = collect();
        
        $header->payroll_period = PayrollPeriod::where('name',$request->payroll_period)->firstOrfail()['code'];
        
        $header->date = $request->date ?  $request->date : Carbon::now()->format('Y-m-d') ;
        $header->date_pay = $request->date_pay ?  $request->date_pay : Carbon::now()->format('d-m-Y') ;
        $header->hour = $request->hour ?  $request->hour : Carbon::now()->format('H:i:s') ;
        $header->cune_propio = $request->cune_propio ;
        
        $header->note_type = $request->note_type ;
        $header->code_payroll = $request->code_payroll ;
        $header->cune_payroll = $request->cune_payroll ;
        $header->date_payroll = $request->date_payroll ;
        
        $header->environment = PayrollController::envirom['debug'] ;
        $header->observation =  $request->observation ;
        $header->date_start_period =  $request->date_start_period ;
        $header->date_end_period =  $request->date_end_period ;
        
        $header->number =  $request->number ;
        $header->prefix =  $request->prefix ;
        $header->code =  $request->code ; // prefix + numer
        
        $accrued =  $request->accrued;
        $deductions =  $request->deductions;
        $totals =  $request->totals;
        
        $person =  $request->person;
        $person = collect($person);
        //dd($person);
        
        
        $pay = collect( $request->pay );
        
        
        // User
        $user = auth()->user();
     
     

        $company = $user->company;
        
       
        // Type document
        $typeDocument = TypeDocument::findOrFail($request->type_document_id);
        
        
          // Resolution
        $request->resolution->number = $request->resolution_number;
        $resolution = $request->resolution;
        
          // Create XML
        $payroll = $this->createXML(compact('user','typeDocument','company' ,'header', 'resolution',
                                        'person' ,'pay' , 'accrued', 'deductions', 'totals'));

        
     #   dd($payrol);
     
        
      
        
       // Signature XML
        $signCreditNote = new SignPayrollNote($company->certificate->path, $company->certificate->password );
        $signCreditNote->typeFile = 'Payroll';
        $signCreditNote->softwareID = $company->software_nomina->identifier;
        $signCreditNote->pin = $company->software_nomina->pin;
        $signCreditNote->typePayrollXml = $typeDocument->code;
        $signCreditNote->note_type =  $request->note_type;

        $signCreditNote->DocEmp = $person['identifier'];
        
        
        $sendTestSetAsync = new SendTestSetAsync($company->certificate->path, $company->certificate->password);
        //$sendTestSetAsync = new SendPayrollAsync($company->certificate->path, $company->certificate->password);
        $sendTestSetAsync->To = $company->software_nomina->url;
        $sendTestSetAsync->fileName = "nie{$request->file}.xml";
        $sendTestSetAsync->contentFile = $this->zipBase64Payroll($company, $signCreditNote->sign($payroll),$request->file);
        $sendTestSetAsync->testSetId = $testSetId;
        
        

        return response()->json([
            'message' => "{$typeDocument->name} #generada con éxito",
            'cune' =>  $signCreditNote->getCune(),
            'ResponseDian' => $sendTestSetAsync->signToSend()->getResponseToObject(),
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ]);
        
     
    }
}
