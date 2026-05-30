<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Preview, logging y parseo de respuestas DIAN (factura / nota crédito).
 * Banderas en config/dian_debug.php (edición manual; no vienen en la petición).
 */
trait DianSendBillTrait
{
    protected function isDianPreviewEnabled(): bool
    {
        return (bool) config('dian_debug.preview', false);
    }

    protected function isDianDebugVerbose(): bool
    {
        return (bool) config('dian_debug.verbose', false);
    }

    protected function shouldIncludeSignedXmlInPreviewResponse(): bool
    {
        return (bool) config('dian_debug.include_xml', true);
    }

    /**
     * Guarda XML en storage/logs y opcionalmente lo devuelve en JSON (sin enviar a DIAN).
     */
    protected function buildDianPreviewResponse(
        string $logFileName,
        string $documentLabel,
        string $identifier,
        string $uuid,
        $signedDom,
        $unsignedXml = null,
        $healt_sector = null
    ): array {
        $xmlForLog = $signedDom instanceof \DOMDocument
            ? $signedDom->saveXML()
            : (is_string($signedDom) ? $signedDom : '');

        $logPath = storage_path('logs/' . $logFileName);
        $header = sprintf(
            "\n--- %s | %s %s | UUID: %s ---\n",
            date('Y-m-d H:i:s'),
            $documentLabel,
            $identifier,
            $uuid
        );
        @file_put_contents($logPath, $header . $xmlForLog . "\n", FILE_APPEND | LOCK_EX);

        if ($this->isDianDebugVerbose()) {
            Log::channel('single')->info('DIAN preview (no enviado)', [
                'document' => $documentLabel,
                'identifier' => $identifier,
                'uuid' => $uuid,
                'log_path' => $logPath,
                'healt_sector' => $healt_sector,
            ]);
        }

        $payload = [
            'preview' => true,
            'message' => "XML guardado en storage/logs/{$logFileName} (no enviado a DIAN)",
            'log_path' => $logPath,
            'uuid' => $uuid,
        ];

        if ($uuid !== '' && strtoupper($documentLabel) === 'FACTURA') {
            $payload['cufe'] = $uuid;
        }
        if ($uuid !== '' && stripos($documentLabel, 'nota') !== false) {
            $payload['cude'] = $uuid;
        }

        if ($this->shouldIncludeSignedXmlInPreviewResponse()) {
            $payload['signed_xml'] = $xmlForLog;
            if ($unsignedXml !== null) {
                $payload['unsigned_xml'] = is_string($unsignedXml)
                    ? $unsignedXml
                    : ($unsignedXml instanceof \DOMDocument ? $unsignedXml->saveXML() : null);
            }
        }

        return $payload;
    }

    protected function normalizeHealtSectorPayload($healt_sector)
    {
        if ($healt_sector === null) {
            return null;
        }

        if (is_array($healt_sector)) {
            return $healt_sector;
        }

        if (is_object($healt_sector)) {
            $converted = json_decode(json_encode($healt_sector), true);

            return is_array($converted) ? $converted : null;
        }

        return null;
    }

    protected function sectorSaludInteroperabilidadPorPrefijoResolucion($prefijoResolucion, $healt_sector)
    {
        $healt_sector = $this->normalizeHealtSectorPayload($healt_sector);
        if (!is_array($healt_sector)) {
            return null;
        }

        $p = strtoupper(trim((string) $prefijoResolucion));
        if (preg_match('/^[A-Z]+/', $p, $m)) {
            $p = $m[0];
        }

        if ($p === 'FECA') {
            $healt_sector['Modalidad_Contratacion'] = 'Pago por capitación';
            $healt_sector['Cobertura_Plan_Beneficios'] = 'Plan de beneficios en salud financiados con UPC';
            $healt_sector['Modalidad_schemeID'] = '03';
            $healt_sector['Cobertura_schemeID'] = '01';
        } elseif ($p === 'FEEP') {
            $healt_sector['Modalidad_Contratacion'] = 'Por servicio';
            $healt_sector['Cobertura_Plan_Beneficios'] = 'Plan de beneficios en salud financiado con UPC';
            $healt_sector['Modalidad_schemeID'] = '04';
            $healt_sector['Cobertura_schemeID'] = '01';
        } elseif ($p === 'FENP') {
            $healt_sector['Modalidad_Contratacion'] = 'Pago por evento';
            $healt_sector['Cobertura_Plan_Beneficios'] = 'Presupuesto maximo';
            $healt_sector['Modalidad_schemeID'] = '04';
            $healt_sector['Cobertura_schemeID'] = '02';
        }

        return $healt_sector;
    }

    private function extractZipKeyFromDianResponse($response): ?string
    {
        $result = $this->getSendBillAsyncResult($response);

        return $result->ZipKey ?? null;
    }

    private function extractDianErrorsFromResponse($response): ?array
    {
        $result = $this->getSendBillAsyncResult($response);
        $errorList = $result->ErrorMessageList ?? null;
        if (!$errorList) {
            return null;
        }
        $attrs = $errorList->_attributes ?? [];
        $nil = is_array($attrs) ? ($attrs['nil'] ?? null) : ($attrs->nil ?? null);
        if ($nil === 'true') {
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

    private function getSendBillSyncResult($response)
    {
        $body = isset($response->Envelope) ? ($response->Envelope->Body ?? null) : ($response->Body ?? null);
        if (!$body) {
            return (object) [];
        }
        $syncResponse = $body->SendBillSyncResponse ?? null;
        $result = $syncResponse->SendBillSyncResult ?? null;
        if (!$result) {
            return (object) [];
        }

        return $result->DianResponse ?? $result;
    }

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
     * @return array{zip_key:?string,dian_errors:?array,error_response:?\Illuminate\Http\JsonResponse}
     */
    protected function parseSendBillAsyncResponse(
        $responseDian,
        $client,
        string $fileLabel,
        string $uuidKey,
        string $uuidValue
    ): array {
        $zipKey = $this->extractZipKeyFromDianResponse($responseDian);
        $dianErrors = $this->extractDianErrorsFromResponse($responseDian);
        $soapFault = $this->extractSoapFault($responseDian);

        if ($this->isDianDebugVerbose()) {
            Log::channel('single')->debug('DIAN SendBillAsync response', [
                'file' => $fileLabel,
                $uuidKey => $uuidValue,
                'zip_key' => $zipKey,
                'dian_errors' => $dianErrors,
                'soap_fault' => $soapFault,
                'raw_response' => $client->getResponse(),
            ]);
        }

        if ($soapFault) {
            return [
                'zip_key' => $zipKey,
                'dian_errors' => $dianErrors,
                'error_response' => response()->json([
                'titulo' => 'Error DIAN',
                'mensaje' => $soapFault['message'],
                'tipo' => 'error',
                'data' => [
                    'Json' => [
                        'message' => $soapFault['message'],
                        $uuidKey => $uuidValue,
                        'dian_errors' => $dianErrors,
                        'ResponseDian' => $responseDian,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422),
            ];
        }

        if ($dianErrors !== null && $dianErrors !== []) {
            $errorText = is_string($dianErrors) ? $dianErrors : json_encode($dianErrors, JSON_UNESCAPED_UNICODE);

            return [
                'zip_key' => $zipKey,
                'dian_errors' => $dianErrors,
                'error_response' => response()->json([
                'titulo' => 'Error DIAN - Documento rechazado',
                'mensaje' => "La DIAN rechazó el documento: {$errorText}",
                'tipo' => 'error',
                'data' => [
                    'Json' => [
                        'message' => "La DIAN rechazó el documento: {$errorText}",
                        $uuidKey => $uuidValue,
                        'zip_key' => $zipKey,
                        'dian_errors' => $dianErrors,
                        'ResponseDian' => $responseDian,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422),
            ];
        }

        if (empty($zipKey)) {
            return [
                'zip_key' => null,
                'dian_errors' => $dianErrors,
                'error_response' => response()->json([
                'titulo' => 'Error DIAN - Respuesta inesperada',
                'mensaje' => 'La DIAN no devolvió ZipKey. Revisar ResponseDian.',
                'tipo' => 'error',
                'data' => [
                    'Json' => [
                        'message' => 'La DIAN no devolvió ZipKey. Revisar ResponseDian.',
                        $uuidKey => $uuidValue,
                        'dian_errors' => $dianErrors,
                        'ResponseDian' => $responseDian,
                        'ZipBase64Bytes' => base64_encode($this->getZIP()),
                    ],
                ],
            ], 422),
            ];
        }

        return [
            'zip_key' => $zipKey,
            'dian_errors' => null,
            'error_response' => null,
        ];
    }
}
