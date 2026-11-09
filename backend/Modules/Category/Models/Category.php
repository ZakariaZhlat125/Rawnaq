<?php

namespace Modules\Category\Models;

use App\Models\BaseModel;
use App\Models\Traits\HasSlug;
use App\Trait\CustomFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Spatie\Translatable\HasTranslations;

class Category extends BaseModel
{
    use CustomFieldsTrait;
    use HasFactory;
    use HasSlug;
    use SoftDeletes;
    use HasTranslations;

    protected $table = 'categories';

    //protected $fillable = ['slug', 'is_visible', 'is_frozen', 'sort_order', 'name', 'status', 'parent_id'];

    protected $fillable = ['slug', 'is_visible', 'is_frozen', 'sort_order', 'name', 'status', 'parent_id', 'price_from', 'durMin', 'durMax', 'image', 'description'];

    const CUSTOM_FIELD_MODEL = 'Modules\Category\Models\Category';

    protected $appends = ['feature_image'];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'name' => 'array',
        'summary' => 'array',
        'description' => 'array',
        'desc2' => 'array',
        'sort_order' => 'integer',
        'price_from' => 'float',
        'durMin' => 'integer',
        'durMax' => 'integer',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Category\database\factories\CategoryFactory::new();
    }

    public function mainCategory()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function subCategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
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

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && !empty($media) ? $media : default_feature_image();
    }

    public function branches()
    {
        return $this->belongsToMany(\App\Models\Branch::class, 'branch_category')->withPivot('is_visible')->withTimestamps();
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
