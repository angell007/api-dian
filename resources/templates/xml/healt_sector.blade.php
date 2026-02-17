<ext:UBLExtension>
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
                            <Value>{{ $healt_sector['Codigo_Prestador'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>TIPO_DOCUMENTO_IDENTIFICACION</Name>
                            <Value>{{ $healt_sector['Tipo_Documento_Identificacion'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>NUMERO_DOCUMENTO_IDENTIFICACION</Name>
                            <Value>{{ $healt_sector['Numero_Documento_Identificacion'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>PRIMER_NOMBRE</Name>
                            <Value>{{ $healt_sector['Primer_Nombre'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>MODALIDAD_PAGO</Name>
                            <Value schemeID="04" schemeName="salud_modalidad_pago.gc">
                                {{ $healt_sector['Modalidad_Contratacion'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>COBERTURA_PLAN_BENEFICIOS</Name>
                            <Value schemeID="02" schemeName="salud_cobertuta.gc">
                                {{ $healt_sector['Cobertura_Plan_Beneficios'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>NUMERO_CONTRATO</Name>
                            <Value>{{ $healt_sector['Numero_Contrato'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>NUMERO_POLIZA</Name>
                            <Value>{{ $healt_sector['Numero_Poliza'] ?? '' }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>COPAGO</Name>
                            <Value>{{ $healt_sector['Copago'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>CUOTA_MODERADORA</Name>
                            <Value>{{ $healt_sector['Cuota_Moderadora'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>CUOTA_RECUPERACION</Name>
                            <Value>{{ $healt_sector['Cuota_Recuperacion'] }}</Value>
                        </AdditionalInformation>
                        <AdditionalInformation>
                            <Name>PAGOS_COMPARTIDOS</Name>
                            <Value>{{ $healt_sector['Pagos_Compartidos'] }}</Value>
                        </AdditionalInformation>
                    </Collection>
                </Group>
            </Interoperabilidad>
        </CustomTagGeneral>
    </ext:ExtensionContent>
</ext:UBLExtension>
@endif
