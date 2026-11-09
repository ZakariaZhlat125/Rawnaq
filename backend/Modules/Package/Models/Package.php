<?php

namespace Modules\Package\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Branch;
use Carbon\Carbon;
use Spatie\Translatable\HasTranslations;

class Package extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'packages';

    public $translatable = ['name', 'description'];

    public const TYPE_PACKAGE = 'package';
    public const TYPE_OFFER = 'offer';
    
    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'package_price' => 'double',
    ];

    const CUSTOM_FIELD_MODEL = 'Modules\Package\Models\Package';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Package\database\factories\PackageFactory::new();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }


    public function scopeBranch($query)
    {
        $branch_id = request()->selected_session_branch_id;
        if (isset($branch_id)) {
            return $query->where('branch_id', $branch_id);
        } else {
            return $query->whereNotNull('branch_id');
        }
    }

    public function employees()
    {
        return $this->belongsToMany(PackageEmployee::class, 'package_employees', 'package_id', 'employee_id');
    }

    public function employee()
    {
        return $this->hasMany(PackageEmployee::class, 'package_id');
    }

    public function services()
    {
        return $this->belongsToMany(PackageService::class, 'package_services', 'package_id', 'service_id');
    }

    public function service()
    {
        return $this->hasMany(PackageService::class, 'package_id');
    }

    public function pricingSummary(): array
    {
        $total = $this->service->sum(fn ($row) => (int) round((float) $row->service_price * 100) * max(0, (int) $row->qty));
        $price = (int) round((float) $this->package_price * 100);
        return ['services_total_price' => $total / 100, 'savings_amount' => max(0, $total - $price) / 100];
    }

    public function userPackage(){
        return $this->hasMany(UserPackage::class, 'package_id');
    }

    public function scopeBasePackages($query)
    {
        return $query->where(function ($q) {
            $q->where('type', self::TYPE_PACKAGE)->orWhereNull('type');
        });
    }

    public function scopeActiveBasePackages($query)
    {
        $today = Carbon::today();

        return $query->basePackages()
            ->where(function ($dateQuery) use ($today) {
                $dateQuery->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($dateQuery) use ($today) {
                $dateQuery->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            });
    }

    public function scopeOfferPackages($query)
    {
        $today = Carbon::today();

        return $query->where('type', self::TYPE_OFFER)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function scopeActiveForFrontend($query)
    {
        $today = Carbon::today();

        return $query->where(function ($q) use ($today) {
            $q->where(function ($inner) use ($today) {
                $inner->where(function ($t) {
                    $t->where('type', self::TYPE_PACKAGE)->orWhereNull('type');
                })
                ->where(function ($date) use ($today) {
                    $date->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                })
                ->where(function ($date) use ($today) {
                    $date->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
                });
            })
            ->orWhere(function ($inner) use ($today) {
                $inner->where('type', self::TYPE_OFFER)
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            });
        });
    }

    public function isActiveForFrontend(): bool
    {
        $today = Carbon::today();
        $type = $this->type ?? self::TYPE_PACKAGE;

        if ($type === self::TYPE_OFFER) {
            return $this->start_date && $this->end_date
                && Carbon::parse($this->start_date)->lte($today)
                && Carbon::parse($this->end_date)->gte($today);
        }

        $startOk = empty($this->start_date) || Carbon::parse($this->start_date)->lte($today);
        $endOk = empty($this->end_date) || Carbon::parse($this->end_date)->gte($today);

        return $startOk && $endOk;
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('package_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

}
