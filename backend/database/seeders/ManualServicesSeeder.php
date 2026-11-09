<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Category\Models\Category;
use App\Models\Branch;
use Illuminate\Support\Str;

class ManualServicesSeeder extends Seeder
{
    public function run()
    {
        $categoriesData = [
            'البديكير' => [
                ['name' => 'عرض بدكير ومنكير', 'price' => 57.50],
                ['name' => 'قص اظافر القدمين وازالة الجلد', 'price' => 40.00],
                ['name' => 'قص اظافر القدمين واليدين وازالة الجلد', 'price' => 100.00],
                ['name' => 'قص اظافر اليدين', 'price' => 30.00],
            ],
            'الحلاقة' => [
                ['name' => 'استشوار', 'price' => 15.00],
                ['name' => 'استشوار سيراميك', 'price' => 30.00],
                ['name' => 'استشوار مع قص شعر', 'price' => 40.00],
                ['name' => 'بروتين ثقيل', 'price' => 250.00],
                ['name' => 'بروتين خفيف', 'price' => 150.00],
                ['name' => 'تنظيف بشرة بالذهب والكولاجين', 'price' => 150.00],
                ['name' => 'تنظيف بشرة كلاسيك', 'price' => 100.00],
                ['name' => 'تنظيف فروة شعر', 'price' => 50.00],
                ['name' => 'حلاقة اطفال', 'price' => 15.00],
                ['name' => 'حلاقة شعر وذقن', 'price' => 35.00],
                ['name' => 'حمام زيت', 'price' => 30.00],
                ['name' => 'شمع اذن', 'price' => 10.00],
                ['name' => 'شمع خدود', 'price' => 10.00],
                ['name' => 'شمع كامل', 'price' => 20.00],
                ['name' => 'صبغة ذقن', 'price' => 20.00],
                ['name' => 'صبغة سكسوكة', 'price' => 15.00],
                ['name' => 'صبغة شعر ايطالي', 'price' => 30.00],
                ['name' => 'صبغة شعر بيجن', 'price' => 50.00],
                ['name' => 'سنفرة', 'price' => 20.00],
                ['name' => 'سنفرة عميقة', 'price' => 35.00],
                ['name' => 'عرض البهية اليوم الوطني', 'price' => 26.00],
                ['name' => 'فتلة خيط', 'price' => 10.00],
                ['name' => 'فرد شعر', 'price' => 40.00],
                ['name' => 'قص وحلاقة الشعر', 'price' => 20.00],
                ['name' => 'قص وحلاقة ذقن', 'price' => 20.00],
                ['name' => 'كيرلي', 'price' => 200.00],
                ['name' => 'كيرلي خفيف', 'price' => 100.00],
                ['name' => 'لصقة انف', 'price' => 5.00],
            ],
            'الحمام المغربي' => [
                ['name' => 'حمام مغربي كلاسيكي', 'price' => 100.00],
                ['name' => 'حمام مغربي ملكي', 'price' => 150.00],
                ['name' => 'عرض حمام مغربي كلاسيكي', 'price' => 57.50],
            ],
            'المساج' => [
                ['name' => 'عرض مساج استرخائي', 'price' => 57.50],
                ['name' => 'مساج احجار', 'price' => 150.00],
                ['name' => 'مساج استرخائي', 'price' => 100.00],
                ['name' => 'مساج سويدي', 'price' => 120.00],
                ['name' => 'مساج كاسات الهواء', 'price' => 150.00],
            ]
        ];

        // جلب الفروع الموجودة - أو إنشاؤها بالحقول الصحيحة
        $branchQuraish = Branch::where('name', 'like', '%قريش%')->first();
        if (!$branchQuraish) {
            $branchQuraish = Branch::create([
                'name' => ['ar' => 'قريش', 'en' => 'Quraish'],
                'slug' => 'quraish-' . uniqid(),
                'status' => 1,
                'contact_email' => 'quraish@example.com',
                'contact_number' => '0000000000',
            ]);
        }

        $branchBaghdadiyah = Branch::where('name', 'like', '%البغدادية%')->first();
        if (!$branchBaghdadiyah) {
            $branchBaghdadiyah = Branch::create([
                'name' => ['ar' => 'البغدادية', 'en' => 'Al Baghdadiyah'],
                'slug' => 'al-baghdadiyah-' . uniqid(),
                'status' => 1,
                'contact_email' => 'baghdadiyah@example.com',
                'contact_number' => '0000000000',
            ]);
        }

        foreach ($categoriesData as $categoryName => $services) {
            // إنشاء أو جلب القسم (Category)
            $category = Category::firstOrCreate(
                ['name->ar' => $categoryName],
                [
                    'name' => ['ar' => $categoryName, 'en' => $categoryName],
                    'slug' => Str::slug($categoryName) . '-' . uniqid(),
                    'status' => 1,
                    'is_visible' => 1,
                ]
            );

            foreach ($services as $serviceData) {
                // إنشاء أو جلب الخدمة
                $service = Service::firstOrCreate(
                    ['name->ar' => $serviceData['name']],
                    [
                        'name' => ['ar' => $serviceData['name'], 'en' => $serviceData['name']],
                        'slug' => Str::slug($serviceData['name']) . '-' . uniqid(),
                        'default_price' => $serviceData['price'],
                        'duration_min' => 30, // وقت افتراضي
                        'category_id' => $category->id,
                        'status' => 1,
                        'is_visible' => 1,
                    ]
                );

                // ربط الخدمة بالفرع الأول (قريش)
                ServiceBranches::firstOrCreate([
                    'service_id' => $service->id,
                    'branch_id' => $branchQuraish->id,
                ], [
                    'service_price' => $serviceData['price'],
                    'duration_min' => 30,
                ]);

                // ربط الخدمة بالفرع الثاني (البغدادية)
                ServiceBranches::firstOrCreate([
                    'service_id' => $service->id,
                    'branch_id' => $branchBaghdadiyah->id,
                ], [
                    'service_price' => $serviceData['price'],
                    'duration_min' => 30,
                ]);
            }
        }

        $this->command->info('تم إضافة جميع الفئات والخدمات بنجاح.');
    }
}
