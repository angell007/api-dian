
<cac:DocumentResponse>
    <cac:Response>
        <cbc:ResponseCode>{{$resolution->type_document->code}}</cbc:ResponseCode>
        <cbc:Description>{{$resolution->type_document->name}}</cbc:Description>
    </cac:Response>
    <cac:DocumentReference>
        <cbc:ID>{{$reference->code}}</cbc:ID>
        <cbc:UUID schemeName="{{$resolution->type_document->cufe_algorithm}}">{{$reference->cufe}}</cbc:UUID>
        <cbc:DocumentTypeCode>{{$reference->type_document}}</cbc:DocumentTypeCode>
    </cac:DocumentReference>
    <cac:IssuerParty>
        <cac:Person>
            <cbc:ID 
            @if ($person->type_document_identification->code=='31')
                schemeID="{{$person->dv}}"
            @endif
            schemeName="{{$person->type_document_identification->code}}"
            >{{$person->identification_number}}</cbc:ID>
            <cbc:FirstName>{{$person->firstName}}</cbc:FirstName>
            <cbc:FamilyName>{{$person->familyName}}</cbc:FamilyName>
            <cbc:JobTitle>{{$person->jobTitle}}</cbc:JobTitle>
            <cbc:OrganizationDepartment>{{$person->organizationDepartment}}</cbc:OrganizationDepartment>
        </cac:Person>
    </cac:IssuerParty>
</cac:DocumentResponse>