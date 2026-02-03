@if ( isset($overtimes['heds']) && count( $overtimes['heds'] ) > 0)
<HEDs>
    @foreach ($overtimes['heds'] as $hed)
    <HED HoraInicio="{{$head['start'}}" HoraFin="{{$head['end'}}" Cantidad="{{$head['hours'}}" Porcentaje="{{$head['percentage'}}" Pago="{{$head['value'}}" />
    @endforeach
</HEDs>

@endif

@if ( isset($overtimes['hens']) && count( $overtimes['hens'] ) > 0)
<HENs>
    @foreach ($overtimes['hens'] as $hen)
    <HEN HoraInicio="{{$hen['start'}}" HoraFin="{{$hen['end'}}" Cantidad="{{$hen['hours'}}" Porcentaje="{{$hen['percentage'}}" Pago="{{$hen['value'}}" />
    @endforeach

</HENs>
@endif


@if ( isset($overtimes['hrns']) && count( $overtimes['hrns'] ) > 0)
<HRNs>
    @foreach ($overtimes['hrns'] as $hrn)
    <HRN HoraInicio="{{$hrn['start'}}" HoraFin="{{$hrn['end'}}" Cantidad="{{$hrn['hours'}}" Porcentaje="{{$hrn['percentage'}}" Pago="{{$hrn['value'}}" />
    @endforeach
</HRNs>
@endif

@if ( isset($overtimes['heddfs']) && count( $overtimes['heddfs'] ) > 0)
<HEDDFs>
    @foreach ($overtimes['heddfs'] as $heddf)
    <HEDDF HoraInicio="{{$heddf['start'}}" HoraFin="{{$heddf['end'}}" Cantidad="{{$heddf['hours'}}" Porcentaje="{{$heddf['percentage'}}" Pago="{{$heddf['value'}}" />
    @endforeach

</HEDDFs>
@endif

@if ( isset($overtimes['hrddfs']) && count( $overtimes['hrddfs'] ) > 0)
<HRDDFs>
    @foreach ($overtimes['hrddfs'] as $hrddf)
    <HRDDF HoraInicio="{{$hrddf['start'}}" HoraFin="{{$hrddf['end'}}" Cantidad="{{$hrddf['hours'}}" Porcentaje="{{$hrddf['percentage'}}" Pago="{{$hrddf['value'}}" />
    @endforeach

</HRDDFs>
@endif

@if ( isset($overtimes['hendfs']) && count( $overtimes['hendfs'] ) > 0)
<HENDFs>
    @foreach ($overtimes['hendfs'] as $hendf )
    <HENDF HoraInicio="{{$hendf['start'}}" HoraFin="{{$hendf['end'}}" Cantidad="{{$hendf['hours'}}" Porcentaje="{{$hendf['percentage'}}" Pago="{{$hendf['value'}}" />
    @endforeach

</HENDFs>
@endif

@if ( isset($overtimes['hrndfs']) && count( $overtimes['hrndfs'] ) > 0)
<HRNDFs>
    @foreach ($overtimes['hrndfs'] as $hrndf )
    <HRNDF HoraInicio="{{$hrndf['start'}}" HoraFin="{{$hrndf['end'}}" Cantidad="{{$hrndf['hours'}}" Porcentaje="{{$hrndf['percentage'}}" Pago="{{$hrndf['value'}}" />
    @endforeach
</HRNDFs>
@endif