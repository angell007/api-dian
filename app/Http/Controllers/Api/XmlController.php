<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

/**
 * Sirve XML de factura y AttachedDocument para RIPS FEV.
 * Validación de filename para prevenir path traversal.
 */
class XmlController extends Controller
{
    private $companyId = 1;

    /**
     * GET /xml/invoice/{resolution_id}/{filename}
     */
    public function invoice($resolution_id, $filename)
    {
        $path = $this->resolveXmlPath($resolution_id, $filename, 'xml');
        return $this->serveXml($path);
    }

    /**
     * GET /xml/attached/{resolution_id}/{filename}
     */
    public function attached($resolution_id, $filename)
    {
        $path = $this->resolveXmlPath($resolution_id, $filename, 'ad');
        return $this->serveXml($path);
    }

    private function resolveXmlPath($resolution_id, $filename, $subdir)
    {
        if (!ctype_digit((string) $resolution_id)) {
            abort(400, 'resolution_id inválido');
        }
        if (!$this->isValidFilename($filename)) {
            abort(400, 'filename inválido');
        }
        $relative = $subdir . '/' . $this->companyId . '/' . $resolution_id . '/' . $filename;
        $full = storage_path('app/' . $relative);
        if (!file_exists($full) || !is_file($full)) {
            abort(404, 'Archivo no encontrado');
        }
        return $full;
    }

    private function isValidFilename($filename)
    {
        if (!is_string($filename) || strlen($filename) < 5) {
            return false;
        }
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return false;
        }
        return (bool) preg_match('/^[a-zA-Z0-9_-]+\.xml$/i', $filename);
    }

    private function serveXml($path)
    {
        return response()->file($path, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
