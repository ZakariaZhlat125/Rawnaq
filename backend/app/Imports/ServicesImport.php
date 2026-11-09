<?php

namespace App\Imports;

use Modules\Service\Models\Service;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ServicesImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     */
    public function model(array $row)
    {
                $keywords = [
            49 => ['Hair'],                // Hair services
            50 => ['Make up'], // Skin care and waxing
            51 => ['Nail Polish'],              // Nail care services
            52 => ['Aroma Masssage'],            // Massage services
            53 => ['Bath'],     // Moroccan Hammam Service
        ];

        // دمج كل النصوص اللي ممكن نحلل منها الفئة
        $text = $row['categories'];

        // تحديد الـ category_id بناءً على الكلمات
        $categoryId = null;
        foreach ($keywords as $id => $words) {
            foreach ($words as $word) {
                if (str_contains($text, $word)) {
                    $categoryId = $id;
                    break 2; // نوقف البحث أول ما نلاقي تطابق
                }
            }
        }

        return new Service([
            'odoo_id'         => $row['odoo_id'],
            'slug'          => str_replace(' ', '-', strtolower(trim($row['name_en']))),
            'name'          => ['ar' => $row['name_ar'] ?? '','en' => $row['name_en'] ?? '',],
            'description'   => $row['description'],
            'duration_min'  => $row['duration_min'],
            'default_price' => $row['default_price'],
            'status'        => 1,
            'category_id'   => $categoryId,
        ]);
    }
}