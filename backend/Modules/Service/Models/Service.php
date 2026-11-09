<?php

namespace Modules\Service\Models;

use App\Models\BaseModel;
use App\Models\Traits\HasSlug;
use App\Trait\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Models\Category;
use Spatie\Translatable\HasTranslations;
use App\Models\Branch;

class Service extends BaseModel
{
    use CustomFieldsTrait;
    use HasFactory;
    use HasSlug;
    use SoftDeletes;
    use HasTranslations;
    protected $table = 'services';

    protected $fillable = ['slug', 'odoo_id', 'name', 'description', 'duration_min', 'default_price', 'category_id', 'sub_category_id', 'is_visible', 'is_frozen', 'status'];

    protected $appends = ['feature_image'];

    public $translatable = ['name'];

    protected $casts = [
        'name' => 'array',
        'duration_min' => 'integer',
        'default_price' => 'double',
        'service_price' => 'double',
        'description' => 'array',
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'status' => 'integer',
        'is_frozen' => 'integer',

    ];

    const CUSTOM_FIELD_MODEL = 'Modules\Service\Models\Service';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Service\database\factories\ServiceFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($table) {
            //
        });

        static::saving(function ($table) {
            //
        });

        static::updating(function ($table) {
            //
        });
    }

    public function employee()
    {
        return $this->hasMany(ServiceEmployee::class, 'service_id', 'id');
    }

    public function gallery()
    {
        return $this->hasMany(ServiceGallery::class, 'id', 'feature_image');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sub_category()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function branches()
    {
        return $this->hasMany(ServiceBranches::class, 'service_id');
    }

    public function branchies()
    {
        return $this->belongsToMany(Branch::class, 'service_branches');
    }

    protected function getFeatureImageAttribute()
    {
        /*$media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();*/
        
            $media = $this->getFirstMedia('feature_image');

    // لو الـ Media موجودة والملف فعلاً موجود
    if ($media && $media->exists()) {
        $path = $media->getPath();

        if (file_exists($path)) {
            return $media->getUrl();
        }
    }

    // لو الملف غير موجود → استخدم صورة الـ Category
    if ($this->category) {
        return $this->category->feature_image;
    }

    return default_feature_image();

    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
