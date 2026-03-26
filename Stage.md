# Etapas del plan — estado de avance

Este documento contrasta las **etapas de implementación** descritas en [PLAN.md](PLAN.md) (§ “Etapas de implementación con prompts…”) con lo que existe hoy en el repositorio. La fecha de referencia del análisis es coherente con las migraciones y el código presentes en el proyecto.

**Cola ordenada para Plan mode:** ver [Stage.v2.md](Stage.v2.md) (bloques **Q1–Qn** secuenciales).

**Leyenda:** ✅ hecho · 🟡 parcial · ⏳ pendiente (casi sin trabajo) · — no aplica o diferencia deliberada documentada en otro artefacto.

**Última actualización de este inventario:** 2026-03-23 (auditoría exhaustiva: eliminados `expense_starts_on`/`expense_ends_on`; todos los bloques Q0–Q12 cerrados; 229 tests pasan; ver [Stage.v2.md](Stage.v2.md)).

---

## Resumen ejecutivo

| Etapa (PLAN) | Estado |
|--------------|--------|
| 1. Alineación de alcance y estados | ✅ |
| 2. Modelo de datos y migraciones | ✅ |
| 3. Backend base (Laravel + auth + roles + políticas) | ✅ |
| 4. Motor de aprobaciones por rol | ✅ |
| 5. Solicitudes de gasto (CRUD + flujo + UI) | ✅ |
| 6. Notificaciones y bitácora (email + DB + colas + activity log) | ✅ |
| 7. Pagos con evidencia y recibos internos PDF | ✅ |
| 8. Comprobaciones PDF/XML y revisión contable | ✅ |
| 9. Balances, liquidación y recordatorios (scheduler) | ✅ |
| 10. Presupuestos morfables y ledger operativo | ✅ |
| 11. Vacaciones (reglas + flujo + UI + recibos/notifs) | ✅ |

**Hilo conductor:** ya hay **dominio persistido**, **HTTP + Inertia** para solicitudes de gasto (MVP), **registro de pago por contabilidad** con evidencia (`attachments`), **recibo PDF de pago ejecutado**, transición **`pending_payment` → `paid` → `awaiting_expense_report`** (persistido el estado final) y **notificación al solicitante** tras pago; **acuse PDF de solicitud**; notificaciones encoladas (mail + BD) con **Redis + Laravel Horizon**. **Comprobación:** borrador y envío con **PDF + XML** (`attachments` en `ExpenseReport`), bandeja **comprobaciones por revisar** (contabilidad), **aprobar/rechazar**. Al aprobar: **`Settlement`** según §3.3 — solicitud **`closed`** y balance **`closed`** si diferencia 0; si no, **`settlement_pending`** con **`pending_user_return`** o **`pending_company_payment`**; contabilidad registra **liquidación con evidencia** → **`settled`** → **`closed`** y cierre de solicitud; **`document_events`** (`settlement_liquidation_recorded`, `settlement_closed`); **recibo PDF de liquidación** y **descarga de evidencia** del settlement; notificaciones de liquidación/cierre y **comando diario** `settlements:send-pending-reminders` en [bootstrap/app.php](bootstrap/app.php). Siguen pendientes **recibos PDF** opcionales (p. ej. un PDF por cada paso de aprobación intermedio — E2). **Presupuesto (etapa 10):** ledger **commit/spend** enganchado a aprobación final y pago (**Q11**); falta CRUD de cupos en UI y políticas de bloqueo por cupo si negocio las define. La **bitácora** de gastos quedó en **Q8** ([docs/audit-log-decision.md](docs/audit-log-decision.md): ampliar `document_events`, evento `expense_request_chain_approved`, línea de tiempo en detalle). Hay **recibo PDF de aprobación final** (**Q6**) y **adjuntos opcionales pre-pago** (**Q7**) en Stage.v2. La **bandeja in-app** de notificaciones `database` (**Q2**) y la **bandeja balances pendientes** (**Q1**) están en Stage.v2. Orden sugerido de cierre: [Stage.v2.md](Stage.v2.md).

---

## 1. Alineación de alcance y estados

**Objetivo (PLAN):** vocabulario, máquinas de estado, matriz de eventos (sin código).

**Estado: ✅**

- Entregable principal: [docs/functional-spec-stage1.md](docs/functional-spec-stage1.md) (glosario, estados, eventos).

---

## 2. Modelo de datos y migraciones

**Objetivo (PLAN):** diseño de tablas para usuarios/territorio, solicitudes, aprobaciones, pagos, comprobaciones, settlements, políticas, budgets, recibos, cancelaciones.

**Estado: ✅** (con matices alineados al diccionario de etapa 2)

- Migraciones en `database/migrations/` cubren regiones, estados, roles, usuarios ampliados, políticas y pasos, `expense_requests`, `expense_request_approvals`, `payments`, `expense_reports`, `settlements`, `budgets`, `budget_ledger_entries`, vacaciones (reglas, entitlements, solicitudes, aprobaciones), `attachments`, `document_events`.
- Tabla **`notifications`** (canal `database` de Laravel) para alertas persistidas.
- [docs/data-dictionary-stage2.md](docs/data-dictionary-stage2.md) documenta que **no** hay tabla `system_receipts` en esa etapa; la traza va hacia `document_events` y evidencias vía `attachments` (equivalente funcional distinto al nombre del PLAN).

---

## 3. Backend base en Laravel (starter + auth + roles + autorización)

**Objetivo (PLAN):** starter Inertia, modelos y relaciones, Spatie Permission con roles fijos, gates/policies, tests mínimos.

**Estado: ✅**

**Hecho**

- Laravel + Fortify + Inertia + React (según `composer.json` / estructura del kit).
- Catálogo `roles` y `users.role_id` (y seeders como `RoleSeeder`), middleware `EnsureUserHasRole`, alias `role` en [bootstrap/app.php](bootstrap/app.php).
- Modelos Eloquent de dominio y relaciones.
- Policies de dominio registradas en [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) (gastos, pagos, comprobaciones, settlements, budgets, vacaciones, políticas).
- Tests: autenticación, dashboard, settings, `DomainSchemaMigrationTest`, `EnsureUserHasRoleMiddlewareTest`, autorización de `ExpenseRequest`, etc.

**Pendiente / diferencia respecto al PLAN**

- **No** está `spatie/laravel-permission` en `composer.json`; el diseño vigente es **roles propios** + policies, como indica el diccionario de datos etapa 2. La desviación frente al PLAN queda registrada como decisión arquitectónica en [docs/roles-architecture-decision.md](docs/roles-architecture-decision.md) (**Q0** en [Stage.v2.md](Stage.v2.md)). Una alineación literal con Spatie sería un cambio de producto explícito, no un requisito pendiente de documentación.

---

## 4. Motor de aprobaciones por rol

**Objetivo (PLAN):** al crear documento, generar aprobaciones pendientes; AND; rechazo/cancelación con nota; “faltan N”; servicios, eventos de dominio, tests; sin UI.

**Estado: ✅**

**Hecho**

- [app/Services/Approvals/](app/Services/Approvals/): resolución de política, validación de cadena, agrupación de pasos (AND/OR por `combine_with_next`), flujos para `ExpenseRequest` y `VacationRequest`.
- Tests: `ExpenseRequestApprovalWorkflowTest`, `VacationRequestApprovalWorkflowTest`, `ApprovalPolicyResolverTest`, `ApprovalStepGrouperTest`.
- En rechazos de gasto se registran `DocumentEvent` (p. ej. tipo rechazo).
- `ExpenseRequestApprovalService::isPendingStepActive()` alinea bandeja HTTP y acciones en detalle con el grupo de pasos activo (AND/OR).

**Pendiente respecto al PLAN**

- No hay **eventos de dominio Laravel** con nombres tipo `ApprovalRecorded` / `DocumentApproved` como capa explícita; la lógica está concentrada en servicios.
- **Cancelación** con nota obligatoria: implementada vía [CancelExpenseRequest](app/Services/ExpenseRequests/CancelExpenseRequest.php), ruta `expense-requests.cancel`, `DocumentEventType::Cancellation`, policy `cancel` (solicitante, estados `submitted` / `approval_in_progress`); ver **Q4** en [Stage.v2.md](Stage.v2.md).
- En UI de detalle de solicitud se expone **progreso por grupos** (equivalente operativo a “faltan N” pasos AND/OR) vía `approval_progress`; no hay eventos Laravel nombrados aún.

---

## 5. Solicitudes de gasto

**Objetivo (PLAN):** formulario, folio, `approval_in_progress`, bandejas solicitante/aprobadores, recibos internos descargables, rutas + Inertia; **sin pagos aún**.

**Estado: ✅**

**Hecho (backend / dominio + HTTP MVP)**

- Modelo `ExpenseRequest`, enums de estado, factory, políticas (actualización del solicitante solo en `submitted`).
- `ExpenseRequestApprovalService::startWorkflow` pasa de `submitted` a `approval_in_progress` y crea instancias de aprobación; al completar la cadena actualiza a `pending_payment` (el PLAN menciona también `approved`; el enum incluye ambos — conviene unificar criterio en documentación).
- Rutas web en [routes/expense-requests.php](routes/expense-requests.php) (cargadas desde [routes/web.php](routes/web.php)): CRUD solicitante (sin destroy), bandeja de pendientes para aprobador, POST aprobar/rechazar; Form Requests; folio `EXP-{año}-{id}` al crear; alta en transacción + arranque del workflow.
- Páginas Inertia: listado del solicitante (con **paginación** cuando aplica), crear, detalle (con acciones de aprobación cuando el paso está activo, **progreso de grupos** y enlace de descarga), editar en `submitted`, bandeja “Pendientes de aprobar”; enlaces en sidebar.
- Servicio `isPendingStepActive()` para alinear bandeja y UI con el grupo de pasos activo; [ExpenseRequestApprovalProgressResolver](app/Services/ExpenseRequests/ExpenseRequestApprovalProgressResolver.php) para “faltan N” **grupos** en pantalla.
- **Recibo PDF “acuse de solicitud”** (dompdf): ruta nombrada `expense-requests.receipts.submission`, vista [resources/views/pdf/expense-request-submission.blade.php](resources/views/pdf/expense-request-submission.blade.php); dependencia `barryvdh/laravel-dompdf`.
- `DocumentEvent` con tipo `expense_request_submitted` al crear y enviar al flujo (auditoría).
- Tras crear solicitud: **notificación** a usuarios del **primer grupo** de aprobación (mail + BD, encolada); tras aprobar/rechazar: notificación al solicitante (progreso, aprobación total o rechazo). Ver [ExpenseRequestNotificationDispatcher](app/Services/ExpenseRequests/ExpenseRequestNotificationDispatcher.php) y `app/Notifications/ExpenseRequests/`.
- Tests HTTP: [tests/Feature/ExpenseRequestHttpTest.php](tests/Feature/ExpenseRequestHttpTest.php).

**Pendiente**

- Otros **recibos PDF** del PLAN (por cada aprobación, comprobación, cierre, etc.): el **recibo de pago ejecutado** quedó en etapa 7; siguen etapas 8+.
- **Adjuntos** en la solicitud (evidencias previas al pago) y demás refinamiento UX del PLAN si aplica a esta etapa.
- **Bandeja Inertia** de notificaciones `database`: ruta `notifications.index`, [NotificationInboxController](app/Http/Controllers/NotificationInboxController.php), UI [notifications/index.tsx](resources/js/pages/notifications/index.tsx), contador sin leer en sidebar vía [HandleInertiaRequests](app/Http/Middleware/HandleInertiaRequests.php); **Q2** en [Stage.v2.md](Stage.v2.md).

---

## 6. Notificaciones y bitácora

**Objetivo (PLAN):** emails a aprobadores/solicitante, notificación en BD, colas, `spatie/laravel-activitylog` para eventos listados.

**Estado: ✅**

**Hecho (MVP gastos + infraestructura)**

- Clases en [app/Notifications/ExpenseRequests/](app/Notifications/ExpenseRequests/): envío **mail + `database`**, `ShouldQueue`, disparadas desde [ExpenseRequestController::store](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php), [ExpenseRequestApprovalService](app/Services/Approvals/ExpenseRequestApprovalService.php) y [RecordExpenseRequestPayment](app/Services/Payments/RecordExpenseRequestPayment.php) vía [ExpenseRequestNotificationDispatcher](app/Services/ExpenseRequests/ExpenseRequestNotificationDispatcher.php) (incl. `DB::afterCommit` en aprobación/rechazo y tras registrar pago).
- Colas: conexión por defecto **Redis** en [config/queue.php](config/queue.php); `QUEUE_CONNECTION=redis` documentado en [.env.example](.env.example). Tests usan `QUEUE_CONNECTION=sync` en [phpunit.xml](phpunit.xml).
- **Laravel Horizon** (`laravel/horizon`): [config/horizon.php](config/horizon.php), [HorizonServiceProvider](app/Providers/HorizonServiceProvider.php); dashboard `/horizon` con middleware `web` + `auth`; gate `viewHorizon`: entorno **local** abierto, fuera de local solo **SuperAdmin**. Script `composer run dev` levanta `php artisan horizon` en lugar de `queue:listen`.
- Migración `notifications`.
- **UI in-app (gastos):** listado paginado, marcar leída / marcar todas, enlace al detalle de solicitud cuando el payload incluye `expense_request_id` ([InAppNotificationPresenter](app/Services/Notifications/InAppNotificationPresenter.php)); rutas en [routes/notifications.php](routes/notifications.php); tests [NotificationInboxHttpTest.php](tests/Feature/NotificationInboxHttpTest.php).
- **Vacaciones (Q9):** notificaciones `ShouldQueue` mail + `database` desde [VacationRequestApprovalService](app/Services/Approvals/VacationRequestApprovalService.php) vía [VacationRequestNotificationDispatcher](app/Services/VacationRequests/VacationRequestNotificationDispatcher.php); presentación in-app de tipos `vacation_request.*` en [InAppNotificationPresenter](app/Services/Notifications/InAppNotificationPresenter.php) (payload incluye `vacation_request_id`; enlace **Ver vacaciones** en [notifications/index.tsx](resources/js/pages/notifications/index.tsx) desde **Q10**).

**Pendiente respecto al PLAN**

- **Bitácora (Q8):** decisión documentada en [docs/audit-log-decision.md](docs/audit-log-decision.md): **no** Spatie por ahora; se amplía `document_events` (incl. `expense_request_chain_approved` al completar la cadena) y se muestra **línea de tiempo** en [show.tsx](resources/js/pages/expense-requests/show.tsx) para quien vea el detalle del gasto.
- Sin `horizon:snapshot` u otras tareas programadas de métricas en scheduler (opcional según despliegue); el **scheduler de negocio** ya incluye recordatorios diarios de balances pendientes (etapa 9).

---

## 7. Pagos por contabilidad (evidencia + recibo PDF)

**Objetivo (PLAN):** bandeja pagos pendientes, marcar pagado con datos y evidencia, Media Library o equivalente, PDF “Pago ejecutado”, notificar solicitante.

**Estado: ✅** (MVP alineado a [docs/functional-spec-stage1.md](docs/functional-spec-stage1.md) §3.1 y diccionario etapa 2)

**Hecho**

- **No** hay `spatie/laravel-medialibrary`; evidencias en morph **`Attachment`** sobre `Payment` (disco `local` / `storage/app/private`).
- Servicio [RecordExpenseRequestPayment](app/Services/Payments/RecordExpenseRequestPayment.php): valida `pending_payment`, monto = `approved_amount_cents`, un solo pago, referencia si transferencia; transacción con `Payment` + archivo + `DocumentEvent` `expense_request_paid`; estados **`paid`** y seguidamente **`awaiting_expense_report`** en la misma transacción (valor persistido final: `awaiting_expense_report`).
- Rutas en [routes/expense-requests.php](routes/expense-requests.php): bandeja `expense-requests.payments.pending`, alta `expense-requests.payments.store`, recibo `expense-requests.receipts.payment`.
- HTTP: [ExpenseRequestPaymentController](app/Http/Controllers/ExpenseRequests/ExpenseRequestPaymentController.php), [StoreExpenseRequestPaymentRequest](app/Http/Requests/ExpenseRequests/StoreExpenseRequestPaymentRequest.php); políticas `recordPayment` y `downloadPaymentReceipt` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php).
- **PDF** “pago ejecutado”: [ExpenseRequestPaymentReceiptPdf](app/Services/ExpenseRequests/ExpenseRequestPaymentReceiptPdf.php) + [resources/views/pdf/expense-request-payment.blade.php](resources/views/pdf/expense-request-payment.blade.php).
- **Notificación** al solicitante: [ExpenseRequestPaidNotification](app/Notifications/ExpenseRequests/ExpenseRequestPaidNotification.php) (mail + BD, cola).
- **UI Inertia:** [resources/js/pages/expense-requests/payments/pending.tsx](resources/js/pages/expense-requests/payments/pending.tsx), formulario y resumen en detalle [show.tsx](resources/js/pages/expense-requests/show.tsx); enlace sidebar “Pagos pendientes” solo rol **contabilidad** ([app-sidebar.tsx](resources/js/components/app-sidebar.tsx)).
- Tests: [tests/Feature/ExpenseRequestPaymentHttpTest.php](tests/Feature/ExpenseRequestPaymentHttpTest.php); factory [PaymentFactory](database/factories/PaymentFactory.php); políticas en [tests/Feature/ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php).
- Descarga **autorizada** del archivo de evidencia de pago: ruta `expense-requests.payments.payment-evidence`, política `downloadPaymentEvidence` (mismo criterio que `view` del gasto, adjunto morph `Payment` ligado a la solicitud); enlace en detalle [show.tsx](resources/js/pages/expense-requests/show.tsx); tests [tests/Feature/PaymentEvidenceHttpTest.php](tests/Feature/PaymentEvidenceHttpTest.php).

---

## 8. Comprobaciones (expense_reports) con PDF/XML

**Objetivo (PLAN):** flujo usuario → contabilidad, archivos, estados, settlement al aprobar.

**Estado: ✅**

**Hecho (MVP)**

- Servicios [SaveExpenseReportDraft](app/Services/ExpenseReports/SaveExpenseReportDraft.php), [SubmitExpenseReportForReview](app/Services/ExpenseReports/SubmitExpenseReportForReview.php), [ApproveExpenseReport](app/Services/ExpenseReports/ApproveExpenseReport.php), [RejectExpenseReport](app/Services/ExpenseReports/RejectExpenseReport.php); adjuntos por tipo PDF/XML vía [ExpenseReportAttachmentWriter](app/Services/ExpenseReports/ExpenseReportAttachmentWriter.php).
- Rutas: borrador `expense-requests.expense-report.draft`, envío `expense-requests.expense-report.submit`, aprobar/rechazar `expense-requests.expense-report.approve|reject`, bandeja `expense-requests.expense-reports.pending-review` ([ExpenseReportController](app/Http/Controllers/ExpenseRequests/ExpenseReportController.php)).
- Políticas: `saveExpenseReportDraft`, `submitExpenseReport`, `reviewExpenseReport` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php); `ExpenseReportPolicy::viewAny` restringido a **contabilidad** (bandeja).
- `DocumentEventType`: `expense_report_submitted`, `expense_report_approved`, `expense_report_rejected`.
- Notificaciones encoladas: contabilidad al enviar comprobación; solicitante al aprobar/rechazar (`ExpenseReportSubmittedForReviewNotification`, `ExpenseReportApprovedNotification`, `ExpenseReportRejectedNotification`).
- UI Inertia: detalle [show.tsx](resources/js/pages/expense-requests/show.tsx) (borrador, envío, revisión); bandeja [pending-review.tsx](resources/js/pages/expense-requests/expense-reports/pending-review.tsx); enlace sidebar contabilidad.
- Tests: [tests/Feature/ExpenseReportHttpTest.php](tests/Feature/ExpenseReportHttpTest.php).
- Descarga **autorizada** de PDF/XML de comprobación: ruta `expense-requests.expense-reports.verification-attachment`, política `downloadExpenseReportVerificationAttachment` (adjunto morph `ExpenseReport` de la solicitud, solo archivos PDF/XML; en **borrador** solo el solicitante; en revisión/aprobada/rechazada quien tenga `view`). Acuse PDF **enviada o aprobada**: `expense-requests.receipts.expense-report-verification`, `downloadExpenseReportVerificationReceipt`, [ExpenseRequestExpenseReportVerificationReceiptPdf](app/Services/ExpenseRequests/ExpenseRequestExpenseReportVerificationReceiptPdf.php); [ExpenseRequestController](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php); UI en [show.tsx](resources/js/pages/expense-requests/show.tsx); tests [tests/Feature/ExpenseReportVerificationHttpTest.php](tests/Feature/ExpenseReportVerificationHttpTest.php) y política en [tests/Feature/ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php).
- Validación semántica CFDI/XML (**Q12**): [CfdiComprobanteValidator](app/Services/ExpenseReports/CfdiComprobanteValidator.php) en borrador y envío; `config/expense_reports.php` (`EXPENSE_REPORT_CFDI_*`); tests [CfdiComprobanteValidatorTest.php](tests/Unit/CfdiComprobanteValidatorTest.php) y casos en [ExpenseReportHttpTest.php](tests/Feature/ExpenseReportHttpTest.php).

**Pendiente / refinamiento**

- Política “XML opcional” (TBD funcional).
- Recibo PDF de comprobación en estado **rechazada** (si el PLAN lo exige).

---

## 9. Balances (settlements), liquidación y scheduler

**Objetivo (PLAN):** cálculo de diferencia, notificaciones, recordatorios diarios, evidencia de liquidación, recibos, tarea programada.

**Estado: ✅** (MVP operativo + PDF liquidación y descarga de evidencia + bandeja balances pendientes)

**Hecho**

- Modelo `Settlement` y enum `SettlementStatus` presentes; evidencias morph **`Attachment`** sobre `Settlement`.
- **Cálculo y estado inicial** al aprobar comprobación ([ApproveExpenseReport](app/Services/ExpenseReports/ApproveExpenseReport.php)): `difference_cents` = base pagada − comprobado; solicitud **`closed`** y settlement **`closed`** si diferencia 0; si no, **`settlement_pending`** con settlement **`pending_user_return`** (diff &gt; 0) o **`pending_company_payment`** (diff &lt; 0).
- **Liquidación y cierre:** [RecordSettlementLiquidation](app/Services/Settlements/RecordSettlementLiquidation.php) (`pending_*` → **`settled`** + evidencia), [CloseSettlement](app/Services/Settlements/CloseSettlement.php) (`settled` → **`closed`** + solicitud **`closed`**).
- Rutas POST `expense-requests.settlement.liquidation.store` y `expense-requests.settlement.close` en [routes/expense-requests.php](routes/expense-requests.php); [ExpenseRequestSettlementController](app/Http/Controllers/ExpenseRequests/ExpenseRequestSettlementController.php); políticas `recordSettlementLiquidation` y `closeSettlement` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php).
- `DocumentEventType`: `settlement_liquidation_recorded`, `settlement_closed`.
- Notificaciones encoladas al solicitante: liquidación registrada, cierre; recordatorio para balances **`pending_*`** (solicitante + contabilidad) vía [SendSettlementPendingRemindersCommand](app/Console/Commands/SendSettlementPendingRemindersCommand.php) (`settlements:send-pending-reminders`, creado ≥24 h).
- **Scheduler:** tarea diaria registrada en [bootstrap/app.php](bootstrap/app.php) (`withSchedule`). [routes/console.php](routes/console.php) sigue sin definir ahí el calendario (solo comandos tipo `inspire` u otros).
- UI en detalle [show.tsx](resources/js/pages/expense-requests/show.tsx) (tarjeta balance, formularios contabilidad).
- **Recibo PDF** de liquidación: ruta `expense-requests.receipts.settlement-liquidation`, servicio [ExpenseRequestSettlementLiquidationReceiptPdf](app/Services/ExpenseRequests/ExpenseRequestSettlementLiquidationReceiptPdf.php), vista [resources/views/pdf/expense-request-settlement-liquidation.blade.php](resources/views/pdf/expense-request-settlement-liquidation.blade.php); política `downloadSettlementLiquidationReceipt`.
- **Descarga autorizada** de evidencia de liquidación: ruta `expense-requests.settlements.liquidation-evidence`, política `downloadSettlementLiquidationEvidence`; UI en detalle [show.tsx](resources/js/pages/expense-requests/show.tsx).
- Tests: [tests/Feature/SettlementLiquidationHttpTest.php](tests/Feature/SettlementLiquidationHttpTest.php); ajustes en [tests/Feature/ExpenseReportHttpTest.php](tests/Feature/ExpenseReportHttpTest.php) para estados iniciales del settlement.
- **Bandeja Inertia** balances pendientes (`pending_user_return` / `pending_company_payment`): ruta `expense-requests.settlements.pending-balances`, `ExpenseRequestSettlementController::pendingBalances`, política `SettlementPolicy::viewPendingBalances` (oversight de gastos); UI [pending-balances.tsx](resources/js/pages/expense-requests/settlements/pending-balances.tsx) y sidebar; tests [SettlementPendingBalancesHttpTest.php](tests/Feature/SettlementPendingBalancesHttpTest.php). **Q1** en [Stage.v2.md](Stage.v2.md) cerrado.

---

## 10. Presupuestos morfables e impacto por movimientos

**Objetivo (PLAN):** morph a Region/State/User/Role, ledger con tipos commit/spend/reverse/adjust, reglas al aprobar/pagar, UI contabilidad, tests.

**Estado: ✅**

- `Budget`, `BudgetLedgerEntry`, enums de tipo de movimiento y policies básicas.
- **Q11:** [ExpenseRequestBudgetResolver](app/Services/Budgets/ExpenseRequestBudgetResolver.php) (prioridad descendente, empate por alcance usuario > rol > estado > región; solape de periodo con fechas de gasto o `created_at`); [ExpenseRequestBudgetLedgerWriter](app/Services/Budgets/ExpenseRequestBudgetLedgerWriter.php): `Commit` al completar cadena de aprobación, `Spend` al registrar pago (mismo presupuesto que el commit; sin commit no hay spend). Enganche en [ExpenseRequestApprovalService](app/Services/Approvals/ExpenseRequestApprovalService.php) y [RecordExpenseRequestPayment](app/Services/Payments/RecordExpenseRequestPayment.php).
- UI: listado Inertia `budgets.index` ([BudgetController](app/Http/Controllers/Budgets/BudgetController.php), [budgets/index.tsx](resources/js/pages/budgets/index.tsx)), sidebar para roles con `canManageBudgetsAndPolicies`. **Sin** pantallas de alta/edición de presupuestos (carga vía datos/seed u otra vía).
- Tests: [ExpenseRequestBudgetResolverTest.php](tests/Unit/ExpenseRequestBudgetResolverTest.php), [ExpenseRequestBudgetLedgerTest.php](tests/Feature/ExpenseRequestBudgetLedgerTest.php), [BudgetIndexHttpTest.php](tests/Feature/BudgetIndexHttpTest.php).

---

## 11. Vacaciones

**Objetivo (PLAN):** entitlements, reglas, solicitudes, motor de aprobación reutilizado, recibos y notificaciones, UI + tests.

**Estado: ✅**

**Hecho**

- Tablas y modelos: `vacation_entitlements`, `vacation_rules`, `vacation_requests`, `vacation_request_approvals`.
- `VacationRequestApprovalService` y tests de flujo.
- Policies para vacaciones.
- **Q9:** notificaciones encoladas mail + BD al iniciar flujo (primer grupo de aprobadores), tras cada aprobación parcial (solicitante), al completar cadena y al rechazar; recibo PDF de aprobación final (`vacation-requests.receipts.final-approval`, [VacationRequestFinalApprovalReceiptPdf](app/Services/VacationRequests/VacationRequestFinalApprovalReceiptPdf.php)); tests [VacationRequestNotificationsTest.php](tests/Feature/VacationRequestNotificationsTest.php), [VacationRequestFinalApprovalReceiptHttpTest.php](tests/Feature/VacationRequestFinalApprovalReceiptHttpTest.php).
- **Q10:** rutas en [routes/vacation-requests.php](routes/vacation-requests.php); [VacationRequestController](app/Http/Controllers/VacationRequests/VacationRequestController.php) (listado propio, crear/enviar con folio `VAC-{año}-{id}`, detalle, descarga recibo); [VacationRequestApprovalController](app/Http/Controllers/VacationRequests/VacationRequestApprovalController.php) (bandeja `vacation-requests.approvals.pending`, aprobar/rechazar); páginas Inertia bajo `resources/js/pages/vacation-requests/`; sidebar; tests [VacationRequestHttpTest.php](tests/Feature/VacationRequestHttpTest.php), [VacationBusinessDayCounterTest.php](tests/Unit/VacationBusinessDayCounterTest.php).

**Pendiente**

- UI o flujos de administración de **entitlements** y **reglas** de vacaciones (modelos y tablas existen; sin pantallas dedicadas en este bloque).

---

## Próximo salto recomendado (orden lógico)

La secuencia detallada para Plan mode está en **[Stage.v2.md](Stage.v2.md)** (Q1 → Qn). Resumen:

1. **Q1 — Etapa 9:** bandeja Inertia de balances `pending_*` ✅
2. **Q2 — Etapas 5+6:** bandeja in-app de `notifications` ✅
3. **Q3 — Etapa 7:** descarga autorizada evidencia de pago ✅
4. A partir de **Q4** (cancelación, comprobación PDF/descargas, aprobaciones PDF, adjuntos solicitud, activity log, vacaciones, presupuestos, CFDI) seguir el orden del archivo v2 o subdividir como indique ese documento.

---

*Generado como inventario de avance frente a PLAN.md; actualizar este archivo cuando se cierren etapas o se cambie el alcance.*
