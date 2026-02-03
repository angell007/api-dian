<cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
<cbc:CustomizationID>1</cbc:CustomizationID>
<cbc:ProfileID>DIAN 2.1: ApplicationResponse de la Factura Electrónica de Venta</cbc:ProfileID>
<cbc:ProfileExecutionID>{{$environment}}</cbc:ProfileExecutionID>
<cbc:ID>{{$code}}</cbc:ID>
<cbc:UUID schemeID="{{$company->type_environment->code}}" 
schemeName="{{$typeDocument->cufe_algorithm}}">
{{$cude_propio}}
</cbc:UUID>

<cbc:IssueDate>{{$date ?? Carbon\Carbon::now()->format('Y-m-d')}}</cbc:IssueDate>
<cbc:IssueTime>{{$time ?? Carbon\Carbon::now()->format('H:i:s')}}-05:00</cbc:IssueTime>
