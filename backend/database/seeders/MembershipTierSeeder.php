<?php

namespace Database\Seeders;

use App\Services\MembershipService;
use Illuminate\Database\Seeder;

class MembershipTierSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(MembershipService::class);
        $service->seedDefaultTiers();

        $this->command->info('✅ تم إنشاء فئات العضوية: Member - Silver - Gold - Platinum');
    }
}
