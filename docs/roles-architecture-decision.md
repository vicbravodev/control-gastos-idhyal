# Roles y autorización: decisión frente a Spatie Permission

**Contexto:** [PLAN.md](../PLAN.md) (etapa 3) propone `spatie/laravel-permission` con roles fijos y seeders. El [diccionario de datos etapa 2](data-dictionary-stage2.md) ya define catálogo **`roles`** y **`users.role_id`** (un rol organizacional principal por usuario), **sin** Spatie.

## Decisión (2026-03, bloque Q0)

**No** se usa `spatie/laravel-permission`. El diseño vigente es **roles propios en base de datos** + **Laravel Policies** y comprobaciones en el modelo `User`.

### Implementación actual

- Tabla `roles` (slug estable), `users.role_id` → un rol por usuario en esta versión del dominio.
- Enum [`App\Enums\RoleSlug`](../app/Enums/RoleSlug.php) alineado al spec funcional (p. ej. `super_admin`, `contabilidad`, `asesor`).
- [`User::hasRoleSlug` / `hasRole` / `hasAnyRole`](../app/Models/User.php) y helpers de negocio (`hasExpenseRequestOversight`, `canManageBudgetsAndPolicies`, etc.).
- Middleware [`EnsureUserHasRole`](../app/Http/Middleware/EnsureUserHasRole.php) (alias `role` en rutas) para rutas que exigen uno o varios slugs.
- [`Gate::before`](../app/Providers/AppServiceProvider.php): `super_admin` autoriza salvo que una policy devuelva explícitamente `false`.
- Autorización fina por recurso en **policies** (`ExpenseRequestPolicy`, `VacationRequestPolicy`, etc.), no en matrices de permisos del paquete.

### Motivos

1. **Coherencia con el modelo persistido:** políticas de aprobación y presupuestos referencian `roles` y `role_id`; duplicar el catálogo en tablas de Spatie añadiría sincronización y migración de datos.
2. **Alcance acotado:** los roles de aplicación son un conjunto **cerrado** y estable; no hay hoy requisito de permisos granulares editables en UI ni múltiples roles simultáneos por usuario.
3. **Menos dependencias y superficie:** sin tablas `model_has_roles` / `permissions` ni convenciones adicionales en tests y seeders.

### Cuándo reconsiderar Spatie (u otro paquete)

- Si negocio exige **varios roles por usuario** o **permisos nombrados** asignables sin despliegue.
- Si se necesita **UI de administración** de roles/permisos con el ecosistema estándar del paquete.

Cualquier migración a Spatie sería un **proyecto explícito** (migraciones de datos, doble lectura temporal, actualización de policies y tests), no un cambio incremental encubierto.

### Referencias

- [Stage.md §3](../Stage.md) — inventario etapa 3.
- [PLAN.md](../PLAN.md) — texto original que asumía Spatie.
