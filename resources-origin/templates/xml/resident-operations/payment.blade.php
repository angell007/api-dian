<cac:PaymentMeans>
    <cbc:ID>{{$paymentForm->code}}</cbc:ID>
    <cbc:PaymentMeansCode>{{$paymentForm->payment_method_code}}</cbc:PaymentMeansCode>
    @if ($paymentForm->code == '2')
        <cbc:PaymentDueDate>{{$paymentForm->payment_due_date}}</cbc:PaymentDueDate>
    @endif
    <cbc:PaymentID>{{$paymentForm->payment_id}}</cbc:PaymentID>
</cac:PaymentMeans>


@foreach ($allowanceCharges as $key => $allowanceCharge)
    <cac:AllowanceCharge>
        <cbc:ID>{{($key + 1)}}</cbc:ID>
        <cbc:ChargeIndicator>{{$allowanceCharge->charge_indicator}}</cbc:ChargeIndicator>
        @if (($allowanceCharge->charge_indicator === 'false') && ($allowanceCharge->discount))
            <cbc:AllowanceChargeReasonCode>{{$allowanceCharge->discount->code}}</cbc:AllowanceChargeReasonCode>
        @endif
        <cbc:AllowanceChargeReason>{{$allowanceCharge->allowance_charge_reason}}</cbc:AllowanceChargeReason>
        <cbc:MultiplierFactorNumeric>{{$allowanceCharge->multiplier_factor_numeric}}</cbc:MultiplierFactorNumeric>
        <cbc:Amount currencyID="{{$company->type_currency->code}}">{{number_format($allowanceCharge->amount, 2, '.', '')}}</cbc:Amount>
        @if ($allowanceCharge->base_amount)
            <cbc:BaseAmount currencyID="{{$company->type_currency->code}}">{{number_format($allowanceCharge->base_amount, 2, '.', '')}}</cbc:BaseAmount>
        @endif
    </cac:AllowanceCharge>
@endforeach



<cac:TaxTotal>
	<cbc:TaxAmount currencyID="{{$company->type_currency->code}}">{{number_format($taxTotals[0]->tax_amount, 2, '.', '')}}</cbc:TaxAmount>
	@foreach ($taxTotals as $key => $taxTotal)
	<cac:TaxSubtotal>
		@if (!$taxTotal->is_fixed_value)
			<cbc:TaxableAmount currencyID="{{$company->type_currency->code}}">{{number_format($taxTotal->taxable_amount, 2, '.', '')}}</cbc:TaxableAmount>
		@endif
		<cbc:TaxAmount currencyID="{{$company->type_currency->code}}">{{number_format($taxTotal->tax_amount, 2, '.', '')}}</cbc:TaxAmount>
		
		<cac:TaxCategory>
			@if (!$taxTotal->is_fixed_value)
				<cbc:Percent>{{number_format($taxTotal->percent, 2, '.', '')}}</cbc:Percent>
			@endif
			<cac:TaxScheme>
				<cbc:ID>{{$taxTotal->tax->code}}</cbc:ID>
				<cbc:Name>{{$taxTotal->tax->name}}</cbc:Name>
			</cac:TaxScheme>
		</cac:TaxCategory>
	</cac:TaxSubtotal>
	@endforeach
</cac:TaxTotal>



@foreach ($withholdingTaxTotals as $key => $whtaxTotal)
<cac:WithholdingTaxTotal>
	<cbc:TaxAmount currencyID="{{$company->type_currency->code}}">{{number_format($whtaxTotal->tax_amount, 2, '.', '')}}</cbc:TaxAmount>
	<cac:TaxSubtotal>
		@if (!$whtaxTotal->is_fixed_value)
			<cbc:TaxableAmount currencyID="{{$company->type_currency->code}}">{{number_format($whtaxTotal->taxable_amount, 2, '.', '')}}</cbc:TaxableAmount>
		@endif
		<cbc:TaxAmount currencyID="{{$company->type_currency->code}}">{{number_format($whtaxTotal->tax_amount, 2, '.', '')}}</cbc:TaxAmount>
		
		<cac:TaxCategory>
			@if (!$whtaxTotal->is_fixed_value)
				<cbc:Percent>{{number_format($whtaxTotal->percent, 2, '.', '')}}</cbc:Percent>
			@endif
			<cac:TaxScheme>
				<cbc:ID>{{$whtaxTotal->tax->code}}</cbc:ID>
				<cbc:Name>{{$whtaxTotal->tax->name}}</cbc:Name>
			</cac:TaxScheme>
		</cac:TaxCategory>
	</cac:TaxSubtotal>
</cac:WithholdingTaxTotal>
@endforeach

<cac:LegalMonetaryTotal>
	<cbc:LineExtensionAmount currencyID="{{$company->type_currency->code}}">{{number_format($legalMonetaryTotals->line_extension_amount, 2, '.', '')}}</cbc:LineExtensionAmount>
	<cbc:TaxExclusiveAmount currencyID="{{$company->type_currency->code}}">{{number_format($legalMonetaryTotals->tax_exclusive_amount, 2, '.', '')}}</cbc:TaxExclusiveAmount>
	<cbc:TaxInclusiveAmount currencyID="{{$company->type_currency->code}}">{{number_format($legalMonetaryTotals->tax_inclusive_amount, 2, '.', '')}}</cbc:TaxInclusiveAmount>
	@if ($legalMonetaryTotals->allowance_total_amount)
		<cbc:AllowanceTotalAmount currencyID="{{$company->type_currency->code}}">{{number_format($legalMonetaryTotals->allowance_total_amount, 2, '.', '')}}</cbc:AllowanceTotalAmount>
	@endif

		<cbc:ChargeTotalAmount currencyID="{{$company->type_currency->code}}">{{number_format($legalMonetaryTotals->charge_total_amount, 2, '.', '')}}</cbc:ChargeTotalAmount>
	<cbc:PayableAmount currencyID="{{$company->type_currency->code}}">{{number_format($legalMonetaryTotals->payable_amount, 2, '.', '')}}</cbc:PayableAmount>
</cac:LegalMonetaryTotal>