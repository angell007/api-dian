<cac:ReceiverParty>
    <cac:PartyTaxScheme>
        <cbc:RegistrationName>{{$customer->name}}</cbc:RegistrationName>
        <cbc:CompanyID schemeAgencyID="195" 
            schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" 

            
            @if ($customer->company->type_document_identification->code=='31')
            schemeID="{{$customer->company->dv}}" 
            @endif

            schemeName="{{$customer->company->type_document_identification->code}}"
            
            schemeVersionID="{{$customer->company->type_organization->code}}"
            >{{$customer->company->identification_number}}</cbc:CompanyID>

        <cac:TaxScheme>
            <cbc:ID>{{$customer->company->tax->code}}</cbc:ID>
            <cbc:Name>{{$customer->company->tax->name}}</cbc:Name>
        </cac:TaxScheme>
        
    </cac:PartyTaxScheme>
</cac:ReceiverParty>
