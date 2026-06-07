<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NOTE: table is "bus_routes" (model BusRoute) to avoid clashing with
// Laravel's Route facade. See docs/DATA_MODEL.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bus_routes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // e.g. R-138
            $table->string('name');                     // Colombo - Galle
            $table->string('start_point');
            $table->string('end_point');
            $table->decimal('total_distance_km', 6, 2)->default(0);
            $table->string('service_type')->default('normal'); // normal, semi-luxury, luxury
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bus_routes');
    }
};
