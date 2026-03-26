# Stage v2 — Inventario y cola para Plan mode

Este documento complementa [Stage.md](Stage.md): resume **qué falta** y define un **orden recomendado** de bloques para ejecutarlos en **Plan mode de uno en uno** (o dos como máximo si el bloque lo indica).

**Leyenda:** ✅ hecho · 🟡 aún parcial · ⏳ casi sin empezar.

**Última actualización:** 2026-03-23 (post: auditoría exhaustiva — eliminados `expense_starts_on`/`expense_ends_on` de solicitudes de gasto; todos los Q0–Q12 cerrados; 229 tests pasan).

---

## Cómo usarlo en Plan mode

1. Abre **solo el bloque `Qn`** que toque (sección [Cola secuencial](#cola-secuencial-q1-qn)).
2. Pide explícitamente: *«Implementa el bloque Qn de Stage.v2»*.
3. Cuando quede cerrado, actualiza [Stage.md](Stage.md) y este archivo (marcar el bloque como hecho y ajustar el resumen si aplica).

---

## Resumen por etapa (1–11)

| # | Etapa | Estado v2 | Comentario breve |
|---|--------|-----------|------------------|
| 1 | Alineación alcance / estados | ✅ | [docs/functional-spec-stage1.md](docs/functional-spec-stage1.md) |
| 2 | Modelo de datos / migraciones | ✅ | Incluye `document_events`, `attachments`, `notifications`; campos `expense_starts_on`/`expense_ends_on` eliminados |
| 3 | Backend base (auth, roles, policies) | ✅ | Roles propios + policies; desviación vs PLAN documentada (**Q0** ✅) |
| 4 | Motor de aprobaciones | ✅ | Cancelación con nota (**Q4**) ✅; eventos de dominio Laravel explícitos fuera de alcance (decisión deliberada) |
| 5 | Solicitudes de gasto (CRUD + UI) | ✅ | Recibo PDF aprobación final (**Q6**) ✅; adjuntos pre-pago (**Q7**) ✅; solo `created_at` como fecha |
| 6 | Notificaciones + bitácora | ✅ | Decisión + MVP bitácora gastos (**Q8**) ✅; notifs vacaciones (**Q9**) ✅; UI in-app (**Q2**) ✅ |
| 7 | Pagos + evidencia + PDF | ✅ | Recibo PDF + descarga evidencia de pago autorizada (**Q3**) |
| 8 | Comprobaciones PDF/XML | ✅ | CFDI semántico (**Q12**) ✅; descargas Q5 ✅; solo `created_at` + `submitted_at` como fechas |
| 9 | Settlements + liquidación + scheduler | ✅ | PDF liquidación + descarga evidencia settlement ✅; bandeja balances pendientes (**Q1**) ✅ |
| 10 | Presupuestos + ledger | ✅ | Resolución + commit/spend en aprobar/pagar (**Q11**) ✅; CRUD presupuestos UI fuera de alcance |
| 11 | Vacaciones | ✅ | Dominio + motor + notifs + recibo PDF (**Q9**) ✅; HTTP/Inertia (**Q10**) ✅; admin entitlements/reglas fuera de alcance |

---

## Mapa de pendientes (por tema)

| Tema | Etapa(s) | Estado |
|------|----------|--------|
| Bandeja Inertia settlements `pending_*` | 9 | ✅ (Q1) |
| Bandeja Inertia notificaciones (`notifications` BD) | 5, 6 | ✅ (Q2) |
| Descarga evidencia **pago** (Attachment en `Payment`) | 7 | ✅ |
| Cancelación solicitud gasto con nota obligatoria + tests | 4 | ✅ (Q4) |
| Descarga PDF/XML comprobación + acuse PDF (enviada/aprobada) | 8 | ✅ (Q5) |
| Recibo PDF **aprobación final** (E3; por paso E2 opcional) | 5 | ✅ (Q6) |
| Adjuntos en solicitud (pre-pago) MVP | 5 | ✅ (Q7) |
| Decisión e implementación: Spatie activitylog vs `document_events` | 6 | ✅ (Q8) |
| Notificaciones mail+BD vacaciones; recibo PDF aprobación final | 11 | ✅ (Q9) |
| Rutas + Inertia vacaciones (CRUD + bandejas) | 11 | ✅ (Q10) |
| Presupuesto: resolver scope + ledger + hooks aprobar/pagar | 10 | ✅ (Q11) |
| Validación CFDI/XML semántica | 8 | ✅ (Q12) |
| Documentar desviación Spatie Permission vs roles propios | 3 | ✅ (Q0) |
| Eliminar `expense_starts_on`/`expense_ends_on` (auditoría) | 2, 5 | ✅ |
| `horizon:snapshot` / métricas scheduler (opcional) | 6 | 🟡 opcional |

---

## Cola secuencial (Q1 → Qn)

Cada bloque está pensado para **una sesión de Plan mode** (implementación acotada). Respeta el orden salvo que dependencias de negocio indiquen lo contrario.

### Q1 — Bandeja «balances pendientes» (etapa 9) ✅

- **Qué:** página Inertia (contabilidad y/o roles con oversight) que liste solicitudes con settlement en `pending_user_return` o `pending_company_payment`, con enlace al detalle existente.
- **Referencia:** [routes/expense-requests.php](routes/expense-requests.php), [app-sidebar.tsx](resources/js/components/app-sidebar.tsx), patrón de [pending-review.tsx](resources/js/pages/expense-requests/expense-reports/pending-review.tsx).
- **Fuera de alcance:** cambiar reglas de negocio del settlement; notificaciones nuevas.
- **Entrega:** ruta `expense-requests.settlements.pending-balances`, [ExpenseRequestSettlementController::pendingBalances](app/Http/Controllers/ExpenseRequests/ExpenseRequestSettlementController.php), [SettlementPolicy::viewPendingBalances](app/Policies/SettlementPolicy.php), [pending-balances.tsx](resources/js/pages/expense-requests/settlements/pending-balances.tsx), [SettlementPendingBalancesHttpTest.php](tests/Feature/SettlementPendingBalancesHttpTest.php).

### Q2 — Bandeja in-app de notificaciones (etapas 5 + 6) ✅

- **Qué:** listar notificaciones `database` del usuario autenticado (paginación, marcar como leídas opcional en MVP).
- **Referencia:** modelo `Illuminate\Notifications\DatabaseNotification`, layout/sidebar.
- **Fuera de alcance:** push web, filtros avanzados, emails nuevos.
- **Entrega:** rutas `notifications.index`, `notifications.read`, `notifications.read-all` en [routes/notifications.php](routes/notifications.php), [NotificationInboxController](app/Http/Controllers/NotificationInboxController.php), presentador [InAppNotificationPresenter](app/Services/Notifications/InAppNotificationPresenter.php), página [notifications/index.tsx](resources/js/pages/notifications/index.tsx), enlace + badge en [app-sidebar.tsx](resources/js/components/app-sidebar.tsx), `unread_notifications_count` en [HandleInertiaRequests](app/Http/Middleware/HandleInertiaRequests.php), tests [NotificationInboxHttpTest.php](tests/Feature/NotificationInboxHttpTest.php) e [InAppNotificationPresenterTest.php](tests/Unit/InAppNotificationPresenterTest.php).

### Q3 — Descarga autorizada evidencia de **pago** (etapa 7) ✅

- **Qué:** ruta GET + policy (mismo criterio que `view` del gasto o explícito contabilidad+solicitante) + enlace en [show.tsx](resources/js/pages/expense-requests/show.tsx); tests HTTP.
- **Referencia:** patrón `expense-requests.settlements.liquidation-evidence` + [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php).
- **Entrega:** ruta `expense-requests.payments.payment-evidence`, [ExpenseRequestController::downloadPaymentEvidence](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php), `downloadPaymentEvidence` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php), [show.tsx](resources/js/pages/expense-requests/show.tsx), [PaymentEvidenceHttpTest.php](tests/Feature/PaymentEvidenceHttpTest.php), ampliación en [ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php).

### Q4 — Cancelación de solicitud de gasto con nota (etapa 4) ✅

- **Qué:** servicio + ruta POST/PATCH + transición de estado + `DocumentEvent` + policy; nota obligatoria; tests felices y 403.
- **Referencia:** [ExpenseRequestApprovalService](app/Services/Approvals/ExpenseRequestApprovalService.php), enums de estado.
- **Entrega:** ruta `expense-requests.cancel`, [CancelExpenseRequest](app/Services/ExpenseRequests/CancelExpenseRequest.php), [ExpenseRequestController::cancel](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php), [CancelExpenseRequestRequest](app/Http/Requests/ExpenseRequests/CancelExpenseRequestRequest.php), `cancel` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php), formulario en [show.tsx](resources/js/pages/expense-requests/show.tsx), tests [ExpenseRequestCancellationHttpTest.php](tests/Feature/ExpenseRequestCancellationHttpTest.php) y política en [ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php).

### Q5 — Comprobación: descarga adjuntos + recibo PDF (etapa 8) ✅

- **Qué:** descarga autorizada de PDF/XML del `ExpenseReport`; al menos un recibo PDF (enviada o aprobada — acotar en el prompt).
- **Referencia:** [ExpenseReportAttachmentWriter](app/Services/ExpenseReports/ExpenseReportAttachmentWriter.php), dompdf existente.
- **Entrega:** rutas `expense-requests.expense-reports.verification-attachment` y `expense-requests.receipts.expense-report-verification`; `downloadExpenseReportVerificationAttachment` y `downloadExpenseReportVerificationReceipt` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php); [ExpenseRequestController](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php); `findVerificationAttachment` en [ExpenseReportAttachmentWriter](app/Services/ExpenseReports/ExpenseReportAttachmentWriter.php); [ExpenseRequestExpenseReportVerificationReceiptPdf](app/Services/ExpenseRequests/ExpenseRequestExpenseReportVerificationReceiptPdf.php) + vista [expense-request-expense-report-verification.blade.php](resources/views/pdf/expense-request-expense-report-verification.blade.php); [show.tsx](resources/js/pages/expense-requests/show.tsx); tests [ExpenseReportVerificationHttpTest.php](tests/Feature/ExpenseReportVerificationHttpTest.php) y [ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php).

### Q6 — Recibos PDF por hitos de aprobación (etapa 5) ✅

- **Qué:** alcance mínimo acordado: **un recibo PDF al completar la cadena** (estado `pending_payment` en adelante), constancia E3 del spec; **no** se genera todavía un PDF distinto por cada paso intermedio (E2 — opcional futuro).
- **Referencia:** [ExpenseRequestApprovalService](app/Services/Approvals/ExpenseRequestApprovalService.php) (transición a `pending_payment`).
- **Entrega:** ruta `expense-requests.receipts.final-approval`, [ExpenseRequestFinalApprovalReceiptPdf](app/Services/ExpenseRequests/ExpenseRequestFinalApprovalReceiptPdf.php), vista [expense-request-final-approval.blade.php](resources/views/pdf/expense-request-final-approval.blade.php), `downloadFinalApprovalReceipt` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php), [ExpenseRequestController::downloadFinalApprovalReceipt](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php), enlace en [show.tsx](resources/js/pages/expense-requests/show.tsx), tests [ExpenseRequestFinalApprovalReceiptHttpTest.php](tests/Feature/ExpenseRequestFinalApprovalReceiptHttpTest.php) y política en [ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php).

### Q7 — Adjuntos en solicitud antes del pago (etapa 5) ✅

- **Qué:** morph `Attachment` en `ExpenseRequest`; crear (multipart), editar en `submitted`, añadir más mientras `approval_in_progress` o `pending_payment` (solo solicitante); descarga con `view`; eliminar solo solicitante en ventana pre-pago; límites en [config/expense_requests.php](config/expense_requests.php) (10 archivos, 10 MB c/u, PDF/JPEG/PNG/WEBP).
- **Fuera de alcance:** reemplazar flujo de comprobación post-pago.
- **Entrega:** [ExpenseRequestSubmissionAttachmentWriter](app/Services/ExpenseRequests/ExpenseRequestSubmissionAttachmentWriter.php); reglas en [StoreExpenseRequestRequest](app/Http/Requests/ExpenseRequests/StoreExpenseRequestRequest.php), [UpdateExpenseRequestRequest](app/Http/Requests/ExpenseRequests/UpdateExpenseRequestRequest.php), [StoreExpenseRequestSubmissionAttachmentsRequest](app/Http/Requests/ExpenseRequests/StoreExpenseRequestSubmissionAttachmentsRequest.php); rutas `expense-requests.submission-attachments.{store,destroy,download}`; métodos en [ExpenseRequestController](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php); políticas `addSubmissionAttachments`, `deleteSubmissionAttachment`, `downloadSubmissionAttachment` en [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php); UI [create.tsx](resources/js/pages/expense-requests/create.tsx), [edit.tsx](resources/js/pages/expense-requests/edit.tsx), [show.tsx](resources/js/pages/expense-requests/show.tsx); tests [ExpenseRequestSubmissionAttachmentsHttpTest.php](tests/Feature/ExpenseRequestSubmissionAttachmentsHttpTest.php) y [ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php).

### Q8 — Bitácora: decisión + MVP (etapa 6) ✅

- **Qué:** documentar decisión (Spatie `laravel-activitylog` vs ampliar `document_events`) e implementar el **mínimo** acordado (p. ej. solo eventos de gasto críticos).
- **Fuera de alcance:** migrar todo el historial retroactivo si no aplica.
- **Entrega:** [docs/audit-log-decision.md](docs/audit-log-decision.md); `DocumentEventType::ExpenseRequestChainApproved` + registro en [ExpenseRequestApprovalService::approve](app/Services/Approvals/ExpenseRequestApprovalService.php); relación `documentEvents` en [ExpenseRequest](app/Models/ExpenseRequest.php); [ExpenseRequestDocumentEventTimelinePresenter](app/Services/ExpenseRequests/ExpenseRequestDocumentEventTimelinePresenter.php); prop `document_timeline` en [ExpenseRequestController::show](app/Http/Controllers/ExpenseRequests/ExpenseRequestController.php); tarjeta «Bitácora» en [show.tsx](resources/js/pages/expense-requests/show.tsx); tests [ExpenseRequestDocumentEventTimelinePresenterTest.php](tests/Unit/ExpenseRequestDocumentEventTimelinePresenterTest.php), [ExpenseRequestApprovalWorkflowTest.php](tests/Feature/ExpenseRequestApprovalWorkflowTest.php), [ExpenseRequestHttpTest.php](tests/Feature/ExpenseRequestHttpTest.php); diccionario de datos actualizado en [data-dictionary-stage2.md](docs/data-dictionary-stage2.md).

### Q9 — Vacaciones: notificaciones + recibos PDF (backend) (etapa 11) ✅

- **Qué:** notificaciones ShouldQueue mail+BD en transiciones clave; PDF(s) análogos a gastos donde tenga sentido.
- **Referencia:** [VacationRequestApprovalService](app/Services/Approvals/VacationRequestApprovalService.php).
- **Entrega:** [VacationRequestNotificationDispatcher](app/Services/VacationRequests/VacationRequestNotificationDispatcher.php), [VacationRequestApprovalProgressResolver](app/Services/VacationRequests/VacationRequestApprovalProgressResolver.php), notificaciones en [app/Notifications/VacationRequests/](app/Notifications/VacationRequests/), `DB::afterCommit` en [VacationRequestApprovalService](app/Services/Approvals/VacationRequestApprovalService.php); ruta `vacation-requests.receipts.final-approval`, [VacationRequestFinalApprovalReceiptPdf](app/Services/VacationRequests/VacationRequestFinalApprovalReceiptPdf.php), vista [vacation-request-final-approval.blade.php](resources/views/pdf/vacation-request-final-approval.blade.php), `downloadFinalApprovalReceipt` en [VacationRequestPolicy](app/Policies/VacationRequestPolicy.php), [VacationRequestController::downloadFinalApprovalReceipt](app/Http/Controllers/VacationRequests/VacationRequestController.php), [routes/vacation-requests.php](routes/vacation-requests.php); tipos `vacation_request.*` en [InAppNotificationPresenter](app/Services/Notifications/InAppNotificationPresenter.php) + prop `vacation_request_id` en bandeja; tests [VacationRequestNotificationsTest.php](tests/Feature/VacationRequestNotificationsTest.php), [VacationRequestFinalApprovalReceiptHttpTest.php](tests/Feature/VacationRequestFinalApprovalReceiptHttpTest.php).

### Q10 — Vacaciones: HTTP + Inertia (etapa 11) ✅

- **Qué:** rutas, controladores, páginas listado/crear/detalle, bandeja aprobador; tests feature.
- **Depende de:** Q9 opcional pero recomendable antes o en el mismo bloque si se acota.
- **Entrega:** `Route::resource` parcial + aprobaciones en [routes/vacation-requests.php](routes/vacation-requests.php); [VacationRequestController](app/Http/Controllers/VacationRequests/VacationRequestController.php) (`index`, `create`, `store`, `show`, `downloadFinalApprovalReceipt`); [VacationRequestApprovalController](app/Http/Controllers/VacationRequests/VacationRequestApprovalController.php); [StoreVacationRequestRequest](app/Http/Requests/VacationRequests/StoreVacationRequestRequest.php), approve/reject Form Requests; [VacationRequestFolioGenerator](app/Services/VacationRequests/VacationRequestFolioGenerator.php), [VacationBusinessDayCounter](app/Services/VacationRequests/VacationBusinessDayCounter.php); `isPendingStepActive` en [VacationRequestApprovalService](app/Services/Approvals/VacationRequestApprovalService.php); páginas [vacation-requests/index.tsx](resources/js/pages/vacation-requests/index.tsx), [create.tsx](resources/js/pages/vacation-requests/create.tsx), [show.tsx](resources/js/pages/vacation-requests/show.tsx), [approvals/pending.tsx](resources/js/pages/vacation-requests/approvals/pending.tsx); enlaces en [app-sidebar.tsx](resources/js/components/app-sidebar.tsx) y «Ver vacaciones» en [notifications/index.tsx](resources/js/pages/notifications/index.tsx); tests [VacationRequestHttpTest.php](tests/Feature/VacationRequestHttpTest.php), [VacationBusinessDayCounterTest.php](tests/Unit/VacationBusinessDayCounterTest.php).

### Q11 — Presupuestos: resolución + ledger + enganche (etapa 10) ✅

- **Qué:** servicio «más específico gana», escrituras en `budget_ledger_entries` en aprobar/pagar (definir reglas con negocio), UI mínima contabilidad, tests.
- **Es el bloque más grande:** conviene subdividirlo en Plan mode (Q11a resolución, Q11b hooks, Q11c UI) si hace falta.
- **Reglas implementadas:** solape de periodo del presupuesto con ventana del gasto (fechas de gasto o, si faltan, día de `created_at`); candidatos por `user` / `role` / `state` / `region` del solicitante; gana mayor `priority`, empate por alcance (usuario > rol > estado > región). **Commit** al cerrar cadena de aprobación (`pending_payment`); **Spend** al registrar pago, mismo `budget_id` que el commit (si no hubo commit, no se escribe spend). Idempotencia por `source` + tipo de entrada.
- **Entrega:** [ExpenseRequestBudgetResolver](app/Services/Budgets/ExpenseRequestBudgetResolver.php), [ExpenseRequestBudgetLedgerWriter](app/Services/Budgets/ExpenseRequestBudgetLedgerWriter.php); llamadas en [ExpenseRequestApprovalService::approve](app/Services/Approvals/ExpenseRequestApprovalService.php) y [RecordExpenseRequestPayment](app/Services/Payments/RecordExpenseRequestPayment.php); ruta `budgets.index`, [BudgetController::index](app/Http/Controllers/Budgets/BudgetController.php), [budgets/index.tsx](resources/js/pages/budgets/index.tsx), enlace sidebar (`can_manage_budgets`); [BudgetFactory](database/factories/BudgetFactory.php); morph `budget` en [AppServiceProvider](app/Providers/AppServiceProvider.php); tests [ExpenseRequestBudgetResolverTest.php](tests/Unit/ExpenseRequestBudgetResolverTest.php), [ExpenseRequestBudgetLedgerTest.php](tests/Feature/ExpenseRequestBudgetLedgerTest.php), [BudgetIndexHttpTest.php](tests/Feature/BudgetIndexHttpTest.php).
- **Fuera de este bloque:** CRUD Inertia de presupuestos, bloqueo por exceso de cupo, reversos automáticos al cancelar tras commit (B3 manual / reglas futuras).

### Q12 — CFDI / XML semántico (etapa 8, avanzado) ✅

- **Qué:** validación o extracción de datos del XML según reglas fiscales acordadas; puede ir después de Q5 si solo se necesita `mimes` al inicio.
- **Reglas implementadas:** XML bien formado; elemento raíz `Comprobante` en namespace SAT CFDI 3.3 o 4.0; atributos `Version`, `Total`, `Moneda` (por defecto **MXN**); opcionalmente el `Total` debe alinear con `reported_amount_cents` (tolerancia en centavos configurable). Desactivar validación o partes vía `config/expense_reports.php` / variables `EXPENSE_REPORT_CFDI_*`.
- **Entrega:** [CfdiComprobanteValidator](app/Services/ExpenseReports/CfdiComprobanteValidator.php); llamadas en [SaveExpenseReportDraft](app/Services/ExpenseReports/SaveExpenseReportDraft.php) y [SubmitExpenseReportForReview](app/Services/ExpenseReports/SubmitExpenseReportForReview.php); [config/expense_reports.php](config/expense_reports.php); tests [CfdiComprobanteValidatorTest.php](tests/Unit/CfdiComprobanteValidatorTest.php), ampliación [ExpenseReportHttpTest.php](tests/Feature/ExpenseReportHttpTest.php).
- **Fuera de alcance:** validación XSD completa del SAT; timbrado; catálogos SAT.

### Q0 (opcional, no bloqueante) — Documentación arquitectura (etapa 3) ✅

- **Qué:** acta corta en `docs/` o nota en README: roles propios vs Spatie Permission; enlazar desde [Stage.md](Stage.md).
- **Solo código si** se decide migrar a Spatie (normalmente **no** sin aprobación explícita).
- **Entrega:** [docs/roles-architecture-decision.md](docs/roles-architecture-decision.md); §3 de [Stage.md](Stage.md) enlaza la decisión y deja de listar la documentación como pendiente abierta.

---

## Bloques ya cerrados respecto a Stage v1 (referencia)

| Entrega | Dónde |
|--------|--------|
| Recibo PDF liquidación de balance | `expense-requests.receipts.settlement-liquidation`, [ExpenseRequestSettlementLiquidationReceiptPdf](app/Services/ExpenseRequests/ExpenseRequestSettlementLiquidationReceiptPdf.php) |
| Descarga evidencia liquidación (Attachment settlement) | `expense-requests.settlements.liquidation-evidence`, [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php) |
| Descarga evidencia pago (Attachment `Payment`) | `expense-requests.payments.payment-evidence`, [ExpenseRequestPolicy](app/Policies/ExpenseRequestPolicy.php) |
| Tests | [SettlementLiquidationHttpTest.php](tests/Feature/SettlementLiquidationHttpTest.php), [PaymentEvidenceHttpTest.php](tests/Feature/PaymentEvidenceHttpTest.php), [ExpenseRequestAuthorizationTest.php](tests/Feature/ExpenseRequestAuthorizationTest.php) |

---

*Mantén este archivo y [Stage.md](Stage.md) sincronizados cuando cierres un `Qn`.*
