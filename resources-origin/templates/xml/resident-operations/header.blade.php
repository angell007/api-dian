<cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
<cbc:CustomizationID>{{$company->type_operation->code}}</cbc:CustomizationID>
<cbc:ProfileID>DIAN 2.1: documento soporte en adquisiciones efectuadas a no obligados a facturar.</cbc:ProfileID>
<cbc:ProfileExecutionID>{{$environment}}</cbc:ProfileExecutionID>
<cbc:ID>{{$code}}</cbc:ID>
<cbc:UUID schemeID="{{$environment}}" schemeName="{{$typeDocument->cufe_algorithm}}">{{$cudsPropio}}</cbc:UUID>


<cbc:IssueDate>{{$date ?? Carbon\Carbon::now()->format('Y-m-d')}}</cbc:IssueDate>
<cbc:IssueTime>{{$time ?? Carbon\Carbon::now()->format('H:i:s')}}-05:00</cbc:IssueTime>
<cbc:DueDate>{{$dueDate}}</cbc:DueDate>

<cbc:InvoiceTypeCode>{{$typeDocument->code}}</cbc:InvoiceTypeCode>

<cbc:Note>Descripcion Doc Soporte</cbc:Note>
<cbc:DocumentCurrencyCode>{{$company->type_currency->code}}</cbc:DocumentCurrencyCode>

<cbc:LineCountNumeric>{{$invoiceLines->count()}}</cbc:LineCountNumeric>
@if (data_get($orderReference, 'code'))
<cac:OrderReference  >
    <cbc:ID>{{data_get($orderReference, 'code')}}</cbc:ID>
    <cbc:IssueDate>{{data_get($orderReference, 'date')}}</cbc:IssueDate>		
</cac:OrderReference>
@endif
