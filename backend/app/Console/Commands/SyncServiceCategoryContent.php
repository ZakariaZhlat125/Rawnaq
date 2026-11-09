<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\Service\Models\Service;

/**
 * أداة تصحيح لمرة واحدة: (1) تعيد تسمية الخدمات الفرعية اللي محتواها فعليًا
 * بيخص قسم تاني (مكياج/رموش تحت قسم "تنظيف البشرة") لتبقى أسماء عناية بشرة
 * حقيقية، و(2) تخلي صورة كل خدمة نشطة = صورة القسم (category) اللي هي تابعة له.
 */
class SyncServiceCategoryContent extends Command
{
    protected $signature = 'services:sync-category-content {--dry-run : اعرض التغييرات من غير ما تحفظها}';

    protected $description = 'إعادة تسمية الخدمات المصنّفة غلط تحت "تنظيف البشرة" وضبط صورة كل خدمة على صورة قسمها';

    /**
     * الخدمات (id => [ar, en]) اللي فعليًا مكياج/رموش مش تنظيف بشرة، تحت category_id=50.
     */
    private array $renames = [
        109 => ['ar' => 'جلسة تنظيف بشرة فاخرة', 'en' => 'Luxury Facial Cleansing Session'],
        110 => ['ar' => 'جلسة تنظيف بشرة مسائية', 'en' => 'Evening Facial Cleansing Session'],
        111 => ['ar' => 'جلسة تجديد بشرة مكثفة', 'en' => 'Intensive Skin Renewal Session'],
        112 => ['ar' => 'جلسة شد ونضارة البشرة', 'en' => 'Skin Tightening & Glow Session'],
        113 => ['ar' => 'جلسة تفتيح بشرة بسيطة', 'en' => 'Simple Skin Brightening Session'],
        114 => ['ar' => 'جلسة عناية بشرة متكاملة', 'en' => 'Complete Skin Care Session'],
        115 => ['ar' => 'جلسة ترطيب عميق للبشرة', 'en' => 'Deep Skin Hydration Session'],
        116 => ['ar' => 'جلسة تنظيف بشرة سريعة', 'en' => 'Quick Facial Cleansing Session'],
        117 => ['ar' => 'جلسة تقشير بشرة خفيف', 'en' => 'Light Skin Exfoliation Session'],
        118 => ['ar' => 'جلسة عناية بشرة للأطفال', 'en' => 'Gentle Skin Care Session for Children'],
        119 => ['ar' => 'جلسة تنظيف بشرة تحضيرية للمناسبات', 'en' => 'Pre-Event Facial Cleansing Session'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->renameMismatchedServices($dryRun);
        $this->syncServiceImagesToCategory($dryRun);

        $this->info($dryRun ? 'انتهى العرض التجريبي (dry-run) — مفيش حاجة اتحفظت.' : 'تم تنفيذ التعديلات وحفظها.');

        return self::SUCCESS;
    }

    private function renameMismatchedServices(bool $dryRun): void
    {
        $this->info('== إعادة تسمية الخدمات المصنّفة غلط ==');

        foreach ($this->renames as $id => $names) {
            $service = Service::withoutGlobalScopes()->whereNull('deleted_at')->find($id);

            if (! $service) {
                $this->warn("تخطّي #{$id}: مش موجودة أو محذوفة");

                continue;
            }

            $oldAr = $service->getTranslation('name', 'ar');
            $this->line("#{$id}: \"{$oldAr}\" → \"{$names['ar']}\"");

            if ($dryRun) {
                continue;
            }

            $service->setTranslation('name', 'ar', $names['ar']);
            $service->setTranslation('name', 'en', $names['en']);
            $service->save();
        }
    }

    private function syncServiceImagesToCategory(bool $dryRun): void
    {
        $this->info('== ضبط صورة كل خدمة على صورة قسمها ==');

        $categories = Category::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNull('parent_id')
            ->get();

        foreach ($categories as $category) {
            $categoryMedia = $category->getFirstMedia('feature_image');

            if (! $categoryMedia) {
                $this->warn("القسم #{$category->id} ({$category->getTranslation('name', 'ar')}) مفيهوش صورة — تخطّي خدماته");

                continue;
            }

            $services = Service::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('category_id', $category->id)
                ->get();

            $this->line("القسم #{$category->id} ({$category->getTranslation('name', 'ar')}): {$services->count()} خدمة");

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($services, $categoryMedia) {
                foreach ($services as $service) {
                    $service->clearMediaCollection('feature_image');

                    $newMedia = $categoryMedia->replicate();
                    $newMedia->model_type = Service::class;
                    $newMedia->model_id = $service->id;
                    $newMedia->uuid = (string) Str::uuid();
                    $newMedia->save();
                }
            });
        }
    }
}
