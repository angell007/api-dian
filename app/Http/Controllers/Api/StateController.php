<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Stenfrank\UBL21dian\Templates\SOAP\GetStatus;
use Stenfrank\UBL21dian\Templates\SOAP\GetStatusZip;
use Stenfrank\UBL21dian\Templates\SOAP\GetNumbering;


class StateController extends Controller
{
    /**
     * Zip.
     *
     * @param string $trackId
     *
     * @return array
     */
    public function zip($trackId)
    {
        // User
        $user = auth()->user();

        $getStatusZip = new GetStatusZip($user->company->certificate->path, $user->company->certificate->password);
        $getStatusZip->To = $user->company->software->url;
        $getStatusZip->trackId = $trackId;

        return [
            'message' => 'Consulta generada con éxito',
            'ResponseDian' => $getStatusZip->signToSend()->getResponseToObject(),
        ];
    }

    /**
     * Document.
     *
     * @param string $trackId
     *
     * @return array
     */
    public function document($trackId)
    {
        // User
        $user = auth()->user();

        $getStatus = new GetStatus($user->company->certificate->path, $user->company->certificate->password);
        $getStatus->To = $user->company->software->url;
        $getStatus->trackId = $trackId;

        return [
            'message' => 'Consulta generada con éxito',
            'ResponseDian' => $getStatus->signToSend()->getResponseToObject(),
        ];
    }
    
    public function numbering($accountCode, $accountCodeT, $softwareCode)
    {
        // User
        $user = auth()->user();

        $getNumbering = new GetNumbering($user->company->certificate->path, $user->company->certificate->password);
        $getNumbering->To = $user->company->software->url;
        $getNumbering->accountCode = $accountCode;
        $getNumbering->accountCodeT = $accountCodeT;
        $getNumbering->softwareCode = $softwareCode;

        return [
            'message' => 'Consulta generada con éxito',
            'ResponseDian' => $getNumbering->signToSend()->getResponseToObject(),
        ];
    }
}
