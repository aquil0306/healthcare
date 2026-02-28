<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL/MariaDB, we need to drop the enum and change to string
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN channel VARCHAR(50) NOT NULL");
        } else {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('channel', 50)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN channel ENUM('email', 'sms', 'in_app') NOT NULL");
        } else {
            Schema::table('notifications', function (Blueprint $table) {
                $table->enum('channel', ['email', 'sms', 'in_app'])->change();
            });
        }
    }
};
