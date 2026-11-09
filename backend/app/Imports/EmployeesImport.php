<?php

namespace App\Imports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Hash;
use Modules\Wallet\Models\Wallet;
use Modules\Employee\Models\BranchEmployee;
use Modules\Service\Models\ServiceEmployee;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Commission\Models\EmployeeCommission;
use App\Models\StaffWorkingHour;

use Carbon\Carbon;

class EmployeesImport implements ToModel, WithHeadingRow
{
public function model(array $row)
{
    $user = User::create([
        'first_name' => $row['first_name'] ?? '',
        'last_name'  => $row['last_name'] ?? '',
        'email'      => $row['email'],
        'mobile'      => $row['mobile'] ?? null,   
        'gender'     => 'female',
        'password'   => Hash::make('123456789'),
        'email_verified_at' => Carbon::now(),
    ]);

    $user->syncRoles(['employee']);

    Wallet::create([
        'title' => $user->first_name . ' ' . $user->last_name,
        'user_id' => $user->id,
        'amount' => 0,
    ]);

        BranchEmployee::create([
            'employee_id' => $user->id,
            'branch_id'   => 8,
            'shift_id'    => $row['shif'] == 'Afternoon' ? 6 : 7, // 6 aft
        ]);
        

    $Hairs = range(1, 99);
    
    // $Hammams = range(100, 108);
        
    $Facial = range(109, 119);
    
    $Massages = range(120, 153);
        
    $Nails = range(154, 178);
    
    $Nails_Facial_Hair = array_merge($Hairs, $Nails , $Facial);

    $Facial_Massage = array_merge( $Massages , $Facial);

    if ($row['service'] !== 'Cleaner') {
        
        if ( $row['service'] == 'Massage') {
            foreach ($Massages as $Massage) {
                ServiceEmployee::create([
                    'employee_id' => $user->id,
                    'service_id'  => $Massage,
                ]);
            }
        }
        if ( $row['service'] == 'Nails') {
            foreach ($Nails as $Nail) {
                ServiceEmployee::create([
                    'employee_id' => $user->id,
                    'service_id'  => $Nail,
                ]);
            }
        }
        if ( $row['service'] == 'Facial & Massage') {
            foreach ($Facial_Massage as $item) {
                ServiceEmployee::create([
                    'employee_id' => $user->id,
                    'service_id'  => $item,
                ]);
            }
        }
        if ( $row['service'] == 'Hair') {
            foreach ($Hairs as $Hair) {
                ServiceEmployee::create([
                    'employee_id' => $user->id,
                    'service_id'  => $Hair,
                ]);
            } 
        }
        if ( $row['service'] == 'Hammam') {
            foreach ($Hammams as $Hammam) {
                ServiceEmployee::create([
                    'employee_id' => $user->id,
                    'service_id'  => $Hammam,
                ]);
            }
        }
    
    }

    // ربط العمولة
    EmployeeCommission::create([
        'employee_id' => $user->id,
        'commission_id' => 1,
    ]);


    return $user;
}

}
