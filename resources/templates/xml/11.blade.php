<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
    xmlns:sts="dian:gov:co:facturaelectronica:Structures-2-1" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"
    xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2     http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">

    @include('xml.resident-operations.ubl_extension', [
        'user' => $user,
        'date' => $date,
        'time' => $time,
        'resolution' => $resolution,
        'code' => $code,
        'environment' => $environment,
    ])
    @include('xml.resident-operations.header', [
        'user' => $user,
        'date' => $date,
        'time' => $time,
        'resolution' => $resolution,
        'code' => $code,
        'environment' => $environment,
    ])
    @include('xml.resident-operations.accounting-supplier')
    @include('xml.resident-operations.accounting-customer', ['customer' => $customer])

    @include('xml.resident-operations.payment')
    @include('xml.resident-operations.invoice-line')

</Invoice>
