
@foreach ($invoiceLines as $key => $invoiceLine)

<cac:InvoiceLine>
    <cbc:ID>{{($key + 1)}}</cbc:ID>
    <cbc:Note>{{$invoiceLine->note}}</cbc:Note>
    <cbc:InvoicedQuantity unitCode="{{$invoiceLine->unit_measure->code}}">{{number_format($invoiceLine->invoiced_quantity, 6, '.', '')}}</cbc:InvoicedQuantity>
    <cbc:LineExtensionAmount currencyID="{{$company->type_currency->code}}">{{number_format($invoiceLine->line_extension_amount, 2, '.', '')}}</cbc:LineExtensionAmount>
      
    <cac:InvoicePeriod>
        <cbc:StartDate>{{$invoiceLine->invoice_period->date}}</cbc:StartDate>
        <cbc:DescriptionCode>{{$invoiceLine->invoice_period->description_code}}</cbc:DescriptionCode>
        <cbc:Description>{{$invoiceLine->invoice_period->description}}</cbc:Description>
    </cac:InvoicePeriod>
    
    @foreach ($invoiceLine->allowance_charges as $key => $allowanceCharge)
        <cac:AllowanceCharge>
            <cbc:ID>{{($key + 1)}}</cbc:ID>
            <cbc:ChargeIndicator>{{$allowanceCharge->charge_indicator}}</cbc:ChargeIndicator>
        
            <cbc:AllowanceChargeReason>{{$allowanceCharge->allowance_charge_reason}}</cbc:AllowanceChargeReason>
            <cbc:MultiplierFactorNumeric>{{$allowanceCharge->multiplier_factor_numeric}}</cbc:MultiplierFactorNumeric>
            <cbc:Amount currencyID="{{$company->type_currency->code}}">{{number_format($allowanceCharge->amount, 2, '.', '')}}</cbc:Amount>
            @if ($allowanceCharge->base_amount)
                <cbc:BaseAmount currencyID="{{$company->type_currency->code}}">{{number_format($allowanceCharge->base_amount, 2, '.', '')}}</cbc:BaseAmount>
            @endif
        </cac:AllowanceCharge>
    @endforeach

    <cac:TaxTotal>
        <cbc:TaxAmount currencyID="{{$company->type_currency->code}}">{{number_format($invoiceLine->tax_totals[0]->tax_amount, 2, '.', '')}}</cbc:TaxAmount>
        @foreach ($invoiceLine->tax_totals as $key => $taxTotal)
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
    
    @foreach ($invoiceLine->withholding_tax_totals as $key => $whtaxTotal)
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


    <cac:Item>
        <cbc:Description>{{$invoiceLine->description}}</cbc:Description>

        <cbc:PackSizeNumeric>{{$invoiceLine->pack_size_numeric}}</cbc:PackSizeNumeric>
        @if ($invoiceLine->brand_name)
            <cbc:BrandName>{{$invoiceLine->brand_name}}</cbc:BrandName>
            @if ($invoiceLine->model_name)
                <cbc:ModelName>{{$invoiceLine->model_name}}</cbc:ModelName>
            @endif
          @endif
        @if ($invoiceLine->sellers_item_identification)
            <cac:SellersItemIdentification>
            @if ($invoiceLine->sellers_item_identification->id)
                <cbc:ID>{{$invoiceLine->sellers_item_identification->id}}</cbc:ID>
            @endif
            @if ($invoiceLine->sellers_item_identification->code)
                <cbc:ExtendedID>{{$invoiceLine->sellers_item_identification->code}}</cbc:ExtendedID>
            @endif
            </cac:SellersItemIdentification>
         @endif
        <cac:StandardItemIdentification>
                <cbc:ID schemeID="{{$invoiceLine->type_item_identification->code}}" schemeName="EAN13" schemeAgencyID="{{$invoiceLine->type_item_identification->code_agency}}">{{$invoiceLine->code}}</cbc:ID>
        </cac:StandardItemIdentification>
    </cac:Item>


    <cac:Price>
            <cbc:PriceAmount currencyID="{{$company->type_currency->code}}">{{number_format(($invoiceLine->free_of_charge_indicator === 'true') ? 0 : $invoiceLine->price_amount, 2, '.', '')}}</cbc:PriceAmount>
            <cbc:BaseQuantity unitCode="{{$invoiceLine->unit_measure->code}}">{{number_format($invoiceLine->base_quantity, 6, '.', '')}}</cbc:BaseQuantity>
    </cac:Price>
</cac:InvoiceLine>




@endforeach

