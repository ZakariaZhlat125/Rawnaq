<?php

namespace App\Imports;

use Modules\World\Models\City;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CityImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new City([
            'name'       => json_encode(['ar' => $row['name_ar'],'en' => $row['name_en']], JSON_UNESCAPED_UNICODE),
            'status'     => 1 ,
            'created_by'     => 1 ,
            'updated_by'     => 1 ,
        ]);
    }
}



