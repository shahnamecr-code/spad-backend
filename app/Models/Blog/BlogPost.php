<?php

namespace App\Models\Blog;

use App\Models\Admin;
use App\Models\File;
use App\Models\Sidebar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use function PHPUnit\Framework\isNull;

class BlogPost extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded=[];

    protected $casts=[
        'show_sidebar'=>'boolean'
    ];

    protected $dates=[
        'created_at','updated_at','start_time','end_time'
    ];



    /*public function getStartTimeAttribute()
    {
        $item=$this->attributes['start_time'];
        if($item){
            return time_to_array($item);
        }
        return $item;
    }

    public function setStartTimeAttribute($val)
    {
        if(isset($val)){
            $val=date_to_timestamp($val);
        }
        $this->attributes['start_time']=$val;
    }

    public function getEndTimeAttribute()
    {
        $item=$this->attributes['end_time'];
        if($item){
            return time_to_array($item);
        }
        return $item;
    }

    public function setEndTimeAttribute($val)
    {
        if(isset($val)){
            $val=date_to_timestamp($val);
        }
        $this->attributes['end_time']=$val;
    }*/

    public function categories()
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_post_categories','blog_post_id','blog_category_id','id','id');
    }

    public function scopeReady($query)
    {
        return $query->where('status',1)->where(function ($query){
            $query->whereNull('start_time')->orWhere('start_time','<',Carbon::now());
        })->where(function ($query){
            $query->whereNull('end_time')->orWhere('end_time','>',Carbon::now());
        });
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function sidebar()
    {
        return $this->belongsTo(Sidebar::class);
    }

    public function mainImg()
    {
        return $this->belongsTo(File::class,'image');
    }
}
