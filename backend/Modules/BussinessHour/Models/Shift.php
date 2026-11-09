<?php

namespace Modules\BussinessHour\Models;

// use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory;


    protected $table = 'shifts';
    protected $fillable = ['name', 'status'];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\BussinessHour\database\factories\ShiftFactory::new();
    }
   
    public function getStatusLabelAttribute()
    {
        return $this->status ? true : false;
            
        // return $this->status = 1 
        //     ? '<span class="badge bg-success">مفعل</span>'
        //     : '<span class="badge bg-danger">غير مفعل</span>';
    }


}
