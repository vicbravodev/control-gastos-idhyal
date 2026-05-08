# Design System Brief — Control de Gastos (Idhyal)

Documento de encargo para **Claude Design**. Su objetivo es que Claude Design produzca un Design System (DS) completo, alineado al stack, dominio y personas reales del producto, que luego se implementa en este repo.

> Idioma de la UI: **español (MX)**. Todo el copy, fechas, números y formatos deben asumir es-MX salvo que se indique lo contrario.

---

## 0. Cómo se debe usar este documento

Este doc es **el input principal** para Claude Design. Tiene tres partes:

1. **Contexto de producto y restricciones técnicas** (secciones 1–4) — para que el diseño no produzca cosas imposibles de implementar con nuestro stack.
2. **Catálogo de componentes y patrones** con requisitos ad-hoc del dominio (secciones 5–9) — el corazón del encargo.
3. **Entregables esperados y cómo briefear a Claude Design paso a paso** (secciones 10–11).

Al final hay una sección **"Step-by-step para pedírselo a Claude Design"** con el orden exacto de prompts que minimiza idas y venidas.

---

## 1. Producto en una página

**Idhyal Control de Gastos** es un sistema transaccional con **trazabilidad fuerte (auditoría)** que cubre tres ciclos de negocio:

1. **Ciclo financiero de gastos:** solicitud → aprobaciones por rol (workflow configurable) → pago por contabilidad con evidencia → comprobación del usuario (PDF/XML CFDI sin timbrado obligatorio) → revisión contable → balance/cuadre (settlement) → liquidación y cierre. Crear una solicitud **no libera fondos** hasta que las reglas de negocio y presupuesto lo indican.
2. **Ciclo de vacaciones:** balance por antigüedad (tabla configurable), límites por periodo, mismo motor de aprobaciones por rol.
3. **Presupuestos morfables (budgets):** aplicables por región, estado (entidad federativa), usuario o rol; ledger inmutable con `commit / spend / reverse / adjust`. Moneda **MXN**, montos en **centavos enteros** en BD.

Documentos clave: `expense_request`, `expense_report` (comprobación), `payment`, `settlement`, `reimbursement`, `vacation_request`, `vacation_adjustment`, `budget`, `budget_ledger_entry`, `approval_policy`, `system_receipt`, `evidence`.

Roles: `super_admin`, `secretario_general`, `contabilidad`, `coord_regional`, `coord_estatal`, `asesor`. Autorización con permisos; **workflow de aprobación con políticas configurables versionables** por tipo de documento y rol del solicitante.

Especificaciones funcionales detalladas: ver `docs/functional-spec-stage1.md`, `docs/data-dictionary-stage2.md`, `docs/roles-architecture-decision.md`, `docs/audit-log-decision.md`.

---

## 2. Stack y restricciones técnicas (no negociables)

El DS debe poder implementarse **con** lo que ya hay y **sin** introducir librerías nuevas si lo existente lo cubre.

- **Backend:** Laravel + Inertia.js
- **Frontend:** React 19 + TypeScript + Vite, vía Inertia (no SPA puro).
- **Estilos:** Tailwind CSS **v4** (config en `resources/css/app.css` con `@theme` y tokens en CSS variables, light + dark mode con `@custom-variant dark`).
- **Componentes base:** estilo **shadcn/ui** sobre **Radix UI primitives** (`@radix-ui/react-*` ya instalado: dialog, popover, dropdown-menu, navigation-menu, select, tooltip, toggle-group, etc.).
- **Iconos:** **lucide-react** (es la única librería de iconos permitida).
- **Fechas:** **react-day-picker v9** + **date-fns v4** con locale `es`.
- **Currency input:** **react-number-format v5**.
- **Animaciones:** `tw-animate-css` + keyframes ya definidos (`fade-in`, `slide-up`, `scale-in`); respeta `prefers-reduced-motion`.
- **Tipografía base:** `DM Sans` (ya cargada como `--font-sans`).
- **Color brand (ya definido como tokens):** `--brand-blue: oklch(0.45 0.12 245)`, `--brand-gold: oklch(0.76 0.15 80)`. El DS puede proponer escalas, pero estos dos hue/lightness **no se cambian**.
- **Tokens semánticos existentes:** `background`, `foreground`, `card`, `popover`, `primary`, `secondary`, `muted`, `accent`, `destructive`, `border`, `input`, `ring`, `chart-1..5`, `sidebar-*`. Se pueden ampliar (`success`, `warning`, `info`) pero **nunca** romper los nombres existentes.
- **Componentes UI ya implementados** (en `resources/js/components/ui/`): `alert`, `avatar`, `badge`, `breadcrumb`, `button`, `calendar`, `card`, `checkbox`, `collapsible`, `currency-input`, `date-picker`, `dialog`, `dropdown-menu`, `icon`, `input-otp`, `input`, `label`, `navigation-menu`, `popover`, `select`, `separator`, `sheet`, `sidebar`, `skeleton`, `spinner`, `table`, `textarea`, `toggle-group`, `toggle`, `tooltip`. **El DS debe rediseñar estos en lugar de reemplazarlos por otra arquitectura.**
- **No se usa:** Material UI, Chakra, Ant, Bootstrap, ni libs propietarias. **No se introducirán nuevos packages npm sin justificación.**

Implicación: el DS debe entregarse como **especificación visual + tokens + redlines + Figma (o equivalente)**, listo para mapear 1:1 a los componentes existentes. Nada de patrones que requieran replantear el árbol de Radix.

---

## 3. Personas y contextos de uso

| Persona | Rol técnico | Contexto típico | Implicación de diseño |
|---|---|---|---|
| **Asesor en campo** | `asesor` | Móvil o laptop con conexión irregular; captura solicitudes y sube evidencia (foto de ticket, PDF). | Mobile-first en formularios de creación; uploaders tolerantes a fotos grandes; estados offline-friendly. |
| **Coordinador estatal/regional** | `coord_estatal`, `coord_regional` | Desktop, revisa muchas solicitudes al día; aprueba o rechaza en lote. | Tablas densas, filtros potentes, bulk actions, vista de detalle compacta. |
| **Contabilidad** | `contabilidad` | Desktop, alta densidad de datos, conciliación de pagos y comprobaciones. | Tablas tipo ERP, columnas configurables, exports CSV, vista de timeline auditable. |
| **Secretario general / super admin** | `secretario_general`, `super_admin` | Desktop; configura políticas, presupuestos, antigüedades. | Formularios largos con validación inmediata; previews de configuración; diffs de versión. |
| **Empleado solicitante de vacaciones** | cualquiera con saldo | Desktop u oficina; pocas solicitudes al año pero **rangos largos** (semana, quincena). | Date range picker excelente; visualización de balance disponible vs comprometido. |

Reglas implícitas: **toda fecha relevante se muestra con día textual + año** (`8 de mayo de 2026`); **todo monto en MXN con `$` y separadores es-MX** (`$1,234.50`); **todo estado se comunica con un Status Badge semántico**, nunca solo con color.

---

## 4. Principios de diseño

1. **Auditable antes que bonito.** Cada acción crítica debe poder reconstruirse mirando la pantalla: quién, cuándo, qué cambió. Timelines y registros visibles, no enterrados en modales.
2. **Densidad inteligente.** Las tablas operativas no son dashboards: privilegiar información sobre espacio. Hay un modo "cómodo" y un modo "compacto" para tablas largas.
3. **Estado primero, copy después.** Los componentes muestran estado (loading, vacío, error, parcial, denegado por permiso) antes que decoración. Empty states explican **por qué** está vacío y **qué hacer**.
4. **Sin sorpresas con dinero.** Inputs monetarios siempre con la moneda visible, sin prefijo ambiguo, sin redondeos a la vista, y con **conversión a centavos invisible al usuario** (la UI ve y muestra pesos con dos decimales).
5. **Tiempo es del usuario.** Cualquier selector de fecha debe permitir saltar a años anteriores en **≤ 2 clics**. Nadie debe usar la flecha de mes para llegar a 1985.
6. **Accesible por defecto.** Contraste AA, focus visible siempre, navegación con teclado en todos los flujos críticos (aprobar/rechazar, subir evidencia, paginar tabla).

---

## 5. Foundations (lo que Claude Design debe entregar primero)

### 5.1 Color
- **Light y dark mode obligatorios.** Los tokens existentes (`--background`, `--foreground`, etc.) ya soportan ambos; solo se ajustan valores.
- **Escala de marca:** del `--brand-blue` derivar 11 tonos (50–950) en oklch, accesible AA contra `background` light y dark.
- **Semánticos:** además de `primary` y `destructive`, agregar tokens **`--success`**, **`--warning`**, **`--info`** (con foreground correspondiente). Los Status Badges del dominio mapean a estos.
- **Charts:** mantener `--chart-1..5` y entregar paleta serial accesible (8+ tonos diferenciables en escala de grises) — varios reportes son apilados.

### 5.2 Tipografía
- Familia: `DM Sans` (no cambia).
- Escala recomendada: `text-xs/sm/base/lg/xl/2xl/3xl/4xl` con line-heights y pesos definidos para: **número monetario**, **número de folio**, **etiqueta de estado**, **dato de tabla** (tabular-nums obligatorio para columnas numéricas).
- Variantes display para encabezados de detalle de documento.

### 5.3 Spacing, radii, elevations, motion
- Radii basados en `--radius` (ya existe `lg/md/sm`).
- Elevations: máximo 4 niveles; sombras suaves, **prohibido inner-shadow llamativo** en inputs.
- Motion: `fade-in 0.4s`, `slide-up 0.35s`, `scale-in 0.25s` (ya existen). Respeta `prefers-reduced-motion`.

### 5.4 Iconografía
- **Solo lucide-react.** El DS define qué icono representa cada concepto recurrente: `expense_request`, `expense_report`, `payment`, `settlement`, `reimbursement`, `vacation`, `budget`, `evidence`, `approval`, `rejection`, `cancellation`, `audit`. Tabla de mapeo concepto→icono explícita.

### 5.5 Tablas y números
- `font-variant-numeric: tabular-nums` por defecto en columnas numéricas.
- Negativos en rojo solo cuando representen deuda real, no para "pendiente".

---

## 6. Componentes con requisitos ad-hoc del dominio

> Por cada componente: **(a) variantes**, **(b) estados**, **(c) anatomía**, **(d) ejemplo real del dominio**, **(e) accesibilidad/teclado**.

### 6.1 DatePicker (rediseño obligatorio del existente)
**Problema actual:** el `date-picker.tsx` usa un calendario mensual sin navegación rápida. Para una **fecha de nacimiento** o **fecha de ingreso** (años atrás) el usuario debe hacer clic en la flecha decenas de veces.

**Requisitos:**
- **Variantes según contexto** (el DS define todas):
  - `single-recent`: para fechas operativas cercanas (fecha de gasto, fecha de pago). Vista mensual + atajos "Hoy / Ayer / Inicio de mes".
  - `single-distant`: para fechas históricas (nacimiento, ingreso laboral, antigüedad). **Obligatorio el patrón de tres niveles de zoom.**
  - `single-future`: para fechas futuras acotadas (fecha esperada de comprobación).
  - `range`: para filtros y vacaciones (ver 6.2).
- **Patrón de zoom de 3 niveles** (este es el detalle clave que motiva el rediseño):
  1. Vista de **días** (mes actual). El header muestra `"mayo 2026"` como **botón clickeable**.
  2. Click en el header → vista de **meses** (los 12 meses del año, grilla 4×3). Header ahora muestra `"2026"` como botón.
  3. Click en el año → vista de **años** (grilla de 12 años, ej. 2020–2031). Las flechas izquierda/derecha avanzan **12 años a la vez**.
  4. Opcional cuarto nivel **décadas** (grilla de 12 décadas) si se requiere ir muy atrás. Recomendado para `single-distant`.
- **Input de texto sincronizado al lado del trigger**: el usuario puede teclear `15/03/1985` directamente y el calendario se posiciona ahí. Formato visible es `dd/mm/yyyy`; valor interno ISO `yyyy-mm-dd`.
- **Atajos contextuales** (chips arriba del calendario):
  - `single-recent` → `Hoy`, `Ayer`, `Inicio de mes`, `Inicio de semana`.
  - `single-distant` → input de "ir al año" (campo numérico de 4 dígitos con stepper).
  - `range` → ver 6.2.
- **Estados:** default, focus, hover, selected, range-start, range-middle, range-end, disabled, today, out-of-month, holiday (opcional), error (fecha inválida o fuera de límites).
- **Restricciones:** soporte `minDate`, `maxDate`, `disabledDates`, `disabledDaysOfWeek`. Cuando la fecha tecleada caiga en zona deshabilitada, mostrar inline error con mensaje específico (`"No se permiten fechas posteriores a hoy"`).
- **Accesibilidad:** navegación con flechas dentro del nivel actual, `PageUp`/`PageDown` para mes anterior/siguiente, `Shift+PageUp/Down` para año, `Home/End` para inicio/fin de semana, `Enter` selecciona, `Esc` cierra. ARIA grid con `role="grid"`, `aria-selected`, `aria-current="date"` para hoy.
- **Localización:** `es` con `date-fns/locale/es`. Primer día de la semana **lunes**. Mes y día con minúscula como ya hace el calendario.
- **Mobile:** en viewports < 640px abrir como `Sheet` (bottom-sheet) en vez de `Popover`, con calendario a ancho completo.

### 6.2 DateRangePicker
- Construido sobre el mismo motor que 6.1.
- **Presets obligatorios** (chips visibles a la izquierda del calendario en desktop, arriba en mobile):
  - `Hoy`
  - `Esta semana` (lunes–domingo es-MX)
  - `Este mes`
  - `Este trimestre`
  - `Este año`
  - `Últimos 7 / 30 / 90 días`
  - `Periodo anterior` (relativo al rango actual seleccionado, útil para comparativas en reportes)
  - `Personalizado`
- **Doble calendario en desktop** (mes actual + mes siguiente), uno solo en mobile.
- **Resumen del rango debajo:** `Del 1 al 31 de mayo de 2026 — 31 días` (con conteo de días útil para vacaciones y reportes).
- **Validación visual de cruce inválido** (start > end): el segundo click reordena en silencio.

### 6.3 CurrencyInput (MXN)
- Símbolo `$` siempre visible (prefijo dentro del input, no como label suelto).
- Separador de miles `,`, decimal `.`, dos decimales fijos.
- Internamente trabajamos en **centavos**, pero **el input solo expone pesos** (la conversión la hace la action, no el componente — ya implementado así, no cambiarlo).
- **Estados especiales requeridos por dominio:**
  - `over-budget`: el monto excede el saldo del presupuesto resuelto. Borde + helper text en `warning` con mensaje `"Excede el saldo disponible: $X,XXX.XX"`. **No bloquear** — la regla de negocio puede permitirlo según política.
  - `over-policy-limit`: monto supera el límite que dispara una aprobación adicional según política. Mostrar info icon con tooltip explicando qué cadena de aprobación se activará.
- Soporta `minAmount` y `maxAmount` con error inline.
- Tabular-nums.

### 6.4 StatusBadge (mapa semántico del dominio)
Una sola pieza, muchas variantes. El DS entrega la **tabla de mapeo estado-de-dominio → variante visual**:

| Estado de dominio | Tipo | Color semántico sugerido |
|---|---|---|
| `submitted`, `approval_in_progress`, `expense_report_in_review`, `accounting_review` | en-curso | `info` |
| `approved`, `paid`, `expense_report_approved`, `closed` | éxito | `success` |
| `pending_payment`, `awaiting_expense_report`, `settlement_pending` | espera-acción | `warning` |
| `rejected`, `expense_report_rejected`, `cancelled`, `expired` | terminal-negativo | `destructive` |
| `draft` | neutro | `muted` |

- Variantes: `solid`, `subtle` (default), `outline`.
- Tamaños: `sm`, `md`.
- Siempre con icono lucide a la izquierda — **el color por sí solo no comunica estado** (accesibilidad).
- Opcional: `pulse` cuando representa "esperando que **tú** actúes" (badge que parpadea suavemente para llamar atención del aprobador).

### 6.5 ApprovalTimeline (componente de dominio)
Visualiza la cadena de pasos de una `approval_policy` aplicada al documento.
- Cada paso: avatar(es) de aprobador requerido por rol, estado del paso (`pending`, `approved`, `rejected`, `skipped`), timestamp, nota si la hubo.
- Línea vertical en desktop, horizontal compacta en mobile.
- Marca claramente **el paso actual** (qué falta) y **quién lo bloquea**.
- Click en paso abre detalle con auditoría (quién aprobó, IP/dispositivo si lo registramos).

### 6.6 DocumentDetailLayout (patrón, no componente único)
Layout para `expense-requests/show`, `vacation-requests/show`, etc. El DS define la rejilla y jerarquía visual:
- **Hero** con folio, título, monto (si aplica), `StatusBadge` grande, acciones primarias (`Aprobar`, `Rechazar`, `Cancelar`, `Subir comprobación`) según permisos.
- **Columna izquierda (2/3):** datos del documento + adjuntos + comentarios.
- **Columna derecha (1/3):** `ApprovalTimeline` + ledger de presupuesto afectado + recibos internos generados.
- En mobile se colapsa a una columna; el timeline va en `Collapsible` colapsado por defecto.

### 6.7 EvidenceUploader
Subida de PDF / imagen / XML (para CFDI).
- Drag & drop + click + paste (`Ctrl+V` de imagen del portapapeles, útil para screenshots).
- **Vista previa inline** según tipo: thumbnail para imagen, primer página para PDF, árbol resumido para XML CFDI (UUID, RFC emisor, RFC receptor, total — porque ya parseamos CFDI metadata, ver `Parse CFDI metadata` en commits).
- Validación: tamaño máx, MIME, **dedup por UUID CFDI** (ya implementado backend; el componente solo muestra el error `"Este CFDI ya fue cargado en la solicitud #1234"`).
- Estados: idle, dragging, uploading (con progress), parsing (cuando lee XML), success, error.
- Multi-archivo con orden y reorden.

### 6.8 DataTable (rediseño)
- Densidad: `comfortable` (default) y `compact` (toggle persistido en preferencias).
- Selector de columnas (mostrar/ocultar) con estado persistido por usuario por tabla.
- Paginación servidor-side (Inertia preserva URL params).
- **Filtros chip** arriba de la tabla, cada filtro es un chip removible; estado vacío de filtro = "Sin filtros activos".
- **Bulk actions** aparecen como barra fija inferior cuando hay selección, con conteo (`3 solicitudes seleccionadas — Aprobar | Rechazar | Exportar`).
- Sort por columna con triple estado (asc / desc / none).
- Sticky header en scroll.
- Empty state contextual (ver 6.10).
- **Skeleton row** mientras carga (no spinner centrado).

### 6.9 FilterBar
Contenedor de filtros sobre tablas. Combina:
- `DateRangePicker` (presets).
- `Select` multi para roles, regiones, estados.
- `Select` mono para estado del documento (`StatusBadge` dentro del item del dropdown).
- Búsqueda libre con debounce 300ms.
- **Botón "Guardar vista"** — el DS debe diseñar el patrón aunque la implementación se haga después.

### 6.10 EmptyState
- Variantes: `no-data`, `no-results-after-filter`, `no-permission`, `error`, `coming-soon`.
- Cada variante tiene icono lucide, título, descripción y CTA primaria + secundaria opcional.
- Ejemplos del dominio:
  - `expense-requests/index` sin solicitudes → `"Aún no has creado solicitudes — Crear solicitud"`.
  - `expense-requests/index` con filtros que no matchean → `"Ninguna solicitud coincide con los filtros — Limpiar filtros"`.
  - Vista bloqueada por permiso → `"No tienes permisos para ver esta sección — Contactar a tu administrador"`.

### 6.11 BudgetGauge
Pequeño componente para mostrar saldo de presupuesto en cards y en el header de creación de solicitud:
- Barra horizontal con segmentos: `consumido` (spend), `comprometido` (commit), `disponible`.
- Tooltip con desglose en MXN.
- Color: `success` si > 30% disponible, `warning` 10–30%, `destructive` < 10%.
- Variante compacta (1 línea) y expandida (con leyenda).

### 6.12 VacationBalanceCard (rediseño del existente)
- Saldo total, días disponibles, días tomados año actual, días pendientes de aprobación.
- Barra de progreso del año.
- CTA `"Solicitar vacaciones"`.
- Estado si está bloqueado por política (p.ej. blackout).

### 6.13 NotificationCenter
- Trigger en header (badge con contador).
- Dropdown / Sheet con lista agrupada por día.
- Item de notificación con icono según tipo, copy, timestamp relativo (`hace 5 min`), CTA inline (`Ver solicitud`), y acción rápida si aplica.
- Estado leído/no-leído visualmente diferenciado.

### 6.14 Otros (variantes ya existentes a revisar)
Rediseñar consistentemente: `Button` (variantes incluyendo `loading` con spinner), `Input` (con `leadingIcon`, `trailingIcon`, `clearable`), `Select` (con búsqueda en opciones largas como regiones/estados de México), `Dialog` y `Sheet` (con header pegajoso y footer de acciones), `Alert` (4 severidades), `Tabs`, `Toast` (no existe — agregar uno basado en Radix `toast` o `sonner` style sin nueva dep), `Tooltip`, `Skeleton`, `Spinner`.

---

## 7. Patrones de pantalla

El DS debe entregar mockups (mobile + desktop) para al menos:

1. **Login + 2FA + Recuperación.**
2. **Dashboard del asesor:** saldos, mis solicitudes recientes, vacaciones disponibles.
3. **Bandeja de aprobación del coordinador:** tabla densa, filtros, bulk approve.
4. **Crear solicitud de gasto** (flujo paso a paso o en una sola pantalla con secciones — el DS decide y argumenta).
5. **Detalle de `expense_request`** con timeline, evidencias, ledger.
6. **Subir comprobación (`expense_report`)** con uploader de CFDI.
7. **Cuadre / settlement:** comparación visual monto pagado vs comprobado, diferencia, acción a tomar.
8. **Reembolso directo (`reimbursement`).**
9. **Crear solicitud de vacaciones** con `DateRangePicker` y validación contra balance.
10. **Configuración de políticas de aprobación** (admin avanzado).
11. **Configuración de presupuestos** (selector polimórfico de target: región / estado / rol / usuario).
12. **Notificaciones** (full page).
13. **Reportes** con `DateRangePicker` + filtros + tabla + export.

Para cada pantalla, el DS entrega: estado por defecto, estado vacío, estado con error, estado denegado por permiso (si aplica), versión mobile.

---

## 8. Patrones transversales obligatorios

- **Permission-aware UI:** componentes que reciben `can` props (`canApprove`, `canCancel`) y se ocultan o se deshabilitan con tooltip explicativo (`"No puedes aprobar tus propias solicitudes"`). El DS define el comportamiento default (ocultar vs disabled-con-explicación) por tipo de acción.
- **Auditabilidad:** todo cambio de estado en un documento muestra **quién + cuándo** en la timeline. El DS define cómo se ve el evento de auditoría inline.
- **Confirmaciones:** acciones destructivas (`Rechazar`, `Cancelar`) usan `ConfirmationDialog` con razón obligatoria (textarea con minLength). Acciones constructivas (`Aprobar`) van directas con `Toast` confirmando.
- **Errores de validación:** inline bajo el input + resumen al inicio del form si hay > 3 errores, con scroll al primero al hacer click.
- **Loading patterns:** skeletons para listas y tablas, spinners contextual para botones, **nunca** bloquear toda la pantalla salvo en transiciones de página (Inertia ya lo hace).
- **Responsive breakpoints alineados a Tailwind v4:** `sm 640`, `md 768`, `lg 1024`, `xl 1280`, `2xl 1536`. Mobile-first.

---

## 9. Accesibilidad e i18n

- **WCAG 2.1 AA** mínimo. Contraste verificado con checker en light y dark.
- **Focus visible siempre** (no se quita el outline; se rediseña con `--ring`).
- **Skip-links** en layouts con sidebar.
- **Trampas de foco** en `Dialog` y `Sheet` (Radix lo da, pero verificarlo en mockups).
- **Tooltip nunca como única fuente de información** crítica.
- **Idioma:** todo el copy del DS en español (MX). Si Claude Design entrega ejemplos en inglés, debe haber su versión en es-MX.
- **Formatos:**
  - Fecha visual: `8 de mayo de 2026` o `08/05/2026` según contexto (long en detalle, corto en tablas).
  - Hora: 24h, `14:30`.
  - Moneda: `$1,234.50 MXN` (con sufijo MXN solo cuando hay riesgo de ambigüedad multimoneda; por defecto sin sufijo).
  - Número de folio: monoespaciado (`tabular-nums`), prefijo de tipo (`SOL-2026-0001`, `COMP-2026-0123`, etc.). El DS define formato visual; el real lo decide implementación.

---

## 10. Entregables esperados de Claude Design

1. **Documento de Foundations** (markdown o Figma con specs):
   - Paleta completa light + dark con tokens mapeados a los CSS vars existentes.
   - Escala tipográfica con tokens.
   - Spacing, radii, elevation, motion.
   - Mapa concepto-de-dominio → icono lucide.
2. **Catálogo de componentes** (Figma o equivalente, una página por componente):
   - Anatomy con redlines.
   - Todas las variantes y estados (incluido focus/disabled/loading/error).
   - Specs de teclado y ARIA.
   - Notas de implementación que apunten a Radix primitives existentes.
3. **Pattern Library** con las 13 pantallas de la sección 7, mobile + desktop.
4. **Tabla de mapeo estado-dominio → StatusBadge variant** (sección 6.4 expandida).
5. **Tokens en formato consumible** (JSON o CSS) que mapeen 1:1 a los CSS vars en `resources/css/app.css`. **No introducir nombres de token nuevos sin justificación.**
6. **Notas de migración**: por cada componente existente en `resources/js/components/ui/`, qué cambia visualmente, qué no, y qué riesgo tiene de regresión.
7. **Storybook plan** (opcional pero deseable): lista de stories por componente.

Formato preferido: **Figma** para visuales + **markdown** para tokens, mapeo de estados, y notas de migración. Los mockups deben venir con **specs exportables** (no solo imágenes).

---

## 11. Step-by-step para pedírselo a Claude Design

Orden recomendado para minimizar idas y venidas. Cada paso es un prompt independiente (esperar entregable antes del siguiente).

### Paso 1 — Setup de contexto
> "Te paso `docs/design-system-brief.md` del proyecto Idhyal Control de Gastos (Laravel + Inertia + React 19 + Tailwind v4 + shadcn/ui + Radix + lucide). Antes de diseñar, **resume en 10 bullets tu entendimiento** del dominio, las personas y las restricciones técnicas, y **lista las dudas o supuestos** que vas a asumir. No diseñes nada todavía."

Objetivo: detectar malentendidos antes de gastar trabajo de diseño.

### Paso 2 — Foundations
> "Apruebo tu entendimiento (con estos ajustes: …). Ahora entrega **Foundations** según la sección 5 del brief: paleta light/dark mapeada a los CSS vars existentes (`resources/css/app.css`), escala tipográfica DM Sans, spacing/radii/elevation/motion, y el mapa concepto-dominio → icono lucide. Formato: markdown con tokens + 1 página de Figma con muestras. **No empieces componentes todavía.**"

### Paso 3 — DatePicker (componente de mayor riesgo)
> "Diseña el **DatePicker** y **DateRangePicker** según la sección 6.1 y 6.2 del brief. Es el componente con más detalle y peor estado actual. Necesito: anatomy de las 4 variantes (`single-recent`, `single-distant`, `single-future`, `range`), las 3–4 vistas de zoom (días → meses → años → décadas), todos los estados, specs de teclado, versión mobile como Sheet. Quiero validarlo antes de seguir con el resto de componentes."

Objetivo: validar con Claude Design el patrón clave antes de aplicarlo en otros lados.

### Paso 4 — Resto del catálogo de componentes
> "DatePicker aprobado. Ahora entrega el **catálogo completo** según sección 6 del brief: CurrencyInput, StatusBadge (con la tabla de mapeo de estados), ApprovalTimeline, EvidenceUploader, DataTable, FilterBar, EmptyState, BudgetGauge, VacationBalanceCard, NotificationCenter, y el rediseño de Button/Input/Select/Dialog/Sheet/Alert/Tabs/Toast/Tooltip. Una página Figma por componente con anatomy, variantes, estados, specs de teclado y notas de Radix."

### Paso 5 — Patrones de pantalla
> "Componentes aprobados. Diseña las **13 pantallas** de la sección 7, mobile + desktop, con estados: default, vacío, error, sin permiso. Usa exclusivamente los componentes ya entregados. Si descubres que falta uno, lista la propuesta antes de inventarlo."

### Paso 6 — Notas de migración + tokens consumibles
> "Entrega: (a) **tokens** en JSON y CSS listos para mapear a `resources/css/app.css`, (b) **notas de migración** por cada componente existente en `resources/js/components/ui/` (qué cambia, qué no, riesgo de regresión), (c) **plan de Storybook** opcional. Con esto cierro el encargo y paso a implementación."

### Paso 7 — Devolverlo a Claude Code (este repo)
> Una vez Claude Design entregue todo, regresa con: el Figma público o exportado, el JSON/CSS de tokens, las notas de migración, y los mocks. Aquí los implemento componente por componente, empezando por Foundations → DatePicker → resto, abriendo una rama por bloque y respetando los componentes ya existentes (rediseño, no reemplazo).

---

## 12. Anti-objetivos (qué **no** debe hacer Claude Design)

- **No introducir** una librería de componentes nueva ni reemplazar Radix.
- **No diseñar** flujos que el backend no soporta (ver `functional-spec-stage1.md`).
- **No proponer** nombres de tokens incompatibles con los CSS vars existentes.
- **No usar** iconos que no estén en lucide.
- **No diseñar** todo en inglés.
- **No abusar** de animaciones; respetar `prefers-reduced-motion`.
- **No usar** color como única señal de estado (siempre icono + texto).
- **No diseñar** un dashboard de "métricas vanidosas" — el usuario quiere ejecutar trabajo, no contemplar gráficas.

---

## 13. Anexos

- `docs/functional-spec-stage1.md` — vocabulario, máquinas de estado, matriz de eventos.
- `docs/data-dictionary-stage2.md` — modelo de datos.
- `docs/roles-architecture-decision.md` — modelo de roles/permisos.
- `docs/audit-log-decision.md` — qué se audita y cómo se muestra.
- `AGENTS.md`, `PLAN.md`, `Stage.md`, `Stage.v2.md` — visión y plan general.
- Componentes existentes: `resources/js/components/ui/*`.
- Tokens existentes: `resources/css/app.css`.
