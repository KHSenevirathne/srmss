<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Append-only audit trail, written by the LogsActivity trait plus login/logout events. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');                 // created | updated | deleted | login | logout
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
