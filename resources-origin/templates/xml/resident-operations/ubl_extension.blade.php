<ext:UBLExtensions>
    <ext:UBLExtension>
        <ext:ExtensionContent>
            <sts:DianExtensions>
                {{-- Evitar error cuando $resolution es null o no tiene datos --}}
                @if ($resolution && $resolution->resolution)
                    @include('xml._invoice_control2')
                @endif
                <sts:InvoiceSource>
                    <cbc:IdentificationCode listAgencyID="6" listAgencyName="United Nations Economic Commission for Europe" listSchemeURI="urn:oasis:names:specification:ubl:codelist:gc:CountryIdentificationCode-2.1">{{$company->country->code}}</cbc:IdentificationCode>
                </sts:InvoiceSource>
                @php
                    $companyIdentification = $company->identification_number;
                    $companyDv = $company->dv;
                    if ($company->type_document_identification_id == 6) {
                        $digits = preg_replace('/\D+/', '', (string) $companyIdentification);
                        if (strlen($digits) === 10) {
                            $companyIdentification = substr($digits, 0, 9);
                            if (empty($companyDv)) {
                                $companyDv = substr($digits, 9, 1);
                            }
                        } else {
                            $companyIdentification = $digits;
                        }
                    }
                @endphp
                <sts:SoftwareProvider>
                    <sts:ProviderID schemeAgencyID="195" 
                        schemeAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)" 
                        @if ($company->type_document_identification_id == 6 && !empty($companyDv))
                            schemeID="{{$companyDv}}"
                        @endif
                        schemeName="{{$company->type_document_identification->code}}">
                        {{$companyIdentification}}
                    </sts:ProviderID>
                    <sts:SoftwareID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)">{{$company->software->identifier}}</sts:SoftwareID>
                </sts:SoftwareProvider>

                <sts:SoftwareSecurityCode schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)"/>
                <sts:AuthorizationProvider>
                    <sts:AuthorizationProviderID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)" schemeID="4" schemeName="31">800197268</sts:AuthorizationProviderID>
                </sts:AuthorizationProvider>
                <sts:QRCode>https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey={{$cudsPropio}}</sts:QRCode>
            </sts:DianExtensions>
        </ext:ExtensionContent>
    </ext:UBLExtension>
    <ext:UBLExtension>
        <ext:ExtensionContent/>
    </ext:UBLExtension>
</ext:UBLExtensions>