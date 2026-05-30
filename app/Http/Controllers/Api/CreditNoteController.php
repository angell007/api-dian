<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Company;
use App\TaxTotal;
use App\PaymentForm;
use App\TypeDocument;
use App\PaymentMethod;
use App\AllowanceCharge;
use App\BillingReference;
use App\LegalMonetaryTotal;
use App\Traits\DocumentTrait;
use App\Traits\DianSendBillTrait;
use App\Http\Controllers\Controller;
use App\InvoiceLine as CreditNoteLine;
use App\Http\Requests\Api\CreditNoteRequest;
use Stenfrank\UBL21dian\XAdES\SignCreditNote;
use Stenfrank\UBL21dian\Templates\SOAP\SendBillAsync;
use Stenfrank\UBL21dian\Templates\SOAP\SendTestSetAsync;
use Illuminate\Support\Facades\Log;

class CreditNoteController extends Controller
{
    use DocumentTrait;
    use DianSendBillTrait;

    /**
     * Store.
     *
     * @param \App\Http\Requests\Api\CreditNoteRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CreditNoteRequest $request)
    {
        $user = auth()->user();
        $cufe_propio = $request->cufe_propio;
        $healt_sector = is_array($request->healt_sector ?? null) ? $request->healt_sector : null;

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
            Log::info('Credit note store', [
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

        $creditNoteLines = collect();
        foreach ($request->credit_note_lines as $creditNoteLine) {
            $creditNoteLines->push(new CreditNoteLine($creditNoteLine));
        }

        $billingReference = new BillingReference($request->billing_reference);

        $customization_id = trim((string) $request->input('customization_id'));

        $crediNote = $this->createXML(compact(
            'user',
            'company',
            'customer',
            'taxTotals',
            'resolution',
            'paymentForm',
            'typeDocument',
            'creditNoteLines',
            'allowanceCharges',
            'legalMonetaryTotals',
            'billingReference',
            'date',
            'time',
            'cufe_propio',
            'healt_sector',
            'customization_id'
        ));

        $signCreditNote = new SignCreditNote($company->certificate->path, $company->certificate->password);
        $signCreditNote->softwareID = $request->software_id ?? $company->software->identifier;
        $signCreditNote->pin = $request->software_pin ?? $company->software->pin;
        $signedCreditNote = $signCreditNote->sign($crediNote);

        $dom = $signCreditNote->getDocument();
        $uuidNodes = $dom->getElementsByTagName('UUID');
        $cude = ($uuidNodes->length > 0) ? trim($uuidNodes->item(0)->nodeValue ?? '') : '';

        $docId = ($resolution->prefix ?? '') . ($resolution->next_consecutive ?? $request->number);

        if ($this->isDianPreviewEnabled()) {
            return $this->buildDianPreviewResponse(
                'credit-note-xml-preview.log',
                'NOTA CREDITO',
                $docId,
                $cude,
                $dom,
                $crediNote,
                $healt_sector
            );
        }

        $sendBillAsync = new SendBillAsync($company->certificate->path, $company->certificate->password);
        $sendBillAsync->To = $company->software->url;
        $sendBillAsync->fileName = "nc{$request->file}.xml";
        $sendBillAsync->contentFile = $this->zipBase64($company, $resolution, $signedCreditNote, $request->file);

        $client = $sendBillAsync->signToSend();

        try {
            $responseDian = $client->getResponseToObject();
        } catch (\Exception $e) {
            Log::channel('single')->error('DIAN SendBillAsync exception (credit note)', [
                'file' => "nc{$request->file}.xml",
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
                        'cude' => $cude,
                        'ResponseDian' => null,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422);
        }

        $parsed = $this->parseSendBillAsyncResponse(
            $responseDian,
            $client,
            "nc{$request->file}.xml",
            'cude',
            $cude
        );

        if ($parsed['error_response']) {
            return $parsed['error_response'];
        }

        return [
            'message' => "{$typeDocument->name} #{$docId} generada con éxito",
            'cude' => $cude,
            'zip_key' => $parsed['zip_key'],
            'consulta_estado' => "POST /api/ubl2.1/status/zip/{$parsed['zip_key']}",
            'dian_errors' => null,
            'ResponseDian' => $responseDian,
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ];
    }

    /**
     * Test set store description].
     *
     * @param \App\Http\Requests\Api\CreditNoteRequest $request
     *
     * @return \Illuminate\Http\Response
     */
    public function testSetStore(CreditNoteRequest $request, $testSetId)
    {
        $user = auth()->user();
        $company = $user->company;
        $this->guardCertificateNit($company);

        $typeDocument = TypeDocument::findOrFail($request->type_document_id);

        $customerAll = collect($request->customer);
        $customer = new User($customerAll->toArray());
        $customer->company = new Company($customerAll->toArray());

        $request->resolution->number = $request->number;
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

        $creditNoteLines = collect();
        foreach ($request->credit_note_lines as $creditNoteLine) {
            $creditNoteLines->push(new CreditNoteLine($creditNoteLine));
        }

        $billingReference = new BillingReference($request->billing_reference);

        $customization_id = trim((string) $request->input('customization_id'));

        $crediNote = $this->createXML(compact(
            'user',
            'company',
            'customer',
            'taxTotals',
            'resolution',
            'paymentForm',
            'typeDocument',
            'creditNoteLines',
            'allowanceCharges',
            'legalMonetaryTotals',
            'billingReference',
            'date',
            'time',
            'customization_id'
        ));

        $signCreditNote = new SignCreditNote($company->certificate->path, $company->certificate->password);
        $signCreditNote->softwareID = $company->software->identifier;
        $signCreditNote->pin = $company->software->pin;

        $sendTestSetAsync = new SendTestSetAsync($company->certificate->path, $company->certificate->password);
        $sendTestSetAsync->To = $company->software->url;
        $sendTestSetAsync->fileName = "{$resolution->next_consecutive}.xml";
        $sendTestSetAsync->contentFile = $this->zipBase64($company, $resolution, $signCreditNote->sign($crediNote));
        $sendTestSetAsync->testSetId = $testSetId;

        return [
            'message' => "{$typeDocument->name} #{$resolution->next_consecutive} generada con éxito",
            'ResponseDian' => $sendTestSetAsync->signToSend()->getResponseToObject(),
            'ZipBase64Bytes' => base64_encode($this->getZIP()),
        ];
    }
}
