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
        $user = auth()->user();

        $cufe_propio = $request->cufe_propio;

        $healt_sector = is_array($request->healt_sector ?? null) ? $request->healt_sector : null;

        // User company
        $company = $user->company;
        $this->guardCertificateNit($company);

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

        // Signature XML
        $signInvoice = new SignInvoice($company->certificate->path, $company->certificate->password);
        $softwareId = $request->software_id ?? $company->software->identifier;
        $softwarePin = $request->software_pin ?? $company->software->pin;
        $signInvoice->softwareID = $softwareId;
        $signInvoice->pin = $softwarePin;
        $signInvoice->technicalKey = $resolution->technical_key;
        $signedInvoice = $signInvoice->sign($invoice);

        $dom = $signInvoice->getDocument();
        $uuidNodes = $dom->getElementsByTagName('UUID');
        $cufe = ($uuidNodes->length > 0) ? trim($uuidNodes->item(0)->nodeValue ?? '') : '';

        $sendBillAsync = new SendBillAsync($company->certificate->path, $company->certificate->password);
        $sendBillAsync->To = $company->software->url;
        $sendBillAsync->fileName = "fv{$request->file}.xml";
        $sendBillAsync->contentFile = $this->zipBase64($company, $resolution, $signedInvoice, $request->file);

        $client = $sendBillAsync->signToSend();

        try {
            $responseDian = $client->getResponseToObject();
        } catch (\Exception $e) {
            Log::channel('single')->error('DIAN SendBillAsync exception', [
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

        // Extraer ZipKey, errores y detectar SOAP Fault de la respuesta DIAN
        $zipKey = $this->extractZipKeyFromDianResponse($responseDian);
        $dianErrors = $this->extractDianErrorsFromResponse($responseDian);
        $soapFault = $this->extractSoapFault($responseDian);

        // Log de la respuesta cruda para auditoría
        Log::channel('single')->debug('DIAN SendBillAsync response', [
            'file' => "fv{$request->file}.xml",
            'cufe' => $cufe,
            'zip_key' => $zipKey,
            'dian_errors' => $dianErrors,
            'soap_fault' => $soapFault,
            'raw_response' => $client->getResponse(),
        ]);

        // Si la DIAN devolvió error: respuesta 422 para evitar falsos positivos
        if ($soapFault) {
            return response()->json([
                'titulo' => 'Error DIAN',
                'mensaje' => $soapFault['message'],
                'tipo' => 'error',
                'data' => [
                    'Json' => [
                        'message' => $soapFault['message'],
                        'cufe' => $cufe,
                        'dian_errors' => $dianErrors,
                        'ResponseDian' => $responseDian,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422);
        }

        if ($dianErrors !== null && $dianErrors !== []) {
            $errorText = is_string($dianErrors) ? $dianErrors : json_encode($dianErrors, JSON_UNESCAPED_UNICODE);
            return response()->json([
                'titulo' => 'Error DIAN - Documento rechazado',
                'mensaje' => "La DIAN rechazó el documento: {$errorText}",
                'tipo' => 'error',
                'data' => [
                    'Json' => [
                        'message' => "La DIAN rechazó el documento: {$errorText}",
                        'cufe' => $cufe,
                        'zip_key' => $zipKey,
                        'dian_errors' => $dianErrors,
                        'ResponseDian' => $responseDian,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422);
        }

        // Sin ZipKey = respuesta inesperada, no asumir éxito
        if (empty($zipKey)) {
            return response()->json([
                'titulo' => 'Error DIAN - Respuesta inesperada',
                'mensaje' => 'La DIAN no devolvió ZipKey. Revisar ResponseDian.',
                'tipo' => 'error',
                'data' => [
                    'Json' => [
                        'message' => 'La DIAN no devolvió ZipKey. Revisar ResponseDian.',
                        'cufe' => $cufe,
                        'dian_errors' => $dianErrors,
                        'ResponseDian' => $responseDian,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422);
        }

        return [
            'message' => "{$typeDocument->name} #{$resolution->prefix}{$request->number} generada con éxito",
            'cufe' => $cufe,
            'zip_key' => $zipKey,
            'dian_errors' => null,
            'consulta_estado' => "POST /api/ubl2.1/status/zip/{$zipKey}",
            'ResponseDian' => $responseDian,
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ];
    }

    /**
     * Extrae el ZipKey de la respuesta anidada de la DIAN.
     */
    private function extractZipKeyFromDianResponse($response): ?string
    {
        $result = $this->getSendBillAsyncResult($response);
        return $result->ZipKey ?? null;
    }

    /**
     * Extrae mensajes de error de la respuesta de la DIAN.
     */
    private function extractDianErrorsFromResponse($response): ?array
    {
        $result = $this->getSendBillAsyncResult($response);
        $errorList = $result->ErrorMessageList ?? null;
        $attrs = $errorList->_attributes ?? [];
        $nil = is_array($attrs) ? ($attrs['nil'] ?? null) : ($attrs->nil ?? null);
        if (!$errorList || $nil === 'true') {
            return null;
        }
        return (array) $errorList;
    }

    private function getSendBillAsyncResult($response)
    {
        $body = isset($response->Envelope) ? ($response->Envelope->Body ?? null) : ($response->Body ?? null);
        if (!$body) {
            return (object) [];
        }
        $asyncResponse = $body->SendBillAsyncResponse ?? null;
        return $asyncResponse->SendBillAsyncResult ?? (object) [];
    }

    /**
     * Detecta SOAP Fault en la respuesta (error de conexión, servidor, etc.).
     */
    private function extractSoapFault($response): ?array
    {
        $body = isset($response->Envelope) ? ($response->Envelope->Body ?? null) : ($response->Body ?? null);
        if (!$body || !isset($body->Fault)) {
            return null;
        }
        $fault = $body->Fault;
        $reason = $fault->Reason->FaultReasonText ?? $fault->faultstring ?? null;
        $code = $fault->Code->Value ?? $fault->faultcode ?? null;
        $msg = is_array($reason) ? ($reason[0] ?? $reason) : $reason;
        $msg = $msg->_value ?? $msg ?? $fault->faultstring ?? null;
        $message = is_object($msg) ? json_encode($msg) : trim((string) ($msg ?? 'Error desconocido en servicio DIAN'));
        return [
            'message' => $message,
            'code' => $code,
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
        $this->guardCertificateNit($company);

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
