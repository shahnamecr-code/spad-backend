<?php

namespace App\Models;

use App\Scopes\AscendingOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable=['name','slug','parent','structure','listSetting','searchItems','singleLayout','icon','mainFilters','crmFields','order'];

    protected $casts=[
        'structure'=>'array',
        'listSetting'=>'array',
        'searchItems'=>'array',
        'singleLayout'=>'array',
        'mainFilters'=>'array',
        'crmFields'=>'array',
    ];

    protected $appends=[
        'is_last'
    ];

    public function layout()
    {
        return $this->hasMany(CategoryLayout::class);
    }

    public function parent_category()
    {
        return $this->belongsTo(self::class,'parent','id');
    }

    public function direct_children()
    {
        return $this->hasMany(self::class, 'parent','id');
    }

    public function children()
    {
        return $this->direct_children()->with('children');
    }

    public function customer_children()
    {
        return $this->direct_children()->select(['id','name','parent','slug','icon'])->with(['customer_children','icon_file:id,name,directory_id']);
    }

    public function parent_category_name()
    {
        return $this->belongsTo(self::class,'parent','id')->select(['id','name','parent','slug']);
    }

    public function recursive_parent_category_name()
    {
        return $this->parent_category_name()->select(['id','name','parent','slug'])->with('recursive_parent_category_name');
    }

    public function getIsLastAttribute()
    {
        return $this->structure && count($this->structure)>=2;
    }

    public function icon_file()
    {
        return $this->belongsTo(File::class,'icon');
    }

    protected static function booted()
    {
        static::addGlobalScope(new AscendingOrder);
    }

}
