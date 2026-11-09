<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void { \App\Services\EmployeeAccess::syncPermissions(); }
    // A rollback must not silently re-grant sensitive privileges to employees.
    public function down(): void {}
};
