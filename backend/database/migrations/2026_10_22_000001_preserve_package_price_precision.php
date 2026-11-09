<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite already preserves fractional values with integer affinity.
        if (DB::getDriverName() !== 'mysql') return;
        foreach (['packages', 'user_packages'] as $table) {
            if (Schema::hasColumn($table, 'package_price')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `package_price` DECIMAL(12,2) NOT NULL");
            }
        }
        if (Schema::hasColumn('package_services', 'discounted_price')) {
            // A discounted line total may not divide evenly by its quantity.
            DB::statement('ALTER TABLE `package_services` MODIFY `discounted_price` DECIMAL(14,6) NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        // Keep the widened money columns: narrowing would discard saved halalas.
    }
};
