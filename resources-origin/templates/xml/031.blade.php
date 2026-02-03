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
  
	@include('xml.notifications.ubl_extension', ['user'=>$user,'date'=>$date,'time'=>$time,'resolution'=>$resolution, 'cude_propio'=>$cude_propio,['environment',$environment]])  
    @include('xml.notifications.headers',['user'=>$user,'date'=>$date,'time'=>$time,'resolution'=>$resolution, 'code'=>$code])   
    @include('xml.notifications.sender_party',['user',$user])   
    @include('xml.notifications.recive_party',['customer',$customer])   
    @include('xml.notifications.031-reject.document_response',['resolution'=>$resolution,'reference'=>$reference,'person'=>$person])   
</ApplicationResponse>
