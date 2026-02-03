<ext:UBLExtension>
			<!--Estructura para reporte de información adicional específica para el sector salud-->
	<ext:ExtensionContent>
		<CustomTagGeneral>
			<Name>Responsable</Name>
			<Value>url www.minSalud.gov.co</Value>
			<Name>Tipo, identificador:año del acto administrativo</Name>
			<Value>Resolución 084:2021</Value>
			<Interoperabilidad>
				<Group schemeName="Sector Salud">
					<Collection schemeName="Usuario">
						<AdditionalInformation>
							<Name>CODIGO_PRESTADOR</Name>
							<Value>{{$healt_sector['Codigo_Prestador']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>TIPO_DOCUMENTO_IDENTIFICACION</Name>
							<Value schemeID="Registro Civil de Nacimiento"
 									schemeName="salud_identificación.gc">{{$healt_sector['Tipo_Documento_Identificacion']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>NUMERO_DOCUMENTO_IDENTIFICACION</Name>
							<Value>{{$healt_sector['Numero_Documento_Identificacion']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>PRIMER_APELLIDO</Name>
							<Value>{{$healt_sector['Primer_Apellido']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>SEGUNDO_APELLIDO</Name>
							<Value>{{$healt_sector['Segundo_Apellido']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>PRIMER_NOMBRE</Name>
							<Value>{{$healt_sector['Primer_Nombre']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>SEGUNDO_NOMBRE</Name>
							<Value>{{$healt_sector['Segundo_Nombre']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>TIPO_USUARIO</Name>
							<Value schemeID="01"
 									schemeName="salud_tipo_usuario.gc">{{$healt_sector['Tipo_Usuario']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>MODALIDAD_CONTRATACION</Name>
							<Value schemeID="06"
 									schemeName="salud_modalidad_pago.gc">{{$healt_sector['Modalidad_Contratacion']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>COBERTURA_PLAN_BENEFICIOS</Name>
							<Value schemeID="10"
 									schemeName="salud_cobertuta.gc">{{$healt_sector['Cobertura_Plan_Beneficios']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>NUMERO_AUTORIZACION</Name>
							<Value>{{$healt_sector['Numero_Autorizacion']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>NNUMERO_MIPRES</Name>
							<Value>{{$healt_sector['Nnumero_Mipres']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>NUMERO_ENTREGA_MIPRES</Name>
							<Value>{{$healt_sector['Numero_Entrega_Mipres']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>NUMERO_CONTRATO</Name>
							<Value>{{$healt_sector['Numero_Contrato']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>NUMERO_POLIZA</Name>
							<Value>{{$healt_sector['Numero_Poliza']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<!-- revise y adecue desde aqui -->
							<Name>COPAGO</Name>
							<Value>{{$healt_sector['Copago']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>CUOTA_MODERADORA</Name>
							<Value>{{$healt_sector['Cuota_Moderadora']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>CUOTA_RECUPERACION</Name>
							<Value>{{$healt_sector['Cuota_Recuperacion']}}</Value>
						</AdditionalInformation>
						<AdditionalInformation>
							<Name>PAGOS_COMPARTIDOS</Name>
							<Value>{{$healt_sector['Pagos_Compartidos']}}</Value>
						</AdditionalInformation>
					</Collection>
				</Group>
			</Interoperabilidad>
		</CustomTagGeneral>
	</ext:ExtensionContent>
</ext:UBLExtension>