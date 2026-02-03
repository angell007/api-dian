<Novedad CUNENov="A">false</Novedad>
<Periodo

@if( isset($header->integration_date) )
 FechaIngreso="{{$header->integration_date}}"
@endif

@if( isset($person['liquidation_date'] ) )
 FechaRetiro="{{$person['liquidation_date']}}"
@endif

FechaLiquidacionInicio="{{$header->date_start_period}}"
FechaLiquidacionFin="{{$header->date_end_period}}"

TiempoLaborado="{{$person['historic_worked_time']}}"
FechaGen="{{ $header->date }}" />

<NumeroSecuenciaXML
CodigoTrabajador="{{$person['code'] }}"
Prefijo="{{$header->prefix}}" Consecutivo="{{$header->number}}" Numero="{{$header->code}}" />

<LugarGeneracionXML
Pais="{{$user->company->country->code}}"
DepartamentoEstado="{{$user->company->municipality->department->code}}"
MunicipioCiudad="{{$user->company->municipality->code}}"
Idioma="{{$user->company->language->code}}" />


<ProveedorXML
    RazonSocial="{{$user->company->merchant_registration}}"
   {{--
    ***CAMPOS OPCIONALES
    PrimerApellido="A"
    SegundoApellido="A"
    PrimerNombre="A"
    OtrosNombres="A"
    --}}

    NIT="{{$user->company->identification_number}}"
    DV="{{$user->company->dv}}"
    SoftwareID="{{$company->software_nomina->identifier}}"
    SoftwareSC="{{$company->software_nomina->identifier}}" />
<CodigoQR>{{$header->qr}}</CodigoQR>

<InformacionGeneral
Version="V1.0: Documento Soporte de Pago de Nómina Electrónica"
Ambiente="{{ $header->environment }}"
TipoXML="{{$typeDocument->code}}"
CUNE="{{$header->cune_propio}}"
EncripCUNE="{{$typeDocument->cufe_algorithm}}"
FechaGen="{{ $header->date }}"
HoraGen="{{ $header->hour }}"
PeriodoNomina="{{ $header->payroll_period }}"
TipoMoneda="{{ $company->type_currency->code }}"
TRM="0"
/>

<Notas>{{ $header->observation }}</Notas>

<Empleador
RazonSocial="{{$user->company->merchant_registration}}"

  {{--
    ***CAMPOS OPCIONALES
    PrimerApellido="A"
    SegundoApellido="A"
    PrimerNombre="A"
    OtrosNombres="A"
    --}}

NIT="{{$user->company->identification_number}}"
DV="{{$user->company->dv}}"
Pais="{{$user->company->country->code}}"
DepartamentoEstado="{{$user->company->municipality->department->code}}"
MunicipioCiudad="{{$user->company->municipality->code}}"
Direccion="{{$user->company->address}}"
/>

<Trabajador
TipoTrabajador="{{ $person['worker_type']['code'] }}"
SubTipoTrabajador="{{ $person['worker_subtype']['code'] }}"
AltoRiesgoPension="{{$person['high_risk_pension'] }}"
TipoDocumento="{{$person['type_document_identification']['code'] }}"
NumeroDocumento="{{$person['identifier']}}"
PrimerApellido="{{$person['last_name'] }}"

@if(isset($person['last_names']) && $person['last_names'] )
    SegundoApellido="{{$person['last_names'] }}"
@endif

PrimerNombre="{{$person['first_name'] }}"

@if(isset($person['middle_name']) && $person['middle_name'])
OtrosNombres="{{$person['middle_name'] }}"

@endif

LugarTrabajoPais="{{$person['work_place']['country']['code'] }}"
LugarTrabajoDepartamentoEstado="{{$person['work_place']['municipality']['department']['code'] }}"
LugarTrabajoMunicipioCiudad="{{$person['work_place']['municipality']['code'] }}"
LugarTrabajoDireccion="{{$person['work_place']['addres'] }}"
SalarioIntegral="{{$person['salary_integral'] }}"
TipoContrato="{{$person['work_contract_type']['code'] }}"
Sueldo="{{$person['salary'] }}"
CodigoTrabajador="{{$person['code'] }}" />


<Pago
Forma="{{$pay['payroll_pay_formate']['code']}}"
Metodo="{{$pay['payroll_pay_method']['code']}}"
Banco="A"
TipoCuenta="A"
NumeroCuenta="A" />


<FechasPagos>
    <FechaPago>{{$header->date_pay}}</FechaPago>
</FechasPagos>
