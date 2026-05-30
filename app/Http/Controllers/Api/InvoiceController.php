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
use App\Traits\DocumentTrait;
use App\Traits\DianSendBillTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvoiceRequest;
use Stenfrank\UBL21dian\XAdES\SignInvoice;
use Stenfrank\UBL21dian\Templates\SOAP\SendBillAsync;
use Stenfrank\UBL21dian\Templates\SOAP\SendBillSync;
use Stenfrank\UBL21dian\Templates\SOAP\SendTestSetAsync;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use DocumentTrait;
    use DianSendBillTrait;

    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\InvoiceRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(InvoiceRequest $request)
    {
        $user = auth()->user();

        $cufe_propio = $request->cufe_propio;
        $healt_sector = $this->normalizeHealtSectorPayload($request->healt_sector ?? null);

        $company = $user->company;
        $this->guardCertificateNit($company);

        $typeDocument = TypeDocument::findOrFail($request->type_document_id);

        $customerAll = collect($request->customer);
        $customer = new User($customerAll->toArray());
        $customer->company = new Company($customerAll->toArray());

        $request->resolution->number = $request->number;
        $request->resolution->next_consecutive = $request->number;
        $resolution = $request->resolution;

        $healt_sector = $this->sectorSaludInteroperabilidadPorPrefijoResolucion($resolution->prefix, $healt_sector);

        if ($this->isDianDebugVerbose()) {
            Log::info('Invoice store', [
                'prefix' => $resolution->prefix,
                'number' => $request->number,
                'healt_sector' => $healt_sector,
                'preview' => $this->isDianPreviewEnabled(),
            ]);
        }

        $date = $request->date;
        $time = $request->time;

        $paymentFormAll = (object) array_merge($this->paymentFormDefault, $request->payment_form ?? []);
        $paymentForm = PaymentForm::findOrFail($paymentFormAll->payment_form_id);
        $paymentForm->payment_method_code = PaymentMethod::findOrFail($paymentFormAll->payment_method_id)->code;
        $paymentForm->payment_due_date = $paymentFormAll->payment_due_date ?? null;
        $paymentForm->duration_measure = $paymentFormAll->duration_measure ?? null;

        $allowanceCharges = collect();
        foreach ($request->allowance_charges ?? [] as $allowanceCharge) {
            $allowanceCharges->push(new AllowanceCharge($allowanceCharge));
        }

        $taxTotals = collect();
        foreach ($request->tax_totals ?? [] as $taxTotal) {
            $taxTotals->push(new TaxTotal($taxTotal));
        }

        $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);

        $invoiceLines = collect();
        foreach ($request->invoice_lines as $invoiceLine) {
            $invoiceLines->push(new InvoiceLine($invoiceLine));
        }

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

        $signInvoice = new SignInvoice($company->certificate->path, $company->certificate->password);
        $softwareId = $request->software_id ?? $company->software->identifier;
        $softwarePin = $request->software_pin ?? $company->software->pin;
        $signInvoice->softwareID = $softwareId;
        $signInvoice->pin = $softwarePin;
        $technicalKey = trim((string) ($request->technical_key ?? ''));
        $signInvoice->technicalKey = $technicalKey !== '' ? $technicalKey : $resolution->technical_key;
        $signedInvoice = $signInvoice->sign($invoice);

        $dom = $signInvoice->getDocument();
        $uuidNodes = $dom->getElementsByTagName('UUID');
        $cufe = ($uuidNodes->length > 0) ? trim($uuidNodes->item(0)->nodeValue ?? '') : '';

        $docId = ($resolution->prefix ?? '') . $request->number;

        if ($this->isDianPreviewEnabled()) {
            return $this->buildDianPreviewResponse(
                'invoice-xml-preview.log',
                'FACTURA',
                $docId,
                $cufe,
                $dom,
                $invoice,
                $healt_sector
            );
        }

        $useSync = filter_var(env('DIAN_USE_SYNC', false), FILTER_VALIDATE_BOOLEAN);

        if ($useSync) {
            $sendBill = new SendBillSync($company->certificate->path, $company->certificate->password);
        } else {
            $sendBill = new SendBillAsync($company->certificate->path, $company->certificate->password);
        }
        $sendBill->To = $company->software->url;
        $sendBill->fileName = "fv{$request->file}.xml";
        $sendBill->contentFile = $this->zipBase64($company, $resolution, $signedInvoice, $request->file);

        $client = $sendBill->signToSend();

        try {
            $responseDian = $client->getResponseToObject();

            if ($this->isDianDebugVerbose()) {
                Log::channel('single')->debug('DIAN SendBill response', [
                    'mode' => $useSync ? 'Sync' : 'Async',
                    'file' => "fv{$request->file}.xml",
                    'response' => json_encode($responseDian, JSON_PRETTY_PRINT),
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('single')->error('DIAN SendBill exception', [
                'file' => "fv{$request->file}.xml",
                'exception' => $e->getMessage(),
                'raw_response' => $client->getResponse(),
            ]);

            return response()->json([
                'titulo' => 'Error DIAN',
                'mensaje' => 'La DIAN devolvió una respuesta inválida: ' . $e->getMessage(),
                'tipo' => 'error',
                'data' => [
                    'Json' => [
                        'message' => $e->getMessage(),
                        'cufe' => $cufe,
                        'ResponseDian' => null,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422);
        }

        if ($useSync) {
            $syncResult = $this->getSendBillSyncResult($responseDian);
            $isValid = ($syncResult->IsValid ?? '') === 'true';
            $statusDesc = $syncResult->StatusDescription ?? '';
            $xmlDocKeyVal = $syncResult->XmlDocumentKey ?? null;
            $xmlDocKey = is_object($xmlDocKeyVal)
                ? ($xmlDocKeyVal->_value ?? (string) $xmlDocKeyVal)
                : $xmlDocKeyVal;
            $cufeFinal = $xmlDocKey ? trim((string) $xmlDocKey) : $cufe;

            if (!$isValid) {
                return response()->json([
                    'titulo' => 'Error DIAN',
                    'mensaje' => $statusDesc ?: 'Documento rechazado por la DIAN',
                    'tipo' => 'error',
                    'data' => [
                        'Json' => [
                            'message' => $statusDesc,
                            'cufe' => $cufe,
                            'ResponseDian' => $responseDian,
                            'ZipBase64Bytes' => base64_encode($this->getZIP()),
                        ],
                    ],
                ], 422);
            }

            return [
                'message' => "{$typeDocument->name} #{$docId} generada con éxito",
                'cufe' => $cufeFinal,
                'zip_key' => null,
                'dian_errors' => null,
                'consulta_estado' => null,
                'ResponseDian' => $responseDian,
                'ZipBase64Bytes' => base64_encode($this->getZIP()),
            ];
        }

        $parsed = $this->parseSendBillAsyncResponse(
            $responseDian,
            $client,
            "fv{$request->file}.xml",
            'cufe',
            $cufe
        );

        if ($parsed['error_response']) {
            $syncResult = $this->getSendBillSyncResult($responseDian);
            $isValidSync = ($syncResult->IsValid ?? '') === 'true';
            if ($isValidSync) {
                $xmlDocKeyVal = $syncResult->XmlDocumentKey ?? null;
                $xmlDocKey = is_object($xmlDocKeyVal)
                    ? ($xmlDocKeyVal->_value ?? (string) $xmlDocKeyVal)
                    : $xmlDocKeyVal;
                $cufeFinal = $xmlDocKey ? trim((string) $xmlDocKey) : $cufe;
                $statusDesc = $syncResult->StatusDescription ?? '';

                return [
                    'message' => $statusDesc ?: "{$typeDocument->name} #{$docId} generada con éxito",
                    'cufe' => $cufeFinal,
                    'zip_key' => null,
                    'dian_errors' => null,
                    'consulta_estado' => null,
                    'ResponseDian' => $responseDian,
                    'ZipBase64Bytes' => base64_encode($this->getZIP()),
                ];
            }

            return $parsed['error_response'];
        }

        return [
            'message' => "{$typeDocument->name} #{$docId} generada con éxito",
            'cufe' => $cufe,
            'zip_key' => $parsed['zip_key'],
            'dian_errors' => null,
            'consulta_estado' => "POST /api/ubl2.1/status/zip/{$parsed['zip_key']}",
            'ResponseDian' => $responseDian,
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
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
        $user = auth()->user();
        $company = $user->company;
        $this->guardCertificateNit($company);

        $typeDocument = TypeDocument::findOrFail($request->type_document_id);

        $customerAll = collect($request->customer);
        $customer = new User($customerAll->toArray());
        $customer->company = new Company($customerAll->toArray());

        $request->resolution->number = $request->number;
        $request->resolution->next_consecutive = $request->number;
        $resolution = $request->resolution;

        $date = $request->date;
        $time = $request->time;

        $paymentFormAll = (object) array_merge($this->paymentFormDefault, $request->payment_form ?? []);
        $paymentForm = PaymentForm::findOrFail($paymentFormAll->payment_form_id);
        $paymentForm->payment_method_code = PaymentMethod::findOrFail($paymentFormAll->payment_method_id)->code;
        $paymentForm->payment_due_date = $paymentFormAll->payment_due_date ?? null;
        $paymentForm->duration_measure = $paymentFormAll->duration_measure ?? null;

        $allowanceCharges = collect();
        foreach ($request->allowance_charges ?? [] as $allowanceCharge) {
            $allowanceCharges->push(new AllowanceCharge($allowanceCharge));
        }

        $taxTotals = collect();
        foreach ($request->tax_totals ?? [] as $taxTotal) {
            $taxTotals->push(new TaxTotal($taxTotal));
        }

        $legalMonetaryTotals = new LegalMonetaryTotal($request->legal_monetary_totals);

        $invoiceLines = collect();
        foreach ($request->invoice_lines as $invoiceLine) {
            $invoiceLines->push(new InvoiceLine($invoiceLine));
        }

        $invoice = $this->createXML(compact('user', 'company', 'customer', 'taxTotals', 'resolution', 'paymentForm', 'typeDocument', 'invoiceLines', 'allowanceCharges', 'legalMonetaryTotals', 'date', 'time'));

        $signInvoice = new SignInvoice($company->certificate->path, $company->certificate->password);
        $softwareId = $request->software_id ?? $company->software->identifier;
        $softwarePin = $request->software_pin ?? $company->software->pin;
        $signInvoice->softwareID = $softwareId;
        $signInvoice->pin = $softwarePin;
        $technicalKey = trim((string) ($request->technical_key ?? ''));
        $signInvoice->technicalKey = $technicalKey !== '' ? $technicalKey : $resolution->technical_key;

        $sendTestSetAsync = new SendTestSetAsync($company->certificate->path, $company->certificate->password);
        $sendTestSetAsync->To = $company->software->url;
        $sendTestSetAsync->fileName = "fv{$request->file}.xml";
        $sendTestSetAsync->contentFile = $this->zipBase64($company, $resolution, $signInvoice->sign($invoice), $request->file);
        $sendTestSetAsync->testSetId = $testSetId;

        return [
            'message' => "{$typeDocument->name} #{$request->number} generada con éxito",
            'ResponseDian' => $sendTestSetAsync->signToSend()->getResponseToObject(),
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ];
    }
}
