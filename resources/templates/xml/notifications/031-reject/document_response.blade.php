
<cac:DocumentResponse>
    <cac:Response>
        <cbc:ResponseCode name="{{$typeRejection->name}}" listID="{{$typeRejection->code}}" >{{$resolution->type_document->code}}</cbc:ResponseCode>
        <cbc:Description>{{$resolution->type_document->name}}</cbc:Description>
    </cac:Response>
    <cac:DocumentReference>
        <cbc:ID>{{$reference->code}}</cbc:ID>
        <cbc:UUID schemeName="{{$resolution->type_document->cufe_algorithm}}">{{$reference->cufe}}</cbc:UUID>
        <cbc:DocumentTypeCode>{{$reference->type_document}}</cbc:DocumentTypeCode>
    </cac:DocumentReference>
</cac:DocumentResponse>


