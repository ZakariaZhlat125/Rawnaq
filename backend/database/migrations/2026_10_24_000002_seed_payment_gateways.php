<?php

use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Deploys run `migrate` but never `db:seed`, so the gateways are seeded here. The
    // seeder copies today's .env credentials into the database once, so checkout keeps
    // working on the release that switches to database credentials.
    public function up(): void
    {
        (new PaymentGatewaySeeder())->run();
    }

    public function down(): void
    {
        DB::table('payment_gateways')->delete();
    }
};
