<cac:SenderParty>
    <cac:PartyTaxScheme>
        <cbc:RegistrationName>{{$user->name}}</cbc:RegistrationName>
        <cbc:CompanyID schemeAgencyID="195" 
            schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" 
            schemeID="{{$user->company->dv}}" 
            schemeName="{{$user->company->type_document_identification->code}}"
            
            schemeVersionID="{{$user->company->type_organization->code}}"

            >{{$user->company->identification_number}}</cbc:CompanyID>
      
        <cac:TaxScheme>
            <cbc:ID>{{$user->company->tax->code}}</cbc:ID>
            <cbc:Name>{{$user->company->tax->name}}</cbc:Name>
        </cac:TaxScheme>
        
    </cac:PartyTaxScheme>
</cac:SenderParty>