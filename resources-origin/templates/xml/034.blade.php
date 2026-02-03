<ApplicationResponse    xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
    xmlns:sts="http://www.dian.gov.co/contratos/facturaelectronica/v1/Structures"
    xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"
    xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2     http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">
  
	@include('xml.notifications.ubl_extension', ['user'=>$user,'date'=>$date,'time'=>$time,'resolution'=>$resolution, 'cude_propio'=>$cude_propio,'environment'=>$environment])  
    @include('xml.notifications.headers',['user'=>$user,'date'=>$date,'time'=>$time,'resolution'=>$resolution, 'code'=>$code])   
	<cbc:Note>Manifiesto bajo la gravedad de juramento que transcurridos 3 días hábiles siguientes a la fecha de recepción de la mercancía o del servicio en la referida factura de este evento, el adquirente {{$customer->name}} identificado con NIT {{$customer->company->identification_number}} no manifestó expresamente la aceptación o rechazo de la referida factura, ni reclamó en contra de su contenido.</cbc:Note>

    @include('xml.notifications.sender_party',['user',$user])   

    <cac:ReceiverParty>
		<cac:PartyTaxScheme>
			<cbc:RegistrationName>Unidad Administrativa Especial Dirección de Impuestos y Aduanas Nacionales</cbc:RegistrationName>
			<cbc:CompanyID schemeID="4"
			               schemeAgencyID="195"
			               schemeName="31"
			               schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)"
			               schemeVersionID="1">800197268</cbc:CompanyID>
			<cac:TaxScheme>
				<cbc:ID>01</cbc:ID>
				<cbc:Name>IVA</cbc:Name>
			</cac:TaxScheme>
		</cac:PartyTaxScheme>
	</cac:ReceiverParty>

    @include('xml.notifications.034_implicit_acceptance.document_response',['resolution'=>$resolution,'reference'=>$reference])   
</ApplicationResponse>
