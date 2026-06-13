# Test cases & results

This is the black-box test-case table for SRMSS, plus the white-box (unit) coverage of
the service logic. Every case below corresponds to an automated test in `tests/`, so the
"Result" column reflects an actual passing run, not a manual claim.

## How to run

```bash
php artisan test            # runs the whole suite
php artisan test --compact  # one-line summary
```

- **Environment:** tests run against an in-memory **SQLite** database (`phpunit.xml`), rebuilt
  fresh for every test (`RefreshDatabase`), so they never touch the MySQL dev/production data.
- **Latest run:** **129 passing, 304 assertions.**
- **Technique:** the feature tests (Section A) are *black-box* — they drive each screen through
  its public behaviour (HTTP requests and Livewire actions) and assert on outcomes. The service
  tests (Section B) are *white-box* — they target the conflict and reporting logic directly.

Roles referenced below (see `database/seeders/RolesAndPermissionsSeeder.php`):
**admin** (all), **supervisor** (operate + view), **operator** (log fuel + view), **none** (a
user with no role, used to prove the permission gate).

---

## A. Black-box test cases (feature tests)

### A1. Authentication & access control  — `tests/Feature/Auth/*`

| ID | Scenario | Input / steps | Expected result | Result |
|---|---|---|---|---|
| TC-AUTH-01 | Login screen renders | Visit `/login` | 200, login form shown | Pass |
| TC-AUTH-02 | Register a new user | POST valid name/email/password | User created, authenticated, redirected to dashboard | Pass |
| TC-AUTH-03 | Password reset link | Request reset for an email | Reset notification dispatched | Pass |
| TC-AUTH-04 | Guest is blocked | Visit any guarded screen while logged out | Redirect to `/login` | Pass |

### A2. User management (Phase 1)  — `tests/Feature/UsersPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-USR-01 | Guard: guest | none | GET `/users` | Redirect to login | Pass |
| TC-USR-02 | Guard: admin allowed | admin | GET `/users` | 200, "Users" listed | Pass |
| TC-USR-03 | Guard: supervisor denied | supervisor | GET `/users` | 403 Forbidden | Pass |
| TC-USR-04 | Guard: operator denied | operator | GET `/users` | 403 Forbidden | Pass |
| TC-USR-05 | Create user + role | admin | Fill name/email/password, role=supervisor, Save | User saved with supervisor role | Pass |
| TC-USR-06 | Duplicate email | admin | Create with an existing email | Validation error on `email`, not saved | Pass |
| TC-USR-07 | Password required on create | admin | Create with blank password | Validation error on `password` | Pass |
| TC-USR-08 | Reassign role on edit | admin | Edit a user, change role to supervisor | Old role removed, new role applied | Pass |
| TC-USR-09 | Cannot self-delete | admin | Delete own account | Blocked; account still exists | Pass |

### A3. Vehicles (Phase 2)  — `tests/Feature/VehiclesPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-VEH-01 | Guard: guest | none | GET `/vehicles` | Redirect to login | Pass |
| TC-VEH-02 | Guard: manage-fleet allowed | admin | GET `/vehicles` | 200, "Vehicles" shown | Pass |
| TC-VEH-03 | Guard: no permission | operator | GET `/vehicles` | 403 Forbidden | Pass |
| TC-VEH-04 | Status filter | admin | Filter status = maintenance | Only maintenance vehicles listed | Pass |

### A4. Drivers (Phase 2)  — `tests/Feature/DriversPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-DRV-01 | Guard: guest | none | GET `/drivers` | Redirect to login | Pass |
| TC-DRV-02 | Guard: manage-fleet allowed | admin | GET `/drivers` | 200, "Drivers" shown | Pass |
| TC-DRV-03 | Guard: no permission | operator | GET `/drivers` | 403 Forbidden | Pass |
| TC-DRV-04 | Create driver | admin | Fill name/licence/expiry, Save | Driver saved | Pass |
| TC-DRV-05 | Edit driver | admin | Change name, Save | Name updated | Pass |
| TC-DRV-06 | Delete driver | admin | Delete | Driver removed | Pass |
| TC-DRV-07 | Required fields | admin | Save with blank name/licence/expiry | Validation errors shown, not saved | Pass |
| TC-DRV-08 | Duplicate licence number | admin | Create with an existing `license_number` | Validation error, not saved | Pass |
| TC-DRV-09 | Status filter | admin | Filter status = inactive | Only inactive drivers listed | Pass |

### A5. Routes & stops + map (Phase 3)  — `tests/Feature/RoutesPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-RTE-01 | Guard: guest | none | GET `/routes` | Redirect to login | Pass |
| TC-RTE-02 | Guard: manage-routes allowed | supervisor | GET `/routes` | 200, "Routes" shown | Pass |
| TC-RTE-03 | Guard: no permission | operator | GET `/routes` | 403 Forbidden | Pass |
| TC-RTE-04 | Create route | admin | Fill code/name/points/distance, Save | Route saved | Pass |
| TC-RTE-05 | Duplicate route code | admin | Create with an existing `code` | Validation error, not saved | Pass |
| TC-RTE-06 | Required fields | admin | Save with blanks | Validation errors shown | Pass |
| TC-RTE-07 | Add stops in order | admin | Add two stops | Stops stored with sequence 1, 2 | Pass |
| TC-RTE-08 | Reorder stop | admin | Move 2nd stop up | Order swaps to [Second, First] | Pass |
| TC-RTE-09 | Remove stop resequences | admin | Remove middle of three | Remaining stops renumber 1, 2 | Pass |
| TC-RTE-10 | Delete route cascades stops | admin | Delete a route with stops | Route and its stops removed | Pass |
| TC-RTE-11 | Stop with coordinates | admin | Add stop with lat/lng | Coordinates stored | Pass |
| TC-RTE-12 | Map prompts for coordinates | admin | Open Map when stops have no lat/lng | "No stops have coordinates" prompt shown | Pass |
| TC-RTE-13 | Map renders (Leaflet, no key) | admin | Open Map with coords, no API key | Map container rendered via Leaflet/OpenStreetMap | Pass |

### A6. Fuel logs (Phase 3)  — `tests/Feature/FuelLogsPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-FUEL-01 | Guard: guest | none | GET `/fuel-logs` | Redirect to login | Pass |
| TC-FUEL-02 | Guard: log-fuel allowed | operator | GET `/fuel-logs` | 200, "Fuel Logs" shown | Pass |
| TC-FUEL-03 | Guard: no permission | none (no role) | GET `/fuel-logs` | 403 Forbidden | Pass |
| TC-FUEL-04 | Record fuel | operator | Fill vehicle/litres/cost/odometer/date | Fuel log saved | Pass |
| TC-FUEL-05 | Required fields | operator | Save with blank vehicle/date | Validation errors shown | Pass |
| TC-FUEL-06 | Filter by vehicle | operator | Select a vehicle | Only that vehicle's logs listed | Pass |
| TC-FUEL-07 | Filter by date range | operator | Set From/To window | Only in-range logs listed | Pass |

### A7. Maintenance logs (Phase 3)  — `tests/Feature/MaintenanceLogsPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-MNT-01 | Guard: guest | none | GET `/maintenance-logs` | Redirect to login | Pass |
| TC-MNT-02 | Guard: log-fuel allowed | operator | GET `/maintenance-logs` | 200, "Maintenance Logs" shown | Pass |
| TC-MNT-03 | Guard: no permission | none (no role) | GET `/maintenance-logs` | 403 Forbidden | Pass |
| TC-MNT-04 | Record maintenance | operator | Fill vehicle/type/desc/cost/dates | Maintenance log saved | Pass |
| TC-MNT-05 | Next-due ≥ serviced | operator | Set next_due before serviced | Validation error on `next_due_at` | Pass |
| TC-MNT-06 | Required fields | operator | Save with blanks | Validation errors shown | Pass |
| TC-MNT-07 | Service-due flag (overdue) | — | next_due in the past | "service due" badge shown | Pass |

### A8. Schedules & conflict detection (Phase 4)  — `tests/Feature/SchedulesPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-SCH-01 | Guard: guest | none | GET `/schedules` | Redirect to login | Pass |
| TC-SCH-02 | Guard: manage-schedules allowed | supervisor | GET `/schedules` | 200, "Schedules" shown | Pass |
| TC-SCH-03 | Guard: no permission | operator | GET `/schedules` | 403 Forbidden | Pass |
| TC-SCH-04 | Create valid schedule | admin | Fill route/vehicle/driver/times/dates | Schedule saved | Pass |
| TC-SCH-05 | Arrival after departure | admin | Departure 10:00, arrival 09:00 | Validation error on `arrival_time` | Pass |
| TC-SCH-06 | **Conflict — same vehicle** | admin | New schedule, same vehicle, overlapping time/date | Blocked with conflict message, not saved | Pass |
| TC-SCH-07 | **Conflict — same driver** | admin | New schedule, same driver, overlapping time/date | Blocked with conflict message, not saved | Pass |
| TC-SCH-08 | No conflict (back-to-back) | admin | Same vehicle, 10:00–12:00 after 08:00–10:00 | Saved successfully | Pass |
| TC-SCH-09 | Cancelled ≠ conflict | admin | Overlap an existing *cancelled* schedule | Saved successfully | Pass |
| TC-SCH-10 | Edit ignores self | admin | Edit a schedule's own time | No self-conflict; saved | Pass |
| TC-SCH-11 | Cancel schedule | admin | Cancel | Status becomes `cancelled` | Pass |
| TC-SCH-12 | Generate trips (idempotent) | admin | Generate over a 3-week weekly window twice | 3 trips created; re-run adds none | Pass |
| TC-SCH-13 | Delete cascades trips | admin | Delete a schedule with trips | Schedule and its trips removed | Pass |
| TC-SCH-14 | Update a trip's live status | admin | Trips panel → set trip to "delayed" | Trip status saved; dashboard board reflects it | Pass |
| TC-SCH-15 | Trip update is schedule-scoped | admin | Update a trip belonging to another schedule | Rejected (404); status unchanged | Pass |

### A9. Dashboard (Phase 5)  — `tests/Feature/DashboardTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-DASH-01 | Guard: guest | none | GET `/dashboard` | Redirect to login | Pass |
| TC-DASH-02 | Authenticated landing | any | GET `/dashboard` | 200 | Pass |
| TC-DASH-03 | Summary cards | any | Seed routes/vehicles/trips | Cards show correct counts | Pass |
| TC-DASH-04 | Trip-status board | any | Seed today's trips | Counts grouped by status | Pass |
| TC-DASH-05 | Alerts panel | any | Seed expiring licence + overdue vehicle | Both surfaced as alerts | Pass |

### A9b. Audit log (HR-02)  — `tests/Feature/ActivityLogTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-AUD-01 | Create is logged | admin | Create a vehicle | `created` entry written with actor + label | Pass |
| TC-AUD-02 | Update & delete logged | admin | Update then delete a vehicle | `updated` and `deleted` entries written | Pass |
| TC-AUD-03 | Actor is null for system actions | — | Create a record with no auth user | Entry recorded with no user | Pass |
| TC-AUD-04 | Guard: guest | none | GET `/activity-log` | Redirect to login | Pass |
| TC-AUD-05 | Guard: admin allowed | admin | GET `/activity-log` | 200, log shown | Pass |
| TC-AUD-06 | Guard: supervisor denied | supervisor | GET `/activity-log` | 403 Forbidden | Pass |
| TC-AUD-07 | Guard: operator denied | operator | GET `/activity-log` | 403 Forbidden | Pass |

### A10. Reports & PDF (Phase 5)  — `tests/Feature/ReportsPageTest.php`

| ID | Scenario | Role | Input / steps | Expected result | Result |
|---|---|---|---|---|---|
| TC-RPT-01 | Guard: guest | none | GET `/reports` | Redirect to login | Pass |
| TC-RPT-02 | Guard: view-reports allowed | operator | GET `/reports` | 200, "Reports" shown | Pass |
| TC-RPT-03 | Guard: no permission | none (no role) | GET `/reports` | 403 Forbidden | Pass |
| TC-RPT-04 | Reports honour date range | admin | Set From/To, view trip completion | Figures match in-range data | Pass |
| TC-RPT-05 | **PDF download** | admin | GET `/reports/pdf?from&to` | 200, `content-type: application/pdf` | Pass |
| TC-RPT-06 | PDF gated | none (no role) | GET `/reports/pdf` | 403 Forbidden | Pass |

---

## B. White-box test cases (unit / service logic)

### B1. Schedule conflict logic  — `tests/Unit/ScheduleConflictServiceTest.php`

Pure tests of the overlap maths (no database).

| ID | Scenario | Input | Expected result | Result |
|---|---|---|---|---|
| TC-CONF-01 | Date ranges overlap | Jan 1–31 vs Jan 15–Feb 15 | true | Pass |
| TC-CONF-02 | Date ranges disjoint | Jan vs Feb | false | Pass |
| TC-CONF-03 | Open-ended range | (Jan 1 → ∞) vs later range | true | Pass |
| TC-CONF-04 | Ranges touch at boundary | end == next start | true (overlap) | Pass |
| TC-CONF-05 | Time windows overlap | 06:00–08:00 vs 07:00–09:00 | true | Pass |
| TC-CONF-06 | Back-to-back times | 06:00–07:00 vs 07:00–08:00 | false | Pass |
| TC-CONF-07 | Disjoint times | 06:00–07:00 vs 09:00–10:00 | false | Pass |
| TC-CONF-08 | DB time format tolerated | `08:00` vs `09:00:00` | compares correctly | Pass |

### B2. Reporting aggregations  — `tests/Feature/ReportServiceTest.php`

| ID | Scenario | Input | Expected result | Result |
|---|---|---|---|---|
| TC-AGG-01 | Trip completion + rate | 2 completed / 4 total | rate = 50.0% | Pass |
| TC-AGG-02 | Date-range filter | trips in Jan + Feb, query Feb | only Feb counted | Pass |
| TC-AGG-03 | Route performance | 2 trips, 1 completed on R-1 | trips=2, completed=1, rate=50% | Pass |
| TC-AGG-04 | Fuel trend by month | logs across Jan/Feb | grouped per month, summed | Pass |
| TC-AGG-05 | Vehicle utilisation | 2 trips on V-1 | V-1 → 2 trips | Pass |
| TC-AGG-06 | Maintenance summary | 1 routine + 1 corrective in range, 1 outside | counts 1/1, cost 150.00, out-of-range excluded | Pass |

---

## Summary

| Area | Cases | Result |
|---|---|---|
| Authentication & access control | 4 (+ starter suite) | Pass |
| User management | 9 | Pass |
| Vehicles | 4 | Pass |
| Drivers | 9 | Pass |
| Routes, stops & map | 13 | Pass |
| Fuel logs | 7 | Pass |
| Maintenance logs | 7 | Pass |
| Schedules, conflict detection & trip status | 15 | Pass |
| Audit log (HR-02) | 7 | Pass |
| Dashboard | 5 | Pass |
| Reports & PDF | 6 | Pass |
| Conflict logic (unit) | 8 | Pass |
| Reporting aggregations | 6 | Pass |
| **Whole suite** | **129 tests / 304 assertions** | **Pass** |

> For the report: take screenshots of `php artisan test` output and of each screen named above
> to evidence these results in the Testing section and the user manual appendix.
