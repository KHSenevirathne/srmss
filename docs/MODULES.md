# Modules — features & acceptance criteria

Each module maps to a section of the case study. Build them per the roadmap phases in
`CLAUDE.md`, following the `VehicleManager` pattern for CRUD. "Done" = acceptance criteria met
+ a feature test passing.

## 1. Auth, roles & users  *(Phase 1)*
- Login / logout / password reset (from starter kit).
- Three roles: **admin** (full), **supervisor** (operate + view), **operator** (log fuel,
  view). Admin can create users and assign roles.
- Each screen/action is guarded by role (`@can`, gates/policies).
- ✅ A non-admin cannot reach user management; an unauthenticated user is redirected to login.

## 2. Driver & vehicle management  *(Phase 2)*
- CRUD drivers and vehicles with search, status filter, pagination, validation.
- Unique `license_number` and `registration_number`.
- ✅ Vehicles module already works (the example). Drivers mirrors it. Invalid/duplicate input
  shows field errors and does not save.

## 3. Route planning  *(Phase 3)*
- CRUD routes (code, name, start/end, distance, service type).
- Add / reorder / remove ordered stops on a route.
- Assign a vehicle + driver to a route, respecting capacity and availability; block assigning
  a vehicle/driver that's unavailable or already booked for an overlapping schedule.
- Show the route + stops on a **Google Map** (Maps JavaScript API; key in `.env`, rendered in
  a Blade partial driven by Alpine). Optional: auto-estimate distance via the API.
- ✅ A route displays its stops in order and renders on a map; an unavailable bus can't be assigned.

## 4. Schedule management  *(Phase 4)* — hardest module
- Create daily/weekly/monthly timetables (route + vehicle + driver + times + validity range).
- **Conflict detection** (`ScheduleConflictService`): reject a new/edited schedule if the same
  vehicle or driver is already committed to an overlapping time window on overlapping dates.
  *Write unit tests for this first.*
- Edit / cancel; adjust for emergencies (reassign vehicle/driver).
- Generate `Trip` rows for scheduled dates; trip status = scheduled | on_time | delayed | completed.
- Calendar or list view, filterable by date/route/status.
- ✅ Overlapping schedule for the same bus is blocked with a clear message; a valid one saves.

## 5. Depot dashboard  *(Phase 5)*
- Summary cards: total routes, active trips today, available buses, assigned drivers.
- Live trip-status board (on-time / delayed / completed) — Livewire polling to refresh.
- Vehicle utilization widget; today's schedule overview.
- Alerts panel: licence expiring soon, service due, schedule conflicts.
- ✅ Cards reflect real seeded data; status board updates without a full page reload.

## 6. Fuel & maintenance log  *(Phase 3)*
- Record fuel per vehicle/trip (liters, cost, odometer, date); filter by vehicle + date range.
- Log routine/corrective maintenance (type, cost, date, notes, next-due); history per vehicle.
- Flag "service due" when `next_due_at` has passed.
- ✅ A vehicle shows its fuel + maintenance history; an overdue vehicle is flagged.

## 7. Reporting & analytics  *(Phase 5)*
- Reports: trip-completion (weekly/monthly), route-performance, fuel-consumption trend,
  vehicle utilization — all with date-range filters.
- **Export to PDF** (barryvdh/laravel-dompdf) — required by the brief.
- Charts (Chart.js) for utilization + fuel trends.
- Aggregation logic in `app/Services/ReportService.php`, unit-tested.
- ✅ A report renders on screen, downloads as a PDF, and the numbers match the data.

## 8. Testing & delivery  *(Phase 6)*
- Feature tests per module (`Livewire::test`), unit tests for services.
- Black-box test-case table in `docs/TEST_CASES.md`.
- Responsive Tailwind polish; user-manual screenshots; deploy; GitHub repo with member IDs.

## Out of scope unless time allows (icebox)
Email/SMS notifications · multi-depot support · 2FA · audit log · dark mode.
