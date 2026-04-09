<?php

namespace App\Traits;

use Storage;
use Exception;
use ZipArchive;
use App\Company;
use App\Log;
use DOMDocument;
use App\Resolution;
use App\TypeDocument;
use Illuminate\Http\Exceptions\HttpResponseException;
use InvalidArgumentException;
use Stenfrank\UBL21dian\Sign;

/**
 * Document trait.
 */
trait DocumentTrait
{
    /**
     * PPP.
     *
     * @var string
     */
    public $ppp = '000';

    /**
     * Payment form default.
     *
     * @var array
     */
    private $paymentFormDefault = [
        'payment_form_id' => 1,
        'payment_method_id' => 10,
    ];

    /**
     * Create xml.
     *
     * @param array $data
     *
     * @return DOMDocument
     */
    protected function createXML(array $data)
    {


        try {
            $DOMDocumentXML = new DOMDocument("1.0", "utf-8");
            $DOMDocumentXML->preserveWhiteSpace = false;
            $DOMDocumentXML->formatOutput = true;
            $name = "xml.{$data['typeDocument']['code']}";
            try {
                // Renderizar plantilla y sanear a UTF-8 antes de cargar en DOMDocument para evitar errores de codificación
                $xml = view($name, $data)->render();
                // // Quitar caracteres de control no permitidos en XML
                // $xml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $xml);
                // // Forzar codificación UTF-8, ignorando bytes inválidos
                // $xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
                // $xml = iconv('UTF-8', 'UTF-8//IGNORE', $xml);

                // 1. Asegurar que la cadena sea UTF-8 válido primero.
                // Esto arregla los caracteres 'rotos' que hacen fallar al modificador /u
                $xml = mb_convert_encoding($xml, 'UTF-8', 'UTF-8');
                // 2. Ejecutar la limpieza. 
                // Guardamos el resultado en una variable temporal para verificar si falló.
                $xmlLimpio = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $xml);
                // 3. Verificamos: Si preg_replace devolvió NULL (falló), nos quedamos con el original.
                if ($xmlLimpio !== null) {
                    $xml = $xmlLimpio;
                }
                $DOMDocumentXML->loadXML($xml);
            } catch (\Throwable $th) {
                echo $th->getMessage();
                exit;
            }
            //   $DOMDocumentXML->loadXML(view("xml.{$data['typeDocument']['code']}{$data['header']->cune_propio}", $data)->render());

            return $DOMDocumentXML;
        } catch (InvalidArgumentException $e) {
            throw new Exception("The API does not support the type of document '{$data['typeDocument']['name']}' Error: line {$e->getLine()} {$e->getMessage()}");
        } catch (Exception $e) {

            throw new Exception("Error XXX: line {$e->getLine()} {$e->getFile()} {$e->getMessage()}");
        }
    }

    /**
     * Zip base64.
     *
     * @param \App\Company              $company
     * @param \App\Resolution           $resolution
     * @param \Stenfrank\UBL21dian\Sign $sign
     *
     * @return string
     */
    protected function zipBase64(Company $company, Resolution $resolution, Sign $sign, $nombre)
    {
        $dir = "zip/{$resolution->company_id}/{$resolution->id}";
        $nameXML = $this->getFileName($resolution, $nombre);
        $nameZip = $this->getFileName($resolution, $nombre, 6, '.zip');


        $xmlPath = storage_path("app/xml/{$resolution->company_id}/{$resolution->id}/{$nameXML}");
        $fileExists = file_exists($xmlPath);

        if ($fileExists) {
            $ruta = $xmlPath;
            header('Content-type:application/json');
            echo http_response_code(406) ? json_encode([
                'message' => "archivo existente ",
                'ruta' => str_replace("/", '/', $ruta)
            ]) : http_response_code(406);
        }

        $this->pathZIP = "app/zip/{$resolution->company_id}/{$resolution->id}/{$nameZip}";

        // No sobrescribir XML existente: preserva sección Sector Salud (CustomTagGeneral/Interoperabilidad)
        if (!$fileExists) {
            Storage::put("xml/{$resolution->company_id}/{$resolution->id}/{$nameXML}", $sign->xml);
        }

        if (!Storage::has($dir)) {
            Storage::makeDirectory($dir);
        }

        $zip = new ZipArchive();
        $zip->open(storage_path($this->pathZIP), ZipArchive::CREATE);
        $zip->addFile(storage_path("app/xml/{$resolution->company_id}/{$resolution->id}/{$nameXML}"), $nameXML);
        $zip->close();

        return $this->ZipBase64Bytes = base64_encode(file_get_contents(storage_path($this->pathZIP)));
    }


    /**
     * Zip base64.
     *
     * @param \App\Company              $company
     * @param \App\Resolution           $resolution
     * @param \Stenfrank\UBL21dian\Sign $sign
     *
     * @return string
     */
    protected function zipBase64Payroll(Company $company, Sign $sign, $nombre)
    {
        $dir = "zip/{$company->id}/NE";
        $nameXML = "nie" . $nombre . ".xml";
        $nameZip = "nie" . $nombre . ".zip";

        $this->pathZIP = "app/zip/{$company->id}/NE/{$nameZip}";

        Storage::put("xml/{$company->id}/NE/{$nameXML}", $sign->xml);

        if (!Storage::has($dir)) {
            Storage::makeDirectory($dir);
        }

        $zip = new ZipArchive();
        $zip->open(storage_path($this->pathZIP), ZipArchive::CREATE);
        $zip->addFile(storage_path("app/xml/{$company->id}/NE/{$nameXML}"), $nameXML);
        $zip->close();

        return $this->ZipBase64Bytes = base64_encode(file_get_contents(storage_path($this->pathZIP)));
    }

    protected function zipBase64Communication(Company $company, Sign $sign, $nombre)
    {
        $dir = "zip/{$company->id}/AE";
        $nameXML = "AE" . $nombre . ".xml";
        $nameZip = "AE" . $nombre . ".zip";

        $this->pathZIP = "app/zip/{$company->id}/AE/{$nameZip}";

        Storage::put("xml/{$company->id}/AE/{$nameXML}", $sign->xml);

        if (!Storage::has($dir)) {
            Storage::makeDirectory($dir);
        }

        $zip = new ZipArchive();
        $zip->open(storage_path($this->pathZIP), ZipArchive::CREATE);
        $zip->addFile(storage_path("app/xml/{$company->id}/AE/{$nameXML}"), $nameXML);
        $zip->close();

        return $this->ZipBase64Bytes = base64_encode(file_get_contents(storage_path($this->pathZIP)));
    }

    /**
     * Get file name.
     *
     * @param \App\Company    $company
     * @param \App\Resolution $resolution
     *
     * @return string
     */
    protected function getFileName(Resolution $resolution, $nombre, $typeDocumentID = null, $extension = '.xml')
    {
        $date = now();
        $prefix = (is_null($typeDocumentID)) ? $resolution->type_document->prefix : TypeDocument::findOrFail($typeDocumentID)->prefix;
        $name = "{$prefix}{$nombre}{$extension}";

        return $name;
    }

    /**
     * Stuffed string.
     *
     * @param string $string
     * @param int    $length
     * @param int    $padString
     * @param int    $padType
     *
     * @return string
     */
    protected function stuffedString($string, $length = 10, $padString = 0, $padType = STR_PAD_LEFT)
    {
        return str_pad($string, $length, $padString, $padType);
    }

    /**
     * Get ZIP.
     *
     * @return string
     */
    protected function getZIP()
    {
        return $this->ZipBase64Bytes;
    }

    protected function guardCertificateNit(Company $company)
    {
        Log::info('guardCertificateNit', ['company' => $company->certificate->path, 'password' => $company->certificate->password]);
        
        if (!$company->certificate) {
            throw new HttpResponseException(response()->json([
                'message' => 'Certificate not configured for company.',
            ], 422));
        }

        $info = $this->getCertificateNitInfo($company->certificate->path, $company->certificate->password);
        if (!empty($info['error'])) {
            throw new HttpResponseException(response()->json([
                'message' => 'Certificate could not be read.',
                'error' => $info['error'],
            ], 422));
        }

        $companyNit = $this->normalizeNit($company->identification_number ?? null);
        $companyDv = $this->normalizeNit($company->dv ?? null);
        $companyNitWithDv = ($companyNit && $companyDv) ? $companyNit . $companyDv : null;

        $certNit = $this->normalizeNit($info['nit'] ?? null);
        if (!$certNit) {
            throw new HttpResponseException(response()->json([
                'message' => 'Certificate NIT not found.',
                'company_nit' => $companyNit,
                'company_dv' => $companyDv,
                'cert_subject_serial' => $info['subject']['serialNumber'] ?? null,
                'cert_subject_cn' => $info['subject']['CN'] ?? null,
            ], 422));
        }

        if ($certNit !== $companyNit && $certNit !== $companyNitWithDv) {
            throw new HttpResponseException(response()->json([
                'message' => 'Certificate NIT does not match company NIT.',
                'company_nit' => $companyNit,
                'company_dv' => $companyDv,
                'cert_nit' => $certNit,
                'cert_subject_serial' => $info['subject']['serialNumber'] ?? null,
                'cert_subject_cn' => $info['subject']['CN'] ?? null,
            ], 422));
        }

        return $info;
    }

    protected function getCertificateNitInfo($path, $password)
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

    protected function extractNitFromText($text)
    {
        if (!$text) {
            return null;
        }
        if (preg_match('/(\\d{7,15})/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function normalizeNit($nit)
    {
        if ($nit === null) {
            return null;
        }
        $normalized = preg_replace('/\\D+/', '', (string) $nit);
        return $normalized !== '' ? $normalized : null;
    }
}
