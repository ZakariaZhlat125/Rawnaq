<?php

namespace Modules\Bank\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Bank\Database\factories\BankFactory;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Bank extends BaseModel implements HasMedia
{
    use HasFactory;
    protected $table = 'banks';

    protected $fillable = [
        'user_id',
        'bank_name',
        'branch_name',
        'account_no',
        'ifsc_no',
        'mobile_no',
        'aadhar_no',
        'pan_no',
        'status',
        'is_default',
    ];

    protected $guarded = [
        'updated_by', 
        'created_by', 
        'updated_at', 
        'created_at'
    ];

    public $timestamps = true;

    // Register media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bank_attachments')->singleFile();
    }
}
