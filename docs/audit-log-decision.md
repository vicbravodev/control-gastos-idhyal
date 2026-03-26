# Bitácora / activity log: decisión de arquitectura

**Contexto:** el PLAN de etapa 6 menciona `spatie/laravel-activitylog` como opción genérica de auditoría. El modelo de datos de etapa 2 ya incluye **`document_events`** con `subject` polimórfico, actor, tipo de evento, nota y `metadata` JSON.

## Decisión (2026-03, bloque Q8)

**No** se adopta Spatie `laravel-activitylog` en este momento. Se **continúa ampliando `document_events`** y el enum `DocumentEventType` para los hitos de negocio que deben quedar auditados.

### Motivos

1. **Un solo carril de auditoría de dominio:** los eventos ya están alineados con el glosario funcional (envío, rechazo, pago, comprobación, settlement, etc.) y referencian el documento correcto (`ExpenseRequest` como sujeto en la mayoría de flujos de gasto).
2. **Menos superficie operativa:** no hay segunda tabla ni convenciones paralelas; las políticas y tests siguen centrados en `DocumentEvent`.
3. **Coste de migración:** introducir Spatie implicaría dependencia nueva, configuración, y decidir qué duplicar o qué migrar desde `document_events`.

### MVP acordado (gastos)

- Registrar explícitamente los **hitos críticos** ya definidos en servicios (envío, rechazo, cancelación, cadena de aprobación completada, pago, comprobación, liquidación/cierre de settlement).
- **Mostrar** en la UI de detalle de solicitud una **línea de tiempo** legible para quien tenga `view` del gasto (misma regla que el resto del detalle).

### Cuándo reconsiderar Spatie

- Si surge la necesidad de **auditar cambios genéricos** (CRUD masivo, atributos arbitrarios) sin mapearlos a tipos de dominio.
- Si producto exige **formato o integraciones** típicas del paquete (p. ej. reportes estándar sobre `activity_log`).

Hasta entonces, nuevos hitos deben añadirse como casos en `DocumentEventType` y crearse en el servicio que ejecuta la transición.
