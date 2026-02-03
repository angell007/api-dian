	<cac:AccountingCustomerParty>
		<cbc:AdditionalAccountID>{{$user->company->type_organization->code}}</cbc:AdditionalAccountID>
		<cac:Party>
			@php
				$liabilityCode = $user->company->type_liability->code ?? '';
				$listName = (substr($liabilityCode, 0, 2) === 'O-') ? '49' : '48';
				$registrationName = $user->company->name
					?? $user->company->merchant_registration
					?? $user->company->identification_number;
				$docCode = $user->company->type_document_identification->code ?? null;
				$isNit = $docCode === '31';
				$customerIdentification = $user->company->identification_number;
				$customerDv = null;
				if ($isNit) {
					$digits = preg_replace('/\D+/', '', (string) $customerIdentification);
					if (strlen($digits) === 10) {
						$customerIdentification = substr($digits, 0, 9);
						$customerDv = substr($digits, 9, 1);
					} else {
						$customerIdentification = $digits;
						$customerDv = $user->company->dv ?? null;
					}
				}
			@endphp
			<cac:PartyTaxScheme>
				<cbc:RegistrationName>{{$registrationName}}</cbc:RegistrationName>
				<cbc:CompanyID schemeAgencyID="195" 
					schemeAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)" 
					@if ($isNit && !empty($customerDv))
						schemeID="{{$customerDv}}"
					@endif
					schemeName="{{$docCode}}">
					{{$customerIdentification}}
				</cbc:CompanyID>
				<cbc:TaxLevelCode listAgencyID="195" listAgencyName="CO, DIAN (Direcci&#xF3;n de Impuestos y Aduanas Nacionales)" listName="{{$listName}}">{{$user->company->type_liability->code}}</cbc:TaxLevelCode>
				<cac:TaxScheme>
					<cbc:ID>{{$user->company->tax->code}}</cbc:ID>
					<cbc:Name>{{$user->company->tax->name}}</cbc:Name>
				</cac:TaxScheme>
			</cac:PartyTaxScheme>
		</cac:Party>
	</cac:AccountingCustomerParty>
