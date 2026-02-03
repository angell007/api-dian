<Deducciones>
    <Salud  Porcentaje="{{ $deductions['healt']['percentage'] }}" Deduccion="{{ $deductions['healt']['deduction'] }}" />

    <FondoPension  Porcentaje="{{ $deductions['pension_funds']['percentage'] }}" Deduccion="{{ $deductions['pension_funds']['deduction'] }}" />

    @if ( isset($deductions['security_pension_funds']) )

        <FondoSP  
        Porcentaje="{{ $deductions['security_pension_funds']['percentage'] }}"
        Deduccion="{{ $deductions['security_pension_funds']['deduction'] }}" 
        PorcentajeSub="{{ $deductions['security_pension_funds']['percentage_sub'] }}"
        DeduccionSub="{{ $deductions['security_pension_funds']['deduction_sub'] }}" />

    @endif

    @if ( isset($deductions['laboral_unions']) && count( $deductions['laboral_unions'] ) > 0)
        <Sindicatos>
            
            @foreach ($deductions['laboral_unions'] as $union)
                <Sindicato  Porcentaje="{{$union['percentage']}}" Deduccion="{{$union['deduction']}}" />
            @endforeach
        </Sindicatos>
    @endif

    @if ( isset($deductions['sanctions']) && count( $deductions['sanctions'] ) > 0)
        <Sanciones>
            @foreach ($deductions['sanctions'] as $sanction)
            <Sancion SancionPublic="{{$sanction['public']}}" SancionPriv="{{$sanction['private']}}" />
            @endforeach
        </Sanciones>
    @endif

    @if ( isset($deductions['loans']) && count( $deductions['loans'] ) > 0)
    <Libranzas>
        @foreach ($deductions['loans'] as $loan)
            <Libranza Descripcion="{{ $loan['description'] }}" Deduccion="{{ $loan['value'] }}" />
        @endforeach
    </Libranzas>
    @endif
    
    @if ( isset($deductions['third_payments']) && count( $deductions['third_payments'] ) > 0)
        <PagosTerceros>
            @foreach ($deductions['third_payments'] as $pay)
                <PagoTercero>{{ $pay['value'] }}</PagoTercero>
            @endforeach
        </PagosTerceros>
    @endif

    @if ( isset($deductions['advances']) && count( $deductions['advances'] ) > 0)

        <Anticipos>
            @foreach ($deductions['advances'] as $advance)
                <Anticipo>{{$advance['value']}}</Anticipo>
            @endforeach
            
        </Anticipos>
    @endif

    @if ( isset($deductions['other_deductions']) && count( $deductions['other_deductions'] ) > 0)

        <OtrasDeducciones>
            @foreach ($deductions['other_deductions'] as $other)
                <OtraDeduccion>{{$other['value']}}</OtraDeduccion>
            @endforeach

        </OtrasDeducciones>

    @endif

    @if ( isset( $deductions['voluntary_pension'] ) )
        <PensionVoluntaria>$deductions['voluntary_pension']</PensionVoluntaria>
    @endif
    

    @if ( isset($deductions['source_retention']) )
        <RetencionFuente> {{$deductions['source_retention']}} </RetencionFuente>
    @endif

    @if ( isset($deductions['afc']) )
        <AFC>$deductions['afc']</AFC>
    @endif
    
    @if ( isset($deductions['laboral_unions']) )

        <Cooperativa>0.00</Cooperativa>
    @endif

    @if ( isset( $deductions['cooperative'] ) )

        <EmbargoFiscal>$deductions['cooperative']</EmbargoFiscal>
    @endif

    @if ( isset($deductions['supplemental_plan'] ) )

        <PlanComplementarios> {{ $deductions['supplemental_plan'] }} </PlanComplementarios>
    @endif

    @if ( isset($deductions['education']) )

        <Educacion>{{ $deductions['education'] }}</Educacion>
    @endif

    @if ( isset($deductions['refund']) )
        <Reintegro>{{ $deductions['refund'] }}</Reintegro>
    @endif

    @if ( isset($deductions['debt']) )
        <Deuda>{{ $deductions['debt'] }}</Deuda>
    @endif

</Deducciones>