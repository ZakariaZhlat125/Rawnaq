<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Service\Models\ServiceEmployee;
use Modules\Category\Models\Category;
use App\Models\User;
use App\Models\Branch;
use Modules\Employee\Models\BranchEmployee;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CombinedImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void 
     */
    public function run()
    {
        $this->importEmployees();
        $this->importServices();
    }

    private function importEmployees()
    {
        $csvFile = fopen(__DIR__ . '/employees.csv', 'r');
        $firstLine = true;
        
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
            if ($firstLine) {
                $firstLine = false;
                continue;
            }

            $serviceName = $data[0] ?? '';
            $branchName = $data[1] ?? '';
            $fullName = $data[2] ?? '';

            if (empty(trim($fullName))) continue;

            // Find or Create Branch
            $branchId = null;
            if (!empty($branchName)) {
                $branch = Branch::where('name', 'like', "%{$branchName}%")->first();
                if (!$branch) {
                    $branch = Branch::create([
                        'name' => ['ar' => $branchName, 'en' => $branchName],
                        'slug' => Str::slug($branchName) . '-' . uniqid(),
                        'status' => 1
                    ]);
                }
                $branchId = $branch->id;
            }

            // Prepare employee data
            $nameParts = explode(' ', trim($fullName));
            $firstName = array_shift($nameParts);
            $lastName = implode(' ', $nameParts);
            $email = Str::slug($firstName . $lastName) . uniqid() . '@example.com';

            // Find or create User
            $user = User::where('first_name', $firstName)
                        ->where('last_name', $lastName)
                        ->first();
                        
            if (!$user) {
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'password' => null,
                    'status' => 1
                ]);
                $user->assignRole('employee');
            }

            // Assign to branch
            if ($branchId) {
                BranchEmployee::firstOrCreate([
                    'employee_id' => $user->id,
                    'branch_id' => $branchId
                ], [
                    'is_primary' => 1
                ]);
            }
        }
        fclose($csvFile);
        $this->command->info('Employees imported successfully.');
    }

    private function importServices()
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

            // Find or Create Service
            $service = Service::where('name', 'like', "%{$name}%")->first();
            
            if (!$service) {
                $service = Service::create([
                    'name' => ['ar' => $name, 'en' => $name],
                    'slug' => Str::slug($name) . '-' . uniqid(),
                    'default_price' => $price,
                    'duration_min' => $duration,
                    'category_id' => $categoryId,
                    'status' => $status,
                    'is_visible' => 1,
                ]);
            }

            // Attach Branches
            if (!empty($branches)) {
                $branchIds = explode(',', $branches);
                foreach($branchIds as $bId) {
                    if (is_numeric(trim($bId))) {
                        ServiceBranches::firstOrCreate([
                            'service_id' => $service->id,
                            'branch_id' => trim($bId),
                        ], [
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
                        ServiceEmployee::firstOrCreate([
                            'service_id' => $service->id,
                            'employee_id' => trim($empId),
                        ]);
                    }
                }
            }
        }

        fclose($csvFile);
        $this->command->info('Services imported successfully.');
    }
}
