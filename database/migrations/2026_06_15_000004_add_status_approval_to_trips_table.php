<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trip status gains a 'cancelled' value (staff-only) and a lightweight
     * approval queue: when a driver requests a status change it is parked in
     * pending_status until a staff member approves it. The status column is
     * widened from an enum to a plain string (the allowed set is enforced in
     * TripManager) so the value list stays in one place and is portable.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('status')->default('scheduled')->change();

            $table->string('pending_status')->nullable()->after('status');
            $table->foreignId('status_requested_by')->nullable()->after('pending_status')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('status_requested_at')->nullable()->after('status_requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_requested_by');
            $table->dropColumn(['pending_status', 'status_requested_at']);
            $table->enum('status', ['scheduled', 'on_time', 'delayed', 'completed'])->default('scheduled')->change();
        });
    }
};
