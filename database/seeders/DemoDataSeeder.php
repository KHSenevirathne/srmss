<?php

namespace Database\Seeders;

use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\FuelLog;
use App\Models\MaintenanceLog;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data so every screen has something to show during development and the
 * Week-8 demo. Run with:  php artisan db:seed --class=DemoDataSeeder
 *
 * The authorisation model (roles + permissions) lives in its own
 * RolesAndPermissionsSeeder — this seeder only creates the three demo logins
 * (one per role) plus example fleet/route/schedule records.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Build roles + permissions first (idempotent, safe to re-run).
        $this->call(RolesAndPermissionsSeeder::class);

        // One demo login per role — password is "password" for all three.
        $this->createUser('Depot Admin', 'admin@srmss.test', 'admin');
        $this->createUser('Shift Supervisor', 'supervisor@srmss.test', 'supervisor');
        $this->createUser('Fuel Operator', 'operator@srmss.test', 'operator');

        // Example records are created once; skip if the fleet already exists so
        // re-seeding never trips the unique-key constraints.
        if (Vehicle::query()->exists()) {
            return;
        }

        $vehicles = collect([
            ['registration_number' => 'NA-1234', 'type' => 'bus', 'seating_capacity' => 54, 'mileage' => 182000],
            ['registration_number' => 'NB-5678', 'type' => 'coach', 'seating_capacity' => 49, 'mileage' => 96000, 'status' => 'available'],
            ['registration_number' => 'NC-9012', 'type' => 'minibus', 'seating_capacity' => 28, 'mileage' => 47000, 'status' => 'maintenance'],
        ])->map(fn ($v) => Vehicle::create($v));

        $drivers = collect([
            ['name' => 'Sunil Perera', 'license_number' => 'DL-001', 'license_expiry' => now()->addMonths(8)],
            ['name' => 'Kamal Silva', 'license_number' => 'DL-002', 'license_expiry' => now()->addDays(20)], // expiring soon
            ['name' => 'Nimal Fernando', 'license_number' => 'DL-003', 'license_expiry' => now()->addYears(2)],
        ])->map(fn ($d) => Driver::create($d));

        $route = BusRoute::create([
            'code' => 'R-138', 'name' => 'Colombo – Galle',
            'start_point' => 'Colombo Fort', 'end_point' => 'Galle',
            'total_distance_km' => 119.0, 'service_type' => 'semi-luxury',
        ]);

        // Stops with real-ish coordinates so the route renders on the map.
        $stops = [
            ['Colombo Fort', 6.9344, 79.8428],
            ['Panadura', 6.7130, 79.9073],
            ['Kalutara', 6.5854, 79.9607],
            ['Aluthgama', 6.4300, 80.0000],
            ['Hikkaduwa', 6.1395, 80.1063],
            ['Galle', 6.0535, 80.2210],
        ];
        foreach ($stops as $i => [$name, $lat, $lng]) {
            RouteStop::create([
                'bus_route_id' => $route->id, 'name' => $name, 'sequence' => $i + 1,
                'latitude' => $lat, 'longitude' => $lng,
            ]);
        }

        Schedule::create([
            'bus_route_id' => $route->id,
            'vehicle_id' => $vehicles[0]->id,
            'driver_id' => $drivers[0]->id,
            'frequency' => 'daily',
            'departure_time' => '06:30',
            'arrival_time' => '09:30',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
        ]);

        // A little fuel history for the first bus.
        FuelLog::create(['vehicle_id' => $vehicles[0]->id, 'liters' => 120.5, 'cost' => 45000, 'odometer' => 181500, 'logged_at' => now()->subDays(14)]);
        FuelLog::create(['vehicle_id' => $vehicles[0]->id, 'liters' => 115.0, 'cost' => 43200, 'odometer' => 182000, 'logged_at' => now()->subDays(2)]);

        // Maintenance: one routine record with a future due date, and one that is
        // already overdue so the "service due" flag is visible in the demo.
        MaintenanceLog::create(['vehicle_id' => $vehicles[0]->id, 'type' => 'routine', 'description' => 'Oil & filter change', 'cost' => 8500, 'serviced_at' => now()->subMonths(2), 'next_due_at' => now()->addMonth()]);
        MaintenanceLog::create(['vehicle_id' => $vehicles[2]->id, 'type' => 'corrective', 'description' => 'Brake pad replacement', 'cost' => 15000, 'serviced_at' => now()->subMonths(4), 'next_due_at' => now()->subWeek()]);
    }

    /** Create (or update) a demo user and give them exactly one role. */
    private function createUser(string $name, string $email, string $role): void
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );

        $user->syncRoles([$role]);
    }
}
