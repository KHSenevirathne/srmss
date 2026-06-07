# Architecture & conventions

This is the working reference for *how the SRMSS code is organised* — the folder structure,
naming rules, and the recipe for adding a module. Keep it accurate; it mirrors the 3-Tier
diagram in the report. (For *what* to build, see `MODULES.md`; for the schema, `DATA_MODEL.md`.)

## 1. The three tiers → folders

The app is a clean 3-tier Laravel application. Each tier lives in specific folders:

| Tier | Responsibility | Lives in |
|---|---|---|
| **Presentation** | What the user sees. HTML, Tailwind, small Alpine bits. Display only — *no business logic*. | `resources/views/` (Blade + `livewire/` partials), Flux components |
| **Application / logic** | UI state + actions (Livewire), and reusable domain logic (services). | `app/Livewire/` (one component per screen), `app/Services/` (plain PHP, unit-tested) |
| **Data** | Tables, rows-as-objects, relationships. No raw SQL elsewhere. | `app/Models/` (Eloquent), `database/migrations/`, MySQL |

**The golden rule (no "god components"):** a Livewire component holds *UI state and thin
actions only*. The moment logic is more than a couple of lines, needs its own test, or would
be duplicated, it moves into a **service** in `app/Services/`. Examples already planned:
`ScheduleConflictService` (Phase 4) and `ReportService` (Phase 5). Components *call* services;
services never know about the UI.

## 2. Folder map (where things go)

```
app/
  Livewire/        one full-page component per module screen  (VehicleManager, DriverManager, …)
  Models/          one Eloquent model per table               (Vehicle, Driver, BusRoute, …)
  Services/        domain logic, framework-free, unit-tested  (ScheduleConflictService, …)
  Providers/       app wiring (AppServiceProvider)
database/
  migrations/      one file per table
  seeders/         RolesAndPermissionsSeeder (RBAC) + DemoDataSeeder (demo records)
resources/views/
  livewire/        the Blade view for each Livewire component
  layouts/         app shell (sidebar) — merge into, never overwrite
  components/      shared Blade UI pieces
routes/
  web.php          one guarded route per screen
tests/
  Feature/         per-screen tests (Livewire::test / HTTP)
  Unit/            per-service tests
docs/              this folder — design & process notes
```

## 3. Naming conventions

| Thing | Rule | Example |
|---|---|---|
| Database | lowercase project name | `srmss` |
| Table | snake_case, **plural** | `bus_routes`, `fuel_logs` |
| Model | StudlyCase, **singular** | `BusRoute`, `FuelLog` |
| Route model | **`BusRoute`**, never `Route` (clashes with Laravel's `Route` facade) | table `bus_routes` |
| Livewire component | `<Thing>Manager` | `VehicleManager` |
| Blade view for it | kebab-case under `livewire/` | `vehicle-manager.blade.php` |
| Permission | `verb-noun`, kebab-case | `manage-fleet`, `log-fuel` |
| Route name | lowercase, matches screen | `vehicles` |

Columns: money/decimals use `decimal` casts; dates use `date`/`datetime` casts. The database
is **utf8mb4 / utf8mb4_unicode_ci** (full Unicode).

## 4. Authorisation (RBAC) — single source of truth

Roles and permissions are defined in **`database/seeders/RolesAndPermissionsSeeder.php`** and
nowhere else. Screens check *permissions*, not roles, so the mapping can change without
touching any screen.

| Permission | admin | supervisor | operator |
|---|:---:|:---:|:---:|
| `manage-users` | ✅ | | |
| `manage-fleet` (vehicles, drivers) | ✅ | ✅ | |
| `manage-routes` | ✅ | ✅ | |
| `manage-schedules` | ✅ | ✅ | |
| `log-fuel` (fuel, maintenance) | ✅ | ✅ | ✅ |
| `view-reports` (dashboard, reports) | ✅ | ✅ | ✅ |

How it's enforced (defence in depth):
- **Route:** `->middleware('can:manage-fleet')` → no permission means a 403, the page never loads.
- **Blade:** `@can('manage-fleet') … @endcan` → hides buttons/nav the user can't use.

Demo logins (password `password` for all): `admin@srmss.test`, `supervisor@srmss.test`,
`operator@srmss.test`. Log in as each to see the nav and buttons change.

## 5. Layout wiring (one-time setup)

Full-page Livewire screens render inside the Flux sidebar shell
(`resources/views/layouts/app.blade.php`). This is set **once** in
`AppServiceProvider` (`config(['livewire.layout' => 'layouts.app'])`) — so no component needs
its own layout attribute.

## 6. Recipe — adding a new CRUD module (copy VehicleManager)

1. **Migration** in `database/migrations/` — one table, with `unique()` where the spec says so.
2. **Model** in `app/Models/` — `$fillable`, `$casts`, typed relationship methods.
3. **Livewire component** in `app/Livewire/` — copy `VehicleManager`; rename fields + validation.
   Move any non-trivial logic into a **service**, not the component.
4. **Blade view** in `resources/views/livewire/` — copy `vehicle-manager.blade.php`; wrap
   destructive buttons in `@can('<permission>')`.
5. **Route** in `routes/web.php` — inside the `auth`+`verified` group, add
   `->middleware('can:<permission>')`.
6. **Nav** in `layouts/app/sidebar.blade.php` — add one `@can`-wrapped `<flux:sidebar.item>`.
7. **Test** in `tests/Feature/` — access control + create/edit/delete/validation.
8. If you added a new permission, register it in `RolesAndPermissionsSeeder` and re-seed.
