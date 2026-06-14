<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('nic')->nullable()->unique()->after('name');
            $table->string('employee_number')->nullable()->unique()->after('nic');
        });

        // Backfill a short employee number (E-001, E-002, …) for existing drivers,
        // so the auto-generated value is present even on data created before this.
        $n = 0;
        foreach (DB::table('drivers')->orderBy('id')->pluck('id') as $id) {
            $n++;
            DB::table('drivers')->where('id', $id)->update([
                'employee_number' => 'E-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['nic', 'employee_number']);
        });
    }
};
