<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Service\Models\ServiceEmployee;
use Modules\Category\Models\Category;
use Illuminate\Support\Str;

class ServicesImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void 
     */
    public function run()
    {
        $csvFile = fopen(__DIR__ . '/services.csv', 'r');
        $firstLine = true;
        
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
            if ($firstLine) {
                $firstLine = false;
                continue;
            }

            $name = $data[0] ?? '';
            if (empty(trim($name))) continue;

            $priceStr = $data[1] ?? '0';
            $durationStr = $data[2] ?? '0';
            $categoryName = $data[3] ?? '';
            $branches = $data[4] ?? '';
            $employees = $data[5] ?? '';
            $statusStr = $data[6] ?? 'Inactive';

            // Parse values
            $price = (float) str_replace(['SAR', ','], '', trim($priceStr));
            $duration = (int) str_replace([' Min', ' '], '', trim($durationStr));
            $status = (strtolower(trim($statusStr)) === 'active') ? 1 : 0;

            // Find or Create category
            $categoryId = null;
            if (!empty($categoryName)) {
                $category = Category::where('name', 'like', "%{$categoryName}%")->first();
                
                if (!$category) {
                    $category = Category::create([
                        'name' => ['ar' => $categoryName, 'en' => $categoryName],
                        'slug' => Str::slug($categoryName) . '-' . uniqid(),
                        'status' => 1,
                        'is_visible' => 1,
                    ]);
                }
                
                $categoryId = $category->id;
            }

            // Create Service
            $service = Service::create([
                'name' => ['ar' => $name, 'en' => $name], // Storing as array for spatie translatable
                'slug' => Str::slug($name) . '-' . uniqid(),
                'default_price' => $price,
                'duration_min' => $duration,
                'category_id' => $categoryId,
                'status' => $status,
                'is_visible' => 1,
            ]);

            // Attach Branches
            if (!empty($branches)) {
                $branchIds = explode(',', $branches);
                foreach($branchIds as $bId) {
                    if (is_numeric(trim($bId))) {
                        ServiceBranches::create([
                            'service_id' => $service->id,
                            'branch_id' => trim($bId),
                            'service_price' => $price,
                            'duration_min' => $duration,
                        ]);
                    }
                }
            }

            // Attach Employees
            if (!empty($employees)) {
                $empIds = explode(',', $employees);
                foreach($empIds as $empId) {
                    if (is_numeric(trim($empId))) {
                        ServiceEmployee::create([
                            'service_id' => $service->id,
                            'employee_id' => trim($empId),
                        ]);
                    }
                }
            }
        }

        fclose($csvFile);
    }
}
