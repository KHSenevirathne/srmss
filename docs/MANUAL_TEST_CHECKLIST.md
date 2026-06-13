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
| 1.4 | As **operator**, look at the sidebar | Only Dashboard, Reports, Fuel Logs, Maintenance visible |
| 1.5 | As **operator**, open `/users` directly | 403 Forbidden |
| 1.6 | As **supervisor**, open `/users` directly | 403 Forbidden |
| 1.7 | Log out via the user menu | Back to login; `/dashboard` redirects again |

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
| 3.2 | Set status filter to *Maintenance* | Only NC-9012 listed |
| 3.3 | Search a partial reg no | List filters live as you type |
| 3.4 | Drivers → add with licence expiry ~2 weeks away | Row shows amber **expiring soon** badge |
| 3.5 | Drivers → add with a duplicate licence no | Unique-validation error |
| 3.6 | Edit a driver's name, Save | Row updates without page reload |
| 3.7 | Delete a driver | Confirm dialog → row disappears, green flash |

## 4. Routes & stops

| # | Step | Expected |
|---|---|---|
| 4.1 | Routes → add with duplicate code `R-138` | Unique-validation error |
| 4.2 | Open **Stops** on R-138 | 6 stops listed in numbered order |
| 4.3 | Move a middle stop ↑ then ↓ | Order swaps and swaps back; numbers stay 1..n |
| 4.4 | Remove a stop | Remaining stops renumber with no gaps |
| 4.5 | Add a stop with lat/lng out of range (e.g. 999) | Validation error |
| 4.6 | Click **Map** (no API key configured) | Friendly "add GOOGLE_MAPS_API_KEY" notice — no crash |
| 4.7 | Delete a test route that has stops | Route and its stops are gone |

## 5. Schedules, conflicts & trips (the core demo)

| # | Step | Expected |
|---|---|---|
| 5.1 | Add schedule: same **vehicle** as R-138's, time 07:00–09:00, overlapping dates | Red conflict banner naming the clash; not saved |
| 5.2 | Same again but different vehicle, same **driver** | Conflict banner again (driver clash) |
| 5.3 | Same vehicle but 09:30–11:30 (back-to-back is fine) | Saves successfully |
| 5.4 | Set arrival earlier than departure | "Arrival must be after departure" error |
| 5.5 | **Cancel** a schedule, then create an overlapping one | Allowed — cancelled schedules don't conflict |
| 5.6 | Open **Trips** on a schedule → **Generate trips** | Trips appear for the validity window; pressing again adds none |
| 5.7 | Set today's trip to **Delayed**, open Dashboard | Within ~15s the Delayed tile increments — no page reload |

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
| 7.2 | Reports → set a range covering the seeded data | Trip completion, route table, charts all populate |
| 7.3 | Set a range in the far past | All zeros / empty states, no errors |
| 7.4 | **Download PDF** | A PDF downloads; numbers match the screen |
| 7.5 | As a role-less user (if you create one): `/reports` | 403 |

## 8. Responsive

| # | Step | Expected |
|---|---|---|
| 8.1 | Shrink browser to phone width (or DevTools device mode) | Sidebar collapses to hamburger; cards stack; tables scroll horizontally |
| 8.2 | Open a modal at phone width | Fields stack one per row; modal scrolls, buttons reachable |
