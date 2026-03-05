# Plan Maestro de Orquestación de Subagentes

## 1) Propósito
Este plan está diseñado para que subagentes ejecutores puedan trabajar de forma autónoma, coordinada y auditable sobre `api-dian`, con el objetivo de convertirlo en una plataforma modular, escalable y mantenible, donde el núcleo de negocio no dependa de Laravel.

## 2) Alcance funcional del proyecto (base real del repo)
Los subagentes deben preservar y mejorar estos procesos ya existentes:

- Facturación electrónica de venta (`/api/ubl2.1/invoice`)
- Facturación sector salud (extensión en factura con `healt_sector` y `SS-SinAporte`)
- Nómina electrónica (`/api/ubl2.1/payroll`)
- Nota de ajuste de nómina (`/api/ubl2.1/payroll-note`)
- Documento soporte (`/api/ubl2.1/support-document`)
- Eventos de recepción/aceptación/rechazo y consulta de estado/numbering

## 3) Principios de arquitectura obligatorios
Todos los subagentes deben cumplir esto:

- Regla A: `Domain` y `Application` no importan `Illuminate\\*` ni clases de framework.
- Regla B: Controllers solo traducen request/response; no contienen reglas de negocio.
- Regla C: Toda integración externa se modela por puertos (`interfaces`) + adaptadores.
- Regla D: Cambios incrementales, sin romper endpoints actuales.
- Regla E: Cada entrega incluye pruebas automáticas y criterios de aceptación verificables.

## 4) Estructura objetivo

- `packages/shared-kernel`
- `packages/core-domain`
- `packages/core-application`
- `packages/module-billing-sales`
- `packages/module-billing-health`
- `packages/module-payroll`
- `packages/module-support-document`
- `packages/module-events-radian`
- `apps/laravel-api` (adaptador HTTP temporal)

Si no se crea esta estructura exacta, se debe justificar en ADR técnico.

## 5) Modelo de orquestación

### 5.1 Roles
- `Orchestrator`: planifica, asigna, desbloquea, valida calidad y merge.
- `Executor`: implementa una unidad de trabajo específica.
- `Reviewer`: audita riesgos funcionales, regresiones y estándares de arquitectura.

### 5.2 Flujo estándar de trabajo
1. Orchestrator crea lote de trabajo con objetivo y DoD.
2. Executor toma una tarea, ejecuta cambios pequeños y testeables.
3. Reviewer valida calidad técnica y funcional.
4. Orchestrator aprueba, integra y habilita siguiente lote.

### 5.3 Reglas de entrega por tarea
- PR pequeño, atómico, reversible.
- Sin deuda silenciosa: todo TODO debe quedar en backlog.
- Con evidencia: pruebas, logs de validación y lista de archivos tocados.

## 6) Plan de ejecución por fases para subagentes

## Fase 0: Baseline y control
Objetivo: crear línea base técnica y funcional.

Tareas:
1. Inventario de endpoints y contratos actuales.
2. Identificación de acoplamientos (Controller-Model-XML-SOAP).
3. Definir métricas base (latencia, errores DIAN, cobertura).

DoD:
- Documento `docs/BASELINE.md` con mapa de sistema actual.
- Lista priorizada de riesgos y quick wins.

## Fase 1: Shared Kernel y reglas de arquitectura
Objetivo: fundación agnóstica al framework.

Tareas:
1. Crear `Result`, errores de dominio y utilidades compartidas.
2. Crear interfaces transversales: `Clock`, `LoggerPort`, `IdGenerator`.
3. Configurar reglas estáticas para bloquear imports Laravel en Core.

DoD:
- `packages/shared-kernel` con tests.
- Validación estática activa en CI.

## Fase 2: Extracción de casos de uso críticos
Objetivo: mover lógica de negocio fuera de controllers.

Tareas:
1. Extraer `CreateInvoiceUseCase`.
2. Extraer `CreatePayrollUseCase`.
3. Extraer `CreatePayrollNoteUseCase`.

DoD:
- Controllers llaman casos de uso, no reglas internas.
- Pruebas unitarias de casos de uso > 80% cobertura.

## Fase 3: Modularización funcional
Objetivo: separar por dominio.

Tareas:
1. Consolidar `Billing.Sales`.
2. Separar `Billing.Health` como extensión explícita.
3. Consolidar `Payroll` y `SupportDocument`.
4. Separar `EventsRADIAN`.

DoD:
- Cada módulo con su namespace, puertos y tests.
- Dependencias entre módulos explícitas y mínimas.

## Fase 4: Adaptadores de infraestructura
Objetivo: encapsular DIAN, persistencia y XML.

Tareas:
1. `DianGatewayAdapter` (SOAP).
2. `XmlRendererAdapter`.
3. `SignatureAdapter`.
4. `RepositoryAdapters` para persistencia.

DoD:
- Ningún caso de uso conoce implementación concreta.
- Cambiar adaptador no impacta dominio.

## Fase 5: Asincronía, resiliencia y observabilidad
Objetivo: operación escalable.

Tareas:
1. Cola para envío/consulta de estado DIAN.
2. Reintentos idempotentes con control por documento.
3. Correlation ID + trazabilidad completa.

DoD:
- Dashboard de métricas por módulo.
- Runbook operativo y alertas mínimas.

## Fase 6: Migración progresiva y hardening
Objetivo: transición segura sin downtime lógico.

Tareas:
1. Feature flags por flujo.
2. Shadow mode para comparar legacy vs nuevo.
3. Cierre de deuda técnica legacy.

DoD:
- Flujos productivos ejecutándose en arquitectura modular.
- Plan de rollback probado.

## 7) Matriz de subagentes ejecutores

| Subagente | Misión | Entradas | Salidas | Dependencias |
|---|---|---|---|---|
| SA-01 Arquitectura Core | Definir y crear estructura agnóstica | Baseline + reglas | Paquetes base + ADR | Ninguna |
| SA-02 Extractor Sales | Migrar facturación venta a UseCase | InvoiceController + Request | `CreateInvoiceUseCase` + tests | SA-01 |
| SA-03 Extractor Health | Aislar sector salud en módulo propio | Plantillas + lógica `healt_sector` | `Billing.Health` + tests de XML | SA-02 |
| SA-04 Extractor Payroll | Migrar nómina y ajuste | Payroll Controllers | UseCases nómina + pruebas | SA-01 |
| SA-05 Extractor Support | Migrar documento soporte | SupportDocumentController | UseCase soporte + contratos | SA-01 |
| SA-06 Integraciones DIAN | Encapsular firma/SOAP/zip/status | Lógica actual de envío | Adapters + puertos + pruebas integración | SA-02..05 |
| SA-07 API Adapter | Mantener compatibilidad HTTP actual | rutas y requests | Controllers delgados + mapeadores DTO | SA-02..06 |
| SA-08 QA/Release | Pruebas de regresión y release gates | Todo lo anterior | Informe de calidad + checklist go-live | SA-01..07 |

## 8) Checklist global de calidad (gates)
Ningún subagente cierra tarea si falla uno:

1. Sin imports de framework en Core.
2. Casos de uso con pruebas unitarias.
3. Contrato HTTP compatible con API actual.
4. XML generado igual o equivalente válido contra reglas DIAN.
5. Logging con correlation id en procesos críticos.
6. Documentación de cambio y riesgos.

## 9) Formato de tarea para el Orchestrator

Plantilla obligatoria:

```md
## Task-ID: <ID>
### Objetivo
<resultado medible>

### Contexto
<archivos/modulos involucrados>

### Restricciones
- No usar clases de framework en Domain/Application
- Mantener compatibilidad endpoint actual

### Entregables
- Código
- Pruebas
- Nota técnica (decisiones)

### DoD
- [ ] pruebas unitarias
- [ ] pruebas integración mínimas
- [ ] lint/static analysis
- [ ] evidencia de compatibilidad
```

## 10) Prompts óptimos para subagentes ejecutores

Usar estos prompts literalmente, reemplazando variables entre `<>`.

### 10.1 Prompt base del Orchestrator
```text
Actúa como Orchestrator técnico senior del proyecto api-dian.
Objetivo global: modularizar y desacoplar la lógica de negocio del framework, preservando funcionalidad DIAN existente.

Reglas obligatorias:
1) Domain/Application no deben depender de Laravel.
2) Cambios incrementales, reversibles y testeables.
3) Prioriza riesgo funcional DIAN sobre refactor estético.
4) Cada tarea debe tener DoD verificable y evidencia.

Entradas:
- Estado actual: <estado_actual>
- Fase activa: <fase_activa>
- Dependencias cumplidas: <dependencias>

Entrega:
- Backlog priorizado (máximo 5 tareas)
- Orden de ejecución
- Riesgos por tarea
- Criterios de aceptación por tarea
```

### 10.2 Prompt SA-01 Arquitectura Core
```text
Actúa como arquitecto de software enfocado en hexagonal + DDD pragmático.

Tarea: crear la base agnóstica al framework para api-dian.

Debes:
1) Definir estructura de paquetes core/shared.
2) Crear contratos (ports) transversales.
3) Establecer reglas estáticas para impedir imports de framework en Core.
4) Entregar ADR con decisiones y trade-offs.

No debes:
- mover lógica de negocio aún
- romper endpoints actuales

Resultado esperado:
- estructura base funcional
- reglas de arquitectura ejecutables
- pruebas mínimas de shared-kernel
```

### 10.3 Prompt SA-02 Facturación Venta
```text
Actúa como especialista en refactor de sistemas legacy con enfoque en seguridad funcional.

Tarea: extraer el caso de uso de facturación de venta desde controllers Laravel hacia Application/Domain.

Debes:
1) identificar lógica de negocio en InvoiceController.
2) crear CreateInvoiceUseCase + DTOs in/out.
3) dejar el controller como adaptador HTTP.
4) mantener contrato actual del endpoint.
5) crear pruebas unitarias del caso de uso y regresión básica.

Criterio crítico:
- el XML y el envío a DIAN deben mantener comportamiento funcional.
```

### 10.4 Prompt SA-03 Sector Salud
```text
Actúa como especialista en interoperabilidad de sector salud sobre facturación electrónica.

Tarea: independizar la lógica de sector salud en módulo Billing.Health.

Debes:
1) aislar validaciones y mapeos de healt_sector.
2) modelar reglas de CustomizationID SS-SinAporte.
3) separar plantillas/constructores de extensión salud.
4) incluir pruebas con payloads válidos e inválidos.

No debes:
- mezclar reglas health dentro del módulo sales

Éxito:
- módulo health invocable como extensión explícita del flujo de factura.
```

### 10.5 Prompt SA-04 Nómina
```text
Actúa como especialista en nómina electrónica DIAN.

Tarea: extraer y modularizar los flujos de nómina y nota de ajuste.

Debes:
1) crear casos de uso independientes (crear nómina, crear ajuste).
2) separar construcción de header/person/pay/accrued/deductions/totals.
3) encapsular firma/envío mediante puertos.
4) preservar endpoints actuales y respuesta esperada.

Éxito:
- módulo Payroll desacoplado de framework y con pruebas.
```

### 10.6 Prompt SA-05 Documento Soporte
```text
Actúa como especialista en documento soporte DIAN.

Tarea: modularizar el flujo de soporte en un caso de uso independiente.

Debes:
1) extraer reglas de validación de negocio.
2) separar mapeo de origin_reference, payment_form y tax totals.
3) encapsular firma/envío/zip en puertos.
4) mantener compatibilidad funcional del endpoint.

Entregar:
- UseCase
- puertos requeridos
- pruebas unitarias y de integración mínima
```

### 10.7 Prompt SA-06 Integraciones DIAN
```text
Actúa como ingeniero de integración para sistemas tributarios.

Tarea: encapsular firma, SOAP DIAN, consulta de estado y numeración en adaptadores de infraestructura.

Debes:
1) definir DianGatewayPort + SignaturePort + XmlPort.
2) crear adapters concretos reutilizando implementación actual.
3) normalizar errores técnicos en un modelo común.
4) preparar soporte para reintentos idempotentes.

Éxito:
- Application usa solo interfaces, no clases concretas de integración.
```

### 10.8 Prompt SA-07 API Adapter
```text
Actúa como backend engineer orientado a compatibilidad de contratos.

Tarea: mantener Laravel solo como capa de transporte.

Debes:
1) convertir controllers en mapeadores Request->Command y Result->Response.
2) minimizar lógica en FormRequest al nivel sintáctico.
3) no romper rutas ni estructura de respuesta vigente.
4) documentar cambios de mapeo en OpenAPI interno.

Éxito:
- API pública compatible y core desacoplado.
```

### 10.9 Prompt SA-08 QA y Release
```text
Actúa como QA lead con foco en regresión funcional DIAN.

Tarea: validar que la migración modular no rompe procesos críticos.

Debes:
1) ejecutar suite de regresión por módulo.
2) validar payloads reales representativos.
3) certificar compatibilidad de respuestas API.
4) emitir reporte Go/No-Go con riesgos.

Salida:
- matriz de pruebas
- defectos críticos/altos
- recomendación final de despliegue
```

## 11) Protocolo de comunicación entre subagentes
Formato obligatorio por actualización:

```md
[Agent-ID]
- Estado: <in_progress|blocked|done>
- Task-ID: <id>
- Cambios: <resumen corto>
- Evidencia: <tests/archivos>
- Riesgos: <si/no + detalle>
- Siguiente paso: <acción inmediata>
```

## 12) Orden sugerido de ejecución real
1. SA-01
2. SA-02 y SA-04 (paralelo)
3. SA-03 y SA-05 (paralelo)
4. SA-06
5. SA-07
6. SA-08

## 13) Definición de éxito del programa
El programa se considera exitoso si:

1. Los procesos de venta, salud y nómina funcionan con el nuevo core.
2. La lógica de negocio se ejecuta sin dependencia de Laravel.
3. Los módulos son desplegables/evolucionables con mínimo acoplamiento.
4. Hay evidencia automatizada de no regresión funcional.

