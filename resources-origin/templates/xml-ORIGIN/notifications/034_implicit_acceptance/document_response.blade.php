
<cac:DocumentResponse>
    <cac:Response>
        <cbc:ResponseCode>{{$resolution->type_document->code}}</cbc:ResponseCode>
        <cbc:Description>Aceptación Tácita</cbc:Description>
    </cac:Response>
    <cac:DocumentReference>
        <cbc:ID>{{$reference->code}}</cbc:ID>
        <cbc:UUID schemeName="{{$resolution->type_document->cufe_algorithm}}">{{$reference->cufe}}</cbc:UUID>
        <cbc:DocumentTypeCode>{{$reference->type_document}}</cbc:DocumentTypeCode>
    </cac:DocumentReference>
 
</cac:DocumentResponse>