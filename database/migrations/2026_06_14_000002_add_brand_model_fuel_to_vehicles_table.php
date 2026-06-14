<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Optional descriptive fields + fuel type, added after the core columns.
            $table->string('brand')->nullable()->after('type');
            $table->string('model')->nullable()->after('brand');
            $table->string('fuel_type')->nullable()->after('model'); // diesel, petrol, electric, hybrid, cng
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['brand', 'model', 'fuel_type']);
        });
    }
};
