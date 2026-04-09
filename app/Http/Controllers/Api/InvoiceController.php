<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Company;
use App\TaxTotal;
use App\InvoiceLine;
use App\PaymentForm;
use App\TypeDocument;
use App\PaymentMethod;
use App\AllowanceCharge;
use App\LegalMonetaryTotal;
use Illuminate\Http\Request;
use App\Traits\DocumentTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvoiceRequest;
use Stenfrank\UBL21dian\XAdES\SignInvoice;
use Stenfrank\UBL21dian\Templates\SOAP\SendBillAsync;
use Stenfrank\UBL21dian\Templates\SOAP\SendTestSetAsync;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use DocumentTrait;

    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\InvoiceRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(InvoiceRequest $request)
    {
        // User
        //  return 'asdasd';

        $user = auth()->user();

        $cufe_propio = $request->cufe_propio;

        $healt_sector = $request->healt_sector;

        // User company
        $company = $user->company;

        // Type document
        $typeDocument = TypeDocument::findOrFail($request->type_document_id);

        // Customer
        $customerAll = collect($request->customer);
        $customer = new User($customerAll->toArray());


        // Customer company
        $customer->company = new Company($customerAll->toArray());
        //return response($customer);
        //return [$customer];
        // Resolution
        $request->resolution->number = $request->number;
        $request->resolution->next_consecutive = $request->number;
        $resolution = $request->resolution;

        // Date time
        $date = $request->date;
        $time = $request->time;

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

        // Legal monetary totals
        $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);

        // Invoice lines
        $invoiceLines = collect();
        foreach ($request->invoice_lines as $invoiceLine) {
            $invoiceLines->push(new InvoiceLine($invoiceLine));
        }

        // Create XML
        $invoice = $this->createXML(compact(
            'user',
            'company',
            'customer',
            'taxTotals',
            'resolution',
            'paymentForm',
            'typeDocument',
            'invoiceLines',
            'allowanceCharges',
            'legalMonetaryTotals',
            'date',
            'time',
            'cufe_propio',
            'healt_sector'
        ));

        // Debug: Log XML generado antes de firmar (solo para facturas NoPos Mipres - FENP)
        if (isset($resolution->prefix) && $resolution->prefix === 'FENP' || $resolution->prefix === 'FEEP' && isset($healt_sector) && !empty($healt_sector)) {
            Log::info('XML generado antes de firmar - Factura NoPos Mipres', [
                'factura' => $resolution->prefix . $request->number,
                'xml' => $invoice->saveXML(),
                'healt_sector' => $healt_sector
            ]);
        }

        // Signature XML
        $signInvoice = new SignInvoice($company->certificate->path, $company->certificate->password);
        $softwareId = $request->software_id ?? $company->software->identifier;
        $softwarePin = $request->software_pin ?? $company->software->pin;
        $signInvoice->softwareID = $softwareId;
        $signInvoice->pin = $softwarePin;
        $signInvoice->technicalKey = $resolution->technical_key;

        $sendBillAsync = new SendBillAsync($company->certificate->path, $company->certificate->password);
        $sendBillAsync->To = $company->software->url;
        $sendBillAsync->fileName = "fv{$request->file}.xml";
        // Firmar XML
        $signedInvoice = $signInvoice->sign($invoice);

        // Debug: Log XML firmado (solo para facturas NoPos Mipres - FENP)
        if (isset($resolution->prefix) && $resolution->prefix === 'FENP' && isset($healt_sector) && !empty($healt_sector)) {
            Log::info('XML firmado - Factura NoPos Mipres', [
                'Faactura' => $signedInvoice->getDocument()->saveXML(),
                // 'cufe' => $signInvoice->getCufe()
            ]);
        }

        $sendBillAsync->contentFile = $this->zipBase64($company, $resolution, $signedInvoice, $request->file);

        // Debug: Log respuesta DIAN (solo para facturas NoPos Mipres - FENP)
        $responseDian = $sendBillAsync->signToSend()->getResponseToObject();
        if (isset($resolution->prefix) && $resolution->prefix === 'FENP' && isset($healt_sector) && !empty($healt_sector)) {
            Log::info('Respuesta DIAN - Factura NoPos Mipres', [
                'factura' => $resolution->prefix . $request->number,
                'response' => $responseDian
            ]);
        }

        // Verificar si hay errores en la respuesta de DIAN
        // getResponseToObject() devuelve un objeto stdClass, usar notación de objeto
        $result = isset($responseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult)
            ? $responseDian->Envelope->Body->SendBillSyncResponse->SendBillSyncResult
            : null;
        $isValid = ($result && isset($result->IsValid)) ? $result->IsValid : "false";
        $statusMessage = ($result && isset($result->StatusMessage)) ? (string)$result->StatusMessage : "";
        $errorMessages = ($result && isset($result->ErrorMessage)) ? $result->ErrorMessage : [];

        // Determinar si hay errores
        $hasErrors = false;
        $errorText = "";

        if ($isValid !== "true") {
            $hasErrors = true;
        }

        if (!empty($errorMessages)) {
            $hasErrors = true;
            if (is_array($errorMessages)) {
                $errorText = implode(" - ", array_filter($errorMessages, function ($e) {
                    return !empty($e) && (is_string($e) || (is_array($e) && !empty($e)));
                }));
            } elseif (is_object($errorMessages)) {
                // Si es un objeto, convertir a string
                $errorText = json_encode($errorMessages);
            } else {
                $errorText = (string)$errorMessages;
            }
        }

        // Si el StatusMessage indica error, también considerar como error
        if (!empty($statusMessage) && (
            stripos($statusMessage, "error") !== false ||
            stripos($statusMessage, "rechazado") !== false ||
            stripos($statusMessage, "invalid") !== false ||
            stripos($statusMessage, "rejected") !== false
        )) {
            $hasErrors = true;
            if (!empty($errorText)) {
                $errorText .= " - " . $statusMessage;
            } else {
                $errorText = $statusMessage;
            }
        }

        return [
            'message' => $hasErrors
                ? "Error al procesar {$typeDocument->name} #{$resolution->prefix}{$request->number}: {$errorText}"
                : "{$typeDocument->name} #{$resolution->prefix}{$request->number} generada con éxito",
            'cufe' =>  $signInvoice->getCufe(),
            'ResponseDian' => $responseDian,
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
            'hasErrors' => $hasErrors,
            'errorMessage' => $hasErrors ? $errorText : null,
            // Incluir XML sin firmar en respuesta para debug (solo facturas FENP con sector salud)
            'xml_preview' => (isset($resolution->prefix) && $resolution->prefix === 'FENP' && isset($healt_sector) && !empty($healt_sector)) ? $invoice->saveXML() : null,
        ];
    }

    /**
     * Test set store.
     *
     * @param \App\Http\Requests\Api\InvoiceRequest $request
     * @param string                                $testSetId
     *
     * @return \Illuminate\Http\Response
     */
    public function testSetStore(InvoiceRequest $request, $testSetId)
    {
        // User

        $user = auth()->user();

        // User company
        $company = $user->company;

        // Type document
        $typeDocument = TypeDocument::findOrFail($request->type_document_id);

        // Customer
        $customerAll = collect($request->customer);
        $customer = new User($customerAll->toArray());

        // Customer company
        $customer->company = new Company($customerAll->toArray());

        // Resolution
        $request->resolution->number = $request->number;
        $request->resolution->next_consecutive = $request->number;
        $resolution = $request->resolution;

        // Date time
        $date = $request->date;
        $time = $request->time;

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

        // Legal monetary totals
        $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);

        // Invoice lines
        $invoiceLines = collect();
        foreach ($request->invoice_lines as $invoiceLine) {
            $invoiceLines->push(new InvoiceLine($invoiceLine));
        }


        // Create XML
        $invoice = $this->createXML(compact('user', 'company', 'customer', 'taxTotals', 'resolution', 'paymentForm', 'typeDocument', 'invoiceLines', 'allowanceCharges', 'legalMonetaryTotals', 'date', 'time'));

        // Signature XML
        $signInvoice = new SignInvoice($company->certificate->path, $company->certificate->password);
        $softwareId = $request->software_id ?? $company->software->identifier;
        $softwarePin = $request->software_pin ?? $company->software->pin;
        $signInvoice->softwareID = $softwareId;
        $signInvoice->pin = $softwarePin;
        $signInvoice->technicalKey = $resolution->technical_key;

        $sendTestSetAsync = new SendTestSetAsync($company->certificate->path, $company->certificate->password);
        $sendTestSetAsync->To = $company->software->url;
        $sendTestSetAsync->fileName = "fv{$request->file}.xml";;
        $sendTestSetAsync->contentFile = $this->zipBase64($company, $resolution, $signInvoice->sign($invoice), $request->file);
        $sendTestSetAsync->testSetId = $testSetId;

        return [
            'message' => "{$typeDocument->name} #{$request->number} generada con éxito",
            'ResponseDian' => $sendTestSetAsync->signToSend()->getResponseToObject(),
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ];
    }
}
