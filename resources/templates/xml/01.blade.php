<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
    xmlns:sts="http://www.dian.gov.co/contratos/facturaelectronica/v1/Structures"
    xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2     http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-Invoice-2.1.xsd">
    {{-- UBLExtensions --}}
    @include('xml._ubl_extensions')
    @if (isset($healt_sector) &&
            !empty($healt_sector) &&
            is_array($healt_sector) &&
            isset($healt_sector['Codigo_Prestador']))
        @include('xml.healt_sector', ['healt_sector' => $healt_sector])
    @endif
    <cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
    @if (isset($healt_sector) && !empty($healt_sector) && is_array($healt_sector))
        <cbc:CustomizationID>SS-SinAporte</cbc:CustomizationID>
    @endif
    @if (!isset($healt_sector))
        <cbc:CustomizationID>{{ $company->type_operation->code }}</cbc:CustomizationID>
    @endif
    <cbc:ProfileID>DIAN 2.1: Factura Electrónica de Venta</cbc:ProfileID>
    <cbc:ProfileExecutionID>{{ $company->type_environment->code }}</cbc:ProfileExecutionID>
    <cbc:ID>{{ $resolution->next_consecutive }}</cbc:ID>
    <cbc:UUID schemeID="{{ $company->type_environment->code }}" schemeName="{{ $typeDocument->cufe_algorithm }}" />
    <cbc:IssueDate>{{ $date ?? Carbon\Carbon::now()->format('Y-m-d') }}</cbc:IssueDate>
    <cbc:IssueTime>{{ $time ?? Carbon\Carbon::now()->format('H:i:s') }}-05:00</cbc:IssueTime>
    <cbc:InvoiceTypeCode>{{ $typeDocument->code }}</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>{{ $company->type_currency->code }}</cbc:DocumentCurrencyCode>
    <cbc:LineCountNumeric>{{ $invoiceLines->count() }}</cbc:LineCountNumeric>
    @if (isset($healt_sector) &&
            !empty($healt_sector) &&
            is_array($healt_sector) &&
            !empty($healt_sector['Fecha_Inicio_Periodo_Facturacion']) &&
            !empty($healt_sector['Fecha_Fin_Periodo_Facturacion']))
        <cac:InvoicePeriod>
            <cbc:StartDate>{{ $healt_sector['Fecha_Inicio_Periodo_Facturacion'] }}</cbc:StartDate>
            <cbc:EndDate>{{ $healt_sector['Fecha_Fin_Periodo_Facturacion'] }}</cbc:EndDate>
        </cac:InvoicePeriod>
    @endif
    {{-- AccountingSupplierParty --}}
    @include('xml._accounting', ['node' => 'AccountingSupplierParty', 'supplier' => true])
    {{-- AccountingCustomerParty --}}
    @include('xml._accounting', ['node' => 'AccountingCustomerParty', 'user' => $customer])
    {{-- PaymentMeans --}}
    @include('xml._payment_means')
    {{-- PaymentTerms --}}
    @include('xml._payment_terms')
    {{-- AllowanceCharges --}}
    @include('xml._allowance_charges')
    {{-- TaxTotals --}}
    @include('xml._tax_totals')
    {{-- LegalMonetaryTotal --}}
    @include('xml._legal_monetary_total', ['node' => 'LegalMonetaryTotal'])
    {{-- InvoiceLines --}}
    @include('xml._invoice_lines')
</Invoice>
