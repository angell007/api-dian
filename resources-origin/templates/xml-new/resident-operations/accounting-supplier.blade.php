<cac:AccountingSupplierParty>
	<cbc:AdditionalAccountID>{{$customer->company->type_organization->code}}</cbc:AdditionalAccountID>
	<cac:Party>
		<cac:PhysicalLocation>
			<cac:Address>
			<cbc:ID>{{$customer->company->municipality->code}}</cbc:ID>
                <cbc:CityName>{{$customer->company->municipality->name}}</cbc:CityName>
				<cbc:PostalZone>252007</cbc:PostalZone>
				<cbc:CountrySubentity>{{$customer->company->municipality->department->name}}</cbc:CountrySubentity>
				<cbc:CountrySubentityCode>{{$customer->company->municipality->department->code}}</cbc:CountrySubentityCode>
				<cac:AddressLine>
				<cbc:Line>{{$customer->company->address}}</cbc:Line>
				</cac:AddressLine>
				<cac:Country>
					<cbc:IdentificationCode>{{$customer->company->country->code}}</cbc:IdentificationCode>
					<cbc:Name languageID="es">{{$customer->company->country->name}}</cbc:Name>
				</cac:Country>
			</cac:Address>
	</cac:PhysicalLocation>
	@php
		$liabilityCode = $customer->company->type_liability->code ?? '';
		if ($liabilityCode === 'O-99') {
			$liabilityCode = 'O-47';
		}
		$listName = (substr($liabilityCode, 0, 2) === 'O-') ? '49' : '48';
		$docCode = $customer->company->type_document_identification->code ?? null;
		$isNit = $docCode === '31';
		$supplierDv = $isNit ? ($customer->company->dv ?? ($customer->dv ?? null)) : null;
	@endphp
	<cac:PartyTaxScheme>
		<cbc:RegistrationName>{{$customer->name}}</cbc:RegistrationName>
		<cbc:CompanyID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)"
			@if (!empty($supplierDv)) schemeID="{{$supplierDv}}" @endif
			schemeName="{{$docCode}}">{{$customer->company->identification_number}}</cbc:CompanyID>
		<cbc:TaxLevelCode listAgencyID="195" listAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)" listName="{{$listName}}">{{$liabilityCode}}</cbc:TaxLevelCode>
			<cac:TaxScheme>
				<cbc:ID>{{$customer->company->tax->code}}</cbc:ID>
				<cbc:Name>{{$customer->company->tax->name}}</cbc:Name>
			</cac:TaxScheme>
	</cac:PartyTaxScheme>
	</cac:Party>
</cac:AccountingSupplierParty>
