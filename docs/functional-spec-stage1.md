# Especificación funcional — Etapa 1: Alineación de alcance y estados

**Versión:** 1.0  
**Alcance:** vocabulario, máquinas de estado, matriz de eventos (sin código).  
**Convención de estados:** inglés, `snake_case`.

---

## 1. Contexto de negocio (ancla de requerimiento)

### 1.1 Visión

Sistema transaccional con **trazabilidad fuerte (auditoría)** que administra:

1. **Ciclo financiero de gastos:** solicitud → aprobaciones por rol (workflow configurable) → pago por contabilidad con evidencia → comprobación del usuario (PDF/XML como evidencia tipo CFDI en México, **sin timbrado** obligatorio) → revisión contable → balance/cuadre (settlement) → liquidación y cierre. **Crear una solicitud no libera ni reserva fondos** hasta que las reglas de negocio y presupuesto lo indiquen.

2. **Ciclo de vacaciones:** días disponibles según tabla configurable (antigüedad), reglas de límites por periodo, y **motor de aprobaciones por rol** análogo al de gastos.

3. **Presupuestos (budgets) morfables:** aplicables por región, estado (entidad federativa), usuario o rol; **ledger** con historial de movimientos (commit/spend/reverse/adjust). Moneda operativa: **MXN**; montos en implementación posterior en **centavos (entero)**.

4. **Recibos y evidencias:**  
   - **Evidencia:** archivos subidos (PDF, imagen, XML).  
   - **Recibo interno:** PDF generado por el sistema que constata un evento (auditoría e impresión).

### 1.2 Usuarios y territorio

- Usuario: nombre, email, username, teléfono, **rol** (jerarquía organizacional), **región** y **estado** (catálogos normalizados; `state` = división territorial, no “estatus” del documento).

### 1.3 Roles organizacionales (catálogo inicial)

| Clave técnica (sugerida) | Nombre negocio        |
|--------------------------|------------------------|
| `super_admin`            | Super administrador    |
| `secretario_general`     | Secretario general     |
| `contabilidad`           | Contabilidad           |
| `coord_regional`         | Coordinador regional   |
| `coord_estatal`          | Coordinador estatal    |
| `asesor`                 | Asesor                 |

**Autorización** (quién puede qué pantalla/acción) se modelará con permisos; **workflow de aprobación** se modelará con **políticas configurables** por tipo de documento y rol del solicitante (no sustituyen la jerarquía, la complementan).

### 1.4 Decisiones ya acordadas para etapas posteriores

| Tema | Decisión |
|------|----------|
| CFDI / XML | Almacenar XML y PDF como evidencia auditables; no se exige timbrado desde el sistema. |
| Descuento presupuesto | **Dos fases en ledger:** al estado `approved` de la solicitud → entrada tipo **commit** (compromiso); al estado `paid` → **spend** (real) y cierre/consumo del compromiso según reglas de ledger documentadas en etapa de implementación. |
| Resolución multi-presupuesto | Por defecto en especificación: **“más específico gana”**: `user` > `role`+`state` > `state` > `region`. (Si existiera solapamiento, etapa 2 puede añadir prioridad numérica.) |

---

## 2. Glosario (negocio ES / clave EN)

| Término negocio (ES) | Clave / término técnico (EN) | Definición |
|----------------------|------------------------------|------------|
| Solicitud de gasto | `expense_request` | Documento que inicia el ciclo; monto, concepto, fechas, método de entrega (efectivo/transferencia). No implica desembolso automático. |
| Comprobación | `expense_report` | Documento con monto comprobado y evidencias (PDF obligatorio; XML obligatorio salvo política que marque XML opcional — **TBD política configurable**). |
| Balance / cuadre | `settlement` | Resultado de comparar monto solicitado/aprobado/pagado vs monto comprobado; define si el usuario debe devolver o la empresa debe pagar la diferencia. |
| Recibo interno | `system_receipt` | PDF generado por la aplicación que documenta un evento (folio interno, hash opcional en implementación). |
| Evidencia | `evidence` / attachment | Archivo externo (PDF, imagen, XML) asociado a pago, comprobación, liquidación, etc. |
| Presupuesto | `budget` | Cupo por periodo asociado polimórficamente a región, estado, usuario o rol. |
| Movimiento de presupuesto | `budget_ledger_entry` | Registro inmutable de afectación: tipo `commit`, `spend`, `reverse`, `adjust`; referencia al origen (p. ej. solicitud aprobada, pago). |
| Política | `approval_policy` | Conjunto versionable de reglas que define, por tipo de documento y rol solicitante, los pasos de aprobación requeridos. |
| Paso de aprobación | `approval_policy_step` | Orden, rol requerido, y reglas AND/OR por paso (por defecto AND entre pasos). |
| Aprobación concreta | `expense_request_approval` / `vacation_request_approval` | Instancia generada al crear el documento: pendiente hasta que un usuario con el rol requerido apruebe o rechace. |
| Rol | `role` (Spatie u homólogo) | Rol de aplicación para permisos; alineado con rol organizacional donde aplique. |
| Folio | `folio` / human-readable id | Identificador legible para recibos y auditoría (formato **TBD** en implementación). |
| Rechazo | `rejection` | Denegación dentro del flujo de aprobación o revisión; **nota obligatoria**. |
| Cancelación | `cancellation` | Anulación del documento con **nota obligatoria**; actor y momentos permitidos en máquinas de estado. |
| Motor de políticas | approval engine | Servicio que, al crear documento, genera registros de aprobación pendientes según política activa. |
| Objetivo polimórfico | `budgetable` / `receiptable` | Patrón morph: un presupuesto o recibo “pertenece” a uno entre varios tipos de modelo. |

---

## 3. Máquinas de estado

### 3.1 `expense_request`

**Estados**

| Estado | Descripción |
|--------|-------------|
| `submitted` | Creada; aún no genera pasos de aprobación (transitorio si se crea y en el mismo acto pasa a `approval_in_progress`). |
| `approval_in_progress` | Pendiente de completar todas las aprobaciones requeridas por política. |
| `rejected` | Rechazada en cadena de aprobaciones; fin de flujo para esta solicitud. |
| `cancelled` | Cancelada con nota; fin de flujo. |
| `approved` | Todas las aprobaciones cumplidas; lista para bandeja de pago. |
| `pending_payment` | En contabilidad para ejecución de pago (puede coincidir semánticamente con `approved`; si se unifican en implementación, mantener una sola fuente de verdad — aquí se listan separados para reflejar bandeja operativa). **Decisión de modelo:** usar **`approved`** como estado formal y “pendiente de pago” como derivado de ausencia de `payment` completado, **o** estado explícito `pending_payment`. **Especificación:** se usa **`pending_payment`** tras `approved` cuando contabilidad debe actuar. |
| `paid` | Pago registrado con evidencia. |
| `awaiting_expense_report` | Pagada; usuario debe presentar comprobación. |
| `expense_report_in_review` | Comprobación enviada; contabilidad revisa (`expense_report` en `accounting_review`). |
| `expense_report_rejected` | Contabilidad rechazó la comprobación; usuario debe corregir y reenviar. |
| `expense_report_approved` | Comprobación aprobada; procede balance. |
| `settlement_pending` | Existe `settlement` abierto (usuario debe devolver o empresa debe pagar diferencia). |
| `closed` | Ciclo terminado (settlement cerrado o diferencia cero sin pendientes — ver transiciones). |

**Transiciones (origen → destino)**

| Desde | Hacia | Actor | Validaciones / notas |
|-------|--------|-------|----------------------|
| — | `submitted` | Solicitante | Datos mínimos válidos; genera folio; **no** descuenta presupuesto. |
| `submitted` | `approval_in_progress` | Sistema | Tras persistir, motor de políticas crea aprobaciones pendientes. |
| `approval_in_progress` | `approved` | Sistema | Cuando todas las aprobaciones requeridas OK. |
| `approval_in_progress` | `rejected` | Aprobador con permiso | Nota obligatoria. |
| `approval_in_progress` | `cancelled` | Solicitante o aprobador según **TBD** | Nota obligatoria. |
| `approved` | `pending_payment` | Sistema | Opcional automático al aprobar último paso. |
| `pending_payment` | `paid` | Contabilidad | Método, fecha, datos de transferencia si aplica; **evidencia de pago** adjunta. |
| `pending_payment` | `cancelled` | Contabilidad o **TBD** | Nota obligatoria; reglas de reverso presupuesto en implementación. |
| `paid` | `awaiting_expense_report` | Sistema | Tras marcar pago. |
| `awaiting_expense_report` | `expense_report_in_review` | Solicitante | Debe existir `expense_report` con archivos según política. |
| `expense_report_rejected` | `expense_report_in_review` | Solicitante | Reenvío tras corrección (misma entidad `expense_report` con nueva versión lógica — ver §3.2). |
| `expense_report_in_review` | `expense_report_approved` | Contabilidad | Nota opcional de revisión. Dispara cálculo de `settlement`. |
| `expense_report_in_review` | `expense_report_rejected` | Contabilidad | Nota obligatoria. |
| `expense_report_approved` | `settlement_pending` | Sistema | Si `settlement` requiere acción (diferencia ≠ 0 o política exige cierre formal). |
| `expense_report_approved` | `closed` | Sistema | Si diferencia = 0 y no hay pasos de liquidación. |
| `settlement_pending` | `closed` | Contabilidad | Tras `settlement` → `closed` y cierre contable del ciclo. |
| `rejected` / `cancelled` | — | — | Estados terminales. |

**Sincronía con entidades hijas:** el estado formal de revisión de comprobación vive en `expense_report`; el de liquidación en `settlement`. Los estados `expense_report_*` y `settlement_pending` en `expense_request` son **proyección** para bandejas y deben mantenerse consistentes por reglas de aplicación (eventos de dominio).

**Recibos / notificaciones:** ver §5 (matriz).

---

### 3.2 `expense_report`

**Estados**

| Estado | Descripción |
|--------|-------------|
| `draft` | Usuario prepara comprobación (opcional si el flujo solo permite envío directo). |
| `submitted` | Enviada a revisión (pendiente de que contabilidad la tome). |
| `accounting_review` | En cola o en revisión por contabilidad. |
| `rejected` | Rechazada con nota; el solicitante debe corregir. |
| `approved` | Aprobada; dispara settlement. |
| `cancelled` | Anulada con nota (**TBD** si aplica solo antes de `approved`). |

**Versionado / corrección tras rechazo**

- **Modelo recomendado:** un solo registro `expense_report` por `expense_request` (1:1); ante rechazo, transición `rejected` → usuario edita archivos/monto → `submitted` → `accounting_review`.  
- **Auditoría:** cada reenvío genera evento de dominio y recibo interno “comprobación reenviada”; historial detallado de archivos puede implementarse con versiones de media o tabla de `expense_report_versions` en etapa 2 (**TBD**).

**Transiciones**

| Desde | Hacia | Actor | Validaciones |
|-------|--------|-------|--------------|
| — | `draft` | Solicitante | Opcional. |
| `draft` | `submitted` | Solicitante | PDF obligatorio; XML según política; monto ≥ 0; fecha. |
| `submitted` | `accounting_review` | Sistema | Al entrar a cola de contabilidad. |
| `accounting_review` | `approved` | Contabilidad | |
| `accounting_review` | `rejected` | Contabilidad | Nota obligatoria. |
| `rejected` | `draft` o `submitted` | Solicitante | Tras edición; política de archivos nueva versión. |
| `draft` / `submitted` | `cancelled` | Solicitante o **TBD** | Nota obligatoria. |

**Alineación con `expense_request`:** al pasar a `accounting_review`, la solicitud padre pasa a `expense_report_in_review`; al `rejected`, padre a `expense_report_rejected`; al `approved`, padre a `expense_report_approved`.

---

### 3.3 `settlement`

**Estados**

| Estado | Descripción |
|--------|-------------|
| `calculated` | Diferencia calculada; pendiente de definir dirección de flujo de dinero. |
| `pending_user_return` | El usuario debe devolver a la empresa. |
| `pending_company_payment` | La empresa debe pagar al usuario. |
| `settled` | Movimiento de dinero registrado con evidencia. |
| `closed` | Contabilidad cerró el caso. |

**Cálculo:** `difference = requested_amount - reported_amount` (usar montos acordados en implementación: aprobado vs comprobado — **TBD** si la base es “pagado” vs “aprobado”; por defecto **comparar contra monto pagado** para cuadre de caja).

| Condición | Dirección | Estado inicial tras cálculo |
|-----------|-----------|-----------------------------|
| `difference > 0` | Usuario → empresa | `pending_user_return` |
| `difference < 0` | Empresa → usuario | `pending_company_payment` |
| `difference == 0` | Ninguna | Transición directa a `closed` (sin `settled`) o `calculated` → `closed` |

**Transiciones**

| Desde | Hacia | Actor | Validaciones |
|-------|--------|-------|--------------|
| — | `calculated` | Sistema | Tras aprobar `expense_report`. |
| `calculated` | `pending_user_return` | Sistema | Si diff > 0. |
| `calculated` | `pending_company_payment` | Sistema | Si diff < 0. |
| `calculated` | `closed` | Sistema | Si diff = 0. |
| `pending_user_return` | `settled` | Contabilidad | Evidencia de cobro/devolución. |
| `pending_company_payment` | `settled` | Contabilidad | Evidencia de pago al usuario. |
| `settled` | `closed` | Contabilidad | Cierre final auditado. |

**Recordatorios:** mientras estado sea `pending_user_return` o `pending_company_payment`, el **scheduler** dispara recordatorio diario (email + notificación DB). No son transiciones de estado; son **eventos recurrentes** sobre el mismo estado.

---

### 3.4 `vacation_request`

**Estados**

| Estado | Descripción |
|--------|-------------|
| `draft` | Opcional: borrador antes de enviar. |
| `submitted` | Enviada; pendiente de motor de aprobaciones. |
| `approval_in_progress` | Pasos de aprobación pendientes. |
| `rejected` | Rechazo en cadena; nota obligatoria. |
| `cancelled` | Cancelación con nota. |
| `approved` | Todas las aprobaciones cumplidas. |
| `completed` | Periodo de vacaciones concluido (**TBD** si se usa o se cierra en `approved`). |

**Transiciones**

| Desde | Hacia | Actor | Validaciones |
|-------|--------|-------|--------------|
| — | `draft` | Solicitante | |
| `draft` | `submitted` | Solicitante | Rango de fechas; días calculados; **reglas** `vacation_rules` y tabla de antigüedad. |
| `submitted` | `approval_in_progress` | Sistema | Genera aprobaciones desde política `document_type = vacation_request`. |
| `approval_in_progress` | `approved` | Sistema | Todos los pasos OK. |
| `approval_in_progress` | `rejected` | Aprobador | Nota obligatoria. |
| `approval_in_progress` | `cancelled` | Solicitante / **TBD** | Nota obligatoria. |
| `approved` | `completed` | Sistema o job | **TBD** según si se trackea ejecución. |

**Validaciones previas a `submitted`:** días disponibles según `vacation_entitlements`; máximos por solicitud y por periodo (mes/trimestre/año); blackout opcional.

---

## 4. Matriz de eventos del sistema

Leyenda: **RI** = recibo interno PDF; **Email**; **DB** = notificación persistida en base de datos.

### 4.1 Gastos (`expense_request` y relacionados)

| # | Evento | RI | Email | DB |
|---|--------|----|-------|-----|
| E1 | Solicitud creada / enviada a aprobación | Sí | Sí (aprobadores) | Sí |
| E2 | Aprobación registrada (cada paso) | Sí | Sí (solicitante) | Sí |
| E3 | Solicitud totalmente aprobada | Sí | Sí (contabilidad, solicitante) | Sí |
| E4 | Solicitud rechazada | Sí | Sí (solicitante, contabilidad si aplica) | Sí |
| E5 | Solicitud cancelada | Sí | Sí (partes afectadas) | Sí |
| E6 | Pago ejecutado por contabilidad | Sí | Sí (solicitante) | Sí |
| E7 | Comprobación enviada (expense_report) | Sí | Sí (contabilidad) | Sí |
| E8 | Comprobación reenviada tras rechazo | Sí | Sí (contabilidad) | Sí |
| E9 | Comprobación aprobada | Sí | Sí (solicitante) | Sí |
| E10 | Comprobación rechazada | Sí | Sí (solicitante) | Sí |
| E11 | Balance generado (settlement) | Sí | Sí (solicitante) | Sí |
| E12 | Recordatorio balance pendiente (diario) | No | Sí | Sí |
| E13 | Liquidación registrada (settlement settled) | Sí | Sí (solicitante) | Sí |
| E14 | Cierre final del ciclo (closed) | Sí | Sí (solicitante, contabilidad) | Sí |

### 4.2 Presupuesto (`budget` / ledger)

| # | Evento | RI | Email | DB |
|---|--------|----|-------|-----|
| B1 | Compromiso de presupuesto (commit al aprobar solicitud) | Opcional **TBD** | No * | Sí (entrada ledger + activity) |
| B2 | Consumo real (spend al pagar) | Opcional **TBD** | No * | Sí |
| B3 | Reverso / ajuste manual de presupuesto | Sí | Sí (contabilidad / admin) | Sí |

\* *Notificación de producto opcional si se desea alerta de umbral; no requerida en etapa 1.*

### 4.3 Vacaciones (`vacation_request`)

| # | Evento | RI | Email | DB |
|---|--------|----|-------|-----|
| V1 | Solicitud creada / en aprobación | Sí | Sí (aprobadores) | Sí |
| V2 | Aprobación de paso | Sí | Sí (solicitante) | Sí |
| V3 | Solicitud aprobada | Sí | Sí (solicitante, RRHH **TBD**) | Sí |
| V4 | Rechazo | Sí | Sí (solicitante) | Sí |
| V5 | Cancelación | Sí | Sí (partes afectadas) | Sí |

---

## 5. Revisión cruzada y TBD

### 5.1 Cobertura transición → evento

- Cada transición que cambia estado en §3 dispara al menos un evento en §4 o un par (E + B) para presupuesto:  
  - Aprobación completa → E3 + B1.  
  - Pago → E6 + B2.  
  - Rechazo/cancelación → E4/E5 (+ reverso B3 **TBD** si ya había commit).  
  - Comprobación y settlement → E7–E14.

### 5.2 Lista TBD explícita (antes de etapa 2)

| ID | Tema |
|----|------|
| TBD-1 | ¿`submitted` vs creación directa en `approval_in_progress`? Unificar en una sola transición visible al usuario. |
| TBD-2 | ¿`approved` y `pending_payment` se fusionan? Si sí, eliminar duplicidad en modelo de datos. |
| TBD-3 | Plazos máximos para comprobar después de `paid` (SLA y escalamiento). |
| TBD-4 | Quién puede **cancelar** en cada estado (solo solicitante hasta X, contabilidad en `pending_payment`, etc.). |
| TBD-5 | Formato de **folio** legible y secuencia por región/año. |
| TBD-6 | XML de comprobación: ¿siempre obligatorio o configurable por política? |
| TBD-7 | Versionado físico de archivos de `expense_report` (tabla de versiones vs solo activity log). |
| TBD-8 | Base numérica exacta del settlement: ¿monto aprobado, pagado, o comprobado solo en numerador? |
| TBD-9 | ¿Recibo interno para cada **commit/spend** de ledger o solo para ajustes manuales (B3)? |
| TBD-10 | Rol **RRHH** para notificaciones de vacaciones o usa `secretario_general`. |
| TBD-11 | Estado `completed` en vacaciones: ¿necesario o basta con `approved`? |

### 5.3 Criterio de salida de Etapa 1

- Glosario y cuatro máquinas de estado aprobadas por negocio.  
- Matriz de eventos sin huecos críticos para E1–E14, B1–B3, V1–V5.  
- TBD list priorizado para etapa 2 (modelo de datos).

---

*Documento generado como entregable de Etapa 1. Siguiente etapa: diccionario de tablas y migraciones (sin ejecutar en este documento).*
