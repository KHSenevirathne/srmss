# Data model

Schema is defined in `starter/database/migrations/`. This is the reference Claude Code (and
your Class diagram) should follow.

## Entities & key fields

- **User** (from starter kit) — `name, email, password`; roles via spatie (`admin`,
  `supervisor`, `operator`).
- **Driver** — `name, phone, email, address, license_number (unique), license_expiry,
  weekly_hours, status (active|inactive)`.
- **Vehicle** — `registration_number (unique), type (bus|minibus|coach), seating_capacity,
  mileage, status (available|in_service|maintenance)`.
- **BusRoute** *(table `bus_routes`)* — `code (unique), name, start_point, end_point,
  total_distance_km, service_type, status`.
- **RouteStop** — `bus_route_id, name, sequence, latitude?, longitude?`.
- **Schedule** — `bus_route_id, vehicle_id, driver_id, frequency (daily|weekly|monthly),
  departure_time, arrival_time, valid_from, valid_to?, status`.
- **Trip** — `schedule_id, trip_date, status (scheduled|on_time|delayed|completed),
  actual_departure?, actual_arrival?`.
- **FuelLog** — `vehicle_id, trip_id?, liters, cost, odometer?, logged_at`.
- **MaintenanceLog** — `vehicle_id, type (routine|corrective), description, cost,
  serviced_at, next_due_at?`.

## Relationships

- BusRoute **1—∞** RouteStop (ordered by `sequence`)
- BusRoute **1—∞** Schedule
- Vehicle **1—∞** Schedule, **1—∞** FuelLog, **1—∞** MaintenanceLog
- Driver **1—∞** Schedule
- Schedule **1—∞** Trip
- Trip **1—∞** FuelLog (a fuel log may optionally belong to a trip)

## Naming note (important)

The route model is **`BusRoute`** on table **`bus_routes`**. Do not call it `Route` — that
collides with Laravel's `Route` facade and causes confusing import bugs. This is also cleaner
on your Class diagram.

## Suggested derived/computed bits (build as model methods or services)

- `Driver::licenseExpiringSoon($days = 30)` — already on the model; surface on the dashboard.
- "Service due" = `MaintenanceLog.next_due_at <= today` for a vehicle's latest record.
- Conflict detection (Phase 4) compares a schedule's vehicle/driver + time window + validity
  range against existing schedules — keep this in `app/Services/ScheduleConflictService.php`.
