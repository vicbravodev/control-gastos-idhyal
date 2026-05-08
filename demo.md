# Demo - Control de Gastos

> **Password para todos los usuarios:** `password`

## Usuarios

| Usuario | Email | Rol |
|---------|-------|-----|
| Admin Demo | admin@demo.com | Super Admin |
| Secretario Demo | secretario@demo.com | Secretario General |
| Contadora Demo | contabilidad@demo.com | Contabilidad |
| Coord. Regional Norte | coord.regional@demo.com | Coord. Regional |
| Coord. Estatal NL | coord.estatal@demo.com | Coord. Estatal |
| Asesor Demo | asesor@demo.com | Asesor |
| Asesor Jalisco | asesor2@demo.com | Asesor |
| Asesor Oaxaca | asesor3@demo.com | Asesor |
| Coord. Estatal Oaxaca | coord.estatal.sur@demo.com | Coord. Estatal |

---

## Flujo 1: Solicitud de Gasto (ciclo completo)

Este es el flujo principal. Hay 14 solicitudes en distintas etapas del ciclo de vida.

### 1a. Crear y enviar solicitud
- [ ] Login como `asesor@demo.com`
- [ ] Crear nueva solicitud o ver la que ya está en **Submitted**

### 1b. Aprobación por Coord. Regional (paso 1)
- [ ] Login como `coord.regional@demo.com`
- [ ] Hay 2 solicitudes pendientes de su aprobación:
  - Combustible del Asesor ($1,750)
  - Renta de espacio del Coord. Estatal NL ($4,200)
- [ ] Puede aprobar o rechazar

### 1c. Aprobación por Contabilidad (paso 2)
- [ ] Login como `contabilidad@demo.com`
- [ ] Hay 1 solicitud donde paso 1 ya fue aprobado y paso 2 está pendiente:
  - Talleres regionales del Asesor Jalisco ($2,500)

### 1d. Solicitud rechazada
- [ ] Login como `asesor3@demo.com`
- [ ] Tiene 1 solicitud rechazada por el coord. regional ("monto excede presupuesto")

### 1e. Solicitud cancelada
- [ ] Login como `asesor@demo.com`
- [ ] Tiene 1 solicitud cancelada por él mismo

### 1f. Pendiente de pago
- [ ] Login como `contabilidad@demo.com`
- [ ] Hay 1 solicitud totalmente aprobada esperando que contabilidad registre el pago:
  - Boletos de avión ($3,200)

### 1g. Pagada, esperando comprobación
- [ ] Login como `asesor@demo.com`
- [ ] Tiene 1 solicitud pagada donde debe subir su comprobación de gastos

### 1h. Comprobación en revisión
- [ ] Login como `contabilidad@demo.com`
- [ ] Hay 1 comprobación del Asesor Jalisco pendiente de revisión (pagó $1,800, reporta $1,750)

### 1i. Comprobación aprobada
- [ ] Solicitud del Coord. Estatal NL con comprobación aprobada (pagaron $1,200, reporta $1,185)

### 1j. Liquidación pendiente (settlement)
- [ ] Asesor Oaxaca tiene una solicitud con liquidación pendiente: pagaron $3,000, reporta $2,850, diferencia de **$150** que el usuario debe devolver

### 1k. Cerrada sin diferencia
- [ ] Solicitud del Asesor cerrada limpia: pagaron $1,000, comprobó $1,000, diferencia **$0**

### 1l. Cerrada con devolución
- [ ] Solicitud del Asesor Jalisco: pagaron $4,000, comprobó $3,500, devolvió $500, ciclo cerrado

---

## Flujo 2: Solicitud de Vacaciones

### 2a. Crear solicitud
- [ ] Login como cualquier asesor o coord. estatal
- [ ] Crear nueva solicitud de vacaciones

### 2b. Aprobar/rechazar vacaciones
- [ ] Login como `secretario@demo.com`
- [ ] Tiene 1 solicitud pendiente del Asesor Jalisco (5 días hábiles)

### 2c. Vacaciones aprobadas
- [ ] Asesor Demo tiene vacaciones aprobadas en 10 días

### 2d. Vacaciones rechazadas
- [ ] Coord. Estatal NL tiene una solicitud rechazada ("excede el máximo de días por solicitud" — pedía 11 días)

### 2e. Vacaciones completadas
- [ ] Asesor Demo tiene unas vacaciones ya disfrutadas (hace ~20 días)

---

## Flujo 3: Presupuestos

| Entidad | Presupuesto Anual |
|---------|-------------------|
| Región Norte | $500,000 |
| Región Sur | $300,000 |
| Estado NL | $200,000 |
| Estado Oaxaca | $150,000 |

> Hay movimientos de ledger (commits y spends) en las solicitudes pagadas, así que puedes mostrar el consumo acumulado.

---

## Flujo 4: Políticas de Aprobación

| Tipo | Aprobadores | Pasos |
|------|-------------|-------|
| Gastos | Coord. Regional → Contabilidad | 2 pasos secuenciales (AND) |
| Vacaciones | Secretario General | 1 paso |

---

## Guión sugerido para el demo

1. **Asesor** (`asesor@demo.com`) → Crea o muestra una solicitud de gasto
2. **Coord. Regional** (`coord.regional@demo.com`) → Aprueba la solicitud (paso 1)
3. **Contabilidad** (`contabilidad@demo.com`) → Aprueba (paso 2), registra pago, revisa comprobación, cierra liquidación
4. **Secretario** (`secretario@demo.com`) → Aprueba una solicitud de vacaciones
5. **Admin** (`admin@demo.com`) → Muestra presupuestos y configuración de políticas
