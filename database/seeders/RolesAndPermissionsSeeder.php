<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Single source of truth for the SRMSS authorisation model (RBAC).
 *
 * Four roles map to the case-study actors:
 *   - admin       full control, including user management
 *   - supervisor  day-to-day operations + viewing, but cannot manage users
 *   - operator    can log fuel/maintenance, view, and approve driver requests
 *   - driver      a login-enabled driver : sees only their own trips and can
 *                 request status changes (which a staff member must approve)
 *
 * Screens and actions are guarded against these *permissions* (not roles
 * directly) via @can('...') in Blade and the `can:` middleware on routes, so
 * the role -> permission map can change here without editing any screen.
 *
 * Idempotent: safe to run repeatedly (findOrCreate + syncPermissions).
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** Every permission the system recognises (each guards one module). */
    public const PERMISSIONS = [
        'manage-users',        // create users, assign roles
        'manage-fleet',        // drivers + vehicles CRUD
        'manage-routes',       // routes + stops CRUD
        'manage-schedules',    // timetables + assignment + setting any trip status (incl. cancelled)
        'log-fuel',            // fuel + maintenance logging
        'view-reports',        // dashboard + reports (read-only surface)
        'view-trips',          // the central trips board (read-only for operators)
        'view-own-trips',      // a driver's scoped view of only their own trips
        'request-trip-status', // a driver requests a (non-cancelled) status change, pending approval
        'approve-trip-status', // staff approve/reject a driver's requested status change
    ];

    /** Which permissions each role holds. admin holds all of them. */
    public const ROLES = [
        'admin' => self::PERMISSIONS,
        'supervisor' => ['manage-fleet', 'manage-routes', 'manage-schedules', 'log-fuel', 'view-reports', 'view-trips', 'approve-trip-status'],
        'operator' => ['log-fuel', 'view-reports', 'view-trips', 'approve-trip-status'],
        'driver' => ['view-own-trips', 'request-trip-status'],
    ];

    public function run(): void
    {
        // spatie caches permissions; clear it so a re-seed reflects any changes.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role)->syncPermissions($permissions);
        }
    }
}
