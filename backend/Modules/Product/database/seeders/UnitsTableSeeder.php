<?php

namespace Modules\Product\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Unit;

class UnitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'gm',
            ],
            [
                'name' => 'kg',
            ],
            [
                'name' => 'pcs',
            ],
            [
                'name' => 'ml',
            ],
            [
                'name' => 'pack',
            ],
            [
                'name' => 'ltr',
            ],
            [
                'name' => 'each',
            ],
            [
                'name' => '250gm',
            ],
        ];

        if (config('sami.is_dummy_data')) {
            foreach ($data as $key => $value) {
                Unit::create($value);
            }
        }
    }
}
