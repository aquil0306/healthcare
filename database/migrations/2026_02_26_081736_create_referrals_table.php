<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('hospital_id')->constrained()->onDelete('cascade');
            $table->enum('urgency', ['routine', 'urgent', 'emergency'])->default('routine');
            $table->enum('status', ['submitted', 'triaged', 'assigned', 'acknowledged', 'in_progress', 'completed', 'cancelled'])->default('submitted');
            $table->text('clinical_notes');
            $table->string('department')->nullable();
            $table->decimal('ai_confidence_score', 5, 2)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('external_referral_id')->nullable(); // For duplicate detection
            $table->timestamps();
            
            $table->index(['hospital_id', 'external_referral_id']);
            $table->index('status');
            $table->index('urgency');
            $table->index('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
