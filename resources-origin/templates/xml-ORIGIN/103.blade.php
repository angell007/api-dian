<NominaIndividualDeAjuste xmlns="dian:gov:co:facturaelectronica:NominaIndividualDeAjuste" 
xmlns:xs="http://www.w3.org/2001/XMLSchema-instance"
                          xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                          xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
                          xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"
                          xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#"
                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                          SchemaLocation=""
                          xsi:schemaLocation="dian:gov:co:facturaelectronica:NominaIndividualDeAjuste NominaIndividualDeAjusteElectronicaXSD.xsd"
    >
 
     @include('xml.partial_payroll_note.ubl_extension')
     @include('xml.partial_payroll_note.headers_replace',['user',$user])
     @if($header->note_type == '1')
        @include('xml.partial_payroll_note.devengados', ['accrued' => $accrued] )
        @include('xml.partial_payroll_note.deducciones')
        @include('xml.partial_payroll_note.totals')
     @endif
     @if($header->note_type == '1')
        </Reemplazar>
     @endif
     @if($header->note_type == '2')
        </Eliminar>
     @endif
</NominaIndividualDeAjuste>




