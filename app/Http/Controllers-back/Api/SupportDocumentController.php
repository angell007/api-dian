<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Company;
use App\TaxTotal;
use App\PaymentMethod;
use App\PaymentForm;
use App\TypeDocument;
use App\AllowanceCharge;
use App\BillingReference;
use App\LegalMonetaryTotal;
use Illuminate\Http\Request;
use App\Traits\DocumentTrait;
use App\Http\Controllers\Controller;
use App\InvoiceLineSupportDocument;
use App\Http\Requests\Api\SupportDocumentRequest;
use Stenfrank\UBL21dian\XAdES\SignSupportDocument;
use Stenfrank\UBL21dian\Templates\SOAP\SendSupportDocumentAsync;
use Stenfrank\UBL21dian\Templates\SOAP\SendTestSetAsync;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SupportDocumentController extends Controller
{
   
    const envirom = ['production'=>1, 'debug'=> 2  ];
    use DocumentTrait;

    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\SupportDocumentRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(SupportDocumentRequest $request)
    {
        
      $environment = SupportDocumentController::envirom['production'] ;
      $user = auth()->user();
      $company = $user->company;
      $typeDocument = TypeDocument::findOrFail($request->type_document_id);

      $resolution = \App\Resolution::find($request->get('resolution_id'));

      $header = collect();
      $date = $request->date ?  $request->date : Carbon::now()->format('Y-m-d') ;
      $time = $request->hour ?  $request->hour : Carbon::now()->format('H:i:s') ;

      $code =  $request->code ; // prefix + numer
      $cudsPropio = $request->cuds_propio ;
      $dueDate = $request->due_date ;

      $orderReference= $this->makeCollection($request->origin_reference) ;

      $customerAll = collect($request->customer);

      $customer = new User($customerAll->toArray());
      $customer->company = new Company($customerAll->toArray());
      if (empty($customer->company->dv) && $customerAll->get('dv')) {
          $customer->company->dv = $customerAll->get('dv');
      }

       // Payment form default
       $paymentFormAll = (object) array_merge($this->paymentFormDefault, $request->payment_form ?? []);
       $paymentForm = PaymentForm::findOrFail($paymentFormAll->payment_form_id);
       $paymentForm->payment_method_code = PaymentMethod::findOrFail($paymentFormAll->payment_method_id)->code;
       $paymentForm->payment_due_date = $paymentFormAll->payment_due_date ?? null;
       $paymentForm->duration_measure = $paymentFormAll->duration_measure ?? null;

       // Allowance charges
       $allowanceCharges = collect();
       foreach ($request->allowance_charges ?? [] as $allowanceCharge) {
           $allowanceCharges->push(new AllowanceCharge($allowanceCharge));
       }

        // Tax totals
        $taxTotals = collect();
        foreach ($request->tax_totals ?? [] as $taxTotal) {
            $taxTotals->push(new TaxTotal($taxTotal));
        }

        $withholdingTaxTotals = collect();
        foreach ($request->withholding_tax_totals ?? [] as $taxTotal) {
            $withholdingTaxTotals->push(new TaxTotal($taxTotal));
        }

         // Legal monetary totals
         $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);
       
        $invoiceLines = collect();
        foreach ($request->invoice_lines ?? [] as $invoiceLine) {
            $invoiceLines->push(new InvoiceLineSupportDocument($invoiceLine));
        }
        
      //......
        // Create XML
        $notification = $this->createXML(
          compact('user',
          'typeDocument',
          'company',
          'resolution',
          'invoiceLines',
          'date',
          'time',
          'code',
          'environment',
          'cudsPropio',
          'dueDate',
          'orderReference',
          'customer',
          'paymentForm',
          'allowanceCharges',
          'taxTotals',
          'withholdingTaxTotals',
          'legalMonetaryTotals'
          )
        );

     // Signature XML
      $signInvoice = new SignSupportDocument($company->certificate->path, $company->certificate->password );
      $signInvoice->softwareID = $company->software->identifier;
      $signInvoice->pin = $company->software->pin;
      $signInvoice->technicalKey = $resolution->technical_key;
      
      $sendTestSetAsync = new SendSupportDocumentAsync($company->certificate->path, $company->certificate->password);
      $sendTestSetAsync->To = $company->software->url;
      $sendTestSetAsync->fileName = "suport-{$request->file}.xml";
      $sendTestSetAsync->contentFile = $this->zipBase64($company,$resolution, $signInvoice->sign($notification),$request->file);

      $certificateInfo = $this->getCertificateNitInfo($company->certificate->path, $company->certificate->password);
      Log::info('DIAN SupportDocument send', [
          'company_id' => $company->id,
          'company_nit' => $company->identification_number,
          'company_dv' => $company->dv,
          'software_id' => $company->software->identifier ?? null,
          'software_url' => $company->software->url ?? null,
          'resolution_id' => $resolution->id ?? null,
          'type_document_id' => $typeDocument->id ?? null,
          'file' => $request->file,
          'cuds' => $signInvoice->getCuds(),
          'cert_nit' => $certificateInfo['nit'] ?? null,
          'cert_subject_serial' => $certificateInfo['subject']['serialNumber'] ?? null,
          'cert_subject_cn' => $certificateInfo['subject']['CN'] ?? null,
          'cert_error' => $certificateInfo['error'] ?? null,
      ]);

      return response()->json([
          'message' => "{$typeDocument->name} #generada con éxito",
          'cuds' =>  $signInvoice->getCuds(),
          'ResponseDian' => $sendTestSetAsync->signToSend()->getResponseToObject(),
          'ZipBase64Bytes' => base64_encode($this->getZIP()),
      ]);
      
   
        
     
    }
    /**
     * Test set store description].
     *
     * @param \App\Http\Requests\Api\SupportDocumentRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function testSetStore(SupportDocumentRequest $request, $testSetId)
    {
      $environment = SupportDocumentController::envirom['debug'] ;
      $user = auth()->user();
      $company = $user->company;
      $typeDocument = TypeDocument::findOrFail($request->type_document_id);

      $resolution = $request->resolution;


      $header = collect();
      $date = $request->date ?  $request->date : Carbon::now()->format('Y-m-d') ;
      $time = $request->hour ?  $request->hour : Carbon::now()->format('H:i:s') ;

      $code =  $request->code ; // prefix + numer
      $cudsPropio = $request->cuds_propio ;
      $dueDate = $request->due_date ;

      $orderReference= $this->makeCollection($request->origin_reference) ;



      $customerAll = collect($request->customer);

      $customer = new User($customerAll->toArray());
      $customer->company = new Company($customerAll->toArray());

       // Payment form default
       $paymentFormAll = (object) array_merge($this->paymentFormDefault, $request->payment_form ?? []);
       $paymentForm = PaymentForm::findOrFail($paymentFormAll->payment_form_id);
       $paymentForm->payment_method_code = PaymentMethod::findOrFail($paymentFormAll->payment_method_id)->code;
       $paymentForm->payment_due_date = $paymentFormAll->payment_due_date ?? null;
       $paymentForm->duration_measure = $paymentFormAll->duration_measure ?? null;

       // Allowance charges
       $allowanceCharges = collect();
       foreach ($request->allowance_charges ?? [] as $allowanceCharge) {
           $allowanceCharges->push(new AllowanceCharge($allowanceCharge));
       }

        // Tax totals
        $taxTotals = collect();
        foreach ($request->tax_totals ?? [] as $taxTotal) {
            $taxTotals->push(new TaxTotal($taxTotal));
        }

        $withholdingTaxTotals = collect();
        foreach ($request->withholding_tax_totals ?? [] as $taxTotal) {
            $withholdingTaxTotals->push(new TaxTotal($taxTotal));
        }

         // Legal monetary totals
         $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);
       
        $invoiceLines = collect();
        foreach ($request->invoice_lines ?? [] as $invoiceLine) {
            $invoiceLines->push(new InvoiceLineSupportDocument($invoiceLine));
        }
         
      //......
        // Create XML
        $notification = $this->createXML(
          compact('user',
          'typeDocument',
          'company',
          'resolution',
          'invoiceLines',
          'date',
          'time',
          'code',
          'environment',
          'cudsPropio',
          'dueDate',
          'orderReference',
          'customer',
          'paymentForm',
          'allowanceCharges',
          'taxTotals',
          'withholdingTaxTotals',
          'legalMonetaryTotals'
          )
        );

     // Signature XML
      $signInvoice = new SignSupportDocument($company->certificate->path, $company->certificate->password );
      $signInvoice->softwareID = $company->software->identifier;
      $signInvoice->pin = $company->software->pin;
      $signInvoice->technicalKey = $resolution->technical_key;

      
      
      $sendTestSetAsync = new SendSupportDocumentAsync($company->certificate->path, $company->certificate->password);
      //$sendTestSetAsync = new SendSupportDocumentAsync($company->certificate->path, $company->certificate->password);
      $sendTestSetAsync->To = $company->software->url;
      $sendTestSetAsync->fileName = "suport-{$request->file}.xml";
      $sendTestSetAsync->contentFile = $this->zipBase64($company,$resolution, $signInvoice->sign($notification),$request->file);

      $certificateInfo = $this->getCertificateNitInfo($company->certificate->path, $company->certificate->password);
      Log::info('DIAN SupportDocument send (test)', [
          'company_id' => $company->id,
          'company_nit' => $company->identification_number,
          'company_dv' => $company->dv,
          'software_id' => $company->software->identifier ?? null,
          'software_url' => $company->software->url ?? null,
          'resolution_id' => $resolution->id ?? null,
          'type_document_id' => $typeDocument->id ?? null,
          'file' => $request->file,
          'cuds' => $signInvoice->getCuds(),
          'cert_nit' => $certificateInfo['nit'] ?? null,
          'cert_subject_serial' => $certificateInfo['subject']['serialNumber'] ?? null,
          'cert_subject_cn' => $certificateInfo['subject']['CN'] ?? null,
          'cert_error' => $certificateInfo['error'] ?? null,
      ]);

/*         $sendTestSetAsync = new SendTestSetAsync($company->certificate->path, $company->certificate->password);
        $sendTestSetAsync->To = $company->software->url;
        $sendTestSetAsync->fileName = "fv{$request->file}.xml";;
        $sendTestSetAsync->contentFile = $this->zipBase64($company, $resolution, $signInvoice->sign($invoice),$request->file);
        $sendTestSetAsync->testSetId = $testSetId; */

      return response()->json([
          'message' => "{$typeDocument->name} #generada con éxito",
          'cuds' =>  $signInvoice->getCuds(),
          'ResponseDian' => $sendTestSetAsync->signToSend()->getResponseToObject(),
          'ZipBase64Bytes' => base64_encode($this->getZIP()),
      ]);
      
   
     
    }

    private function makeCollection($array)
    {
      if (is_string($array)) {
        $decoded = json_decode($array, true);
        if (json_last_error() === JSON_ERROR_NONE) {
          $array = $decoded;
        }
      }

      if (is_object($array) && !($array instanceof \Traversable)) {
        $array = (array) $array;
      }

      if (!is_iterable($array)) {
        return collect();
      }

      $tempCol = collect();
      foreach ($array as $key => $value) {
        $tempCol->$key = $value;
      }
      return $tempCol;
    }

    private function getCertificateNitInfo($path, $password)
    {
      try {
        $binary = @file_get_contents($path);
        if ($binary === false) {
          return ['error' => 'certificate_read_failed'];
        }
        $certs = [];
        if (!@openssl_pkcs12_read($binary, $certs, $password)) {
          return ['error' => 'certificate_parse_failed'];
        }
        $parsed = @openssl_x509_parse($certs['cert'] ?? null);
        if (!is_array($parsed)) {
          return ['error' => 'certificate_x509_parse_failed'];
        }
        $subject = $parsed['subject'] ?? [];
        $candidateText = implode(' ', array_filter([
          $subject['serialNumber'] ?? null,
          $subject['CN'] ?? null,
        ]));
        $nit = $this->extractNitFromText($candidateText);
        return [
          'nit' => $nit,
          'subject' => $subject,
        ];
      } catch (\Throwable $e) {
        return ['error' => 'certificate_exception'];
      }
    }

    private function extractNitFromText($text)
    {
      if (!$text) {
        return null;
      }
      if (preg_match('/(\\d{7,15})/', $text, $matches)) {
        return $matches[1];
      }
      return null;
    }
}
