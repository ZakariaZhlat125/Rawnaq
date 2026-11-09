<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nullify passwords for all users with employee role to enforce OTP login only
        try {
            $employeeRole = DB::table('roles')->where('name', 'employee')->first();
            if ($employeeRole) {
                $employeeUserIds = DB::table('model_has_roles')
                    ->where('role_id', $employeeRole->id)
                    ->where('model_type', User::class)
                    ->pluck('model_id');

                if ($employeeUserIds->isNotEmpty()) {
                    DB::table('users')
                        ->whereIn('id', $employeeUserIds)
                        ->update(['password' => null]);
                }
            }
        } catch (\Throwable $e) {
            // Silently continue if tables not yet populated in test runner
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse for password nullification
    }
};
