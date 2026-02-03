<Devengados>
    <Basico DiasTrabajados="{{$accrued['basic']['worked_days']}}" SueldoTrabajado="{{$accrued['basic']['salary_payroll'] }}" />

    @if ( isset( $accrued['transport_subsidy'] ) )
    <Transporte 
        @if ( isset( $accrued['transport_subsidy']['salarial'] ) )
            AuxilioTransporte="{{$accrued['transport_subsidy']['salarial']}}" 
        @endif
  
            />
    @endif

    @if ( isset( $accrued['overtimes'] ) )
    @include('xml.partial_payroll.overtimes' , ['overtimes', $accrued['overtimes'] ] )
    @endif


    @if ( isset( $accrued['vacations'] ) )
    <Vacaciones>
        @if ( isset( $accrued['vacations']['cumn'] ) )
        <VacacionesComunes FechaInicio="{{ $accrued['vacations']['cumn']['date_start'] }}" FechaFin="{{ $accrued['vacations']['cumn']['date_end'] }}" Cantidad="{{ $accrued['vacations']['cumn']['days'] }}" Pago="{{ $accrued['vacations']['cumn']['value'] }}" />
        @endif

        @if ( isset( $accrued['vacations']['pays'] ) )
        <VacacionesCompensadas Cantidad="{{ $accrued['vacations']['pays']['days'] }}" Pago="{{ $accrued['vacations']['pays']['value'] }}" />
        @endif
    </Vacaciones>
    @endif

    @if ( isset( $accrued['prima'] ) )
    <Primas Cantidad="{{$accrued['days']}}" 
        @if ( isset( $accrued['value'] ) )
            Pago="{{$accrued['value']}}" 
        
        @endif
        @if ( isset( $accrued['value_ns'] ) )
            PagoNS="{{$accrued['value_ns']}}" />
        @endif
    @endif

    @if ( isset( $accrued['severance'] ) )
    <Cesantias Pago="{{ $accrued['severance']['value'] }}" Porcentaje="{{ $accrued['severance']['percentage'] }}" PagoIntereses="{{ $accrued['severance']['rate_pay'] }}" />
    @endif

    @if ( isset( $accrued['inabilities'] ) )
    <Incapacidades>
        @foreach ($accrued['inabilities'] as $inability)
        <Incapacidad FechaInicio="{{ $inability['date_start'] }}" FechaFin="{{ $inability['date_end'] }}" Cantidad="{{$inability['days']}}" Tipo="{{$inability['type']}}" Pago="{{$inability['value']}}" />
        @endforeach
    </Incapacidades>
    @endif

    @if ( isset( $accrued['licences'] ) && count( $accrued['licences'] ) > 0 )
    <Licencias>
        @if ( isset( $accrued['licences']['mp'] )) 
            @foreach ($accrued['licences']['mp'] as $mp)
            <LicenciaMP FechaInicio="{{ $mp['date_start'] }}" FechaFin="{{ $mp['date_start'] }}" Cantidad="{{ $mp['days'] }}" Pago="{{ $mp['value'] }}" />
            @endforeach
        @endif
        @if ( isset( $accrued['licences']['r'] )) 
            @foreach ($accrued['licences']['r'] as $r)
            <LicenciaR FechaInicio="{{ $r['date_start'] }}" FechaFin="{{ $r['date_start'] }}" Cantidad="{{ $r['days'] }}" Pago="{{ $r['value'] }}" />
            @endforeach
        @endif
        @if ( isset( $accrued['licences']['nr'] )) 
            @foreach ($accrued['licences']['nr'] as $nr)
            <LicenciaNR FechaInicio="{{ $nr['date_start'] }}" FechaFin="{{ $nr['date_start'] }}" Cantidad="{{ $nr['days'] }}" />
            @endforeach
        @endif
    </Licencias>
    @endif


    @if ( isset( $accrued['bonus'] ) && count( $accrued['bonus'] ) > 0 )
    <Bonificaciones>
        @foreach ($accrued['bonus'] as $bonus)
        <Bonificacion 
            @if ( isset( $bonus['salarial'] ) )
                BonificacionS="{{$bonus['salarial']}}" 
            @endif
            @if ( isset( $bonus['no_salarial'] ) )
                BonificacionNS="{{$bonus['no_salarial']}}" 
            @endif
            />
        @endforeach
    </Bonificaciones>
    @endif

    @if ( isset( $accrued['assistances'] ) && count( $accrued['assistances'] ) > 0 )
    <Auxilios>
        @foreach ($accrued['assistances'] as $assistence)
        <Auxilio 
            @if ( isset( $assistence['salarial'] ) )
                AuxilioS="{{  $assistence['salarial'] }}"
            @endif
            @if ( isset( $assistence['no_salarial'] ) )
                AuxilioNS="{{ $assistence['no_salarial'] }}"
            @endif
             />
        @endforeach
    </Auxilios>
    @endif

    @if ( isset( $accrued['legal_strikes'] ) && count( $accrued['legal_strikes'] ) > 0 )
    <HuelgasLegales>
        @foreach ($accrued['legal_strikes'] as $str)
        <HuelgaLegal FechaInicio="{{ $tr['date_start']}}" FechaFin="{{ $tr['date_end']}}" Cantidad="{{ $tr['days']}}" />
        @endforeach
    </HuelgasLegales>
    @endif

    @if ( isset( $accrued['others'] ) && count( $accrued['others'] ) > 0 )
    <OtrosConceptos>
        @foreach ($accrued['others'] as $other)
        <OtroConcepto
            @if ( isset( $other['description'] ) )
                DescripcionConcepto="{{ $other['description'] }}" 
             @endif
            @if ( isset($other['salarial'] ) )
                ConceptoS="{{ $other['salarial'] }}"
             @endif
             
            @if ( isset( $other['no_salarial'] ) )
                ConceptoNS="{{ $other['no_salarial'] }}"
            @endif
            
            />
        @endforeach
    </OtrosConceptos>
    @endif

    @if ( isset( $accrued['compensations'] ) && count( $accrued['compensations'] ) > 0 )
    <Compensaciones>
        @foreach ($accrued['compensations'] as $comp)
        <Compensacion CompensacionO="{{$comp['value_ordanary']}}" CompensacionE="{{$comp['value_extra_ordanary']}}" />
        @endforeach
    </Compensaciones>
    @endif

    @if ( isset( $accrued['bonus_epctvs'] ) && count( $accrued['bonus_epctvs'] ) > 0 )

    <BonoEPCTVs>
        @foreach ($accrued['bonus_epctvs'] as $bon)

        <BonoEPCTV PagoS="{{ $bon ['value_salarial']}}" PagoNS="{{ $bon ['value_no_salarial'] }}" PagoAlimentacionS="{{ $bon ['value_alim_salarial']}}" PagoAlimentacionNS="{{ $bon ['value_alim_no_salarial']}}" />
        @endforeach

    </BonoEPCTVs>
    @endif


    @if ( isset( $accrued['commissions'] ) && count( $accrued['commissions'] ) > 0 )
    <Comisiones>
        @foreach ($accrued['commissions'] as $commission)
        <Comision>{{$commission['value']}}</Comision>
        @endforeach
    </Comisiones>
    @endif

    @if ( isset( $accrued['third_payments'] ) && count( $accrued['third_payments'] ) > 0 )
    <PagosTerceros>
        @foreach ($accrued['third_payments'] as $pay)
        <PagoTercero>{{$pay['value']}}</PagoTercero>
        @endforeach
    </PagosTerceros>
    @endif
    @if ( isset( $accrued['advances'] ) && count( $accrued['advances'] ) > 0 )
    <Anticipos>
        @foreach ($accrued['advances'] as $advance)
        <Anticipo>{{$advance['value']}}</Anticipo>
        @endforeach
    </Anticipos>
    @endif

    @if ( isset( $accrued['endowment'] ) )
    <Dotacion> {{$accrued['endowment'] }} </Dotacion>
    @endif

    @if ( isset( $accrued['assitence_practical'] ) )
    <ApoyoSost>{{$accrued['assitence_practical'] }}</ApoyoSost>
    @endif

    @if ( isset( $accrued['remote_work'] ) )
    <Teletrabajo>{{$accrued['remote_work'] }}</Teletrabajo>
    @endif

    @if ( isset( $accrued['bono_leave'] ) )
    <BonifRetiro>{{$accrued['bono_leave'] }}</BonifRetiro>
    @endif

    @if ( isset( $accrued['compensation'] ) )
    <Indemnizacion>{{$accrued['compensation'] }}</Indemnizacion>
    @endif

    @if ( isset( $accrued['refund'] ) )
    <Reintegro>{{$accrued['refund'] }}</Reintegro>
    @endif

</Devengados>