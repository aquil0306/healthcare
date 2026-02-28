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
        Schema::create('icd10_code_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icd10_code_id')->constrained('icd10_codes')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->integer('priority')->default(1)->comment('Priority/weight for this mapping (1=primary, 2=secondary, etc.)');
            $table->boolean('is_primary')->default(false)->comment('Is this the primary department for this code?');
            $table->text('notes')->nullable()->comment('Optional notes about this mapping');
            $table->timestamps();
            
            // Ensure unique combination of code and department
            $table->unique(['icd10_code_id', 'department_id']);
            
            // Indexes for performance
            $table->index('icd10_code_id');
            $table->index('department_id');
            $table->index('is_primary');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icd10_code_department');
    }
};

