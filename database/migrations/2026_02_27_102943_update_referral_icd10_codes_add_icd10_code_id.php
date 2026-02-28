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
        Schema::table('referral_icd10_codes', function (Blueprint $table) {
            $table->foreignId('icd10_code_id')->nullable()->after('referral_id')->constrained('icd10_codes')->onDelete('restrict');
            // Keep 'code' field for backward compatibility
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referral_icd10_codes', function (Blueprint $table) {
            $table->dropForeign(['icd10_code_id']);
            $table->dropColumn('icd10_code_id');
        });
    }
};
