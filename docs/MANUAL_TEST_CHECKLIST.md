# Manual test checklist (click-through QA)

Run `php artisan serve`, open http://127.0.0.1:8000. Demo logins all use password `password`:
`admin@srmss.test` · `supervisor@srmss.test` · `operator@srmss.test`.
Work through the steps in order — each row is a small, independently checkable behaviour.

## 1. Authentication & access control

| # | Step | Expected |
|---|---|---|
| 1.1 | Open `/dashboard` while logged out | Redirected to `/login` |
| 1.2 | Log in as `admin@srmss.test` | Lands on the Depot Dashboard |
| 1.3 | Log in with a wrong password | Error shown, stays on login |
| 1.4 | As **operator**, look at the sidebar | Only Dashboard, Reports, Fuel Logs, Maintenance visible — no Operations CRUD, no Administration |
| 1.5 | As **operator**, open `/users` directly | 403 Forbidden |
| 1.6 | As **supervisor**, open `/users` directly | 403 Forbidden |
| 1.7 | As **supervisor**, open `/activity-log` directly | 403 Forbidden (audit log is admin-only) |
| 1.8 | As **operator**, open `/vehicles`, `/routes`, `/schedules` directly | 403 Forbidden on each |
| 1.9 | As **admin**, look at the sidebar | Administration group (Users + Activity Log) visible in addition to all Operations items |
| 1.10 | Log out via the user menu | Back to login; `/dashboard` redirects again |

## 2. User management (admin)

| # | Step | Expected |
|---|---|---|
| 2.1 | Users → **+ Add User**, fill name/email/password, role = supervisor, Save | User appears with supervisor badge |
| 2.2 | Add another user with the **same email** | "Email already taken" error; not saved |
| 2.3 | Create a user with a blank password | Validation error on password |
| 2.4 | Edit that user, change role to operator, Save (password blank) | Role badge changes; old password still works |
| 2.5 | Find your own row | Delete is replaced with "(you)" — cannot self-delete |

## 3. Vehicles & drivers (admin or supervisor)

| # | Step | Expected |
|---|---|---|
| 3.1 | Vehicles → add one with an existing reg no | Unique-validation error |
| 3.1a | Add a vehicle, leave **Brand**/**Model** blank, pick a **Fuel Type** | Saves (brand/model optional, stored null); fuel type persists. Edit it back — fuel type shows the saved value |
| 3.2 | Set status filter to *Maintenance* | Only NC-9012 listed |
| 3.3 | Search a partial reg no | List filters live as you type |
| 3.4 | Drivers → add with licence expiry ~2 weeks away | Row shows amber **expiring soon** badge |
| 3.5 | Drivers → add with a duplicate licence no | Unique-validation error |
| 3.5a | Drivers → add, leave **NIC** blank | Validation error — NIC is required |
| 3.5b | Drivers → add with a valid NIC | Saves; an **Employee Number** (E-001, E-002, …) is auto-assigned and shown in the Emp No column. The form's Employee Number box is read-only |
| 3.5c | Drivers → add with a duplicate NIC | Unique-validation error |
| 3.6 | Edit a driver's name, Save | Row updates without page reload; employee number unchanged |
| 3.7 | Delete a driver | Confirm dialog → row disappears, green flash |

## 4. Routes & stops

| # | Step | Expected |
|---|---|---|
| 4.1 | Routes → add with duplicate code `R-138` | Unique-validation error |
| 4.2 | Open **Stops** on R-138 | 6 stops listed in numbered order |
| 4.3 | Move a middle stop ↑ then ↓ | Order swaps and swaps back; numbers stay 1..n |
| 4.4 | Remove a stop | Remaining stops renumber with no gaps |
| 4.5 | Add a stop with lat/lng out of range (e.g. 999) | Validation error |
| 4.6 | Click **Map** on R-138 (stops have coords, no API key) | Leaflet/OpenStreetMap renders with numbered markers + a connecting line; no key needed, no crash |
| 4.7 | Click **Map** on a route whose stops have no coordinates | Friendly "add latitude/longitude" notice instead of a map — no crash |
| 4.8 | Delete a test route that has stops | Route and its stops are gone |

## 5. Schedules, conflicts & trips (the core demo)

| # | Step | Expected |
|---|---|---|
| 5.1 | Add schedule: same **vehicle** as R-138's, time 07:00–09:00, overlapping dates | Red conflict banner naming the clash; not saved |
| 5.2 | Same again but different vehicle, same **driver** | Conflict banner again (driver clash) |
| 5.3 | Same vehicle but 09:30–11:30 (back-to-back is fine) | Saves successfully |
| 5.4 | Set arrival earlier than departure | "Arrival must be after departure" error |
| 5.4a | Try to assign a vehicle whose status is **Maintenance** (e.g. NC-9012) | Option is greyed in the dropdown; if forced, save is blocked: "…is maintenance and can't be assigned" — only available vehicles schedule |
| 5.4b | Open the **Driver** dropdown in Add Schedule | Only **active** drivers listed, each shown as "Name (E-00x)". An inactive driver is not listed; assigning one is blocked: "…driver is inactive and can't be assigned" |
| 5.5 | **Cancel** a schedule, then create an overlapping one | Allowed — cancelled schedules don't conflict |
| 5.6 | Open **Trips** on a schedule → **Generate trips** | Trips appear for the validity window; pressing again adds none |
| 5.7 | Set today's trip to **Delayed**, open Dashboard, click **Refresh** | The Delayed tile increments after clicking Refresh (the dashboard no longer auto-polls; refresh is manual) |

## 6. Fuel & maintenance (works as operator too)

| # | Step | Expected |
|---|---|---|
| 6.1 | Fuel Logs → add (vehicle, litres, cost, date) | Row appears; flash message |
| 6.2 | Filter by vehicle + a date range with no logs | Empty state shown |
| 6.3 | Add fuel log linked to a **Trip** (optional dropdown) | Saves; trip linkage stored |
| 6.4 | Maintenance → add with next-due **before** serviced date | Validation error |
| 6.5 | Add maintenance with next-due in the past | Red **service due** badge on the row |
| 6.6 | Check Dashboard alerts | That vehicle listed under "Vehicles — service due" |

## 7. Dashboard & reports

| # | Step | Expected |
|---|---|---|
| 7.1 | Dashboard cards | Counts match the data (routes, available buses, etc.) |
| 7.1a | Vehicle highlights row (Most/Least Used, Highest/Lowest Mileage) | On seed: Most Used NA-1234 (4 trips), Least Used NC-9012 (0 trips), Highest Mileage NA-1234 (182,000 km), Lowest Mileage NC-9012 (47,000 km) |
| 7.2 | Reports → set a range covering the seeded data | Trip completion, route table, charts all populate |
| 7.3 | Set a range in the far past | All zeros / empty states, no errors |
| 7.4 | **Download PDF** | A PDF downloads; numbers match the screen |
| 7.5 | As a role-less user (if you create one): `/reports` | 403 |

## 8. Audit trail / activity log (admin only)

The log is written automatically by an Eloquent trait + auth event listeners — there is no
"add log" button. These steps confirm actions elsewhere produce entries here.

| # | Step | Expected |
|---|---|---|
| 8.1 | As **admin**, open **Activity Log** from the Administration group | Table of recent activity loads; newest first |
| 8.2 | Your recent login appears | A `login` row for your user (grey badge), description "Logged in" |
| 8.3 | Add/edit/delete any record (e.g. a Vehicle), then reopen Activity Log | `created` (green) / `updated` (blue) / `deleted` (red) rows for that subject, with your name |
| 8.4 | Check the **Subject** column on a CRUD row | Shows the model + id, e.g. `Vehicle #5`; auth rows show `—` |
| 8.5 | Filter **Event** = Deleted | Only delete rows remain |
| 8.6 | Filter **User** = another user | Only that user's activity shown; combine with Event filter |
| 8.7 | Confirm there is **no** add/edit/delete control on this screen | Read-only by design — append-only audit record |
| 8.8 | Log out, then log back in, reopen the log | A `logout` then `login` row recorded for the round-trip |

## 9. Responsive

| # | Step | Expected |
|---|---|---|
| 9.1 | Shrink browser to phone width (or DevTools device mode) | Sidebar collapses to hamburger; cards stack; tables scroll horizontally |
| 9.2 | Open a modal at phone width | Fields stack one per row; modal scrolls, buttons reachable |
