# Manual del cliente — Control de Gastos IDHYAL

> **Contraseña para TODOS los usuarios de demo:** `password`
>
> Para regenerar todos los datos en un ambiente limpio:
> ```bash
> php artisan migrate:fresh --seed --force
> ```

---

## 1. Usuarios de demo

Cada usuario está asignado a un rol distinto. El campo **Género** alimenta el saludo personalizado del dashboard (Bienvenido / Bienvenida / Bienvenido(a)).

| Usuario | Email | Rol | Género | Saludo esperado |
|---|---|---|---|---|
| Admin Demo | `admin@demo.com` | Super Admin | Masculino | Bienvenido, Admin |
| Secretario Demo | `secretario@demo.com` | Secretario General | Masculino | Bienvenido, Secretario |
| Contadora Demo | `contabilidad@demo.com` | Contabilidad | Femenino | **Bienvenida**, Contadora |
| Coord. Regional Norte | `coord.regional@demo.com` | Coord. Regional | Masculino | Bienvenido, Coord. |
| Coord. Estatal NL | `coord.estatal@demo.com` | Coord. Estatal | Femenino | **Bienvenida**, Coord. |
| Asesor Demo | `asesor@demo.com` | Asesor | Masculino | Bienvenido, Asesor |
| Asesora Jalisco | `asesor2@demo.com` | Asesor | Femenino | **Bienvenida**, Asesora |
| Asesor Oaxaca | `asesor3@demo.com` | Asesor | Prefiero no especificar | Bienvenido(a), Asesor |
| Coord. Estatal Oaxaca | `coord.estatal.sur@demo.com` | Coord. Estatal | Femenino | **Bienvenida**, Coord. |

### Cómo probar el saludo personalizado
- [ ] Login como `contabilidad@demo.com` → el dashboard debe decir **"Bienvenida, Contadora"**.
- [ ] Logout. Login como `asesor@demo.com` → el dashboard debe decir **"Bienvenido, Asesor"**.
- [ ] Logout. Login como `asesor3@demo.com` → el dashboard debe decir **"Bienvenido(a), Asesor"**.

### Cómo probar la edición de género
- [ ] Login como cualquier usuario → menú de cuenta → **Configuración de perfil**.
- [ ] Cambiar el dropdown **Género** y guardar.
- [ ] Refrescar el dashboard → el saludo cambia inmediatamente.
- [ ] Login como `admin@demo.com` → **Usuarios** (admin) → editar un usuario → cambiar género y guardar.

---

## 2. Flujo de solicitud de gasto (ciclo completo, 1 comprobación)

Hay 14 solicitudes en distintas etapas del ciclo de vida. Es el flujo "histórico" que ya funcionaba antes de multi-comprobación.

### 2.1 Recién enviada (Submitted)
- [ ] Login como `asesor@demo.com` → solicitud "Visita a comunidad rural" ($1,500) en estado **Enviada**.

### 2.2 Esperando aprobación de coord. regional (paso 1)
- [ ] Login como `coord.regional@demo.com` → bandeja muestra 2 solicitudes:
  - Combustible del Asesor ($1,750)
  - Renta de espacio del Coord. Estatal NL ($4,200)
- [ ] Puede **Aprobar** o **Rechazar** cada una.

### 2.3 Pendiente de Contabilidad (paso 2 del flujo)
- [ ] Login como `contabilidad@demo.com` → bandeja muestra "Talleres regionales del Asesor Jalisco" ($2,500) con paso 1 ya aprobado.

### 2.4 Solicitud rechazada
- [ ] Login como `asesor3@demo.com` → tiene 1 solicitud rechazada por el coord. regional con nota "monto excede presupuesto".

### 2.5 Solicitud cancelada
- [ ] Login como `asesor@demo.com` → tiene 1 solicitud cancelada por él mismo.

### 2.6 Aprobada, esperando pago
- [ ] Login como `contabilidad@demo.com` → 1 solicitud "Boletos de avión" ($3,200) totalmente aprobada esperando que se registre el pago.
- [ ] Probar **Registrar pago** desde la vista de la solicitud.

### 2.7 Pagada, esperando comprobación
- [ ] Login como `asesor@demo.com` → tiene 1 solicitud pagada lista para subir comprobación (PDF + XML).

### 2.8 Comprobación en revisión
- [ ] Login como `contabilidad@demo.com` → 1 comprobación pendiente de revisión del Asesor Jalisco (pagó $1,800, reporta $1,750).

### 2.9 Comprobación aprobada, liquidación pendiente
- [ ] Solicitud del Coord. Estatal NL con comprobación aprobada ($1,200 pagado, $1,185 reportado, $15 a devolver).

### 2.10 Liquidación pendiente (devolución del usuario)
- [ ] Asesor Oaxaca tiene una solicitud con $3,000 pagados, $2,850 reportados, **$150 que el usuario debe devolver**.

### 2.11 Cerrada sin diferencia
- [ ] Solicitud del Asesor cerrada limpia: $1,000 pagado y comprobado, diferencia $0.

### 2.12 Cerrada con devolución registrada
- [ ] Solicitud de la Asesora Jalisco: $4,000 pagado, $3,500 comprobado, $500 ya devueltos, ciclo cerrado.

---

## 3. Flujo de comprobaciones múltiples (multi-receipt)

> **Concepto:** una sola solicitud aprobada con un cap (ej. $30K para un viaje) puede tener **N comprobaciones agrupadas** (hotel, vuelos, viáticos, gasolina, etc.), cada una con su propio XML/CFDI y su propio estado de validación contable.

### 3.1 Viaje Querétaro — 3 comprobaciones aprobadas, ciclo cerrado
**Solicitante:** `asesor@demo.com` · **Folio:** `EXP-2026-DEMO-018` · **Aprobado:** $30,000 · **Comprobado:** $30,000 · **Diferencia:** $0

- [ ] Login como `asesor@demo.com` → abrir la solicitud "Viaje a Querétaro — Capacitación regional 3 días".
- [ ] Verificar la **tarjeta de Balance** arriba:
  - Aprobado: $30,000.00
  - Comprobado: $30,000.00
  - Restante: $0.00
- [ ] Ver 3 tarjetas de comprobación, cada una con su CFDI:

| Etiqueta | Monto | CFDI extra que debe mostrarse |
|---|---|---|
| **Hotel** | $10,000 | IVA 16% trasladado + **ISH 3% impuesto local** |
| **Vuelos** | $10,000 | IVA 16% trasladado |
| **Gasolina (viáticos)** | $10,000 | IVA 16% trasladado + **IEPS Cuota 0.5913** + chip "Combustible (IEPS)" |

- [ ] Estado de cada comprobación: **Aprobada** (etiqueta visible al lado del label).
- [ ] La línea del timeline muestra los 3 "Comprobación enviada" + 3 "Comprobación aprobada" + el cierre del Settlement.

### 3.2 Viaje Monterrey — supera el presupuesto, requiere aprobación extra
**Solicitante:** `asesor2@demo.com` · **Folio:** `EXP-2026-DEMO-019` · **Aprobado:** $20,000 · **Comprobado:** $23,500 · **Excedente:** $3,500

- [ ] Login como `asesor2@demo.com` → abrir "Viaje a Monterrey — Junta directiva".
- [ ] La **tarjeta de Balance** muestra:
  - Aprobado: $20,000.00
  - Comprobado: $23,500.00
  - **Restante: -$3,500.00** (en rojo / con banner)
  - **Aviso visible**: "Aprobación adicional requerida por exceso de presupuesto".
- [ ] Tres comprobaciones:
  - **Hotel ejecutivo** ($9,500) — aprobada, con ISH.
  - **Comida cliente (RESICO)** ($4,000) — en revisión, con **retención ISR 1.25%** (régimen 626 RESICO, debe mostrar el chip **"RESICO (626)"**).
  - **Gasolina (excedente)** ($10,000) — en revisión, con **IEPS Cuota** + chip "Combustible (IEPS)".

#### Aprobar la extensión por sobre-cap
- [ ] Login como `coord.regional@demo.com` → bandeja: aparece **una segunda ronda de aprobación** para `EXP-2026-DEMO-019` con motivo "Extensión por exceso de presupuesto".
- [ ] Aprobar paso 1.
- [ ] Login como `contabilidad@demo.com` → bandeja: aparece el segundo paso de la extensión.
- [ ] Aprobar paso 2 → la solicitud queda libre para liquidación final.
- [ ] **Validar en el ledger de presupuesto** (admin): debe existir una segunda entrada de "commit" por la diferencia ($3,500), separada del commit original de $20,000.

### 3.3 Viaje CDMX — revisión parcial (2 validadas, 1 en revisión)
**Solicitante:** `coord.estatal@demo.com` · **Folio:** `EXP-2026-DEMO-020` · **Aprobado:** $15,000

- [ ] Login como `contabilidad@demo.com` → bandeja de comprobaciones pendientes: ver **"Cenas de trabajo"** (factura RESICO con retención ISR) que sigue en revisión, aunque "Hotel" y "Taxis" ya están validadas.
- [ ] Confirmar que el **Settlement no se cierra** mientras una comprobación quede en revisión.
- [ ] Validar "Cenas de trabajo" → debería disparar la creación de Settlement al haber quedado todas validadas.

### 3.4 Acciones adicionales que vale la pena probar
- [ ] **Como `asesor@demo.com`** en una solicitud con cap suficiente: usar el botón **"Agregar comprobación"** para subir un cuarto comprobante (PDF + XML). El balance se debe recalcular en vivo.
- [ ] **Como `contabilidad@demo.com`**: en una comprobación pendiente, usar **"Rechazar"** con nota → la comprobación queda como rechazada, las demás siguen validadas independientemente.
- [ ] Subir un **CFDI con UUID que ya fue usado** en otra solicitud → el sistema debe rechazarlo con "Esta factura ya fue comprobada en la solicitud …".

---

## 4. Extracción de impuestos CFDI (nuevos rubros)

Al subir XML, el parser ahora extrae automáticamente:

| Tipo | Cuándo aparece | Cómo lo verás |
|---|---|---|
| **IEPS (Cuota)** | XML con `Impuesto=003 TipoFactor=Cuota` o complemento `hidrocarburospetroliferos` (gasolina) | Sección "Impuestos trasladados" + chip "Combustible (IEPS)" |
| **ISH** (Impuesto Sobre Hospedaje) | XML con `implocal:TrasladosLocales ImpLocTrasladado="ISH"` | Sección "Impuestos locales" |
| **Retención ISR** | XML con `cfdi:Retencion Impuesto=001` (típico RESICO) | Sección "Retenciones" |
| **RESICO (626)** | Emisor con `RegimenFiscal=626` | Chip azul "RESICO (626)" en la tarjeta CFDI |

### Dónde verlo en la demo
- **Hotel con ISH:** receipts "Hotel" en cualquiera de los 3 viajes (Querétaro, Monterrey, CDMX).
- **Gasolina con IEPS:** receipt "Gasolina (viáticos)" en Querétaro y "Gasolina (excedente)" en Monterrey.
- **Retención ISR + RESICO:** receipts "Comida cliente (RESICO)" en Monterrey y "Cenas de trabajo" en CDMX.

### Cómo probarlo manualmente
- [ ] Login como `asesor@demo.com` → crear una nueva solicitud pequeña ($500 viáticos), aprobarla con `coord.regional@demo.com` + `contabilidad@demo.com`, registrar pago.
- [ ] Subir uno de los XML de ejemplo del repo (carpeta `tests/Support/CfdiTestFixtures.php` tiene los 3 patrones: gasolina IEPS, hospedaje ISH, RESICO retención).
- [ ] En la vista de la solicitud, la tarjeta CFDI debe mostrar las nuevas secciones según el XML cargado.

---

## 5. Reembolsos directos (sin solicitud previa)

Casos donde el usuario pagó de su bolsa y sube la factura directamente:

### 5.1 Reembolso recién enviado a revisión
- [ ] `contabilidad@demo.com` → bandeja: "Almuerzo con cliente — pagué de mi bolsa" ($850) del Asesor.

### 5.2 Reembolso aprobado, esperando que la empresa pague al solicitante
- [ ] Asesora Jalisco tiene un reembolso por toner ($450) aprobado, settlement marcado **"pendiente pago empresa"** ($-450 a favor del usuario).

### 5.3 Reembolso ya pagado y cerrado
- [ ] Coord. Estatal NL: reembolso de taxi ($220) totalmente liquidado.

---

## 6. Flujo de vacaciones

### 6.1 Solicitudes en distintos estados
| Estado | Usuario | Días |
|---|---|---|
| Aprobada (en 10 días) | Asesor Demo | 3 |
| En aprobación | Asesora Jalisco | 5 |
| Rechazada | Coord. Estatal NL | 11 |
| Completada (hace 20 días) | Asesor Demo | 3 |
| Próxima a expirar (creada hace 9 días) | Asesor Oaxaca | 3 |
| Ya expirada por inactividad | Coord. Estatal Oaxaca | 2 |

### 6.2 Pruebas sugeridas
- [ ] `secretario@demo.com` → aprobar/rechazar la solicitud pendiente.
- [ ] Como admin: ejecutar `php artisan vacation-requests:expire-stale-pending` → la solicitud "próxima a expirar" debe marcarse como expirada.

### 6.3 Ajustes manuales de saldo de vacaciones (admin)
Hay 4 ajustes pre-cargados (devolución de días, premios, correcciones). Login `admin@demo.com` → buscar al usuario → **Ajustes de vacaciones**.

---

## 7. Presupuestos y trazabilidad

| Entidad | Presupuesto Anual |
|---|---|
| Región Norte | $500,000 |
| Región Sur | $300,000 |
| Estado NL | $200,000 |
| Estado Oaxaca | $150,000 |

> Los presupuestos tienen movimientos de **ledger** (commits y spends) por cada solicitud pagada. La solicitud Monterrey ($20K cap + $3.5K sobre-cap aprobado) ilustra el caso donde se escriben **dos commits separados** sin mutar el primero.

---

## 8. Políticas de aprobación

| Tipo | Aprobadores | Pasos |
|---|---|---|
| Gastos (inicial) | Coord. Regional → Contabilidad | 2 secuenciales (AND) |
| Gastos (sobre-cap) | Coord. Regional → Contabilidad | 2 secuenciales adicionales con `reason=over_cap_extension` |
| Vacaciones | Secretario General | 1 paso |

---

## 9. Guión sugerido para una demo de 15 minutos

1. **`admin@demo.com`** → recorrer panel de usuarios y mostrar el campo género.
2. **`asesor2@demo.com`** → dashboard con "Bienvenida, Asesora".
3. **`asesor@demo.com`** → abrir viaje Querétaro: tarjeta de balance + 3 comprobaciones validadas + chips de IEPS / ISH en sus CFDI.
4. **`asesor2@demo.com`** → abrir viaje Monterrey: mostrar la alerta de **sobre-cap** y el chip "RESICO (626)" en la comida.
5. **`coord.regional@demo.com` → `contabilidad@demo.com`** → aprobar la **extensión** de Monterrey y ver el segundo commit en el ledger de presupuesto.
6. **`contabilidad@demo.com`** → validar la última comprobación pendiente del viaje CDMX → Settlement se cierra automáticamente.
7. **`secretario@demo.com`** → aprobar una solicitud de vacaciones para cerrar el demo.

---

## 10. Comandos útiles para pruebas

```bash
# Reiniciar todo
php artisan migrate:fresh --seed --force

# Solo refrescar datos demo (preserva esquema)
php artisan db:seed --class=DemoDataSeeder --force

# Disparar el expirador de vacaciones (manual)
php artisan vacation-requests:expire-stale-pending

# Disparar recordatorios de settlement pendiente
php artisan settlement:send-pending-reminders

# Tests
php artisan test                            # Toda la suite (396 tests)
php artisan test --filter=MultiReceipt       # Solo el flujo multi-comprobación
php artisan test --filter=CfdiComprobante    # Solo el parser CFDI
```
